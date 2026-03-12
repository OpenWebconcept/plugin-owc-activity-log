<?php

declare(strict_types=1);

/**
 * Admin settings page view.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$settings   = owc_activity_log_get_settings();
$all_groups = array( 'posts', 'meta', 'users', 'options', 'taxonomy', 'comments', 'media', 'plugins', 'themes', 'menus', 'widgets' );
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors( 'owc_at_settings' ); ?>

	<form method="post">
		<?php wp_nonce_field( 'owc_at_save_settings', 'owc_at_settings_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="retention_days"><?php esc_html_e( 'Log retention (days)', 'owc-activity-log' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						id="retention_days"
						name="retention_days"
						value="<?php echo esc_attr( $settings['retention_days'] ); ?>"
						min="1"
						max="3650"
						class="small-text"
					>
					<p class="description"><?php esc_html_e( 'Log entries older than this number of days are deleted automatically.', 'owc-activity-log' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="log_ip"><?php esc_html_e( 'Log IP addresses', 'owc-activity-log' ); ?></label>
				</th>
				<td>
					<label>
						<input
							type="checkbox"
							id="log_ip"
							name="log_ip"
							value="1"
							<?php checked( $settings['log_ip'] ); ?>
						>
						<?php esc_html_e( 'Enable IP address logging', 'owc-activity-log' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'When enabled, the IP address of the user is stored with each log entry.', 'owc-activity-log' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Tracked groups', 'owc-activity-log' ); ?>
				</th>
				<td>
					<?php foreach ( $all_groups as $group ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
						<label style="display:block;margin-bottom:4px;">
							<input
								type="checkbox"
								name="enabled_groups[]"
								value="<?php echo esc_attr( $group ); ?>"
								<?php checked( in_array( $group, $settings['enabled_groups'], true ) ); ?>
							>
							<?php echo esc_html( $group ); ?>
						</label>
					<?php endforeach; ?>
					<p class="description"><?php esc_html_e( 'Uncheck groups to stop tracking them.', 'owc-activity-log' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="ignored_meta_keys"><?php esc_html_e( 'Ignored meta keys', 'owc-activity-log' ); ?></label>
				</th>
				<td>
					<textarea
						id="ignored_meta_keys"
						name="ignored_meta_keys"
						rows="6"
						class="large-text code"
					><?php echo esc_textarea( implode( "\n", $settings['ignored_meta_keys'] ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One key per line. Wildcards supported: _my_prefix_*', 'owc-activity-log' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="ignored_option_names"><?php esc_html_e( 'Ignored option names', 'owc-activity-log' ); ?></label>
				</th>
				<td>
					<textarea
						id="ignored_option_names"
						name="ignored_option_names"
						rows="6"
						class="large-text code"
					><?php echo esc_textarea( implode( "\n", $settings['ignored_option_names'] ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One option name per line. Wildcards supported: my_plugin_*', 'owc-activity-log' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
