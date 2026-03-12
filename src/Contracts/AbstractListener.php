<?php

declare(strict_types=1);

/**
 * Abstract listener base class.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCActivityLog\Contracts;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCActivityLog\Database\ActivityRepository;
use WP_User;

/**
 * Base class for all activity listeners.
 *
 * @since 1.0.0
 */
abstract class AbstractListener
{
	protected ActivityRepository $repository;

	public function __construct( ?ActivityRepository $repository = null )
	{
		$this->repository = $repository ?? new ActivityRepository();
	}

	/**
	 * Return a map of WP hook name => [ method, priority, accepted_args ].
	 */
	abstract public function get_hooks(): array;

	/**
	 * Write an activity record.
	 */
	protected function log(
		string $group,
		string $action,
		string $message,
		array $context = array()
	): void {
		if ( ! owc_activity_log_group_enabled( $group ) ) {
			return;
		}

		$user       = wp_get_current_user();
		$user_id    = $user instanceof WP_User ? $user->ID : 0;
		$user_login = $user instanceof WP_User ? $user->user_login : '';

		$this->repository->insert(
			array(
				'created_at'  => current_time( 'mysql', true ),
				'group'       => $group,
				'action'      => $action,
				'message'     => $message,
				'user_id'     => $context['user_id'] ?? $user_id,
				'user_login'  => $context['user_login'] ?? $user_login,
				'object_id'   => $context['object_id'] ?? 0,
				'object_type' => $context['object_type'] ?? '',
				'meta'        => isset( $context['meta'] ) ? wp_json_encode( $context['meta'] ) : null,
				'ip'          => ( owc_activity_log_get_settings()['log_ip'] ?? false ) ? $this->get_ip() : '',
			)
		);
	}

	/**
	 * Get the visitor IP address.
	 */
	private function get_ip(): string
	{
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput
		$candidates = array(
			$_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
			$_SERVER['HTTP_CLIENT_IP'] ?? '',
			$_SERVER['REMOTE_ADDR'] ?? '',
		);
		// phpcs:enable

		foreach ( $candidates as $ip ) {
			$ip = trim( explode( ',', $ip )[0] );

			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}

		return '';
	}

	/**
	 * Truncate and stringify a value for storage.
	 */
	protected function truncate( mixed $value, int $max_length = 500 ): string
	{
		if ( is_array( $value ) || is_object( $value ) ) {
			$value = wp_json_encode( $value );
		} else {
			$value = (string) $value;
		}

		if ( strlen( $value ) > $max_length ) {
			return substr( $value, 0, $max_length ) . '…';
		}

		return $value;
	}
}
