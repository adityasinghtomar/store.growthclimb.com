<?php
/**
 * Woostify Frequently Bought Together woocommerce
 *
 * @package Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_FBT' ) ) {
	/**
	 * Class for Woostify Frequently Bought Together WooCommerce.
	 */
	class Woostify_FBT {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->define_constants();
			$this->includes();
		}

		/**
		 * Define constant
		 */
		public function define_constants() {
			if ( ! defined( 'WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER' ) ) {
				define( 'WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER', WOOSTIFY_PRO_VERSION );
			}

			if ( ! defined( 'WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER_URL' ) ) {
				define( 'WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER_URL', WOOSTIFY_PRO_MODULES_URI . 'woocommerce/frequently-bought-together/' );
			}

			if ( ! defined( 'WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER_PATH' ) ) {
				define( 'WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER_PATH', WOOSTIFY_PRO_MODULES_PATH . 'woocommerce/frequently-bought-together/' );
			}
		}

		/**
		 * Include files.
		 */
		public function includes() {
			$classes = array( 'functions', 'settings', 'admin', 'frontend' );

			foreach ( $classes as $class ) {
				$file = WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER_PATH . "inc/class-woostify-fbt-{$class}.php";
				if ( file_exists( $file ) ) {
					require_once $file;
				}
			}
		}
	}

	new Woostify_FBT();
}
