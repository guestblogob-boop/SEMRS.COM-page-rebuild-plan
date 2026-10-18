<?php
/**
 * Store collection page (/store/ and /store/category/…/).
 *
 * @package semrs-site
 */

defined( 'ABSPATH' ) || exit;

$semrs_term = is_tax( 'semrs_product_cat' ) ? get_queried_object() : null;
get_header();
?>
<main id="primary" class="sp-main semrs-store-main">
	<header class="semrs-hero-band">
		<div class="semrs-wrap">
			<nav class="semrs-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'semrs-site' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'semrs-site' ); ?></a>
				<span aria-hidden="true">›</span>
				<?php if ( $semrs_term ) : ?>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'semrs_product' ) ); ?>"><?php esc_html_e( 'Store', 'semrs-site' ); ?></a>
					<span aria-hidden="true">›</span> <span aria-current="page"><?php echo esc_html( $semrs_term->name ); ?></span>
				<?php else : ?>
					<span aria-current="page"><?php esc_html_e( 'Store', 'semrs-site' ); ?></span>
				<?php endif; ?>
			</nav>
			<h1 class="semrs-hero-band__title"><?php echo $semrs_term ? esc_html( $semrs_term->name ) : esc_html__( 'WordPress themes and plugins', 'semrs-site' ); ?></h1>
			<p class="semrs-hero-band__lead">
				<?php
				echo $semrs_term && $semrs_term->description
					? esc_html( $semrs_term->description )
					: esc_html__( 'A fast theme, free SEO, speed and ads tools, blocks with 16 starter sites, and SEMRS Pro when you need more. Built to follow Google, AdSense and WordPress.org rules.', 'semrs-site' );
				?>
			</p>
			<ul class="semrs-trust">
				<li><?php esc_html_e( 'Instant download', 'semrs-site' ); ?></li>
				<li><?php esc_html_e( '100% GPL', 'semrs-site' ); ?></li>
				<li><?php esc_html_e( 'WordPress 6.5+ and PHP 7.4+', 'semrs-site' ); ?></li>
				<li><?php esc_html_e( 'Works with any theme (plugins)', 'semrs-site' ); ?></li>
			</ul>
		</div>
	</header>

	<section class="semrs-wrap semrs-store-section" aria-label="<?php esc_attr_e( 'Products', 'semrs-site' ); ?>">
		<?php
		echo semrs_store_grid( // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaping.
			array(
				'filters'  => 'yes',
				'category' => $semrs_term ? $semrs_term->slug : '',
			)
		);
		?>
	</section>

	<?php
	$semrs_pro = get_page_by_path( 'semrs-pro', OBJECT, 'semrs_product' );
	if ( $semrs_pro && 'publish' === $semrs_pro->post_status && ! $semrs_term ) :
		$semrs_pd   = semrs_store_product( $semrs_pro );
		$semrs_sale = get_page_by_path( 'semrs-pro' );
		?>
		<section class="semrs-promo" aria-labelledby="semrs-promo-title">
			<div class="semrs-wrap semrs-promo__inner">
				<div>
					<p class="semrs-promo__eyebrow"><?php esc_html_e( 'SEMRS Pro', 'semrs-site' ); ?></p>
					<h2 id="semrs-promo-title"><?php esc_html_e( 'One add-on unlocks Pro across the theme and every plugin', 'semrs-site' ); ?></h2>
					<p><?php esc_html_e( 'Ads Pro with click protection, Speed Pro, bulk SEO, redirects, local SEO, languages, dark mode, Pro blocks, popups and 7 more starter sites.', 'semrs-site' ); ?></p>
				</div>
				<div class="semrs-promo__cta">
					<?php echo semrs_store_price_html( $semrs_pd ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
					<a class="semrs-btn semrs-btn--accent" href="<?php echo esc_url( $semrs_sale && 'publish' === $semrs_sale->post_status ? get_permalink( $semrs_sale ) : get_permalink( $semrs_pro ) ); ?>"><?php esc_html_e( 'See what Pro adds', 'semrs-site' ); ?></a>
				</div>
			</div>
		</section>
	<?php endif; ?>
</main>
<?php
get_footer();
