<?php
/**
 * Template Name: Full width (landing & service pages)
 * Template Post Type: page, post
 *
 * No sidebar, no title, edge-to-edge sections. Header and footer stay.
 * The page content must contain its own H1 (SEMRS page builds always do).
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
			<div class="entry-content"><?php the_content(); ?></div>
		</article>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
