<?php
/**
 * Template library controller
 *
 * @package WPFunnels\Rest\Controllers
 */
namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPFunnels\Wpfnl_functions;
use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;

class TemplateLibraryController extends Wpfnl_REST_Controller {

	public static $funnel_api_url 				= 'https://templates.getwpfunnels.com/wp-json/wp/v2/wpfunnels/';
    public static $funnel_categories_api_url 	= 'https://templates.getwpfunnels.com/wp-json/wp/v2/template_industries/';
    public static $funnel_steps_api_url 		= 'https://templates.getwpfunnels.com/wp-json/wp/v2/wpfunnel_steps/';
	public static $all_funnels_api_url			= 'https://templates.getwpfunnels.com/wp-json/wpfunnels/v1/get_all_funnels/';


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
    protected $rest_base = 'templates/';


	/**
	 * Get remote funnel api url
	 *
	 * @return string
	 * @since  2.2.8
	 */
	private static function get_remote_funnel_api_url() {
		if( 'oxygen' === Wpfnl_functions::get_builder_type() ) {
			return 'https://oxygentemplates.getwpfunnels.com/wp-json/wp/v2/wpfunnels/';
		}
		return self::$funnel_api_url;
	}


	/**
	 * Get remote funnel builders categories url
	 *
	 * @return string
	 *
	 * @since 2.2.8
	 */
	private static function get_remote_funnel_categories_api_url() {
		if( 'oxygen' === Wpfnl_functions::get_builder_type() ) {
			return 'https://oxygentemplates.getwpfunnels.com/wp-json/wp/v2/template_industries/';
		}
		return self::$funnel_categories_api_url;
	}


	/**
	 * Get remote funnel steps categories url
	 *
	 * @return string
	 *
	 * @since 2.2.8
	 */
	private static function get_remote_funnel_steps_api_url() {
		if( 'oxygen' === Wpfnl_functions::get_builder_type() ) {
			return 'https://oxygentemplates.getwpfunnels.com/wp-json/wp/v2/wpfunnel_steps/';
		}
		return self::$funnel_steps_api_url;
	}


	/**
	 * Get all templates API url
	 *
	 * @return string
	 * @since  2.2.8
	 */
	private static function get_all_templates_api_url() {
		if( 'oxygen' === Wpfnl_functions::get_builder_type() ) {
			return 'https://oxygentemplates.getwpfunnels.com/wp-json/wpfunnels/v1/get_all_funnels/';
		}
		return self::$all_funnels_api_url;
	}


	/**
	 * Check user permission
	 *
	 * @param $request
	 *
	 * @return bool|WP_Error
	 */
	public function update_items_permissions_check( $request ) {
        $permission = current_user_can('manage_options');
        if ( ! Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'templates' ) ) {
            return new WP_Error( 'wpfunnels_rest_cannot_edit', __( 'Sorry, you cannot edit this resource.', 'wpfnl' ), array( 'status' => rest_authorization_required_code() ) );
        }
        return true;
    }


    /**
     * Makes sure the current user has access to READ the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
	 *
     * @return WP_Error|boolean
	 * @since  3.0.0
     */
    public function get_items_permissions_check( $request ) {
        if ( ! Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'templates' ) ) {
            return new WP_Error( 'wpfunnels_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'wpfnl' ), array( 'status' => rest_authorization_required_code() ) );
        }
        return true;
    }



    public function register_routes()
    {
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . 'get_templates' , array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'get_templates' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),
                ),
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . 'get_template_type_id' , array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'get_template_type_id' ),
                    'permission_callback'   => array( $this, 'get_items_permissions_check' ),

                )
            )
        );

    }


	/**
	 * Prepare a single setting object for response.
	 *
	 * @param $request
	 *
	 * @return WP_REST_Response
	 */
    public function get_templates($request) {
		$funnel_template_type 	= isset( $_GET['type'] ) ? $_GET['type'] : 'wc';
		$step 					= isset( $_GET['step'] ) ? $_GET['step'] : false;
		$templates 				= $this->get_funnels_data($funnel_template_type, $step , [] ,false);
		$templates['success'] 	= true;
		return $this->prepare_item_for_response( $templates, $request );
	}



	/**
	 * Send http request
	 *
	 * @param $url
	 * @param $args
	 *
	 * @return array
	 */
    private function remote_get($url, $args)
    {
        $response = wp_remote_get($url, $args);

        // bail if there is an error
		if ( is_wp_error( $response ) || ! is_array( $response ) || ! isset( $response['body'] ) ) {
			return [
				'success' => false,
				'message' => $response->get_error_message(),
				'data'    => $response,
			];
		}

		// Decode the results.
		$results = json_decode( $response['body'], true );

		// bail if there is no result found
		if ( ! is_array( $results ) ) {
			return new \WP_Error( 'unexpected_data_format', 'Data was not returned in the expected format.' );
		}

        return [
            'success' => true,
            'message' => 'Data successfully retrieved',
            'data'    => json_decode(wp_remote_retrieve_body($response), true),
        ];
    }


	/**
	 * Get all funnels of the specific builder
  	 *
	 * @param bool $type
	 * @param bool $isStep
	 * @param array $args
	 * @param bool $force_update
	 *
	 * @return bool|mixed|void
	 */
    private function get_funnels($type = false, $isStep= false,$args = [], $force_update = false)
    {
        $builder_type 	= Wpfnl_functions::get_builder_type();
		$cache_key 		= 'wpfunnels_remote_template_data_'.$type.'_'. WPFNL_VERSION;
		$data 			= get_transient($cache_key);
       	if ( $data ) {
       		return;
		}
		if ($type && ($force_update || false === $data) ) {
			if( false === $data ){
				$data = [];
			}
			$timeout = ($force_update) ? 40 : 55;
			// get all templates
			$params = [
				'per_page'  		=> 100,
				'offset'  			=> 0,
				'builder'  			=> $builder_type,
				'template_type'		=> $type
			];
			$url = add_query_arg($params, self::get_all_templates_api_url());

			$template_data = self::remote_get($url, [
				'timeout'       => $timeout,
			]);

			if ( !is_array($template_data) || !$template_data['success'] ) {
				set_transient( $cache_key, [], 24 * HOUR_IN_SECONDS );
				return false;
			}

			// get all steps
			$steps = [];
			if ( $template_data['data'] ) {
				foreach ( $template_data['data'] as $key => $template ) {
					$i 			= 0;
					$thankyou_count 	= 0;
					foreach ($template['steps'] as $_step) {
						if ( $thankyou_count && 'thankyou' === $_step['step_type'] ) {
							continue;
						}
						if( 0 == $i ){
							$template_data['data'][$key]['link'] = $_step['link'];
						}
						$_step['funnel_name'] 	= $template['title'];
						$_step['template_type'] = isset($template['templateType']) ? ('free' === $template['templateType'] ? 'free' : 'pro') : 'free';
						$steps[] = $_step;
						if ( 'thankyou' === $_step['step_type'] ) {
							$thankyou_count++;
						}
						$i++;
					}
				}
			}

			// fetch the funnel categories from the remote server
			$params = [
				'per_page'  		=> 100,
			];
			$url = add_query_arg($params, self::get_remote_funnel_categories_api_url());
			$categories_data = self::remote_get( $url, [
				'timeout'       => $timeout,
			]);
			if ( !is_array($categories_data) ||  !$categories_data['success'] ) {
				set_transient($cache_key, [], 24 * HOUR_IN_SECONDS);
				return false;
			}

			$data['templates'] 	= $template_data['data'];
			$data['steps'] 		= $steps;
			$data['categories'] = $categories_data['data'];
			update_option(WPFNL_TEMPLATES_OPTION_KEY.'_'.$type, $data, 'no');
			set_transient($cache_key, $data, 24 * HOUR_IN_SECONDS);
			return false;
		}
    }



    /**
     * Get funnel templates data
	 *
     * @param array $args
     * @param bool $force_update
	 *
     * @return array|mixed|void
     * @since  1.0.0
     */
    public function get_funnels_data($type = false, $step = false, $args = [], $force_update = false)
    {

		self::get_funnels($type,$step,$args, $force_update);
		$template_data 	= get_option(WPFNL_TEMPLATES_OPTION_KEY.'_'.$type);

        if (empty($template_data)) {
            return [];
        }
        return $template_data;
    }


    /**
	 * Get step from template library site by step ID
	 *
     * @param $step_id
     * @param bool $force_update
	 *
     * @return array
     * @since  1.0.0
     */
    public static function get_step( $step_id, $force_update = false )
    {
        $timeout = ($force_update) ? 25 : 8;

        $params = [
            '_fields'      => 'id,title,link,slug,featured_media,post_meta,rawData,steps,divi_content,featured_image,steps_order,builder,industry,is_pro,type,step_type,funnel_id,funnel_name,_qubely_css,_qubely_interaction_json,__qubely_available_blocks',
        ];

        $url = add_query_arg($params, self::get_remote_funnel_steps_api_url() . $step_id);
        $api_args = [
            'timeout' => $timeout,
        ];
        $response = (new TemplateLibraryController)->remote_get($url, $api_args);

        if ($response['success']) {
            $step = $response['data'];
            return [
                'title'         => (isset($step['title']['rendered'])) ? $step['title']['rendered'] : '',
                'post_meta'     => (isset($step['post_meta'])) ? $step['post_meta'] : '',
                'data'          => $step,
                'content'       => isset($response['data']['content']['rendered']) ? $response['data']['content']['rendered'] : '',
                'rawData'       => isset($response['data']['rawData']) ? $response['data']['rawData'] : '',
				'divi_content'  => isset($response['data']['divi_content']) ? $response['data']['divi_content'] : '',
                'message'       => $response['message'],
                'success'       => $response['success'],
            ];
        }

        return [
            'title'        => '',
            'post_meta'    => [],
            'message'      => $response['message'],
            'data'         => $response['data'],
            'success'      => $response['success'],
            'content'      => '',
        ];
    }




    public static function get_funnel( $funnel_id, $force_update = false ) {
		$timeout = ($force_update) ? 25 : 8;
		$params = [
			'_fields' => 'id,title,link,slug,featured_media,funnel_data',
		];

		$url = add_query_arg( $params, self::get_remote_funnel_api_url() . $funnel_id );
		$api_args = [
			'timeout' => $timeout
		];
		$response = (new TemplateLibraryController)->remote_get( $url, $api_args );
		if ($response['success']) {
			$funnel = $response['data'];
			return [
				'funnel_id'		=> $funnel_id,
				'funnel_data'	=> $funnel['funnel_data'] ?? '',
				'title'         => (isset($funnel['title']['rendered'])) ? $funnel['title']['rendered'] : '',
				'success'       => $response['success'],
			];
		}

		return [
			'title'        => '',
			'post_meta'    => [],
			'message'      => $response['message'],
			'data'         => $response['data'],
			'success'      => $response['success'],
			'content'      => '',
		];
	}


    /**
     * Get funnel type id
     */
    public function get_template_type_id( $request ){

        $response = [
            'success' => false,
            'type_id'  => '',
        ];


        if( $request['type'] ){
            $type = $request['type'];
            $force_update = false;
            $timeout = ($force_update) ? 40 : 55;
            $teplate_type_url = self::get_remote_funnel_template_type_api_url();
            $response = self::remote_get($teplate_type_url, [
                'timeout'       => $timeout,
            ]);

            if( isset($response['data']) && is_array($response['data'])){
                foreach( $response['data'] as $types ){
                    if( $type === $types['name'] ){
                        $response['success'] = true;
                        $response['type_id'] = $types['id'];
                    }
                }
            }
        }
        return rest_ensure_response( $response );
    }


    /**
     * Prepare a single setting object for response.
     *
     * @param object          $item Setting object.
     * @param WP_REST_Request $request Request object.
	 *
     * @return WP_REST_Response $response Response data.
	 * @since  1.0.0
     */
    public function prepare_item_for_response( $item, $request ) {
        $data     = $this->add_additional_fields_to_object( $item, $request );
		return rest_ensure_response( $data );
    }
}
