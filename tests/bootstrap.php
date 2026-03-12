<?php

declare(strict_types=1);

/**
 * Pest / WP_Mock bootstrap.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

// Patchwork must be loaded before autoload so it can intercept user-defined functions.
require_once dirname( __DIR__ ) . '/vendor/antecedent/patchwork/Patchwork.php';

// Define ABSPATH before loading autoload so helpers.php guard passes.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once dirname( __DIR__ ) . '/src/helpers.php';

// Stub WP_User so tests can use new WP_User() and Mockery::mock(WP_User::class).
if ( ! class_exists( 'WP_User' ) ) {
	class WP_User
	{
		public int $ID              = 0;
		public string $user_login   = '';
		public string $user_email   = '';
		public string $display_name = '';
		public string $user_url     = '';
	}
}

// Plugin constants used throughout the codebase.
if ( ! defined( 'OWC_ACTIVITY_LOG_VERSION' ) ) {
	define( 'OWC_ACTIVITY_LOG_VERSION', '1.0.0' );
}

if ( ! defined( 'OWC_ACTIVITY_LOG_FILE' ) ) {
	define( 'OWC_ACTIVITY_LOG_FILE', dirname( __DIR__ ) . '/owc-activity-log.php' );
}

if ( ! defined( 'OWC_ACTIVITY_LOG_DIR_PATH' ) ) {
	define( 'OWC_ACTIVITY_LOG_DIR_PATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'OWC_ACTIVITY_LOG_PLUGIN_URL' ) ) {
	define( 'OWC_ACTIVITY_LOG_PLUGIN_URL', 'https://example.com/wp-content/plugins/owc-activity-log/' );
}

if ( ! defined( 'OWC_ACTIVITY_LOG_SLUG' ) ) {
	define( 'OWC_ACTIVITY_LOG_SLUG', 'owc-activity-log' );
}

if ( ! defined( 'OWC_ACTIVITY_LOG_DB_OPTION' ) ) {
	define( 'OWC_ACTIVITY_LOG_DB_OPTION', 'owc_activity_log_db_version' );
}

if ( ! defined( 'OWC_ACTIVITY_LOG_SETTINGS_KEY' ) ) {
	define( 'OWC_ACTIVITY_LOG_SETTINGS_KEY', 'owc_activity_log_settings' );
}

if ( ! defined( 'OWC_ACTIVITY_LOG_CRON_HOOK' ) ) {
	define( 'OWC_ACTIVITY_LOG_CRON_HOOK', 'owc_activity_log_cleanup' );
}

if ( ! defined( 'OWC_ACTIVITY_LOG_DEFAULT_RETENTION_DAYS' ) ) {
	define( 'OWC_ACTIVITY_LOG_DEFAULT_RETENTION_DAYS', 30 );
}

WP_Mock::bootstrap();
