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

		if ( ! is_array( $decoded ) || array() === $decoded ) {
			return $output;
		}

		// Always-visible, colour-coded badges so what changed is scannable
		// without opening "Details" (e.g. per-field added/removed/changed for
		// Gravity Forms). Silent no-op for meta shapes without any status items.
		$output .= $this->render_quick_summary( $decoded );

		// Add toggle button.
		$output .= '<button type="button" class="button button-small owcal-toggle">'
			. esc_html__( 'Details', 'owc-activity-log' )
			. '</button>';

		// Add hidden, human-readable rendering of the meta.
		$output .= '<div class="owcal-meta">' . $this->render_meta( $decoded ) . '</div>';

		return $output;
	}

	/**
	 * Render always-visible badges for every added/removed/changed item found
	 * in the meta (e.g. GravityFormsListener's per-field statuses), so the
	 * gist of a change is visible at a glance, without opening "Details".
	 */
	private function render_quick_summary( array $meta ): string
	{
		$items = $this->find_status_items( $meta );

		if ( array() === $items ) {
			return '';
		}

		$badges = '';

		foreach ( $items as $item ) {
			$properties = $item['properties'] ?? null;
			$detail     = ! is_array( $properties ) || array() === $properties ? '' : ' — ' . implode( ', ', $properties );

			$badges .= sprintf(
				'<span class="owcal-quick-badge"><span class="owcal-status owcal-status--%1$s">%2$s</span> %3$s%4$s</span>',
				esc_attr( $item['status'] ),
				esc_html( $this->status_label( $item['status'] ) ),
				esc_html( $item['label'] ),
				esc_html( $detail )
			);
		}

		return '<div class="owcal-quick-summary">' . $badges . '</div>';
	}

	/**
	 * Human-readable label for an added/removed/changed status.
	 */
	private function status_label( string $status ): string
	{
		$tags = array(
			'added'   => __( 'Added', 'owc-activity-log' ),
			'removed' => __( 'Removed', 'owc-activity-log' ),
			'changed' => __( 'Changed', 'owc-activity-log' ),
		);

		return $tags[ $status ] ?? $status;
	}

	/**
	 * Recursively find every `{label, status}` item in a meta array
	 * (status being one of added/removed/changed), regardless of nesting depth.
	 *
	 * For "changed" items, also surface which specific properties changed
	 * (e.g. just "Description") so that's visible without opening Details.
	 */
	private function find_status_items( array $data ): array
	{
		if ( isset( $data['label'], $data['status'] )
			&& in_array( $data['status'], array( 'added', 'removed', 'changed' ), true )
		) {
			$properties = array();

			if ( 'changed' === $data['status'] && is_array( $data['changed'] ?? null ) && array() !== $data['changed'] ) {
				$properties = array_map( array( $this, 'humanize_key' ), array_keys( $data['changed'] ) );
			}

			return array(
				array(
					'label'      => (string) $data['label'],
					'status'     => (string) $data['status'],
					'properties' => $properties,
				),
			);
		}

		$items = array();

		foreach ( $data as $value ) {
			if ( is_array( $value ) ) {
				$items = array_merge( $items, $this->find_status_items( $value ) );
			}
		}

		return $items;
	}

	/**
	 * Render a decoded meta array as a human-readable definition list instead of raw JSON.
	 */
	private function render_meta( array $meta ): string
	{
		$rows = '';

		foreach ( $meta as $key => $value ) {
			$rows .= $this->render_meta_row( is_string( $key ) ? $this->humanize_key( $key ) : '', $value );
		}

		return '<dl class="owcal-meta-list">' . $rows . '</dl>';
	}

	/**
	 * Render a single meta row, recognising common change shapes (before/after
	 * pairs, added/removed lists, field-level statuses) so they read naturally
	 * instead of as raw nested JSON.
	 */
	private function render_meta_row( string $label, mixed $value ): string
	{
		$value = $this->maybe_decode_json( $value );

		if ( is_array( $value ) && isset( $value['label'], $value['status'] )
			&& in_array( $value['status'], array( 'added', 'removed', 'changed' ), true )
		) {
			return $this->render_status_row( $label, $value );
		}

		if ( is_array( $value ) && array_key_exists( 'from', $value ) && array_key_exists( 'to', $value ) ) {
			return $this->render_change_row( $label, $value['from'], $value['to'] );
		}

		if ( is_array( $value ) && ( array_key_exists( 'added', $value ) || array_key_exists( 'removed', $value ) ) ) {
			return $this->render_add_remove_row(
				$label,
				(array) ( $value['added'] ?? array() ),
				(array) ( $value['removed'] ?? array() )
			);
		}

		if ( is_array( $value ) && array_is_list( $value ) ) {
			return $this->render_scalar_row( $label, implode( ', ', array_map( array( $this, 'stringify_scalar' ), $value ) ) );
		}

		if ( is_array( $value ) ) {
			return sprintf(
				'<div class="owcal-meta-group"><dt>%s</dt><dd>%s</dd></div>',
				esc_html( $label ),
				$this->render_meta( $value )
			);
		}

		return $this->render_scalar_row( $label, $this->stringify_scalar( $value ) );
	}

	/**
	 * Render a field-level status row (added / removed / changed), as produced by GravityFormsListener.
	 */
	private function render_status_row( string $label, array $item ): string
	{
		$label  = '' !== (string) ( $item['label'] ?? '' ) ? (string) $item['label'] : $label;
		$status = (string) $item['status'];

		$output = sprintf(
			'<div class="owcal-meta-group"><dt>%s <span class="owcal-status owcal-status--%s">%s</span></dt>',
			esc_html( $label ),
			esc_attr( $status ),
			esc_html( $this->status_label( $status ) )
		);

		if ( 'changed' === $status && is_array( $item['changed'] ?? null ) && array() !== $item['changed'] ) {
			$output .= '<dd>' . $this->render_meta( $item['changed'] ) . '</dd>';
		}

		return $output . '</div>';
	}

	/**
	 * Render a "from → to" row as a red/green diff, the way most people
	 * recognise changes from track-changes or version-control tools.
	 */
	private function render_change_row( string $label, mixed $from, mixed $to ): string
	{
		return sprintf(
			'<div class="owcal-meta-row"><dt>%s</dt><dd><span class="owcal-from">− %s</span><span class="owcal-to">+ %s</span></dd></div>',
			esc_html( $label ),
			esc_html( $this->stringify_scalar( $this->maybe_decode_json( $from ) ) ),
			esc_html( $this->stringify_scalar( $this->maybe_decode_json( $to ) ) )
		);
	}

	/**
	 * Render an "added / removed" list row.
	 */
	private function render_add_remove_row( string $label, array $added, array $removed ): string
	{
		$parts = array();

		if ( array() !== $added ) {
			$parts[] = sprintf(
				/* translators: %s: comma-separated list of added items */
				__( 'added: %s', 'owc-activity-log' ),
				implode( ', ', array_map( array( $this, 'stringify_scalar' ), $added ) )
			);
		}

		if ( array() !== $removed ) {
			$parts[] = sprintf(
				/* translators: %s: comma-separated list of removed items */
				__( 'removed: %s', 'owc-activity-log' ),
				implode( ', ', array_map( array( $this, 'stringify_scalar' ), $removed ) )
			);
		}

		if ( array() === $parts ) {
			$parts[] = __( '(no change)', 'owc-activity-log' );
		}

		return $this->render_scalar_row( $label, implode( ' · ', $parts ) );
	}

	/**
	 * Render a simple "label: value" row.
	 */
	private function render_scalar_row( string $label, string $value ): string
	{
		return sprintf(
			'<div class="owcal-meta-row"><dt>%s</dt><dd>%s</dd></div>',
			esc_html( $label ),
			esc_html( $value )
		);
	}

	/**
	 * Convert a scalar meta value into a human-friendly string.
	 */
	private function stringify_scalar( mixed $value ): string
	{
		if ( null === $value ) {
			return __( '(none)', 'owc-activity-log' );
		}

		if ( is_bool( $value ) ) {
			return $value ? __( 'Yes', 'owc-activity-log' ) : __( 'No', 'owc-activity-log' );
		}

		if ( '' === $value ) {
			return __( '(empty)', 'owc-activity-log' );
		}

		if ( is_array( $value ) ) {
			return implode( ', ', array_map( array( $this, 'stringify_scalar' ), $value ) );
		}

		return (string) $value;
	}

	/**
	 * Turn a "field_key"/"fieldKey" into "Field key" for display.
	 */
	private function humanize_key( string $key ): string
	{
		if ( '' === $key ) {
			return '';
		}

		$spaced = (string) preg_replace( '/(?<!^)[A-Z]/', ' $0', $key );
		$spaced = str_replace( array( '_', '-' ), ' ', $spaced );
		$spaced = trim( (string) preg_replace( '/\s+/', ' ', $spaced ) );

		return ucfirst( strtolower( $spaced ) );
	}

	/**
	 * Decode a value if it is a JSON-encoded string (as produced by
	 * AbstractListener::truncate()), so it can be rendered instead of dumped as text.
	 */
	private function maybe_decode_json( mixed $value ): mixed
	{
		if ( ! is_string( $value ) || ! isset( $value[0] ) || ! in_array( $value[0], array( '{', '[' ), true ) ) {
			return $value;
		}

		$decoded = json_decode( $value, true );

		return JSON_ERROR_NONE === json_last_error() ? $decoded : $value;
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
