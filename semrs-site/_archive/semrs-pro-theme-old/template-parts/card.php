<?php
/**
 * Post card used by archives, search and related posts.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;
$semrs_excerpt = ! isset( $args['excerpt'] ) || $args['excerpt'];
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'sp-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="post-thumbnail" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'semrs-card', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
		</a>
	<?php endif; ?>
	<div class="sp-card-body">
		<?php the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '">', '</a></h2>' ); ?>
		<?php if ( 'post' === get_post_type() ) : ?>
			<div class="entry-meta"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></div>
		<?php endif; ?>
		<?php if ( $semrs_excerpt ) : ?>
			<div class="entry-summary"><?php the_excerpt(); ?></div>
			<a class="sp-read-more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read more', 'semrs-pro' ); ?><span class="screen-reader-text"> <?php the_title(); ?></span> →</a>
		<?php endif; ?>
	</div>
</article>
