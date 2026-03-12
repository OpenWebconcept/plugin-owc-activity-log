<?php

declare(strict_types=1);

/**
 * Option listener.
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
 * Listens to WordPress option changes.
 *
 * @since 1.0.0
 */
class OptionListener extends AbstractListener
{
	/**
	 * High-frequency or sensitive option names to always ignore.
	 */
	private const IGNORED_OPTIONS = array(
		'cron',
		'rewrite_rules',
		'recently_activated',
		'active_plugins',      // tracked by PluginListener
		'template',            // tracked by ThemeListener
		'stylesheet',          // tracked by ThemeListener
		'_site_transient_*',
		'_transient_*',
		'owc_activity_log_*',
	);

	public function get_hooks(): array
	{
		return array(
			'added_option'   => array( 'on_added_option', 10, 2 ),
			'updated_option' => array( 'on_updated_option', 10, 3 ),
			'deleted_option' => array( 'on_deleted_option', 10, 1 ),
		);
	}

	public function on_added_option( string $option, mixed $value ): void
	{
		if ( $this->is_ignored( $option ) ) return;

		$this->log(
			'options',
			'added_option',
			sprintf(
				/* translators: 1: option name */
				__( 'Option "%1$s" was added.', 'owc-activity-log' ),
				$option
			),
			array(
				'object_type' => 'option',
				'meta'        => array(
					'option' => $option,
					'value'  => $this->truncate( $value ),
				),
			)
		);
	}

	public function on_updated_option( string $option, mixed $old_value, mixed $new_value ): void
	{
		if ( $this->is_ignored( $option ) ) return;

		$this->log(
			'options',
			'updated_option',
			sprintf(
				/* translators: 1: option name */
				__( 'Option "%1$s" was updated.', 'owc-activity-log' ),
				$option
			),
			array(
				'object_type' => 'option',
				'meta'        => array(
					'option'    => $option,
					'old_value' => $this->truncate( $old_value ),
					'new_value' => $this->truncate( $new_value ),
				),
			)
		);
	}

	public function on_deleted_option( string $option ): void
	{
		if ( $this->is_ignored( $option ) ) return;

		$this->log(
			'options',
			'deleted_option',
			sprintf(
				/* translators: 1: option name */
				__( 'Option "%1$s" was deleted.', 'owc-activity-log' ),
				$option
			),
			array(
				'object_type' => 'option',
				'meta'        => array( 'option' => $option ),
			)
		);
	}

	/**
	 * Check whether an option should be ignored.
	 */
	private function is_ignored( string $option ): bool
	{
		$settings      = owc_activity_log_get_settings();
		$extra_ignored = (array) ( $settings['ignored_option_names'] ?? array() );
		$all_ignored   = array_merge( self::IGNORED_OPTIONS, $extra_ignored );
		$all_ignored   = (array) apply_filters( 'owc_activity_log_ignored_options', $all_ignored );

		foreach ( $all_ignored as $pattern ) {
			if ( fnmatch( $pattern, $option ) ) return true;
		}

		return false;
	}
}
