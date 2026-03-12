<?php

declare(strict_types=1);

/**
 * Listener service provider.
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

use Closure;
use OWCActivityLog\Listeners\CommentListener;
use OWCActivityLog\Listeners\MediaListener;
use OWCActivityLog\Listeners\MenuListener;
use OWCActivityLog\Listeners\MetaListener;
use OWCActivityLog\Listeners\OptionListener;
use OWCActivityLog\Listeners\PluginListener;
use OWCActivityLog\Listeners\PostListener;
use OWCActivityLog\Listeners\TaxonomyListener;
use OWCActivityLog\Listeners\ThemeListener;
use OWCActivityLog\Listeners\UserListener;
use OWCActivityLog\Listeners\WidgetListener;

/**
 * Registers all activity listeners with WordPress hooks.
 *
 * @since 1.0.0
 */
class ListenerServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$listeners = array(
			new PostListener(),
			new MetaListener(),
			new UserListener(),
			new OptionListener(),
			new TaxonomyListener(),
			new CommentListener(),
			new MediaListener(),
			new PluginListener(),
			new ThemeListener(),
			new MenuListener(),
			new WidgetListener(),
		);

		foreach ( $listeners as $listener ) {
			foreach ( $listener->get_hooks() as $hook => $config ) {
				add_action(
					$hook,
					Closure::fromCallable( array( $listener, $config[0] ) ),
					$config[1],
					$config[2]
				);
			}
		}

		// WidgetListener also registers a filter.
		foreach ( $listeners as $listener ) {
			if ( $listener instanceof WidgetListener ) {
				$listener->register_filter();
			}
		}
	}
}
