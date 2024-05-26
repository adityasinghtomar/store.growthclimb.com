<?php

namespace WPFunnelsProWebHooks\Functions;

class Wpfnl_Pro_Webhook_Functions {


    /**
     * Get webhook settings by funnel Id
     * 
     * @param String $funnel_id
     * @return array
     */
    public static function get_webhook_settings( $funnel_id ){
        return get_post_meta( $funnel_id, '_wpfunnels_webhook_settings', true );
    }


    /**
     * Get all supported events
	 * 
	 * @return array
     */
    public static function get_all_supported_event(){
	    return [
		    'wpfnl_wc_checkout_order_placed' => __( 'After Checkout Order Placed', 'wpfnl-pro' ),
		    'upsell_accepted'                => __( 'Upsell Accepted (Any)', 'wpfnl-pro' ),
		    'upsell_rejected'                => __( 'Upsell Rejected (Any)', 'wpfnl-pro' ),
		    'downsell_accepted'              => __( 'Downsell Accepted (Any)', 'wpfnl-pro' ),
		    'downsell_rejected'              => __( 'Downsell Rejected (Any)', 'wpfnl-pro' ),
		    'optin_submitted_landing'        => __( 'After Optin Form Submitted', 'wpfnl-pro' ),
		    'optin_submitted_custom'         => __( 'After Optin Form Submitted', 'wpfnl-pro' )
	    ];
    } 


    /**
     * Get all supported request body fields for webhook
     * 
	 * @return Array $supported_fields
     */
    public static function get_all_body_fields(){

        $supported_fields = array(
            
            'product_id'        => 'Product ID',
            'product_name'      => 'Product Name',
            'product_quantity'  => 'Product Quantity',
        
            'order_id'      => 'Order ID',
            'order_total'   => 'Order Total',
            'order_status'  => 'Order Status',
        
            'payment_method'    => 'Payment Method',
            'shipping_method'   => 'Shipping Method',
         
            'customer_name'    => 'Customer Name',
            'customer_email'   => 'Customer Email',
            'customer_phone'   => 'Customer Phone',
            'customer_city'    => 'Customer City',
            'customer_address' => 'Customer Address',

            'first_name' => 'Opt-in first name',
            'last_name'  => 'Opt-in last name',
            'email'      => 'Opt-in email',
            'phone'      => 'Opt-in phone',

        );

        return $supported_fields;
    }





    /**
     * Get all request header name for webhook
     * 
	 * @return Array
     */
    public static function get_all_request_header_name(){

        return array(
	
			array(
				'label' => 'Accept',
				'value' => 'Accept',
			),
			array(
				'label' => 'Accept-Charset',
				'value' => 'Accept-Charset',
			),
			array(
				'label' => 'Accept-Encoding',
				'value' => 'Accept-Encoding',
			),
			array(
				'label' => 'Accept-Language',
				'value' => 'Accept-Language',
			),
			array(
				'label' => 'Accept-Datetime',
				'value' => 'Accept-Datetime',
			),
			array(
				'label' => 'Authorization',
				'value' => 'Authorization',
			),
			array(
				'label' => 'Cache-Control',
				'value' => 'Cache-Control',
			),
			array(
				'label' => 'Connection',
				'value' => 'Connection',
			),
			array(
				'label' => 'Cookie',
				'value' => 'Cookie',
			),
			array(
				'label' => 'Content-Length',
				'value' => 'Content-Length',
			),
			array(
				'label' => 'Content-Type',
				'value' => 'Content-Type',
			),
			array(
				'label' => 'Date',
				'value' => 'Date',
			),
			array(
				'label' => 'Expect',
				'value' => 'Expect',
			),
			array(
				'label' => 'Forwarded',
				'value' => 'Forwarded',
			),
			array(
				'label' => 'From',
				'value' => 'From',
			),
			array(
				'label' => 'Host',
				'value' => 'Host',
			),
			array(
				'label' => 'If-Match',
				'value' => 'If-Match',
			),
			array(
				'label' => 'If-Modified-Since',
				'value' => 'If-Modified-Since',
			),
			array(
				'label' => 'If-None-Match',
				'value' => 'If-None-Match',
			),
			array(
				'label' => 'If-Range',
				'value' => 'If-Range',
			),
			array(
				'label' => 'If-Unmodified-Since',
				'value' => 'If-Unmodified-Since',
			),
			array(
				'label' => 'Max-Forwards',
				'value' => 'Max-Forwards',
			),
			array(
				'label' => 'Origin',
				'value' => 'Origin',
			),
			array(
				'label' => 'Pragma',
				'value' => 'Pragma',
			),
			array(
				'label' => 'Proxy-Authorization',
				'value' => 'Proxy-Authorization',
			),
			array(
				'label' => 'Range',
				'value' => 'Range',
			),
			array(
				'label' => 'Referer',
				'value' => 'Referer',
			),
			array(
				'label' => 'TE',
				'value' => 'TE',
			),
			array(
				'label' => 'User-Agent',
				'value' => 'User-Agent',
			),
			array(
				'label' => 'Upgrade',
				'value' => 'Upgrade',
			),
			array(
				'label' => 'Via',
				'value' => 'Via',
			),
			array(
				'label' => 'Warning',
				'value' => 'Warning',
			),
		);
    }


    /**
     * Get request url
     * 
     * @param Array $setting
     * @return String $url
     */
    public static function get_request_url( $setting ){
        return $setting['request']['url'];
    }


    /**
     * Prepare request url
     * 
     * @param Array $setting
     * @param String $order_id
     * @param String $event_name
	 * 
     * @return String $url
     */
    public static function prepare_request_body( $settings , $order_id, $event_name ){

        $body = array();
        $body['Event name'] =  $event_name;
        $order = wc_get_order($order_id);
        if( $settings['request']['body']['type'] === 'selected' ){
            foreach( $settings['request']['body']['values'] as $key => $value ){
                if( $key == 'order_id' ){
                    $body[$value] = $order_id;
                }elseif( $key === 'total_price' ){
                    $body[$value] = $order->get_total();
                }
            }
        }   
        return $body;
    }


	/**
	 * Prepare request header for webhook
	 * 
	 * @param Array $header
	 */
	public static function prepare_request_header( $headers ){

		$formatted_header = array();
		foreach( $headers as $header ){
			$formatted_header[$header['name']] = $header['value'];
		}

		return $formatted_header;
	}

	/**
	 * Order bump key matching
	 */
	public static function ob_key_matching( $product_id, $item_id ){
		return $product_id == $item_id;
	}






    /**
     * Check webhook status
     * 
     * @param String $status
     * @return Boolean
    */
    public static function check_webhook_status( $status ){
        if( $status === 'on' ){
            return true;
        }
        return false;
    }

    /**
     * Match conditions
     * 
     */
    public static function match_conditions( $value, $rule ){
        return $rule === $value;
    }

	
    /**
     * Get class instance
     */
    public static function get_class_instance( $event, $settings, $record = [], $order_id = '', $offer_product = '' , $offer_status = '', $proudct_id = '', $type = '' , $cookie_data = [], $step_id = '' ){
        
        $class_name = "WPFunnelsProWebHooks\\Events\\".'Wpfnl_Pro_Webhook_'.ucfirst($type).ucfirst($event);
	    if ( class_exists( ucfirst( $class_name ) ) ) {
		    if ( 'checkout' === $event ) {
			    return new $class_name( $settings, $order_id );
		    }
		    elseif ( $event === 'offer' ) {
			    if ( !$type ) {
				    return new $class_name( $settings, $order_id, $offer_product, $offer_status );
			    }
			    else {
				    if ( $cookie_data ) {
					    return new $class_name( $settings, $cookie_data, $offer_status, $proudct_id, $step_id );
				    }
			    }
		    }
		    else if ( $event === 'optin' ) {
			    return new $class_name( $settings, $record );
		    }
		    elseif ( $event === 'orderbump' ) {
			    if ( !$type ) {
				    return new $class_name( $settings, $order_id, $offer_status, $proudct_id );
			    }
			    else {
				    if ( $cookie_data ) {
					    return new $class_name( $settings, $cookie_data, $offer_status, $proudct_id );
				    }
			    }
		    }
	    }
	    return false;
    }

	/**
	 * Get the upsell and downsell IDs, titles, and types associated with a funnel.
	 *
	 * @param int $funnel_id The ID of the funnel.
	 *
	 * @return array An array containing the upsell and downsell post IDs, titles, and types.
	 * @since 1.9.12
	 */
	public static function get_upsell_ids( $funnel_id ) {
		global $wpdb;
		$query = $wpdb->prepare( 'SELECT posts.ID AS id, posts.post_title AS title, postmeta1.meta_value AS type FROM %i AS posts ', [ $wpdb->posts ] );
		$query .= $wpdb->prepare( 'JOIN %i AS postmeta1 ', [ $wpdb->postmeta ] );
		$query .= 'ON posts.ID = postmeta1.post_id ';
		$query .= $wpdb->prepare( 'JOIN %i AS postmeta2 ', [ $wpdb->postmeta ] );
		$query .= 'ON posts.ID = postmeta2.post_id ';
		$query .= $wpdb->prepare( 'WHERE (postmeta1.meta_key = %s AND (postmeta1.meta_value = %s ', [ '_step_type', 'upsell' ] );
		$query .= $wpdb->prepare( 'OR postmeta1.meta_value = %s)) ', [ 'downsell' ] );
		$query .= $wpdb->prepare( 'AND (postmeta2.meta_key = %s AND postmeta2.meta_value = %d) ', [ '_funnel_id', $funnel_id ] );
		return $wpdb->get_results( $query, ARRAY_A );
	}
}