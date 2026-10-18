<?php
/**
 * Sidebar (only printed for sidebar layouts).
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;
if ( 'none' === semrs_theme_layout() ) {
	return;
}
?>
<aside id="secondary" class="sp-sidebar" aria-label="<?php esc_attr_e( 'Sidebar', 'semrs' ); ?>">
	<div class="<?php echo semrs_theme_opt( 'sticky_sidebar' ) ? 'sp-sticky-sidebar' : ''; ?>">
		<?php do_action( 'semrs_sidebar_top' ); ?>
		<?php dynamic_sidebar( 'sidebar-1' ); ?>
		<?php do_action( 'semrs_sidebar_bottom' ); ?>
	</div>
</aside>
