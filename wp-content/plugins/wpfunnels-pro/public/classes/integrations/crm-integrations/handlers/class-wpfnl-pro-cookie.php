<?php

namespace WPFunnelsPro\Integrations\CRM;

use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

/**
 * Cookie Handler class for WPF Pro
 *
 * Class CookieHandler
 * @package WPFunnelsPro\Integrations
 */
class CookieHandler {

    use SingletonTrait;

    public function init_actions() {
        add_action( 'wpfunnels/funnel_journey_starts', array( $this, 'start_journey' ), 10, 2 );
        add_action( 'wpfunnels/funnel_journey_end', array( $this, 'end_journey' ), 10, 2 );
        add_action( 'wpfunnels/trigger_cta', array( $this, 'trigger_cta' ), 10, 2 );
        add_action( 'wpfunnels/order_bump_accepted', array( $this, 'order_bump_accepted' ), 10, 2 );
        add_action( 'wpfunnels/order_bump_rejected', array( $this, 'order_bump_rejected' ), 10, 2 );
        add_action( 'wpfunnels/funnel_order_placed', array($this, 'funnel_order_placed'), 10, 3 );
        add_action( 'wpfunnels/offer_accepted', array( $this, 'offer_accepted' ), 10, 2 );
        add_action( 'wpfunnels/offer_rejected', array( $this, 'offer_rejected' ), 10, 2 );
        add_action( 'wpfunnels/maybe_user_abandoned_funnel', array( $this, 'funnel_abandoned' ), 10, 2 );
        add_action( 'wpfunnels/after_optin_submit', array( $this, 'get_optin_data' ), 10, 4 );
    }

    /**
     * check if automation is enabled or not
     *
     * @return bool
     */
    private function is_automation_enabled($funnel_id) {
        if($funnel_id) {
            $automations = get_post_meta( $funnel_id, 'funnel_automation_data', true);
            if( is_array($automations) && !empty($automations) ){
                return 'true';
            }
        }
        return 'false';
    }

    /**
     * Start Journey hook trigger if user visit funnel landing page
     *
     * @param $step_id
     * @param $funnel_id
     */
    public function start_journey( $step_id, $funnel_id ) {
        if('false' === $this->is_automation_enabled($funnel_id)) {
            return;
        }

        /** Define Cookie Key */
        $cookie_name        = 'wpfunnels_automation_data';

        /** Un-setting Cookie Data with Key. */
        setcookie( $cookie_name, '', time() + 3600 * 6, '/', COOKIE_DOMAIN );

        /** Checking and Initialize Cookie with Key */
        $cookie  = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
        
        if($funnel_id) {
            $cookie['funnel_id'] = $funnel_id;
            
            $current_user = wp_get_current_user();
            $cookie['user_info']   = [
                'id'            => get_current_user_id(),
                'first_name'    => $current_user->user_firstname,
                'last_name'     => $current_user->user_lastname,
                'email'         => $current_user->user_email
            ];
        }
        
        if( !isset($cookie['ob_accepetd_products']) ){
            $cookie['ob_accepetd_products'] = [];
        }

        $cookie['funnel_status']   = 'processing';
        /** Set Cookies. */
        setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
    }


    /**
     * Get optin data
     * 
     * @param $step_id, $record
     */
    public function get_optin_data( $step_id, $post_action, $action_type, $record ){

        
        $funnel_id = Wpfnl_functions::get_funnel_id_from_step($step_id);
        if('false' === $this->is_automation_enabled($funnel_id)) {
            return;
        }
        /** Set Cookie Data */
        $cookie_name        = 'wpfunnels_automation_data';
        $cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
        $cookie['after_optin_submit']   = ( isset($record->form_data['email']) && $record->form_data['email'] ) ? $record->form_data : false;
        setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
        
        if(!isset($cookie['funnel_id'])) {
            $cookie['funnel_id']   = $funnel_id;
        }

        if( !isset($cookie['ob_accepetd_products']) ){
            $cookie['ob_accepetd_products'] = [];
        }
        $cookie['funnel_status']   = 'successful';
        // setcookie( $cookie_name, null, strtotime( '-1 days' ), '/', COOKIE_DOMAIN );
        if(isset( $_COOKIE[$cookie_name] )){
            ob_start();
            do_action( 'wpfunnels/trigger_automation', $cookie );
            ob_get_clean();
        }
       
    }

    /**
     * End Journey hook if funnel journey end
     *
     * @param $step_id
     * @param $funnel_id
     */
    public function end_journey( $step_id, $funnel_id ) {
        // if('false' === $this->is_automation_enabled($funnel_id)) {
        //     return;
        // }

        Wpfnl_functions::unset_site_cookie( $step_id, 'wpfunnels_automation_data', 'wpfunnels/trigger_automation', $funnel_id );
    }


    /**
     * Action if user clicks on the cta
     *
     * @param $step_id
     * @param $funnel_id
     */
    public function trigger_cta( $step_id, $funnel_id ) {
        if('false' === $this->is_automation_enabled($funnel_id)) {
            return;
        }
        /** Set Cookie Data */
        $cookie_name        = 'wpfunnels_automation_data';
        $cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
        
        $cookie['cta_clicked']   = true;
        if(!isset($cookie['funnel_id'])) {
            $cookie['funnel_id']   = $funnel_id;
        }

        setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );

        if(isset( $_COOKIE[$cookie_name] )){
            ob_start();
            do_action( 'wpfunnels/trigger_automation', $cookie );
            ob_get_clean();
        }
    }


    /**
     * Order bump accepted hook
     *
     * @param $step_id
     * @param $product_id
     */
    public function order_bump_accepted( $step_id, $product_id ) {

        /** Set Cookie Data */
        $cookie_name        = 'wpfunnels_automation_data';
        $cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
        $cookie['orderbump_accepted']   = true;
        
        if( !isset($cookie['ob_accepetd_products']) ){
            $cookie['ob_accepetd_products'] = [];
        }

        $funnel_id = get_post_meta($step_id,'_funnel_id',true);
        $is_lms = get_post_meta($funnel_id,'_wpfnl_funnel_type',true);
        
        if( $is_lms !== 'lms' ){
            if( !in_array($product_id,$cookie['ob_accepetd_products']) ){
                array_push($cookie['ob_accepetd_products'],$product_id);
            }
        }else{
            $cookie['ob_accepetd_products'] = [];
            array_push($cookie['ob_accepetd_products'],$product_id);
            
        }
        
        if(!isset($cookie['funnel_id'])) {
            $cookie['funnel_id']   = $funnel_id;
        }
        setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
    }
    
    /**
     * Order bump accepted hook
     *
     * @param $step_id
     * @param $product_id
     */
    public function order_bump_rejected( $step_id, $product_id ) {
        
        /** Set Cookie Data */
        $cookie_name        = 'wpfunnels_automation_data';
        $cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
        
        if( !isset($cookie['ob_accepetd_products']) ){
            $cookie['ob_accepetd_products'] = [];
            if(!isset($cookie['orderbump_accepted'])){
                $cookie['orderbump_accepted']   = false;
            }
        }
        if( in_array($product_id,$cookie['ob_accepetd_products']) ){
            if (($key = array_search($product_id, $cookie['ob_accepetd_products'])) !== false) {
                unset($cookie['ob_accepetd_products'][$key]);
            }
        }
        $funnel_id = get_post_meta($step_id,'_funnel_id',true);
        
        if(!isset($cookie['funnel_id'])) {
            $cookie['funnel_id']   = $funnel_id;
        }
        setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
    }

    /**
     * Main Order Tracking hook
     *
     * @param $order_id
     * @param $funnel_id
     */
    public function funnel_order_placed( $order_id, $funnel_id, $step_id) {
        
        // if('false' === $this->is_automation_enabled($funnel_id)) {
        //     return;
        // }

        if($funnel_id){
            /** Set Cookie Data */
            $cookie_name        = 'wpfunnels_automation_data';
            $cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();

            $cookie['main_order_id']    = $order_id;

            if(!isset($cookie['funnel_id'])) {
                $cookie['funnel_id']   = $funnel_id;
            }
            
            if( !isset($cookie['ob_accepetd_products']) ){
                $cookie['ob_accepetd_products'] = [];
            }

            if(!isset($cookie['orderbump_accepted'])){
                $cookie['orderbump_accepted']   = false;
            }
            
            setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );

            if(isset( $_COOKIE[$cookie_name] )){
                ob_start();
                do_action( 'wpfunnels/trigger_automation', $cookie );
                ob_get_clean();
            }
        }

    }


    /**
     * This will trigger after user accept the offer
     *
     * @param $order
     * @param $offer_product
     */
    public function offer_accepted( $order, $offer_product ) {

        $step_id            = $offer_product['step_id'];
        $funnel_id          = get_post_meta($step_id, '_funnel_id', true);
        $step_type          = get_post_meta($step_id, '_step_type', true);
        $order_id           = $order->get_id();

        if('false' === $this->is_automation_enabled($funnel_id)) {
            return;
        }
        if(!isset($cookie['funnel_id'])) {
            $cookie['funnel_id']   = $funnel_id;
        }
        $offer_settings = \WPFunnels\Wpfnl_functions::get_offer_settings();
        $product_id         = $offer_product['id'];
        $product_name       = $offer_product['name'];
        $product_qty        = $offer_product['qty'];
        $product_price      = $offer_product['price'];

        /** Set Cookie Data */
        $cookie_name        = 'wpfunnels_automation_data';
        $cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();

        $cookie['child_order']   = $offer_settings['offer_orders'] == 'child-order' ? true : false;
        $cookie['offer'][$step_id]   = array(
            'id'            => $step_id,
            'type'          => $step_type,
            'order_id'      => $order_id,
            'status'        => 'accepted'
        );
        setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );

        if(isset( $_COOKIE[$cookie_name] )){
            ob_start();
            do_action( 'wpfunnels/trigger_automation', $cookie );
            ob_get_clean();
        }
    }


    /**
     * This will trigger after user rejected the offer
     *
     * @param $order
     * @param $offer_product
     */
    public function offer_rejected( $order, $offer_product ) {
        
        $step_id            = isset($offer_product['step_id']) ? $offer_product['step_id'] : '';
        $funnel_id          = get_post_meta($step_id, '_funnel_id', true);
        $step_type          = get_post_meta($step_id, '_step_type', true);

        if('false' === $this->is_automation_enabled($funnel_id)) {
            return;
        }
        if(!isset($cookie['funnel_id'])) {
            $cookie['funnel_id']   = $funnel_id;
        }
        $offer_settings = \WPFunnels\Wpfnl_functions::get_offer_settings();
        
        $product_id         = $offer_product['id'];

        /** Set Cookie Data */
        $cookie_name        = 'wpfunnels_automation_data';
        $cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();

        $cookie['child_order']   = $offer_settings['offer_orders'] == 'child-order' ? true : false;
        $cookie['offer'][$step_id]   = array(
            'id'            => $step_id,
            'type'          => $step_type,
            'status'        => 'rejected'
        );
        
        setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
        if(isset( $_COOKIE[$cookie_name] )){
            ob_start();
            do_action( 'wpfunnels/trigger_automation', $cookie );
            ob_get_clean();
        }
    }


    /**
     * this will trigger after any funnel is abandoned
     *
     * @param $step_id
     * @param $funnel_id
     */
    public function funnel_abandoned( $step_id, $funnel_id ) {
        if('false' === $this->is_automation_enabled($funnel_id)) {
            return;
        }
        /** Set Cookie Data */
        $cookie_name        = 'wpfunnels_automation_data';
        $cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
        $cookie['funnel_status']   = 'abandoned';
        if(!isset($cookie['funnel_id'])) {
            $cookie['funnel_id']   = $funnel_id;
        }
        setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
    }

}