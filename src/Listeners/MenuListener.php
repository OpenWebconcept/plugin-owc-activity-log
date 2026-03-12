<?php

declare(strict_types=1);

/**
 * Menu listener.
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
 * Listens to navigation menu events.
 *
 * @since 1.0.0
 */
class MenuListener extends AbstractListener
{
	public function get_hooks(): array
	{
		return array(
			'wp_update_nav_menu'      => array( 'on_update_menu', 10, 2 ),
			'wp_delete_nav_menu'      => array( 'on_delete_menu', 10, 1 ),
			'wp_add_nav_menu_item'    => array( 'on_add_menu_item', 10, 3 ),
			'wp_update_nav_menu_item' => array( 'on_update_menu_item', 10, 3 ),
		);
	}

	public function on_update_menu( int $menu_id, array $_menu_data = array() ): void
	{
		$menu = wp_get_nav_menu_object( $menu_id );

		$this->log(
			'menus',
			'updated_menu',
			sprintf(
				/* translators: 1: menu name */
				__( 'Navigation menu "%1$s" updated.', 'owc-activity-log' ),
				$menu ? $menu->name : "#{$menu_id}"
			),
			array(
				'object_id'   => $menu_id,
				'object_type' => 'nav_menu',
			)
		);
	}

	public function on_delete_menu( mixed $menu ): void
	{
		$menu_name = is_object( $menu ) ? $menu->name : (string) $menu;

		$this->log(
			'menus',
			'deleted_menu',
			sprintf(
				/* translators: 1: menu name */
				__( 'Navigation menu "%1$s" deleted.', 'owc-activity-log' ),
				$menu_name
			),
			array(
				'object_type' => 'nav_menu',
				'meta'        => array( 'menu' => $menu_name ),
			)
		);
	}

	public function on_add_menu_item( int $menu_id, int $menu_item_db_id, array $_args ): void
	{
		$menu = wp_get_nav_menu_object( $menu_id );

		$this->log(
			'menus',
			'added_menu_item',
			sprintf(
				/* translators: 1: menu name */
				__( 'Item added to navigation menu "%1$s".', 'owc-activity-log' ),
				$menu ? $menu->name : "#{$menu_id}"
			),
			array(
				'object_id'   => $menu_id,
				'object_type' => 'nav_menu',
				'meta'        => array( 'menu_item_id' => $menu_item_db_id ),
			)
		);
	}

	public function on_update_menu_item( int $menu_id, int $menu_item_db_id, array $_args ): void
	{
		$menu = wp_get_nav_menu_object( $menu_id );

		$this->log(
			'menus',
			'updated_menu_item',
			sprintf(
				/* translators: 1: menu name */
				__( 'Item updated in navigation menu "%1$s".', 'owc-activity-log' ),
				$menu ? $menu->name : "#{$menu_id}"
			),
			array(
				'object_id'   => $menu_id,
				'object_type' => 'nav_menu',
				'meta'        => array( 'menu_item_id' => $menu_item_db_id ),
			)
		);
	}
}
