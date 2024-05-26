<?php

namespace WPFunnels\Rest\Controllers;

use Error;
use WP_Error;
use WP_REST_Request;
use WPFunnels\Data_Store\Wpfnl_Steps_Store_Data;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;

use WPFunnels\Admin\Module\Steps\Wpfnl_Steps_Factory;
use WPFunnels\Admin\Module\Wpfnl_Admin_Module;
use WPFunnels\Wpfnl;


class AbTestingController extends Wpfnl_REST_Controller
{

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
    protected $rest_base = 'abtesting';

    /**
     * check if user has valid permission
     *
     * @param $request
     * @return bool|WP_Error
     * @since 1.0.0
     */
    public function update_items_permissions_check()
    {   
        return true;
        if ( !Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'steps', 'edit' ) ) {
            return new WP_Error('wpfunnels_rest_cannot_edit', __('Sorry, you cannot edit this resource.', 'wpfnl'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }

    /**
     * Makes sure the current user has access to READ the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     * @since  3.0.0
     */
    public function get_items_permissions_check( $request ) {
        return true;
        if( !Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'settings' ) ) {
            return new WP_Error( 'wpfunnels_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'wpfnl' ), [ 'status' => rest_authorization_required_code() ] );
        }
        return true;
    }


    /**
     * register rest routes
     *
     * @since 1.0.0
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/update-status/(?P<step_id>[\d]+)', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'update_status'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);


        register_rest_route($this->namespace, '/' . $this->rest_base . '/update-running-status', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'update_running_status'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
                'args'                => array(
					'stepId'     => array(
						'description' => __( 'Funnel step ID.','wpfnl-pro' ),
						'type'        => 'integer',
                        'required'      => true
					),
				),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/create-varient', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'create_varient'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/duplicate-varient', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'duplicate_varient'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);
        
        register_rest_route($this->namespace, '/' . $this->rest_base . '/restore-variant', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'restore_variant'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ],
                'args'                => array(
                    'stepId'     => array(
                        'description' => __( 'Funnel step ID.','wpfnl-pro' ),
                        'type'        => 'integer',
                        'required'      => true
                    ),
                    'variationId'     => array(
                        'description' => __( 'AB testing variation ID.','wpfnl-pro' ),
                        'type'        => 'integer',
                        'required'      => true
                    ),
                ),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/update-settings', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'update_settings'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ],
                'args'                => array(
					'stepId'     => array(
						'description' => __( 'Funnel step ID.','wpfnl-pro' ),
						'type'        => 'integer',
                        'required'      => true
					),
				),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/delete-variant', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'delete_variant'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ],
                'args'                => array(
					'stepId'     => array(
						'description' => __( 'Funnel step ID.','wpfnl-pro' ),
						'type'        => 'integer',
                        'required'      => true
					),
                    'variationId'     => array(
						'description' => __( 'AB testing variation ID.','wpfnl-pro' ),
						'type'        => 'integer',
                        'required'      => true
					),
				),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/get-start-settings/(?P<step_id>[\d]+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [
                    $this,
                    'get_start_settings'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/get-settings', [
            [
                'methods' => \WP_REST_Server::READABLE,
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

        register_rest_route($this->namespace, '/' . $this->rest_base . '/get-stats/(?P<funnel_id>[\d]+)/(?P<step_id>[\d]+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [
                    $this,
                    'get_stats'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/reset-stats/(?P<step_id>[\d]+)', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'reset_stats'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/reset-settings/(?P<step_id>[\d]+)', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'reset_settings'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/declare-winner', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'declare_winner'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ],
                'args'                => array(
					'stepId'     => array(
						'description' => __( 'Funnel step ID.','wpfnl-pro' ),
						'type'        => 'integer',
                        'required'      => true
					),
				),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/archive-variant', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'archive_variant'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ],
                'args'                => array(
					'stepId'     => array(
						'description' => __( 'Funnel step ID.','wpfnl-pro' ),
						'type'        => 'integer',
                        'required'      => true
					),
                    'variationId'     => array(
						'description' => __( 'AB testing variation ID.','wpfnl-pro' ),
						'type'        => 'integer',
                        'required'      => true
					),
				),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/delete-archive-variant', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'delete_archive_variant'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ],
                'args'                => array(
                    'stepId'     => array(
                        'description' => __( 'Funnel step ID.','wpfnl-pro' ),
                        'type'        => 'integer',
                        'required'      => true
                    ),
                    'variationId'     => array(
                        'description' => __( 'AB testing variation ID.','wpfnl-pro' ),
                        'type'        => 'integer',
                        'required'      => true
                    ),
                ),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/get-winner/(?P<step_id>[\d]+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [
                    $this,
                    'get_winner'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/stop-ab-testing/(?P<step_id>[\d]+)', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'stop_ab_testing'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);
    }


    /**
     * Update A/B testing status
     * 
     * @param WP_REST_Request $request
     * @return Array
     * 
     * @since 1.6.21
     */
    public function update_status( WP_REST_Request $request ){

        if( isset($request['step_id']) ){
            $step_id = $request['step_id'];
            $value   = isset($request['value']) ? $request['value'] : '';

            $response = Wpfnl_Ab_Testing::update_ab_testing_status( $step_id, $value );
          
            if( $response ){
                return rest_ensure_response( $this->get_success_response() );
            }
        }
        return rest_ensure_response( $this->get_error_response() );
    }
    
    
    /**
     * Update A/B testing running status
     * 
     * @param WP_REST_Request $request
     * @return Array
     * 
     * @since 1.6.21
     */
    public function update_running_status( WP_REST_Request $request ){

        $required_params = array('stepId');

        foreach ( $required_params as $param ) {
            if ( !$request->has_param($param) ) {
                return rest_ensure_response( $this->get_error_response( __( "Required parameter '$param' is missing.", 'wpfnl-pro' ), 400 ) );
            }
        }
        
        $step_id = sanitize_text_field($request['stepId']);
        $response = Wpfnl_Ab_Testing::update_running_status( $step_id );
        if( !$response || !is_array( $response ) ){
            return rest_ensure_response( $this->get_error_response( __( "AB testing settings is not available", 'wpfnl-pro' ), 400 ) );
        }

        return rest_ensure_response( $response );
        
    }


    /**
     * Get setttings
     * 
     * @param WP_REST_Request $request
     * @return Array
     * 
     * @since 1.6.21
     */
    public function get_settings( WP_REST_Request $request ){
        $response = [];
        if( !empty( $request['stepId'] ) ){
            $step_id            = $request['stepId'];
            $is_migrated = Wpfnl_Ab_Testing::maybe_migrate_start_settings( $step_id );
            if( $is_migrated ){
                $settings  = get_post_meta( $step_id, '_wpfnl_ab_testing_start_settings', true );
            }else{
                $settings           = get_post_meta( $step_id, '_wpfnl_ab_testing_start_settings', true );
                if( !$settings || !is_array($settings) ){
                    $settings             = Wpfnl_Ab_Testing::get_default_start_setting( $step_id );
                    update_post_meta( $step_id, '_wpfnl_ab_testing_start_settings', $settings );
                }
            }

            $settings = Wpfnl_Ab_Testing::get_settings_with_stats( $step_id, $settings );
            $settings = Wpfnl_Ab_Testing::update_variation_link( $settings );
        
            $response           = $this->get_success_response();
            $response['data']   = $settings;
            $response['stepName']   = get_the_title( $step_id );
        }else{
            $response = $this->get_error_response();
            $response['data']   = '';
        }
        return rest_ensure_response( $response );
    }


    /**
     * Update/save start settings
     * 
     * @param WP_REST_Request $request
     * @return Array 
     * @since 1.6.21
     */
    public function update_settings( WP_REST_Request $request ){
        $required_params = array('stepId');
       
        foreach ( $required_params as $param ) {
            if ( !$request->has_param($param) ) {
                return rest_ensure_response( $this->get_error_response( __( "Required parameter '$param' is missing.", 'wpfnl-pro' ), 400 ) );
            }
        }

        $step_id    = sanitize_text_field( $request['stepId'] );
        $settings   = filter_var_array( $request['settings'] ? $request['settings'] : [] );
        Wpfnl_Ab_Testing::update_ab_testing_status( $step_id, 'yes' );
        $response = Wpfnl_Ab_Testing::update_start_settings( $step_id, $settings );

        if( !$response ){
            return rest_ensure_response( $this->get_error_response( __( "Failed to save settings", 'wpfnl-pro' ), 400 ) );
        }
        return rest_ensure_response( $this->get_success_response() );
    }

    /**
     * Update/save start settings
     *
     * @param WP_REST_Request $request
     * @return Array
     * @since 1.6.21
     */
    public function stop_ab_testing( WP_REST_Request $request ){
        if( isset($request['step_id']) ){

            $step_id = $request['step_id'];
            $data = get_post_meta( $step_id, '_wpfnl_ab_testing_start_settings' , true );
            if( isset($data['auto_winner']['is_enabled']) ){
                $data['auto_winner']['is_enabled'] = 'true' == $data['auto_winner']['is_enabled'] ? 'yes' : 'no';
            }
            $data['start_date'] = date( 'Y-m-d H:i:s' );
            $data['is_started'] = '';
            $result = Wpfnl_Ab_Testing::update_start_settings( $step_id, $data );

            if( $result ){
                return rest_ensure_response( $this->get_success_response() );
            }
        }
        return rest_ensure_response( $this->get_error_response() );
    }



    


    /**
     * Get start settings 
     * 
     * @param WP_REST_Request $request
     * @return Array 
     * @since 1.6.21
     */
    public function get_start_settings( WP_REST_Request $request ){

        if( isset($request['step_id']) ){
            $step_id = $request['step_id'];
            $result = Wpfnl_Ab_Testing::get_start_settings( $step_id );
            if( $result ){
                $response = $this->get_success_response();
                $response['data']       = $result;
                $start_date = new \DateTime($result['start_date']);
                $now = new \DateTime(Date('Y-m-d'));
                $interval = $start_date->diff($now);
                $response['running_days']=  $interval->d;
                $response['step_type']  = get_post_meta( $step_id, '_step_type', true );
                return rest_ensure_response( $response );
            }
        }

        return rest_ensure_response( $this->get_error_response() );
    }


    /**
     * Declear winner
     * 
     * @param WP_REST_Request $request
     * 
     * @return Array 
     * @since  1.6.21
     */
    public function declare_winner( WP_REST_Request $request ){
        $required_params = array('stepId');
       
        foreach ( $required_params as $param ) {
            if ( !$request->has_param($param) ) {
                return rest_ensure_response( $this->get_error_response( __( "Required parameter '$param' is missing.", 'wpfnl-pro' ), 400 ) );
            }
        }

        $step_id         = sanitize_text_field( $request['stepId'] );
        $variation_id    = sanitize_text_field( isset($request['variationId']) ? $request['variationId'] : '' );
        $is_archived     = sanitize_text_field( isset($request['isArchived']) ? $request['isArchived'] : 'no' );
        $result          = Wpfnl_Ab_Testing::set_winner( $step_id, $variation_id );
        if( !$result ){
            return rest_ensure_response( $this->get_error_response( __( "Failed to update winner", 'wpfnl-pro' ), 400 ) );
        }
        if( 'yes' === $is_archived ){
            Wpfnl_Ab_Testing::archive_all_variant( $step_id, $variation_id );
        }
        
        $settings = get_post_meta( $step_id, '_wpfnl_ab_testing_start_settings', true );
        $settings = Wpfnl_Ab_Testing::get_settings_with_stats( $step_id, $settings );
        $response = [
            'success' => true,
            'data'    => $settings,
        ];
        return rest_ensure_response( $response );
    }
    
    
    /**
     * Archive a variant in the A/B testing configuration.
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return \WP_REST_Response The REST response containing success or error information.
     * @since  1.6.21
     */
    public function archive_variant( WP_REST_Request $request ) {
        $required_params = array('stepId', 'variationId');

        // Check if all required parameters are present in the request.
        foreach ( $required_params as $param ) {
            if ( !$request->has_param($param) ) {
                return rest_ensure_response( $this->get_error_response( __( "Required parameter '$param' is missing.", 'wpfnl-pro' ), 400 ) );
            }
        }

        // Get the step ID and variant ID from the request.
        $step_id    = sanitize_text_field( $request['stepId'] );
        $variation_id = sanitize_text_field( $request['variationId'] );

        // Get the A/B testing start settings for the step.
        $settings = Wpfnl_Ab_Testing::get_start_settings( $step_id );

        // Check if both active variations and archived variations exist in the settings.
        if ( !isset( $settings['variations'], $settings['archived_variations'] ) ) {
            return rest_ensure_response( $this->get_error_response(  __( "No variation found", "wpfnl-pro" ), 400  ) );
        }

        // Find the index of the variant to be archived within active variations.
        $key = array_search( $variation_id, array_column( $settings['variations'], 'stepId' ) );

        // Check if the variant was found.
        if ( false === $key ) {
            return rest_ensure_response( $this->get_error_response(  __( "Variation ID is not exist", "wpfnl-pro" ), 400  ) );
        }

        // Retrieve the archived variant's data and remove it from active variations.
        $archived_variation_data = $settings['variations'][$key];
        array_splice( $settings['variations'], $key, 1 );

        // Add the archived variant to the list of archived variations.
        array_push( $settings['archived_variations'], $archived_variation_data );

        // Update the A/B testing start settings.
        $is_updated = Wpfnl_Ab_Testing::update_start_settings( $step_id, $settings );

        // Check if the update was successful.
        if ( !$is_updated ) {
            return rest_ensure_response( $this->get_error_response(  __( "Please provide correct step id and settings for update", "wpfnl-pro" ), 400  ) );
        }

        // Prepare the response data.
        $response = [
            'success' => true,
            'data'    => $settings
        ];

        // Return the response.
        return rest_ensure_response( $response );
    }


    /**
     * Get winner
     * 
     * @param WP_REST_Request $request
     * @return Array 
     * @since 1.6.21
     */
    public function get_winner( WP_REST_Request $request ){
        if( isset( $request['step_id'] ) ){
            $step_id        = $request['step_id'];
            $result = Wpfnl_Ab_Testing::get_winner( $step_id );
            if( $result ){
                $response = $this->get_success_response();
                $response['data'] = $result;
                return rest_ensure_response( $response );
            }
        }
        return rest_ensure_response( $this->get_error_response() );
    }


    /**
     * Delete a variant from the AB testing configuration.
     *
     * @param \WP_REST_Request $request The REST request object.
     *
     * @return \WP_REST_Response The REST response containing success or error information.
     *
     * @since 1.6.21
     */
    public function delete_variant( WP_REST_Request $request ) {
        $required_params = array('stepId', 'variationId');

        // Check if all required parameters are present in the request.
        foreach ( $required_params as $param ) {
            if ( !$request->has_param($param) ) {
                return rest_ensure_response( $this->get_error_response( __( "Required parameter '$param' is missing.", 'wpfnl-pro' ), 400 ) );
            }
        }
        
        // Get the step ID and variant ID from the request.
        $step_id    = sanitize_text_field( $request['stepId'] );
        $variant_id = sanitize_text_field( $request['variationId'] );

        // Get the AB testing start settings for the step.
        $settings = Wpfnl_Ab_Testing::get_start_settings( $step_id );
        
        // Check if variations exist in the settings.
        if ( !isset( $settings['variations'] ) ) {
            return rest_ensure_response( $this->get_error_response(  __( "No variation found", "wpfnl-pro" ), 400  ) );
        }

        // Find the index of the variant to be deleted.
        $key = array_search( $variant_id, array_column( $settings['variations'], 'stepId' ) );

        // Check if the variant was found.
        if ( false === $key ) {
            return rest_ensure_response( $this->get_error_response(  __( "Variation ID is not exist", "wpfnl-pro" ), 400  ) );
        }

        // Remove the variant from the settings.
        array_splice( $settings['variations'], $key, 1 );
        
        // If no variations remain, restore default settings.
        if ( !count( $settings['variations'] ) ) {
            $default_settings = Wpfnl_Ab_Testing::get_default_start_setting( $step_id );
            $settings = $default_settings;
        }

        // Update the AB testing start settings.
        $is_updated = Wpfnl_Ab_Testing::update_start_settings( $step_id, $settings );

        // Check if the update was successful.
        if ( !$is_updated ) {
            return rest_ensure_response( $this->get_error_response(  __( "Please provide correct step id and settings for update", "wpfnl-pro" ), 400  ) );
        }

        // Prepare the response data.
        $response = [
            'success' => true,
            'data'    => $settings
        ];

        if( intval($step_id) !== intval($variant_id) ){
            wp_delete_post($variant_id);
        }
        // Return the response.
        return rest_ensure_response( $response );
    }


    /**
     * Create varient
     * 
     */
    public function create_varient( WP_REST_Request $request ){
        if( isset( $request['step_id'], $request['step_type'], ) && isset( $request['funnel_id'] ) && isset( $request['step_name'] ) ){
            $step_id        = $request['step_id'];
            $step_type      = $request['step_type'];
            $funnel_id     = $request['funnel_id'];
            $step_name      = $request['step_name'];

            $funnel = Wpfnl::get_instance()->funnel_store;
            $step = Wpfnl::get_instance()->step_store;

            $varient_id = $step->create_step( $funnel_id, $step_name, $step_type , '' , false );
            $step->set_id($varient_id);

            if ($varient_id && ! is_wp_error($varient_id)) {
                $funnel->set_id($funnel_id);
                $step_edit_link = get_edit_post_link($varient_id);
    
                if( 'elementor' ==  Wpfnl_functions::get_builder_type() ){
                    $step_edit_link = str_replace('/&amp;/g','&',$step_edit_link);
                    $step_edit_link = str_replace('edit','elementor',$step_edit_link);
                }

                $step_view_link = get_post_permalink($varient_id);


                Wpfnl_Ab_Testing::update_variations( $step_id, $varient_id );
                update_post_meta( $varient_id, '_parent_step_id', $step_id );
                Wpfnl_Ab_Testing::update_ab_testing_status( $step_id, 'yes');
                return [
                    'success'          		=> true,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $step_id->get_error_message(),
                ];
            }

        }
        return rest_ensure_response( $this->get_error_response() );
    }

    
    /**
     * Delete an archived variant from A/B testing configuration.
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return \WP_REST_Response The REST response containing success or error information.
     * @since 1.6.21
     */
    public function delete_archive_variant( WP_REST_Request $request ) {
        $required_params = array('stepId', 'variationId');

        // Check if all required parameters are present in the request.
        foreach ( $required_params as $param ) {
            if ( !$request->has_param($param) ) {
                return rest_ensure_response( $this->get_error_response( __( "Required parameter '$param' is missing.", 'wpfnl-pro' ), 400 ) );
            }
        }

        // Get the step ID and variant ID from the request.
        $step_id    = sanitize_text_field( $request['stepId'] );
        $variant_id = sanitize_text_field( $request['variationId'] );

        // Get the A/B testing start settings for the step.
        $settings = Wpfnl_Ab_Testing::get_start_settings( $step_id );

        // Check if archived variations exist in the settings.
        if ( !isset( $settings['archived_variations'] ) ) {
            return rest_ensure_response( $this->get_error_response(  __( "No variation found", "wpfnl-pro" ), 400  ) );
        }

        // Find the index of the archived variant to be deleted.
        $key = array_search( $variant_id, array_column( $settings['archived_variations'], 'stepId' ) );

        // Check if the variant was found.
        if ( false === $key ) {
            return rest_ensure_response( $this->get_error_response(  __( "Variation ID is not exist", "wpfnl-pro" ), 400  ) );
        }

        // Remove the archived variant from the list of archived variations.
        array_splice( $settings['archived_variations'], $key, 1 );

        // Update the A/B testing start settings.
        $is_updated = Wpfnl_Ab_Testing::update_start_settings( $step_id, $settings );

        // Check if the update was successful.
        if ( !$is_updated ) {
            return rest_ensure_response( $this->get_error_response(  __( "Please provide correct step id and settings for update", "wpfnl-pro" ), 400  ) );
        }

        if( intval($step_id) !== intval($variant_id) ){
            wp_delete_post($variant_id);
        }

        // Prepare the response data.
        $response = [
            'success' => true,
            'data'    => $settings
        ];

        // Return the response.
        return rest_ensure_response( $response );
    }


    /**
     * Duplicate AB testing varient
     * @param \WP_REST_Request $request
     */
    public function duplicate_varient( \WP_REST_Request $request ){
        if( isset( $request['step_id'], $request['step_type'] )  && isset( $request['funnel_id'] ) && isset( $request['step_name'] ) ){
            $step_id        = $request['step_id'];
            $step_type      = $request['step_type'];
            $funnel_id      = $request['funnel_id'];
            $step_name      = $request['step_name'];
            
            $title          = get_the_title($step_id);
            $page_template  = get_post_meta($step_id, '_wp_page_template', true);
            $post_content   = get_post_field('post_content', $step_id);

            $funnel = Wpfnl::get_instance()->funnel_store;
            $step = Wpfnl::get_instance()->step_store;

            $varient_id = $step->create_step($funnel_id, $step_name, $step_type, $post_content, false);
            $step->set_id($varient_id);

            if ($varient_id && ! is_wp_error($varient_id)) {

                $builder = Wpfnl_functions::get_builder_type();
                delete_post_meta($varient_id, '_wp_page_template');
                $step->update_meta($varient_id, '_wp_page_template', $page_template);
                Wpfnl_Ab_Testing::duplicate_ab_testing_meta($step_id, $varient_id, array('_funnel_id', '_wpf_step_title', '_wpf_step_slug'));
                ob_start();
                do_action('wpfunnels_after_step_import', $varient_id, $builder);
                ob_get_clean();
                
                $funnel->set_id($funnel_id);

                $step_edit_link = get_edit_post_link($varient_id);

                if( 'elementor' ==  Wpfnl_functions::get_builder_type() ){
                    $step_edit_link = str_replace('/&amp;/g','&',$step_edit_link);
                    $step_edit_link = str_replace('edit','elementor',$step_edit_link);
                }
                
                
                $step_view_link = get_post_permalink($varient_id);

                Wpfnl_Ab_Testing::update_variations( $step_id, $varient_id );
                update_post_meta( $varient_id, '_parent_step_id', $step_id );
                Wpfnl_Ab_Testing::update_ab_testing_status( $step_id, 'yes');
                
                $settings = Wpfnl_Ab_Testing::get_start_settings( $step_id );
                return [
                    'success'          		=> true,
                    'data'          		=> $settings,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $step_id->get_error_message(),
                ];
            }

        }

        return rest_ensure_response( $this->get_error_response() );
    }
    
    
    /**
     * Restore an archived variant to the AB testing configuration.
     *
     * @param \WP_REST_Request $request The REST request object.
     *
     * @return \WP_REST_Response The REST response containing success or error information.
     * @since 1.6.21
     */
    public function restore_variant( \WP_REST_Request $request ) {
        $required_params = array('stepId', 'variationId');

        // Check if all required parameters are present in the request.
        foreach ( $required_params as $param ) {
            if ( !$request->has_param($param) ) {
                return rest_ensure_response( $this->get_error_response( __( "Required parameter '$param' is missing.", 'wpfnl-pro' ), 400 ) );
            }
        }

        // Get the step ID and variant ID from the request.
        $step_id    = sanitize_text_field( $request['stepId'] );
        $variant_id = sanitize_text_field( $request['variationId'] );

        // Get the AB testing start settings for the step.
        $settings = Wpfnl_Ab_Testing::get_start_settings( $step_id );

        // Check if archived variations exist in the settings.
        if ( !isset( $settings['archived_variations'] ) ) {
            return rest_ensure_response( $this->get_error_response(  __( "No archived variation found", "wpfnl-pro" ), 400  ) );
        }

        // Find the index of the archived variant to be restored.
        $key = array_search( $variant_id, array_column( $settings['archived_variations'], 'stepId' ) );

        // Check if the variant was found.
        if ( false === $key ) {
            return rest_ensure_response( $this->get_error_response(  __( "This archived variation is not exist", "wpfnl-pro" ), 400  ) );
        }

        // Retrieve the restored variant's data and remove it from archived variations.
        $restore_step_data = $settings['archived_variations'][$key];
        array_splice( $settings['archived_variations'], $key, 1 );

        // Check if variations exist in the settings.
        if ( !isset( $settings['variations'] ) ) {
            return rest_ensure_response( $this->get_error_response(  __( "No variation found", "wpfnl-pro" ), 400  ) );
        }

        // Add the restored variant to the list of variations.
        array_push( $settings['variations'], $restore_step_data );

        // Update the AB testing start settings.
        $is_updated = Wpfnl_Ab_Testing::update_start_settings( $step_id, $settings );

        // Check if the update was successful.
        if ( !$is_updated ) {
            return rest_ensure_response( $this->get_error_response(  __( "Please provide correct step id and settings to restore", "wpfnl-pro" ), 400  ) );
        }

        // Prepare the response data.
        $response = [
            'success' => true,
            'data'    => $settings
        ];

        // Return the response.
        return rest_ensure_response( $response );
    }


    /**
     * return error reponse
     * @return Array
     * 
     */
    private function get_error_response(){
        return [
            'success' => false,
            'data'    => '',
        ];
    }


    /**
     * return error reponse
     * @return Array
     * 
     */
    private function get_success_response(){

        return [
            'success' => true,
            'data'    => '',
        ];
    }


    /**
     * Prepare a single setting object for response.
     *
     * @param object $item Setting object.
     * @param WP_REST_Request $request Request object.
     * @return \WP_REST_Response $response Response data.
     * @since  1.0.0
     */
    public function prepare_item_for_response($item, $request)
    {
        $data = $this->add_additional_fields_to_object($item, $request);
        return rest_ensure_response($data);
    }


    /**
     * @desc Get statistics data for A/B testing.
     *
     * @param WP_REST_Request $data
     * @return WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function get_stats( \WP_REST_Request $data ) {

        if( isset($data['funnel_id'] ) && isset($data['step_id'] ) ){
            $funnel_id = $data->get_param( 'funnel_id' );
            $step_id   = $data->get_param( 'step_id' );
            $step_type   = get_post_meta( $data->get_param( 'step_id' ), '_step_type', true );
            $stats = Wpfnl_Ab_Testing::get_stats( $funnel_id, $step_id );
            $response = $this->get_success_response();
            $response['data'] = $stats;
            $response['step_type'] = $step_type;
            return rest_ensure_response( $response );
        }
        return rest_ensure_response( $this->get_error_response() );
        
    }


    /**
     * @desc Reset analytics stats API callback function
     *
     * @param WP_REST_Request $data
     * @return WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function reset_stats( \WP_REST_Request $data ) {
        $step_id   = $data->get_param( 'step_id' );
        if( Wpfnl_Ab_Testing::reset_stats( $step_id ) ) {
            return rest_ensure_response( $this->get_success_response() );
        }
        return rest_ensure_response( $this->get_error_response() );
    }


    /**
     * Reset settings API callback function
     *
     * @param WP_REST_Request $data
     * @return WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function reset_settings( \WP_REST_Request $data ) {
        $step_id   = $data->get_param( 'step_id' );
        if( Wpfnl_Ab_Testing::reset_settings( $step_id ) ) {
            return rest_ensure_response( $this->get_success_response() );
        }
        return rest_ensure_response( $this->get_error_response() );
    }
}
