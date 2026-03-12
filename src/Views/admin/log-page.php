<?php

declare(strict_types=1);

/**
 * Admin activity log page view.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 *
 * @var \OWCActivityLog\Admin\ActivityLogTable $table
 * @var string[]                                   $groups
 * @var string[]                                   $object_types
 */

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap owc-activity-log">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php require __DIR__ . '/partials/filters.php'; ?>

	<form method="get">
		<input type="hidden" name="page" value="<?php echo esc_attr( OWC_ACTIVITY_LOG_SLUG ); ?>">
		<?php $table->search_box( esc_html__( 'Search messages', 'owc-activity-log' ), 'owcal-search' ); ?>
		<?php $table->display(); ?>
	</form>
</div>
