<?php

namespace WPFunnelsProWebHooks\Events;

use WPFunnelsProWebHooks\Functions\Wpfnl_Pro_Webhook_Functions;
use WPFunnelsPro\Wpfnl_Pro_functions;
use WPFunnels\Wpfnl_functions;
class Wpfnl_Pro_Webhook_Offer {
    
    protected $offer_product = [];

    protected $settings = [];

    protected $order_id = '';

    protected $offer_status = '';

    public function __construct( $settings , $order_id, $offer_product, $offer_status )
    {
        $this->settings      = $settings;
        $this->order_id      = $order_id;
        $this->offer_product = $offer_product;
        $this->offer_status = $offer_status;
    }


    

    /**
     * Get event name
     * 
     * @return String
     */
    public function get_event_name(){

        $step_id            = $this->offer_product['step_id'];
        $funnel_id          = get_post_meta($step_id, '_funnel_id', true);
        $step_type          = get_post_meta($step_id, '_step_type', true);
        return ucfirst($step_type).' '.ucfirst($this->offer_status);
    }



    /**
     * Send data to request url through webhook
     */
    public function send_data(){
        $request_url  = Wpfnl_Pro_Webhook_Functions::get_request_url( $this->settings );
        $request_args = $this->prepare_request_args( $this->settings );
        $response = wp_remote_request( $request_url, $request_args );
    }

    
    /**
     * Prepare request arguments
     * 
     * @param Array $settings
     * @return Array $request_args
     */
    public function prepare_request_args( $settings ){
        
        $request_body = $this->prepare_request_body( $this->settings , $this->order_id , $this->offer_product );
        $content_type = 'application/json';
        return Wpfnl_Pro_functions::prepare_common_request_args( $this->settings, $content_type, $request_body, $this->get_event_name(), home_url( '/' ) );
    } 


    /**
     * Prepare request body
     * 
     * @param Array $settings
     * @return Array $formatted_body
     */
    private function prepare_request_body( $settings , $order_id , $offer_product ){
        $type = $settings['request']['body']['type'];
        $funnel_type = 'wc';
        if( isset($offer_product['step_id']) && $offer_product['step_id'] ){
            $funnel_id = get_post_meta( $offer_product['step_id'], '_funnel_id', true );
            $funnel_type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );
        }


        if( 'lms' == $funnel_type ){
            $order = '';
        }else{
            $order = wc_get_order( $order_id ); 
        }
        
        $formatted_body = array();
       
        if( $type === 'all' ){
            $formatted_body = $this->prepare_all_body_fields( $order, $order_id, $offer_product );
        }else{
            $values = $settings['request']['body']['values'];
            $_body = array();
            $_body = $this->prepare_selected_body_fields( $order, $order_id, $offer_product );
            $formatted_body['Event Name']   = $this->get_event_name();
            foreach( $values as $value ){

                if( isset( $_body[$value['value']] )){
                    $formatted_body[$value['key']] = $_body[$value['value']];
                }

            }

        }

        return $formatted_body;
    }


    /**
     * Prepare request body field for type 'all'
     * 
     * @param $order, $order_id, $offer_product
     * @return $formatted_body
     */
    public function prepare_all_body_fields( $order, $order_id, $offer_product ){
        
        $formatted_body['Date']         = date("Y-m-d h:i");
        $formatted_body['Event Name']   = $this->get_event_name();
       
        if( isset($offer_product['step_id']) && $offer_product['step_id'] ){
            $funnel_id = get_post_meta($offer_product['step_id'],'_funnel_id', true);
            $type = get_post_meta($funnel_id,'_wpfnl_funnel_type', true);
           
            if( 'lms' === $type ){
                if( Wpfnl_functions::is_lms_addon_active() ){
                    $lms_formatted_body = \WPFunnels\lms\helper\Wpfnl_lms_learndash_functions::get_webhook_data( $offer_product );
                    
                    $formatted_body = array_merge($formatted_body,$lms_formatted_body);
                    return $formatted_body;
                }
                return [];
            }
        }
        // Product info
        $formatted_body['Product ID']       = $offer_product['id'];
        $formatted_body['Product Name']     = $offer_product['name'];
        $formatted_body['Product Quantity'] = $offer_product['qty'];
        
        if( $this->offer_status == 'accepted' ){
            // Order info
            $formatted_body['Order ID']     = $order_id;
            $formatted_body['Total Price']  = $order->get_total();
            $formatted_body['Order Status'] = $order->get_status();
        }else{
            $formatted_body['Product Price'] = $offer_product['args']['total'];
        }
        

        // payment info
        $formatted_body['Payment Method'] = $order->get_payment_method();
            
        // shipping info
        $formatted_body['Shipping Method'] = $order->get_shipping_method();
        
        // customer info
        $formatted_body['Customer Name']    =  $order->get_formatted_billing_full_name() ? $order->get_formatted_billing_full_name() : $order->get_formatted_shipping_full_name();
        $formatted_body['Customer Phone']   = $order->get_billing_phone() ? $order->get_billing_phone() : '';
        $formatted_body['Customer Email']   = $order->get_billing_email() ? $order->get_billing_email() : '';
        $formatted_body['Customer City']    = $order->get_billing_city() ? strip_tags($order->get_billing_city()) : strip_tags($order->get_shipping_city());
        $formatted_body['Customer Address'] = $order->get_billing_address_1() ? strip_tags($order->get_billing_address_1()) : strip_tags($order->get_shipping_address_1());
        

        return $formatted_body;
    }


    /**
     * Prepare request body field for type 'selected'
     * 
     * @param $order, $order_id, $offer_product
     * @return $formatted_body
     */
    public function prepare_selected_body_fields( $order, $order_id, $offer_product ){

        $formatted_body['Date']  = date("Y-m-d h:i");

        if( isset($offer_product['step_id']) && $offer_product['step_id'] ){
            $funnel_id = get_post_meta($offer_product['step_id'],'_funnel_id', true);
            $type = get_post_meta($funnel_id,'_wpfnl_funnel_type', true);

            if( 'lms' === $type ){
                if( Wpfnl_functions::is_lms_addon_active() ){
                    $lms_formatted_body = \WPFunnels\lms\helper\Wpfnl_lms_learndash_functions::get_webhook_selected_data( $offer_product );
                    $formatted_body = array_merge($formatted_body,$lms_formatted_body);
                    
                    return $formatted_body;
                }
                return [];
            }
        }
        // Product info
        $formatted_body['product_id']       = $offer_product['id'];
        $formatted_body['product_name']     = $offer_product['name'];
        $formatted_body['product_quantity'] = $offer_product['qty'];

        // Order info
        $formatted_body['order_id']     = $order_id;
        $formatted_body['order_total']  = $order->get_total();
        $formatted_body['order_status'] = $order->get_status();

        // payment info
        $formatted_body['payment_method'] = $order->get_payment_method();
            
        // shipping info
        $formatted_body['shipping_method'] = $order->get_shipping_method();
        
        // customer info
        $formatted_body['customer_name']    =  $order->get_formatted_billing_full_name() ? $order->get_formatted_billing_full_name() : $order->get_formatted_shipping_full_name();
        $formatted_body['customer_phone']   = $order->get_billing_phone() ? $order->get_billing_phone() : '';
        $formatted_body['customer_email']   = $order->get_billing_email() ? $order->get_billing_email() : '';
        $formatted_body['customer_city']    = $order->get_billing_city() ? strip_tags($order->get_billing_city()) : strip_tags($order->get_shipping_city());
        $formatted_body['customer_address'] = $order->get_billing_address_1() ? strip_tags($order->get_billing_address_1()) : strip_tags($order->get_shipping_address_1());
        

        return $formatted_body;
    }

}