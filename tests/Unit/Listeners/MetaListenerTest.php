<?php

declare(strict_types=1);

/**
 * MetaListener unit tests.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCActivityLog\Database\ActivityRepository;
use OWCActivityLog\Listeners\MetaListener;

// Shared helper to build a listener with a mocked repository.
function owc_activity_log_make_meta_listener( ActivityRepository $repo ): MetaListener
{
	WP_Mock::userFunction( 'owc_activity_log_get_settings' )->andReturn(
		array(
			'ignored_meta_keys'    => array(),
			'ignored_option_names' => array(),
			'enabled_groups'       => array( 'meta' ),
		)
	);
	WP_Mock::userFunction( 'apply_filters' )->andReturnArg( 1 );

	return new MetaListener( $repo );
}

// ---------------------------------------------------------------------------
// Ignored keys — should NOT call insert
// ---------------------------------------------------------------------------

it(
	'skips logging for the built-in ignored key _edit_lock',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldNotReceive( 'insert' );

		$listener = owc_activity_log_make_meta_listener( $repo );
		$listener->on_updated_post_meta( 1, 10, '_edit_lock', '1234567890:1' );
	}
);

it(
	'skips logging for transient meta keys via wildcard',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldNotReceive( 'insert' );

		$listener = owc_activity_log_make_meta_listener( $repo );
		$listener->on_added_post_meta( 1, 10, '_transient_doing_cron', 'value' );
	}
);

it(
	'skips logging for session_tokens user meta',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldNotReceive( 'insert' );

		$listener = owc_activity_log_make_meta_listener( $repo );
		$listener->on_updated_user_meta( 1, 5, 'session_tokens', array() );
	}
);

// ---------------------------------------------------------------------------
// Regular keys — should call insert
// ---------------------------------------------------------------------------

it(
	'logs added_post_meta for a regular meta key',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
		->once()
		->with(
			Mockery::on(
				fn( $d ) =>
					$d['group'] === 'meta' && $d['action'] === 'added_post_meta'
			)
		)
		->andReturn( true );

		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$listener = owc_activity_log_make_meta_listener( $repo );
		$listener->on_added_post_meta( 1, 10, '_my_custom_key', 'hello' );
	}
);

it(
	'logs updated_user_meta for a regular meta key',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
		->once()
		->with(
			Mockery::on(
				fn( $d ) =>
					$d['group'] === 'meta' && $d['action'] === 'updated_user_meta'
			)
		)
		->andReturn( true );

		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$listener = owc_activity_log_make_meta_listener( $repo );
		$listener->on_updated_user_meta( 1, 5, 'my_preference', 'dark_mode' );
	}
);

// ---------------------------------------------------------------------------
// Deleted meta — array type for $_meta_ids
// ---------------------------------------------------------------------------

it(
	'accepts an array for the $_meta_ids parameter on deleted_post_meta',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )->once()->andReturn( true );

		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$listener = owc_activity_log_make_meta_listener( $repo );

		// WordPress passes an array of meta IDs — this must not throw a TypeError.
		$listener->on_deleted_post_meta( array( 42, 43 ), 10, 'my_custom_key', '' );
	}
);
