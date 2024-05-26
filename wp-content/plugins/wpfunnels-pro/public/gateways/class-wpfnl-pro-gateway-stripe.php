<?php

namespace WPFunnelsPro\Frontend\Gateways;

use WC_Stripe_API;
use WC_Stripe_Helper;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;

class Wpfnl_Stripe_payment_process {

    public $key = 'stripe';

    public $refund_support;

    public function __construct() {
        $this->refund_support = true;

        add_filter( 'wc_stripe_force_save_source', array( $this, 'should_tokenize_stripe' ), 9999);
        add_filter( 'wc_stripe_3ds_source', array( $this, 'may_be_modify_3ds_param' ), 9999, 2);
        add_action( 'wc_gateway_stripe_process_response', array( $this, 'handle_redirection' ), 9999, 2 );

        add_action( 'wp_ajax_wpfunnels_stripe_sca_check', array( $this, 'check_stripe_sca' ),9999);
        add_action( 'wp_ajax_nopriv_wpfunnels_stripe_sca_check', array( $this, 'check_stripe_sca' ),9999);

        add_action( 'wpfunnels/child_order_created_' . $this->key, array( $this, 'add_capture_meta_to_child_order' ), 9999, 3 );

        add_action( 'wpfunnels/subscription_created', array( $this, 'add_offer_subscription_meta' ), 9999, 3 );

    }


    /** 
     * wpfnl_stripe_tokenization
     * If required then tokenize to save source of payment   
     *  
     * @param bool $save_source force save source.
     * 
    */
    public function should_tokenize_stripe( $save_source )
    {
        $checkout_id = Wpfnl_functions::get_checkout_id_from_post_data();
        $funnel_id   = Wpfnl_functions::get_funnel_id_from_step( $checkout_id );
        
        if ( $checkout_id && $funnel_id ) {

            if ( Wpfnl_Pro_functions::is_offer_exists_in_funnel($funnel_id) ) {
                $save_source = true;
            }
        }
       
        return $save_source;
    }


    /**
     * @param $funnel_id
     * @param $node_found
     * @return bool
     */
    public function go_to_output_1($funnel_id, $node_found) {
        $funnel_json = get_post_meta($funnel_id, '_funnel_data', true);
        if ($funnel_json) {
            $node_data = $funnel_json['drawflow']['Home']['data'];
            foreach ($node_data as $node_key => $node_value) {
                if ($node_value['id'] == $node_found) {
                    $next_node = $node_value['outputs']['output_1']['connections'][0]['node'];
                    return $next_node;
                }
            }
            return false;
        }
    }


    /**
     * @param $funnel_id
     * @param $node_found
     * @return bool
     */
    public function go_to_output_2($funnel_id, $node_found) {
        $funnel_json = get_post_meta($funnel_id, '_funnel_data', true);
        if ($funnel_json) {
            $node_data = $funnel_json['drawflow']['Home']['data'];
            foreach ($node_data as $node_key => $node_value) {
                if ($node_value['id'] == $node_found) {
                    $next_node = $node_value['outputs']['output_2']['connections'][0]['node'];
                    return $next_node;
                }
            }
            return false;
        }
    }


    /**
     * save 3ds source data for offers
     *
     * @param $post_data
     * @param $order
     * @return mixed
     */
    public function may_be_modify_3ds_param( $post_data, $order ) {
        if ( $order && Wpfnl_Pro_functions::check_if_offer_exists($order) ) {
            $order->update_meta_data( '_wpfunnels_stripe_source_id', $post_data['three_d_secure']['card'] );
            $order->save();
        }
        return $post_data;
    }


    /**
     * Redirection to order received url
     *
     * @param $response
     * @param $order
     */
    public function handle_redirection( $response, $order ) {
        if ( 1 === did_action( 'wpfunnels/offer_funnel_started' ) && 1 === did_action( 'wc_gateway_stripe_process_redirect_payment' ) ) {
            $order_received_url = $order->get_checkout_order_received_url();
            wp_safe_redirect( $order_received_url );
            exit();
        }
    }

    /**
     * wpfnl_stripe_maybe_hide_save_payment
     */
    public function wpfnl_stripe_maybe_hide_save_payment( $is_show ) {
		return $is_show;
	}


    /**
     * wpfnl_stripe_verify_sca
     * Verify if payment type is SCA or not
     *
     * @throws \WC_Stripe_Exception
     */
    public function check_stripe_sca() {
        $security = filter_input( INPUT_POST, 'security', FILTER_SANITIZE_STRING );
        if ( ! wp_verify_nonce( $security, 'wpfnl_stripe_sca_check_nonce' ) ) {
            return;
        }

        global $woocommerce;

        $step_id       = isset( $_POST['step_id'] ) ? intval( $_POST['step_id'] ) : 0;
        $order_id      = isset( $_POST['order_id'] ) ? intval($_POST['order_id']) : 0;
        $offer_type    = isset( $_POST['offer_type'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_type'] ) ) : '';
        $offer_action  = isset( $_POST['offer_action'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_action'] ) ) : '';
        $product_id    = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : '';
        
        if( !empty($_POST['attr']) && Wpfnl_functions::is_wc_active() ){
            $variation_id = (new \WC_Product_Data_Store_CPT())->find_matching_product_variation(
                new \WC_Product($product_id),
                $_POST['attr']
            );
            if( $variation_id ){
                $product_id = $variation_id;
            }
        }
        $quantity      = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : '';
        $order         = wc_get_order( $order_id );
        $offer_product = Wpfnl_Pro_functions::get_offer_product_data( $step_id, $product_id, 0, $order_id );
        if ( isset($offer_product['price']) && (floatval(0) === floatval( $offer_product['price'] ) || '' === trim($offer_product['price'])) ) {
            wp_send_json(array(
                'result'    => 'fail',
                'message'   => __('Product price is less than 0', 'wpfnl-pro'),
            ));
        } else {
            $gateways   = $woocommerce->payment_gateways->payment_gateways();
            $gateway    = $gateways['stripe'];
            if ( $gateway ) {
                $order_source   = $gateway->prepare_order_source($order);

                $is_3ds         = isset($order_source->source_object->card->three_d_secure) ? $order_source->source_object->card->three_d_secure : false;

                $_3ds_array = [
                    'optional',
                    'not_supported',
                ];

                // check if 3ds is active or not
                if ( isset($is_3ds) && !in_array( $is_3ds,$_3ds_array ) ) {
                    
                    $intent         = $this->create_intent($order, $order_source, $offer_product);
                   
                    $main_settings  = get_option('woocommerce_stripe_settings');
                    $testmode       = (!empty($main_settings['testmode']) && 'yes' === $main_settings['testmode']) ? true : false;
                    if ($testmode) {
                        $publishable_key = !empty($main_settings['test_publishable_key']) ? $main_settings['test_publishable_key'] : '';
                    } else {
                        $publishable_key = !empty($main_settings['publishable_key']) ? $main_settings['publishable_key'] : '';
                    }

                    if ( $order ) {
                        $order->update_meta_data( '_stripe_intent_id_' . $step_id, $intent->id );
                        $order->save();
                    }

                    wp_send_json(array(
                        'result'        => 'success',
                        'redirect'      => $gateway->get_return_url( $order ),
                        'intent_secret' => $intent->client_secret,
                        'stripe_pk'     => $publishable_key,
                    ));
                    
                }
            }

            wp_send_json(array(
                'result' => 'fail',
                'message' => 'No 3ds payment',
            ));
        }
    }


    /**
     * @param $order
     * @param $order_source
     * @param $product
     * @return array|\stdClass
     * @throws \WC_Stripe_Exception
     */
    public function create_intent($order, $order_source, $product)
    {
        // The request for a charge contains metadata for the intent.
        $full_request = $this->generate_payment_request($order, $order_source, $product);
        $request = [
          
            'amount' => \WC_Stripe_Helper::get_stripe_amount($product['price']),
            'currency' => strtolower($order->get_currency()),
            'description' => $full_request['description'],
            'metadata' => $full_request['metadata'],
            'statement_descriptor' => \WC_Stripe_Helper::clean_statement_descriptor($full_request['statement_descriptor']),
            'capture_method' => ('true' === $full_request['capture']) ? 'automatic' : 'manual',
            'payment_method_types' => [
                'card',
            ],
            'customer'             => $order_source->customer,
        ];
        $request = \WC_Stripe_Helper::add_payment_method_to_request_array( $order_source->source, $request );

        if ($order_source->customer) {
            $request['customer'] = $order_source->customer;
        }
        // Create an intent that awaits an action.
        $intent = \WC_Stripe_API::request( $request, 'payment_intents' );

        if (!empty($intent->error)) {
            $intent_id = $order->get_meta('_stripe_intent_id');
            if ( $intent_id ) {
                $intent =  $this->get_intent( 'payment_intents', $intent_id );
                if( !empty($intent->error) ){
                    return $intent;
                }
            }
        }

        $order_id = $order->get_id();

        $step_id = filter_input(INPUT_POST, 'step_id', FILTER_VALIDATE_INT);
        // Save the intent ID to the order.
        update_post_meta($order_id, '_stripe_intent_id_' . $step_id, $intent->id);
        
        return $intent;
    }

    


    /**
	 * Retrieves intent from Stripe API by intent id.
	 *
	 * @param string $intent_type   Either 'payment_intents' or 'setup_intents'.
	 * @param string $intent_id     Intent id.
	 * @return object|bool          Either the intent object or `false`.
	 * @throws Exception            Throws exception for unknown $intent_type.
	 */
	public function get_intent( $intent_type, $intent_id ) {
		if ( ! in_array( $intent_type, [ 'payment_intents', 'setup_intents' ], true ) ) {
			throw new \Exception( "Failed to get intent of type $intent_type. Type is not allowed" );
		}

		$response = \WC_Stripe_API::request( [], "$intent_type/$intent_id?expand[]=payment_method", 'GET' );

		if ( $response && isset( $response->{ 'error' } ) ) {
			return false;
		}

		return $response;
	}


    /**
     * check if token is present in the order
     *
     * @param $order
     */
    private function has_token( $order ) {
        if( false === is_a( $order, 'WC_Order' ) ){
            return false;
        }
        $token      = $order->get_meta('_wpfunnels_stripe_source_id');
        if ( empty( $token ) ) {
            $token = $order->get_meta('_stripe_source_id');
        }
        if ( ! empty( $token ) ) {
            return true;
        }
        return false;
    }

    /**
     * process the offer payment
     *
     * @param $order
     * @param $offer_product
     * @return bool
     * @throws \WC_Stripe_Exception
     */
    public function process_payment( $order, $offer_product ) {
        $result = array(
            'is_success' => false,
            'message' => ''
        );
        if ( ! $this->has_token( $order ) ) {
            return $result;
        }
        $gateway = $this->get_wc_gateway();
        $order_source = $gateway->prepare_order_source( $order );
        $response = \WC_Stripe_API::request( $this->generate_payment_request( $order, $order_source, $offer_product ) );
        
        if ( ! is_wp_error( $response ) ) {
            if ( ! empty( $response->error ) ) {
                $result['message'] = $response->error->message;
            } else {
                $result['is_success'] = true;
                $this->update_stripe_payout_details( $order, $response );
                $this->store_offer_transaction( $order, $response, $offer_product );
            }
        }
        return $result;
    }

    /**
     * generate payment request post data
     *
     * @param $order
     * @param $order_source
     * @param $product
     * @return mixed|void
     */
    public function generate_payment_request($order, $order_source, $product) {
        global $woocommerce;
        $gateways = $woocommerce->payment_gateways->payment_gateways();
        $gateway = $gateways['stripe'];
        $post_data = [];
        $post_data['currency'] = strtolower($order ? $order->get_currency() : get_woocommerce_currency());
        $post_data['amount'] = \WC_Stripe_Helper::get_stripe_amount($product['price'], $post_data['currency']);
        /* translators: %1s site name */
        $post_data['description'] = sprintf(__('%1$s - Order %2$s - One Time offer', 'wpfnl'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), $order->get_order_number());

        /* translators: %1s order number */
        $post_data['statement_descriptor'] = apply_filters( 'wpfunnels/stripe_descriptor_text_modifiaction', sprintf(__('Order %1$s-OTO', 'wpfnl'), $order->get_order_number()), $order );
        $post_data['capture'] = $gateway->capture ? 'true' : 'false';
        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name = $order->get_billing_last_name();
        $billing_email = $order->get_billing_email();

        if (!empty($billing_email) && apply_filters('wc_stripe_send_stripe_receipt', false)) {
            $post_data['receipt_email'] = $billing_email;
        }

        $metadata = [
            __('customer_name', 'wpfnl') => sanitize_text_field($billing_first_name) . ' ' . sanitize_text_field($billing_last_name),
            __('customer_email', 'wpfnl') => sanitize_email($billing_email),
            'order_id' => apply_filters( 'wpfunnels/stripe_descriptor_text_modifiaction', sprintf(__('Order %1$s-OTO', 'wpfnl'), $order->get_order_number()), $order ) . '_' . $product['id'],
        ];

        $post_data['expand[]'] = 'balance_transaction';
        $post_data['metadata'] = apply_filters('wc_stripe_payment_metadata', $metadata, $order, $order_source);

        if ($order_source->customer) {
            $post_data['customer'] = $order_source->customer;
        }

        if ($order_source->source) {
            $source_3ds = $order->get_meta('_wpfunnels_stripe_source_id', true);

            $post_data['source'] = ('' !== $source_3ds) ? $source_3ds : $order_source->source;
        }
        $post_data['source'] = $order_source->source;
        return $post_data;
    }


    /**
     * @param $order
     * @param $response
     */
    public function update_stripe_payout_details($order, $response)
    {
        $fee = !empty($response->balance_transaction->fee) ? \WC_Stripe_Helper::format_balance_fee($response->balance_transaction, 'fee') : 0;
        $net = !empty($response->balance_transaction->net) ? \WC_Stripe_Helper::format_balance_fee($response->balance_transaction, 'net') : 0;

        $fee = $fee + \WC_Stripe_Helper::get_stripe_fee($order);
        $net = $net + \WC_Stripe_Helper::get_stripe_net($order);

        \WC_Stripe_Helper::update_stripe_fee($order, $fee);
        \WC_Stripe_Helper::update_stripe_net($order, $net);
    }


    /**
     * @param $order
     * @param $response
     * @param $product
     */
    public function store_offer_transaction( $order, $response, $product )
    {
        $order->update_meta_data('_wpfunnels_offer_txn_resp_' . $product['step_id'], $response->id);
        $order->save();
    }


    /**
     * create child order reference with the parent order
     *
     * @param $parent_order
     * @param $product_data
     * @param string $type
     * @return bool|\WC_Order|\WP_Error
     * @throws \WC_Data_Exception
     */
    public function create_child_order( $parent_order, $product_data, $type = 'upsell' )
    {
        $order = false;

        if (!empty($parent_order)) {
            $parent_order_id = $parent_order->get_id();
            $parent_order_billing = $parent_order->get_address('billing');
            $funnel_id = $parent_order->get_meta('_wpfunnels_funnel_id');

            if (!empty($parent_order_billing['email'])) {
                $customer_id = $parent_order->get_customer_id();

                $order = wc_create_order(
                    [
                        'customer_id' => $customer_id,
                        'status' => 'wc-pending',
                        'parent' => $parent_order_id,
                    ]
                );

                /* Set Order type */
                $order->update_meta_data('_wpfunnels_offer', 'yes' );
                $order->update_meta_data('_wpfunnels_offer_type', $type );
                $order->update_meta_data('_wpfunnels_parent_funnel_id', $funnel_id);
                $order->update_meta_data('_wpfunnels_offer_step_id', $product_data['step_id'] );
                $order->update_meta_data('_wpfunnels_offer_parent_id', $parent_order_id );

                $item_id = $order->add_product(wc_get_product($product_data['id']), $product_data['qty'], $product_data['args']);

                if( $item_id ){
                    wc_add_order_item_meta( $item_id, "_wpfunnels_{$type}", 'yes' );
                    wc_add_order_item_meta( $item_id, '_wpfunnels_step_id', $product_data['step_id']);
                }
                
                $order->set_address($parent_order->get_address('billing'), 'billing');
                $order->set_address($parent_order->get_address('shipping'), 'shipping');

                // Set shipping data.
                $order->set_payment_method($parent_order->get_payment_method());
                $order->set_payment_method_title($parent_order->get_payment_method_title());

                if (!wc_tax_enabled()) {
                    // Reports won't track orders fix.
                    $order->set_shipping_tax(0);
                    $order->set_cart_tax(0);
                }

                $order->calculate_totals();

                $offer_orders_meta = $parent_order->get_meta('_wpfunnels_offer_child_orders');

                if (!is_array($offer_orders_meta)) {
                    $offer_orders_meta = [];
                }

                $offer_orders_meta[$order->get_id()] = ['type' => $type];

                $parent_order->update_meta_data('_wpfunnels_offer_child_orders', $offer_orders_meta);

                // Save the order.
                $parent_order->save();
            }
        }

        if ($order) {
            $transaction_id = $parent_order->get_transaction_id();

            $this->payment_complete($order, $transaction_id);

            $order->set_transaction_id($transaction_id);
            $order->save();

            $transaction_id_note = '';

            if (!empty($transaction_id)) {
                $transaction_id_note = sprintf(' (Transaction ID: %s)', $transaction_id);
            }

            $order->add_order_note('Offer Accepted | ' . $type . ' | Step ID - ' . $product_data['step_id'] . ' | ' . $transaction_id_note);
            return $order;
        }
        return false;
    }


    /**
     * payment_complete
     * Complete the payment 
     * 
     * @param WC_Order $order             Parent order detail
     * @param String   $transaction_id    Transaction  id.
     * 
     */

    public function payment_complete($order, $transaction_id = '')
    {
        $payment_method = $order->get_payment_method();

        if ('cod' === $payment_method) {
            $order->set_status('processing');
            wc_reduce_stock_levels($order);
        } elseif ('bacs' === $payment_method) {
            $order->set_status('on-hold');
            wc_reduce_stock_levels($order);
        } else {
            $order->payment_complete($transaction_id);
        }
    }


    /**
     * Get WooCommerce payment geteways.
     *
     * @return array
     */
    public function get_wc_gateway() {

        global $woocommerce;
        $gateways = $woocommerce->payment_gateways->payment_gateways();

        return $gateways[ $this->key ];
    }


    /**
     * process refund offer
     *
     * @param $order
     * @param $data
     * @return bool
     * @throws \WC_Stripe_Exception
     */
    public function process_refund_offer( $order, $data ) {

        $transaction_id = $data['transaction_id'];
        $amount         = $data['amount'];
        $currency       = $order->get_currency( $order );

        $request     = array();
        $response_id = false;

        if ( ! is_null( $amount ) && class_exists( 'WC_Stripe_Helper' ) ) {
            $request['amount'] = WC_Stripe_Helper::get_stripe_amount( $amount, $currency );
        }
        if ( ! is_null( $amount ) && class_exists( 'WC_Stripe_API' ) ) {
            $request['charge'] = $transaction_id;
            $response          = WC_Stripe_API::request( $request, 'refunds' );
            if ( ! empty( $response->error ) || ! $response ) {
                $response_id = false;
            } else {
                $this->get_wc_gateway()->update_fees( $order, $response->balance_transaction );
                $response_id = isset( $response->id ) ? $response->id : true;
            }
        }
        return $response_id;
    }


    /**
     * add required meta for refund
     *
     * @param $parent_order
     * @param $child_order
     * @param $transaction_id
     */
    public function add_capture_meta_to_child_order( $parent_order, $child_order, $transaction_id ) {
        $child_order->update_meta_data('_stripe_charge_captured', 'yes' );
    }


    /**
     * add subscription offer meta to order
     *
     * @param $subscription
     * @param $offer_product
     * @param $order
     */
    public function add_offer_subscription_meta( $subscription, $offer_product, $order ) {
        if ( 'stripe' === $order->get_payment_method() ) {
            $subscription_id = $subscription->get_id();
            update_post_meta( $subscription_id, '_stripe_source_id', $order->get_meta( '_stripe_source_id', true ) );
            update_post_meta( $subscription_id, '_stripe_customer_id', $order->get_meta( '_stripe_customer_id', true ) );
        }
    }
}
