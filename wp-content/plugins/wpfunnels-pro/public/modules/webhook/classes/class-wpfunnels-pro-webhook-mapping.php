<?php

namespace WPFunnelsPro\Frontend\Modules\Webhook;

use WPFunnelsProWebHooks\Functions\Wpfnl_Pro_Webhook_Functions;
use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;
use WPFunnelsProWebHooks\Events;
use WPFunnels\Wpfnl_functions;
use WPFunnels\Data_Store\Wpfnl_Steps_Store_Data;
class Wpfnl_Pro_Webhook_Mapping {


    public $funnel_id;
    public $settings;
    public function __construct()
    {
        add_action( 'wpfunnels/offer_accepted', array( $this, 'offer_accepted' ), 10, 2 );
        add_action( 'wpfunnels/offer_rejected', array( $this, 'offer_rejected' ), 10, 2 );

        add_action( 'wpfunnels/after_optin_submit', array( $this, 'send_optin_data' ), 10, 4 );

        add_action( 'wpfunnels/funnel_order_placed', array($this, 'funnel_order_placed'), 10, 2 );
    }


    /**
     * This will trigger after user accept the offer
     *
     * @param $order
     * @param $offer_product
     */
    public function offer_accepted( $order, $offer_product ) {
       
        $is_activated = Wpfnl_functions::is_webhook_license_activated();
        if( $is_activated ){
            $this->offer_event_control( $order, $offer_product, 'accepted' );
        }
          
    }


    /**
     * This will trigger after user rejected the offer
     *
     * @param $order
     * @param $offer_product
     */
    public function offer_rejected( $order, $offer_product ) {
    
        $is_activated = Wpfnl_functions::is_webhook_license_activated();
        if( $is_activated ){
            $this->offer_event_control( $order, $offer_product, 'rejected' );
        }
        
    }


    /**
     * Send optin data 
     * 
     * @param $step_id, $record
     */
    public function send_optin_data( $step_id, $post_action, $action_type, $record ){
        
        $is_activated = Wpfnl_functions::is_webhook_license_activated();
        if( $is_activated ){
            $funnel_id  = get_post_meta($step_id, '_funnel_id', true);
            $settings   =  Wpfnl_Pro_Webhook_Functions::get_webhook_settings( $funnel_id );
            if( !empty( $settings )){
                foreach( $settings as $key=>$setting ){
                    $status = Wpfnl_Pro_Webhook_Functions::check_webhook_status( $setting['status'] );
                    if( !$status ){
                        continue;
                    }
                    if( false !== strpos($setting['conditions'], 'optin_submitted') ){
                        $condition = 'optin_submitted';
                    }

                    $is_matched = Wpfnl_Pro_Webhook_Functions::match_conditions( 'optin_submitted', $condition );
                    if( $is_matched ){
                        $class = Wpfnl_Pro_Webhook_Functions::get_class_instance( 'optin', $setting, $record );
                        if( $class ){
                            $class->send_data(); 
                            break;
                        }
                    }   
                }
            }
        }
        
    }


     /**
     * Main Order Tracking hook
     *
     * @param $order_id
     * @param $funnel_id
     */
    public function funnel_order_placed( $order_id, $funnel_id ) {
        $checkout_id = Wpfnl_functions::get_checkout_id_from_order( $order_id );
        $is_ob_settings = get_post_meta( $checkout_id , 'order-bump-settings' , true );

	    $order = wc_get_order($order_id);
	    $settings   =  Wpfnl_Pro_Webhook_Functions::get_webhook_settings( $funnel_id );
	    foreach( $order->get_items() as $item ){
		    if( $item ){
			    $is_ob = $item->get_meta('_wpfunnels_order_bump');
			    if( 'yes' === $is_ob ){
				    if( !empty( $settings )){
					    foreach( $settings as $setting ){
						    $status = Wpfnl_Pro_Webhook_Functions::check_webhook_status( $setting['status'] );

						    if( !$status ){
							    continue;
						    }
						    $index = str_replace('_order_bump_accepted','',$setting['conditions']);

						    if ( 'any' === $index ) {
							    $is_ob_key_matched = true;
							    $_item_id          = $item[ 'variation_id' ] ? $item[ 'variation_id' ] : $item[ 'product_id' ];
						    }
						    elseif( !empty( $is_ob_settings[ $index ][ 'product' ] ) ) {
							    $proudct_id = $is_ob_settings[ $index ][ 'product' ];
							    $_item_id          = $item[ 'variation_id' ] ? $item[ 'variation_id' ] : $item[ 'product_id' ];
							    $is_ob_key_matched = Wpfnl_Pro_Webhook_Functions::ob_key_matching( $proudct_id, $_item_id );
						    }

						    if( $is_ob_key_matched ){
							    $is_matched = Wpfnl_Pro_Webhook_Functions::match_conditions( $index.'_order_bump_accepted', $setting['conditions'] );

							    if( $is_matched ){
								    $class = Wpfnl_Pro_Webhook_Functions::get_class_instance( 'orderbump', $setting, [], $order_id, '', 'accepted', $_item_id );
								    if( $class ){
									    $class->send_data();
								    }
							    }
						    }
					    }
				    }
			    }
		    }
	    }

	    if( !empty( $settings )){
		    foreach( $settings as $setting ){
			    $status = Wpfnl_Pro_Webhook_Functions::check_webhook_status( $setting['status'] );
			    if( !$status ){
				    continue;
			    }
			    if ( !empty( $setting['conditions'] ) ) {
				    if ( 'wpfnl_wc_checkout_order_placed' === $setting['conditions'] ) {
					    $class = Wpfnl_Pro_Webhook_Functions::get_class_instance( 'checkout', $setting, [], $order_id, '', 'not Accepted' );
					    if ( $class ) {
						    $class->send_data();
					    }
				    }

				    if ( !empty( $is_ob_settings[ $index ][ 'product' ] ) ) {
					    $index       = str_replace( '_order_bump_rejected', '', $setting[ 'conditions' ] );
					    $product_id  = $is_ob_settings[ $index ][ 'product' ];
					    $cookie_name = 'wpfunnels_automation_data';
					    $cookie      = isset( $_COOKIE[ $cookie_name ] ) ? json_decode( wp_unslash( $_COOKIE[ $cookie_name ] ), true ) : [];

					    if ( isset( $cookie[ 'ob_accepetd_products' ] ) && is_array( $cookie[ 'ob_accepetd_products' ] ) && !in_array( $product_id, $cookie[ 'ob_accepetd_products' ] ) ) {
						    $is_matched = Wpfnl_Pro_Webhook_Functions::match_conditions( $index . '_order_bump_rejected', $setting[ 'conditions' ] );

						    if ( $is_matched ) {
							    $class = Wpfnl_Pro_Webhook_Functions::get_class_instance( 'orderbump', $setting, [], $order_id, '', 'not Accepted', $product_id );
							    if ( $class ) {
								    $class->send_data();
							    }
						    }
					    }
				    }
			    }
		    }
	    }
    }


    /**
     * offer event control 
     * @param $order
     * @param $offer_product
     * @param $offer_status
     * 
     */
    private function offer_event_control( $order, $offer_product , $offer_status ){
        $step_id            = isset($offer_product['step_id']) ? $offer_product['step_id'] : '';
        $funnel_id          = get_post_meta($step_id, '_funnel_id', true);
        $is_lms = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );
        
        $step_type          = get_post_meta($step_id, '_step_type', true);
        
        $settings           =  Wpfnl_Pro_Webhook_Functions::get_webhook_settings( $funnel_id );
        if( !empty( $settings )){
            foreach( $settings as $key=>$setting ){
                $status = Wpfnl_Pro_Webhook_Functions::check_webhook_status( $setting['status'] );
                if( !$status ){
                    continue;
                }
             
                $is_matched = Wpfnl_Pro_Webhook_Functions::match_conditions( $step_type.'_'.$offer_status, $setting['conditions'] );

                if( $is_matched || "{$step_id}_{$step_type}_$offer_status" === $setting['conditions'] ){
	                if ( 'lms' === $is_lms ) {
		                $order_id = '';
	                }
	                else {
		                $order_id = $order->get_id();
	                }
                    $class = Wpfnl_Pro_Webhook_Functions::get_class_instance( 'offer', $setting, [], $order_id, $offer_product , $offer_status );
                    if( $class ){
                        $class->send_data(); 
                    }
                }
            }
        }
    }


    /**
     * Create offer class instance
     */
    private function create_offer_class_instance( $is_ob_key_matched, $step_type, $setting, $cookie_data, $status, $step_id){
        if( $is_ob_key_matched ){
            $offer_product = Wpfnl_lms_learndash_functions::get_course_details($step_id);
            $product_id = '';
            if( is_array($offer_product) ){
                if( isset($offer_product['id']) ){
                    $product_id = $offer_product['id'];
                }
            }

            if( $product_id ){
                $class = Wpfnl_Pro_Webhook_Functions::get_class_instance( 'offer', $setting, [], '' , '', $status, $product_id ,'lms' , $cookie_data , $step_id);
                if( $class ){
                    $class->send_data(); 
                }
            }
            
             
        }
    }


}