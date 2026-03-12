<?php

declare(strict_types=1);

/**
 * Activity log repository.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace OWCActivityLog\Database;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all database operations for the activity log.
 *
 * @since 1.0.0
 */
class ActivityRepository
{
	private \wpdb $wpdb;
	private string $table;

	public function __construct()
	{
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $this->wpdb->prefix . 'total_activity_log';
	}

	/**
	 * Insert an activity log entry.
	 */
	public function insert( array $data ): bool
	{
		$result = $this->wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$this->table,
			array(
				'created_at'  => $data['created_at'] ?? current_time( 'mysql', true ),
				'group'       => $data['group'] ?? '',
				'action'      => $data['action'] ?? '',
				'message'     => $data['message'] ?? '',
				'user_id'     => $data['user_id'] ?? 0,
				'user_login'  => $data['user_login'] ?? '',
				'object_id'   => $data['object_id'] ?? 0,
				'object_type' => $data['object_type'] ?? '',
				'meta'        => $data['meta'] ?? null,
				'ip'          => $data['ip'] ?? '',
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( false !== $result ) {
			$this->invalidate_cache();
		}

		return false !== $result;
	}

	/**
	 * Query log entries with optional filters.
	 */
	public function query( array $filters = array(), int $per_page = 50, int $offset = 0 ): array
	{
		$cache_key = 'query_' . md5( serialize( $filters ) . $per_page . $offset . $this->get_last_changed() ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$cached    = wp_cache_get( $cache_key, 'owc_activity_log' );

		if ( false !== $cached ) {
			return $cached;
		}

		list( $where, $values ) = $this->build_where( $filters );

		$orderby = isset( $filters['orderby'] ) ? sanitize_key( $filters['orderby'] ) : 'created_at';
		$order   = isset( $filters['order'] ) && 'asc' === strtolower( $filters['order'] ) ? 'ASC' : 'DESC';
		$allowed = array( 'id', 'created_at', 'group', 'action', 'user_login', 'object_type' );

		if ( ! in_array( $orderby, $allowed, true ) ) {
			$orderby = 'created_at';
		}

		$sql = "SELECT * FROM {$this->table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$values[] = $per_page;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$results = $this->wpdb->get_results( $this->wpdb->prepare( $sql, $values ), ARRAY_A ) ?: array();

		wp_cache_set( $cache_key, $results, 'owc_activity_log' );

		return $results;
	}

	/**
	 * Count log entries matching filters.
	 */
	public function count( array $filters = array() ): int
	{
		$cache_key = 'count_' . md5( serialize( $filters ) . $this->get_last_changed() ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$cached    = wp_cache_get( $cache_key, 'owc_activity_log' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		list( $where, $values ) = $this->build_where( $filters );

		$sql = "SELECT COUNT(*) FROM {$this->table} {$where}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$count = (int) $this->wpdb->get_var( $sql );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$count = (int) $this->wpdb->get_var( $this->wpdb->prepare( $sql, $values ) );
		}

		wp_cache_set( $cache_key, $count, 'owc_activity_log' );

		return $count;
	}

	/**
	 * Delete entries older than a given UTC datetime string.
	 */
	public function delete_before( string $datetime ): int
	{
		$deleted = (int) $this->wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->wpdb->prepare(
				"DELETE FROM {$this->table} WHERE created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$datetime
			)
		);

		if ( $deleted > 0 ) {
			$this->invalidate_cache();
		}

		return $deleted;
	}

	/**
	 * Get distinct object types present in the log.
	 */
	public function get_object_types(): array
	{
		$cache_key = 'object_types_' . $this->get_last_changed();
		$cached    = wp_cache_get( $cache_key, 'owc_activity_log' );

		if ( false !== $cached ) {
			return $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $this->wpdb->get_col( "SELECT DISTINCT object_type FROM {$this->table} WHERE object_type != '' ORDER BY object_type ASC" ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		wp_cache_set( $cache_key, $results, 'owc_activity_log' );

		return $results;
	}

	/**
	 * Get distinct groups present in the log.
	 */
	public function get_groups(): array
	{
		$cache_key = 'groups_' . $this->get_last_changed();
		$cached    = wp_cache_get( $cache_key, 'owc_activity_log' );

		if ( false !== $cached ) {
			return $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $this->wpdb->get_col( "SELECT DISTINCT `group` FROM {$this->table} ORDER BY `group` ASC" ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		wp_cache_set( $cache_key, $results, 'owc_activity_log' );

		return $results;
	}

	/**
	 * Get the last-changed token for this table's cache group.
	 */
	private function get_last_changed(): string
	{
		$last_changed = wp_cache_get( 'last_changed', 'owc_activity_log' );

		if ( false === $last_changed ) {
			$last_changed = microtime();
			wp_cache_set( 'last_changed', $last_changed, 'owc_activity_log' );
		}

		return $last_changed;
	}

	/**
	 * Invalidate all cached queries by resetting the last-changed token.
	 */
	private function invalidate_cache(): void
	{
		wp_cache_delete( 'last_changed', 'owc_activity_log' );
	}

	/**
	 * Build the WHERE clause and values array from filter params.
	 */
	private function build_where( array $filters ): array
	{
		$clauses = array();
		$values  = array();

		if ( ! empty( $filters['group'] ) ) {
			$clauses[] = '`group` = %s';
			$values[]  = sanitize_text_field( $filters['group'] );
		}

		if ( ! empty( $filters['action'] ) ) {
			$clauses[] = 'action = %s';
			$values[]  = sanitize_text_field( $filters['action'] );
		}

		if ( ! empty( $filters['user_id'] ) ) {
			$clauses[] = 'user_id = %d';
			$values[]  = (int) $filters['user_id'];
		}

		if ( ! empty( $filters['object_type'] ) ) {
			$clauses[] = 'object_type = %s';
			$values[]  = sanitize_text_field( $filters['object_type'] );
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$clauses[] = 'created_at >= %s';
			$values[]  = sanitize_text_field( $filters['date_from'] ) . ' 00:00:00';
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$clauses[] = 'created_at <= %s';
			$values[]  = sanitize_text_field( $filters['date_to'] ) . ' 23:59:59';
		}

		if ( ! empty( $filters['search'] ) ) {
			$clauses[] = 'message LIKE %s';
			$values[]  = '%' . $this->wpdb->esc_like( sanitize_text_field( $filters['search'] ) ) . '%';
		}

		$where = empty( $clauses ) ? '' : 'WHERE ' . implode( ' AND ', $clauses );

		return array( $where, $values );
	}
}
