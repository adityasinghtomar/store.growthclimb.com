<?php

namespace WPFunnels\Rest\Controllers;

use WP_Error;
use WP_REST_Request;
use WPFunnels\Wpfnl_functions;
use \WC_Subscriptions_Product;
use Wpfnl_Pro_OfferProduct_Factory;
class OfferController extends Wpfnl_REST_Controller
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
    protected $rest_base = 'offer';

    /**
     * check if user has valid permission
     *
     * @param $request
     * @return bool|WP_Error
     * @since 1.0.0
     */
    public function update_items_permissions_check($request)
    {   
        if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'steps', 'edit' )) {
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
    public function get_items_permissions_check($request)
    {
        if (!Wpfnl_functions::wpfnl_rest_check_manager_permissions('settings')) {
            return new WP_Error('wpfunnels_rest_cannot_view', __('Sorry, you cannot list resources.', 'wpfnl'), ['status' => rest_authorization_required_code()]);
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

        register_rest_route($this->namespace, '/' . $this->rest_base . '/getUpsellData/', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [
                    $this,
                    'get_upsell_data'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/saveUpsellData/', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'save_upsell_data'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/getDownsellData/', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [
                    $this,
                    'get_downsell_data'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/saveDownsellData/', [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [
                    $this,
                    'save_downsell_data'
                ],
                'permission_callback' => [
                    $this,
                    'update_items_permissions_check'
                ] ,
            ],
        ]);
    }


    /**
     * Save upsell data
     */
    public function save_upsell_data($request)
    {
        $step_id = $request['step_id'];
        $products = array();
        $data = json_decode($request['product'], true);
        $products[] = $data;
        update_post_meta($step_id, '_wpfnl_upsell_products', $products);
        return 'success';
    }


    /**
     * Get upsell product data
     *
     * @param $request
     * @return WP_Error|\WP_REST_Response
     *
     * @since 1.0.0
     */
    public function get_upsell_data( $request )
    {
        $response = [];
        $step_id    = $request['step_id'];
        $_products   = get_post_meta( $step_id, '_wpfnl_upsell_products', true );
        $products   =  apply_filters( 'wpfunnels/upsell_product', $_products, $step_id );
        $funnel_id  = Wpfnl_functions::get_funnel_id_from_step($step_id);
        $type = get_post_meta($funnel_id, '_wpfnl_funnel_type', true);
        if( 'lms' === $type ){
            $_class = 'lms';
        }else{
            $_class = 'wc';
        }
        $class_object = Wpfnl_Pro_OfferProduct_Factory::build( $_class );
        if( $class_object ){
            $response = $class_object->get_upsell_items( $products, $step_id );
        }
        return $this->prepare_item_for_response( $response, $request );
    }

    /**
     * Save downsell data
     */
    public function save_downsell_data($request)
    {
        $step_id = $request['step_id'];
        $products = array();
        $data = json_decode($request['product'], true);
        $products[] = $data;
        update_post_meta($step_id, '_wpfnl_downsell_product', $products);
        return 'success';
    }


    /**
     * get downsell product data
     *
     * @param $request
     * @return WP_Error|\WP_REST_Response
     *
     * @since 1.0.0
     */
    public function get_downsell_data($request)
    {
        $response = [];
        $step_id        = $request['step_id'];
        $_products      = get_post_meta( $step_id, '_wpfnl_downsell_products', true );
        $products       =  apply_filters( 'wpfunnels/downsell_product', $_products, $step_id );
        $discount       = get_post_meta( $step_id, '_wpfnl_downsell_discount', true );
        $offer_settings = \WPFunnels\Wpfnl_functions::get_offer_settings();
        $funnel_id  = Wpfnl_functions::get_funnel_id_from_step($step_id);
        $type = get_post_meta($funnel_id, '_wpfnl_funnel_type', true);
        if( 'lms' === $type ){
            $_class = 'lms';
        }else{
            $_class = 'wc';
        }

        $class_object = Wpfnl_Pro_OfferProduct_Factory::build( $_class );
        
        if( $class_object ){
            $response = $class_object->get_downsell_items( $products, $step_id );
        }
        return $this->prepare_item_for_response( $response, $request );
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


    
}
