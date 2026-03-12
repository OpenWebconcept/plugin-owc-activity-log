<?php

declare(strict_types=1);

/**
 * Database service provider.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCActivityLog\Providers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCActivityLog\Database\Schema;

/**
 * Ensures the database table exists and is up to date.
 *
 * @since 1.0.0
 */
class DatabaseServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$installed = get_option( OWC_ACTIVITY_LOG_DB_OPTION, '0' );

		if ( version_compare( $installed, OWC_ACTIVITY_LOG_VERSION, '<' ) ) {
			Schema::create_table();
			update_option( OWC_ACTIVITY_LOG_DB_OPTION, OWC_ACTIVITY_LOG_VERSION );
		}
	}
}
