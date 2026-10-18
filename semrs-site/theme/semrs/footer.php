<?php
/**
 * Footer.
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;
$semrs_cols = (int) semrs_theme_opt( 'footer_cols' );
?>
		</div><!-- .sp-layout -->
		<?php do_action( 'semrs_content_bottom' ); ?>
	</div><!-- .sp-container -->
	<?php do_action( 'semrs_after_content' ); ?>
</div><!-- #content -->

<?php do_action( 'semrs_before_footer' ); ?>
<?php if ( ! semrs_theme_part_hidden( 'footer' ) ) : ?>
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
	<div class="sp-container footer-bottom sp-fb">
		<?php
		$semrs_fb = semrs_theme_builder_layout( 'footer' )['bottom'];
		foreach ( array( 'left', 'center', 'right' ) as $semrs_zone ) {
			echo '<div class="sp-fb-zone sp-fb-' . esc_attr( $semrs_zone ) . '">';
			foreach ( $semrs_fb[ $semrs_zone ] as $semrs_el ) {
				semrs_theme_render_element( $semrs_el, 'footer' );
			}
			echo '</div>';
		}
		do_action( 'semrs_footer_bottom' );
		?>
	</div>
</footer>
<?php endif; ?>
<?php if ( semrs_theme_opt( 'footer_note' ) ) : ?>
	<p class="sp-footer-note"><?php echo esc_html( semrs_theme_opt( 'footer_note' ) ); ?></p>
<?php endif; ?>
<?php do_action( 'semrs_after_footer' ); ?>
<?php wp_footer(); ?>
</body>
</html>
