<?php

declare(strict_types=1);

/**
 * Media listener.
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
 * Listens to media/attachment events.
 *
 * @since 1.0.0
 */
class MediaListener extends AbstractListener
{
	public function get_hooks(): array
	{
		return array(
			'add_attachment'    => array( 'on_add_attachment', 10, 1 ),
			'edit_attachment'   => array( 'on_edit_attachment', 10, 1 ),
			'delete_attachment' => array( 'on_delete_attachment', 10, 1 ),
		);
	}

	public function on_add_attachment( int $post_id ): void
	{
		$post = get_post( $post_id );

		$this->log(
			'media',
			'uploaded',
			sprintf(
				/* translators: 1: attachment title */
				__( 'Media "%1$s" uploaded.', 'owc-activity-log' ),
				$post instanceof WP_Post ? $post->post_title : "#{$post_id}"
			),
			array(
				'object_id'   => $post_id,
				'object_type' => 'attachment',
				'meta'        => array(
					'mime_type' => $post instanceof WP_Post ? $post->post_mime_type : '',
					'file'      => $post instanceof WP_Post ? basename( get_attached_file( $post_id ) ) : '',
				),
			)
		);
	}

	public function on_edit_attachment( WP_Post $post ): void
	{
		$this->log(
			'media',
			'edited',
			sprintf(
				/* translators: 1: attachment title */
				__( 'Media "%1$s" edited.', 'owc-activity-log' ),
				$post->post_title
			),
			array(
				'object_id'   => $post->ID,
				'object_type' => 'attachment',
				'meta'        => array( 'mime_type' => $post->post_mime_type ),
			)
		);
	}

	public function on_delete_attachment( int $post_id ): void
	{
		$post = get_post( $post_id );

		$this->log(
			'media',
			'deleted',
			sprintf(
				/* translators: 1: attachment title */
				__( 'Media "%1$s" deleted.', 'owc-activity-log' ),
				$post instanceof WP_Post ? $post->post_title : "#{$post_id}"
			),
			array(
				'object_id'   => $post_id,
				'object_type' => 'attachment',
				'meta'        => array(
					'mime_type' => $post instanceof WP_Post ? $post->post_mime_type : '',
					'file'      => $post instanceof WP_Post ? basename( get_attached_file( $post_id ) ) : '',
				),
			)
		);
	}
}
