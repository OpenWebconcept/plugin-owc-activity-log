<?php

declare(strict_types=1);

/**
 * Gravity Forms listener.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.3
 */

namespace OWCActivityLog\Listeners;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GFAPI;
use OWCActivityLog\Contracts\AbstractListener;

/**
 * Listens to Gravity Forms form definition changes.
 *
 * Form updates can be saved through the form editor or through the WordPress
 * REST API / GFAPI directly. Both paths run through
 * GFFormsModel::update_form_meta(), so this listener hooks the filter it
 * fires before the write (to snapshot the previous field definitions) and
 * the action it fires after the write (to capture the new definitions),
 * rather than the editor-only `gform_after_save_form` hook.
 *
 * A single form save can trigger update_form_meta() for 'display_meta' more
 * than once in the same request (e.g. the form editor persisting the form,
 * then re-reading and re-saving it to apply server-side defaults). Diffing
 * and logging on every one of those calls produces duplicate/noisy entries,
 * some of which show no real change. To avoid that, this listener only
 * remembers the *first* "before" snapshot and the *last* "after" state per
 * form ID for the request, and diffs/logs those once, on 'shutdown'.
 *
 * A brand new form has no stored 'display_meta' yet when the "before" write
 * is snapshotted, so GFAPI::get_form() returns false and the snapshot is
 * recorded as null (as opposed to an empty fields array, which would mean an
 * existing-but-fieldless form). flush() treats a null snapshot as a creation
 * and logs it as such, rather than diffing against it.
 *
 * @since 1.0.3
 */
class GravityFormsListener extends AbstractListener
{
	/**
	 * Field properties that are meaningful to a form editor. Everything else
	 * (internal layout/bookkeeping properties GF also stores on a field) is
	 * ignored, so the log doesn't fill up with noise nobody asked about.
	 *
	 * @var string[]
	 */
	private const TRACKED_FIELD_PROPERTIES = array(
		'label',
		'type',
		'isRequired',
		'description',
		'placeholder',
		'defaultValue',
		'adminLabel',
		'size',
		'maxLength',
		'choices',
		'visibility',
		'inputMask',
		'cssClass',
	);

	/**
	 * GF's own defaults for properties it always fills in explicitly once a
	 * field is saved through the form editor, even if the stored field never
	 * had the key set before. Falling back to these (instead of null) when a
	 * property is missing keeps a no-op editor save from being logged as a
	 * change.
	 *
	 * @var array<string, string>
	 */
	private const FIELD_PROPERTY_DEFAULTS = array(
		'size'       => 'medium',
		'visibility' => 'visible',
	);

	/**
	 * The form field definitions from before the first write in this request, keyed by form ID.
	 *
	 * @var array<int, array|null>
	 */
	private array $before_fields = array();

	/**
	 * The most recently written form definition in this request, keyed by form ID.
	 *
	 * @var array<int, array>
	 */
	private array $after_forms = array();

	/**
	 * Whether the shutdown flush has already been scheduled.
	 *
	 * @var bool
	 */
	private bool $flush_scheduled = false;

	public function get_hooks(): array
	{
		return array(
			'gform_post_update_form_meta' => array( 'on_post_update_form_meta', 10, 3 ),
		);
	}

	/**
	 * Register the gform_form_update_meta filter separately as it is a filter, not an action.
	 */
	public function register_filter(): void
	{
		add_filter( 'gform_form_update_meta', $this->on_pre_update_form_meta( ... ), 10, 3 );
	}

	/**
	 * Fires before a form's meta is written, so the current field definitions can be snapshotted.
	 */
	public function on_pre_update_form_meta( mixed $form_meta, int $form_id, string $meta_name = 'display_meta' ): mixed
	{
		if ( 'display_meta' !== $meta_name || array_key_exists( $form_id, $this->before_fields ) ) {
			return $form_meta;
		}

		$existing_form = $this->get_existing_form( $form_id );

		$this->before_fields[ $form_id ] = is_array( $existing_form ) ? ( $existing_form['fields'] ?? array() ) : null;

		return $form_meta;
	}

	/**
	 * Fetch the currently stored form definition, prior to the pending write.
	 */
	protected function get_existing_form( int $form_id ): mixed
	{
		return class_exists( GFAPI::class ) ? GFAPI::get_form( $form_id ) : false;
	}

	/**
	 * Fires after a form's meta has been written for any form.
	 */
	public function on_post_update_form_meta( string $form_meta_json, int $form_id, string $meta_name = 'display_meta' ): void
	{
		if ( 'display_meta' !== $meta_name ) {
			return;
		}

		// The pre-hook (on_pre_update_form_meta) always runs first and sets this
		// key, even if the form didn't exist yet (see the class docblock). A
		// missing key means that hook never ran for this form ID, so there is
		// nothing to diff or log against.
		if ( ! array_key_exists( $form_id, $this->before_fields ) ) {
			return;
		}

		$after_form = json_decode( $form_meta_json, true );

		if ( ! is_array( $after_form ) ) {
			return;
		}

		$this->after_forms[ $form_id ] = $after_form;

		$this->schedule_flush();
	}

	/**
	 * Schedule a single end-of-request flush that logs the net field changes per form.
	 */
	private function schedule_flush(): void
	{
		if ( $this->flush_scheduled ) {
			return;
		}

		$this->flush_scheduled = true;

		add_action( 'shutdown', $this->flush( ... ) );
	}

	/**
	 * Diff and log the net field changes for every form touched during this request.
	 */
	public function flush(): void
	{
		foreach ( $this->after_forms as $form_id => $after_form ) {
			// No fields snapshot means the form didn't exist yet when the first
			// write of this request was snapshotted, i.e. it was just created.
			if ( ! array_key_exists( $form_id, $this->before_fields ) ) {
				continue;
			}

			$before_fields = $this->before_fields[ $form_id ];

			if ( null === $before_fields ) {
				$this->log_form_created( $form_id, $after_form );
				continue;
			}

			$changed = $this->diff_fields( $before_fields, $after_form['fields'] ?? array() );

			if ( empty( $changed ) ) {
				continue;
			}

			$this->log(
				'gravity_forms',
				'form_fields_changed',
				sprintf(
					/* translators: 1: form title, 2: plain-language summary of what changed */
					__( 'Form "%1$s" updated: %2$s.', 'owc-activity-log' ),
					$after_form['title'] ?? "#{$form_id}",
					$this->summarize_changes( $changed )
				),
				array(
					'object_id'   => $form_id,
					'object_type' => 'gf_form',
					'meta'        => array( 'changed_fields' => $changed ),
				)
			);
		}
	}

	/**
	 * Log the creation of a brand new form, listing its initial fields as "added"
	 * so the entry renders the same way an update's added fields would.
	 */
	private function log_form_created( int $form_id, array $after_form ): void
	{
		$added = array();

		foreach ( $this->index_fields_by_id( $after_form['fields'] ?? array() ) as $field_id => $field ) {
			$added[ $field_id ] = array(
				'label'  => $field['label'] ?? '',
				'status' => 'added',
			);
		}

		$this->log(
			'gravity_forms',
			'form_created',
			sprintf(
				/* translators: 1: form title, 2: plain-language summary of the initial fields */
				__( 'Form "%1$s" created: %2$s.', 'owc-activity-log' ),
				$after_form['title'] ?? "#{$form_id}",
				empty( $added ) ? __( 'no fields', 'owc-activity-log' ) : $this->summarize_changes( $added )
			),
			array(
				'object_id'   => $form_id,
				'object_type' => 'gf_form',
				'meta'        => array( 'changed_fields' => $added ),
			)
		);
	}

	/**
	 * Build a short, plain-language summary of the field changes for the log message,
	 * e.g. `"Name" changed; "New field" added; "Old field" removed`.
	 */
	private function summarize_changes( array $changed ): string
	{
		$labels = array(
			'changed' => array(),
			'added'   => array(),
			'removed' => array(),
		);

		foreach ( $changed as $field ) {
			$label = '' !== (string) $field['label'] ? $field['label'] : __( '(untitled field)', 'owc-activity-log' );

			$labels[ $field['status'] ][] = $label;
		}

		$summary_parts = array(
			'changed' => __( 'changed', 'owc-activity-log' ),
			'added'   => __( 'added', 'owc-activity-log' ),
			'removed' => __( 'removed', 'owc-activity-log' ),
		);

		$parts = array();

		foreach ( $summary_parts as $status => $verb ) {
			if ( empty( $labels[ $status ] ) ) {
				continue;
			}

			$parts[] = sprintf( '%1$s %2$s', $this->list_with_limit( $labels[ $status ] ), $verb );
		}

		return implode( '; ', $parts );
	}

	/**
	 * Quote and join a list of field labels, capping how many are spelled out.
	 */
	private function list_with_limit( array $labels, int $limit = 4 ): string
	{
		$quoted = array_map( static fn( $label ) => '"' . $label . '"', array_slice( $labels, 0, $limit ) );

		$remaining = count( $labels ) - $limit;

		if ( $remaining > 0 ) {
			$quoted[] = sprintf(
				/* translators: %d: number of additional fields not spelled out */
				_n( 'and %d more', 'and %d more', $remaining, 'owc-activity-log' ),
				$remaining
			);
		}

		return implode( ', ', $quoted );
	}

	/**
	 * Diff two sets of form fields by field ID.
	 *
	 * Rather than dumping the entire before/after field definition, this only
	 * records which individual properties (label, type, choices, etc.) of a
	 * changed field actually differ, so the log stays small and readable.
	 */
	private function diff_fields( array $before_fields, array $after_fields ): array
	{
		$before_by_id = $this->index_fields_by_id( $before_fields );
		$after_by_id  = $this->index_fields_by_id( $after_fields );

		$changed = array();

		foreach ( $after_by_id as $field_id => $after_field ) {
			$before_field = $before_by_id[ $field_id ] ?? null;

			if ( null === $before_field ) {
				$changed[ $field_id ] = array(
					'label'  => $after_field['label'] ?? '',
					'status' => 'added',
				);
				continue;
			}

			$property_changes = $this->diff_field_properties( $before_field, $after_field );

			if ( empty( $property_changes ) ) {
				continue;
			}

			$changed[ $field_id ] = array(
				'label'   => $after_field['label'] ?? $before_field['label'] ?? '',
				'status'  => 'changed',
				'changed' => $property_changes,
			);
		}

		foreach ( $before_by_id as $field_id => $before_field ) {
			if ( isset( $after_by_id[ $field_id ] ) ) {
				continue;
			}

			$changed[ $field_id ] = array(
				'label'  => $before_field['label'] ?? '',
				'status' => 'removed',
			);
		}

		return $changed;
	}

	/**
	 * Diff the individual properties of a single field between its before/after state.
	 */
	private function diff_field_properties( array $before_field, array $after_field ): array
	{
		$changed = array();

		foreach ( self::TRACKED_FIELD_PROPERTIES as $key ) {
			$default      = self::FIELD_PROPERTY_DEFAULTS[ $key ] ?? null;
			$before_value = $before_field[ $key ] ?? $default;
			$after_value  = $after_field[ $key ] ?? $default;

			// Loose comparison is intentional: it ignores key order, unlike a JSON string comparison would.
			if ( $before_value == $after_value ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
				continue;
			}

			$changed[ $key ] = array(
				'from' => $this->stringify_field_value( $before_value ),
				'to'   => $this->stringify_field_value( $after_value ),
			);
		}

		return $changed;
	}

	/**
	 * Stringify a field property value for storage, keeping booleans readable
	 * instead of the "1" / "" that a plain (string) cast would produce.
	 */
	private function stringify_field_value( mixed $value ): string
	{
		if ( is_bool( $value ) ) {
			$value = $value ? 'true' : 'false';
		}

		return $this->truncate( $value );
	}

	/**
	 * Index an array of GF_Field objects/arrays by field ID.
	 */
	private function index_fields_by_id( array $fields ): array
	{
		$indexed = array();

		foreach ( $fields as $field ) {
			$field_array = (array) $field;
			$field_id    = $field_array['id'] ?? null;

			if ( null === $field_id ) {
				continue;
			}

			$indexed[ (string) $field_id ] = $field_array;
		}

		return $indexed;
	}
}
