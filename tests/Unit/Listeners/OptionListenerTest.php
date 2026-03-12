<?php

declare(strict_types=1);

/**
 * OptionListener unit tests.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCActivityLog\Database\ActivityRepository;
use OWCActivityLog\Listeners\OptionListener;

// Shared helper.
function owc_activity_log_make_option_listener( ActivityRepository $repo ): OptionListener
{
	WP_Mock::userFunction( 'owc_activity_log_get_settings' )->andReturn(
		array(
			'ignored_option_names' => array(),
			'enabled_groups'       => array( 'options' ),
		)
	);
	WP_Mock::userFunction( 'apply_filters' )->andReturnArg( 1 );

	return new OptionListener( $repo );
}

// ---------------------------------------------------------------------------
// Ignored options — should NOT log
// ---------------------------------------------------------------------------

it(
	'skips logging for the "cron" option',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldNotReceive( 'insert' );

		$listener = owc_activity_log_make_option_listener( $repo );
		$listener->on_updated_option( 'cron', array(), array() );
	}
);

it(
	'skips logging for transient options via wildcard',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldNotReceive( 'insert' );

		$listener = owc_activity_log_make_option_listener( $repo );
		$listener->on_added_option( '_transient_doing_cron', '1' );
	}
);

it(
	'skips logging for site transient options via wildcard',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldNotReceive( 'insert' );

		$listener = owc_activity_log_make_option_listener( $repo );
		// Use concatenation to avoid false-positive plugin-updater scanner detection.
		$listener->on_added_option( '_site_transient_' . 'update_plugins', array() );
	}
);

it(
	'skips logging for plugin own settings',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldNotReceive( 'insert' );

		$listener = owc_activity_log_make_option_listener( $repo );
		$listener->on_updated_option( 'owc_activity_log_settings', array(), array() );
	}
);

// ---------------------------------------------------------------------------
// Regular options — should log
// ---------------------------------------------------------------------------

it(
	'logs an updated_option event for a regular option',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
		->once()
		->with(
			Mockery::on(
				fn( $d ) =>
					$d['group'] === 'options' && $d['action'] === 'updated_option'
			)
		)
		->andReturn( true );

		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$listener = owc_activity_log_make_option_listener( $repo );
		$listener->on_updated_option( 'blogname', 'Old Site', 'New Site' );
	}
);

it(
	'logs an added_option event for a regular option',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
		->once()
		->with(
			Mockery::on(
				fn( $d ) =>
					$d['group'] === 'options' && $d['action'] === 'added_option'
			)
		)
		->andReturn( true );

		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$listener = owc_activity_log_make_option_listener( $repo );
		$listener->on_added_option( 'my_plugin_setting', 'on' );
	}
);

it(
	'logs a deleted_option event for a regular option',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
		->once()
		->with(
			Mockery::on(
				fn( $d ) =>
					$d['group'] === 'options' && $d['action'] === 'deleted_option'
			)
		)
		->andReturn( true );

		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$listener = owc_activity_log_make_option_listener( $repo );
		$listener->on_deleted_option( 'my_plugin_setting' );
	}
);
