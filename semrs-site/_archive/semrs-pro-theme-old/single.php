<?php
/**
 * Single post (and other post types without their own template).
 *
 * @package semrs-pro
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
			<header class="entry-header">
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				<?php
				if ( 'post' === get_post_type() ) {
					semrs_pro_entry_meta();
				}
				?>
			</header>
			<?php if ( has_post_thumbnail() && semrs_pro_opt( 'single_thumb' ) ) : ?>
				<figure class="post-thumbnail"><?php the_post_thumbnail( 'large' ); ?></figure>
			<?php endif; ?>
			<?php do_action( 'semrs_before_entry_content' ); ?>
			<div class="entry-content">
				<?php
				the_content();
				wp_link_pages( array( 'before' => '<nav class="page-links">' . esc_html__( 'Pages:', 'semrs-pro' ), 'after' => '</nav>' ) );
				?>
			</div>
			<?php do_action( 'semrs_after_entry_content' ); ?>
			<?php if ( has_tag() ) : ?>
				<footer class="entry-footer"><span class="tags-links"><?php the_tags( '', '' ); ?></span></footer>
			<?php endif; ?>
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
