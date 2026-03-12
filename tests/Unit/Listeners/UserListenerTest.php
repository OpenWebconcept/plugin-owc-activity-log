<?php

declare(strict_types=1);

/**
 * UserListener unit tests.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCActivityLog\Database\ActivityRepository;
use OWCActivityLog\Listeners\UserListener;

// ---------------------------------------------------------------------------
// get_hooks()
// ---------------------------------------------------------------------------

it(
	'registers all expected user hooks',
	function () {
		$repo     = Mockery::mock( ActivityRepository::class );
		$listener = new UserListener( $repo );
		$hooks    = $listener->get_hooks();

		expect( $hooks )->toHaveKeys(
			array(
				'user_register',
				'profile_update',
				'delete_user',
				'wp_login',
				'wp_logout',
				'wp_login_failed',
			)
		);
	}
);

// ---------------------------------------------------------------------------
// on_login()
// ---------------------------------------------------------------------------

it(
	'logs a login event with the correct user_login',
	function () {
		$user     = Mockery::mock( WP_User::class );
		$user->ID = 1;

		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
		->once()
		->with(
			Mockery::on(
				fn( $d ) =>
					$d['group'] === 'users'
					&& $d['action'] === 'login'
					&& $d['user_login'] === 'john'
			)
		)
		->andReturn( true );

		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$listener = new UserListener( $repo );
		$listener->on_login( 'john', $user );
	}
);

// ---------------------------------------------------------------------------
// on_login_failed() — only username, never password
// ---------------------------------------------------------------------------

it(
	'logs a failed login with only the username in meta',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
		->once()
		->with(
			Mockery::on(
				fn( $d ) =>
					$d['group'] === 'users'
					&& $d['action'] === 'login_failed'
					&& isset( $d['meta'] )
					&& str_contains( $d['meta'], 'supplied_username' )
					&& ! str_contains( $d['meta'], 'password' )
			)
		)
		->andReturn( true );

		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )
		->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$listener = new UserListener( $repo );
		$listener->on_login_failed( 'badactor' );
	}
);

// ---------------------------------------------------------------------------
// on_login() — skipped when group is disabled
// ---------------------------------------------------------------------------

it(
	'does not log a login event when the users group is disabled',
	function () {
		$user     = Mockery::mock( WP_User::class );
		$user->ID = 2;

		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldNotReceive( 'insert' );

		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( false );

		$listener = new UserListener( $repo );
		$listener->on_login( 'jane', $user );
	}
);
