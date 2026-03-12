<?php

declare(strict_types=1);

/**
 * Meta listener.
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

/**
 * Listens to post, user and term meta changes.
 *
 * @since 1.0.0
 */
class MetaListener extends AbstractListener
{
	/**
	 * Meta keys to always ignore regardless of settings, to prevent log flooding.
	 */
	private const IGNORED_META_KEYS = array(
		'_edit_lock',
		'_edit_last',
		'session_tokens',
		'_transient_*',
		'_site_transient_*',
		'_wp_old_slug',
		'_wp_trash_meta_status',
		'_wp_trash_meta_time',
	);

	public function get_hooks(): array
	{
		return array(
			// Post meta.
			'added_post_meta'   => array( 'on_added_post_meta', 10, 4 ),
			'updated_post_meta' => array( 'on_updated_post_meta', 10, 4 ),
			'deleted_post_meta' => array( 'on_deleted_post_meta', 10, 4 ),
			// User meta.
			'added_user_meta'   => array( 'on_added_user_meta', 10, 4 ),
			'updated_user_meta' => array( 'on_updated_user_meta', 10, 4 ),
			'deleted_user_meta' => array( 'on_deleted_user_meta', 10, 4 ),
			// Term meta.
			'added_term_meta'   => array( 'on_added_term_meta', 10, 4 ),
			'updated_term_meta' => array( 'on_updated_term_meta', 10, 4 ),
			'deleted_term_meta' => array( 'on_deleted_term_meta', 10, 4 ),
		);
	}

	public function on_added_post_meta( int $_meta_id, int $post_id, string $meta_key, mixed $meta_value ): void
	{
		if ( $this->is_ignored_key( $meta_key ) ) return;

		$this->log(
			'meta',
			'added_post_meta',
			sprintf(
				/* translators: 1: meta key, 2: post ID */
				__( 'Post meta "%1$s" added on post #%2$d.', 'owc-activity-log' ),
				$meta_key,
				$post_id
			),
			array(
				'object_id'   => $post_id,
				'object_type' => 'post_meta',
				'meta'        => array(
					'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => $this->truncate( $meta_value ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				),
			)
		);
	}

	public function on_updated_post_meta( int $_meta_id, int $post_id, string $meta_key, mixed $meta_value ): void
	{
		if ( $this->is_ignored_key( $meta_key ) ) return;

		$this->log(
			'meta',
			'updated_post_meta',
			sprintf(
				/* translators: 1: meta key, 2: post ID */
				__( 'Post meta "%1$s" updated on post #%2$d.', 'owc-activity-log' ),
				$meta_key,
				$post_id
			),
			array(
				'object_id'   => $post_id,
				'object_type' => 'post_meta',
				'meta'        => array(
					'meta_key'       => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'new_meta_value' => $this->truncate( $meta_value ),
				),
			)
		);
	}

	public function on_deleted_post_meta( array $_meta_ids, int $post_id, string $meta_key, mixed $_meta_value ): void
	{
		if ( $this->is_ignored_key( $meta_key ) ) return;

		$this->log(
			'meta',
			'deleted_post_meta',
			sprintf(
				/* translators: 1: meta key, 2: post ID */
				__( 'Post meta "%1$s" deleted from post #%2$d.', 'owc-activity-log' ),
				$meta_key,
				$post_id
			),
			array(
				'object_id'   => $post_id,
				'object_type' => 'post_meta',
				'meta'        => array( 'meta_key' => $meta_key ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);
	}

	public function on_added_user_meta( int $_meta_id, int $user_id, string $meta_key, mixed $meta_value ): void
	{
		if ( $this->is_ignored_key( $meta_key ) ) return;

		$this->log(
			'meta',
			'added_user_meta',
			sprintf(
				/* translators: 1: meta key, 2: user ID */
				__( 'User meta "%1$s" added on user #%2$d.', 'owc-activity-log' ),
				$meta_key,
				$user_id
			),
			array(
				'object_id'   => $user_id,
				'object_type' => 'user_meta',
				'meta'        => array(
					'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => $this->truncate( $meta_value ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				),
			)
		);
	}

	public function on_updated_user_meta( int $_meta_id, int $user_id, string $meta_key, mixed $meta_value ): void
	{
		if ( $this->is_ignored_key( $meta_key ) ) return;

		$this->log(
			'meta',
			'updated_user_meta',
			sprintf(
				/* translators: 1: meta key, 2: user ID */
				__( 'User meta "%1$s" updated on user #%2$d.', 'owc-activity-log' ),
				$meta_key,
				$user_id
			),
			array(
				'object_id'   => $user_id,
				'object_type' => 'user_meta',
				'meta'        => array(
					'meta_key'       => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'new_meta_value' => $this->truncate( $meta_value ),
				),
			)
		);
	}

	public function on_deleted_user_meta( array $_meta_ids, int $user_id, string $meta_key, mixed $_meta_value ): void
	{
		if ( $this->is_ignored_key( $meta_key ) ) return;

		$this->log(
			'meta',
			'deleted_user_meta',
			sprintf(
				/* translators: 1: meta key, 2: user ID */
				__( 'User meta "%1$s" deleted from user #%2$d.', 'owc-activity-log' ),
				$meta_key,
				$user_id
			),
			array(
				'object_id'   => $user_id,
				'object_type' => 'user_meta',
				'meta'        => array( 'meta_key' => $meta_key ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);
	}

	public function on_added_term_meta( int $_meta_id, int $term_id, string $meta_key, mixed $meta_value ): void
	{
		if ( $this->is_ignored_key( $meta_key ) ) return;

		$this->log(
			'meta',
			'added_term_meta',
			sprintf(
				/* translators: 1: meta key, 2: term ID */
				__( 'Term meta "%1$s" added on term #%2$d.', 'owc-activity-log' ),
				$meta_key,
				$term_id
			),
			array(
				'object_id'   => $term_id,
				'object_type' => 'term_meta',
				'meta'        => array(
					'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => $this->truncate( $meta_value ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				),
			)
		);
	}

	public function on_updated_term_meta( int $_meta_id, int $term_id, string $meta_key, mixed $meta_value ): void
	{
		if ( $this->is_ignored_key( $meta_key ) ) return;

		$this->log(
			'meta',
			'updated_term_meta',
			sprintf(
				/* translators: 1: meta key, 2: term ID */
				__( 'Term meta "%1$s" updated on term #%2$d.', 'owc-activity-log' ),
				$meta_key,
				$term_id
			),
			array(
				'object_id'   => $term_id,
				'object_type' => 'term_meta',
				'meta'        => array(
					'meta_key'       => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'new_meta_value' => $this->truncate( $meta_value ),
				),
			)
		);
	}

	public function on_deleted_term_meta( array $_meta_ids, int $term_id, string $meta_key, mixed $_meta_value ): void
	{
		if ( $this->is_ignored_key( $meta_key ) ) return;

		$this->log(
			'meta',
			'deleted_term_meta',
			sprintf(
				/* translators: 1: meta key, 2: term ID */
				__( 'Term meta "%1$s" deleted from term #%2$d.', 'owc-activity-log' ),
				$meta_key,
				$term_id
			),
			array(
				'object_id'   => $term_id,
				'object_type' => 'term_meta',
				'meta'        => array( 'meta_key' => $meta_key ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);
	}

	/**
	 * Check whether a meta key should be ignored.
	 */
	private function is_ignored_key( string $meta_key ): bool
	{
		$settings      = owc_activity_log_get_settings();
		$extra_ignored = (array) ( $settings['ignored_meta_keys'] ?? array() );
		$all_ignored   = array_merge( self::IGNORED_META_KEYS, $extra_ignored );
		$all_ignored   = (array) apply_filters( 'owc_activity_log_ignored_meta_keys', $all_ignored );

		foreach ( $all_ignored as $pattern ) {
			if ( fnmatch( $pattern, $meta_key ) ) return true;
		}

		return false;
	}
}
