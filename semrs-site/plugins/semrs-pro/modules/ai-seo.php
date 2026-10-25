<?php
/**
 * AI SEO (Pro): SEO titles, meta descriptions, focus keywords, social text and image alt text
 * written by the AI provider the site owner chooses (Anthropic Claude, OpenAI or Google Gemini),
 * with their own API key: they pay the provider directly. Suggestions appear in the SEMRS SEO
 * sidebar ("AI" buttons); optional autofill on publish and alt text on upload; bulk fill on the
 * settings screen. Every request needs a valid SEMRS Pro license.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Saved settings with defaults.
 *
 * @return array{provider:string,key:string,model:string,autofill:bool,auto_alt:bool}
 */
function semrs_ai_settings() {
	$s = get_option( 'semrs_ai', array() );
	return array_merge(
		array(
			'provider' => 'anthropic',
			'key'      => '',
			'model'    => '',
			'autofill' => false,
			'auto_alt' => false,
		),
		is_array( $s ) ? $s : array()
	);
}

/**
 * Providers: label, default model and where to get a key.
 *
 * @return array
 */
function semrs_ai_providers() {
	return array(
		'anthropic' => array( 'Anthropic Claude', 'claude-opus-5-5', 'https://console.anthropic.com/settings/keys' ),
		'openai'    => array( 'OpenAI', 'gpt-4o-mini', 'https://platform.openai.com/api-keys' ),
		'gemini'    => array( 'Google Gemini', 'gemini-2.5-flash', 'https://aistudio.google.com/app/apikey' ),
	);
}

/**
 * Can this site use AI SEO right now?
 *
 * @return true|WP_Error
 */
function semrs_ai_ready() {
	if ( ! function_exists( 'semrs_seo_pro' ) || ! semrs_seo_pro() ) {
		return new WP_Error( 'semrs_ai_pro', __( 'AI SEO unlocks with a SEMRS Pro license.', 'semrs-pro' ) );
	}
	if ( '' === semrs_ai_settings()['key'] ) {
		return new WP_Error( 'semrs_ai_key', __( 'Add your AI provider API key under SEMRS → AI SEO.', 'semrs-pro' ) );
	}
	return true;
}

/**
 * Send one prompt (and optionally one image) to the chosen provider and return the reply text.
 *
 * @param string $prompt Prompt.
 * @param string $image  Optional base64 image data.
 * @param string $mime   Image media type.
 * @return string|WP_Error
 */
function semrs_ai_complete( $prompt, $image = '', $mime = 'image/jpeg' ) {
	$s     = semrs_ai_settings();
	$prov  = isset( semrs_ai_providers()[ $s['provider'] ] ) ? $s['provider'] : 'anthropic';
	$model = '' !== trim( $s['model'] ) ? trim( $s['model'] ) : semrs_ai_providers()[ $prov ][1];
	$args  = array( 'timeout' => 60, 'headers' => array( 'Content-Type' => 'application/json' ) );

	if ( 'anthropic' === $prov ) {
		$content = array();
		if ( $image ) {
			$content[] = array( 'type' => 'image', 'source' => array( 'type' => 'base64', 'media_type' => $mime, 'data' => $image ) );
		}
		$content[] = array( 'type' => 'text', 'text' => $prompt );
		$body      = array(
			'model'         => $model,
			'max_tokens'    => 4000,
			'output_config' => array( 'effort' => 'low' ), // Short copywriting: low effort keeps it fast and cheap.
			'messages'      => array( array( 'role' => 'user', 'content' => $content ) ),
		);
		$args['headers'] += array( 'x-api-key' => $s['key'], 'anthropic-version' => '2026-10-21' );
		if ( in_array( $model, array( 'claude-opus-5-5', 'claude-opus-5', 'claude-fable-5-1', 'claude-sonnet-5-5' ), true ) ) {
			$args['headers']['anthropic-beta'] = 'server-side-fallback-2026-07-01'; // If a safety check declines, the API retries on a fallback model.
			$body['fallbacks']                 = 'default';
		}
		$url = 'https://api.anthropic.com/v1/messages';
	} elseif ( 'openai' === $prov ) {
		$content = array( array( 'type' => 'text', 'text' => $prompt ) );
		if ( $image ) {
			$content[] = array( 'type' => 'image_url', 'image_url' => array( 'url' => 'data:' . $mime . ';base64,' . $image ) );
		}
		$body                             = array( 'model' => $model, 'messages' => array( array( 'role' => 'user', 'content' => $content ) ) );
		$args['headers']['Authorization'] = 'Bearer ' . $s['key'];
		$url                              = 'https://api.openai.com/v1/chat/completions';
	} else {
		$parts = array( array( 'text' => $prompt ) );
		if ( $image ) {
			$parts[] = array( 'inline_data' => array( 'mime_type' => $mime, 'data' => $image ) );
		}
		$body                              = array( 'contents' => array( array( 'parts' => $parts ) ) );
		$args['headers']['x-goog-api-key'] = $s['key'];
		$url                               = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $model ) . ':generateContent';
	}

	$args['body'] = wp_json_encode( $body );
	$res          = wp_remote_post( $url, $args );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( $code < 200 || $code >= 300 || ! is_array( $data ) ) {
		$msg = is_array( $data ) ? ( $data['error']['message'] ?? ( is_string( $data['error'] ?? null ) ? $data['error'] : '' ) ) : '';
		/* translators: 1: HTTP status, 2: provider message */
		return new WP_Error( 'semrs_ai_http', sprintf( __( 'The AI provider returned an error (%1$d). %2$s', 'semrs-pro' ), $code, $msg ) );
	}
	$text = '';
	if ( 'anthropic' === $prov ) {
		if ( 'refusal' === ( $data['stop_reason'] ?? '' ) ) {
			return new WP_Error( 'semrs_ai_refusal', __( 'The AI declined this request. Try rewording the content or another provider.', 'semrs-pro' ) );
		}
		foreach ( (array) ( $data['content'] ?? array() ) as $block ) {
			if ( 'text' === ( $block['type'] ?? '' ) ) {
				$text .= $block['text'];
			}
		}
	} elseif ( 'openai' === $prov ) {
		$text = (string) ( $data['choices'][0]['message']['content'] ?? '' );
	} else {
		foreach ( (array) ( $data['candidates'][0]['content']['parts'] ?? array() ) as $part ) {
			$text .= (string) ( $part['text'] ?? '' );
		}
	}
	return '' === trim( $text ) ? new WP_Error( 'semrs_ai_empty', __( 'The AI returned no text.', 'semrs-pro' ) ) : trim( $text );
}

/**
 * Writing rules for every request (house style); sites can add their own.
 *
 * @return string
 */
function semrs_ai_rules() {
	$rules = array(
		'Write in plain, natural ' . ( 0 === strpos( get_locale(), 'en_GB' ) ? 'British' : ( 0 === strpos( get_locale(), 'en' ) ? 'US' : '' ) ) . ' English (or the language of the content if it is not English).',
		'Be accurate to the content. Never invent statistics, prices, results, awards, reviews or guarantees.',
		'No clickbait, no ALL CAPS, no emoji, no quotation marks around the text.',
	);
	return implode( "\n", (array) apply_filters( 'semrs_ai_rules', $rules ) );
}

/**
 * Ask for suggestions for one field of a post.
 *
 * @param string $field   title|description|focus|og_title|og_description.
 * @param array  $context post_title, content, focus, url.
 * @return string[]|WP_Error
 */
function semrs_ai_suggest( $field, $context ) {
	$ready = semrs_ai_ready();
	if ( is_wp_error( $ready ) ) {
		return $ready;
	}
	$kw   = trim( (string) ( $context['focus'] ?? '' ) );
	$task = array(
		'title'          => 'Write 3 SEO titles for this page. Each 50-60 characters, with the focus keyword at the very beginning when one is given. Where it fits naturally, include a number or a strong word (for example proven, simple, complete, best). Do not add the site name.',
		'description'    => 'Write 3 meta descriptions for this page. Each 140-160 characters, starting with the focus keyword when one is given, summarising what the reader gets and ending with a reason to click.',
		'focus'          => 'Suggest 3 focus keywords for this page: realistic search phrases of 2-4 words that people type into Google, ordered from best to third best. The first should match the main topic exactly.',
		'og_title'       => 'Write 3 titles for sharing this page on Facebook and LinkedIn. Each under 70 characters, engaging and specific.',
		'og_description' => 'Write 3 descriptions for sharing this page on Facebook and LinkedIn. Each 100-200 characters, conversational and specific.',
	);
	if ( ! isset( $task[ $field ] ) ) {
		return new WP_Error( 'semrs_ai_field', __( 'Unknown field.', 'semrs-pro' ) );
	}
	$text   = wp_trim_words( wp_strip_all_tags( (string) ( $context['content'] ?? '' ) ), 1200, '' );
	$prompt = $task[ $field ] . "\n\n" . semrs_ai_rules() . "\n\nReply with JSON only, in this exact form: {\"suggestions\": [\"first\", \"second\", \"third\"]}\n\n"
		. 'Site: ' . get_bloginfo( 'name' ) . "\n"
		. 'Page title: ' . ( $context['post_title'] ?? '' ) . "\n"
		. ( '' !== $kw ? 'Focus keyword: ' . $kw . "\n" : '' )
		. "Page content:\n" . $text;
	$out = semrs_ai_complete( $prompt );
	if ( is_wp_error( $out ) ) {
		return $out;
	}
	$json = json_decode( preg_match( '/\{.*\}/s', $out, $m ) ? $m[0] : $out, true );
	$list = is_array( $json ) && ! empty( $json['suggestions'] ) ? (array) $json['suggestions'] : array_filter( array_map( 'trim', preg_split( '/\n+/', $out ) ) );
	$list = array_values( array_filter( array_map( function ( $v ) { return trim( wp_strip_all_tags( (string) $v ), " \"'-•*\t" ); }, $list ) ) );
	return array_slice( $list, 0, 3 );
}

/**
 * Alt text for an image attachment, written from the picture itself.
 *
 * @param int $id Attachment ID.
 * @return string|WP_Error
 */
function semrs_ai_alt_text( $id ) {
	$ready = semrs_ai_ready();
	if ( is_wp_error( $ready ) ) {
		return $ready;
	}
	$mime = (string) get_post_mime_type( $id );
	if ( ! in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ), true ) ) {
		return new WP_Error( 'semrs_ai_type', __( 'Only JPEG, PNG, WebP and GIF images can be described.', 'semrs-pro' ) );
	}
	// A mid-size copy keeps the request small.
	$src  = image_get_intermediate_size( $id, 'medium_large' ) ?: image_get_intermediate_size( $id, 'large' );
	$file = $src && ! empty( $src['path'] ) ? path_join( wp_get_upload_dir()['basedir'], $src['path'] ) : get_attached_file( $id );
	if ( ! $file || ! file_exists( $file ) || filesize( $file ) > 5 * MB_IN_BYTES ) {
		return new WP_Error( 'semrs_ai_file', __( 'Image file not found or too large.', 'semrs-pro' ) );
	}
	$parent = (int) wp_get_post_parent_id( $id );
	$kw     = $parent && function_exists( 'semrs_seo_meta' ) ? (string) semrs_seo_meta( $parent, 'focus' ) : '';
	$prompt = 'Write alt text for this image: one sentence under 125 characters that describes what is actually shown, for people who cannot see it. Do not start with "Image of" or "Picture of".'
		. ( '' !== $kw ? ' If it fits naturally, include the phrase "' . $kw . '".' : '' )
		. "\n" . semrs_ai_rules() . "\nReply with the alt text only.";
	$out = semrs_ai_complete( $prompt, base64_encode( file_get_contents( $file ) ), $mime ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local upload file.
	return is_wp_error( $out ) ? $out : mb_substr( trim( wp_strip_all_tags( $out ), " \"'" ), 0, 125 );
}

/**
 * Sidebar request: suggestions for one field.
 */
add_action(
	'wp_ajax_semrs_ai_suggest',
	function () {
		check_ajax_referer( 'semrs_ai', 'nonce' );
		$id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'semrs-pro' ) ), 403 );
		}
		$post = get_post( $id );
		$res  = semrs_ai_suggest(
			sanitize_key( wp_unslash( $_POST['field'] ?? '' ) ),
			array(
				'post_title' => sanitize_text_field( wp_unslash( $_POST['post_title'] ?? $post->post_title ) ),
				'content'    => isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : $post->post_content,
				'focus'      => sanitize_text_field( wp_unslash( $_POST['focus'] ?? '' ) ),
			)
		);
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}
		wp_send_json_success( array( 'suggestions' => $res ) );
	}
);

/**
 * Give the Core sidebar its AI settings.
 */
add_filter(
	'semrs_seo_sidebar_config',
	function ( $cfg ) {
		$ready      = semrs_ai_ready();
		$cfg['ai']  = array(
			'ready'    => true === $ready,
			'message'  => is_wp_error( $ready ) ? $ready->get_error_message() : '',
			'nonce'    => wp_create_nonce( 'semrs_ai' ),
			'settings' => admin_url( 'admin.php?page=semrs-ai' ),
		);
		return $cfg;
	}
);

/**
 * Autofill on publish: empty focus keyword, SEO title and description are written by AI.
 *
 * @param int $id Post ID.
 */
function semrs_ai_autofill( $id ) {
	$post = get_post( $id );
	if ( ! $post || 'publish' !== $post->post_status || ! function_exists( 'semrs_seo_meta' ) || true !== semrs_ai_ready() ) {
		return;
	}
	$ctx = array( 'post_title' => $post->post_title, 'content' => $post->post_content, 'focus' => (string) semrs_seo_meta( $id, 'focus' ) );
	foreach ( array( 'focus', 'title', 'description' ) as $field ) {
		if ( '' !== (string) semrs_seo_meta( $id, $field ) ) {
			continue; // Never overwrite what a person (or an import) already set.
		}
		$res = semrs_ai_suggest( $field, $ctx );
		if ( ! is_wp_error( $res ) && ! empty( $res[0] ) ) {
			update_post_meta( $id, '_semrs_' . $field, sanitize_text_field( $res[0] ) );
			if ( 'focus' === $field ) {
				$ctx['focus'] = $res[0];
			}
		}
	}
}
add_action( 'semrs_ai_autofill', 'semrs_ai_autofill' );
add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( 'publish' === $new && 'publish' !== $old && semrs_ai_settings()['autofill'] && function_exists( 'semrs_seo_post_types' ) && in_array( $post->post_type, semrs_seo_post_types(), true ) ) {
			wp_schedule_single_event( time() + 15, 'semrs_ai_autofill', array( (int) $post->ID ) ); // In the background, so publishing stays instant.
		}
	},
	10,
	3
);

/**
 * Alt text on upload (when switched on), in the background.
 */
add_action(
	'add_attachment',
	function ( $id ) {
		if ( semrs_ai_settings()['auto_alt'] && wp_attachment_is_image( $id ) ) {
			wp_schedule_single_event( time() + 30, 'semrs_ai_alt_event', array( (int) $id ) );
		}
	}
);
add_action(
	'semrs_ai_alt_event',
	function ( $id ) {
		if ( '' !== trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ) {
			return;
		}
		$alt = semrs_ai_alt_text( $id );
		if ( ! is_wp_error( $alt ) && '' !== $alt ) {
			update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
		}
	}
);

if ( ! is_admin() ) {
	return;
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'AI SEO', 'semrs-pro' ), __( 'AI SEO', 'semrs-pro' ), 'manage_options', 'semrs-ai', 'semrs_ai_page' );
	},
	19
);

/**
 * Save settings (the key is kept when the field is left empty).
 */
add_action(
	'admin_post_semrs_ai_save',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'semrs-pro' ) );
		}
		check_admin_referer( 'semrs_ai_save' );
		$old  = semrs_ai_settings();
		$in   = wp_unslash( $_POST['semrs_ai'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field.
		$prov = sanitize_key( $in['provider'] ?? 'anthropic' );
		$key  = trim( sanitize_text_field( $in['key'] ?? '' ) );
		update_option(
			'semrs_ai',
			array(
				'provider' => isset( semrs_ai_providers()[ $prov ] ) ? $prov : 'anthropic',
				'key'      => ! empty( $in['forget'] ) ? '' : ( '' !== $key ? $key : $old['key'] ),
				'model'    => sanitize_text_field( $in['model'] ?? '' ),
				'autofill' => ! empty( $in['autofill'] ),
				'auto_alt' => ! empty( $in['auto_alt'] ),
			),
			false
		);
		wp_safe_redirect( admin_url( 'admin.php?page=semrs-ai&saved=1' ) );
		exit;
	}
);

/**
 * Settings screen actions over AJAX: connection test and bulk fills (a few items per request).
 */
add_action(
	'wp_ajax_semrs_ai_tool',
	function () {
		check_ajax_referer( 'semrs_ai_tool', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'semrs-pro' ) ), 403 );
		}
		$do = sanitize_key( wp_unslash( $_POST['do'] ?? '' ) );
		if ( 'test' === $do ) {
			$ready = semrs_ai_ready();
			$out   = true === $ready ? semrs_ai_complete( 'Reply with the single word: ready' ) : $ready;
			is_wp_error( $out ) ? wp_send_json_error( array( 'message' => $out->get_error_message() ) ) : wp_send_json_success( array( 'message' => __( 'Connected. The AI replied: ', 'semrs-pro' ) . mb_substr( $out, 0, 40 ) ) );
		}
		if ( 'alt' === $do ) {
			$ids = get_posts(
				array(
					'post_type'      => 'attachment',
					'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ),
					'post_status'    => 'inherit',
					'posts_per_page' => 3,
					'fields'         => 'ids',
					'meta_query'     => array( 'relation' => 'OR', array( 'key' => '_wp_attachment_image_alt', 'compare' => 'NOT EXISTS' ), array( 'key' => '_wp_attachment_image_alt', 'value' => '' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery -- admin tool.
				)
			);
			$done = 0;
			foreach ( $ids as $id ) {
				$alt = semrs_ai_alt_text( $id );
				if ( is_wp_error( $alt ) ) {
					update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( get_the_title( $id ) ) ); // Skip it next round.
					continue;
				}
				update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
				$done++;
			}
			wp_send_json_success( array( 'done' => $done, 'more' => count( $ids ) === 3 ) );
		}
		if ( 'meta' === $do ) {
			$ids = get_posts(
				array(
					'post_type'      => function_exists( 'semrs_seo_post_types' ) ? semrs_seo_post_types() : array( 'post', 'page' ),
					'post_status'    => 'publish',
					'posts_per_page' => 2,
					'fields'         => 'ids',
					'meta_query'     => array( 'relation' => 'OR', array( 'key' => '_semrs_description', 'compare' => 'NOT EXISTS' ), array( 'key' => '_semrs_description', 'value' => '' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery -- admin tool.
					'post__not_in'   => array_map( 'absint', (array) get_option( 'semrs_ai_meta_skip', array() ) ),
				)
			);
			$done = 0;
			foreach ( $ids as $id ) {
				$before = (string) get_post_meta( $id, '_semrs_description', true );
				semrs_ai_autofill( $id );
				if ( '' === (string) get_post_meta( $id, '_semrs_description', true ) || $before === (string) get_post_meta( $id, '_semrs_description', true ) ) {
					update_option( 'semrs_ai_meta_skip', array_merge( (array) get_option( 'semrs_ai_meta_skip', array() ), array( $id ) ), false );
				} else {
					$done++;
				}
			}
			wp_send_json_success( array( 'done' => $done, 'more' => count( $ids ) === 2 ) );
		}
		wp_send_json_error( array( 'message' => __( 'Unknown action.', 'semrs-pro' ) ) );
	}
);

/**
 * Settings screen.
 */
function semrs_ai_page() {
	$s     = semrs_ai_settings();
	$ready = semrs_ai_ready();
	$pro   = function_exists( 'semrs_seo_pro' ) && semrs_seo_pro();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'AI SEO', 'semrs-pro' ); ?></h1>
		<p><?php esc_html_e( 'SEO titles, meta descriptions, focus keywords, social text and image alt text written by AI from your page content. You use your own API key and pay the provider directly; SEMRS never sees your content or key.', 'semrs-pro' ); ?></p>
		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification -- display only. ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Settings saved.', 'semrs-pro' ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! $pro ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'AI SEO unlocks with a SEMRS Pro license. Activate your license under SEMRS → License.', 'semrs-pro' ); ?></p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="semrs_ai_save">
			<?php wp_nonce_field( 'semrs_ai_save' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="semrs_ai_provider"><?php esc_html_e( 'AI provider', 'semrs-pro' ); ?></label></th><td>
					<select id="semrs_ai_provider" name="semrs_ai[provider]">
						<?php foreach ( semrs_ai_providers() as $k => $p ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['provider'], $k ); ?>><?php echo esc_html( $p[0] ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php foreach ( semrs_ai_providers() as $p ) : ?><a href="<?php echo esc_url( $p[2] ); ?>" target="_blank" rel="noopener"><?php /* translators: %s: provider */ echo esc_html( sprintf( __( 'Get a %s key', 'semrs-pro' ), $p[0] ) ); ?></a> &nbsp; <?php endforeach; ?></p>
				</td></tr>
				<tr><th scope="row"><label for="semrs_ai_key"><?php esc_html_e( 'API key', 'semrs-pro' ); ?></label></th><td>
					<input type="password" class="regular-text" id="semrs_ai_key" name="semrs_ai[key]" value="" autocomplete="off" placeholder="<?php echo esc_attr( '' !== $s['key'] ? '•••••••• ' . substr( $s['key'], -4 ) : '' ); ?>">
					<p class="description"><?php esc_html_e( 'Stored on your site only. Leave empty to keep the saved key.', 'semrs-pro' ); ?></p>
					<?php if ( '' !== $s['key'] ) : ?><label><input type="checkbox" name="semrs_ai[forget]" value="1"> <?php esc_html_e( 'Remove the saved key', 'semrs-pro' ); ?></label><?php endif; ?>
				</td></tr>
				<tr><th scope="row"><label for="semrs_ai_model"><?php esc_html_e( 'Model', 'semrs-pro' ); ?></label></th><td>
					<input class="regular-text" id="semrs_ai_model" name="semrs_ai[model]" value="<?php echo esc_attr( $s['model'] ); ?>" placeholder="<?php echo esc_attr( semrs_ai_providers()[ $s['provider'] ][1] ?? '' ); ?>">
					<p class="description"><?php esc_html_e( 'Leave empty for the default model of the provider.', 'semrs-pro' ); ?></p>
				</td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Automatic', 'semrs-pro' ); ?></th><td>
					<label><input type="checkbox" name="semrs_ai[autofill]" value="1" <?php checked( $s['autofill'] ); ?>> <?php esc_html_e( 'When a post or page is published, write its focus keyword, SEO title and meta description if they are empty', 'semrs-pro' ); ?></label><br>
					<label><input type="checkbox" name="semrs_ai[auto_alt]" value="1" <?php checked( $s['auto_alt'] ); ?>> <?php esc_html_e( 'Write alt text for new image uploads that have none', 'semrs-pro' ); ?></label>
				</td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2><?php esc_html_e( 'Tools', 'semrs-pro' ); ?></h2>
		<p>
			<button type="button" class="button" data-semrs-ai="test"<?php disabled( true !== $ready ); ?>><?php esc_html_e( 'Test connection', 'semrs-pro' ); ?></button>
			<button type="button" class="button" data-semrs-ai="meta"<?php disabled( true !== $ready ); ?>><?php esc_html_e( 'Fill missing SEO titles and descriptions', 'semrs-pro' ); ?></button>
			<button type="button" class="button" data-semrs-ai="alt"<?php disabled( true !== $ready ); ?>><?php esc_html_e( 'Write missing image alt text', 'semrs-pro' ); ?></button>
		</p>
		<p id="semrs-ai-status" class="description" aria-live="polite"><?php echo is_wp_error( $ready ) ? esc_html( $ready->get_error_message() ) : ''; ?></p>
		<p class="description"><?php esc_html_e( 'Bulk tools only fill empty fields and never overwrite what you wrote. Each item is one request to your provider.', 'semrs-pro' ); ?></p>
		<script>
		document.querySelectorAll('[data-semrs-ai]').forEach(function (b) {
			b.addEventListener('click', function () {
				var st = document.getElementById('semrs-ai-status'), total = 0, step = function () {
					var f = new FormData(); f.append('action', 'semrs_ai_tool'); f.append('do', b.dataset.semrsAi); f.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'semrs_ai_tool' ) ); ?>);
					fetch(ajaxurl, { method: 'POST', body: f, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
						if (!r.success) { st.textContent = r.data && r.data.message ? r.data.message : 'Error'; b.disabled = false; return; }
						if (r.data.message) { st.textContent = r.data.message; b.disabled = false; return; }
						total += r.data.done; st.textContent = <?php echo wp_json_encode( __( 'Done so far:', 'semrs-pro' ) ); ?> + ' ' + total;
						if (r.data.more) { step(); } else { st.textContent += ' — ' + <?php echo wp_json_encode( __( 'finished.', 'semrs-pro' ) ); ?>; b.disabled = false; }
					}).catch(function () { st.textContent = 'Error'; b.disabled = false; });
				};
				b.disabled = true; st.textContent = '…'; step();
			});
		});
		</script>
	</div>
	<?php
}
