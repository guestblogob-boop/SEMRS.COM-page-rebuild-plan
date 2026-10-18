<?php
/**
 * Pattern content: "Hero with photo and keyword tool" (semrs.com only).
 * Registered in semrs-site.php. Replace the photo with select → Replace.
 * The keyword strip opens the Keyword Ideas Generator with the results.
 *
 * @package semrs-site
 */

defined( 'ABSPATH' ) || exit;
$semrs_img = SEMRS_SITE_URL . 'assets/images/';
$semrs_kw  = home_url( '/tools/keyword-ideas-generator/' );
?>
<!-- wp:group {"align":"full","className":"semrs-section semrs-hero semrs-hero-cover semrs-hero-photo","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull semrs-section semrs-hero semrs-hero-cover semrs-hero-photo"><!-- wp:columns {"verticalAlignment":"center","className":"semrs-hero-cover__cols"} -->
<div class="wp-block-columns are-vertically-aligned-center semrs-hero-cover__cols"><!-- wp:column {"verticalAlignment":"center","width":"52%","className":"semrs-hero-cover__text"} -->
<div class="wp-block-column is-vertically-aligned-center semrs-hero-cover__text" style="flex-basis:52%"><!-- wp:paragraph {"className":"semrs-eyebrow"} -->
<p class="semrs-eyebrow">Welcome</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Creative SEO that brings your business more customers</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"semrs-lead"} -->
<p class="semrs-lead">Technical SEO, local SEO and content for small businesses, with public prices and a human check on every change.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/get-a-quote/">Get a free SEO review</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/services/">Our services</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"bottom","width":"48%","className":"semrs-hero-cover__media"} -->
<div class="wp-block-column is-vertically-aligned-bottom semrs-hero-cover__media" style="flex-basis:48%"><!-- wp:group {"className":"semrs-hero-photo__stage","layout":{"type":"default"}} -->
<div class="wp-block-group semrs-hero-photo__stage"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"semrs-hero-photo__img"} -->
<figure class="wp-block-image size-full semrs-hero-photo__img"><img src="<?php echo esc_url( $semrs_img . 'hero-photo.jpg' ); ?>" alt="SEO specialist smiling and holding a tablet" width="800" height="1000" fetchpriority="high"/></figure>
<!-- /wp:image -->

<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"semrs-hero-photo__overlay"} -->
<figure class="wp-block-image size-full semrs-hero-photo__overlay"><img src="<?php echo esc_url( $semrs_img . 'hero-photo-overlay.svg' ); ?>" alt="" width="600" height="600"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:html -->
<div class="semrs-kw-strip">
	<div class="semrs-kw-strip__text">
		<h2>Find keywords your customers are searching for</h2>
		<p>Try our free keyword idea generator: enter what you sell and get 50+ ideas grouped by search intent. No sign-up, and nothing you type leaves your browser.</p>
	</div>
	<form class="semrs-kw-strip__form" action="<?php echo esc_url( $semrs_kw ); ?>" method="get">
		<label><span>Seed keyword</span><input type="text" name="seed" placeholder="e.g. dentist, web design, seo" maxlength="60" required></label>
		<label><span>City or area (optional)</span><input type="text" name="city" placeholder="e.g. Lahore" maxlength="60"></label>
		<button type="submit" class="wp-element-button">Get keyword ideas</button>
		<p class="semrs-kw-strip__note">Free, no sign-up. Runs in your browser: nothing you type is sent to SEMRS.</p>
	</form>
</div>
<!-- /wp:html --></div>
<!-- /wp:group -->
