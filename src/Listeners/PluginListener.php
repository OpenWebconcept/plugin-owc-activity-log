<?php

declare(strict_types=1);

/**
 * Plugin listener.
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
 * Listens to plugin install, activation and deactivation events.
 *
 * @since 1.0.0
 */
class PluginListener extends AbstractListener
{
	public function get_hooks(): array
	{
		return array(
			'activated_plugin'          => array( 'on_activated', 10, 2 ),
			'deactivated_plugin'        => array( 'on_deactivated', 10, 2 ),
			'deleted_plugin'            => array( 'on_deleted', 10, 2 ),
			'upgrader_process_complete' => array( 'on_upgrader_complete', 10, 2 ),
		);
	}

	public function on_activated( string $plugin, bool $network_wide ): void
	{
		$name = $this->get_plugin_name( $plugin );

		$this->log(
			'plugins',
			'activated',
			sprintf(
				/* translators: 1: plugin name */
				__( 'Plugin "%1$s" activated.', 'owc-activity-log' ),
				$name
			),
			array(
				'object_type' => 'plugin',
				'meta'        => array(
					'plugin_file'  => $plugin,
					'network_wide' => $network_wide,
				),
			)
		);
	}

	public function on_deactivated( string $plugin, bool $network_wide ): void
	{
		$name = $this->get_plugin_name( $plugin );

		$this->log(
			'plugins',
			'deactivated',
			sprintf(
				/* translators: 1: plugin name */
				__( 'Plugin "%1$s" deactivated.', 'owc-activity-log' ),
				$name
			),
			array(
				'object_type' => 'plugin',
				'meta'        => array(
					'plugin_file'  => $plugin,
					'network_wide' => $network_wide,
				),
			)
		);
	}

	public function on_deleted( string $plugin_file, bool $deleted ): void
	{
		if ( ! $deleted ) return;

		$this->log(
			'plugins',
			'deleted',
			sprintf(
				/* translators: 1: plugin file */
				__( 'Plugin "%1$s" deleted.', 'owc-activity-log' ),
				$plugin_file
			),
			array(
				'object_type' => 'plugin',
				'meta'        => array( 'plugin_file' => $plugin_file ),
			)
		);
	}

	/**
	 * Fires after a plugin or theme update/install via the upgrader.
	 */
	public function on_upgrader_complete( mixed $upgrader, array $hook_extra ): void
	{
		if ( 'plugin' !== ( $hook_extra['type'] ?? '' ) ) return;

		$action  = $hook_extra['action'] ?? 'update';
		$plugins = $hook_extra['plugins'] ?? array( $hook_extra['plugin'] ?? '' );

		foreach ( $plugins as $plugin_file ) {
			if ( empty( $plugin_file ) ) continue;

			$name = $this->get_plugin_name( $plugin_file );

			$this->log(
				'plugins',
				$action,
				sprintf(
					/* translators: 1: plugin name, 2: action */
					__( 'Plugin "%1$s" %2$s via upgrader.', 'owc-activity-log' ),
					$name,
					$action
				),
				array(
					'object_type' => 'plugin',
					'meta'        => array( 'plugin_file' => $plugin_file ),
				)
			);
		}
	}

	/**
	 * Get a human-readable plugin name.
	 */
	private function get_plugin_name( string $plugin_file ): string
	{
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$full_path = WP_PLUGIN_DIR . '/' . $plugin_file;
		$data      = file_exists( $full_path ) ? get_plugin_data( $full_path ) : array();

		return ! empty( $data['Name'] ) ? $data['Name'] : $plugin_file;
	}
}
