<?php
/**
 * Title: SEMRS: Hero with illustration
 * Slug: semrs/hero-split
 * Categories: semrs
 * Keywords: semrs, section, hero, banner, illustration
 * Block Types: core/group
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"semrs-section semrs-hero semrs-hero--split","layout":{"type":"default"}} -->
<div class="wp-block-group semrs-section semrs-hero semrs-hero--split"><!-- wp:columns {"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"52%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:52%"><!-- wp:paragraph {"className":"semrs-eyebrow"} -->
<p class="semrs-eyebrow">Grow with SEO</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">SEO that brings you leads, with prices in public</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"semrs-lead"} -->
<p class="semrs-lead">Monthly SEO, web design and content for small businesses. A human approves every change before it goes live.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/get-a-quote/">Get a free SEO review</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/tools/">Try free SEO tools</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"48%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:48%"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"semrs-hero-art"} -->
<figure class="wp-block-image size-full semrs-hero-art"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/hero-illustration.svg' ); ?>" alt="" width="560" height="440"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
