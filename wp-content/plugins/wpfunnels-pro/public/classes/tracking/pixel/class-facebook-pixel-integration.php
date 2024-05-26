<?php

namespace WPFunnelsPro\Tracking\Pixel;
use WC_Order_Factory;
use WC_Product_Factory;
use WPFunnels\Compatibility\Wpfnl_Theme_Compatibility;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

Class Facebook_Pixel_Integration{

    use SingletonTrait;

    private $add_to_cart;

    private $product_id;

    public function init_actions() {
        add_action( 'wp_head', array( $this, 'init' ) );
        add_action( 'wpfunnels/funnel_order_placed', array($this, 'funnel_order_placed'), 10, 3 );
        add_action( 'wpfunnels/order_bump_accepted', array( $this, 'order_bump_accepted' ), 10, 2 );
        add_action( 'wpfunnels/offer_accepted', array( $this, 'offer_accepted' ), 10, 2 );
        add_action( 'wpfunnels/facebook_pixel_events', array($this, 'track_offer_event_for_fb'),10,2 );
        add_action( 'wpfunnels/facebook_pixel_events', array($this, 'track_payment_event_for_fb'),10,2 );
        add_action( 'wpfunnels/facebook_pixel_events', array($this, 'track_main_order_purchase_event_for_fb'),10,2 );
    }


    public function init() {
       
     
        if( $this->is_facebook_pixel_enable()  && $this->is_edit_page() != 'yes') {
            $this->load_facebook_pixel_public_script();
           
            add_action( 'woocommerce_thankyou', array($this, 'load_facebook_pixel_public_script') );
            add_action( 'woocommerce_add_to_cart', array($this, 'added_to_cart'),10,2 );
            // AddToCart while AJAX is enabled
            add_action( 'woocommerce_ajax_added_to_cart', array( $this, 'ajax_added_to_cart' ) );
        }
    }


    /**get_customer_id
     * Trigger Facebook tracking events 'ViewContent','Purchase','AddPaymentInfo','InitiateCheckout'
     * @param false $order_id
     */
    public function load_facebook_pixel_public_script( $order_id = false ) {
        // if( $order_id ){
            $compatibility = Wpfnl_Theme_Compatibility::getInstance();
            if ( $compatibility->is_builder_preview() ) {
                return;
            }
            $fb_events      = $this->get_active_facebook_tacking_event();
            $pixel_id       = $this->get_facebook_pixel_id();
            if ( !$fb_events && !$pixel_id ) {
                return;
            }
            if ($pixel_id != '' && $this->is_funnel_page() && $this->is_edit_page() != 'yes' ){
                $is_landing = $this->get_offer_step_type(get_the_ID());
                if ($is_landing == 'landing' && isset($fb_events['ViewContent'])){
                    echo $this->load_script( $pixel_id, 'ViewContent', array(
                        'content_ids'  => $this->product_id,
                        'content_type' => 'product',
                        'plugin'       => 'WPFunnel-Landing'
                    ));
                }
                if( Wpfnl_functions::is_wc_active() && is_checkout() && isset($fb_events['InitiateCheckout']) && strpos( $_SERVER['REQUEST_URI'], 'order-received' ) === false ) {
                    echo $this->load_script( $pixel_id, 'InitiateCheckout' );
                }
                echo $this->load_script( $pixel_id, 'PageView' );

                do_action( 'wpfunnels/facebook_pixel_events',$pixel_id,$fb_events );
            }
        // }
        
    }


    /**
     * Load facebook Pixel code
     * @param $pixel_id
     * @param false $event
     * @param string $params
     * @return string
     */
    public function load_script( $pixel_id, $event = false, $params='' ) {
        $http_params = '';
        if($params) {
            $http_params = '&' . http_build_query($params);
            $params = ', ' . json_encode($params);
        }
        
        return "
            <!-- Facebook Pixel Code by WPFunnels -->
                <script>
                  !function(f,b,e,v,n,t,s)
                  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                  n.queue=[];t=b.createElement(e);t.async=!0;
                  t.src=v;s=b.getElementsByTagName(e)[0];
                  s.parentNode.insertBefore(t,s)}(window, document,'script',
                  'https://connect.facebook.net/en_US/fbevents.js');                    
                </script>
                <noscript>
                    <img height='1' width='1' style='display:none' src='https://www.facebook.com/tr?id='". esc_js($pixel_id) . "'&ev=".$event . $http_params ."&noscript=1'/>
                </noscript>
            

                <script type='text/javascript'>
                    fbq('init', ".esc_js( $pixel_id ) .");
                    fbq('track', '$event'$params, {'plugin': 'WPFunnels'});
                </script>
            <!-- End Facebook Pixel Code by WPFunnels -->
            ";
    }



    /**
     * Order bump accepted hook
     *
     * @param $step_id
     * @param $product_id
     */
    public function order_bump_accepted( $step_id, $product_id ) {
        add_filter(
            'woocommerce_update_order_review_fragments',
            function( $data ) use ( $product_id ) {
                $data['product_added_to_cart'] = $this->prepare_fb_pixel_response( $product_id );
                return $data;
            }
        );
    }

    /**
     * Check facebook Pixel
     * @return string
     */
    public function is_facebook_pixel_enable() {
        $facebook_pixel_setting = get_option( '_wpfunnels_facebook_pixel' );
        
        if ( isset($facebook_pixel_setting['enable_fb_pixel']) && 'on' == $facebook_pixel_setting['enable_fb_pixel'] ) {
            $step_id        = get_the_id();
            $funnel_id      = Wpfnl_functions::get_funnel_id_from_step( $step_id );
            $is_disabled    = get_post_meta( $funnel_id, '_wpfunnels_disabled_fb_pixel', true );
            
            if( !$is_disabled || ($is_disabled && $is_disabled == 'no') ){
                return true;
            }
        }
        return false;
    }

    /**
     * Get facebook pixel ID
     * @return string
     */
    public function get_facebook_pixel_id(){
        $facebook_pixel_setting = get_option('_wpfunnels_facebook_pixel');
        if (isset($facebook_pixel_setting['facebook_pixel_id'])){
            return $facebook_pixel_setting['facebook_pixel_id'];
        }
    }

    /**
     * Get active facebook tracking events
     * @return mixed|void
     */
    public function get_active_facebook_tacking_event(){
        $facebook_pixel_setting = get_option('_wpfunnels_facebook_pixel');
        if (isset($facebook_pixel_setting['facebook_tracking_events'])){
            return $facebook_pixel_setting['facebook_tracking_events'];
        }
    }



    /**
     * Load Facebook Pixel code for ajax (add_to_cart)
     */
    public function load_script_for_ajax() {
        ?>
        <script>
            var selectors = '.ajax_add_to_cart';
            jQuery( selectors ).click(function() {
                fbq('track', 'AddToCart',{
                    content_ids: this.dataset.product_id,
                    content_type: 'product',
                });
            })
        </script>
        <?php
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
     * Check Edit Page
     */
    public function is_edit_page(){
        $is_edit = isset($_GET['action']) ? $_GET['action'] : '' ;
        if( $is_edit == 'edit' ){
            return 'yes';
        }
        return false;
    }

    /**
     * Trigger Facebook Tracking event 'AddToCart'
     * @param $product_id
     */
    public function added_to_cart( $product_id ) {
        $this->add_to_cart  = true;
        $this->product_id   = $product_id;
        $fb_events          = $this->get_active_facebook_tacking_event();
        $pixel_id           = $this->get_facebook_pixel_id();
        if ($pixel_id != '' && $this->is_funnel_page() && $this->is_edit_page() != 'yes'){
            if( $this->add_to_cart  && isset($fb_events['AddToCart']) ) {
                echo $this->load_script( $pixel_id, 'AddToCart', array(
                    'content_ids'  => $this->product_id,
                    'content_type' => 'product',
                ));
            }
        }
    }

    public function ajax_added_to_cart()
    {
        if( get_option( 'woocommerce_enable_ajax_add_to_cart' ) == 'yes' && isset($fb_events['AddToCart'])) {
           $this->load_script_for_ajax();
        }
    }

    /**
     * Get Facebook data for event Purchase in Thank you page
     * @param $order
     * @param array $offer_data
     * @return array
     */
    public function get_fb_data($order, $offer_data = array())
    {
        $value      = $order->get_total();
        $currency   = $order->get_currency();
        $data       = array(
            'content_type'  => 'product',
            'value'     => $value,
            'currency'  => $currency,
        );
        if( $order ){
            foreach ( $order->get_items() as $item_key => $item ) {
                $product                    = $item->get_product();
                if( $product ){
                    $data['content_ids'][]      = (string) $product->get_id();
                    $data['content_names'][]    = $product->get_name();
                    $data['content_category'][] = wp_strip_all_tags( wc_get_product_category_list( $product->get_id() ) );
                    $data['plugin']             = 'WPFunnels-Main-Order';
                }
            }
            $data['transaction_id']  = $order->get_id();
        }
        
        return $data;
    }

    /**
     * Prepare Order bump product info for fb event
     * @param $product_id
     * @return array
     */
    public function prepare_fb_pixel_response($product_id)
    {
        $response     = array();
        if( Wpfnl_functions::is_wc_active() ){
           
            $product_details = array();
            $product      = wc_get_product( $product_id );
            $items        = WC()->cart->get_cart();

            foreach ( $items as $index => $item ) {
                if ( $item['product_id'] === $product_id ) {
                    $product_details = $item;
                    break;
                }
            }

            if ( ! empty( $product_details ) ) {

                $add_to_cart['content_type']       = 'product';
                $add_to_cart['plugin']             = 'WPFunnels-OrderBump';
                $add_to_cart['content_category'][] = wp_strip_all_tags( wc_get_product_category_list( $product  ? $product->get_id() : '' ) );
                $add_to_cart['currency']           = get_woocommerce_currency();
                $add_to_cart['value']              = $product_details['line_subtotal'] + $product_details['line_subtotal_tax'];
                $add_to_cart['content_name']       = $product->get_title();
                $add_to_cart['content_ids'][]      = (string) $item['product_id'];

                $add_to_cart['contents'] = wp_json_encode(
                    array(
                        array(
                            'id'         => $product_details['product_id'],
                            'name'       => $product->get_title(),
                            'quantity'   => $product_details['quantity'],
                            'item_price' => $product_details['line_subtotal'] + $product_details['line_subtotal_tax']
                        ),
                    )
                );
                $response['product_added_to_cart'] = $add_to_cart;
            }
        }
        return $response;
    }

    /**
     * Main Order Tracking hook
     * Add payment,Purchase set in transient
     * @param $order_id
     * @param $funnel_id
     */
    public function funnel_order_placed( $order_id, $funnel_id, $step_id ) {
        if( Wpfnl_functions::is_wc_active() ){
            $order = wc_get_order($order_id);
            $payment_info = array(
                'content_ids'           => $order_id,
                'value'                 => $order->get_total(),
                'currency'              => $order->get_currency(),
                'payment_method'        => $order->get_payment_method_title(),
            );
            $user_key = WC()->session->get_customer_id();
            $payment_data = array(
                'order_id'      => $order_id,
                'payment_info'  => $payment_info,
            );

            $purchase_data = $this->get_fb_data($order);
        
            set_transient( 'wpfnl-payment-method-details-for-fbp-' . $user_key, $payment_data );
            set_transient( 'wpfnl-main-order-purchase-details-for-fbp-' . $user_key, $purchase_data );
        }
    }


    /**
     * This will trigger after user accept the offer
     *
     * @param $order
     * @param $offer_product
     */
    public function offer_accepted( $order, $offer_product ) {
        if( Wpfnl_functions::is_wc_active() ){
            $user_key = WC()->session->get_customer_id();
            $data = array(
                'order_id'      => $order->get_id(),
                'offer_product' => $offer_product,
                'accept_offer'  => $this->get_offer_step_type($offer_product['step_id'])
            );
            set_transient( 'wpfnl-offer-details-for-fbp-' . $user_key, $data );
        }
    }

    /**
     * Prepare Facebook response for upsell/downsell offer
     * @param $order_id
     * @param $offer_details
     * @return array
     */
    public function prepare_offer_fb_pixel_response( $order_id, $offer_details ) {

        $order = wc_get_order($order_id);

        if( !$order ) {
            return;
        }

        $purchase_info= array();

        $product_details = $offer_details['offer_product'];

        if ( empty( $product_details ) ) {
            return $purchase_info;
        }

        $purchase_info['content_type']          = 'product';
        $purchase_info['currency']              = $order->get_currency();
        $purchase_info['userAgent']             = $order->get_customer_user_agent();
        $purchase_info['plugin']                = 'WPFunnel-Offer';
        $purchase_info['content_ids'][]         = (string) $product_details['id'];
        $purchase_info['content_names'][]       = $product_details['name'];
        $purchase_info['content_category'][]    = wp_strip_all_tags( wc_get_product_category_list( $product_details['id'] ) );
        $purchase_info['value']                 = $product_details['total'];
        $purchase_info['transaction_id']        = $offer_details['order_id'];
        $purchase_info['accepted_offer']        = $offer_details['accept_offer'];
        return $purchase_info;
    }

    /**
     * Track offer Event for facebook
     * @param $pixel_id
     * @param $fb_events
     */
    public function track_offer_event_for_fb( $pixel_id, $fb_events )
    {
        if( Wpfnl_functions::is_wc_active() ){
            $user_key = Wpfnl_functions::is_wc_active() && isset(WC()->session) ? WC()->session->get_customer_id()  : '' ;

            $offer_product = get_transient( 'wpfnl-offer-details-for-fbp-' . $user_key );
        
            if ( empty( $offer_product ) ) {
                return;
            }
            $order_id = $offer_product['order_id'];

            $purchase_details = $this->prepare_offer_fb_pixel_response( $order_id, $offer_product );

            delete_transient( 'wpfnl-offer-details-for-fbp-' . $user_key );

            if ( ! empty( $purchase_details ) ) {
                if ($pixel_id != '' && $this->is_funnel_page()){
                    if( $order_id != false &&  isset($fb_events['Purchase']) ) {
                        echo $this->load_script( $pixel_id, 'Purchase', $purchase_details );
                    }
                }

            }
        }
    }

    /**
     * Prepare Payment info for facebook response
     * @param $order_id
     * @param $payment_info
     * @return array|mixed
     */
    public function prepare_payment_info_fb_pixel_response($order_id, $payment_info)
    {
        $add_payment_info     = array();

        $payments_details = $payment_info['payment_info'];

        if ( empty( $payments_details ) ) {
            return $payment_info;
        }
        $add_payment_info['content_ids']     = $payments_details['content_ids'];
        $add_payment_info['value']           = $payments_details['value'];
        $add_payment_info['currency']        = $payments_details['currency'];
        $add_payment_info['payment_method']  = $payments_details['payment_method'];
        $add_payment_info['plugin']          = 'WPFunnel-PaymentInfo';
        return $add_payment_info;
    }

    /**
     * Track AddPaymentInfo Event for facebook
     * @param $pixel_id
     * @param $fb_events
     */
    public function track_payment_event_for_fb( $pixel_id, $fb_events )
    {
        if( Wpfnl_functions::is_wc_active() ){

            $user_key =  isset(WC()->session) ? WC()->session->get_customer_id()  : '' ;

            $payment_info = get_transient( 'wpfnl-payment-method-details-for-fbp-' . $user_key );
            if ( empty( $payment_info ) ) {
                return;
            }
            $order_id = $payment_info['order_id'];

            $purchase_details = $this->prepare_payment_info_fb_pixel_response( $order_id, $payment_info );

            delete_transient( 'wpfnl-payment-method-details-for-fbp-' . $user_key );

            if ( ! empty( $purchase_details ) ) {
                if ($pixel_id != '' && $this->is_funnel_page()) {
                    if ($order_id != false && isset($fb_events['AddPaymentInfo'])) {
                        echo $this->load_script($pixel_id, 'AddPaymentInfo', $purchase_details);
                    }
                }
            }
        }
    }

    public function track_main_order_purchase_event_for_fb( $pixel_id, $fb_events )
    {
        if( Wpfnl_functions::is_wc_active() ){
            $user_key =  isset(WC()->session) ? WC()->session->get_customer_id()  : '' ;

            $purchase_info = get_transient( 'wpfnl-main-order-purchase-details-for-fbp-' . $user_key );
            delete_transient( 'wpfnl-main-order-purchase-details-for-fbp-' . $user_key );

            if ( ! empty( $purchase_info ) ) {
                if ($pixel_id != '' && $this->is_funnel_page()) {
                    if ($purchase_info['transaction_id'] != false && isset($fb_events['Purchase'])) {
                        echo $this->load_script($pixel_id, 'Purchase', $purchase_info);
                    }
                }
            }
        }
        
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