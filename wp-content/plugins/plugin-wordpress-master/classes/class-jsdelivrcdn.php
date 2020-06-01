<?php
/**
 * JsDelivrCdn class file.
 *
 * @package JsDelivrCdn
 */

/**
 * Class JsDelivrCdn
 */
class JsDelivrCdn {
	const SOURCE_LIST = 'source_list';

	const ADVANCED_MODE = 'advanced_mode';

	const AUTOENABLE = 'autoenable';

	const AUTOMINIFY = 'autominify';

	const PLUGIN_SETTINGS = 'jsdelivrcdn_settings';

	const JSDELIVR_SOURCE_URL = 'jsdelivr_url';

	const ORIGINAL_SOURCE_URL = 'original_url';

	const SOURCE_LAST_LOADED = 'last_loaded_datetime';

	const JSDELIVR_ANALYZE_CRON_HOOK = 'jsdelivr_analyze_cron_hook';

	const JSDELIVR_REMOVE_OLD_CRON_HOOK = 'jsdelivr_remove_old_cron_hook';

	const JSDELIVR_CDN_URL = 'https://cdn.jsdelivr.net/';

	const COMMENT_PATTERN = '/\/\*[\s\S]*?\*\/|\/\/.*?(?:\n|$)/';

	/**
	 * Set true when plugin initialized
	 *
	 * @var bool
	 */
	private static $initiated = false;
	/**
	 * Hash lookup URL
	 *
	 * @var string
	 */
	private static $jsdelivr_hash_lookup_url = 'https://data.jsdelivr.com/v1/lookup/hash/';
	/**
	 * Options
	 *
	 * @var array
	 */
	private static $options;
	/**
	 * Plugin version.
	 *
	 * @var string $jsdelivr_plugin_version
	 */
	private static $jsdelivr_plugin_version;

	/**
	 * Init function
	 */
	public static function init() {
		$plugin_data = get_plugin_data( JSDELIVRCDN_PLUGIN_FILE );

		self::$jsdelivr_plugin_version = $plugin_data['Version'];

		self::$options = get_option( self::PLUGIN_SETTINGS );
		if ( ! self::$initiated ) {
			self::init_hooks();
		}

		if ( ! wp_next_scheduled( self::JSDELIVR_ANALYZE_CRON_HOOK ) ) {
			wp_schedule_event( time(), 'fifteen_minutes', self::JSDELIVR_ANALYZE_CRON_HOOK );
		}
	}

	/**
	 * Init hooks
	 */
	private static function init_hooks() {
		self::$initiated = true;

		add_action( self::JSDELIVR_ANALYZE_CRON_HOOK, [ 'JsDelivrCdn', 'analyze' ] );
		add_action( self::JSDELIVR_REMOVE_OLD_CRON_HOOK, [ 'JsDelivrCdn', 'clear_old_sources' ] );

		if ( is_admin() ) {
			add_action( 'admin_menu', [ 'JsDelivrCdn', 'add_admin_pages' ] );
			add_action( 'admin_init', [ 'JsDelivrCdn', 'admin_init' ] );
			add_filter( 'plugin_action_links_' . JSDELIVRCDN_PLUGIN_NAME, [ 'JsDelivrCdn', 'settings_link' ] );
			add_action( 'admin_enqueue_scripts', [ 'JsDelivrCdn', 'admin_enqueue_scripts' ] );

			add_action( 'wp_ajax_clear_source_list', [ 'JsDelivrCdn', 'clear_source_list' ] );
			add_action( 'wp_ajax_clear_source', [ 'JsDelivrCdn', 'clear_source' ] );
			add_action( 'wp_ajax_get_source_list', [ 'JsDelivrCdn', 'get_source_list' ] );
			add_action( 'wp_ajax_jsdelivr_analyze', [ 'JsDelivrCdn', 'jsdelivr_analyze' ] );
			add_action( 'wp_ajax_delete_source_list', [ 'JsDelivrCdn', 'delete_source_list' ] );
			add_action( 'wp_ajax_advanced_mode_switch', [ 'JsDelivrCdn', 'advanced_mode_switch' ] );
			add_action( 'wp_ajax_autoenable_switch', [ 'JsDelivrCdn', 'autoenable_switch' ] );
			add_action( 'wp_ajax_save_form', [ 'JsDelivrCdn', 'save_form' ] );
		} else {
			add_action( 'wp_print_scripts', [ 'JsDelivrCdn', 'custom_enqueue_scripts' ], 999 );
			add_action( 'wp_print_styles', [ 'JsDelivrCdn', 'custom_enqueue_styles' ], 999 );
		}
	}

	/**
	 * Plugin activation hook
	 */
	public static function activate() {
		self::$options = get_option( self::PLUGIN_SETTINGS );
		if ( ! self::$options ) {
			self::$options = [
				self::SOURCE_LIST   => [],
				self::ADVANCED_MODE => false,
				self::AUTOENABLE    => true,
				self::AUTOMINIFY    => true,
			];
			add_option( self::PLUGIN_SETTINGS, self::$options );
		}

		if ( ! wp_next_scheduled( self::JSDELIVR_ANALYZE_CRON_HOOK ) ) {
			wp_schedule_event( time(), 'fifteen_minutes', self::JSDELIVR_ANALYZE_CRON_HOOK );
		}

		if ( ! wp_next_scheduled( self::JSDELIVR_REMOVE_OLD_CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::JSDELIVR_REMOVE_OLD_CRON_HOOK );
		}
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::JSDELIVR_ANALYZE_CRON_HOOK );
		wp_clear_scheduled_hook( self::JSDELIVR_REMOVE_OLD_CRON_HOOK );
		flush_rewrite_rules();
	}
	/**
	 * Enqueue admin page styles and scripts
	 *
	 * @param string $hook Hook name.
	 */
	public static function admin_enqueue_scripts( $hook ) {
		if ( 'toplevel_page_jsdelivrcdn' === $hook ) {
			wp_register_script( 'jsdelivrcdn-script', JSDELIVRCDN_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), '1.0', true );
			wp_enqueue_script( 'jsdelivrcdn-script' );

			wp_register_style( 'jsdelivrcdn-style', JSDELIVRCDN_PLUGIN_URL . 'assets/css/style.css', '', 1 );
			wp_enqueue_style( 'jsdelivrcdn-style' );
		}
	}

	/**
	 * Add link to admin page
	 *
	 * @param array $links Links array.
	 * @return mixed
	 */
	public static function settings_link( $links ) {
		array_push( $links, '<a href="admin.php?page=jsdelivrcdn">Settings</a>' );
		return $links;
	}

	/**
	 * Check if remote url exists and files are same
	 *
	 * @param string $url Remote file url.
	 * @param string $sha256 Original file sha256.
	 *
	 * @return bool
	 */
	public static function check_remote_file( $url, $sha256 ) {
		$accepted_status_codes = array( 200 );

		$response = wp_safe_remote_get( $url, self::get_jsdelivr_cdn_request_options() );
		if ( is_wp_error( $response ) || ! in_array( wp_remote_retrieve_response_code( $response ), $accepted_status_codes, true ) ) {
			return false;
		}

		$file_content = wp_remote_retrieve_body( $response );

		$new_file_sha256 = hash( 'sha256', $file_content );

		return $new_file_sha256 === $sha256;
	}

	/**
	 * Check files by hash with jsdelivr api
	 *
	 * @param string $sha256 File sha256.
	 * @return array|bool|mixed|object
	 */
	private static function get_jsdelivr_data( $sha256 ) {
		$response = wp_safe_remote_get( self::$jsdelivr_hash_lookup_url . $sha256, self::get_jsdelivr_cdn_request_options() );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$file_content = wp_remote_retrieve_body( $response );

		$result = json_decode( $file_content, true );

		$result['sha256'] = $sha256;

		return $result;
	}

	/**
	 * Get jsdelivr url for script
	 *
	 * @param array $source Source object.
	 * @return bool|string
	 */
	public static function get_jsdelivr_source_url( $source ) {
		WP_Filesystem();
		global $wp_filesystem;

		$script = $source['handle'];

		$jsdelivrcdn_url = '';

		$plugin_data = [];

		if ( empty( $script->src ) ) {
			return $jsdelivrcdn_url;
		}

		if ( ! defined( 'ABSPATH' ) ) {
			return $jsdelivrcdn_url;
		}

		chdir( ABSPATH );

		$position = strpos( $source[ self::ORIGINAL_SOURCE_URL ], '//' );
		if ( false !== $position ) {
			$response = wp_safe_remote_get( ( 0 === $position ? 'http:' : '' ) . $source[ self::ORIGINAL_SOURCE_URL ], self::get_jsdelivr_cdn_request_options() );

			$file_content = wp_remote_retrieve_body( $response );
		} else {
			$file_content = $wp_filesystem->get_contents( $source[ self::ORIGINAL_SOURCE_URL ] );
		}

		if ( ! $file_content ) {
			return $jsdelivrcdn_url;
		}

		$sha256 = hash( 'sha256', $file_content );

		$jsdelivr_data = self::get_jsdelivr_data( $sha256 );

		if ( isset( $jsdelivr_data['file'] ) ) {
			if ( 'WordPress/WordPress' === $jsdelivr_data['name'] ) {
				$jsdelivr_data['version'] = get_bloginfo( 'version' );
			}

			$temp = self::JSDELIVR_CDN_URL . "{$jsdelivr_data['type']}/{$jsdelivr_data['name']}@{$jsdelivr_data['version']}{$jsdelivr_data['file']}";

			if ( self::check_remote_file( $temp, $sha256 ) ) {
				$jsdelivrcdn_url = $temp;
			}
		} elseif ( preg_match( '/wp-content\/plugins\/(?<plugin>[^\/]*)\/(?<file>.*)/i', $script->src, $matches ) ) {
			if ( $matches['plugin'] && $matches['file'] ) {
				$plugin_file = ABSPATH . "wp-content/plugins/{$matches['plugin']}/{$matches['plugin']}.php";
				if ( file_exists( $plugin_file ) ) {
					$plugin_data = get_plugin_data( $plugin_file );
				} else {
					$php_files = glob( ABSPATH . "wp-content/plugins/{$matches['plugin']}/*.php" );
					foreach ( $php_files as $path ) {
						$plugin_data = get_plugin_data( $path );
						if ( $plugin_data['Version'] ) {
							break;
						}
					}
				}
				if ( $plugin_data['Version'] ) {
					$temp = self::JSDELIVR_CDN_URL . "wp/plugins/{$matches['plugin']}/tags/{$plugin_data['Version']}/{$matches['file']}";
					if ( self::check_remote_file( $temp, $sha256 ) ) {
						$jsdelivrcdn_url = $temp;
					}
				}
			}
		} elseif ( preg_match( '/wp-content\/themes\/(?<theme>[^\/]*)\/(?<file>.*)/i', $script->src, $matches ) ) {
			if ( $matches['theme'] && $matches['file'] ) {
				$theme = wp_get_theme( $matches['theme'] );
				if ( $theme->exists() ) {
					$temp = self::JSDELIVR_CDN_URL . "wp/themes/{$matches['theme']}/{$theme->get('Version')}/{$matches['file']}";
					if ( self::check_remote_file( $temp, $sha256 ) ) {
						$jsdelivrcdn_url = $temp;
					}
				}
			}
		}

		if ( self::$options[ self::AUTOMINIFY ] && ! self::check_if_file_minified( $file_content ) ) {
			$jsdelivrcdn_url = substr_replace( $jsdelivrcdn_url, '.min', strrpos( $jsdelivrcdn_url, '.' ), 0 );
		}

		$original_file_type = wp_check_filetype( $source[ self::ORIGINAL_SOURCE_URL ] );

		$mirror_file_type = wp_check_filetype( $jsdelivrcdn_url );

		if ( $original_file_type['ext'] !== $mirror_file_type['ext'] ) {
			$jsdelivrcdn_url = '';
		}

		return $jsdelivrcdn_url;
	}

	/**
	 * Check is file minified
	 *
	 * @param string $file_content File content.
	 * @return bool
	 */
	private static function check_if_file_minified( $file_content ) {
		$code = preg_replace( self::COMMENT_PATTERN, '', $file_content );
		return strlen( $code ) / count( preg_split( '/\n/', $code ) ) > 200;
	}

	/**
	 * Replace source with jsdelivr url
	 *
	 * @param string $handle Handle name.
	 * @param string $type Source type ('script','style').
	 * @return bool
	 */
	private static function replace_source( $handle, $type ) {
		global $wp_scripts;
		global $wp_styles;
		$updated = false;

		$index = $type . '-' . $handle;

		$source = ( 'script' === $type ) ? $wp_scripts->registered[ $handle ] : $wp_styles->registered[ $handle ];

		if ( preg_match( '/cdn.jsdelivr.net|googleapi/i', $source->src ) ) {
			return $updated;
		}

		if ( ! isset( self::$options[ self::SOURCE_LIST ][ $index ] ) ) {
			self::$options[ self::SOURCE_LIST ][ $index ] = [
				'handle'                  => $source,
				self::JSDELIVR_SOURCE_URL => '',
				self::ORIGINAL_SOURCE_URL => self::get_file_path( $source->src ),
				'active'                  => self::$options[ self::AUTOENABLE ],
				self::SOURCE_LAST_LOADED  => time(),
			];

			$updated = true;

		} elseif ( time() - self::$options[ self::SOURCE_LIST ][ $index ][ self::SOURCE_LAST_LOADED ] > 60 * 60 * 24 ) {
			self::$options[ self::SOURCE_LIST ][ $index ][ self::SOURCE_LAST_LOADED ] = time();
			$updated = true;
		}
		if ( isset( self::$options[ self::SOURCE_LIST ][ $index ] ) ) {
			if ( self::$options[ self::SOURCE_LIST ][ $index ][ self::JSDELIVR_SOURCE_URL ] && self::$options[ self::SOURCE_LIST ][ $index ]['active'] ) {
				if ( 'script' === $type ) {
					wp_deregister_script( $handle );
					wp_register_script( $handle, self::$options[ self::SOURCE_LIST ][ $index ][ self::JSDELIVR_SOURCE_URL ], $source->deps, $source->ver, true );
					foreach ( $source->extra as $key => $value ) {
						wp_script_add_data( $handle, $key, $value );
					}
				} elseif ( 'style' === $type ) {
					wp_deregister_style( $handle );
					wp_register_style( $handle, self::$options[ self::SOURCE_LIST ][ $index ][ self::JSDELIVR_SOURCE_URL ], $source->deps, $source->ver );
					foreach ( $source->extra as $key => $value ) {
						wp_style_add_data( $index, $key, $value );
					}
				}
			}
		}
		foreach ( $source->deps as $dep ) {
			$updated = $updated || self::replace_source( $dep, $type );
		}
		return $updated;
	}

	/**
	 * Action for scripts replacement
	 */
	public static function custom_enqueue_scripts() {
		global $wp_scripts;
		$updated = false;

		foreach ( $wp_scripts->queue  as $handle ) {
			$updated = $updated || self::replace_source( $handle, 'script' );
		}

		if ( $updated ) {
			update_option( self::PLUGIN_SETTINGS, self::$options );
		}
	}

	/**
	 * Action for styles replacement
	 */
	public static function custom_enqueue_styles() {
		global $wp_styles;
		$updated = false;

		foreach ( $wp_styles->queue as $handle ) {
			$updated = $updated || self::replace_source( $handle, 'style' );
		}

		if ( $updated ) {
			update_option( self::PLUGIN_SETTINGS, self::$options );
		}
	}

	/**
	 * Get file path (url, local)
	 *
	 * @param string $src Source url.
	 * @return string
	 */
	public static function get_file_path( $src ) {
		if ( strpos( $src, '//' ) !== false ) {
			$site_url = site_url();

			if ( strpos( $src, $site_url ) === 0 ) {
				$file_path = ltrim( str_replace( $site_url, '', $src ), '/' );
			} else {
				$file_path = $src;
			}
		} else {
			$file_path = ltrim( $src, '/' );
		}
		return $file_path;
	}

	/**
	 * Get admin page template
	 */
	public static function get_admin_page_template() {
		require_once JSDELIVRCDN_PLUGIN_PATH . 'templates/admin.php';
	}

	/**
	 * Add admin pages
	 */
	public static function add_admin_pages() {
		add_menu_page(
			'jsDelivr CDN',
			'jsDelivr CDN',
			'manage_options',
			'jsdelivrcdn',
			[ 'JsDelivrCdn', 'get_admin_page_template' ],
			JSDELIVRCDN_PLUGIN_URL . 'assets/img/jsdelivr-icon.png',
			110
		);
	}

	/**
	 * Init Admin page setting, sections and fields
	 */
	public static function admin_init() {
		register_setting( self::PLUGIN_SETTINGS, self::PLUGIN_SETTINGS );
		add_settings_section( self::PLUGIN_SETTINGS, '', '', 'main_settings' );
		add_settings_field(
			self::ADVANCED_MODE,
			'Advanced mode',
			function() {
				?>
			<input id="<?php echo esc_attr( self::ADVANCED_MODE ); ?>" <?php echo esc_attr( self::$options[ self::ADVANCED_MODE ] ? 'checked' : '' ); ?>
			type="checkbox" name="<?php echo esc_attr( self::ADVANCED_MODE ); ?>" title="Advanced mode">
				<?php
			},
			'main_settings',
			self::PLUGIN_SETTINGS
		);
		add_settings_field(
			self::AUTOENABLE,
			'Automatically enable',
			function() {
				?>
			<input id="<?php echo esc_attr( self::AUTOENABLE ); ?>" <?php echo esc_attr( self::$options[ self::AUTOENABLE ] ? 'checked' : '' ); ?>
			type="checkbox" name="<?php echo esc_attr( self::AUTOENABLE ); ?>" title="Automatically enable">
				<?php
			},
			'main_settings',
			self::PLUGIN_SETTINGS
		);
		add_settings_field(
			self::AUTOMINIFY,
			'Automatically minify files',
			function() {
				?>
			<input id="<?php echo esc_attr( self::AUTOMINIFY ); ?>" <?php echo esc_attr( self::$options[ self::AUTOMINIFY ] ? 'checked' : '' ); ?>
			type="checkbox" name="<?php echo esc_attr( self::AUTOMINIFY ); ?>" title="Automatically minify">
				<?php
			},
			'main_settings',
			self::PLUGIN_SETTINGS
		);
	}

	/**
	 * Ajax action clear source list
	 */
	public static function clear_source_list() {
		check_ajax_referer( JSDELIVRCDN_PLUGIN_NAME, 'security' );

		foreach ( self::$options[ self::SOURCE_LIST ] as $index => $source ) {
			self::$options[ self::SOURCE_LIST ][ $index ][ self::JSDELIVR_SOURCE_URL ] = '';
			$data[ $index ] = self::$options[ self::SOURCE_LIST ][ $index ][ self::JSDELIVR_SOURCE_URL ];
		}
		update_option( self::PLUGIN_SETTINGS, self::$options );

		echo wp_json_encode( [ 'result' => 'OK' ] );
		wp_die();
	}

	/**
	 * Ajax action remove one row
	 */
	public static function clear_source() {
		check_ajax_referer( JSDELIVRCDN_PLUGIN_NAME, 'security' );

		if ( ! empty( $_POST['handle'] ) ) {
			$index = sanitize_text_field( wp_unslash( $_POST['handle'] ) );
			if ( isset( self::$options[ self::SOURCE_LIST ][ $index ] ) ) {
				self::$options[ self::SOURCE_LIST ][ $index ][ self::JSDELIVR_SOURCE_URL ] = '';
			}
			update_option( self::PLUGIN_SETTINGS, self::$options );

			echo wp_json_encode( [ 'result' => 'OK' ] );
		} else {
			echo wp_json_encode(
				[
					'result'  => 'ERROR',
					'message' => 'Input value not set',
				]
			);
		}
		wp_die();
	}

	/**
	 * Ajax Get saved data
	 */
	public static function get_source_list() {
		check_ajax_referer( JSDELIVRCDN_PLUGIN_NAME, 'security' );

		$data = [];
		foreach ( self::$options[ self::SOURCE_LIST ] as $index => $source ) {
			if ( time() - $source[ self::SOURCE_LAST_LOADED ] <= 60 * 60 * 24 || self::$options[ self::ADVANCED_MODE ] ) {
				if ( $source[ self::ORIGINAL_SOURCE_URL ] ) {
					$data[ $index ] = [
						'original_url' => $source[ self::ORIGINAL_SOURCE_URL ],
						'jsdelivr_url' => $source[ self::JSDELIVR_SOURCE_URL ],
						'active'       => $source['active'],
					];
					if ( self::$options[ self::ADVANCED_MODE ] ) {
						$data[ $index ]['ver'] = $source['handle']->ver;

						$data[ $index ]['handle'] = $source['handle']->handle;
					}
				}
			}
		}

		echo wp_json_encode(
			[
				'result' => 'OK',
				'data'   => $data,
			]
		);

		wp_die();
	}

	/**
	 * Analyze sources
	 *
	 * @return array
	 */
	public static function analyze() {

		$data = [];

		$updated = false;
		foreach ( self::$options[ self::SOURCE_LIST ] as $index => $source ) {
			if ( ! $source[ self::JSDELIVR_SOURCE_URL ] && $source['handle']->src ) {
				self::$options[ self::SOURCE_LIST ][ $index ][ self::JSDELIVR_SOURCE_URL ] = self::get_jsdelivr_source_url( $source );
				if ( self::$options[ self::AUTOENABLE ] ) {
					self::$options[ self::SOURCE_LIST ][ $index ]['active'] = true;
				}

				$updated = true;
			}

			$data[ $index ] = self::$options[ self::SOURCE_LIST ][ $index ][ self::JSDELIVR_SOURCE_URL ];

		}

		if ( $updated ) {
			update_option( self::PLUGIN_SETTINGS, self::$options );
		}

		return $data;
	}

	/**
	 * Ajax Analyze sources
	 */
	public static function jsdelivr_analyze() {
		check_ajax_referer( JSDELIVRCDN_PLUGIN_NAME, 'security' );

		$data = self::analyze();

		echo wp_json_encode(
			[
				'result' => 'OK',
				'data'   => $data,
			]
		);

		wp_die();
	}

	/**
	 * Ajax Remove all saved data
	 */
	public static function delete_source_list() {
		check_ajax_referer( JSDELIVRCDN_PLUGIN_NAME, 'security' );

		self::$options[ self::SOURCE_LIST ] = [];

		update_option( self::PLUGIN_SETTINGS, self::$options );

		echo wp_json_encode( [ 'result' => 'OK' ] );

		wp_die();
	}

	/**
	 * Remove Sources last loaded more then 48 hours ago
	 */
	public static function clear_old_sources() {
		$updated = false;
		foreach ( self::$options[ self::SOURCE_LIST ] as $index => $source ) {
			if ( time() - $source[ self::SOURCE_LAST_LOADED ] > 60 * 60 * 24 * 2 ) {
				unset( self::$options[ self::SOURCE_LIST ][ $index ] );
				$updated = true;
			}
		}

		if ( $updated ) {
			update_option( self::PLUGIN_SETTINGS, self::$options );
		}
	}

	/**
	 * Switch advanced mode setting
	 */
	public static function advanced_mode_switch() {
		check_ajax_referer( JSDELIVRCDN_PLUGIN_NAME, 'security' );

		if ( ! empty( $_POST[ self::ADVANCED_MODE ] ) ) {
			self::$options[ self::ADVANCED_MODE ] = filter_var( wp_unslash( $_POST[ self::ADVANCED_MODE ] ), FILTER_VALIDATE_BOOLEAN );

			update_option( self::PLUGIN_SETTINGS, self::$options );

			echo wp_json_encode( [ 'result' => 'OK' ] );
		} else {
			echo wp_json_encode(
				[
					'result'  => 'ERROR',
					'message' => 'Input value not set',
				]
			);
		}
		wp_die();
	}

	/**
	 * Switch autoenable setting
	 */
	public static function autoenable_switch() {
		check_ajax_referer( JSDELIVRCDN_PLUGIN_NAME, 'security' );

		if ( ! empty( $_POST[ self::AUTOENABLE ] ) ) {
			self::$options[ self::AUTOENABLE ] = filter_var( wp_unslash( $_POST[ self::AUTOENABLE ] ), FILTER_VALIDATE_BOOLEAN );

			update_option( self::PLUGIN_SETTINGS, self::$options );

			echo wp_json_encode( [ 'result' => 'OK' ] );
		} else {
			echo wp_json_encode(
				[
					'result'  => 'ERROR',
					'message' => 'Input value not set',
				]
			);
		}
		wp_die();
	}

	/**
	 * Save form ajax
	 */
	public static function save_form() {
		check_ajax_referer( JSDELIVRCDN_PLUGIN_NAME, 'security' );
		filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
		if ( isset( $_POST['source_list'] ) ) {
			$data = array_flip( explode( ',', filter_var( wp_unslash( $_POST['source_list'] ), FILTER_SANITIZE_STRING ) ) );

			foreach ( self::$options[ self::SOURCE_LIST ] as $index => $source ) {
				if ( isset( $data[ $index ] ) ) {
					self::$options[ self::SOURCE_LIST ] [ $index ] ['active'] = true;
				} else {
					self::$options[ self::SOURCE_LIST ] [ $index ] ['active'] = false;
				}
			}
			update_option( self::PLUGIN_SETTINGS, self::$options );

			echo wp_json_encode( [ 'result' => 'OK' ] );
		} else {
			echo wp_json_encode(
				[
					'result'  => 'ERROR',
					'message' => 'Input value not set',
				]
			);
		}
		wp_die();
	}

	/**
	 * Get jsDelivrCdn request options
	 */
	private static function get_jsdelivr_cdn_request_options() {
		return [
			'headers' => [ 'User-Agent' => 'jsDelivr WP plugin/' . self::$jsdelivr_plugin_version ],
		];
	}
}
