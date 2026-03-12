<?php

declare(strict_types=1);

/**
 * Theme listener.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCActivityLog\Listeners;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCActivityLog\Contracts\AbstractListener;
use WP_Theme;

/**
 * Listens to theme switch and upgrade events.
 *
 * @since 1.0.0
 */
class ThemeListener extends AbstractListener
{
	public function get_hooks(): array
	{
		return array(
			'switch_theme'              => array( 'on_switch_theme', 10, 3 ),
			'upgrader_process_complete' => array( 'on_upgrader_complete', 10, 2 ),
		);
	}

	public function on_switch_theme( string $new_name, WP_Theme $new_theme, WP_Theme $old_theme ): void
	{
		$this->log(
			'themes',
			'switched',
			sprintf(
				/* translators: 1: old theme, 2: new theme */
				__( 'Theme switched from "%1$s" to "%2$s".', 'owc-activity-log' ),
				$old_theme->get( 'Name' ),
				$new_name
			),
			array(
				'object_type' => 'theme',
				'meta'        => array(
					'old_theme'     => $old_theme->get( 'Name' ),
					'new_theme'     => $new_name,
					'new_theme_ver' => $new_theme->get( 'Version' ),
				),
			)
		);
	}

	/**
	 * Fires after a theme update/install via the upgrader.
	 */
	public function on_upgrader_complete( mixed $upgrader, array $hook_extra ): void
	{
		if ( 'theme' !== ( $hook_extra['type'] ?? '' ) ) return;

		$action = $hook_extra['action'] ?? 'update';
		$themes = $hook_extra['themes'] ?? array( $hook_extra['theme'] ?? '' );

		foreach ( $themes as $theme_slug ) {
			if ( ! is_string( $theme_slug ) || '' === trim( $theme_slug ) ) continue;

			$theme_slug = trim( $theme_slug );
			$theme      = wp_get_theme( $theme_slug );

			$this->log(
				'themes',
				$action,
				sprintf(
					/* translators: 1: theme name, 2: action */
					__( 'Theme "%1$s" %2$s via upgrader.', 'owc-activity-log' ),
					$theme->get( 'Name' ) instanceof WP_Theme ? $theme->get( 'Name' ) : $theme_slug,
					$action
				),
				array(
					'object_type' => 'theme',
					'meta'        => array( 'theme_slug' => $theme_slug ),
				)
			);
		}
	}
}
