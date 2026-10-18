<?php
/**
 * Header. Rows and elements come from Customizer → SEMRS Theme → Header builder.
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
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'semrs' ); ?></a>
<?php do_action( 'semrs_before_header' ); ?>
<?php if ( ! semrs_theme_part_hidden( 'header' ) ) : ?>
<header id="masthead" class="site-header">
	<?php
	semrs_theme_render_row( 'header', 'top' );
	semrs_theme_render_row( 'header', 'main' );
	semrs_theme_render_row( 'header', 'bottom' );
	?>
</header>
<?php endif; ?>
<?php do_action( 'semrs_after_header' ); ?>

<div id="content" class="site-content">
	<?php do_action( 'semrs_before_content' ); ?>
	<div class="sp-container sp-content-container">
		<?php do_action( 'semrs_content_top' ); ?>
		<div class="sp-layout">
