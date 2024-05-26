<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WOO_Installation' ) ) :

	final class WPSC_WOO_Installation {

		/**
		 * Currently installed version
		 *
		 * @var integer
		 */
		public static $current_version;

		/**
		 * For checking whether upgrade available or not
		 *
		 * @var boolean
		 */
		public static $is_upgrade = false;

		/**
		 * Initialize installation
		 */
		public static function init() {

			self::get_current_version();
			self::check_upgrade();

			// db upgrade addon installer hook.
			add_action( 'wpsc_upgrade_install_addons', array( __CLASS__, 'upgrade_install' ) );

			// Database upgrade is in progress.
			if ( defined( 'WPSC_DB_UPGRADING' ) ) {
				return;
			}

			if ( self::$is_upgrade ) {

				define( 'WPSC_WOO_INSTALLING', true );

				// Do not allow parallel process to run.
				if ( 'yes' === get_transient( 'wpsc_woo_installing' ) ) {
					return;
				}

				// Set transient.
				set_transient( 'wpsc_woo_installing', 'yes', MINUTE_IN_SECONDS * 10 );

				// Run installation.
				if ( self::$current_version == 0 ) {

					add_action( 'init', array( __CLASS__, 'initial_setup' ), 1 );
					add_action( 'init', array( __CLASS__, 'set_upgrade_complete' ), 1 );

				} else {

					add_action( 'init', array( __CLASS__, 'upgrade' ), 1 );
				}

				// Delete transient.
				delete_transient( 'wpsc_woo_installing' );
			}

			// activation functionality.
			register_activation_hook( WPSC_WOO_PLUGIN_FILE, array( __CLASS__, 'activate' ) );

			// Deactivate functionality.
			register_deactivation_hook( WPSC_WOO_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
		}

		/**
		 * Check version
		 */
		public static function get_current_version() {

			self::$current_version = get_option( 'wpsc_woo_com_current_version', 0 );
		}

		/**
		 * Check for upgrade
		 */
		public static function check_upgrade() {

			if ( self::$current_version != WPSC_WOO_VERSION ) {
				self::$is_upgrade = true;
			}
		}

		/**
		 * DB upgrade addon installer hook callback
		 *
		 * @return void
		 */
		public static function upgrade_install() {

			self::initial_setup();
			self::set_upgrade_complete();
		}

		/**
		 * First time installation
		 */
		public static function initial_setup() {

			$string_translations = get_option( 'wpsc-string-translation' );

			// settings.
			$tab_label = esc_attr__( 'Support Tickets', 'wpsc-woo' );
			$help_label = esc_attr__( 'Help', 'wpsc-woo' );
			$product_help_label = esc_attr__( 'Help', 'wpsc-woo' );
			$product_help_tab_label = esc_attr__( 'Help', 'wpsc-woo' );
			update_option(
				'wpsc-woo-settings',
				array(
					'dashboard-support-tab'       => 1,
					'dashboard-support-tab-label' => $tab_label,
					'order-help-button'           => 1,
					'order-help-button-label'     => $help_label,
					'product-help-button'         => 1,
					'product-help-button-label'   => $product_help_label,
					'product-help-tab'            => 1,
					'product-help-tab-label'      => $product_help_tab_label,
					'dashboard-as-ticket-url'     => 0,
				)
			);

			$string_translations['wpsc-woo-dashboard-tab-label'] = $tab_label;
			$string_translations['wpsc-woo-order-help-button-label'] = $help_label;
			$string_translations['wpsc-woo-product-help-button-label'] = $product_help_label;
			$string_translations['wpsc-woo-product-help-tab-label'] = $product_help_tab_label;

			// update string translations.
			update_option( 'wpsc-string-translation', $string_translations );

			// install widget.
			self::install_widget();
		}

		/**
		 * Upgrade the version
		 */
		public static function upgrade() {

			if ( version_compare( self::$current_version, '3.0.4', '<' ) ) {

				$setting = get_option( 'wpsc-woo-settings' );
				$setting['product-help-button'] = 1;
				$setting['product-help-button-label'] = esc_attr__( 'Help', 'wpsc-woo' );
				update_option( 'wpsc-woo-settings', $setting );
			}

			if ( version_compare( self::$current_version, '3.1.1', '<' ) ) {

				$string_translations = get_option( 'wpsc-string-translation' );
				$product_help_tab_label = esc_attr__( 'Help', 'wpsc-woo' );

				$setting = get_option( 'wpsc-woo-settings' );
				$setting['product-help-tab'] = 1;
				$setting['product-help-tab-label'] = $product_help_tab_label;
				update_option( 'wpsc-woo-settings', $setting );

				$string_translations['wpsc-woo-product-help-tab-label'] = $product_help_tab_label;
				update_option( 'wpsc-string-translation', $string_translations );
			}

			self::set_upgrade_complete();
		}

		/**
		 * Mark upgrade as complete
		 */
		public static function set_upgrade_complete() {

			update_option( 'wpsc_woo_com_current_version', WPSC_WOO_VERSION );
			self::$current_version = WPSC_WOO_VERSION;
			self::$is_upgrade      = false;
		}

		/**
		 * Actions to perform after plugin activated
		 *
		 * @return void
		 */
		public static function activate() {

			// Widget might not be installed as a result of race condition while upgrade.
			// There is an option for administrator to deactivate and then activate the plugin.
			self::install_widget();
			do_action( 'wpsc_woo_activate' );
		}

		/**
		 * Actions to perform after plugin deactivated
		 *
		 * @return void
		 */
		public static function deactivate() {

			do_action( 'wpsc_woo_deactivate' );
		}

		/**
		 * Install widget if not already installed
		 *
		 * @return void
		 */
		public static function install_widget() {

			$widgets = get_option( 'wpsc-ticket-widget', array() );
			$string_translations = get_option( 'wpsc-string-translation' );

			// Orders widget.
			if ( ! isset( $widgets['woo-order'] ) ) {

				$agent_roles = array_keys( get_option( 'wpsc-agent-roles', array() ) );
				$label = esc_attr( wpsc__( 'WooCommerce', 'woocommerce' ) );
				$widgets['woo-order'] = array(
					'title'               => $label,
					'is_enable'           => 1,
					'allow-customer'      => 0,
					'allowed-agent-roles' => $agent_roles,
					'callback'            => 'wpsc_get_tw_woo_order()',
					'class'               => 'WPSC_ITW_WOO',
				);
				update_option( 'wpsc-ticket-widget', $widgets );
				$string_translations['wpsc-twt-woo-order'] = $label;
			}

			// Subscriptions widget.
			if ( ! isset( $widgets['woo-sub'] ) ) {

				$agent_roles = array_keys( get_option( 'wpsc-agent-roles', array() ) );
				$label = esc_attr__( 'Woo Subscriptions', 'wpsc-woo' );
				$widgets['woo-sub'] = array(
					'title'               => $label,
					'is_enable'           => 1,
					'allow-customer'      => 0,
					'allowed-agent-roles' => $agent_roles,
					'callback'            => 'wpsc_get_tw_woo_subscription()',
					'class'               => 'WPSC_ITW_WOO_Subscription',
				);
				update_option( 'wpsc-ticket-widget', $widgets );
				$string_translations['wpsc-twt-woo-sub'] = $label;
			}

			update_option( 'wpsc-string-translation', $string_translations );
		}
	}
endif;

WPSC_WOO_Installation::init();
