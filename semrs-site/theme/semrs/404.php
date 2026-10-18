<?php
/**
 * 404.
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="primary" class="sp-main sp-404">
	<h1><?php esc_html_e( 'Page not found', 'semrs' ); ?></h1>
	<p><?php esc_html_e( 'The page you were looking for has moved or no longer exists. Try a search or start from the homepage.', 'semrs' ); ?></p>
	<?php get_search_form(); ?>
	<p><a class="sp-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go to the homepage', 'semrs' ); ?></a></p>
	<?php do_action( 'semrs_404_after' ); ?>
</main>
<?php
get_footer();
