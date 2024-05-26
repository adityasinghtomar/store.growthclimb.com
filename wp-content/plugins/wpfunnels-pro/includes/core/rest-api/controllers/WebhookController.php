<?php

namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPFunnels\Rest\Controllers\Wpfnl_REST_Controller;
use WPFunnelsProWebHooks\Functions\Wpfnl_Pro_Webhook_Functions;
use WPFunnels\Wpfnl_functions;

class WebhookController extends Wpfnl_REST_Controller {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wpfunnels/v1';


    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'webhook';

    /**
     * Makes sure the current user has access to READ the settings APIs.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|boolean
     * @since  3.0.0
     */
    public function get_items_permissions_check($request)
    {
        return true;
        if ( !Wpfnl_functions::wpfnl_rest_check_manager_permissions('settings') ) {
            return new \WP_Error('wpfunnels_rest_cannot_get', __('Sorry, you cannot list resources.', 'wpfnl'), array('status' => rest_authorization_required_code()));
        }
        return true;
    }


    /**
     * check if user has valid permission
     *
     * @param $request
     * @return bool|WP_Error
     * @since 1.0.0
     */
    public function update_items_permissions_check($request)
    {   
        return true;
        if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'steps', 'edit' )) {
            return new WP_Error('wpfunnels_rest_cannot_edit', __('Sorry, you cannot edit this resource.', 'wpfnl'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }



    /**
     * register rest routes
     *
     * @since 1.0.0
     */
    public function register_routes() {
    
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<funnel_id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [
                    $this,
                    'get_settings'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
                
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/get-supported-settings/(?P<funnel_id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [
                    $this,
                    'get_supported_settings'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
                
            ],
        ]);
        
        register_rest_route($this->namespace, '/' . $this->rest_base . '/delete-webhook/(?P<funnel_id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [
                    $this,
                    'delete_webhook'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/update-webhook-status/(?P<funnel_id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [
                    $this,
                    'update_webhook_status'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);
        
        register_rest_route($this->namespace, '/' . $this->rest_base . '/get-supported-body-settings/(?P<funnel_id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [
                    $this,
                    'get_supported_body_settings'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
                
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/update-webhook/(?P<funnel_id>\d+)', [
            [
                'methods' => 'POST',
                'callback' => [
                    $this,
                    'save_or_update_webhook'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
                
            ],
        ]);
    }


    /**
     * Get webhook settings by funnel ID
     * 
     * @param \WP_REST_Request $request
     * @return Array $response
     */
    public function get_settings( \WP_REST_Request $request ){
       
        $response = $this->get_response_array();
        if( isset( $request['funnel_id'] ) && $request['funnel_id'] ){
            $funnel_id = $request['funnel_id'];
            $settings = Wpfnl_Pro_Webhook_Functions::get_webhook_settings( $funnel_id );
            if( !empty($settings) ){
                $response['status'] = true;
                $response['data'] = $settings;
            }
        }
        return rest_ensure_response( $response );
    }
    

    /**
     * get supported settings
     * 
     * @param \WP_REST_Request $request
     * @return Array $response
     */
    public function get_supported_settings( \WP_REST_Request $request ){
        if( isset($request['funnel_id']) ){
	        $offer_steps         = Wpfnl_Pro_Webhook_Functions::get_upsell_ids( $request[ 'funnel_id' ] );
	        $supported_events    = Wpfnl_Pro_Webhook_Functions::get_all_supported_event();
	        $request_body        = Wpfnl_Pro_Webhook_Functions::get_all_body_fields();
	        $request_header_name = Wpfnl_Pro_Webhook_Functions::get_all_request_header_name();
	        $response            = $this->get_response_array();
	        $steps               = Wpfnl_functions::get_steps( $request[ 'funnel_id' ] );
	        $type                = get_post_meta( $request[ 'funnel_id' ], '_wpfnl_funnel_type', true );
	        $is_landing          = false;
	        $is_checkout         = false;
	        $is_orderbump        = false;
	        $is_upsell           = false;
	        $is_downsell         = false;
	        $is_custom           = false;
	        foreach( $steps as $step ) {
		        if ( 'landing' === $step[ 'step_type' ] ) {
			        $is_landing                                    = true;
			        $supported_events[ 'optin_submitted_landing' ] = 'After Optin Form Submitted (' . $step[ 'name' ] . ')';
		        }
		        elseif ( 'custom' === $step[ 'step_type' ] ) {
			        $is_custom                                    = true;
			        $supported_events[ 'optin_submitted_custom' ] = 'After Optin Form Submitted (' . $step[ 'name' ] . ')';
		        }
		        elseif ( $step[ 'step_type' ] == 'checkout' ) {
			        $is_checkout = true;
			        $ob_settings = get_post_meta( $step[ 'id' ], 'order-bump-settings', true );
			        if ( is_array( $ob_settings ) && !empty( $ob_settings ) ) {
				        $is_orderbump = true;
			        }

		        }
		        elseif ( 'upsell' === $step[ 'step_type' ] ) {
			        $is_upsell = true;
		        }
		        elseif ( 'downsell' === $step[ 'step_type' ] ) {
			        $is_downsell = true;
		        }
	        }

	        if ( $is_orderbump ) {
		        if ( !empty( $ob_settings ) ) {
			        $supported_events[ 'any_order_bump_accepted' ] = 'Any Order Bump Accepted';
			        foreach( $ob_settings as $key => $settings ) {
				        if ( !empty( $settings[ 'isEnabled' ] ) && 'yes' === $settings[ 'isEnabled' ] ) {

					        if ( 'wc' === $type || !$type ) {
						        $supported_events[ $key . '_order_bump_accepted' ] = ucwords( $settings[ 'name' ] ) . ' (#' . ( (int)( $key ) + 1 ) . ') Accepted';
						        $supported_events[ $key . '_order_bump_rejected' ] = ucwords( $settings[ 'name' ] ) . ' (#' . ( (int)( $key ) + 1 ) . ') Not Accepted';
					        }
					        elseif ( 'lms' === $type ) {
						        $supported_events[ $key . '_order_bump_enrolled_accepted' ] = ucwords( $settings[ 'name' ] ) . ' (#' . ( (int)( $key ) + 1 ) . ') Accepted';
						        $supported_events[ $key . '_order_bump_accepted' ]          = ucwords( $settings[ 'name' ] ) . ' (#' . ( (int)( $key ) + 1 ) . ') Accepted( New enrollment only )';
						        $supported_events[ $key . '_order_bump_rejected' ]          = ucwords( $settings[ 'name' ] ) . ' (#' . ( (int)( $key ) + 1 ) . ') Not Accepted';
					        }
				        }
			        }
		        }
	        }

	        if ( $is_upsell || $is_downsell ) {
		        if ( is_array( $offer_steps ) && !empty( $offer_steps ) ) {
			        foreach( $offer_steps as $offer_step ) {
				        if ( isset( $offer_step[ 'id' ], $offer_step[ 'title' ], $offer_step[ 'type' ] ) ) {
					        if ( $is_upsell && 'upsell' === $offer_step[ 'type' ] ) {
						        $id                                          = $offer_step[ 'id' ];
						        $title                                       = $offer_step[ 'title' ];
						        $supported_events[ "{$id}_upsell_accepted" ] = "Upsell Accepted - [ {$title} ]";
						        $supported_events[ "{$id}_upsell_rejected" ] = "Upsell Rejected - [ {$title} ]";
					        }
					        elseif ( $is_downsell && 'downsell' === $offer_step[ 'type' ] ) {
						        $id                                            = $offer_step[ 'id' ];
						        $title                                         = $offer_step[ 'title' ];
						        $supported_events[ "{$id}_downsell_accepted" ] = "Downsell Accepted - [ {$title} ]";
						        $supported_events[ "{$id}_downsell_rejected" ] = "Downsell Rejected - [ {$title} ]";
					        }
				        }
			        }
		        }
	        }

	        if ( $type == 'lms' ) {
		        $supported_events[ 'upsell_enrolled_accepted' ]   = $supported_events[ 'upsell_accepted' ];
		        $supported_events[ 'upsell_accepted' ]            = $supported_events[ 'upsell_accepted' ] . '( New enrollment only )';
		        $supported_events[ 'downsell_enrolled_accepted' ] = $supported_events[ 'downsell_accepted' ];
		        $supported_events[ 'downsell_accepted' ]          = $supported_events[ 'downsell_accepted' ] . '( New enrollment only )';
	        }

            if( !$is_landing ){
                $supported_events = $this->unset_landing_events( $supported_events );
            }

            if( !$is_custom ){
                $supported_events = $this->unset_custom_events( $supported_events );
            }

            if( !$is_checkout ){
                $supported_events = $this->unset_checkout_events( $supported_events );
            }

            if( !$is_orderbump ){
                $supported_events = $this->unset_orderbump_events( $supported_events );
            }

            if( !$is_upsell ){
                $supported_events = $this->unset_upsell_events( $supported_events );
            }

            if( !$is_downsell ){
                $supported_events = $this->unset_downsell_events( $supported_events );
            }

            $settings = array();
            $settings['conditions']          = $supported_events;
            $settings['request_body']        = $request_body;
            $settings['request_header_name'] = $request_header_name;
            $response =array(
                'status' => true,
                'data'   => $settings
            );
        }
        
        return rest_ensure_response( $response );
    }


    /**
     * Unset landing event
     */
    private function unset_landing_events( $supported_events ){
        unset($supported_events['optin_submitted_landing']);
        return $supported_events;
    }
    
    /**
     * Unset custom event
     */
    private function unset_custom_events( $supported_events ){
        unset($supported_events['optin_submitted_custom']);
        return $supported_events;
    }

    /**
     * Unset checkout event
     */
    private function unset_checkout_events( $supported_events ){
	    unset( $supported_events[ 'wpfnl_wc_checkout_order_placed' ] );
	    unset( $supported_events[ 'order_bump_accepted' ] );
	    unset( $supported_events[ 'any_order_bump_accepted' ] );
	    unset( $supported_events[ 'order_bump_rejected' ] );
	    return $supported_events;
    }

    /**
     * Unset Order bump events
     */
    private function unset_orderbump_events( $supported_events ){
	    unset( $supported_events[ 'order_bump_accepted' ] );
	    unset( $supported_events[ 'any_order_bump_accepted' ] );
	    unset( $supported_events[ 'order_bump_rejected' ] );
	    return $supported_events;
    }

    /**
    * Unset upsell event
    */
    private function unset_upsell_events( $supported_events ){
        unset($supported_events['upsell_accepted']);
        unset($supported_events['upsell_enrolled_accepted']);
        unset($supported_events['upsell_rejected']);
        return $supported_events;
    }


    /**
     * Unset downsell event
    */
    private function unset_downsell_events( $supported_events ){
        unset($supported_events['downsell_accepted']);
        unset($supported_events['downsell_enrolled_accepted']);
        unset($supported_events['downsell_rejected']);
        return $supported_events;
    }


    /**
     * Get supported body settings
     * 
     * @param \WP_REST_Request $request
     * @return Array $response
     */
    public function get_supported_body_settings( \WP_REST_Request $request ){
        $response = $this->get_response_array();
        if( isset( $request['funnel_id'] ) && $request['funnel_id'] ){
            if( isset( $request['data']['selected_condition']) && $request['data']['selected_condition'] ){

                if( strpos($request['data']['selected_condition'], 'optin') !== false ){
                    $response['status'] = true;
                    $response['data'] = $this->get_optin_fields();
                }
				elseif( strpos( $request['data']['selected_condition'], 'order_bump') !== false
					|| strpos( $request['data']['selected_condition'], 'upsell') !== false
					|| strpos( $request['data']['selected_condition'], 'downsell') !== false
					|| 'wpfnl_wc_checkout_order_placed' === $request['data']['selected_condition']
				){
                    $response['status'] = true;
                    $response['data'] = $this->get_offer_fields();
                }
            }
        }
        return rest_ensure_response( $response );
    }


    /**
     * Get optin field
     * 
     * @return array $supported_fields
     */
    private function get_optin_fields(){
        $supported_fields = array(
            'first_name' => 'Opt-in first name',
            'last_name'  => 'Opt-in last name',
            'email'      => 'Opt-in email',
            'phone'      => 'Opt-in phone',
            'website'    => 'Opt-in website url',
            'message'    => 'Opt-in message',
        );
        return $supported_fields;
    }

    /**
     * Get optin field
     * 
     * @return array
     */
    private function get_offer_fields(){
	    return [
		    'product_id'       => 'Product ID',
		    'product_name'     => 'Product Name',
		    'product_quantity' => 'Product Quantity',
		    'order_id'         => 'Order ID',
		    'order_total'      => 'Order Total',
		    'order_status'     => 'Order Status',
		    'payment_method'   => 'Payment Method',
		    'shipping_method'  => 'Shipping Method',
		    'customer_name'    => 'Customer Name',
		    'customer_email'   => 'Customer Email',
		    'customer_phone'   => 'Customer Phone',
		    'customer_city'    => 'Customer City',
		    'customer_address' => 'Customer Address'
	    ];
    }

    /**
     * Delete webhook by index
     * 
     * @param \WP_REST_Request $request
     * @return Array $response
     */
    public function delete_webhook( \WP_REST_Request $request ){

        $response = $this->get_response_array();
        if( isset( $request['funnel_id'] ) && $request['funnel_id'] && isset( $request['data']['index'] ) && $request['data']['index'] !== false ){
            
            $funnel_id = $request['funnel_id'];
            $settings = Wpfnl_Pro_Webhook_Functions::get_webhook_settings( $funnel_id );
            if( !empty($settings) ){
                
                if( isset( $settings[$request['data']['index']] )){
                    unset($settings[$request['data']['index']]);
                    if( !empty($settings)){
                        update_post_meta( $request['funnel_id'], '_wpfunnels_webhook_settings', $settings );
                    }else{
                        delete_post_meta( $request['funnel_id'],'_wpfunnels_webhook_settings' );
                    }
                    
                    $response['status'] = true;
                    $response['data'] = $settings;
                }
            }
        }
        return rest_ensure_response( $response );
    }

    /**
     * Update or save webhook by index
     * 
     * @param \WP_REST_Request $request
     * @return Array $response
     */
    public function save_or_update_webhook( \WP_REST_Request $request ){

        $response = $this->get_response_array();
        
        if( isset( $request['funnel_id'] ) && $request['funnel_id'] ){
            $funnel_id = $request['funnel_id'];
            $settings = Wpfnl_Pro_Webhook_Functions::get_webhook_settings( $funnel_id );

            $data = $request['data']['content'];
            $data['id'] = mt_rand().date('hs');
            
            if( !empty($settings) ){
                if( isset($request['data']['index']) && $request['data']['index'] != 'false'){
                    
                    $settings[$request['data']['index']] = $data;
                    
                    update_post_meta( $funnel_id, '_wpfunnels_webhook_settings', $settings );
                    $response['status'] = true;
                    $response['data'] = 'Update successful';
                    $settings = get_post_meta( $funnel_id, '_wpfunnels_webhook_settings', true);
                }else{
                    $key = array_search($request['data']['content']['id'], array_column($settings, 'id'));
                    if( $key !== false ){
                        $settings[$key] = $data;
                        update_post_meta( $funnel_id, '_wpfunnels_webhook_settings', $settings );
                        $response['status'] = true;
                        $response['data'] = 'Update successful';
                    }else{
                     
                        array_push($settings,$data);
                        update_post_meta( $funnel_id, '_wpfunnels_webhook_settings', $settings );
                        $response['status'] = true;
                        $response['data'] = 'Update successful';
                    }
                }

            }else{
                $settings = array();
                array_push($settings,$data);
                update_post_meta( $funnel_id, '_wpfunnels_webhook_settings', $settings );
                $response['status'] = true;
                $response['data'] = 'Save successful';

            }
        }
        return rest_ensure_response( $response );
    }


    /**
     * Update webhook status  
     * 
     * @param \WP_REST_Request $request
     * @return Array $response
     */    
    public function update_webhook_status( \WP_REST_Request $request ){
        $response = $this->get_response_array();
        if( isset( $request['funnel_id'] ) && $request['funnel_id'] && isset( $request['data']['index'] ) && $request['data']['index'] !== false ){
            
            $funnel_id = $request['funnel_id'];
            $settings = Wpfnl_Pro_Webhook_Functions::get_webhook_settings( $funnel_id );
            if( !empty($settings) ){
                
                if( isset( $settings[$request['data']['index']] )){
                    $settings[$request['data']['index']]['status'] = $request['data']['status'];
                    update_post_meta( $request['funnel_id'], '_wpfunnels_webhook_settings', $settings );
                    $response['status'] = true;
                    $response['data'] = $settings;
                }
            }
        }
        return rest_ensure_response( $response );
    }

    /**
     * get common response message
     */
    private function get_response_array(){
        return  array(
            'status' => false,
            'data'   => 'Data not found'
        );
    }
}