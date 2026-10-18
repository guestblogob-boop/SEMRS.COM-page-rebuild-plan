<?php
/**
 * Page.
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="primary" class="sp-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
			<?php if ( ! get_post_meta( get_the_ID(), 'semrs_hide_title', true ) ) : ?>
				<header class="entry-header"><?php the_title( '<h1 class="entry-title">', '</h1>' ); ?></header>
			<?php endif; ?>
			<?php do_action( 'semrs_before_entry_content' ); ?>
			<div class="entry-content">
				<?php
				the_content();
				wp_link_pages( array( 'before' => '<nav class="page-links">' . esc_html__( 'Pages:', 'semrs' ), 'after' => '</nav>' ) );
				?>
			</div>
			<?php do_action( 'semrs_after_entry_content' ); ?>
		</article>
		<?php
		do_action( 'semrs_after_entry' );
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
	endwhile;
	?>
</main>
<?php
get_sidebar();
get_footer();
