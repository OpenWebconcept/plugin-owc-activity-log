<?php

declare(strict_types=1);

/**
 * Helper functions unit tests.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// owc_activity_log_get_settings()
// ---------------------------------------------------------------------------

it(
	'returns default settings when no saved option exists',
	function () {
		WP_Mock::userFunction( 'get_option' )
		->with( OWC_ACTIVITY_LOG_SETTINGS_KEY, array() )
		->andReturn( array() );

		WP_Mock::userFunction( 'wp_parse_args' )
		->andReturnUsing(
			function ( $saved, $defaults ) {
				return array_merge( $defaults, $saved );
			}
		);

		$settings = owc_activity_log_get_settings();

		expect( $settings['retention_days'] )->toBe( 30 );
		expect( $settings['ignored_meta_keys'] )->toBe( array() );
		expect( $settings['ignored_option_names'] )->toBe( array() );
		expect( $settings['enabled_groups'] )->toContain( 'posts' );
		expect( $settings['enabled_groups'] )->toContain( 'meta' );
		expect( $settings['enabled_groups'] )->toContain( 'users' );
	}
);

it(
	'merges saved settings over defaults',
	function () {
		WP_Mock::userFunction( 'get_option' )
		->andReturn( array( 'retention_days' => 30 ) );

		WP_Mock::userFunction( 'wp_parse_args' )
		->andReturnUsing(
			function ( $saved, $defaults ) {
				return array_merge( $defaults, $saved );
			}
		);

		$settings = owc_activity_log_get_settings();

		expect( $settings['retention_days'] )->toBe( 30 );
	}
);

// ---------------------------------------------------------------------------
// owc_activity_log_group_enabled()
// ---------------------------------------------------------------------------

it(
	'returns true when the group is in enabled_groups',
	function () {
		WP_Mock::userFunction( 'get_option' )
		->andReturn( array() );

		WP_Mock::userFunction( 'wp_parse_args' )
		->andReturnUsing(
			function ( $saved, $defaults ) {
				return array_merge( $defaults, $saved );
			}
		);

		expect( owc_activity_log_group_enabled( 'posts' ) )->toBeTrue();
	}
);

it(
	'returns false when the group is not in enabled_groups',
	function () {
		WP_Mock::userFunction( 'get_option' )
		->andReturn( array( 'enabled_groups' => array( 'posts' ) ) );

		WP_Mock::userFunction( 'wp_parse_args' )
		->andReturnUsing(
			function ( $saved, $defaults ) {
				return array_merge( $defaults, $saved );
			}
		);

		expect( owc_activity_log_group_enabled( 'meta' ) )->toBeFalse();
	}
);
