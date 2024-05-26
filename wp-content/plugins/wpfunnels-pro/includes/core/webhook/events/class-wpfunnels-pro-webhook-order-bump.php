<?php

namespace WPFunnelsProWebHooks\Events;

use WPFunnelsProWebHooks\Functions\Wpfnl_Pro_Webhook_Functions;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;

class Wpfnl_Pro_Webhook_Orderbump {
   

    protected $settings = [];

    protected $order_id;

    protected $offer_status;

    protected $proudct_id;

    public function __construct( $settings, $order_id, $offer_status ,$proudct_id )
    {
      
        $this->settings     = $settings;
        $this->order_id     = $order_id;
        $this->offer_status = $offer_status;
        $this->proudct_id   = $proudct_id;

        
    }


    

    /**
     * Get event name
     * 
     * @return String
     */
    public function get_event_name(){
        return 'Order Bump '.ucfirst($this->offer_status);
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
        
        $request_body = $this->prepare_request_body( $this->settings , $this->order_id, $this->offer_status );
        $request_header = Wpfnl_Pro_Webhook_Functions::prepare_request_header($this->settings['request']['header']);
        $content_type = 'application/json';
        return Wpfnl_Pro_functions::prepare_common_request_args( $this->settings, $content_type, $request_body, $this->get_event_name(), home_url( '/' ) );
    } 


    /**
     * Prepare request body
     * @param Array $settings
     * @return Array $formatted_body
     */
    private function prepare_request_body( $settings , $order_id, $offer_status ){
        $type = $settings['request']['body']['type'];
        $order = wc_get_order( $order_id ); 
        $formatted_body = array();
       
        if( $type === 'all' ){
            $formatted_body = $this->prepare_all_body_fields( $order, $order_id, $offer_status );
        }else{
            $values = $settings['request']['body']['values'];
            $_body = array();
            $_body = $this->prepare_selected_body_fields( $order, $order_id, $offer_status );
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
    public function prepare_all_body_fields( $order, $order_id, $offer_status ){

        $formatted_body['Date']         = date("Y-m-d h:i");
        $formatted_body['Event Name']   = $this->get_event_name();

        if( $offer_status === 'accepted' ){
            
            foreach ( $order->get_items() as $item_id => $item ) {
                
                $item_id = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
              
                if( $item->get_meta('_wpfunnels_order_bump') === 'yes' && $item_id == $this->proudct_id ){

                    $formatted_body['Product ID']   = $item->get_product_id();
                    $formatted_body['Product Name'] = $item->get_name();
                    $formatted_body['Product Quantity']  = $item->get_quantity();
                    
                    $formatted_body['Order ID']     = $order_id;
                    $formatted_body['Total Price']  = $item->get_total();
                    $formatted_body['Order Status'] = $order->get_status();
                    
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

                }
            }
        }else{
           $funnel_id = Wpfnl_functions::get_funnel_id_from_order( $order_id );
           if( $funnel_id ){
                $steps = Wpfnl_functions::get_steps( $funnel_id );
                $key = array_search('checkout', array_column($steps, 'step_type'));
                if( $key !== false ){
                    $step_id = $steps[$key]['id'];
                    $ob_settings 	= get_post_meta($step_id, 'order-bump-settings', true);
                    foreach( $ob_settings as $settings ){
                        if( $settings['product'] == $this->proudct_id ){
                            $product	= wc_get_product( $settings['product'] );
                            $formatted_body['Product ID']   = $settings['product'];
                            $formatted_body['Product Name'] = $product ? ($product->get_name()) : '';
                            $formatted_body['Product Quantity']  =$settings['quantity'];

                            // customer info
                            $formatted_body['Customer Name']    =  $order->get_formatted_billing_full_name() ? $order->get_formatted_billing_full_name() : $order->get_formatted_shipping_full_name();
                            $formatted_body['Customer Phone']   = $order->get_billing_phone() ? $order->get_billing_phone() : '';
                            $formatted_body['Customer Email']   = $order->get_billing_email() ? $order->get_billing_email() : '';
                            $formatted_body['Customer City']    = $order->get_billing_city() ? strip_tags($order->get_billing_city()) : strip_tags($order->get_shipping_city());
                            $formatted_body['Customer Address'] = $order->get_billing_address_1() ? strip_tags($order->get_billing_address_1()) : strip_tags($order->get_shipping_address_1());
                        }
                    }
                    
 
                }
           }
        }
        return $formatted_body;
    }


    /**
     * Prepare request body field for type 'selected'
     * 
     * @param $order, $order_id, $offer_product
     * @return $formatted_body
     */
    public function prepare_selected_body_fields( $order, $order_id, $offer_status ){

        $formatted_body['Date'] = date("Y-m-d h:i");
        if( $offer_status === 'accepted' ){
            
            foreach ( $order->get_items() as $item_id => $item ) {
                if( $item->get_meta('_wpfunnels_order_bump') === 'yes' ){
                     // Product info
                    $formatted_body['product_id']       = $item->get_product_id();
                    $formatted_body['product_name']     = $item->get_name();
                    $formatted_body['product_quantity'] = $item->get_quantity();

                    // Order info
                    $formatted_body['order_id']     = $order_id;
                    $formatted_body['order_total']  = $item->get_total();
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
                    
                }
            }
        }else{
            $funnel_id = Wpfnl_functions::get_funnel_id_from_order( $order_id );
            if( $funnel_id ){
                $steps = Wpfnl_functions::get_steps( $funnel_id );
                $key = array_search('checkout', array_column($steps, 'step_type'));
                if( $key !== false ){
                    $step_id = $steps[$key]['id'];
                    
                    $ob_settings 	= get_post_meta($step_id, 'order-bump-settings', true);
                    foreach( $ob_settings as $settings ){
                        if( $settings['product'] == $this->proudct_id ){
                            $product	= wc_get_product( $settings['product'] );
                            $formatted_body['product_id']   = $settings['product'];
                            $formatted_body['product_name'] = $product ? ($product->get_name()) : '';
                            $formatted_body['product_quantity']  =$settings['quantity'];

                            // customer info
                            $formatted_body['customer_name']    =  $order->get_formatted_billing_full_name() ? $order->get_formatted_billing_full_name() : $order->get_formatted_shipping_full_name();
                            $formatted_body['customer_phone']   = $order->get_billing_phone() ? $order->get_billing_phone() : '';
                            $formatted_body['customer_email']   = $order->get_billing_email() ? $order->get_billing_email() : '';
                            $formatted_body['customer_city']    = $order->get_billing_city() ? strip_tags($order->get_billing_city()) : strip_tags($order->get_shipping_city());
                            $formatted_body['customer_address'] = $order->get_billing_address_1() ? strip_tags($order->get_billing_address_1()) : strip_tags($order->get_shipping_address_1());
                        }
                    }
                }
            }
        }
        return $formatted_body;
    }


}