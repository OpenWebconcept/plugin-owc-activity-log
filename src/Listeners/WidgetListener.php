<?php

declare(strict_types=1);

/**
 * Widget listener.
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

/**
 * Listens to widget and sidebar changes.
 *
 * @since 1.0.0
 */
class WidgetListener extends AbstractListener
{
	public function get_hooks(): array
	{
		return array(
			'update_option_sidebars_widgets' => array( 'on_sidebars_changed', 10, 2 ),
		);
	}

	/**
	 * Register the widget_update_callback filter separately as it is a filter, not an action.
	 */
	public function register_filter(): void
	{
		add_filter( 'widget_update_callback', $this->on_widget_updated( ... ), 10, 4 );
	}

	/**
	 * Fires when the sidebars_widgets option is saved.
	 */
	public function on_sidebars_changed( mixed $old_value, mixed $new_value ): void
	{
		$old_sidebars = is_array( $old_value ) ? $old_value : array();
		$new_sidebars = is_array( $new_value ) ? $new_value : array();

		$changed = array();

		foreach ( array_keys( $new_sidebars ) as $sidebar_id ) {
			if ( 'array_version' === $sidebar_id ) continue;

			$old_widgets = $old_sidebars[ $sidebar_id ] ?? array();
			$new_widgets = $new_sidebars[ $sidebar_id ] ?? array();

			if ( $old_widgets !== $new_widgets ) {
				$changed[ $sidebar_id ] = array(
					'removed' => array_values( array_diff( (array) $old_widgets, (array) $new_widgets ) ),
					'added'   => array_values( array_diff( (array) $new_widgets, (array) $old_widgets ) ),
				);
			}
		}

		if ( empty( $changed ) ) return;

		$this->log(
			'widgets',
			'sidebars_changed',
			__( 'Widget sidebar assignments changed.', 'owc-activity-log' ),
			array(
				'object_type' => 'widget',
				'meta'        => array( 'changed_sidebars' => $changed ),
			)
		);
	}

	/**
	 * Fires when an individual widget's settings are updated.
	 */
	public function on_widget_updated(
		array $instance,
		array $new_instance,
		array $old_instance,
		mixed $widget
	): array {
		$widget_id = is_object( $widget ) && isset( $widget->id ) ? $widget->id : 'unknown';

		$this->log(
			'widgets',
			'widget_updated',
			sprintf(
				/* translators: 1: widget ID */
				__( 'Widget "%1$s" settings updated.', 'owc-activity-log' ),
				$widget_id
			),
			array(
				'object_type' => 'widget',
				'meta'        => array( 'widget_id' => $widget_id ),
			)
		);

		return $instance;
	}
}
