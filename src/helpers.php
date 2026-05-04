<?php

declare(strict_types=1);

/**
 * Plugin helper functions.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a view template.
 *
 * @since 1.0.0
 */
function owc_activity_log_render_view( string $view, array $data = array() ): void
{
	$file = OWC_ACTIVITY_LOG_DIR_PATH . 'src/Views/' . $view . '.php';

	if ( ! file_exists( $file ) ) {
		return;
	}

	// Extract data into local scope.
	extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

	include $file;
}

/**
 * Get plugin settings.
 *
 * @since 1.0.0
 */
function owc_activity_log_get_settings(): array
{
	$defaults = array(
		'retention_days'       => OWC_ACTIVITY_LOG_DEFAULT_RETENTION_DAYS,
		'log_ip'               => false,
		'ignored_meta_keys'    => array(),
		'ignored_option_names' => array(),
		'enabled_groups'       => array(
			'posts',
			'meta',
			'users',
			'options',
			'taxonomy',
			'comments',
			'media',
			'plugins',
			'themes',
			'menus',
			'widgets',
		),
	);

	$saved = get_option( OWC_ACTIVITY_LOG_SETTINGS_KEY, array() );
	$saved = wp_parse_args( $saved, $defaults );

	return is_array( $saved ) ? $saved : $defaults;
}

/**
 * Check whether a given group is enabled in settings.
 *
 * @since 1.0.0
 */
function owc_activity_log_group_enabled( string $group ): bool
{
	$settings = owc_activity_log_get_settings();

	return in_array( $group, $settings['enabled_groups'], true );
}

/**
 * @since NEXT
 */
function settings_page_cap(): string
{
	$cap = apply_filters( 'owc_activity_log_admin_page_overview_cap', OWC_ACTIVITY_LOG_SETTINGS_PAGE_CAP );

	return is_string( $cap ) && '' !== trim( $cap ) ? $cap : OWC_ACTIVITY_LOG_SETTINGS_PAGE_CAP;
}
