<?php

declare(strict_types=1);

/**
 * Admin filters partial.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 *
 * @var string[] $groups       Available groups from the DB.
 * @var string[] $object_types Available object types from the DB.
 */

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$current_group       = isset( $_GET['at_group'] ) ? sanitize_text_field( wp_unslash( $_GET['at_group'] ) ) : '';
$current_object_type = isset( $_GET['at_object_type'] ) ? sanitize_text_field( wp_unslash( $_GET['at_object_type'] ) ) : '';
$current_date_from   = isset( $_GET['at_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['at_date_from'] ) ) : '';
$current_date_to     = isset( $_GET['at_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['at_date_to'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

?>
<div class="owcal-filters tablenav top">
	<form method="get">
		<input type="hidden" name="page" value="<?php echo esc_attr( OWC_ACTIVITY_LOG_SLUG ); ?>">

		<div>
			<label for="at_group"><?php esc_html_e( 'Group', 'owc-activity-log' ); ?></label>
			<select id="at_group" name="at_group">
				<option value=""><?php esc_html_e( '— All groups —', 'owc-activity-log' ); ?></option>
				<?php foreach ( $groups as $group ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
					<option value="<?php echo esc_attr( $group ); ?>" <?php selected( $current_group, $group ); ?>>
						<?php echo esc_html( $group ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div>
			<label for="at_object_type"><?php esc_html_e( 'Object type', 'owc-activity-log' ); ?></label>
			<select id="at_object_type" name="at_object_type">
				<option value=""><?php esc_html_e( '— All object types —', 'owc-activity-log' ); ?></option>
				<?php foreach ( $object_types as $object_type ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
					<option value="<?php echo esc_attr( $object_type ); ?>" <?php selected( $current_object_type, $object_type ); ?>>
						<?php echo esc_html( $object_type ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div>
			<label for="at_date_from"><?php esc_html_e( 'From', 'owc-activity-log' ); ?></label>
			<input
				type="date"
				id="at_date_from"
				name="at_date_from"
				value="<?php echo esc_attr( $current_date_from ); ?>"
			>
		</div>

		<div>
			<label for="at_date_to"><?php esc_html_e( 'To', 'owc-activity-log' ); ?></label>
			<input
				type="date"
				id="at_date_to"
				name="at_date_to"
				value="<?php echo esc_attr( $current_date_to ); ?>"
			>
		</div>

		<div>
			<label>&nbsp;</label>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'owc-activity-log' ); ?></button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . OWC_ACTIVITY_LOG_SLUG ) ); ?>" class="button">
				<?php esc_html_e( 'Reset', 'owc-activity-log' ); ?>
			</a>
		</div>
	</form>
</div>
