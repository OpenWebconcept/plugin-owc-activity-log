<?php

declare(strict_types=1);

/**
 * Comment listener.
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
use WP_Comment;

/**
 * Listens to comment lifecycle events.
 *
 * @since 1.0.0
 */
class CommentListener extends AbstractListener
{
	public function get_hooks(): array
	{
		return array(
			'wp_insert_comment'              => array( 'on_insert_comment', 10, 2 ),
			'edit_comment'                   => array( 'on_edit_comment', 10, 2 ),
			'trash_comment'                  => array( 'on_trash_comment', 10, 2 ),
			'untrash_comment'                => array( 'on_untrash_comment', 10, 2 ),
			'delete_comment'                 => array( 'on_delete_comment', 10, 2 ),
			'comment_unapproved_to_approved' => array( 'on_comment_approved', 10, 1 ),
			'comment_approved_to_unapproved' => array( 'on_comment_unapproved', 10, 1 ),
			'comment_on_hold_to_approved'    => array( 'on_comment_approved', 10, 1 ),
		);
	}

	public function on_insert_comment( int $comment_id, WP_Comment $comment ): void
	{
		$this->log(
			'comments',
			'inserted',
			sprintf(
				/* translators: 1: comment author, 2: post ID */
				__( 'Comment by "%1$s" added on post #%2$s.', 'owc-activity-log' ),
				$comment->comment_author,
				$comment->comment_post_ID
			),
			array(
				'object_id'   => (int) $comment->comment_post_ID,
				'object_type' => 'comment',
				'meta'        => array(
					'comment_id'     => $comment_id,
					'comment_author' => $comment->comment_author,
					'comment_type'   => $comment->comment_type,
				),
			)
		);
	}

	public function on_edit_comment( int $comment_id, array $data ): void
	{
		$comment = get_comment( $comment_id );

		$this->log(
			'comments',
			'edited',
			sprintf(
				/* translators: 1: comment ID */
				__( 'Comment #%1$d was edited.', 'owc-activity-log' ),
				$comment_id
			),
			array(
				'object_id'   => $comment instanceof WP_Comment ? (int) $comment->comment_post_ID : 0,
				'object_type' => 'comment',
				'meta'        => array( 'comment_id' => $comment_id ),
			)
		);
	}

	public function on_trash_comment( int $comment_id, WP_Comment $comment ): void
	{
		$this->log(
			'comments',
			'trashed',
			sprintf(
				/* translators: 1: comment author */
				__( 'Comment by "%1$s" moved to trash.', 'owc-activity-log' ),
				$comment->comment_author
			),
			array(
				'object_id'   => (int) $comment->comment_post_ID,
				'object_type' => 'comment',
				'meta'        => array( 'comment_id' => $comment_id ),
			)
		);
	}

	public function on_untrash_comment( int $comment_id, WP_Comment $comment ): void
	{
		$this->log(
			'comments',
			'untrashed',
			sprintf(
				/* translators: 1: comment author */
				__( 'Comment by "%1$s" restored from trash.', 'owc-activity-log' ),
				$comment->comment_author
			),
			array(
				'object_id'   => (int) $comment->comment_post_ID,
				'object_type' => 'comment',
				'meta'        => array( 'comment_id' => $comment_id ),
			)
		);
	}

	public function on_delete_comment( int $comment_id, WP_Comment $comment ): void
	{
		$this->log(
			'comments',
			'deleted',
			sprintf(
				/* translators: 1: comment author */
				__( 'Comment by "%1$s" permanently deleted.', 'owc-activity-log' ),
				$comment->comment_author
			),
			array(
				'object_id'   => (int) $comment->comment_post_ID,
				'object_type' => 'comment',
				'meta'        => array( 'comment_id' => $comment_id ),
			)
		);
	}

	public function on_comment_approved( WP_Comment $comment ): void
	{
		$this->log(
			'comments',
			'approved',
			sprintf(
				/* translators: 1: comment author */
				__( 'Comment by "%1$s" approved.', 'owc-activity-log' ),
				$comment->comment_author
			),
			array(
				'object_id'   => (int) $comment->comment_post_ID,
				'object_type' => 'comment',
				'meta'        => array( 'comment_id' => $comment->comment_ID ),
			)
		);
	}

	public function on_comment_unapproved( WP_Comment $comment ): void
	{
		$this->log(
			'comments',
			'unapproved',
			sprintf(
				/* translators: 1: comment author */
				__( 'Comment by "%1$s" unapproved.', 'owc-activity-log' ),
				$comment->comment_author
			),
			array(
				'object_id'   => (int) $comment->comment_post_ID,
				'object_type' => 'comment',
				'meta'        => array( 'comment_id' => $comment->comment_ID ),
			)
		);
	}
}
