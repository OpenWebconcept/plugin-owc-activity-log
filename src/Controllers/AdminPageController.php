<?php

declare(strict_types=1);

/**
 * Admin page controller.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCActivityLog\Controllers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCActivityLog\Admin\ActivityLogTable;
use OWCActivityLog\Database\ActivityRepository;

/**
 * Renders admin pages for the activity tracker.
 *
 * @since 1.0.0
 */
class AdminPageController
{
	/**
	 * Render the activity log page.
	 */
	public function render_log(): void
	{
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'owc-activity-log' ) );
		}

		$table        = new ActivityLogTable();
		$repository   = new ActivityRepository();
		$groups       = $repository->get_groups();
		$object_types = $repository->get_object_types();

		$table->prepare_items();

		owc_activity_log_render_view(
			'admin/log-page',
			array(
				'table'        => $table,
				'groups'       => $groups,
				'object_types' => $object_types,
			)
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings(): void
	{
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'owc-activity-log' ) );
		}

		if ( isset( $_POST['owc_at_settings_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['owc_at_settings_nonce'] ) ), 'owc_at_save_settings' )
		) {
			$this->save_settings();
		}

		owc_activity_log_render_view( 'admin/settings-page' );
	}

	/**
	 * Save settings from POST data.
	 */
	private function save_settings(): void
	{
		$all_groups = array( 'posts', 'meta', 'users', 'options', 'taxonomy', 'comments', 'media', 'plugins', 'themes', 'menus', 'widgets' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- already verified above
		$retention_days = isset( $_POST['retention_days'] ) ? absint( $_POST['retention_days'] ) : OWC_ACTIVITY_LOG_DEFAULT_RETENTION_DAYS;
		$retention_days = max( 1, min( 3650, $retention_days ) );

		$raw_meta_keys = isset( $_POST['ignored_meta_keys'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ignored_meta_keys'] ) ) : '';
		$ignored_meta  = array_filter( array_map( 'trim', explode( "\n", $raw_meta_keys ) ) );

		$raw_options     = isset( $_POST['ignored_option_names'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ignored_option_names'] ) ) : '';
		$ignored_options = array_filter( array_map( 'trim', explode( "\n", $raw_options ) ) );

		$posted_groups  = isset( $_POST['enabled_groups'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['enabled_groups'] ) ) : array();
		$enabled_groups = array_intersect( $posted_groups, $all_groups );

		$log_ip = isset( $_POST['log_ip'] ) && '1' === $_POST['log_ip'];
		// phpcs:enable

		update_option(
			OWC_ACTIVITY_LOG_SETTINGS_KEY,
			array(
				'retention_days'       => $retention_days,
				'log_ip'               => $log_ip,
				'ignored_meta_keys'    => array_values( $ignored_meta ),
				'ignored_option_names' => array_values( $ignored_options ),
				'enabled_groups'       => array_values( $enabled_groups ),
			)
		);

		add_settings_error(
			'owc_at_settings',
			'saved',
			__( 'Settings saved.', 'owc-activity-log' ),
			'updated'
		);
	}
}
