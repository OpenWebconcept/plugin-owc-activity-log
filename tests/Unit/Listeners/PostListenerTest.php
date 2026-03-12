<?php

declare(strict_types=1);

/**
 * PostListener unit tests.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCActivityLog\Database\ActivityRepository;
use OWCActivityLog\Listeners\PostListener;

// ---------------------------------------------------------------------------
// get_hooks()
// ---------------------------------------------------------------------------

it(
	'registers all expected hooks',
	function () {
		$repo     = Mockery::mock( ActivityRepository::class );
		$listener = new PostListener( $repo );
		$hooks    = $listener->get_hooks();

		expect( $hooks )->toHaveKeys(
			array(
				'save_post',
				'post_updated',
				'wp_trash_post',
				'untrash_post',
				'before_delete_post',
			)
		);
	}
);

it(
	'maps each hook to three-element config array',
	function () {
		$repo     = Mockery::mock( ActivityRepository::class );
		$listener = new PostListener( $repo );

		foreach ( $listener->get_hooks() as $config ) {
			expect( $config )->toHaveCount( 3 );
		}
	}
);

// ---------------------------------------------------------------------------
// on_save_post() — bail conditions
// ---------------------------------------------------------------------------

it(
	'does not log on autosave',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldNotReceive( 'insert' );

		$post              = Mockery::mock( \WP_Post::class );
		$post->post_status = 'publish';
		$post->post_title  = 'Hello World';
		$post->post_type   = 'post';

		WP_Mock::userFunction( 'wp_is_post_revision' )->andReturn( false );

		$listener = Mockery::mock( PostListener::class, array( $repo ) )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$listener->shouldReceive( 'is_auto_save' )
			->once()
			->andReturn( true );

		$listener->on_save_post( 1, $post, false );
	}
);

it(
	'does not log for revisions',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldNotReceive( 'insert' );

		$post              = Mockery::mock( WP_Post::class );
		$post->post_status = 'inherit';

		WP_Mock::userFunction( 'wp_is_post_revision' )->andReturn( true );

		$listener = new PostListener( $repo );
		$listener->on_save_post( 2, $post, false );
	}
);

it(
	'does not log for auto-drafts',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldNotReceive( 'insert' );

		$post              = Mockery::mock( WP_Post::class );
		$post->post_status = 'auto-draft';
		$post->post_type   = 'post';

		WP_Mock::userFunction( 'wp_is_post_revision' )->andReturn( false );

		$listener = new PostListener( $repo );
		$listener->on_save_post( 3, $post, false );
	}
);

// ---------------------------------------------------------------------------
// on_save_post() — successful log
// ---------------------------------------------------------------------------

it(
	'logs a "created" event for a new post',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
		->once()
		->with(
			Mockery::on(
				fn( $data ) =>
					$data['group'] === 'posts' && $data['action'] === 'created'
			)
		)
		->andReturn( true );

		$post              = Mockery::mock( WP_Post::class );
		$post->post_status = 'publish';
		$post->post_type   = 'post';
		$post->post_title  = 'Hello World';

		WP_Mock::userFunction( 'wp_doing_autosave' )->andReturn( false );
		WP_Mock::userFunction( 'wp_is_post_revision' )->andReturn( false );
		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$listener = new PostListener( $repo );
		$listener->on_save_post( 10, $post, false );
	}
);

it(
	'logs an "updated" event for an existing post',
	function () {
		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
		->once()
		->with(
			Mockery::on(
				fn( $data ) =>
					$data['group'] === 'posts' && $data['action'] === 'updated'
			)
		)
		->andReturn( true );

		$post              = Mockery::mock( WP_Post::class );
		$post->post_status = 'publish';
		$post->post_type   = 'post';
		$post->post_title  = 'Hello World';

		WP_Mock::userFunction( 'wp_doing_autosave' )->andReturn( false );
		WP_Mock::userFunction( 'wp_is_post_revision' )->andReturn( false );
		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$listener = new PostListener( $repo );
		$listener->on_save_post( 10, $post, true );
	}
);

// ---------------------------------------------------------------------------
// on_trash_post()
// ---------------------------------------------------------------------------

it(
	'logs a "trashed" event when a post is trashed',
	function () {
		$post             = Mockery::mock( WP_Post::class );
		$post->post_type  = 'post';
		$post->post_title = 'Trash me';

		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
		->once()
		->with( Mockery::on( fn( $data ) => $data['action'] === 'trashed' ) )
		->andReturn( true );

		WP_Mock::userFunction( 'get_post' )->with( 5 )->andReturn( $post );
		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$listener = new PostListener( $repo );
		$listener->on_trash_post( 5 );
	}
);

// ---------------------------------------------------------------------------
// on_delete_post()
// ---------------------------------------------------------------------------

it(
	'logs a "deleted" event when a post is permanently deleted',
	function () {
		$post              = Mockery::mock( WP_Post::class );
		$post->post_type   = 'post';
		$post->post_title  = 'Gone forever';
		$post->post_status = 'trash';

		$repo = Mockery::mock( ActivityRepository::class );
		$repo->shouldReceive( 'insert' )
		->once()
		->with( Mockery::on( fn( $data ) => $data['action'] === 'deleted' ) )
		->andReturn( true );

		WP_Mock::userFunction( 'wp_is_post_revision' )->andReturn( false );
		WP_Mock::userFunction( 'owc_activity_log_group_enabled' )->andReturn( true );
		WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( new WP_User() );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );

		$listener = new PostListener( $repo );
		$listener->on_delete_post( 7, $post );
	}
);
