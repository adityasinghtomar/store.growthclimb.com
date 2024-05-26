<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WOO_Frontend' ) ) :

	final class WPSC_WOO_Frontend {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// scripts and styles.
			add_action( 'wpsc_js_frontend', array( __CLASS__, 'frontend_scripts' ) );
			add_action( 'wpsc_css_frontend', array( __CLASS__, 'frontend_styles' ) );

			// woo my account.
			add_filter( 'woocommerce_my_account_my_orders_actions', array( __CLASS__, 'order_support_button' ), 10, 2 );

			// product tab.
			add_action( 'init', array( __CLASS__, 'woocommerce_add_support_ticket_endpoint' ) );
			add_filter( 'query_vars', array( __CLASS__, 'add_custom_query_var' ), 0 );
			add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'wpsc_account_menu_items' ), 10, 1 );
			add_action( 'woocommerce_account_support-ticket_endpoint', array( __CLASS__, 'wpsc_endpoint_content' ) );

			// woo dashboard as ticket url.
			add_filter( 'wpsc_get_ticket_url', array( __CLASS__, 'ticket_url' ), 10, 2 );
			add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'product_help_button' ) );
			add_action( 'woocommerce_product_tabs', array( __CLASS__, 'woo_custom_product_tabs' ) );

			// write custom rules.
			add_action( 'wp_loaded', array( __CLASS__, 'my_custom_flush_rewrite_rules' ) );
		}

		/**
		 * Add help tab next to 'review' on product page.
		 *
		 * @param array $tabs - tabs.
		 * @return array
		 */
		public static function woo_custom_product_tabs( $tabs ) {

			$woo_settings = get_option( 'wpsc-woo-settings' );
			if ( $woo_settings['product-help-tab'] ) {

				$tabs['product_help_tab'] = array(
					'title'    => $woo_settings['product-help-tab-label'],
					'priority' => 110,
					'callback' => array( __CLASS__, 'woo_custom_product_content' ),
				);
			}
			return $tabs;
		}

		/**
		 * Add shortcode in woo product account
		 *
		 * @return void
		 */
		public static function woo_custom_product_content() {

			global $product;
			set_transient( 'wpsc_temp_product_id', $product->get_id(), 60 );
			echo do_shortcode( '[wpsc_create_ticket]' );

		}

		/**
		 * Frontend scripts
		 *
		 * @return void
		 */
		public static function frontend_scripts() {

			echo file_get_contents( WPSC_WOO_ABSPATH . 'asset/js/public.js' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
		}

		/**
		 * Frontend styles
		 *
		 * @return void
		 */
		public static function frontend_styles() {

			if ( is_rtl() ) {
				echo file_get_contents( WPSC_WOO_ABSPATH . 'asset/css/public-rtl.css' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
			} else {
				echo file_get_contents( WPSC_WOO_ABSPATH . 'asset/css/public.css' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
			}
		}

		/**
		 * Order Help
		 *
		 * @param array     $actions - actions.
		 * @param Woo_Order $order - order.
		 * @return array
		 */
		public static function order_support_button( $actions, $order ) {

			$woo_settings = get_option( 'wpsc-woo-settings' );

			if ( $woo_settings['order-help-button'] ) {

				$page_settings = get_option( 'wpsc-gs-page-settings' );

				if ( $woo_settings['dashboard-support-tab'] && $woo_settings['dashboard-as-ticket-url'] ) {

					$url = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) . 'support-ticket/?wpsc-section=new-ticket&woo-order=' . $order->get_id();

				} elseif ( $page_settings['new-ticket-page'] == 'default' && $page_settings['support-page'] ) {

					$url = add_query_arg( array( 'woo-order' => $order->get_id() ), WPSC_Functions::get_new_ticket_url() );

				} elseif ( $page_settings['new-ticket-page'] == 'custom' && $page_settings['new-ticket-url'] ) {

					$url = add_query_arg( array( 'woo-order' => $order->get_id() ), $page_settings['new-ticket-url'] );

				}

				$actions[ $woo_settings['order-help-button-label'] ] = array(
					'url'  => $url,
					'name' => WPSC_Translations::get(
						'wpsc-woo-order-help-button-label',
						stripslashes( $woo_settings['order-help-button-label'] )
					),
				);
			}
			return $actions;
		}

		/**
		 * Woocoomerce add ticket endpoint
		 *
		 * @return void
		 */
		public static function woocommerce_add_support_ticket_endpoint() {

			add_rewrite_endpoint( 'support-ticket', EP_ROOT | EP_PAGES );
		}

		/**
		 * Add query variable
		 *
		 * @param array $vars - query variables .
		 * @return array
		 */
		public static function add_custom_query_var( $vars ) {

			$vars[] = 'support-ticket';
			return $vars;
		}

		/**
		 * Woo account menu items
		 *
		 * @param array $items - menu items.
		 * @return array
		 */
		public static function wpsc_account_menu_items( $items ) {

			$woo_settings = get_option( 'wpsc-woo-settings' );

			if ( $woo_settings['dashboard-support-tab'] ) :
				$new = array(
					'support-ticket' => WPSC_Translations::get(
						'wpsc-woo-dashboard-tab-label',
						stripslashes( $woo_settings['dashboard-support-tab-label'] )
					),
				);
				$items = array_slice( $items, 0, count( $items ) - 1, true ) + $new + array_slice( $items, count( $items ) - 1, null, true );
			endif;
			return $items;
		}

		/**
		 * Add shortcode in woo account
		 *
		 * @return void
		 */
		public static function wpsc_endpoint_content() {

			echo do_shortcode( '[supportcandy]' );
		}

		/**
		 * Change ticket url
		 *
		 * @param array       $ticket_url - ticket url.
		 * @param WPSC_ticket $ticket - ticket object.
		 * @return url
		 */
		public static function ticket_url( $ticket_url, $ticket ) {

			$woo_settings = get_option( 'wpsc-woo-settings' );
			if ( $woo_settings['dashboard-as-ticket-url'] ) {
				$ticket_url = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) . 'support-ticket/?wpsc-section=ticket-list&ticket-id=' . $ticket->id;
			}
			return $ticket_url;
		}

		/**
		 * Custom rule for flush buffer
		 *
		 * @return void
		 */
		public static function my_custom_flush_rewrite_rules() {

			flush_rewrite_rules();
		}

		/**
		 * Add help button next to 'add to cart' on product page.
		 *
		 * @return void
		 */
		public static function product_help_button() {

			global $product;
			$woo_settings = get_option( 'wpsc-woo-settings' );

			if ( $woo_settings['product-help-button'] ) {

				$page_settings = get_option( 'wpsc-gs-page-settings' );

				if ( $page_settings['new-ticket-page'] == 'default' && $page_settings['support-page'] ) {

					$url = add_query_arg( array( 'woo-product' => $product->get_id() ), WPSC_Functions::get_new_ticket_url() );
				} elseif ( $page_settings['new-ticket-page'] == 'custom' && $page_settings['new-ticket-url'] ) {

					$url = add_query_arg( array( 'woo-product' => $product->get_id() ), $page_settings['new-ticket-url'] );
				}
				echo '<button class="button wpsc-product-help" onclick="wpsc_help_button(event, \'' . esc_url( $url ) . '\')">' . esc_attr( $woo_settings['product-help-button-label'] ) . '</button>';
			}
			?>
			<script>
				function wpsc_help_button(e, url){
					e.preventDefault();
					location.href = url;
				}
			</script>
			<?php
		}

	}
endif;

WPSC_WOO_Frontend::init();
