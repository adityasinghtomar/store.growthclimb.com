<?php


namespace WPFunnels\Rest\Controllers;


use WP_REST_Server;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;
use GuzzleHttp\Exception\GuzzleException;


class AutomationController extends Wpfnl_REST_Controller {

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
    protected $rest_base = 'automation';


    /**
     * Order by property, used in the cmp function.
     *
     * @var string
     */
    private $order_by = '';


    /**
     * Order property, used in the cmp function.
     *
     * @var string
     */
    private $order = '';


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
     * register rest routes
     *
     * @since 1.0.0
     */
    public function register_routes() {

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/(?P<funnel_id>\d+)', array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'get_automation_data' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema(WP_REST_Server::READABLE),
                )
            )
        );
        
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/get_data_by_index/(?P<funnel_id>\d+)', array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'get_automation_data_by_index' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema(WP_REST_Server::READABLE),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/get_crm_data/(?P<funnel_id>\d+)', array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'get_crm_data' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema(WP_REST_Server::READABLE),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/update_status/(?P<funnel_id>\d+)', array(
                array(
                    'methods'               => 'POST',
                    'callback'              => array( $this, 'update_status' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args' => array(
                        'funnel_id' => array(
                            'description'   => __('Funnel ID.', 'wpfnl-pro'),
                            'type'          =>   'string',
                        )
                    ),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/update_trigger_status/(?P<funnel_id>\d+)', array(
                array(
                    'methods'               => 'POST',
                    'callback'              => array( $this, 'update_trigger_status' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args' => array(
                        'funnel_id' => array(
                            'description'   => __('Funnel ID.', 'wpfnl-pro'),
                            'type'          =>   'string',
                        )
                    ),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/save_data/(?P<funnel_id>\d+)', array(
                array(
                    'methods'               => 'POST',
                    'callback'              => array( $this, 'save_automation_data' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args' => array(
                        'funnel_id' => array(
                            'description'   => __('Funnel ID.', 'wpfnl-pro'),
                            'type'          =>   'string',
                        )
                    ),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/wpfnl-test-zapier/(?P<funnel_id>\d+)', array(
                array(
                    'methods'               => 'POST',
                    'callback'              => array( $this, 'wpfnl_test_zapier' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args' => array(
                        'funnel_id' => array(
                            'description'   => __('Funnel ID.', 'wpfnl-pro'),
                            'type'          =>   'string',
                        )
                    ),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/save_name/(?P<funnel_id>\d+)', array(
                array(
                    'methods'               => 'POST',
                    'callback'              => array( $this, 'save_automation_name' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args' => array(
                        'funnel_id' => array(
                            'description'   => __('Funnel ID.', 'wpfnl-pro'),
                            'type'          =>   'string',
                        )
                    ),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/save_crm', array(
                array(
                    'methods'               => 'POST',
                    'callback'              => array( $this, 'save_crm' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args' => array(
                        'funnel_id' => array(
                            'description'   => __('Funnel ID.', 'wpfnl-pro'),
                            'type'          =>   'string',
                        )
                    ),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/get_crm', array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'get_crm' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema(WP_REST_Server::READABLE),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/delete/(?P<funnel_id>\d+)', array(
                array(
                    'methods'               => 'DELETE',
                    'callback'              => array( $this, 'delete_automation' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args' => array(
                        'funnel_id' => array(
                            'description'   => __('Funnel ID.', 'wpfnl-pro'),
                            'type'          =>   'string',
                        )
                    ),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/delete/(?P<funnel_id>\d+)', array(
                array(
                    'methods'               => 'POST',
                    'callback'              => array( $this, 'delete_automation' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/get_mailchimp_tags', array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'get_mailchimp_tags' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema(WP_REST_Server::READABLE),
                )
            )
        );

    }

    /**
     * Get Automation data from DB by funnel id
     *
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function get_automation_data( \WP_REST_Request $request ) {

        $funnel_id              = $request['funnel_id'];
        $is_automation_enabled  = get_post_meta( $funnel_id, 'is_automation_enabled', true );
        $automation_data        = get_post_meta( $funnel_id, 'funnel_automation_data', true );
        if( !$automation_data ) {
            $automation_data = array();
        }
        $response               = array(
            'status'                => 'success',
            'is_automation_enabled' => $is_automation_enabled,
            'automation_data'       => $automation_data,
        );
        return rest_ensure_response( $response );
    }

    /**
     * Get automation data by funnel id from DB and return data by index
     *
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function get_automation_data_by_index( \WP_REST_Request $request ) {

        $funnel_id              = $request['funnel_id'];
        $index                  = $request['index'];

        $is_automation_enabled  = get_post_meta( $funnel_id, 'is_automation_enabled', true );
        $automation_data        = get_post_meta( $funnel_id, 'funnel_automation_data', true );
        if( !$automation_data ) {
            $automation_data = array();
        }else{
            $automation_data = $automation_data[$index];
        }
        $response               = array(
            'status'                => 'success',
            'is_automation_enabled' => $is_automation_enabled,
            'name'                  => $automation_data['name'],
            'crm'                   => $automation_data['crm'],
            'automation_data'       => $automation_data['triggers'],
        );
        return rest_ensure_response( $response );
    }

    /**
     * get all events for automation from supported CRM
     * 
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function get_crm_data( \WP_REST_Request $request ){

        $funnel_id  = isset($request['funnel_id'])  ? $request['funnel_id'] : '';
        $crm_name   = isset($request['crm_name'])   ? $request['crm_name']  : 'fluent_crm';
        $events     = $this->get_events( $funnel_id );
        $supported_crm = Wpfnl_Pro_functions::get_supported_crm();
        $lists = array();
        $tags = array();
        foreach( $supported_crm as $key => $crm ){
            if( $key == $crm_name ){
                $crm_class   = "WPFunnelsPro\\Integrations\\".$crm['class_name'];
                if (class_exists($crm_class)) {
                    $crm_object  = $crm_class::getInstance();
                    if($crm_object->is_connected()){
                        $lists =  $crm_object->get_crm_contact_lists();
                        $tags  =  $crm_object->get_crm_contact_tags();
                    }
                }
            }
        }

 
        $data  = array(
            'events' => $events,
            'lists'  => $lists,
            'tags'   => $tags,
        );

        return rest_ensure_response( $data );
    }

    /**
     * get all events for automation from funnel steps
     * 
     * @param $funnel_id
     * @return $events
     * 
     */
    private function get_events( $funnel_id ){

        // $get_steps  = Wpfnl_functions::get_steps( $funnel_id );
        $get_steps  = get_post_meta( $funnel_id, '_steps', true );
        $type = get_post_meta($funnel_id,'_wpfnl_funnel_type',true);
        $events = array();
        if( !empty( $get_steps ) ){
            foreach( $get_steps as $key=>$step ){
                if( isset( $step['step_type'], $step['name'] ) ){
                    if( 'landing' === $step['step_type'] || 'custom' === $step['step_type'] ){ 
                        $events['cta_clicked_'.$step['step_type']] = array(
                            'value'     => 'CTA Triggered ('.$step['name'].')',
                            'stepID'    => '',
                            'stepName'    => '',
                            'type'      => 'default',
                        );
    
                        $events['after_optin_submit_'.$step['step_type']] = array(
                            'value'     => 'After Opt-in Form Submit ('.$step['name'].')',
                            'stepID'    => '',
                            'stepName'    => '',
                            'type'      => 'default',
                        );
                        
                    } elseif( $step['step_type'] === 'checkout' ) {
                        if( 'lms'  === $type ){
                            $events['main_order_accepted_enrolled']  = array(
                                'value'     =>'Main Order Accepted',
                                'stepID'    => '',
                                'stepName'    => '',
                                'type'      => 'default',
                            );
    
                            
                        }
                       
                        $events['main_order_accepted']  = array(
                            'value'     => $type === 'lms' ? 'Main Order Accepted(new enrollment only)' : 'Main Order Accepted',
                            'stepID'    => '',
                            'stepName'    => '',
                            'type'      => 'default',
                        );
    
                        $ob_settings 	= get_post_meta($step['id'], 'order-bump-settings', true);
                       
                        if( !empty($ob_settings) ){
                            $events['any_orderbump_accepted'] = array(
                                'value'     => 'Any Order Bump Accepted',
                                'stepID'    => '',
                                'stepName'    => '',
                                'type'      => 'default',
                            );
                            
                            foreach($ob_settings as $index=>$settings){
                                if( $settings['isEnabled'] || $settings['isEnabled'] === 'yes' ){
                                    if( 'lms'  === $type ){
                                        $events[(int)($index+1).'_orderbump_accepted_enrolled'] = array(
                                            'value'     => ucwords($settings['name']).'(#'.(int)($index+1).') Accepted',
                                            'stepID'    => '',
                                            'stepName'    => '',
                                            'type'      => 'default',
                                        );
                                    }
                                    $events[(int)($index+1).'_orderbump_accepted'] = array(
                                        'value'     => $type === 'lms' ? ucwords($settings['name']).'(#'.(int)($index+1).') Accepted(new enrollment only)' : ucwords($settings['name']).'(#'.(int)($index+1).') Accepted',
                                        'stepID'    => '',
                                        'stepName'    => '',
                                        'type'      => 'default',
                                    );
                                    $events[(int)($index+1).'_orderbump_not_accepted'] = array(
                                        'value'     => ucwords($settings['name']).'(#'.(int)($index+1).') Not Accepted',
                                        'stepID'    => '',
                                        'stepName'    => '',
                                        'type'      => 'default',
                                    );
                                }
                            }
                        }
                        
                    } elseif ( $step['step_type'] === 'upsell' || $step['step_type'] === 'downsell' ) {
    
                        if( 'lms'  === $type ){
                            $events[$step['step_type'].'_accepted_enrolled_'.$step['id']]  = array(
                                'value'     => ucfirst($step['step_type']).' Accepted ('.$step['name'].')',
                                'stepID'     => $step['id'],
                                'stepName'  => $step['name'],
                                'type'      => 'offer',
                            );
                        }
                        $events[$step['step_type'].'_accepted_'.$step['id']]  = array(
                            'value'     => $type === 'lms' ? ucfirst($step['step_type']).' Accepted ('.$step['name'].')(new enrollment only)' : ucfirst($step['step_type']).' Accepted ('.$step['name'].')',
                            'stepID'     => $step['id'],
                            'stepName'  => $step['name'],
                            'type'      => 'offer',
                        );
                        $events[$step['step_type'].'_rejected_'.$step['id']]  = array(
                            'value'     => ucfirst($step['step_type']).' Rejected ('.$step['name'].')',
                            'stepID'    => $step['id'],
                            'stepName' => $step['name'],
                            'type'      => 'offer',
                        );
                    }
                }
            }
        }
        return $events;
    }


    /**
     * update funnel automation status
     * 
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     * 
     */
    public function update_status( \WP_REST_Request $request ){

        $funnel_id  = $request['funnel_id']     ? $request['funnel_id'] : '';
        $data       = $request['data'];
        
        update_post_meta( $funnel_id, 'is_automation_enabled', $data );
        $response = array(
            'status'    => 'success',
            'msg'       => 'Update automation status successfully',
            'data'      =>  $data,
        );
        return rest_ensure_response( $response );
    }
    
    /**
     * update trigger status
     * 
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     * 
     */
    public function update_trigger_status( \WP_REST_Request $request ){

        $funnel_id  = $request['funnel_id'] ? $request['funnel_id'] : '';
        $index      = $request['index'] ? $request['index'] : 0;
        $data       = $request['data'];
        
        $automations = get_post_meta( $funnel_id, 'funnel_automation_data', true );

        if( !empty($automations) ){
            $automations[$index]['status'] = $data;
            update_post_meta( $funnel_id, 'funnel_automation_data', $automations );
            $response = array(
                'status'    => 'success',
                'msg'       => 'Update automation status successfully',
                'data'      => $data
            );
        } else {
            $response = array(
                'status'    => 'fail',
                'data'      => 'Automation not found',
            );
        }
        return rest_ensure_response( $response );
    }

    /**
     * Save automation data to postmeta
     * 
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function save_automation_data( \WP_REST_Request $request ){

        $funnel_id  = $request['funnel_id'] ? $request['funnel_id'] : '';
        $data       = array();
        $data       = $request['data'];
        $index      = $request['index'];
        
        $previous_data = get_post_meta( $funnel_id, 'funnel_automation_data', true );
      
        if( !empty($previous_data ) ) {
            $previous_data[$index]  =  $data;
            update_post_meta( $funnel_id, 'funnel_automation_data', $previous_data );
        } else {
            $previous_data = array();
            $previous_data[$index]  =  $data;
            update_post_meta( $funnel_id, 'funnel_automation_data', $previous_data );
        }

        $response = array(
            'status' => 'success',
            'message' => 'Saved Successfully',
        );
        return rest_ensure_response( $response );
    }

    /**
     * Save automation data to postmeta
     * 
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function wpfnl_test_zapier( \WP_REST_Request $request ){
        $url = isset ($request['data']['triggers'][0][0]['tag']) ? $request['data']['triggers'][0][0]['tag'] : '';

        $client = new \WPFunnelsPro\Integrations\GuzzleHttp\Client();

        if ( $url ){

            $dummy_data = [
                                'ad_tracking'         => '',
                                'email'               => '',
                                'name'                => '',
                                'phone_number'        => '',
                                'billing_email'       => '',
                                'billing_first_name'  => '',
                                'billing_last_name'   => '',
                                'billing_phone'       => '',
                                'product_name_1'      => '',
                                'product_price_1'     => '',
                                'product_name_2'      => '',
                                'product_price_2'     => '',
                            ];
            $dummy_data = json_encode($dummy_data);

            $response = $client->request('POST', $url, [
                                'headers' => [
                                    'Content-Type'      => 'application/json',
                                    'Accept'            => 'application/json'
                                ],
                                'body' => $dummy_data
                            ]);
            if( 200 == $response->getStatusCode() ){
                $result = json_decode($response->getBody()->getContents());
                return $result;
            } 
        } 
        $response = array(
            'status' => 'failed',
            'message' => 'Failed to sent data',
        );
        return rest_ensure_response( $response );
        
    }
    
    
    /**
     * Save automation data to postmeta
     * 
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function save_automation_name( \WP_REST_Request $request ){

        $funnel_id  = $request['funnel_id'] ? $request['funnel_id'] : '';
        $data       = $request['data'];
        $index      = $request['index'];

        $previous_data = get_post_meta( $funnel_id, 'funnel_automation_data', true );
        
        if( !empty($previous_data ) ) {
            if(isset( $previous_data[$index]) ){
                $previous_data[$index]['name']  =  $data;
                update_post_meta( $funnel_id, 'funnel_automation_data', $previous_data );
                $response = array(
                    'status' => 'success',
                    'message' => 'Saved Successfully',
                );
            }else{
                $response = array(
                    'status' => 'fail',
                    'message' => 'No integrations were created. Please choose a CRM and create integrations.',
                );
            }
            
        }else {
            $response = array(
                'status' => 'fail',
                'message' => 'No integrations were created. Please choose a CRM and create integrations.',
            );
        }

        
        return rest_ensure_response( $response );
    }


    /**
     * Save CRM to option table
     */
    public function save_crm( \WP_REST_Request $request ){
        
        $crm = array(
            'fluent_crm' => 'Fluent CRM'
        );
        if( $crm ) {
            update_option( '_wpfunnels_supported_crm', $crm, 'yes' );
            $response['data'] = 'save crm successfully';
        }
        $response['status'] = 'success';
        return rest_ensure_response( $response );
    }

    /**
     * get crm name from CRM
     */
    public function get_crm( \WP_REST_Request $request ){
        
        $supported_crm = Wpfnl_Pro_functions::get_supported_crm();
        $connected_crm = array();
        
        foreach( $supported_crm as $key => $crm ){
            $crm_class   = "WPFunnelsPro\\Integrations\\".$crm['class_name'];
            if (class_exists($crm_class)) {
                $crm_object  = $crm_class::getInstance();
                if($crm_object->is_connected()){
                    $connected_crm[$key] =  $crm_object->get_name();
                }
            }
            
        }
        return rest_ensure_response( $connected_crm );
    }

    /**
     * Delete automation
     * 
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function delete_automation( \WP_REST_Request $request ){

        $funnel_id  = isset($request['funnel_id']) ? $request['funnel_id'] : null;
        $ids        = isset($request['ids']) ? $request['ids'] : array();

        if( $funnel_id ){
            $data = get_post_meta( $funnel_id, 'funnel_automation_data', true );
            foreach($ids as $id){
                if( isset($data[$id]) ){
                    unset($data[$id]);
                }
            }
            update_post_meta( $funnel_id, 'funnel_automation_data', $data );
            $response = array(
                'status' => 'success',
                'data' => 'Delete successfully',
            );
        } else {
            $response = array(
                'status' => 'fail',
                'data' => 'Data not found',
            );
        }
        return rest_ensure_response( $response );
    }
    
    
    /**
     * Delete automation
     * 
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function get_mailchimp_tags( \WP_REST_Request $request ){

        $response = [];
        
        if( isset($request['data']['list']) ){
            $list_data = $request['data'];
            if( Wpfnl_functions::is_integrations_addon_active() ){
                $response = \WPFunnelsPro\Integartions\Mailchimp\Wpfnl_Integartion_Mailchimp_functions::get_mailchimp_tags( $list_data );
            }
        }
        return rest_ensure_response( $response );
    }

}