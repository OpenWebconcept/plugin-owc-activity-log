<?php

declare(strict_types=1);

/**
 * Abstract service provider.
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

/**
 * Base service provider.
 *
 * @since 1.0.0
 */
abstract class ServiceProvider
{
	/**
	 * Register hooks and bindings.
	 */
	abstract public function register(): void;

	/**
	 * Boot the provider after all providers are registered.
	 */
	public function boot(): void
	{
		// Override in subclass if needed.
	}
}
