<?php
/**
 * Header.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;
$semrs_cta_text = semrs_pro_opt( 'cta_text' );
$semrs_cta_url  = semrs_pro_opt( 'cta_url' );
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
<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'semrs-pro' ); ?></a>
<?php do_action( 'semrs_before_header' ); ?>
<header id="masthead" class="site-header">
	<div class="sp-container header-inner">
		<div class="site-branding">
			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				$semrs_tag = is_front_page() ? 'h1' : 'p';
				printf( '<%1$s class="site-title"><a href="%2$s" rel="home">%3$s</a></%1$s>', tag_escape( $semrs_tag ), esc_url( home_url( '/' ) ), esc_html( get_bloginfo( 'name' ) ) );
			}
			if ( semrs_pro_opt( 'show_tagline' ) && get_bloginfo( 'description' ) ) {
				echo '<p class="site-description">' . esc_html( get_bloginfo( 'description' ) ) . '</p>';
			}
			?>
		</div>

		<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Primary', 'semrs-pro' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_id'        => 'primary-menu',
						'fallback_cb'    => false,
					)
				);
				if ( $semrs_cta_text && $semrs_cta_url ) {
					printf( '<div class="sp-mobile-cta"><a class="sp-button" href="%s">%s</a></div>', esc_url( $semrs_cta_url ), esc_html( $semrs_cta_text ) );
				}
				?>
			</nav>
		<?php endif; ?>

		<?php do_action( 'semrs_header_actions' ); ?>

		<?php if ( $semrs_cta_text && $semrs_cta_url ) : ?>
			<a class="sp-button header-cta" href="<?php echo esc_url( $semrs_cta_url ); ?>"><?php echo esc_html( $semrs_cta_text ); ?></a>
		<?php endif; ?>

		<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<button class="menu-toggle" aria-controls="site-navigation" aria-expanded="false">
				<span class="menu-toggle-icon" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'semrs-pro' ); ?></span>
			</button>
		<?php endif; ?>
	</div>
</header>
<?php do_action( 'semrs_after_header' ); ?>

<div id="content" class="site-content">
	<?php do_action( 'semrs_before_content' ); ?>
	<div class="sp-container sp-content-container">
		<?php do_action( 'semrs_content_top' ); ?>
		<div class="sp-layout">
