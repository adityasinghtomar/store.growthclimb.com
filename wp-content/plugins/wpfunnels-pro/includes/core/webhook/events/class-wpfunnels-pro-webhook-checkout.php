<?php

namespace WPFunnelsProWebHooks\Events;

use WPFunnelsProWebHooks\Functions\Wpfnl_Pro_Webhook_Functions;
use WPFunnelsPro\Wpfnl_Pro_functions;

class Wpfnl_Pro_Webhook_Checkout {

	protected $settings;

	protected $order_id;

	/**
	 * Constructs an instance of the OrderProcessor class.
	 *
	 * Initializes a new OrderProcessor object with the provided settings and order ID.
	 *
	 * @param array $settings An array of settings or configuration parameters for order processing.
	 * @param int $order_id The unique identifier of the order to be processed.
	 * @since 1.9.12
	 */
	public function __construct( $settings, $order_id ) {
		$this->settings = $settings;
		$this->order_id = $order_id;
	}

	/**
	 * Send data to a specified webhook URL using an HTTP request.
	 *
	 * This method constructs an HTTP request to send data to the configured webhook URL.
	 *
	 * @return \WP_Error|array The response from the HTTP request, or a WP_Error object on failure.
	 * @since 1.9.12
	 */
	public function send_data() {
		$request_url  = Wpfnl_Pro_Webhook_Functions::get_request_url( $this->settings );
		$request_args = $this->prepare_request_args();
		return wp_remote_request( $request_url, $request_args );
	}

	/**
	 * Get event name
	 *
	 * @return String
	 * @since 1.9.12
	 */
	public function get_event_name(){
		return 'Checkout Order Placed';
	}

	/**
	 * Prepare the request arguments for an HTTP request to the webhook URL.
	 *
	 * This method constructs the request body, content type, and other common request arguments
	 * required for sending data to the webhook.
	 *
	 * @return array An array of request arguments to be used in the HTTP request.
	 * @since 1.9.12
	 */
	public function prepare_request_args(){
		$request_body = $this->prepare_request_body();
		return Wpfnl_Pro_functions::prepare_common_request_args( $this->settings, 'application/json', $request_body, $this->get_event_name(), home_url( '/' ) );
	}

	/**
	 * Prepare the request body data to be sent in the webhook request.
	 *
	 * This method constructs the request body based on the specified type and selected fields.
	 * It formats the body data to match the expected format for the webhook request.
	 *
	 * @return array An array representing the formatted request body data.
	 * @since 1.9.12
	 */
	public function prepare_request_body() {
		$type = $this->settings[ 'request' ][ 'body' ][ 'type' ] ?? 'all';
		if ( 'selected' === $type ) {
			return $this->prepare_selected_body_fields();
		}
		return $this->prepare_all_body_fields();
	}

	/**
	 * Prepare all available body fields for the webhook request.
	 *
	 * This method retrieves various order-related data and formats it into an array.
	 *
	 * @return array An array containing formatted order-related data for the webhook request.
	 * @since 1.9.12
	 */
	public function prepare_all_body_fields() {
		// Get the order object based on the provided order ID.
		$order = wc_get_order($this->order_id);

		// Initialize a formatted body array with default values.
		$formatted_body = [
			'Date'             => date( "Y-m-d h:i" ),
			'Event Name'       => $this->get_event_name(),
			'Order ID'         => $this->order_id,
			'Order Status'     => $order->get_status(),
			'Order Total'      => $order->get_total(),
			'Payment Method'   => $order->get_payment_method(),
			'Shipping Method'  => $order->get_shipping_method(),
			'Customer Name'    => $order->get_formatted_billing_full_name() ? $order->get_formatted_billing_full_name() : $order->get_formatted_shipping_full_name(),
			'Customer Phone'   => $order->get_billing_phone() ? $order->get_billing_phone() : '',
			'Customer Email'   => $order->get_billing_email() ? $order->get_billing_email() : '',
			'Customer City'    => $order->get_billing_city() ? strip_tags( $order->get_billing_city() ) : strip_tags( $order->get_shipping_city() ),
			'Customer Address' => $order->get_billing_address_1() ? strip_tags( $order->get_billing_address_1() ) : strip_tags( $order->get_shipping_address_1() ),
		];

		foreach( $order->get_items() as $item ) {
			$formatted_body[ 'Items' ][] = [
				'Product ID'       => $item->get_id(),
				'Product Name'     => $item->get_name(),
				'Product Quantity' => $item->get_quantity()
			];
		}

		return $formatted_body;
	}

	/**
	 * Prepare selected body fields for the webhook request based on user configuration.
	 *
	 * This method retrieves specific order-related data fields as configured by the user and formats them into an array.
	 *
	 * @return array An array containing formatted selected order-related data for the webhook request.
	 * @since 1.9.12
	 */
	public function prepare_selected_body_fields() {
		$order           = wc_get_order( $this->order_id );
		$selected_fields = $this->settings[ 'request' ][ 'body' ][ 'values' ] ?? [];
		$formatted_body  = [
			'Date'       => date( "Y-m-d h:i" ),
			'Event Name' => $this->get_event_name()
		];

		if ( is_array( $selected_fields ) && !empty( $selected_fields ) ) {
			foreach( $selected_fields as $field ) {
				if ( isset( $field[ 'value' ], $field[ 'key' ] ) ) {
					switch( $field[ 'value' ] ) {
						case 'order_id':
							$formatted_body[ $field[ 'key' ] ] = $this->order_id;
							break;
						case 'order_total':
							$formatted_body[ $field[ 'key' ] ] = $order->get_total();
							break;
						case 'order_status':
							$formatted_body[ $field[ 'key' ] ] = $order->get_status();
							break;
						case 'payment_method':
							$formatted_body[ $field[ 'key' ] ] = $order->get_payment_method();
							break;
						case 'shipping_method':
							$formatted_body[ $field[ 'key' ] ] = $order->get_shipping_method();
							break;
						case 'customer_name':
							$formatted_body[ $field[ 'key' ] ] = $order->get_formatted_billing_full_name() ? $order->get_formatted_billing_full_name() : $order->get_formatted_shipping_full_name();
							break;
						case 'customer_phone':
							$formatted_body[ $field[ 'key' ] ] = $order->get_billing_phone() ?? '';
							break;
						case 'customer_email':
							$formatted_body[ $field[ 'key' ] ] = $order->get_billing_email() ?? '';
							break;
						case 'customer_city':
							$formatted_body[ $field[ 'key' ] ] = $order->get_billing_city() ? strip_tags( $order->get_billing_city() ) : strip_tags( $order->get_shipping_city() );
							break;
						case 'customer_address':
							$formatted_body[ $field[ 'key' ] ] = $order->get_billing_address_1() ? strip_tags( $order->get_billing_address_1() ) : strip_tags( $order->get_shipping_address_1() );
							break;
						case 'product_name':
							$index = 0;
							foreach( $order->get_items() as $item ) {
								$formatted_body[ 'Items' ][ $index++ ][ $field[ 'key' ] ] = $item->get_name();
							}
							break;
						case 'product_id':
							$index = 0;
							foreach( $order->get_items() as $item ) {
								$formatted_body[ 'Items' ][ $index++ ][ $field[ 'key' ] ] = $item->get_id();
							}
							break;
						case 'product_quantity':
							$index = 0;
							foreach( $order->get_items() as $item ) {
								$formatted_body[ 'Items' ][ $index++ ][ $field[ 'key' ] ] = $item->get_quantity();
							}
							break;
						default:
							break;
					}
				}
			}
		}
		return $formatted_body;
	}
}