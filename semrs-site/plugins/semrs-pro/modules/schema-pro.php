<?php
/**
 * Custom schema (Pro): add your own JSON-LD to any post or page (Event,
 * Product, Course, Recipe, HowTo, Review of a product you sell, …) from a
 * box in the editor, with ready-to-fill starters or a fill-in form (Event, Course, Recipe,
 * Product, Job posting, Software). It is checked as JSON on
 * save and printed in the page head next to the SEMRS or Rank Math graph.
 *
 * Google's rule still applies: mark up only what visitors can see on the
 * page.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Print the saved JSON-LD.
 */
function semrs_schema_pro_print() {
	if ( ! is_singular() ) {
		return;
	}
	$json = (string) get_post_meta( get_queried_object_id(), '_semrs_custom_schema', true );
	$data = $json ? json_decode( $json, true ) : null;
	if ( ! is_array( $data ) ) {
		return;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON with HEX_TAG.
}
add_action( 'wp_head', 'semrs_schema_pro_print', 30 );

if ( ! is_admin() ) {
	return;
}

/**
 * Starters (placeholders in CAPITALS).
 *
 * @return array<string, array>
 */
function semrs_schema_pro_starters() {
	$ctx = array( '@context' => 'https://schema.org' );
	return array(
		'Event'   => $ctx + array( '@type' => 'Event', 'name' => 'EVENT NAME', 'startDate' => '2026-12-01T10:00', 'endDate' => '2026-12-01T12:00', 'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode', 'eventStatus' => 'https://schema.org/EventScheduled', 'location' => array( '@type' => 'VirtualLocation', 'url' => 'JOIN URL' ), 'organizer' => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) ) ),
		'Product' => $ctx + array( '@type' => 'Product', 'name' => 'PRODUCT NAME', 'description' => 'SHORT DESCRIPTION', 'image' => 'IMAGE URL', 'brand' => array( '@type' => 'Brand', 'name' => get_bloginfo( 'name' ) ), 'offers' => array( '@type' => 'Offer', 'price' => '0.00', 'priceCurrency' => 'USD', 'availability' => 'https://schema.org/InStock', 'url' => 'PAGE URL' ) ),
		'Course'  => $ctx + array( '@type' => 'Course', 'name' => 'COURSE NAME', 'description' => 'WHAT STUDENTS LEARN', 'provider' => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'sameAs' => home_url( '/' ) ) ),
		'HowTo'   => $ctx + array( '@type' => 'HowTo', 'name' => 'HOW TO …', 'step' => array( array( '@type' => 'HowToStep', 'text' => 'FIRST STEP' ), array( '@type' => 'HowToStep', 'text' => 'SECOND STEP' ) ) ),
		'Recipe'  => $ctx + array( '@type' => 'Recipe', 'name' => 'RECIPE NAME', 'image' => 'IMAGE URL', 'recipeIngredient' => array( 'INGREDIENT' ), 'recipeInstructions' => array( array( '@type' => 'HowToStep', 'text' => 'STEP' ) ) ),
		'VideoObject' => $ctx + array( '@type' => 'VideoObject', 'name' => 'VIDEO TITLE', 'description' => 'WHAT THE VIDEO SHOWS', 'thumbnailUrl' => 'THUMBNAIL URL', 'uploadDate' => '2026-10-21', 'contentUrl' => 'VIDEO URL' ),
	);
}

add_action(
	'add_meta_boxes',
	function () {
		foreach ( get_post_types( array( 'public' => true ) ) as $type ) {
			if ( 'attachment' !== $type ) {
				add_meta_box( 'semrs_custom_schema', __( 'Custom schema (JSON-LD)', 'semrs-pro' ), 'semrs_schema_pro_box', $type, 'normal', 'low' );
			}
		}
	}
);

/**
 * Form templates: schema.org type => label, fixed base values, fields (key = dotted path in the
 * JSON, req = required by Google for the rich result), site = properties that default to the
 * site's organization, url = add the page address.
 *
 * @return array
 */
function semrs_schema_pro_forms() {
	$status = array( array( 'https://schema.org/EventScheduled', __( 'Scheduled', 'semrs-pro' ) ), array( 'https://schema.org/EventPostponed', __( 'Postponed', 'semrs-pro' ) ), array( 'https://schema.org/EventCancelled', __( 'Cancelled', 'semrs-pro' ) ) );
	$mode   = array( array( 'https://schema.org/OfflineEventAttendanceMode', __( 'In person', 'semrs-pro' ) ), array( 'https://schema.org/OnlineEventAttendanceMode', __( 'Online', 'semrs-pro' ) ), array( 'https://schema.org/MixedEventAttendanceMode', __( 'Both', 'semrs-pro' ) ) );
	$stock  = array( array( 'https://schema.org/InStock', __( 'In stock', 'semrs-pro' ) ), array( 'https://schema.org/OutOfStock', __( 'Out of stock', 'semrs-pro' ) ), array( 'https://schema.org/PreOrder', __( 'Pre-order', 'semrs-pro' ) ) );
	$forms  = array(
		'Event'               => array(
			'label'  => __( 'Event', 'semrs-pro' ),
			'base'   => array(),
			'site'   => array( 'organizer' ),
			'url'    => true,
			'fields' => array(
				array( 'key' => 'name', 'label' => __( 'Event name', 'semrs-pro' ), 'req' => true ),
				array( 'key' => 'startDate', 'label' => __( 'Starts', 'semrs-pro' ), 'type' => 'datetime-local', 'req' => true ),
				array( 'key' => 'endDate', 'label' => __( 'Ends', 'semrs-pro' ), 'type' => 'datetime-local' ),
				array( 'key' => 'eventStatus', 'label' => __( 'Status', 'semrs-pro' ), 'type' => 'select', 'options' => $status ),
				array( 'key' => 'eventAttendanceMode', 'label' => __( 'Where', 'semrs-pro' ), 'type' => 'select', 'options' => $mode ),
				array( 'key' => 'location.name', 'label' => __( 'Venue name (or "Online")', 'semrs-pro' ), 'req' => true ),
				array( 'key' => 'location.address', 'label' => __( 'Venue address or join link', 'semrs-pro' ) ),
				array( 'key' => 'image', 'label' => __( 'Image address', 'semrs-pro' ), 'type' => 'url' ),
				array( 'key' => 'description', 'label' => __( 'Short description', 'semrs-pro' ) ),
				array( 'key' => 'offers.price', 'label' => __( 'Ticket price (0 = free)', 'semrs-pro' ), 'type' => 'number' ),
				array( 'key' => 'offers.priceCurrency', 'label' => __( 'Currency (e.g. USD)', 'semrs-pro' ) ),
				array( 'key' => 'offers.url', 'label' => __( 'Ticket link', 'semrs-pro' ), 'type' => 'url' ),
			),
		),
		'Course'              => array(
			'label'  => __( 'Course', 'semrs-pro' ),
			'base'   => array(),
			'site'   => array( 'provider' ),
			'url'    => true,
			'fields' => array(
				array( 'key' => 'name', 'label' => __( 'Course name', 'semrs-pro' ), 'req' => true ),
				array( 'key' => 'description', 'label' => __( 'Description', 'semrs-pro' ), 'req' => true ),
				array( 'key' => 'offers.price', 'label' => __( 'Price (0 = free)', 'semrs-pro' ), 'type' => 'number' ),
				array( 'key' => 'offers.priceCurrency', 'label' => __( 'Currency (e.g. USD)', 'semrs-pro' ) ),
				array( 'key' => 'hasCourseInstance.courseMode', 'label' => __( 'Mode (online, onsite, blended)', 'semrs-pro' ) ),
				array( 'key' => 'hasCourseInstance.courseWorkload', 'label' => __( 'Workload (e.g. PT10H = 10 hours)', 'semrs-pro' ) ),
			),
		),
		'Recipe'              => array(
			'label'  => __( 'Recipe', 'semrs-pro' ),
			'base'   => array(),
			'site'   => array( 'author' ),
			'fields' => array(
				array( 'key' => 'name', 'label' => __( 'Recipe name', 'semrs-pro' ), 'req' => true ),
				array( 'key' => 'image', 'label' => __( 'Photo address', 'semrs-pro' ), 'type' => 'url', 'req' => true ),
				array( 'key' => 'description', 'label' => __( 'Short description', 'semrs-pro' ) ),
				array( 'key' => 'prepTime', 'label' => __( 'Prep time (e.g. PT15M)', 'semrs-pro' ) ),
				array( 'key' => 'cookTime', 'label' => __( 'Cook time (e.g. PT1H)', 'semrs-pro' ) ),
				array( 'key' => 'recipeYield', 'label' => __( 'Servings (e.g. 4)', 'semrs-pro' ) ),
				array( 'key' => 'recipeCategory', 'label' => __( 'Category (e.g. Dinner)', 'semrs-pro' ) ),
				array( 'key' => 'recipeCuisine', 'label' => __( 'Cuisine (e.g. Italian)', 'semrs-pro' ) ),
				array( 'key' => 'recipeIngredient', 'label' => __( 'Ingredients', 'semrs-pro' ), 'type' => 'list', 'req' => true ),
				array( 'key' => 'recipeInstructions', 'label' => __( 'Steps', 'semrs-pro' ), 'type' => 'list', 'wrap' => 'HowToStep', 'req' => true ),
			),
		),
		'Product'             => array(
			'label'  => __( 'Product (without WooCommerce)', 'semrs-pro' ),
			'base'   => array( 'offers' => array( '@type' => 'Offer' ) ),
			'url'    => true,
			'fields' => array(
				array( 'key' => 'name', 'label' => __( 'Product name', 'semrs-pro' ), 'req' => true ),
				array( 'key' => 'image', 'label' => __( 'Image address', 'semrs-pro' ), 'type' => 'url', 'req' => true ),
				array( 'key' => 'description', 'label' => __( 'Description', 'semrs-pro' ) ),
				array( 'key' => 'brand.name', 'label' => __( 'Brand', 'semrs-pro' ) ),
				array( 'key' => 'sku', 'label' => __( 'SKU', 'semrs-pro' ) ),
				array( 'key' => 'gtin', 'label' => __( 'GTIN / EAN / UPC', 'semrs-pro' ) ),
				array( 'key' => 'offers.price', 'label' => __( 'Price', 'semrs-pro' ), 'type' => 'number', 'req' => true ),
				array( 'key' => 'offers.priceCurrency', 'label' => __( 'Currency (e.g. USD)', 'semrs-pro' ), 'req' => true ),
				array( 'key' => 'offers.availability', 'label' => __( 'Stock', 'semrs-pro' ), 'type' => 'select', 'options' => $stock ),
			),
		),
		'JobPosting'          => array(
			'label'  => __( 'Job posting', 'semrs-pro' ),
			'base'   => array( 'jobLocation' => array( '@type' => 'Place', 'address' => array( '@type' => 'PostalAddress' ) ) ),
			'site'   => array( 'hiringOrganization' ),
			'fields' => array(
				array( 'key' => 'title', 'label' => __( 'Job title', 'semrs-pro' ), 'req' => true ),
				array( 'key' => 'description', 'label' => __( 'Job description', 'semrs-pro' ), 'req' => true ),
				array( 'key' => 'datePosted', 'label' => __( 'Date posted', 'semrs-pro' ), 'type' => 'date', 'req' => true ),
				array( 'key' => 'validThrough', 'label' => __( 'Apply before', 'semrs-pro' ), 'type' => 'date' ),
				array( 'key' => 'employmentType', 'label' => __( 'Type (FULL_TIME, PART_TIME, CONTRACTOR…)', 'semrs-pro' ) ),
				array( 'key' => 'jobLocation.address.addressLocality', 'label' => __( 'City', 'semrs-pro' ), 'req' => true ),
				array( 'key' => 'jobLocation.address.addressRegion', 'label' => __( 'State / region', 'semrs-pro' ) ),
				array( 'key' => 'jobLocation.address.addressCountry', 'label' => __( 'Country code (e.g. US)', 'semrs-pro' ), 'req' => true ),
				array( 'key' => 'baseSalary.currency', 'label' => __( 'Salary currency', 'semrs-pro' ) ),
				array( 'key' => 'baseSalary.value.value', 'label' => __( 'Salary amount', 'semrs-pro' ), 'type' => 'number' ),
				array( 'key' => 'baseSalary.value.unitText', 'label' => __( 'Per (HOUR, MONTH, YEAR)', 'semrs-pro' ) ),
			),
		),
		'SoftwareApplication' => array(
			'label'  => __( 'Software or app', 'semrs-pro' ),
			'base'   => array( 'offers' => array( '@type' => 'Offer' ) ),
			'url'    => true,
			'fields' => array(
				array( 'key' => 'name', 'label' => __( 'App name', 'semrs-pro' ), 'req' => true ),
				array( 'key' => 'applicationCategory', 'label' => __( 'Category (e.g. BusinessApplication)', 'semrs-pro' ), 'req' => true ),
				array( 'key' => 'operatingSystem', 'label' => __( 'Works on (e.g. Windows, Android, Web)', 'semrs-pro' ), 'req' => true ),
				array( 'key' => 'offers.price', 'label' => __( 'Price (0 = free)', 'semrs-pro' ), 'type' => 'number', 'req' => true ),
				array( 'key' => 'offers.priceCurrency', 'label' => __( 'Currency (e.g. USD)', 'semrs-pro' ), 'req' => true ),
			),
		),
	);
	// Typed sub-objects for the dotted paths.
	$types = array( 'location' => 'Place', 'offers' => 'Offer', 'brand' => 'Brand', 'hasCourseInstance' => 'CourseInstance', 'baseSalary' => 'MonetaryAmount' );
	foreach ( $forms as $k => $f ) {
		foreach ( $f['fields'] as $field ) {
			$root = explode( '.', $field['key'] )[0];
			if ( isset( $types[ $root ] ) && false !== strpos( $field['key'], '.' ) && empty( $forms[ $k ]['base'][ $root ]['@type'] ) ) {
				$forms[ $k ]['base'][ $root ]['@type'] = $types[ $root ];
			}
		}
	}
	if ( isset( $forms['JobPosting'] ) ) {
		$forms['JobPosting']['base']['baseSalary']['value'] = array( '@type' => 'QuantitativeValue' );
	}
	return (array) apply_filters( 'semrs_schema_pro_forms', $forms );
}

/**
 * Editor box.
 *
 * @param WP_Post $post Post.
 */
function semrs_schema_pro_box( $post ) {
	$json  = (string) get_post_meta( $post->ID, '_semrs_custom_schema', true );
	$error = get_transient( 'semrs_schema_err_' . $post->ID );
	wp_nonce_field( 'semrs_custom_schema', 'semrs_custom_schema_nonce' );
	if ( $error ) {
		delete_transient( 'semrs_schema_err_' . $post->ID );
		echo '<div class="notice notice-error inline"><p>' . esc_html__( 'The schema was not saved: it is not valid JSON.', 'semrs-pro' ) . '</p></div>';
	}
	?>
	<p>
		<label for="semrs-schema-starter"><?php esc_html_e( 'Start from:', 'semrs-pro' ); ?></label>
		<select id="semrs-schema-starter"><option value=""><?php esc_html_e( '— choose —', 'semrs-pro' ); ?></option>
			<?php foreach ( semrs_schema_pro_starters() as $name => $data ) : ?>
				<option value="<?php echo esc_attr( wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?>"><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?>
		</select>
		<a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener"><?php esc_html_e( 'Test in Google Rich Results', 'semrs-pro' ); ?></a>
	</p>
	<details id="semrs-schema-form" style="margin:0 0 10px">
		<summary style="cursor:pointer;font-weight:600"><?php esc_html_e( 'Fill in a form instead of writing JSON', 'semrs-pro' ); ?></summary>
		<p>
			<label for="semrs-schema-form-type"><?php esc_html_e( 'Type:', 'semrs-pro' ); ?></label>
			<select id="semrs-schema-form-type"><option value=""><?php esc_html_e( '— choose —', 'semrs-pro' ); ?></option>
			<?php foreach ( semrs_schema_pro_forms() as $type => $form ) : ?>
				<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $form['label'] ); ?></option>
			<?php endforeach; ?>
			</select>
		</p>
		<div id="semrs-schema-form-fields" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:8px 14px"></div>
		<p><button type="button" class="button button-secondary" id="semrs-schema-form-build" hidden><?php esc_html_e( 'Build schema', 'semrs-pro' ); ?></button> <span class="description" id="semrs-schema-form-note"></span></p>
	</details>
	<textarea id="semrs-custom-schema" name="semrs_custom_schema" rows="10" class="large-text code" spellcheck="false"><?php echo esc_textarea( $json ); ?></textarea>
	<p class="description"><?php esc_html_e( 'One JSON-LD object (or a list). Replace every CAPITALISED placeholder, and only describe what visitors can see on this page. Leave empty for none.', 'semrs-pro' ); ?></p>
	<script>
	document.getElementById('semrs-schema-starter').addEventListener('change', function () {
		var t = document.getElementById('semrs-custom-schema');
		if (this.value && (!t.value.trim() || confirm(<?php echo wp_json_encode( __( 'Replace the current schema?', 'semrs-pro' ) ); ?>))) { t.value = this.value; }
		this.value = '';
	});
	(function () {
		var forms = <?php echo wp_json_encode( semrs_schema_pro_forms() ); ?>;
		var site = <?php echo wp_json_encode( array( 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ), 'page' => get_permalink( $post ) ) ); ?>;
		var sel = document.getElementById('semrs-schema-form-type'), box = document.getElementById('semrs-schema-form-fields');
		var btn = document.getElementById('semrs-schema-form-build'), note = document.getElementById('semrs-schema-form-note');
		sel.addEventListener('change', function () {
			box.innerHTML = ''; note.textContent = ''; btn.hidden = !this.value;
			if (!this.value) { return; }
			forms[this.value].fields.forEach(function (f) {
				var wrap = document.createElement('label'); wrap.style.display = 'block';
				var span = document.createElement('span'); span.textContent = f.label + (f.req ? ' *' : ''); span.style.cssText = 'display:block;font-weight:600;margin-bottom:2px';
				var inp = document.createElement(f.type === 'list' ? 'textarea' : (f.type === 'select' ? 'select' : 'input'));
				inp.dataset.key = f.key; inp.className = 'widefat';
				if (f.type === 'list') { inp.rows = 3; inp.placeholder = <?php echo wp_json_encode( __( 'One per line', 'semrs-pro' ) ); ?>; }
				else if (f.type === 'select') { f.options.forEach(function (o) { var op = document.createElement('option'); op.value = o[0]; op.textContent = o[1]; inp.appendChild(op); }); }
				else { inp.type = f.type || 'text'; if (f.ph) { inp.placeholder = f.ph; } }
				wrap.appendChild(span); wrap.appendChild(inp); box.appendChild(wrap);
			});
		});
		var put = function (obj, path, val) { var p = path.split('.'), o = obj; for (var i = 0; i < p.length - 1; i++) { o[p[i]] = o[p[i]] || {}; o = o[p[i]]; } o[p[p.length - 1]] = val; };
		btn.addEventListener('click', function () {
			var type = sel.value, form = forms[type], out = JSON.parse(JSON.stringify(form.base)), missing = [];
			box.querySelectorAll('[data-key]').forEach(function (inp) {
				var f = form.fields.filter(function (x) { return x.key === inp.dataset.key; })[0], v = inp.value.trim();
				if (!v) { if (f.req) { missing.push(f.label); } return; }
				if (f.type === 'list') { var items = v.split(/\n+/).map(function (s) { return s.trim(); }).filter(Boolean); v = f.wrap ? items.map(function (s) { var x = {}; x['@type'] = f.wrap; x[f.wrapKey || 'text'] = s; return x; }) : items; }
				if (f.type === 'number') { v = String(v); }
				put(out, f.key, v);
			});
			if (missing.length) { note.textContent = <?php echo wp_json_encode( __( 'Please fill in:', 'semrs-pro' ) ); ?> + ' ' + missing.join(', '); return; }
			out['@context'] = 'https://schema.org'; out['@type'] = type;
			(form.site || []).forEach(function (k) { if (!out[k]) { out[k] = { '@type': 'Organization', name: site.name, url: site.url }; } });
			if (form.url && !out.url) { out.url = site.page; }
			var t = document.getElementById('semrs-custom-schema'), json = JSON.stringify(out, null, 2);
			if (!t.value.trim() || confirm(<?php echo wp_json_encode( __( 'Replace the current schema?', 'semrs-pro' ) ); ?>)) { t.value = json; note.textContent = <?php echo wp_json_encode( __( 'Done. Save the page, then test it in Google Rich Results.', 'semrs-pro' ) ); ?>; }
		});
	})();
	</script>
	<?php
}

add_action(
	'save_post',
	function ( $post_id ) {
		if ( ! isset( $_POST['semrs_custom_schema_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['semrs_custom_schema_nonce'] ) ), 'semrs_custom_schema' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$raw = trim( (string) wp_unslash( $_POST['semrs_custom_schema'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated as JSON below.
		if ( '' === $raw ) {
			delete_post_meta( $post_id, '_semrs_custom_schema' );
			return;
		}
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			set_transient( 'semrs_schema_err_' . $post_id, 1, 300 );
			return;
		}
		update_post_meta( $post_id, '_semrs_custom_schema', wp_slash( wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
	}
);
