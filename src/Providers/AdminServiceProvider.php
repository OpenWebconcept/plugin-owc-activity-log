<?php

declare(strict_types=1);

/**
 * Admin service provider.
 *
 * @package OWC_Activity_Log
 *
 * @author  Yard | Digital Agency
 *
 * @since   1.0.0
 */

namespace OWCActivityLog\Providers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCActivityLog\Controllers\AdminPageController;

/**
 * Registers admin menu pages and enqueues assets.
 *
 * @since 1.0.0
 */
class AdminServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		add_action( 'admin_menu', $this->register_menu( ... ) );
		add_action( 'admin_enqueue_scripts', $this->enqueue_assets( ... ) );
	}

	/**
	 * Register the admin menu pages.
	 */
	public function register_menu(): void
	{
		$controller = new AdminPageController();

		add_menu_page(
			__( 'Activity Tracker', 'owc-activity-log' ),
			__( 'Activity Log', 'owc-activity-log' ),
			'manage_options',
			OWC_ACTIVITY_LOG_SLUG,
			$controller->render_log( ... ),
			'dashicons-visibility',
			75
		);

		// Re-register the first submenu entry with a display-friendly title.
		// Empty callback prevents a second render of the same page.
		add_submenu_page(
			OWC_ACTIVITY_LOG_SLUG,
			__( 'Activity Log', 'owc-activity-log' ),
			__( 'Activity Log', 'owc-activity-log' ),
			'manage_options',
			OWC_ACTIVITY_LOG_SLUG,
			''
		);

		add_submenu_page(
			OWC_ACTIVITY_LOG_SLUG,
			__( 'Settings', 'owc-activity-log' ),
			__( 'Settings', 'owc-activity-log' ),
			'manage_options',
			OWC_ACTIVITY_LOG_SLUG . '-settings',
			$controller->render_settings( ... )
		);
	}

	/**
	 * Enqueue admin styles and scripts on our own pages.
	 */
	public function enqueue_assets(string $hook ): void
	{
		$our_pages = array(
			'toplevel_page_' . OWC_ACTIVITY_LOG_SLUG,
			'activity-log_page_' . OWC_ACTIVITY_LOG_SLUG . '-settings',
		);

		if ( ! in_array( $hook, $our_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'owc-activity-log-admin',
			OWC_ACTIVITY_LOG_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			OWC_ACTIVITY_LOG_VERSION
		);

		wp_enqueue_script(
			'owc-activity-log-admin',
			OWC_ACTIVITY_LOG_PLUGIN_URL . 'assets/js/admin.js',
			array(),
			OWC_ACTIVITY_LOG_VERSION,
			true
		);

		wp_localize_script(
			'owc-activity-log-admin',
			'owcalData',
			array(
				'showDetails' => __( 'Details', 'owc-activity-log' ),
				'hideDetails' => __( 'Hide details', 'owc-activity-log' ),
			)
		);
	}
}
