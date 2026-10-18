<?php
/**
 * Footer.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;
$semrs_cols = (int) semrs_pro_opt( 'footer_cols' );
?>
		</div><!-- .sp-layout -->
		<?php do_action( 'semrs_content_bottom' ); ?>
	</div><!-- .sp-container -->
	<?php do_action( 'semrs_after_content' ); ?>
</div><!-- #content -->

<?php do_action( 'semrs_before_footer' ); ?>
<footer id="colophon" class="site-footer">
	<?php
	$semrs_has_widgets = false;
	for ( $i = 1; $i <= $semrs_cols; $i++ ) {
		$semrs_has_widgets = $semrs_has_widgets || is_active_sidebar( 'footer-' . $i );
	}
	if ( $semrs_has_widgets ) :
		?>
		<div class="sp-container footer-widgets">
			<?php for ( $i = 1; $i <= $semrs_cols; $i++ ) : ?>
				<div class="footer-col">
					<?php dynamic_sidebar( 'footer-' . $i ); ?>
				</div>
			<?php endfor; ?>
		</div>
	<?php endif; ?>
	<div class="sp-container footer-bottom">
		<p class="site-info" style="margin:0"><?php echo esc_html( semrs_pro_copyright() ); ?></p>
		<?php
		if ( has_nav_menu( 'footer' ) ) {
			wp_nav_menu(
				array(
					'theme_location'  => 'footer',
					'container'       => 'nav',
					'container_aria_label' => __( 'Footer', 'semrs-pro' ),
					'menu_class'      => 'footer-menu',
					'depth'           => 1,
					'fallback_cb'     => false,
				)
			);
		}
		do_action( 'semrs_footer_bottom' );
		?>
	</div>
</footer>
<?php if ( semrs_pro_opt( 'footer_note' ) ) : ?>
	<p class="sp-footer-note"><?php echo esc_html( semrs_pro_opt( 'footer_note' ) ); ?></p>
<?php endif; ?>
<?php do_action( 'semrs_after_footer' ); ?>
<?php wp_footer(); ?>
</body>
</html>
