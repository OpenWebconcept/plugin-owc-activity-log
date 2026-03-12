<?php

declare(strict_types=1);

/**
 * Database schema management.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCActivityLog\Database;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates and updates the activity log database table.
 *
 * @since 1.0.0
 */
class Schema
{
	public static function create_table(): void
	{
		global $wpdb;

		$table_name      = $wpdb->prefix . 'total_activity_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			created_at   DATETIME NOT NULL,
			`group`      VARCHAR(50) NOT NULL DEFAULT '',
			action       VARCHAR(100) NOT NULL DEFAULT '',
			message      TEXT NOT NULL,
			user_id      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			user_login   VARCHAR(60) NOT NULL DEFAULT '',
			object_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			object_type  VARCHAR(50) NOT NULL DEFAULT '',
			meta         LONGTEXT NULL,
			ip           VARCHAR(45) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY grp      (`group`),
			KEY action   (action),
			KEY user_id  (user_id),
			KEY created_at (created_at),
			KEY object_id  (object_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop the activity log table.
	 */
	public static function drop_table(): void
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'total_activity_log';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $table_name ) . '`' );
	}
}
