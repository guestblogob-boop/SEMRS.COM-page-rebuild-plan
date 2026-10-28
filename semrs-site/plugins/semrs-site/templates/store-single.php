<?php
/**
 * Product page (Shopify-style): gallery, buy box with plan picker,
 * accordions, related products and a sticky buy bar on phones.
 *
 * @package semrs-site
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$semrs_post = get_post();
	$semrs_d    = semrs_store_product( $semrs_post );
	$semrs_imgs = semrs_store_gallery( $semrs_post, $semrs_d );
	$semrs_best = semrs_store_best_value( $semrs_d['tiers'] );
	$semrs_pick = $semrs_best >= 0 ? $semrs_best : 0;
	?>
	<main id="primary" class="sp-main semrs-store-main">
		<div class="semrs-wrap">
			<nav class="semrs-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'semrs-site' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'semrs-site' ); ?></a> <span aria-hidden="true">›</span>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'semrs_product' ) ); ?>"><?php esc_html_e( 'Store', 'semrs-site' ); ?></a> <span aria-hidden="true">›</span>
				<?php if ( $semrs_d['cat'] ) : ?>
					<a href="<?php echo esc_url( get_term_link( $semrs_d['cat'] ) ); ?>"><?php echo esc_html( $semrs_d['cat']->name ); ?></a> <span aria-hidden="true">›</span>
				<?php endif; ?>
				<span aria-current="page"><?php the_title(); ?></span>
			</nav>

			<div class="semrs-product">
				<div class="semrs-gallery" data-gallery>
					<div class="semrs-gallery__stage">
						<?php
						foreach ( $semrs_imgs as $semrs_i => $semrs_img ) {
							$semrs_attr = array(
								'class'         => 'semrs-gallery__img' . ( $semrs_i ? ' is-phone' : '' ),
								'data-slide'    => $semrs_i,
								'loading'       => $semrs_i ? 'lazy' : 'eager',
								'fetchpriority' => $semrs_i ? 'auto' : 'high',
								'sizes'         => '(max-width: 900px) 100vw, 640px',
							);
							if ( $semrs_i ) {
								$semrs_attr['hidden'] = 'hidden';
							}
							echo wp_get_attachment_image( $semrs_img, 'large', false, $semrs_attr );
						}
						if ( $semrs_d['badge'] ) {
							echo '<span class="semrs-badge' . ( $semrs_d['paid'] ? ' semrs-badge--pro' : '' ) . '">' . esc_html( $semrs_d['badge'] ) . '</span>';
						}
						?>
					</div>
					<?php if ( count( $semrs_imgs ) > 1 ) : ?>
						<div class="semrs-gallery__thumbs" role="tablist" aria-label="<?php esc_attr_e( 'Screenshots', 'semrs-site' ); ?>">
							<?php foreach ( $semrs_imgs as $semrs_i => $semrs_img ) : ?>
								<button type="button" role="tab" class="semrs-gallery__thumb<?php echo $semrs_i ? '' : ' is-active'; ?>" data-to="<?php echo (int) $semrs_i; ?>" aria-selected="<?php echo $semrs_i ? 'false' : 'true'; ?>">
									<?php echo wp_get_attachment_image( $semrs_img, 'thumbnail', false, array( 'alt' => '' ) ); ?>
									<span><?php echo 0 === $semrs_i ? esc_html__( 'Computer', 'semrs-site' ) : esc_html__( 'Phone', 'semrs-site' ); ?></span>
								</button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="semrs-buybox" data-buybox>
					<p class="semrs-card__kind"><?php echo esc_html( semrs_store_kind( $semrs_d ) ); ?><?php echo $semrs_d['version'] ? ' · ' . esc_html__( 'Version', 'semrs-site' ) . ' ' . esc_html( $semrs_d['version'] ) : ''; ?></p>
					<h1 class="semrs-buybox__title"><?php the_title(); ?></h1>
					<p class="semrs-buybox__lead"><?php echo esc_html( $semrs_d['short'] ); ?></p>

					<?php $semrs_engine = $semrs_d['paid'] && ! $semrs_d['addons'] ? semrs_store_engine( $semrs_d ) : ''; ?>
					<?php if ( $semrs_d['addons'] ) : ?>
						<p class="semrs-buybox__price"><?php echo semrs_store_price_html( $semrs_d ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?></p>
						<p><?php esc_html_e( 'Pick only the add-ons you need (SEO Pro, Speed Pro, Ads Pro, Theme & Blocks Pro) or All Pro for everything. One key, and you can add more later.', 'semrs-site' ); ?></p>
						<div id="semrs-plans"><a class="semrs-btn semrs-btn--buy semrs-btn--block" href="#plans"><?php esc_html_e( 'Choose your add-ons', 'semrs-site' ); ?></a></div>
					<?php elseif ( $semrs_engine ) : ?>
						<div id="semrs-plans"><?php echo $semrs_engine; // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaping by SEMRS Blocks. ?></div>
					<?php elseif ( $semrs_d['paid'] ) : ?>
						<p class="semrs-buybox__price" data-price-out><?php echo esc_html( semrs_store_money( $semrs_d['tiers'][ $semrs_pick ]['price'], $semrs_d['currency'] ) ); ?> <span><?php echo esc_html( semrs_store_period( $semrs_d ) ); ?></span></p>
						<fieldset class="semrs-tiers" id="semrs-plans">
							<legend><?php esc_html_e( 'Choose your plan', 'semrs-site' ); ?></legend>
							<?php foreach ( $semrs_d['tiers'] as $semrs_i => $semrs_t ) : ?>
								<label class="semrs-tier">
									<input type="radio" name="semrs-tier" value="<?php echo (int) $semrs_i; ?>" <?php checked( $semrs_i, $semrs_pick ); ?> data-price="<?php echo esc_attr( semrs_store_money( $semrs_t['price'], $semrs_d['currency'] ) ); ?>" data-url="<?php echo esc_url( $semrs_t['url'] ); ?>">
									<span class="semrs-tier__name"><?php echo esc_html( $semrs_t['label'] ); ?><?php echo $semrs_i === $semrs_best ? ' <em>' . esc_html__( 'Best value', 'semrs-site' ) . '</em>' : ''; ?></span>
									<span class="semrs-tier__price"><?php echo esc_html( semrs_store_money( $semrs_t['price'], $semrs_d['currency'] ) ); ?><?php echo ! empty( $semrs_t['compare'] ) && (float) $semrs_t['compare'] > (float) $semrs_t['price'] ? ' <s>' . esc_html( semrs_store_money( $semrs_t['compare'], $semrs_d['currency'] ) ) . '</s>' : ''; ?></span>
								</label>
							<?php endforeach; ?>
						</fieldset>
						<div data-buy-out>
							<?php foreach ( $semrs_d['tiers'] as $semrs_i => $semrs_t ) : ?>
								<div data-tier-buy="<?php echo (int) $semrs_i; ?>"<?php echo $semrs_i === $semrs_pick ? '' : ' hidden'; ?>><?php echo semrs_store_buy_button( $semrs_t, 'semrs-btn--block' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?></div>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="semrs-buybox__price"><?php esc_html_e( 'Free', 'semrs-site' ); ?></p>
						<?php if ( $semrs_d['download'] ) : ?>
							<a class="semrs-btn semrs-btn--buy semrs-btn--block" href="<?php echo esc_url( $semrs_d['download'] ); ?>" rel="nofollow" download><?php esc_html_e( 'Download free', 'semrs-site' ); ?></a>
						<?php endif; ?>
						<?php if ( $semrs_d['wporg'] ) : ?>
							<a class="semrs-btn semrs-btn--ghost semrs-btn--block" href="<?php echo esc_url( $semrs_d['wporg'] ); ?>" rel="noopener" target="_blank"><?php esc_html_e( 'View on WordPress.org', 'semrs-site' ); ?></a>
						<?php endif; ?>
					<?php endif; ?>
					<?php if ( $semrs_d['demo'] ) : ?>
						<a class="semrs-btn semrs-btn--ghost semrs-btn--block" href="<?php echo esc_url( $semrs_d['demo'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Live demo', 'semrs-site' ); ?></a>
					<?php endif; ?>

					<ul class="semrs-facts">
						<li><?php echo $semrs_d['paid'] ? esc_html__( 'Instant download after checkout', 'semrs-site' ) : esc_html__( 'Free forever, no sign-up', 'semrs-site' ); ?></li>
						<li><?php echo $semrs_d['paid'] ? esc_html__( 'One-click updates and support while licensed', 'semrs-site' ) : esc_html__( 'One-click updates in your dashboard', 'semrs-site' ); ?></li>
						<li><?php esc_html_e( 'GPL licensed: use and modify freely', 'semrs-site' ); ?></li>
						<?php if ( $semrs_d['needs'] ) : ?>
							<li><?php echo esc_html( $semrs_d['needs'] ); ?></li>
						<?php endif; ?>
					</ul>
					<dl class="semrs-specs">
						<?php if ( $semrs_d['version'] ) : ?>
							<dt><?php esc_html_e( 'Version', 'semrs-site' ); ?></dt><dd><?php echo esc_html( $semrs_d['version'] ); ?></dd>
						<?php endif; ?>
						<?php if ( $semrs_d['updated'] ) : ?>
							<dt><?php esc_html_e( 'Last updated', 'semrs-site' ); ?></dt><dd><?php echo esc_html( mysql2date( get_option( 'date_format' ), $semrs_d['updated'] ) ); ?></dd>
						<?php endif; ?>
						<dt><?php esc_html_e( 'Requires', 'semrs-site' ); ?></dt><dd><?php echo esc_html( sprintf( /* translators: 1: WP version, 2: PHP version */ __( 'WordPress %1$s+, PHP %2$s+', 'semrs-site' ), $semrs_d['requires_wp'], $semrs_d['requires_php'] ) ); ?></dd>
						<?php if ( ! empty( $semrs_d['tested'] ) ) : ?>
							<dt><?php esc_html_e( 'Tested up to', 'semrs-site' ); ?></dt><dd>WordPress <?php echo esc_html( $semrs_d['tested'] ); ?></dd>
						<?php endif; ?>
					</dl>
				</div>
			</div>

			<?php echo $semrs_d['addons'] ? semrs_store_pro_plans() : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaping. ?>

			<div class="semrs-details">
				<details class="semrs-acc" open>
					<summary><?php esc_html_e( 'Description', 'semrs-site' ); ?></summary>
					<div class="semrs-acc__body entry-content"><?php the_content(); ?></div>
				</details>
				<?php if ( $semrs_d['features'] ) : ?>
					<details class="semrs-acc" open>
						<summary><?php esc_html_e( 'Features', 'semrs-site' ); ?></summary>
						<ul class="semrs-acc__body semrs-ticks">
							<?php foreach ( $semrs_d['features'] as $semrs_f ) : ?>
								<li><?php echo esc_html( $semrs_f ); ?></li>
							<?php endforeach; ?>
						</ul>
					</details>
				<?php endif; ?>
				<?php if ( $semrs_d['faq'] ) : ?>
					<details class="semrs-acc">
						<summary><?php esc_html_e( 'Questions', 'semrs-site' ); ?></summary>
						<div class="semrs-acc__body">
							<?php
							foreach ( $semrs_d['faq'] as $semrs_q ) :
								$semrs_qa = array_map( 'trim', explode( '|', $semrs_q, 2 ) );
								?>
								<h3 class="semrs-faq__q"><?php echo esc_html( $semrs_qa[0] ); ?></h3>
								<p><?php echo esc_html( $semrs_qa[1] ?? '' ); ?></p>
							<?php endforeach; ?>
						</div>
					</details>
				<?php endif; ?>
			</div>

			<section class="semrs-related" aria-labelledby="semrs-related-title">
				<h2 id="semrs-related-title"><?php esc_html_e( 'Works well with', 'semrs-site' ); ?></h2>
				<?php echo semrs_store_grid( array( 'limit' => 4, 'columns' => 3, 'exclude' => (string) get_the_ID() ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaping. ?>
			</section>
		</div>

		<div class="semrs-stickybar" data-stickybar hidden>
			<div class="semrs-wrap semrs-stickybar__inner">
				<span class="semrs-stickybar__name"><?php the_title(); ?></span>
				<?php if ( $semrs_d['paid'] ) : ?>
					<span class="semrs-stickybar__price"><?php echo semrs_store_price_html( $semrs_d ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?></span>
					<a class="semrs-btn semrs-btn--buy semrs-btn--sm" href="#semrs-plans" data-scroll-buy><?php esc_html_e( 'Choose plan', 'semrs-site' ); ?></a>
				<?php elseif ( $semrs_d['download'] ) : ?>
					<a class="semrs-btn semrs-btn--buy semrs-btn--sm" href="<?php echo esc_url( $semrs_d['download'] ); ?>" rel="nofollow" download><?php esc_html_e( 'Download free', 'semrs-site' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</main>
	<?php
endwhile;
get_footer();
