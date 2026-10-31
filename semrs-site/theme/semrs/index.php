<?php
/**
 * Blog index, archives and search results.
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="primary" class="sp-main">
	<?php if ( is_home() && ! is_front_page() ) : ?>
		<header class="page-header"><h1 class="page-title"><?php single_post_title(); ?></h1></header>
		<?php
		// The posts page's own text, if any, is shown as an intro above the latest posts.
		$semrs_intro = trim( (string) get_post_field( 'post_content', (int) get_option( 'page_for_posts' ) ) );
		if ( '' !== $semrs_intro ) {
			echo '<div class="entry-content sp-blog-intro">' . apply_filters( 'the_content', $semrs_intro ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- post content, core filter.
		}
		?>
	<?php elseif ( is_search() ) : ?>
		<header class="page-header">
			<h1 class="page-title">
				<?php
				/* translators: %s: search query */
				printf( esc_html__( 'Search results for "%s"', 'semrs' ), esc_html( get_search_query() ) );
				?>
			</h1>
			<?php get_search_form(); ?>
		</header>
	<?php elseif ( is_archive() ) : ?>
		<header class="page-header">
			<?php the_archive_title( '<h1 class="page-title">', '</h1>' ); ?>
			<?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
		</header>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<div class="sp-posts">
			<?php
			while ( have_posts() ) {
				the_post();
				get_template_part( 'template-parts/card' );
			}
			?>
		</div>
		<?php
		the_posts_pagination(
			array(
				'mid_size'  => 1,
				'prev_text' => esc_html__( 'Previous', 'semrs' ),
				'next_text' => esc_html__( 'Next', 'semrs' ),
			)
		);
		?>
	<?php else : ?>
		<?php get_template_part( 'template-parts/none' ); ?>
	<?php endif; ?>
</main>
<?php
get_sidebar();
get_footer();
