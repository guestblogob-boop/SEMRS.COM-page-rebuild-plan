<?php
/**
 * Review widgets (free): show your real Trustpilot TrustBox or a link to
 * your Google reviews with [semrs_reviews]. Trustpilot's script is loaded
 * only on pages that use the shortcode, and only after the visitor's
 * browser reaches it (async), so other pages carry no review code.
 *
 * Reviews are never stored or written here: the widget shows what the
 * review platform publishes.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings with defaults.
 *
 * @return array
 */
function semrs_reviews_settings() {
	$saved = get_option( 'semrs_reviews', array() );
	return array_merge(
		array(
			'tp_unit'     => '',
			'tp_domain'   => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
			'tp_template' => '5419b6a8b0d04a076446a9ad',
			'tp_locale'   => str_replace( '_', '-', get_locale() ),
			'google_url'  => '',
		),
		is_array( $saved ) ? $saved : array()
	);
}

/**
 * Free TrustBox templates offered by Trustpilot.
 *
 * @return array<string, string>
 */
function semrs_reviews_templates() {
	return array(
		'5419b6a8b0d04a076446a9ad' => __( 'Micro Review Count (one line)', 'semrs-core' ),
		'5419b732fbfb950b10de65e5' => __( 'Micro Star', 'semrs-core' ),
		'53aa8807dec7e10d38f59f36' => __( 'Mini (stars and review count)', 'semrs-core' ),
		'56278e9abfbbba0bdcd568bc' => __( 'Review Collector (invite reviews)', 'semrs-core' ),
	);
}

/**
 * [semrs_reviews provider="trustpilot|google" template="" height="24" theme="light|dark"]
 *
 * @param array $atts Attributes.
 * @return string
 */
function semrs_reviews_shortcode( $atts ) {
	$s    = semrs_reviews_settings();
	$atts = shortcode_atts(
		array(
			'provider' => 'trustpilot',
			'template' => $s['tp_template'],
			'height'   => '',
			'theme'    => 'light',
			'label'    => '',
		),
		$atts,
		'semrs_reviews'
	);
	if ( 'google' === $atts['provider'] ) {
		if ( ! $s['google_url'] ) {
			return '';
		}
		$label = $atts['label'] ? $atts['label'] : __( 'Read our reviews on Google', 'semrs-core' );
		return '<p class="semrs-reviews semrs-reviews--google"><a class="wp-element-button" href="' . esc_url( $s['google_url'] ) . '" target="_blank" rel="noopener">' . esc_html( $label ) . '</a></p>';
	}
	if ( ! preg_match( '/^[a-f0-9]{24}$/', $s['tp_unit'] ) ) {
		return current_user_can( 'manage_options' ) ? '<p class="semrs-reviews">' . esc_html__( 'Add your Trustpilot business unit ID under SEMRS → Reviews.', 'semrs-core' ) . '</p>' : '';
	}
	$template = preg_match( '/^[a-f0-9]{24}$/', $atts['template'] ) ? $atts['template'] : $s['tp_template'];
	$heights  = array(
		'5419b6a8b0d04a076446a9ad' => 24,
		'5419b732fbfb950b10de65e5' => 24,
		'53aa8807dec7e10d38f59f36' => 150,
		'56278e9abfbbba0bdcd568bc' => 52,
	);
	$height   = absint( $atts['height'] ) ? absint( $atts['height'] ) : ( $heights[ $template ] ?? 24 );
	wp_enqueue_script( 'semrs-trustpilot', 'https://widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js', array(), null, array( 'in_footer' => true, 'strategy' => 'async' ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Trustpilot serves the current version.
	return sprintf(
		'<div class="semrs-reviews trustpilot-widget" data-locale="%1$s" data-template-id="%2$s" data-businessunit-id="%3$s" data-style-height="%4$dpx" data-style-width="100%%" data-theme="%5$s" style="min-height:%4$dpx"><a href="%6$s" target="_blank" rel="noopener">%7$s</a></div>',
		esc_attr( $s['tp_locale'] ),
		esc_attr( $template ),
		esc_attr( $s['tp_unit'] ),
		$height,
		'dark' === $atts['theme'] ? 'dark' : 'light',
		esc_url( 'https://www.trustpilot.com/review/' . rawurlencode( $s['tp_domain'] ) ),
		esc_html__( 'See our reviews on Trustpilot', 'semrs-core' )
	);
}
add_shortcode( 'semrs_reviews', 'semrs_reviews_shortcode' );

if ( ! is_admin() ) {
	return;
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'Review Widgets', 'semrs-core' ), __( 'Reviews', 'semrs-core' ), 'manage_options', 'semrs-reviews', 'semrs_reviews_page' );
	},
	40
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'semrs_reviews',
			'semrs_reviews',
			array(
				'sanitize_callback' => function ( $in ) {
					$in = is_array( $in ) ? wp_unslash( $in ) : array();
					return array(
						'tp_unit'     => preg_match( '/^[a-f0-9]{24}$/', trim( (string) ( $in['tp_unit'] ?? '' ) ) ) ? trim( $in['tp_unit'] ) : '',
						'tp_domain'   => sanitize_text_field( $in['tp_domain'] ?? '' ),
						'tp_template' => array_key_exists( $in['tp_template'] ?? '', semrs_reviews_templates() ) ? $in['tp_template'] : '5419b6a8b0d04a076446a9ad',
						'tp_locale'   => preg_match( '/^[a-z]{2}-[A-Z]{2}$/', (string) ( $in['tp_locale'] ?? '' ) ) ? $in['tp_locale'] : 'en-US',
						'google_url'  => esc_url_raw( $in['google_url'] ?? '' ),
					);
				},
			)
		);
	}
);

/**
 * Settings screen.
 */
function semrs_reviews_page() {
	$s = semrs_reviews_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Review Widgets', 'semrs-core' ); ?></h1>
		<p><?php esc_html_e( 'Show your real reviews from Trustpilot or Google anywhere with the shortcode below. The review platform shows the reviews; nothing is copied or edited here.', 'semrs-core' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'semrs_reviews' ); ?>
			<h2>Trustpilot</h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="semrs-tp-unit"><?php esc_html_e( 'Business unit ID', 'semrs-core' ); ?></label></th><td>
					<input id="semrs-tp-unit" class="regular-text code" name="semrs_reviews[tp_unit]" value="<?php echo esc_attr( $s['tp_unit'] ); ?>" placeholder="24 characters">
					<p class="description"><?php esc_html_e( 'Trustpilot Business → Integrations → TrustBox: the data-businessunit-id value in the widget code.', 'semrs-core' ); ?></p>
				</td></tr>
				<tr><th><label for="semrs-tp-domain"><?php esc_html_e( 'Profile domain', 'semrs-core' ); ?></label></th><td><input id="semrs-tp-domain" class="regular-text" name="semrs_reviews[tp_domain]" value="<?php echo esc_attr( $s['tp_domain'] ); ?>"> <span class="description">trustpilot.com/review/<strong><?php echo esc_html( $s['tp_domain'] ); ?></strong></span></td></tr>
				<tr><th><label for="semrs-tp-template"><?php esc_html_e( 'Default widget', 'semrs-core' ); ?></label></th><td><select id="semrs-tp-template" name="semrs_reviews[tp_template]">
					<?php foreach ( semrs_reviews_templates() as $id => $label ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, $s['tp_template'] ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select></td></tr>
				<tr><th><label for="semrs-tp-locale"><?php esc_html_e( 'Language', 'semrs-core' ); ?></label></th><td><input id="semrs-tp-locale" class="small-text" name="semrs_reviews[tp_locale]" value="<?php echo esc_attr( $s['tp_locale'] ); ?>" placeholder="en-US"></td></tr>
			</table>
			<h2>Google</h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="semrs-g-url"><?php esc_html_e( 'Google reviews link', 'semrs-core' ); ?></label></th><td><input id="semrs-g-url" type="url" class="large-text" name="semrs_reviews[google_url]" value="<?php echo esc_attr( $s['google_url'] ); ?>" placeholder="https://g.page/r/…"></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2><?php esc_html_e( 'Shortcodes', 'semrs-core' ); ?></h2>
		<p><code>[semrs_reviews]</code> — <?php esc_html_e( 'Trustpilot widget (default style)', 'semrs-core' ); ?><br>
		<code>[semrs_reviews template="53aa8807dec7e10d38f59f36" theme="dark"]</code> — <?php esc_html_e( 'another style, dark background', 'semrs-core' ); ?><br>
		<code>[semrs_reviews provider="google"]</code> — <?php esc_html_e( 'button to your Google reviews', 'semrs-core' ); ?></p>
	</div>
	<?php
}
