<?php

declare(strict_types=1);

/**
 * User listener.
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
use WP_User;

/**
 * Listens to user lifecycle events.
 *
 * @since 1.0.0
 */
class UserListener extends AbstractListener
{
	public function get_hooks(): array
	{
		return array(
			'user_register'   => array( 'on_user_register', 10, 1 ),
			'profile_update'  => array( 'on_profile_update', 10, 2 ),
			'delete_user'     => array( 'on_delete_user', 10, 2 ),
			'wp_login'        => array( 'on_login', 10, 2 ),
			'wp_logout'       => array( 'on_logout', 10, 1 ),
			'wp_login_failed' => array( 'on_login_failed', 10, 1 ),
		);
	}

	public function on_user_register( int $user_id ): void
	{
		$user = get_userdata( $user_id );

		$this->log(
			'users',
			'registered',
			sprintf(
				/* translators: 1: user login */
				__( 'New user registered: "%1$s".', 'owc-activity-log' ),
				$user ? $user->user_login : "#{$user_id}"
			),
			array(
				'object_id'   => $user_id,
				'object_type' => 'user',
				'user_id'     => $user_id,
				'user_login'  => $user ? $user->user_login : '',
			)
		);
	}

	public function on_profile_update( int $user_id, WP_User $old_user ): void
	{
		$new_user = get_userdata( $user_id );

		if ( ! $new_user instanceof WP_User ) return;

		$changed = array();

		if ( $old_user->user_email !== $new_user->user_email ) {
			$changed['email'] = array(
				'from' => $old_user->user_email,
				'to'   => $new_user->user_email,
			);
		}

		if ( $old_user->display_name !== $new_user->display_name ) {
			$changed['display_name'] = array(
				'from' => $old_user->display_name,
				'to'   => $new_user->display_name,
			);
		}

		if ( $old_user->user_url !== $new_user->user_url ) {
			$changed['url'] = array(
				'from' => $old_user->user_url,
				'to'   => $new_user->user_url,
			);
		}

		$this->log(
			'users',
			'profile_updated',
			sprintf(
				/* translators: 1: user login */
				__( 'User profile updated: "%1$s".', 'owc-activity-log' ),
				$new_user->user_login
			),
			array(
				'object_id'   => $user_id,
				'object_type' => 'user',
				'user_id'     => $user_id,
				'user_login'  => $new_user->user_login,
				'meta'        => array( 'changed' => $changed ),
			)
		);
	}

	public function on_delete_user( int $user_id, ?int $reassign_id ): void
	{
		$user = get_userdata( $user_id );

		$this->log(
			'users',
			'deleted',
			sprintf(
				/* translators: 1: user login */
				__( 'User deleted: "%1$s".', 'owc-activity-log' ),
				$user ? $user->user_login : "#{$user_id}"
			),
			array(
				'object_id'   => $user_id,
				'object_type' => 'user',
				'user_id'     => $user_id,
				'user_login'  => $user ? $user->user_login : '',
				'meta'        => array( 'reassigned_to' => $reassign_id ),
			)
		);
	}

	public function on_login( string $user_login, WP_User $user ): void
	{
		$this->log(
			'users',
			'login',
			sprintf(
				/* translators: 1: user login */
				__( 'User "%1$s" logged in.', 'owc-activity-log' ),
				$user_login
			),
			array(
				'object_id'   => $user->ID,
				'object_type' => 'user',
				'user_id'     => $user->ID,
				'user_login'  => $user_login,
			)
		);
	}

	public function on_logout( int $user_id ): void
	{
		$user = get_userdata( $user_id );

		$this->log(
			'users',
			'logout',
			sprintf(
				/* translators: 1: user login */
				__( 'User "%1$s" logged out.', 'owc-activity-log' ),
				$user ? $user->user_login : "#{$user_id}"
			),
			array(
				'object_id'   => $user_id,
				'object_type' => 'user',
				'user_id'     => $user_id,
				'user_login'  => $user ? $user->user_login : '',
			)
		);
	}

	/**
	 * Only log the supplied username — never passwords.
	 */
	public function on_login_failed( string $username ): void
	{
		$this->log(
			'users',
			'login_failed',
			sprintf(
				/* translators: 1: supplied username */
				__( 'Failed login attempt for "%1$s".', 'owc-activity-log' ),
				$username
			),
			array(
				'object_type' => 'user',
				'meta'        => array( 'supplied_username' => $username ),
			)
		);
	}
}
