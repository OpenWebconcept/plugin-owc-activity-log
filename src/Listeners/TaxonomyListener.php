<?php

declare(strict_types=1);

/**
 * Taxonomy listener.
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
use WP_Term;

/**
 * Listens to taxonomy/term events.
 *
 * @since 1.0.0
 */
class TaxonomyListener extends AbstractListener
{
	public function get_hooks(): array
	{
		return array(
			'created_term'     => array( 'on_created_term', 10, 3 ),
			'edited_term'      => array( 'on_edited_term', 10, 3 ),
			'delete_term'      => array( 'on_delete_term', 10, 4 ),
			'set_object_terms' => array( 'on_set_object_terms', 10, 6 ),
		);
	}

	public function on_created_term( int $term_id, int $tt_id, string $taxonomy ): void
	{
		$term = get_term( $term_id, $taxonomy );

		$this->log(
			'taxonomy',
			'created_term',
			sprintf(
				/* translators: 1: term name, 2: taxonomy */
				__( 'Term "%1$s" created in taxonomy "%2$s".', 'owc-activity-log' ),
				$term instanceof WP_Term ? $term->name : "#{$term_id}",
				$taxonomy
			),
			array(
				'object_id'   => $term_id,
				'object_type' => $taxonomy,
				'meta'        => array(
					'taxonomy'         => $taxonomy,
					'term_taxonomy_id' => $tt_id,
				),
			)
		);
	}

	public function on_edited_term( int $term_id, int $tt_id, string $taxonomy ): void
	{
		$term = get_term( $term_id, $taxonomy );

		$this->log(
			'taxonomy',
			'edited_term',
			sprintf(
				/* translators: 1: term name, 2: taxonomy */
				__( 'Term "%1$s" edited in taxonomy "%2$s".', 'owc-activity-log' ),
				$term instanceof WP_Term ? $term->name : "#{$term_id}",
				$taxonomy
			),
			array(
				'object_id'   => $term_id,
				'object_type' => $taxonomy,
				'meta'        => array( 'taxonomy' => $taxonomy ),
			)
		);
	}

	public function on_delete_term( int $term_id, int $tt_id, string $taxonomy, WP_Term $deleted_term ): void
	{
		$this->log(
			'taxonomy',
			'deleted_term',
			sprintf(
				/* translators: 1: term name, 2: taxonomy */
				__( 'Term "%1$s" deleted from taxonomy "%2$s".', 'owc-activity-log' ),
				$deleted_term->name,
				$taxonomy
			),
			array(
				'object_id'   => $term_id,
				'object_type' => $taxonomy,
				'meta'        => array(
					'taxonomy' => $taxonomy,
					'slug'     => $deleted_term->slug,
				),
			)
		);
	}

	public function on_set_object_terms(
		int $object_id,
		array $terms,
		array $tt_ids,
		string $taxonomy,
		bool $append,
		array $old_tt_ids
	): void {
		$added   = array_diff( $tt_ids, $old_tt_ids );
		$removed = array_diff( $old_tt_ids, $tt_ids );

		if ( 0 === count( $added ) && 0 === count( $removed ) ) return;

		$this->log(
			'taxonomy',
			'set_object_terms',
			sprintf(
				/* translators: 1: taxonomy, 2: object ID */
				__( 'Terms updated for taxonomy "%1$s" on object #%2$d.', 'owc-activity-log' ),
				$taxonomy,
				$object_id
			),
			array(
				'object_id'   => $object_id,
				'object_type' => $taxonomy,
				'meta'        => array(
					'taxonomy'       => $taxonomy,
					'added_tt_ids'   => array_values( $added ),
					'removed_tt_ids' => array_values( $removed ),
				),
			)
		);
	}
}
