<?php
/**
 * Title: SEMRS: Hero with cover image
 * Slug: semrs/hero-cover
 * Categories: semrs, featured
 * Keywords: semrs, hero, banner, cover, image, photo, agency
 * Block Types: core/group
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"align":"full","className":"semrs-section semrs-hero semrs-hero-cover","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull semrs-section semrs-hero semrs-hero-cover"><!-- wp:columns {"verticalAlignment":"center","className":"semrs-hero-cover__cols"} -->
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
<div class="wp-block-column is-vertically-aligned-bottom semrs-hero-cover__media" style="flex-basis:48%"><!-- wp:group {"className":"semrs-hero-cover__stage","layout":{"type":"default"}} -->
<div class="wp-block-group semrs-hero-cover__stage"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"semrs-hero-cover__photo"} -->
<figure class="wp-block-image size-full semrs-hero-cover__photo"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/hero-photo.jpg' ); ?>" alt="SEO specialist smiling and holding a tablet" width="800" height="1000" fetchpriority="high"/></figure>
<!-- /wp:image -->

<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"semrs-hero-cover__overlay"} -->
<figure class="wp-block-image size-full semrs-hero-cover__overlay"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/hero-cover-overlay.svg' ); ?>" alt="" width="600" height="600"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
