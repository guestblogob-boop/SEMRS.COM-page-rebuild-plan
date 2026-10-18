<?php
/**
 * Lead tracking: Contact Form 7 success → GA4 "generate_lead" → thank-you page.
 *
 * Settings → General → "Thank-you page path" (default /thank-you/).
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setting for the thank-you path.
 */
function semrs_core_lead_settings() {
	register_setting(
		'general',
		'semrs_thank_you_path',
		array(
			'type'              => 'string',
			'default'           => '/thank-you/',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	add_settings_field(
		'semrs_thank_you_path',
		__( 'Thank-you page path (after form submit)', 'semrs-pro' ),
		function () {
			printf(
				'<input type="text" class="regular-text" name="semrs_thank_you_path" value="%s"><p class="description">%s</p>',
				esc_attr( get_option( 'semrs_thank_you_path', '/thank-you/' ) ),
				esc_html__( 'Leave empty to stay on the form page. Fires GA4 "generate_lead" either way.', 'semrs-pro' )
			);
		},
		'general'
	);
}
add_action( 'admin_init', 'semrs_core_lead_settings' );

/**
 * Inline listener, printed only on pages that load Contact Form 7.
 */
function semrs_core_lead_script() {
	if ( ! wp_script_is( 'contact-form-7', 'enqueued' ) ) {
		return;
	}
	$path = (string) get_option( 'semrs_thank_you_path', '/thank-you/' );
	$url  = $path ? home_url( $path ) : '';
	?>
	<script>
	document.addEventListener('wpcf7mailsent', function (e) {
		var formId = e.detail && e.detail.contactFormId;
		var go = <?php echo wp_json_encode( esc_url_raw( $url ) ); ?>;
		var done = false;
		function next() { if (!done && go) { done = true; window.location.href = go; } }
		if (typeof window.gtag === 'function') {
			window.gtag('event', 'generate_lead', { form_id: String(formId || ''), page_path: location.pathname, event_callback: next });
			setTimeout(next, 1200);
		} else {
			next();
		}
	});
	</script>
	<?php
}
add_action( 'wp_footer', 'semrs_core_lead_script', 99 );
