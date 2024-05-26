<?php


namespace WPFunnels\Rest\Controllers;

use Mint\MRM\DataBase\Tables\AutomationMetaSchema;
use WP_REST_Server;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;
use WPFunnelsPro\MailMint\Automation;

class MintController extends Wpfnl_REST_Controller {

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
    protected $rest_base = 'mint/automation';


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
                    'callback'              => array( $this, 'get_automations' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema(WP_REST_Server::READABLE),
                )
            )
        );
        
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/get_automation/(?P<funnel_id>\d+)', array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'get_automation_by_id' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema(WP_REST_Server::READABLE),
                )
            )
        );

        
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/save_data', array(
                array(
                    'methods'               => 'POST',
                    'callback'              => array( $this, 'save_data' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args' => array(
                        'funnelId' => array(
                            'description'   => __('Funnel ID.', 'wpfnl-pro'),
                            'type'          =>   'integer',
                            'required'      => true
                        ),
                        'stepId' => array(
                            'description'   => __('Step ID.', 'wpfnl-pro'),
                            'type'          =>   'integer',
                            'required'      => true
                        ),
                        'data' => array(
                            'description'   => __('Automation steps', 'wpfnl-pro'),
                            'type'          =>   'array',
                            'required'      => true
                        ),
                        'trigger' => array(
                            'description'   => __('Automation trigger.', 'wpfnl-pro'),
                            'required'      => true
                        )
                    ),
                )
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/save_mail_data', array(
                array(
                    'methods'               => 'POST',
                    'callback'              => array( $this, 'save_mail_data' ),
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
            $this->namespace, '/' . $this->rest_base . '/get_mail_data/(?P<step_id>\d+)/(?P<index>\d+)', array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'get_mail_data' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema(WP_REST_Server::READABLE),
                )
            )
        );
        
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/get_mail_template_data/(?P<index>\d+)', array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'get_mail_template_data' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema(WP_REST_Server::READABLE),
                )
            )
        );
        
        
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/render_email_builder', array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'render_email_builder' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args'                  => $this->get_endpoint_args_for_item_schema(WP_REST_Server::READABLE),
                )
            )
        );
        
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/get_data', array(
                array(
                    'methods'               => 'GET',
                    'callback'              => array( $this, 'get_data' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                )
            )
        );
        
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/delete_data', array(
                array(
                    'methods'               => WP_REST_Server::EDITABLE,
                    'callback'              => array( $this, 'delete_data' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                    'args' => array(
                        'stepId' => array(
                            'description'   => __('Step ID.', 'wpfnl-pro'),
                            'required'      => true
                        )
                    ),
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
     * Save automation data to postmeta
     * 
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function save_automation_data( \WP_REST_Request $request ){

        $funnel_id  = $request['funnel_id'] ? $request['funnel_id'] : '';
        $step_id  = $request['step_id'] ? $request['step_id'] : '';
        $params = Wpfnl_functions::get_default_automation();
        try {
            $class_name = "Mint\\MRM\\Automation\AutomationModel";
            if( class_exists($class_name) ){
                $automation_id = $class_name::get_instance()->create_or_update( $params );
                if ( $automation_id ) {
                    $data = array(
                        'automation_id' => $automation_id,
                    );
                    update_post_meta( $step_id, '_wpfnl_autoamtion_id', $automation_id );
                    $this->update_meta( $automation_id, 'source', 'wpf' );
                    return $this->get_success_response( __( 'Automation has been saved successfully', 'mrm' ), 201, $data );
                }
                return $this->get_error_response( __( 'Failed to save', 'mrm' ), 400 );
            }
            return $this->get_error_response( __( 'Failed to save automation step', 'mrm' ), 400 );
		} catch ( Exception $e ) {
			return $this->get_error_response( __( 'Failed to save automation step', 'mrm' ), 400 );
		}
    }

    /**
     * Save automation data to postmeta.
     *
     * This method is responsible for saving automation-related data to the postmeta of a specific step within a funnel.
     *
     * @param \WP_REST_Request $request The REST request object containing parameters.
     *
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response The response indicating the status of the operation.
     * 
     * @since 2.0.0
     */
    public function save_data( \WP_REST_Request $request ){
        $required_params = array('funnelId','stepId','data', 'trigger');
        foreach ( $required_params as $param ) {
            if ( !$request->has_param($param) ) {
                return rest_ensure_response( $this->get_error_response( __( "Required parameter '$param' is missing.", 'wpfnl-pro' ), 400 ) );
            }
        }

        $stepId        = isset($request['stepId']) ? sanitize_text_field( $request['stepId'] ) : '';
        $funnelId        = isset($request['funnelId']) ? sanitize_text_field( $request['funnelId'] ) : '';
        $data        = isset($request['data']) ? $request['data'] : [];
        $trigger        = isset($request['trigger']) ? $request['trigger'] : [
            'value' => '',
            'title' => '',
        ];
        
        update_post_meta( $stepId, '_wpfnl_automation_steps', $data );
        
        update_post_meta( $stepId, '_wpfnl_automation_trigger', $trigger );
        $response = [
            'success' => true,
            'data' => $data,
        ];
        $mint_automation = new Automation();
        $automation_data = $mint_automation->prepare_automation_data( $funnelId, $stepId );
        
        if( !$automation_data ){
            return $this->get_success_response( __( 'Automation has been saved successfully but fail to prepare data for MailMint', 'wpfnl-pro' ), 201, $response );
        }
        $is_saved = $mint_automation->save_or_update_automation( $automation_data, $funnelId, $stepId );
        
        if( !$is_saved ){
            return $this->get_success_response( __( 'Automation has been saved successfully but fail to save from MailMint', 'wpfnl-pro' ), 201, $response );
        }

        return $this->get_success_response( __( 'Automation has been saved successfully', 'wpfnl-pro' ), 201, $response );
    }
    
    /**
     * Save automation data to postmeta.
     *
     * This method is responsible for saving automation-related data to the postmeta of a specific step within a funnel.
     *
     * @param \WP_REST_Request $request The REST request object containing parameters.
     *
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response The response indicating the status of the operation.
     * 
     * @since 2.0.0
     */
    public function delete_data( \WP_REST_Request $request ){
        $required_params = array('stepId');
        foreach ( $required_params as $param ) {
            if ( !$request->has_param($param) ) {
                return rest_ensure_response( $this->get_error_response( __( "Required parameter '$param' is missing.", 'wpfnl-pro' ), 400 ) );
            }
        }

        $stepId        = isset($request['stepId']) ? sanitize_text_field( $request['stepId'] ) : '';
       
        delete_post_meta( $stepId, '_wpfnl_automation_steps' );
        delete_post_meta( $stepId, '_wpfnl_automation_trigger' );
        $response = [
            'success' => true
        ];

        $mint_automation = new Automation();
        $maybe_deleted = $mint_automation->delete_automation( $stepId );
        
        if( !$maybe_deleted ){
            return $this->get_success_response( __( 'Automation has been deleted successfully but fail to delete from MailMint', 'wpfnl-pro' ), 201, $response );
        }
        delete_post_meta( $stepId, 'wpfnl_mint_automation_id' );
        return $this->get_success_response( __( 'Automation has been deleted successfully', 'wpfnl-pro' ), 201, $response );
    }
    
    
    /**
     * Save automation data to postmeta
     * 
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function save_mail_data( \WP_REST_Request $request ){
        $step_id  = $request['step_id'] ? $request['step_id'] : '';
        $index  = $request['index'] ? $request['index'] : 0;
        
        $logical_index  = $request['logicalIndex'] ? $request['logicalIndex'] : 0;
        $logic  = $request['logic'] ? $request['logic'] : '';

        try {
            if( $step_id ){
                $settings = [];
                $step_settings = get_post_meta( $step_id, '_wpfnl_automation_steps', true );
                if( !$logic ){
                    if( isset($step_settings[$index]['settings']['settings']) ){
                        $settings = $step_settings[$index]['settings'];
                    }
                    if( !$settings ){
                        $settings = [];
                    }
                    
                    $settings['settings']['message_data']['body']       = $request['email_body'] ?? '';
                    $settings['settings']['message_data']['json_body']  = $request['json_data'] ?? '';
                    $step_settings[$index]['settings'] = $settings;
                    
                    update_post_meta( $step_id, '_wpfnl_automation_steps', $step_settings );
                }else{
                   
                    if( isset($step_settings[$index]['settings']['settings'][$logic][$logical_index]['settings']['settings']) ){
                        $settings = $step_settings[$index]['settings']['settings'][$logic][$logical_index]['settings'];
                    }
                    if( !$settings ){
                        $settings = [];
                    }
                    $settings['settings']['message_data']['body']       = $request['email_body'] ?? '';
                    $settings['settings']['message_data']['json_body']  = $request['json_data'] ?? '';
                    $step_settings[$index]['settings']['settings'][$logic][$logical_index]['settings'] = $settings;
                    
                    update_post_meta( $step_id, '_wpfnl_automation_steps', $step_settings );
                }
                
                $funnel_id = get_post_meta( $step_id, '_funnel_id', true );
                $mint_automation = new Automation();
                $automation_data = $mint_automation->prepare_automation_data( $funnel_id, $step_id );
                if( $automation_data ){
                    $mint_automation->save_or_update_automation( $automation_data, $funnel_id, $step_id );
                }
                
            }
            return $this->get_success_response( __( 'Automation has been saved successfully', 'wpfnl' ), 201, [] );
		} catch ( \Exception $e ) {
			return $this->get_error_response( __( 'Failed to save automation step', 'wpfnl' ), 400 );
		}
    }


    /**
     * Save automation data to postmeta
     * 
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function get_mail_data( \WP_REST_Request $request ){
        $step_id  = $request['step_id'] ? $request['step_id'] : '';
        $index  = $request['index'] ? $request['index'] : 0;
        $logical_index  = $request['logicalIndex'] ? $request['logicalIndex'] : 0;
        $logic  = $request['logic'] ? $request['logic'] : '';

        try {
            $step_settings = get_post_meta( $step_id, '_wpfnl_automation_steps', true );
            if( !$logic ){
                if( isset($step_settings[$index]['settings']['settings']) ){
                    $settings = $step_settings[$index]['settings'];
                    if( $settings ){
                        $response = [
                            'json_data'     => isset($settings['settings']['message_data']['json_body']) ? $settings['settings']['message_data']['json_body'] : [],
                        ];
                        return $this->get_success_response( __( 'Automation has been saved successfully', 'mrm' ), 201, $response );
                    }
                }
            }else{
                if( isset($step_settings[$index]['settings']['settings'][$logic][$logical_index]['settings']['settings']) ){
                    $settings = $step_settings[$index]['settings']['settings'][$logic][$logical_index]['settings'];
                    if( $settings ){
                        $response = [
                            'json_data'     => isset($settings['settings']['message_data']['json_body']) ? $settings['settings']['message_data']['json_body'] : [],
                        ];
                        return $this->get_success_response( __( 'Automation has been saved successfully', 'mrm' ), 201, $response );
                    }
                }
            }
            return $this->get_error_response( __( 'Failed to get data', 'wpfnl' ), 400 );
		} catch ( \Exception $e ) {
			return $this->get_error_response( __( 'Failed to get data', 'wpfnl' ), 400 );
		}
    }
    
    /**
     * Save automation data to postmeta
     * 
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function get_mail_template_data( \WP_REST_Request $request ){
	    $index          = $request[ 'index' ] ?? null;
	    $saved_template = $request[ 'saved_template' ] ?? 'no';
        try {
			if ( 'yes' === $saved_template ) {
				$json_data = get_post_meta( $index, 'mailmint_email_template_json_content', true );
			}
			else {
				$templates = \Mint\MRM\Internal\Admin\EmailTemplates\DefaultEmailTemplates::get_default_templates();
				$json_data = $templates[ $index ][ 'json_content' ] ?? [];
			}

            return $this->get_success_response( __( 'Success', 'wpfnl-pro' ), 201, [ 'json_data' => $json_data ] );
		} catch ( \Exception $e ) {
			return $this->get_error_response( __( 'Failed to get data', 'wpfnl-pro' ), 400 );
		}
    }

    /**
     * Save automation data to postmeta
     * 
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function render_email_builder( \WP_REST_Request $request ){
        $url = admin_url().'admin.php?page=email-builder';
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $response = [
            'url'           => $url,
            'current_url'   => $current_url,
        ];
        return $this->get_success_response( __( 'Automation has been saved successfully', 'mrm' ), 201, $response );
        
    }


    /**
     * Get automation data from postmeta
     * 
     * @param \WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function get_data( \WP_REST_Request $request ){
        $required_params = array('stepId');

        foreach ( $required_params as $param ) {
            if ( !$request->has_param($param) ) {
                return rest_ensure_response( $this->get_error_response( __( "Required parameter '$param' is missing.", 'wpfnl-pro' ), 400 ) );
            }
        }
        $stepId        = isset($request['stepId']) ? $request['stepId'] : '';
        $stepTitle     = get_the_title( $stepId );
        $steps      = get_post_meta( $stepId, '_wpfnl_automation_steps', true );
        $trigger    = get_post_meta( $stepId, '_wpfnl_automation_trigger', true );
        if( !$steps || !is_array($steps) ){
            $steps = [];
        }
        if( isset($trigger['trigger']) ){
            if( 'cta' === $trigger['trigger'] ){
                $trigger['value'] = 'wpf_cta_trigger';
                $trigger['title'] = 'CTA Triggered';
            }elseif( 'optin' === $trigger['trigger'] ){
                $trigger['value'] = 'wpf_optin_submit';
                $trigger['title'] = 'Optin Submitted';
            }elseif( 'orderbump' === $trigger['trigger'] ){
                $trigger['value'] = 'accepted' == $trigger['condition']  ? 'wpf_orderbump_accepted' : 'wpf_orderbump_rejected';
                $trigger['title'] = 'accepted' == $trigger['condition']  ? 'Order Bump Accepted' : 'Order Bump Rejected';
            }elseif( 'upsell' === $trigger['trigger'] ){
                $trigger['value'] = 'accepted' == $trigger['condition']  ? 'wpf_upsell_accepted' : 'wpf_upsell_rejected';
                $trigger['title'] = 'accepted' == $trigger['condition']  ? 'Upsell Accepted' : 'Upsell Rejected';
            }elseif( 'downsell' === $trigger['trigger'] ){
                $trigger['value'] = 'accepted' == $trigger['condition']  ? 'wpf_downsell_accepted' : 'wpf_downsell_rejected';
                $trigger['title'] = 'accepted' == $trigger['condition']  ? 'Downsell Accepted' : 'Downsell Rejected';
            }elseif( 'downsell' === $trigger['trigger'] ){
               
                $trigger['value'] = 'accepted' == $trigger['condition']  ? 'wpf_downsell_accepted' : 'wpf_downsell_rejected';
                $trigger['title'] = 'accepted' == $trigger['condition']  ? 'Downsell Accepted' : 'Downsell Rejected';
            }elseif( 'order' === $trigger['trigger'] ){
                $trigger['value'] = 'accepted' == $trigger['condition']  ? 'wpf_order_placed' : '';
                $trigger['title'] = 'accepted' == $trigger['condition']  ? 'Checkout Order Accepted' : '';
            }
       
            update_post_meta( $stepId, '_wpfnl_automation_trigger', $trigger );
            
        }
        
        if( !$trigger || !is_array($trigger) ){
            $trigger = [
                'value' => '',
                'title' => '',
            ];
        }
        $tags = Wpfnl_functions::get_mint_contact_groups( 'tags' );
        $lists = Wpfnl_functions::get_mint_contact_groups( 'lists' );
        $response = [
            'success'   => true,
            'steps'     => $steps,
            'stepTitle'     => $stepTitle,
            'trigger'     => $trigger,
            'tags'     => $tags,
            'lists'     => $lists,
        ];
        return rest_ensure_response( $response );
    }


    /**
	 * Update/insert automation meta
	 * 
	 * @param int $automation_id
	 * @param string $meta_key
	 * @param string $meta_value
	 */
	public function update_meta( $automation_id,$meta_key,$meta_value ){

		global $wpdb;
		$automation_meta_table = $wpdb->prefix . AutomationMetaSchema::$table_name;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$select_query = $wpdb->prepare( "SELECT * FROM $automation_meta_table WHERE automation_id = %d AND meta_key = %s", array( $automation_id, $meta_key ) );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $select_query ); // db call ok. ; no-cache ok.
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
       
		if( $results ){
			try{
				$payload = [
					'id' 			=> isset($results[0]['id']) ? $results[0]['id'] : '',
					'meta_key'		=> $meta_key,
					'meta_value'	=> $meta_value,
				];
				$payload['updated_at'] = current_time( 'mysql' );
				$updated = $wpdb->update(
					$automation_meta_table,
					$payload,
					array( 'ID' => $payload['id'] )
				); // db call ok. ; no-cache ok.
	
				if( $updated ){
					return true;
				}else{
					return false;
				}
			}catch( \Exception $e ){
				return false;
			}
		}else{
			try{
				$wpdb->insert(
				$automation_meta_table,
					array(
						'automation_id'         => $automation_id,
						'meta_key'       		=> $meta_key,
						'meta_value'		 	=> $meta_value,
						'created_at'   			=> current_time( 'mysql' ),
						'updated_at'   			=> current_time( 'mysql' ),
					)
				); // db call ok.
				return $wpdb->insert_id;
			}catch( \Exception $e ){
				return false;
			}
		}
	}



    /**
	 * Prepare success response for REST API
	 *
	 * @param string $message Response success message.
	 * @param int    $code Response success code.
	 * @param mixed  $data Response data on success.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_success_response( $message = '', $code = 0, $data = null ) {
		$response = array(
			'code'    => $code,
			'message' => $message,
			'success' => true,
			'data'    => $data,
		);

		return rest_ensure_response( $response );
	}


	/**
	 * Prepare error response for REST API
	 *
	 * @param string $message Response error message.
	 * @param int    $code Response error code.
	 * @param mixed  $wp_error Response data on error.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_error_response( $message = '', $code = 0, $wp_error = null ) {
		return array(
            'success'       => false,
        );
	}

}