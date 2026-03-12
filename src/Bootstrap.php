<?php

declare(strict_types=1);

/**
 * Bootstrap providers.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCActivityLog;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCActivityLog\Providers\AdminServiceProvider;
use OWCActivityLog\Providers\DatabaseServiceProvider;
use OWCActivityLog\Providers\ListenerServiceProvider;
use OWCActivityLog\Providers\MaintenanceServiceProvider;

require_once __DIR__ . '/helpers.php';

/**
 * Builds and boots all service providers.
 *
 * @since 1.0.0
 */
final class Bootstrap
{
	private array $providers;

	public function __construct()
	{
		$this->providers = $this->get_providers();
		$this->register_providers();
		$this->boot_providers();
	}

	protected function get_providers(): array
	{
		return array(
			new DatabaseServiceProvider(),
			new ListenerServiceProvider(),
			new AdminServiceProvider(),
			new MaintenanceServiceProvider(),
		);
	}

	protected function register_providers(): void
	{
		foreach ( $this->providers as $provider ) {
			$provider->register();
		}
	}

	protected function boot_providers(): void
	{
		foreach ( $this->providers as $provider ) {
			$provider->boot();
		}
	}
}
