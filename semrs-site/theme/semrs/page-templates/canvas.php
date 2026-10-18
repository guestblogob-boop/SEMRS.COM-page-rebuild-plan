<?php
/**
 * Template Name: Blank canvas (no header or footer)
 * Template Post Type: page
 *
 * For campaign landing pages. Only the page content is shown.
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'sp-canvas sp-full-width' ); ?>>
<?php wp_body_open(); ?>
<main id="primary" class="sp-main">
	<?php
	while ( have_posts() ) :
		the_post();
		echo '<div class="entry-content">';
		the_content();
		echo '</div>';
	endwhile;
	?>
</main>
<?php wp_footer(); ?>
</body>
</html>
