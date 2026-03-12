<?php

declare(strict_types=1);

/**
 * Maintenance service provider.
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

use OWCActivityLog\Database\ActivityRepository;

/**
 * Schedules and runs the periodic log cleanup cron job.
 *
 * @since 1.0.0
 */
class MaintenanceServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		if ( ! wp_next_scheduled( OWC_ACTIVITY_LOG_CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', OWC_ACTIVITY_LOG_CRON_HOOK );
		}

		add_action( OWC_ACTIVITY_LOG_CRON_HOOK, $this->run_cleanup( ... ) );
	}

	/**
	 * Delete log entries older than the configured retention period.
	 */
	public function run_cleanup(): void
	{
		$settings = owc_activity_log_get_settings();
		$days     = (int) apply_filters( 'owc_activity_log_retention_days', $settings['retention_days'] );
		$date     = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		( new ActivityRepository() )->delete_before( $date );
	}
}
