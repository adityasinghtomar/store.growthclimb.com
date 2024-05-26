<?php // phpcs:ignore
/**
 * Plugin Name: SupportCandy - WooCommerce
 * Plugin URI: https://supportcandy.net/
 * Description: WooCommerce integration for SupportCandy!
 * Version: 3.1.2
 * Author: SupportCandy
 * Author URI: https://supportcandy.net/
 * Requires at least: 5.6
 * Tested up to: 6.0
 * WC tested up to:  7.6.1
 * Text Domain:wpsc-woo
 * Domain Path: /i18n
 */

if ( ! ( class_exists( 'PSM_Support_Candy' ) ) ) {
	return;
}

if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
	return;
}

// exit if core plugin is installing.
if ( defined( 'WPSC_INSTALLING' ) ) {
	return;
}

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WOO' ) ) :

	final class WPSC_WOO {

		/**
		 * Addon version
		 *
		 * @var string
		 */
		public static $version = '3.1.2';

		/**
		 * Constructor for main class
		 */
		public static function init() {

			self::define_constants();
			add_action( 'init', array( __CLASS__, 'load_textdomain' ), 1 );
			self::load_files();

			// Return if installation is in progress.
			if ( defined( 'WPSC_DB_UPGRADING' ) || defined( 'WPSC_WOO_INSTALLING' ) ) {
				return;
			}

			add_action( 'admin_init', array( __CLASS__, 'plugin_updator' ) );

			add_action(
				'before_woocommerce_init',
				function() {
					if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
						\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
					}
				}
			);

		}

		/**
		 * Defines global constants that can be availabel anywhere in WordPress
		 *
		 * @return void
		 */
		public static function define_constants() {

			self::define( 'WPSC_WOO_PLUGIN_FILE', __FILE__ );
			self::define( 'WPSC_WOO_ABSPATH', dirname( __FILE__ ) . '/' );
			self::define( 'WPSC_WOO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			self::define( 'WPSC_WOO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			self::define( 'WPSC_WOO_VERSION', self::$version );
			self::define( 'WPSC_WOO_STORE_ID', 5397 );
		}

		/**
		 * Loads internationalization strings
		 *
		 * @return void
		 */
		public static function load_textdomain() {

			$locale = apply_filters( 'plugin_locale', get_locale(), 'wpsc-woo' );
			load_textdomain( 'wpsc-woo', WP_LANG_DIR . '/supportcandy/wpsc-woo-' . $locale . '.mo' );
			load_plugin_textdomain( 'wpsc-woo', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n' );
		}

		/**
		 * Load all classes
		 *
		 * @return void
		 */
		private static function load_files() {

			// Load installation.
			include_once WPSC_WOO_ABSPATH . 'class-wpsc-woo-installation.php';

			// Return if installation is in progress.
			if ( defined( 'WPSC_DB_UPGRADING' ) || defined( 'WPSC_WOO_INSTALLING' ) ) {
				return;
			}

			// Load common classes.
			foreach ( glob( WPSC_WOO_ABSPATH . 'includes/*.php' ) as $filename ) {
				include_once $filename;
			}
		}

		/**
		 * Define constants
		 *
		 * @param string $name - name of global constant.
		 * @param string $value - value of constant.
		 * @return void
		 */
		private static function define( $name, $value ) {

			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Plugin updator
		 *
		 * @return void
		 */
		public static function plugin_updator() {

			$licenses = get_option( 'wpsc-licenses', array() );
			$license  = isset( $licenses['woo'] ) ? $licenses['woo'] : array();
			if ( $license ) {
				$edd_updater = new WPSC_EDD_SL_Plugin_Updater(
					WPSC_STORE_URL,
					__FILE__,
					array(
						'version' => WPSC_WOO_VERSION,
						'license' => $license['key'],
						'item_id' => WPSC_WOO_STORE_ID,
						'author'  => 'Pradeep Makone',
						'url'     => home_url(),
					)
				);
			}
		}
	}
endif;

WPSC_WOO::init();
