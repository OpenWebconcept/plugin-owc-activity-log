<?php

declare(strict_types=1);

/**
 * GravityFormsListener unit tests.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCActivityLog\Database\ActivityRepository;
use OWCActivityLog\Listeners\GravityFormsListener;

// ---------------------------------------------------------------------------
// get_hooks()
// ---------------------------------------------------------------------------

it(
	'registers the gform_post_update_form_meta hook',
	function () {
		$repo     = Mockery::mock( ActivityRepository::class );
		$listener = new GravityFormsListener( $repo );
		$hooks    = $listener->get_hooks();

		expect( $hooks )->toHaveKey( 'gform_post_update_form_meta' );
		expect( $hooks['gform_post_update_form_meta'] )->toHaveCount( 3 );
	}
);

// ---------------------------------------------------------------------------
// on_pre_update_form_meta()
// ---------------------------------------------------------------------------

it(
	'ignores meta writes for confirmations and notifications',
	function () {
		$repo     = Mockery::mock( ActivityRepository::class );
		$listener = Mockery::mock( GravityFormsListener::class, array( $repo ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$listener->shouldNotReceive( 'get_existing_form' );

		$result = $listener->on_pre_update_form_meta( array( 'foo' => 'bar' ), 5, 'confirmations' );

		expect( $result )->toBe( array( 'foo' => 'bar' ) );
	}
);

it(
	'snapshots the existing form fields before the first write, keyed by form id',
	function () {
		$repo     = Mockery::mock( ActivityRepository::class );
		$listener = Mockery::mock( GravityFormsListener::class, array( $repo ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$listener->shouldReceive( 'get_existing_form' )
			->once()
			->with( 5 )
			->andReturn(
				array(
					'id'     => 5,
					'title'  => 'Contact',
					'fields' => array(
						array(
							'id'    => 1,
							'label' => 'Name',
							'type'  => 'text',
						),
					),
				)
			);

		$form_meta = array(
			'id'    => 5,
			'title' => 'Contact',
		);

		// A single request can write display_meta more than once (e.g. the form
		// editor re-saving the form to apply server-side defaults). Only the
		// first call should fetch and cache the "before" snapshot.
		$listener->on_pre_update_form_meta( $form_meta, 5, 'display_meta' );
		$result = $listener->on_pre_update_form_meta( $form_meta, 5, 'display_meta' );

		expect( $result )->toBe( $form_meta );
	}
);

// ---------------------------------------------------------------------------
// on_post_update_form_meta() — scheduling
// ---------------------------------------------------------------------------

it(
	'does not schedule a flush when the pre-hook never ran for this form id',
	function () {
		$listener = new GravityFormsListener( Mockery::mock( ActivityRepository::class ) );

		WP_Mock::expectActionNotAdded( 'shutdown', Mockery::type( 'Closure' ) );

		$listener->on_post_update_form_meta( '{"id":9,"fields":[]}', 9, 'display_meta' );
	}
);

it(
	'does not schedule a flush for confirmations or notifications meta',
	function () {
		$listener = new GravityFormsListener( Mockery::mock( ActivityRepository::class ) );

		WP_Mock::expectActionNotAdded( 'shutdown', Mockery::type( 'Closure' ) );

		$listener->on_post_update_form_meta( '{}', 5, 'notifications' );
	}
);

it(
	'schedules the shutdown flush after a real write',
	function () {
		$listener = Mockery::mock( GravityFormsListener::class, array( Mockery::mock( ActivityRepository::class ) ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$listener->shouldReceive( 'get_existing_form' )->once()->andReturn(
			array(
				'id'     => 5,
				'fields' => array(
					array(
						'id'    => 1,
						'label' => 'Name',
					),
				),
			)
		);

		WP_Mock::expectActionAdded( 'shutdown', Mockery::type( 'Closure' ) );

		$listener->on_pre_update_form_meta( array( 'id' => 5 ), 5, 'display_meta' );
		// Saved twice within the same request: the dedup guard means only the
		// net result is diffed/logged once by flush(), proven separately below.
		$listener->on_post_update_form_meta( '{"id":5,"fields":[]}', 5, 'display_meta' );
		$listener->on_post_update_form_meta( '{"id":5,"fields":[]}', 5, 'display_meta' );
	}
);

// ---------------------------------------------------------------------------
// flush()
// ---------------------------------------------------------------------------

it(
	'logs the diff between the first "before" and the last "after" state, once',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
			->once()
			->with(
				Mockery::on(
					function ( $data ) {
						if ( 'gravity_forms' !== $data['group'] || 'form_fields_changed' !== $data['action'] ) {
							return false;
						}

						$meta = json_decode( $data['meta'], true );

						return isset( $meta['changed_fields']['1'], $meta['changed_fields']['2'], $meta['changed_fields']['3'] )
							&& 'changed' === $meta['changed_fields']['1']['status']
							&& isset( $meta['changed_fields']['1']['changed']['label'] )
							&& 'removed' === $meta['changed_fields']['2']['status']
							&& 'added' === $meta['changed_fields']['3']['status'];
					}
				)
			)
			->andReturn( true );

		$listener = Mockery::mock( GravityFormsListener::class, array( $repo ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$listener->shouldReceive( 'get_existing_form' )
			->once()
			->with( 5 )
			->andReturn(
				array(
					'id'     => 5,
					'title'  => 'Contact',
					'fields' => array(
						array(
							'id'    => 1,
							'label' => 'Name',
							'type'  => 'text',
						),
						array(
							'id'    => 2,
							'label' => 'Removed field',
							'type'  => 'text',
						),
					),
				)
			);

		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'owc_activity_log_get_settings' )->andReturn( array( 'log_ip' => false ) );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$unchanged_meta = array(
			'id'     => 5,
			'title'  => 'Contact',
			'fields' => array(
				array(
					'id'    => 1,
					'label' => 'Name',
					'type'  => 'text',
				),
				array(
					'id'    => 2,
					'label' => 'Removed field',
					'type'  => 'text',
				),
			),
		);

		$changed_meta = array(
			'id'     => 5,
			'title'  => 'Contact',
			'fields' => array(
				array(
					'id'    => 1,
					'label' => 'Full name',
					'type'  => 'text',
				),
				array(
					'id'    => 3,
					'label' => 'New field',
					'type'  => 'email',
				),
			),
		);

		$listener->on_pre_update_form_meta( $unchanged_meta, 5, 'display_meta' );
		// First write in the request: nothing actually changed yet.
		$listener->on_post_update_form_meta( wp_json_encode( $unchanged_meta ), 5, 'display_meta' );
		// Second write in the same request: this is the real edit.
		$listener->on_post_update_form_meta( wp_json_encode( $changed_meta ), 5, 'display_meta' );

		$listener->flush();
	}
);

it(
	'summarizes what changed in plain language in the log message',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
			->once()
			->with(
				Mockery::on(
					fn( $data ) => 'Form "Contact" updated: "Full name" changed; "New field" added; "Old field" removed.' === $data['message']
				)
			)
			->andReturn( true );

		$listener = Mockery::mock( GravityFormsListener::class, array( $repo ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$listener->shouldReceive( 'get_existing_form' )->once()->andReturn(
			array(
				'id'     => 5,
				'title'  => 'Contact',
				'fields' => array(
					array(
						'id'    => 1,
						'label' => 'Name',
						'type'  => 'text',
					),
					array(
						'id'    => 2,
						'label' => 'Old field',
						'type'  => 'text',
					),
				),
			)
		);

		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'owc_activity_log_get_settings' )->andReturn( array( 'log_ip' => false ) );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$after = array(
			'id'     => 5,
			'title'  => 'Contact',
			'fields' => array(
				array(
					'id'    => 1,
					'label' => 'Full name',
					'type'  => 'text',
				),
				array(
					'id'    => 3,
					'label' => 'New field',
					'type'  => 'email',
				),
			),
		);

		$listener->on_pre_update_form_meta( array( 'id' => 5 ), 5, 'display_meta' );
		$listener->on_post_update_form_meta( wp_json_encode( $after ), 5, 'display_meta' );

		$listener->flush();
	}
);

it(
	'ignores changes to field properties that are not on the tracked list',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldNotReceive( 'insert' );

		$listener = Mockery::mock( GravityFormsListener::class, array( $repo ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$listener->shouldReceive( 'get_existing_form' )->once()->andReturn(
			array(
				'id'     => 5,
				'fields' => array(
					array(
						'id'               => 1,
						'label'            => 'Name',
						'type'             => 'text',
						'layoutGroupId'    => 'group-a',
						'conditionalLogic' => array( 'actionType' => 'show' ),
					),
				),
			)
		);

		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		// Only the untracked properties differ; label/type stay the same.
		$after = array(
			'id'     => 5,
			'fields' => array(
				array(
					'id'               => 1,
					'label'            => 'Name',
					'type'             => 'text',
					'layoutGroupId'    => 'group-b',
					'conditionalLogic' => array( 'actionType' => 'hide' ),
				),
			),
		);

		$listener->on_pre_update_form_meta( array( 'id' => 5 ), 5, 'display_meta' );
		$listener->on_post_update_form_meta( wp_json_encode( $after ), 5, 'display_meta' );

		$listener->flush();
	}
);

it(
	'does not log anything when the net result of the request is no change',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldNotReceive( 'insert' );

		$listener = Mockery::mock( GravityFormsListener::class, array( $repo ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$form = array(
			'id'     => 5,
			'title'  => 'Contact',
			'fields' => array(
				array(
					'id'    => 1,
					'label' => 'Name',
					'type'  => 'text',
				),
			),
		);

		$listener->shouldReceive( 'get_existing_form' )->once()->andReturn( $form );

		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$listener->on_pre_update_form_meta( $form, 5, 'display_meta' );
		$listener->on_post_update_form_meta( wp_json_encode( $form ), 5, 'display_meta' );

		$listener->flush();
	}
);

it(
	'only records the individual properties that actually changed on a field',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
			->once()
			->with(
				Mockery::on(
					function ( $data ) {
						$meta   = json_decode( $data['meta'], true );
						$change = $meta['changed_fields']['1']['changed'] ?? array();

						// "type" did not change, so only "label" should be reported.
						return array( 'label' ) === array_keys( $change );
					}
				)
			)
			->andReturn( true );

		$listener = Mockery::mock( GravityFormsListener::class, array( $repo ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$listener->shouldReceive( 'get_existing_form' )->once()->andReturn(
			array(
				'id'     => 5,
				'fields' => array(
					array(
						'id'    => 1,
						'label' => 'Name',
						'type'  => 'text',
					),
				),
			)
		);

		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'owc_activity_log_get_settings' )->andReturn( array( 'log_ip' => false ) );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$after = array(
			'id'     => 5,
			'fields' => array(
				array(
					'id'    => 1,
					'label' => 'Full name',
					'type'  => 'text',
				),
			),
		);

		$listener->on_pre_update_form_meta( array( 'id' => 5 ), 5, 'display_meta' );
		$listener->on_post_update_form_meta( wp_json_encode( $after ), 5, 'display_meta' );

		$listener->flush();
	}
);

it(
	'logs a form_created event for a form that did not exist before this request',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
			->once()
			->with(
				Mockery::on(
					function ( $data ) {
						if ( 'gravity_forms' !== $data['group'] || 'form_created' !== $data['action'] ) {
							return false;
						}

						$meta = json_decode( $data['meta'], true );

						return isset( $meta['changed_fields']['1'], $meta['changed_fields']['2'] )
							&& 'added' === $meta['changed_fields']['1']['status']
							&& 'added' === $meta['changed_fields']['2']['status'];
					}
				)
			)
			->andReturn( true );

		$listener = Mockery::mock( GravityFormsListener::class, array( $repo ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// No existing form yet: GFAPI::get_form() returns false for a form ID
		// that hasn't had its display_meta written yet.
		$listener->shouldReceive( 'get_existing_form' )
			->once()
			->with( 9 )
			->andReturn( false );

		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'owc_activity_log_get_settings' )->andReturn( array( 'log_ip' => false ) );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$new_form = array(
			'id'     => 9,
			'title'  => 'New form',
			'fields' => array(
				array(
					'id'    => 1,
					'label' => 'Name',
					'type'  => 'text',
				),
				array(
					'id'    => 2,
					'label' => 'Email',
					'type'  => 'email',
				),
			),
		);

		$listener->on_pre_update_form_meta( $new_form, 9, 'display_meta' );
		$listener->on_post_update_form_meta( wp_json_encode( $new_form ), 9, 'display_meta' );

		$listener->flush();
	}
);

it(
	'summarizes the initial fields in plain language in the creation log message',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
			->once()
			->with(
				Mockery::on(
					fn( $data ) => 'Form "New form" created: "Name", "Email" added.' === $data['message']
				)
			)
			->andReturn( true );

		$listener = Mockery::mock( GravityFormsListener::class, array( $repo ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$listener->shouldReceive( 'get_existing_form' )->once()->andReturn( false );

		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'owc_activity_log_get_settings' )->andReturn( array( 'log_ip' => false ) );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$new_form = array(
			'id'     => 9,
			'title'  => 'New form',
			'fields' => array(
				array(
					'id'    => 1,
					'label' => 'Name',
					'type'  => 'text',
				),
				array(
					'id'    => 2,
					'label' => 'Email',
					'type'  => 'email',
				),
			),
		);

		$listener->on_pre_update_form_meta( $new_form, 9, 'display_meta' );
		$listener->on_post_update_form_meta( wp_json_encode( $new_form ), 9, 'display_meta' );

		$listener->flush();
	}
);

it(
	'ignores field key order when detecting changes',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldNotReceive( 'insert' );

		$listener = Mockery::mock( GravityFormsListener::class, array( $repo ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$listener->shouldReceive( 'get_existing_form' )->once()->andReturn(
			array(
				'id'     => 5,
				'fields' => array(
					array(
						'id'    => 1,
						'label' => 'Name',
						'type'  => 'text',
					),
				),
			)
		);

		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$reordered_after = array(
			'id'     => 5,
			'fields' => array(
				array(
					'type'  => 'text',
					'id'    => 1,
					'label' => 'Name',
				),
			),
		);

		$listener->on_pre_update_form_meta( array( 'id' => 5 ), 5, 'display_meta' );
		$listener->on_post_update_form_meta( wp_json_encode( $reordered_after ), 5, 'display_meta' );

		$listener->flush();
	}
);
