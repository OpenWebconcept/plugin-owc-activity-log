<?php

declare(strict_types=1);

/**
 * Activity log WP_List_Table.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCActivityLog\Admin;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OWCActivityLog\Database\ActivityRepository;
use WP_User;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Renders paginated, sortable, filterable activity log table.
 *
 * @since 1.0.0
 */
class ActivityLogTable extends \WP_List_Table
{
	private ActivityRepository $repository;
	private array $filters;

	public function __construct()
	{
		parent::__construct(
			array(
				'singular' => 'activity',
				'plural'   => 'activities',
				'ajax'     => false,
			)
		);

		$this->repository = new ActivityRepository();
		$this->filters    = $this->parse_filters();
	}

	public function get_columns(): array
	{
		$columns = array(
			'created_at'  => __( 'Date / time', 'owc-activity-log' ),
			'group'       => __( 'Group', 'owc-activity-log' ),
			'action'      => __( 'Action', 'owc-activity-log' ),
			'message'     => __( 'Message', 'owc-activity-log' ),
			'user_login'  => __( 'User', 'owc-activity-log' ),
			'object_type' => __( 'Object type', 'owc-activity-log' ),
		);

		if ( owc_activity_log_get_settings()['log_ip'] ) {
			$columns['ip'] = __( 'IP', 'owc-activity-log' );
		}

		return $columns;
	}

	public function get_sortable_columns(): array
	{
		return array(
			'created_at'  => array( 'created_at', true ),
			'group'       => array( 'group', false ),
			'action'      => array( 'action', false ),
			'user_login'  => array( 'user_login', false ),
			'object_type' => array( 'object_type', false ),
		);
	}

	protected function get_default_primary_column_name(): string
	{
		return 'message';
	}

	/**
	 * Prepare table items (runs queries).
	 */
	public function prepare_items(): void
	{
		$per_page     = 50;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;
		$total        = $this->repository->count( $this->filters );

		$this->items = $this->repository->query( $this->filters, $per_page, $offset );

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total / $per_page ),
			)
		);

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}

	/**
	 * Default column renderer.
	 */
	protected function column_default( $item, $column_name ): string
	{
		return isset( $item[ $column_name ] )
			? esc_html( $item[ $column_name ] )
			: '—';
	}

	protected function column_created_at( $item ): string
	{
		$timestamp = strtotime( $item['created_at'] );

		if ( ! $timestamp ) {
			return esc_html( $item['created_at'] );
		}

		return sprintf(
			'<span title="%s">%s</span>',
			esc_attr( $item['created_at'] ),
			esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) )
		);
	}

	protected function column_group( $item ): string
	{
		return sprintf(
			'<span class="owcal-badge owcal-badge--%s">%s</span>',
			esc_attr( $item['group'] ),
			esc_html( $item['group'] )
		);
	}

	protected function column_message( $item ): string
	{
		$output = '<span class="owcal-message-text">' . esc_html( $item['message'] ) . '</span>';

		if ( ! isset( $item['meta'] ) || ! is_string( $item['meta'] ) ) {
			return $output;
		}

		$decoded = json_decode( $item['meta'], true );

		if ( ! is_array( $decoded ) ) {
			return $output;
		}

		// Add toggle button.
		$output .= '<button type="button" class="button button-small owcal-toggle">'
			. esc_html__( 'Details', 'owc-activity-log' )
			. '</button>';

		// Add hidden pre block with pretty-printed JSON.
		$output .= '<pre class="owcal-meta">'
			. esc_html( wp_json_encode( $decoded, JSON_PRETTY_PRINT ) )
			. '</pre>';

		return $output;
	}

	protected function column_user_login( $item ): string
	{
		if ( ! isset( $item['user_login'] ) ) {
			return '—';
		}

		$user = get_user_by( 'login', $item['user_login'] );

		if ( ! $user instanceof WP_User ) {
			return esc_html( $item['user_login'] );
		}

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( get_edit_user_link( $user->ID ) ),
			esc_html( $item['user_login'] )
		);
	}

	/**
	 * Parse and sanitize $_GET filter params.
	 */
	private function parse_filters(): array
	{
		$filters = array();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['at_group'] ) )
			$filters['group'] = sanitize_text_field( wp_unslash( $_GET['at_group'] ) );

		if ( ! empty( $_GET['at_action'] ) )
			$filters['action'] = sanitize_text_field( wp_unslash( $_GET['at_action'] ) );

		if ( ! empty( $_GET['at_user'] ) )
			$filters['user_id'] = (int) $_GET['at_user'];

		if ( ! empty( $_GET['at_object_type'] ) )
			$filters['object_type'] = sanitize_text_field( wp_unslash( $_GET['at_object_type'] ) );

		if ( ! empty( $_GET['at_date_from'] ) )
			$filters['date_from'] = sanitize_text_field( wp_unslash( $_GET['at_date_from'] ) );

		if ( ! empty( $_GET['at_date_to'] ) )
			$filters['date_to'] = sanitize_text_field( wp_unslash( $_GET['at_date_to'] ) );

		if ( ! empty( $_GET['s'] ) )
			$filters['search'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );

		if ( ! empty( $_GET['orderby'] ) )
			$filters['orderby'] = sanitize_key( $_GET['orderby'] );

		if ( ! empty( $_GET['order'] ) )
			$filters['order'] = sanitize_key( $_GET['order'] );
		// phpcs:enable

		return $filters;
	}
}
