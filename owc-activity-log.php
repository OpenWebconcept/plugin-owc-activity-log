<?php

declare(strict_types=1);

/**
 * OWC Activity Log.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 *
 * Plugin Name:       OWC Activity Log
 * Plugin URI:        https://github.com/OpenWebconcept/plugin-owc-activity-log
 * Description:       Tracks all WordPress activity such as posts, meta, options, users, taxonomy, comments, plugins, themes and more.
 * Version:           1.0.0
 * Author:            Yard | Digital Agency
 * Author URI:        https://www.yard.nl
 * License:           EUPL
 * License URI:       https://github.com/OpenWebconcept/plugin-owc-activity-log/blob/main/LICENSE.txt
 * Text Domain:       owc-activity-log
 * Domain Path:       /languages
 * Requires at least: 6.7
 * Requires PHP:      8.1
 */

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const OWC_ACTIVITY_LOG_VERSION     = '1.0.0';
const OWC_ACTIVITY_LOG_REQUIRED_WP = '6.7';
const OWC_ACTIVITY_LOG_FILE        = __FILE__;

define( 'OWC_ACTIVITY_LOG_DIR_PATH', plugin_dir_path( OWC_ACTIVITY_LOG_FILE ) );
define( 'OWC_ACTIVITY_LOG_PLUGIN_URL', plugins_url( '/', OWC_ACTIVITY_LOG_FILE ) );

const OWC_ACTIVITY_LOG_SLUG                   = 'owc-activity-log';
const OWC_ACTIVITY_LOG_DB_OPTION              = 'owc_activity_log_db_version';
const OWC_ACTIVITY_LOG_SETTINGS_KEY           = 'owc_activity_log_settings';
const OWC_ACTIVITY_LOG_CRON_HOOK              = 'owc_activity_log_cleanup';
const OWC_ACTIVITY_LOG_DEFAULT_RETENTION_DAYS = 30;

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/autoload.php';
require_once __DIR__ . '/src/Bootstrap.php';

register_activation_hook(
	OWC_ACTIVITY_LOG_FILE,
	function () {
		OWCActivityLog\Database\Schema::create_table();
	}
);

register_deactivation_hook(
	OWC_ACTIVITY_LOG_FILE,
	function () {
		wp_clear_scheduled_hook( OWC_ACTIVITY_LOG_CRON_HOOK );
	}
);

add_action(
	'plugins_loaded',
	function () {
		new OWCActivityLog\Bootstrap();
	}
);
