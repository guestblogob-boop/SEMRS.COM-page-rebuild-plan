<?php
/**
 * Sidebar (only printed for sidebar layouts).
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;
if ( 'none' === semrs_pro_layout() ) {
	return;
}
?>
<aside id="secondary" class="sp-sidebar" aria-label="<?php esc_attr_e( 'Sidebar', 'semrs-pro' ); ?>">
	<div class="<?php echo semrs_pro_opt( 'sticky_sidebar' ) ? 'sp-sticky-sidebar' : ''; ?>">
		<?php do_action( 'semrs_sidebar_top' ); ?>
		<?php dynamic_sidebar( 'sidebar-1' ); ?>
		<?php do_action( 'semrs_sidebar_bottom' ); ?>
	</div>
</aside>
