<?php
/**
 * Comments.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;
if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="comments-area">
	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title">
			<?php
			/* translators: %s: number of comments */
			printf( esc_html( _n( '%s comment', '%s comments', get_comments_number(), 'semrs-pro' ) ), esc_html( number_format_i18n( get_comments_number() ) ) );
			?>
		</h2>
		<ol class="comment-list">
			<?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 40 ) ); ?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php
	if ( ! comments_open() && get_comments_number() ) {
		echo '<p class="no-comments">' . esc_html__( 'Comments are closed.', 'semrs-pro' ) . '</p>';
	}
	comment_form();
	?>
</section>
