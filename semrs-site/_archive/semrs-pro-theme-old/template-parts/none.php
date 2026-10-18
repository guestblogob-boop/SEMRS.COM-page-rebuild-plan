<?php
/**
 * No results.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="no-results">
	<h2><?php esc_html_e( 'Nothing found', 'semrs-pro' ); ?></h2>
	<p><?php esc_html_e( 'Try a different search.', 'semrs-pro' ); ?></p>
	<?php get_search_form(); ?>
</section>
