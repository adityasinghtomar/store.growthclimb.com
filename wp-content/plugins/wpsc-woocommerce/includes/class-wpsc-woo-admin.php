<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WOO_Admin' ) ) :

	final class WPSC_WOO_Admin {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			add_action( 'wpsc_js_backend', array( __CLASS__, 'backend_scripts' ) );

			// add meta box in order.
			add_action( 'add_meta_boxes', array( __CLASS__, 'mv_add_meta_boxes' ) );
		}

		/**
		 * Backend scripts
		 *
		 * @return void
		 */
		public static function backend_scripts() {

			echo file_get_contents( WPSC_WOO_ABSPATH . 'asset/js/admin.js' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
		}

		/**
		 * Add meta box in
		 *
		 * @return void
		 */
		public static function mv_add_meta_boxes() {

			add_meta_box( 'mv_other_fields', 'SupportCandy', 'wpsc_create_ticket_from_order', 'shop_order', 'side', 'core' );

			/**
			 * Create ticket from order
			 *
			 * @return void
			 */
			function wpsc_create_ticket_from_order() {

				global $post;

				$order          = wc_get_order( $post->ID );
				$customer_id    = $order->get_customer_id();
				$customer       = new WC_Customer( $customer_id );

				$email      = $customer->get_email();
				$firstname  = $customer->get_first_name();
				$lastname   = $customer->get_last_name();
				$name       = $lastname ? $firstname . ' ' . $lastname : $customer->get_display_name();

				$new_ticket_url = admin_url( 'admin.php?page=wpsc-tickets&section=new-ticket&woo-order=' . $order->get_id() . '&create_as_email=' . $email . '&create_as_name=' . $name . '' );
				echo '<a href="' . esc_url( $new_ticket_url ) . '" target="_blank" type="button" id="wpsc-woo-order-create-ticket" class="button save_order button-primary">' . esc_attr__( 'Create Ticket', 'wpsc-woo' ) . '</a>';
			}
		}
	}
endif;

WPSC_WOO_Admin::init();
