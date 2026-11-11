<?php
/**
 * SEMRS Updater: update notices for self-hosted SEMRS themes and plugins.
 *
 * Uses WordPress's native "Update URI" mechanism: a package whose header has
 * `Update URI: https://semrs.com/...` is checked through the
 * `update_plugins_semrs.com` / `update_themes_semrs.com` filters, so the
 * normal "Update available" notices, one-click updates, auto-updates and
 * "View details" work exactly like WordPress.org packages.
 *
 * Sent with each check (twice a day, cached): product slug and version, site
 * address, WordPress and PHP versions, and the license key for SEMRS Pro.
 * Disable with: add_filter( 'semrs_updater_enabled', '__return_false' );
 *
 * The same file ships in every SEMRS package; the class name is versioned so
 * several copies can coexist safely.
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'SEMRS_Updater_1' ) ) {

	/**
	 * Registry + WordPress hooks (loaded once).
	 */
	class SEMRS_Updater_1 {

		/**
		 * Registered packages: slug => [type, file, version, name].
		 *
		 * @var array
		 */
		private static $packages = array();

		/**
		 * Hooks added?
		 *
		 * @var bool
		 */
		private static $hooked = false;

		/**
		 * Register a package.
		 *
		 * @param array $args type (plugin|theme), slug, file, version, name.
		 */
		public static function register( $args ) {
			self::$packages[ $args['slug'] ] = $args;
			if ( self::$hooked ) {
				return;
			}
			self::$hooked = true;
			add_filter( 'update_plugins_semrs.com', array( __CLASS__, 'plugin_update' ), 10, 3 );
			add_filter( 'update_themes_semrs.com', array( __CLASS__, 'theme_update' ), 10, 3 );
			add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
			add_action( 'upgrader_process_complete', array( __CLASS__, 'flush' ) );
			add_action( 'load-update-core.php', array( __CLASS__, 'maybe_force' ) );
		}

		/**
		 * Update server base URL.
		 *
		 * @return string
		 */
		public static function server() {
			$base = defined( 'SEMRS_UPDATE_SERVER' ) ? SEMRS_UPDATE_SERVER : 'https://semrs.com/wp-json/semrs-updates/v1/';
			return trailingslashit( (string) apply_filters( 'semrs_update_server', $base ) );
		}

		/**
		 * Ask the server about one package (cached 12 hours).
		 *
		 * @param string $slug Package slug.
		 * @return array|null
		 */
		public static function check( $slug ) {
			if ( ! apply_filters( 'semrs_updater_enabled', true ) || empty( self::$packages[ $slug ] ) ) {
				return null;
			}
			$cache_key = 'semrs_upd_' . md5( $slug );
			$cached    = get_site_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached ? $cached : null;
			}
			$pkg  = self::$packages[ $slug ];
			$args = array(
				'slug'    => $slug,
				'version' => $pkg['version'],
				'site'    => home_url( '/' ),
				'wp'      => get_bloginfo( 'version' ),
				'php'     => PHP_VERSION,
				'key'     => 'semrs-pro' === $slug ? (string) get_option( 'semrs_pro_license_key', '' ) : '',
			);
			$res  = wp_remote_get( add_query_arg( array_map( 'rawurlencode', $args ), self::server() . 'check' ), array( 'timeout' => 10 ) );
			$data = array();
			if ( ! is_wp_error( $res ) && 200 === (int) wp_remote_retrieve_response_code( $res ) ) {
				$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
				if ( is_array( $json ) && ! empty( $json['version'] ) ) {
					$data = $json;
					if ( isset( $json['license'] ) && 'semrs-pro' === $slug ) {
						update_option( 'semrs_pro_license_remote', sanitize_key( $json['license'] ), false );
					}
				}
			}
			// Cache failures for 1 hour so an unreachable server never slows admin pages.
			set_site_transient( $cache_key, $data, $data ? 12 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
			return $data ? $data : null;
		}

		/**
		 * Plugins: answer WordPress's update check.
		 *
		 * @param array|false $update      Existing update data.
		 * @param array       $plugin_data Plugin headers.
		 * @param string      $plugin_file Plugin file (folder/file.php).
		 * @return array|false
		 */
		public static function plugin_update( $update, $plugin_data, $plugin_file ) {
			$slug = dirname( $plugin_file );
			if ( empty( self::$packages[ $slug ] ) ) {
				return $update;
			}
			$d = self::check( $slug );
			if ( ! $d ) {
				return $update;
			}
			return array(
				'id'           => 'semrs.com/' . $slug,
				'slug'         => $slug,
				'plugin'       => $plugin_file,
				'version'      => $d['version'],
				'new_version'  => $d['version'],
				'url'          => isset( $d['homepage'] ) ? $d['homepage'] : 'https://semrs.com/',
				'package'      => isset( $d['package'] ) ? $d['package'] : '',
				'tested'       => isset( $d['tested'] ) ? $d['tested'] : '',
				'requires'     => isset( $d['requires'] ) ? $d['requires'] : '',
				'requires_php' => isset( $d['requires_php'] ) ? $d['requires_php'] : '',
				'icons'        => isset( $d['icons'] ) ? (array) $d['icons'] : array(),
				'upgrade_notice' => isset( $d['notice'] ) ? $d['notice'] : '',
			);
		}

		/**
		 * Themes: answer WordPress's update check (WordPress 6.1+).
		 *
		 * @param array|false $update     Existing update data.
		 * @param array       $theme_data Theme headers.
		 * @param string      $stylesheet Theme folder.
		 * @return array|false
		 */
		public static function theme_update( $update, $theme_data, $stylesheet ) {
			if ( empty( self::$packages[ $stylesheet ] ) ) {
				return $update;
			}
			$d = self::check( $stylesheet );
			if ( ! $d ) {
				return $update;
			}
			return array(
				'id'           => 'semrs.com/' . $stylesheet,
				'theme'        => $stylesheet,
				'version'      => $d['version'],
				'new_version'  => $d['version'],
				'url'          => isset( $d['homepage'] ) ? $d['homepage'] : 'https://semrs.com/',
				'package'      => isset( $d['package'] ) ? $d['package'] : '',
				'requires'     => isset( $d['requires'] ) ? $d['requires'] : '',
				'requires_php' => isset( $d['requires_php'] ) ? $d['requires_php'] : '',
			);
		}

		/**
		 * "View details" popup for plugins (changelog, requirements).
		 *
		 * @param false|object $result Result.
		 * @param string       $action API action.
		 * @param object       $args   Arguments.
		 * @return false|object
		 */
		public static function plugin_info( $result, $action, $args ) {
			if ( 'plugin_information' !== $action || empty( $args->slug ) || empty( self::$packages[ $args->slug ] ) ) {
				return $result;
			}
			$d = self::check( $args->slug );
			if ( ! $d ) {
				return $result;
			}
			return (object) array(
				'name'          => self::$packages[ $args->slug ]['name'],
				'slug'          => $args->slug,
				'version'       => $d['version'],
				'author'        => '<a href="https://semrs.com/">SEMRS</a>',
				'homepage'      => isset( $d['homepage'] ) ? $d['homepage'] : 'https://semrs.com/',
				'requires'      => isset( $d['requires'] ) ? $d['requires'] : '',
				'tested'        => isset( $d['tested'] ) ? $d['tested'] : '',
				'requires_php'  => isset( $d['requires_php'] ) ? $d['requires_php'] : '',
				'last_updated'  => isset( $d['last_updated'] ) ? $d['last_updated'] : '',
				'download_link' => isset( $d['package'] ) ? $d['package'] : '',
				'icons'         => isset( $d['icons'] ) ? (array) $d['icons'] : array(),
				'banners'       => isset( $d['banners'] ) ? (array) $d['banners'] : array(),
				'sections'      => array(
					'changelog' => isset( $d['changelog'] ) ? wp_kses_post( $d['changelog'] ) : '',
				),
			);
		}

		/**
		 * Clear cached checks after any update.
		 */
		public static function flush() {
			foreach ( array_keys( self::$packages ) as $slug ) {
				delete_site_transient( 'semrs_upd_' . md5( $slug ) );
			}
		}

		/**
		 * "Check again" on Dashboard → Updates also refreshes SEMRS checks.
		 */
		public static function maybe_force() {
			if ( isset( $_GET['force-check'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- read-only refresh.
				self::flush();
			}
		}
	}
}
