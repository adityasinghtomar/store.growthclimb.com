<?php

namespace WPFunnelsPro\Tracking;

use WC_Order_Factory;
use WPFunnels\Compatibility\Wpfnl_Theme_Compatibility;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

/**
 * Google Tag Manager class for WPF Pro
 *
 * Class GTM
 * @package WPFunnelsPro\Tracking
 */
class GTM {

    use SingletonTrait;

    protected $settings;

    public function init_actions() {
        add_action( 'wp_head', array( $this, 'init' ), 100 );
    }


    public function init() {
        $this->settings = $this->get_settings();
        if( $this->is_gtm_enabled() ) {
            $this->add_gtm_header_script();
            add_action( 'wp_body_open', array( $this, 'add_gtm_body_script' ), 100 );
            add_action( 'wpfunnels/order_bump_accepted', array( $this, 'order_bump_accepted' ), 10, 2 );
            add_action( 'wpfunnels/funnel_order_placed', array($this, 'funnel_order_placed'), 10, 3 );
            add_action( 'wpfunnels/offer_accepted', array( $this, 'offer_accepted' ), 10, 2 );
            add_action( 'woocommerce_add_to_cart', array( $this, 'add_to_cart' ) );
        }
    }

    /**
     * Load dataLayer Events and GTM Snippets for <head></head>
     *
     * @return void
     */
    public function add_gtm_header_script() {

        $compatibility = Wpfnl_Theme_Compatibility::getInstance();
        if ( $compatibility->is_builder_preview() ) {
            return;
        }
        
        $gtm_container_id = isset($this->settings['gtm_container_id']) ? $this->settings['gtm_container_id']: '' ;

        if( $gtm_container_id != '' ) {

            $gtm_active_events = $this->get_active_gtm_events();
            if($gtm_active_events['orderbump_accept']) {
                $this->trigger_gtm_order_bump();
            }

            $this->trigger_gtm_purchase();        

            if($gtm_active_events['upsell']) {
                $this->trigger_gtm_upsell();
            }

            if($gtm_active_events['downsell']) {
                $this->trigger_gtm_downsell();
            }

            $gtm_head_snippet = "
            <!-- Google Tag Manager -->
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','$gtm_container_id');</script>
            <!-- End Google Tag Manager -->
            ";
            echo $gtm_head_snippet;
        }
    }

    /**
     * Load GTM Snippets for <body></body>
     *
     * @return void
     */
    public function add_gtm_body_script () {

        $compatibility = Wpfnl_Theme_Compatibility::getInstance();
        if ( $compatibility->is_builder_preview() ) {
            return;
        }

        $gtm_container_id = isset($this->settings['gtm_container_id']) ? $this->settings['gtm_container_id']: '' ;
        if( $gtm_container_id != '') {
            $gtm_body_snippet = '
            <!-- Google Tag Manager (noscript) -->
            <noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.$gtm_container_id.'"
            height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
            <!-- End Google Tag Manager (noscript) -->
            ';
            echo $gtm_body_snippet;
        }
    }

    /**
     * Process upsell trigger data for dataLayer
     *
     * @return void
     */
    public function trigger_gtm_upsell() {
        $key     = get_current_user_id();

        $upsell_data = get_transient( 'wpfunnels-upsell-info-' . $key );
       
        if($upsell_data) {
            $this->load_purchage_gtm_script($event = 'upsell', $upsell_data);
            delete_transient( 'wpfunnels-upsell-info-' . $key );
        }
    }
    /**
     * Process downsell trigger data for dataLayer
     *
     * @return void
     */
    public function trigger_gtm_downsell() {
        $key     = get_current_user_id();
        $downsell_data = get_transient( 'wpfunnels-downsell-info-' . $key );
        if($downsell_data) {
            $this->load_purchage_gtm_script($event = 'downsell', $downsell_data);
            delete_transient( 'wpfunnels-downsell-info-' . $key );
        }
    }

    /**
     * Process purchase trigger data for dataLayer
     *
     * @return void
     */
    public function trigger_gtm_purchase() {
        $key     = get_current_user_id();

        $payment_data = get_transient( 'wpfunnels-payment-info-' . $key );
        $purchase_data = get_transient( 'wpfunnels-purchase-info-' . $key );
        $shipping_data = get_transient( 'wpfunnels-shipping-info-' . $key );
        $gtm_active_events = $this->get_active_gtm_events();

        if( isset($gtm_active_events['add_payment_info']) && $gtm_active_events['add_payment_info'] && $payment_data) {
            $this->load_purchage_gtm_script($event = 'add_payment_info', $payment_data);
            delete_transient( 'wpfunnels-payment-info-' . $key );
        }
        if( isset($gtm_active_events['add_shipping_info']) && $gtm_active_events['add_shipping_info'] && $shipping_data) {
            $this->load_purchage_gtm_script($event = 'add_shipping_info', $shipping_data);
            delete_transient( 'wpfunnels-shipping-info-' . $key );
        }
        if( isset($gtm_active_events['purchase']) && $gtm_active_events['purchase'] ) {
            $this->load_purchage_gtm_script($event = 'purchase', $purchase_data);
            delete_transient( 'wpfunnels-purchase-info-' . $key );
        }
    }

    /**
     * Process order bump trigger data for dataLayer
     *
     * @return void
     */
    public function trigger_gtm_order_bump() {
        $key     = get_current_user_id();

        $orderbump_data = get_transient( 'wpfunnels-order-bump-accept-' . $key );
        if($orderbump_data) {
            $orderbump_response = $this->prepare_gtm_order_bump_response( $orderbump_data );
            $orderbump_response_data = wp_json_encode( $orderbump_response );
    
            delete_transient( 'wpfunnels-order-bump-accept-' . $key );
            delete_transient( 'wpfunnels-cart-item-key-' . $key );
    
            $dataLayer_script = '
            <script>
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
                window.dataLayer.push( '.$orderbump_response_data.' );
            </script>
            ';
            echo $dataLayer_script;
        }
    }

    /**
     * Prepare order bump event data
     *
     * @return array
     */
    public function prepare_gtm_order_bump_response( $orderbump_data ) {
        return $orderbump_data;
    }

    /**
     * WooCommerce woocommerce_ajax_add_to_cart hook
     *
     * @param $step_id
     * @param $product_id
     */
    public function add_to_cart() {
        $compatibility = Wpfnl_Theme_Compatibility::getInstance();
        if ( $compatibility->is_builder_preview() ) {
            return;
        }
        $key     = get_current_user_id();
        $cart = WC()->cart->get_cart();
        
        $cart_data = array();

        foreach( $cart as $cart_item_key => $cart_item ){
            $is_order_bump = isset($cart_item['wpfnl_order_bump']) ? $cart_item['wpfnl_order_bump']: '' ;
            $cart_item_key_exist = get_transient( 'wpfunnels-cart-item-key-' . $key );
            if($is_order_bump == '' && !$cart_item_key_exist) {
                $product                = $cart_item['data'];
                $cart_data['item_name'] = $product->get_name();
                $cart_data['item_id']   = $product->get_id();
                $cart_data['price']     = $product->get_price();
                $cart_data['index']     = $product->get_id();
                $cart_data['quantity']  = $cart_item['quantity'];
                set_transient( 'wpfunnels-cart-item-key-' . $key, $cart_item_key );
            }
            
        }
        if(empty($cart_data)) {
            return;
        }
        $this->trigger_gtm_add_to_cart($cart_data);
    }

    /**
     * Process order bump trigger data for dataLayer
     *
     * @return void
     */
    public function trigger_gtm_add_to_cart($cart_data) {
        $compatibility = Wpfnl_Theme_Compatibility::getInstance();
        if ( $compatibility->is_builder_preview() ) {
            return;
        }
        $gtm_active_events = $this->get_active_gtm_events();
        if($gtm_active_events['add_to_cart']) {
            $this->load_pre_purchage_gtm_script($event = 'add_to_cart', $cart_data);
        }
        if($gtm_active_events['begin_checkout']) {
            $this->load_pre_purchage_gtm_script($event = 'begin_checkout', $cart_data);
        }
    }

    /**
     * Load purchase event GTM Script
     *
     * @return array
     */
    public function load_purchage_gtm_script($event, $data) {
        if (!$event || !$data ) {
            return;
        }
        $gtm_event_data = array(
			'event'       => $event,
			'ecommerce'   => array(
                'items' => array(
                    $data,
                )
			)
		);
        $dataLayer_script = '
        <script>
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
            window.dataLayer.push( '.wp_json_encode( $gtm_event_data ).' );
        </script>
        ';
        echo $dataLayer_script;
    }

    /**
     * Load pre purchase event GTM Script
     *
     * @return array
     */
    public function load_pre_purchage_gtm_script($event, $data) {
        if (!$event) {
            return;
        }
        $gtm_event_data = array(
			'event'       => $event,
			'ecommerce'   => array(
                'items' => array(
                    $data,
                )
			)
		);

        $dataLayer_script = '
        <script>
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
            window.dataLayer.push( '.wp_json_encode( $gtm_event_data ).' );
        </script>
        ';
        echo $dataLayer_script;
    }

    /**
     * Order bump accepted hook
     *
     * @param $step_id
     * @param $product_id
     */
    public function order_bump_accepted( $step_id, $product_id ) {

        $product = wc_get_product($product_id);
        if( $product ){
            $key     = get_current_user_id();
            $data = array(
                'event'           => 'orderbump_accept',
                'ecommerce'       => array(
                    'items'   => array(
                        array(
                            'item_name'         => $product->get_name(),
                            'item_id'           => $product->get_id(),
                            'price'             => $product->get_price(),
                            'index'             => $product->get_id(),
                            'quantity'          => 1,
                        ),
                    )
                )
            );
            $gtm_active_events = $this->get_active_gtm_events();
            if($gtm_active_events['orderbump_accept']) {
                set_transient( 'wpfunnels-order-bump-accept-' . $key, $data );
            }
        }
    }
    
    /**
     * Main Order Tracking hook
     *
     * @param $order_id
     * @param $funnel_id
     */
    public function funnel_order_placed( $order_id, $funnel_id, $step_id ) {
        $order = wc_get_order( $order_id );
        $coupon_codes   = $order->get_coupon_codes();

        $item_data = array();
        foreach ( $order->get_items() as $item_id => $item ) {
            $item_data['item_name'] = $item->get_name();
            $item_data['item_id'] = $item->get_product_id();
            $item_data['affiliation']   = get_bloginfo( 'name' );
            $item_data['price']   = $item->get_total();
            $item_data['index'] = $item->get_product_id();
            $item_data['quantity'] = $item->get_quantity();  

         }

        $key     = get_current_user_id();
        $payment_info = array(
            'currency'      => $order->get_currency(),
            'value'         => $order->get_total(),
            'coupon'        => '',
            'payment_type'  => $order->get_payment_method_title(),
            'items'   => array(
                $item_data
            )
		);
        $shipping_info = array(
            'currency'      => $order->get_currency(),
            'value'         => $order->get_total(),
            'coupon'        => '',
            'shipping_tier' => '',
            'items'   => array(
                $item_data
            )
		);
        $gtm_active_events = $this->get_active_gtm_events();
        if($gtm_active_events['add_payment_info']) {
            set_transient( 'wpfunnels-payment-info-' . $key, $payment_info );
        }

        if($gtm_active_events['purchase']) {
            set_transient( 'wpfunnels-purchase-info-' . $key, $payment_info );
        }

        if($gtm_active_events['add_shipping_info']) {
            set_transient( 'wpfunnels-shipping-info-' . $key, $shipping_info );
        }
    }

    /**
     * This will trigger after user accept the offer
     *
     * @param $order
     * @param $offer_product
     */
    public function offer_accepted( $order, $offer_product ) {

        $step_id = $offer_product['step_id'];
        $step_type = $this->get_offer_step_type( $step_id );

        $item_data = array();
        $item_data['item_name'] = $offer_product['name'];
        $item_data['item_id'] = $offer_product['id'];
        $item_data['affiliation']   = get_bloginfo( 'name' );
        $item_data['price']   = $offer_product['total'];
        $item_data['index'] = $offer_product['id'];
        $item_data['quantity'] = $offer_product['qty'];  

        $key     = get_current_user_id();

        $offer_info = array(
            'currency'      => $order->get_currency(),
            'value'         => $order->get_total(),
            'coupon'        => '',
            'payment_type'  => $order->get_payment_method_title(),
            'items'   => array(
                $item_data
            )
		);
        $gtm_active_events = $this->get_active_gtm_events();

        if($step_type == 'upsell' && $gtm_active_events['upsell']) {
            set_transient( 'wpfunnels-upsell-info-' . $key, $offer_info );
        }

        if($step_type == 'downsell' && $gtm_active_events['downsell']) {
            set_transient( 'wpfunnels-downsell-info-' . $key, $offer_info );
        }
    }

    /**
     * Get GTM Settings
     * @return array
     */

    public function get_settings() {
		$default = array(
			'gtm_enable'		=> 'off',
			'gtm_container_id' 	=> '',
			'gtm_events' 		=> array(
				'view_item' 		=> 'true',
				'view_item_list' 	=> 'true',
				'select_item' 		=> 'true',
				'add_to_cart' 		=> 'true',
				'remove_from_cart' 	=> 'true',
				'view_cart' 		=> 'true',
				'begin_checkout' 	=> 'true',
				'add_payment_info' 	=> 'true',
				'add_shipping_info' => 'true',
				'purchase' 			=> 'true',
			),
		);
        $settings = get_option('_wpfunnels_gtm', $default);
        return wp_parse_args($settings, $default);
    }
    
    /**
     * check if gtm is enabled or not
     *
     * @return bool
     */
    private function is_gtm_enabled() {
        if ( 'on' == $this->settings['gtm_enable'] ) {
            $step_id        = get_the_id();
            $funnel_id      = Wpfnl_functions::get_funnel_id_from_step( $step_id );
            $is_disabled    = get_post_meta( $funnel_id, '_wpfunnels_disabled_gtm', true );
            if( !$is_disabled || ($is_disabled && 'no' == $is_disabled) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get active GTM events
     * @return array|bool
     */
    public function get_active_gtm_events(){

        if (isset($this->settings['gtm_events'])){
            return $this->settings['gtm_events'];
        }
        return false;
    }

    /**
     * Check Funnel Page
     * @return bool
     */
    public function is_funnel_page(){
        if(get_post_type() == 'wpfunnel_steps'){
            return true;
        }
        return false;
    }

    /**
     * check if the current step is upsell/downsell
     * @return string
     */
    public  function get_offer_step_type( $step_id ) {
        $is_upsell_downsell = false;
        if( !$step_id ) {
            global $post;
            $step_id = $post->ID;
        }
        $step_type = get_post_meta( $step_id, '_step_type', true );
        if ( $step_type === 'upsell' || $step_type === 'downsell' || $step_type === 'landing' ) {
            return  $step_type;
        }
        return $is_upsell_downsell;
    }
}