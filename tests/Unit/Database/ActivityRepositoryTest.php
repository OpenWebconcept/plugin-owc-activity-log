<?php

declare(strict_types=1);

/**
 * ActivityRepository unit tests.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCActivityLog\Database\ActivityRepository;

beforeEach(
	function () {
		$this->wpdb         = Mockery::mock( 'wpdb' );
		$this->wpdb->prefix = 'wp_';

		$GLOBALS['wpdb'] = $this->wpdb;

		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		WP_Mock::userFunction( 'wp_cache_set' )->andReturn( true );
		WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );
	}
);

afterEach(
	function () {
		unset( $GLOBALS['wpdb'] );
	}
);

// ---------------------------------------------------------------------------
// insert()
// ---------------------------------------------------------------------------

it(
	'returns true when wpdb->insert succeeds',
	function () {
		$this->wpdb
		->shouldReceive( 'insert' )
		->once()
		->andReturn( 1 );

		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );

		$repo   = new ActivityRepository();
		$result = $repo->insert(
			array(
				'group'   => 'posts',
				'action'  => 'created',
				'message' => 'Post "Hello" (post) was created.',
			)
		);

		expect( $result )->toBeTrue();
	}
);

it(
	'returns false when wpdb->insert fails',
	function () {
		$this->wpdb
		->shouldReceive( 'insert' )
		->once()
		->andReturn( false );

		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );

		$repo   = new ActivityRepository();
		$result = $repo->insert(
			array(
				'group'   => 'posts',
				'action'  => 'created',
				'message' => 'Post "Hello" (post) was created.',
			)
		);

		expect( $result )->toBeFalse();
	}
);

// ---------------------------------------------------------------------------
// count()
// ---------------------------------------------------------------------------

it(
	'returns an integer count with no filters',
	function () {
		$this->wpdb
		->shouldReceive( 'get_var' )
		->once()
		->with( Mockery::type( 'string' ) )
		->andReturn( '42' );

		$repo = new ActivityRepository();

		expect( $repo->count() )->toBe( 42 );
	}
);

it(
	'passes filter values through prepare() when filters are present',
	function () {
		$this->wpdb
		->shouldReceive( 'prepare' )
		->once()
		->andReturn( 'SELECT COUNT(*) FROM owc_activity_log WHERE `group` = \'posts\'' );

		$this->wpdb
		->shouldReceive( 'get_var' )
		->once()
		->andReturn( '5' );

		WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );

		$repo = new ActivityRepository();

		expect( $repo->count( array( 'group' => 'posts' ) ) )->toBe( 5 );
	}
);

// ---------------------------------------------------------------------------
// delete_before()
// ---------------------------------------------------------------------------

it(
	'calls wpdb->query with a DELETE statement and returns affected rows',
	function () {
		$this->wpdb
		->shouldReceive( 'prepare' )
		->once()
		->andReturn( "DELETE FROM owc_activity_log WHERE created_at < '2024-01-01 00:00:00'" );

		$this->wpdb
		->shouldReceive( 'query' )
		->once()
		->andReturn( 10 );

		$repo = new ActivityRepository();

		expect( $repo->delete_before( '2024-01-01 00:00:00' ) )->toBe( 10 );
	}
);
