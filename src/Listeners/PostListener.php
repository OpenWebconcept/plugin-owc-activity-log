<?php

declare(strict_types=1);

/**
 * Post listener.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCActivityLog\Listeners;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCActivityLog\Contracts\AbstractListener;
use WP_Post;

/**
 * Listens to post lifecycle events.
 *
 * @since 1.0.0
 */
class PostListener extends AbstractListener
{
	public function get_hooks(): array
	{
		return array(
			'save_post'          => array( 'on_save_post', 10, 3 ),
			'post_updated'       => array( 'on_post_updated', 10, 3 ),
			'wp_trash_post'      => array( 'on_trash_post', 10, 1 ),
			'untrash_post'       => array( 'on_untrash_post', 10, 1 ),
			'before_delete_post' => array( 'on_delete_post', 10, 2 ),
		);
	}

	public function on_save_post( int $post_id, WP_Post $post, bool $update ): void
	{
		if ( $this->is_auto_save() ) return;
		if ( wp_is_post_revision( $post_id ) ) return;
		if ( 'auto-draft' === $post->post_status ) return;

		$verb    = $update ? 'updated' : 'created';
		$title   = $post->post_title ?: "#{$post_id}";
		$message = sprintf(
			/* translators: 1: post title, 2: post type, 3: verb */
			__( 'Post "%1$s" (%2$s) was %3$s.', 'owc-activity-log' ),
			$title,
			$post->post_type,
			$verb
		);

		$this->log(
			'posts',
			$verb,
			$message,
			array(
				'object_id'   => $post_id,
				'object_type' => $post->post_type,
				'meta'        => array(
					'post_status' => $post->post_status,
					'post_type'   => $post->post_type,
				),
			)
		);
	}

	protected function is_auto_save(): bool
	{
		return defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE;
	}

	public function on_post_updated( int $post_id, WP_Post $post_after, WP_Post $post_before ): void
	{
		if ( wp_is_post_revision( $post_id ) ) return;

		$changed = array();

		if ( $post_before->post_title !== $post_after->post_title ) {
			$changed['title'] = array(
				'from' => $post_before->post_title,
				'to'   => $post_after->post_title,
			);
		}

		if ( $post_before->post_status !== $post_after->post_status ) {
			$changed['status'] = array(
				'from' => $post_before->post_status,
				'to'   => $post_after->post_status,
			);
		}

		if ( $post_before->post_name !== $post_after->post_name ) {
			$changed['slug'] = array(
				'from' => $post_before->post_name,
				'to'   => $post_after->post_name,
			);
		}

		if ( empty( $changed ) ) return;

		$title   = $post_after->post_title ?: "#{$post_id}";
		$message = sprintf(
			/* translators: 1: post title, 2: post type */
			__( 'Post "%1$s" (%2$s) fields changed.', 'owc-activity-log' ),
			$title,
			$post_after->post_type
		);

		$this->log(
			'posts',
			'fields_changed',
			$message,
			array(
				'object_id'   => $post_id,
				'object_type' => $post_after->post_type,
				'meta'        => array( 'changed' => $changed ),
			)
		);
	}

	public function on_trash_post( int $post_id ): void
	{
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) return;

		$title   = $post->post_title ?: "#{$post_id}";
		$message = sprintf(
			/* translators: 1: post title, 2: post type */
			__( 'Post "%1$s" (%2$s) moved to trash.', 'owc-activity-log' ),
			$title,
			$post->post_type
		);

		$this->log(
			'posts',
			'trashed',
			$message,
			array(
				'object_id'   => $post_id,
				'object_type' => $post->post_type,
			)
		);
	}

	public function on_untrash_post( int $post_id ): void
	{
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) return;

		$title   = $post->post_title ?: "#{$post_id}";
		$message = sprintf(
			/* translators: 1: post title, 2: post type */
			__( 'Post "%1$s" (%2$s) restored from trash.', 'owc-activity-log' ),
			$title,
			$post->post_type
		);

		$this->log(
			'posts',
			'untrashed',
			$message,
			array(
				'object_id'   => $post_id,
				'object_type' => $post->post_type,
			)
		);
	}

	public function on_delete_post( int $post_id, WP_Post $post ): void
	{
		if ( wp_is_post_revision( $post_id ) ) return;
		if ( 'auto-draft' === $post->post_status ) return;

		$title   = $post->post_title ?: "#{$post_id}";
		$message = sprintf(
			/* translators: 1: post title, 2: post type */
			__( 'Post "%1$s" (%2$s) permanently deleted.', 'owc-activity-log' ),
			$title,
			$post->post_type
		);

		$this->log(
			'posts',
			'deleted',
			$message,
			array(
				'object_id'   => $post_id,
				'object_type' => $post->post_type,
			)
		);
	}
}
