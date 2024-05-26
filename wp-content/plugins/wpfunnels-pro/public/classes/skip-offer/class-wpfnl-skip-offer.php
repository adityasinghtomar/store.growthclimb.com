<?php

namespace WPFunnelsPro\Frontend;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;
use Wpfnl_Pro_GBF_Offer_Conditions_Factory;
/**
 * Handle skip offer feature
 *
 * @package    WPFunnelsPro\Frontend\SkipOffer
 */
class SkipOffer {

    
    /**
     * Class instance.
     * @var object
     */
    private static $instance;

    /**
     * Get class instance.
     *
     * @since 1.8.5
     * @return object Instance.
     */
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

	/**
	 * Initialize hooks
	 *
	 * @since 1.8.5
	 */
	public function init( ) {

        add_action( 'wpfunnels/next_step_data', [$this, 'wpfnl_next_step_data_modification'], 10 );
		// add_filter( 'wpfunnels/next_step_data', array( $this, 'modify_next_step_data' ), 10 );
		add_filter( 'wpfunnels/next_step_url', array( $this, 'get_next_step_url' ), 10, 3 );
		add_filter( 'wpfunnels/modify_next_step_based_on_order', array( $this, 'modify_next_step_based_on_order' ), 10, 2 );
	}


	/**
     * Modify next step id for AB testings
     * 
     * @param Array $data
     * @return Array
     * @since 1.8.5
     */
    public function wpfnl_next_step_data_modification( $data ){
        if (!class_exists('\WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing')) {
            return $data;
        }
  
        $instance = new \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;
        if( !is_callable(array($instance, 'get_ab_testing_variation_id')) ){
            return $data;
        }

        if( !isset($data['step_id']) ){
            return $data;
        }

        $funnel_id = get_post_meta( $data['step_id'], '_funnel_id', true );
        if( !$funnel_id ){
            return $data;
        }

        $displayable_variation_id = \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing::get_ab_testing_variation_id( $funnel_id, $data['step_id'] );
        if( !$displayable_variation_id ){
            return $data;
        }
        
        $data = [
            'step_id'   => $displayable_variation_id,
            'step_type' => get_post_meta( $displayable_variation_id, '_step_type', true )
        ];
        return $data;
    }


	/**
     * Skip the next offer step(s)
     * if the assigned product is out of stock.
     * @param Array $next_step
     * 
     * @since 1.8.5
     * @return Array
     */
    public function modify_next_step_data( $next_step ) {
       
        if( isset( $next_step['step_type'] ) && in_array($next_step['step_type'], ['upsell', 'downsell']) ) {
            $next_step_type = $next_step[ 'step_type' ];
            $next_step_id = $next_step[ 'step_id' ] ?? false;
            if( $next_step_id ) {
                $funnel_id = get_post_meta( $next_step_id, '_funnel_id', true );
                $is_gbf = get_post_meta( $funnel_id, 'is_global_funnel', true );
                if( 'yes' !== $is_gbf ){
                    $offer_products = Wpfnl_Pro_functions::get_offer_product( $next_step_id, $next_step_type );
                    $product_ids = array_column( $offer_products, 'id' );
                    
                    if( !empty( $product_ids ) ) {
                        foreach( $product_ids as $id ) {
                            $product = '';
                            if( is_plugin_active('wpfunnels-pro-gbf/wpfnl-pro-gb.php' )){
                                $product = wc_get_product( $id );
                            }
                            
                            if( $product && ( $product->is_on_backorder() || $product->is_in_stock() || $product->get_stock_quantity() ) ) {
                                continue;
                            }

                            $funnel_id = Wpfnl_functions::get_funnel_id_from_step( $next_step_id );
                            $next_step = Wpfnl_functions::get_next_step( $funnel_id, $next_step_id );
                            return $this->modify_next_step_data( $next_step );
                        }
                    }else{ 
                        $funnel_id = Wpfnl_functions::get_funnel_id_from_step( $next_step_id );
                        $next_step = Wpfnl_functions::get_next_step( $funnel_id, $next_step_id );
                        return $this->modify_next_step_data( $next_step );
                    }
                }else{
                    if( is_plugin_active('wpfunnels-pro-gbf/wpfnl-pro-gb.php' )){
                        $offer_product = $this->skip_next_step_for_gbf( $funnel_id, $next_step_id );
                        if( isset($offer_product['id']) && $offer_product['id'] ){
                            $product = wc_get_product( $offer_product['id'] );
                            if( $product && ( $product->is_on_backorder() || $product->is_in_stock() || $product->get_stock_quantity() ) ) {
                                return $next_step;
                            }
                            $funnel_id = Wpfnl_functions::get_funnel_id_from_step( $next_step_id );
                            $next_step = Wpfnl_functions::get_next_step( $funnel_id, $next_step_id );
                            return $this->modify_next_step_data( $next_step );
                            
                        }else{
                            $funnel_id = Wpfnl_functions::get_funnel_id_from_step( $next_step_id );
                            $next_step = Wpfnl_functions::get_next_step( $funnel_id, $next_step_id );
                            return $this->modify_next_step_data( $next_step );
                        }
                    }
                    
                }
                
            }
        }
        
        return $next_step;
    }



	/**
     * get next step url
     *
     * @param string $thankyou_step_url
     * @param string $next_step_url
     * @param object $order
     * 
     * @since 1.8.5
     * @return string
     */
	public function get_next_step_url( $thankyou_step_url, $next_step_url, $order ) {
	    // check if payment gateway is supported or not

        $offer_settings = Wpfnl_functions::get_offer_settings();
        if( $offer_settings['skip_offer_step_for_free'] === 'on' ){
            if( $order && $order->get_total() <= 0 ){
                return $thankyou_step_url;
            }   
        }

        if( $offer_settings['skip_offer_step'] === 'on' ){
            $supported_gateways = \WPFunnelsPro\Frontend\Modules\Gateways\Payment_Gateways_Factory::getInstance()->get_supported_payment_gateways();
            if( $order && isset($supported_gateways[$order->get_payment_method()]) ){
                return $next_step_url;
            }else{
                return $thankyou_step_url;
            }
        }
	    return $next_step_url;
    }


	/**
     * Modify next step based on order
     * 
     * @param array  $next_step next step data.
     * @param object $order order details.
     * 
     * @return array
     * @since  1.8.5
     */
    public function modify_next_step_based_on_order( $next_step, $order ){
        if( $order && $next_step ){
            $offer_steps = [ 'upsell', 'downsell', 'conditional' ];
            if( isset($next_step['step_type']) && in_array( $next_step['step_type'], $offer_steps ) ){
                $funnel_id = Wpfnl_functions::get_funnel_id_from_step( $next_step['step_id'] );
                $offer_settings = Wpfnl_functions::get_offer_settings();
                $maybe_skip = get_post_meta($funnel_id,'_wpfunnels_skip_recurring_offer',true);
                $maybe_skip_within_days = get_post_meta($funnel_id,'_wpfunnels_skip_recurring_offer_within_days',true);
                if( 'yes' == $maybe_skip ){
                    $email = $order->get_billing_email();
                    if( $email ) {
                        $order_arg = array(
                            'customer' => $email,
                            'limit'    => -1
                        );
                        $is_gbf = get_post_meta( $funnel_id, 'is_global_funnel', true );
                        if( 'yes' !== $is_gbf ){
                            $offer_product = Wpfnl_Pro_functions::get_offer_product( $next_step['step_id'], $next_step['step_type'] );
                        }else{
                            if( is_plugin_active('wpfunnels-pro-gbf/wpfnl-pro-gb.php' )){
                                $offer_product = $this->skip_next_step_for_gbf( $funnel_id, $next_step['step_id'] );
                            }
                        }
                        $order_arg = array(
                            'customer' => $email,
                            'limit'    => -1
                        );
                        $orders = wc_get_orders($order_arg);
                        if( is_array($orders) ) {
                            $is_product_purchaged = false;
                            foreach( $orders as $customer_order ){
                                if( $is_product_purchaged ){
                                    break;
                                }
                                $order_date = $customer_order->get_date_created();
                                $date = $order_date->date('Y-m-d');
                                $current_date = date("Y-m-d");
                                $diff = date_diff(date_create($date),date_create($current_date));
                                $days = $diff->format("%R%a");
                                $days = str_replace('+','',$days);
                                foreach ($customer_order->get_items() as $item_id => $item) {
                                    $product = $item->get_product();
                                    if( $product ){
                                        $product_id = 'variation' == $product->get_type() ? $product->get_parent_id() : $product->get_id();
                                        if( $product_id ){
                                            if( 'yes' == $is_gbf ){
                                                $product = wc_get_product( isset($offer_product['id']) ? $offer_product['id'] : '' );
                                                if( $product && 'variation' == $product->get_type() ){
                                                    $offer_id =$product->get_parent_id();
                                                }elseif( $product ){
                                                    $offer_id =$product->get_id();
                                                }
                                                
                                                if( $product_id == $offer_id ){
                                                    if( $maybe_skip_within_days ){
                                                        if( $maybe_skip_within_days >= $days ){
                                                            $is_product_purchaged = true; 
                                                        }
                                                    }else{
                                                        $is_product_purchaged = true; 
                                                    }
                                                    break;
                                                }
                                            }else{
                                                $is_available = false;
                                                foreach( $offer_product as $_product ){
                                                    $product = wc_get_product( $_product['id'] );
                                                    if( $product && 'variation' == $product->get_type() ){
                                                        $offer_id =$product->get_parent_id();
                                                    }elseif( $product ){
                                                        $offer_id =$product->get_id();
                                                    }
                                
                                                    if( $product_id == $offer_id ){
                                                        if( $maybe_skip_within_days ){
                                                            if( $maybe_skip_within_days >= $days ){
                                                                $is_product_purchaged = true; 
                                                            }
                                                        }else{
                                                            $is_product_purchaged = true; 
                                                        }
                                                        break;
                                                    }
                                                }
                                                
                                                if( $is_product_purchaged ){
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    
                                }
                            }
                            
                            if( $is_product_purchaged ){
                                $next_step = Wpfnl_functions::get_next_step( $funnel_id, $next_step['step_id'] );
                                return $this->modify_next_step_based_on_order( $next_step, $order );
                            }
                            
                        }
                    }
                }
            }
        }
        return $next_step;
    }


    /**
     * @desc Skip the next offer step(s) for gbf
     * if the assigned product is out of stock.
     * @since 1.6.21
     * @param $next_step
     * @return mixed
     */
    private function skip_next_step_for_gbf( $funnel_id, $step_id ){
        
        $step_type          = get_post_meta($step_id, '_step_type', true);
        $offer_product      = [];
        $order_id           = ( isset( $_POST['order_id'] ) ) ? intval( $_POST['order_id'] ) : (( isset( $_GET['wpfnl-order'] ) ) ? intval( $_GET['wpfnl-order'] ) : 0);
        $gbf_product        = $this->get_gbf_product_from_cookie();
        $gb_funnel_settings = wpfnl()->meta->get_funnel_meta( $funnel_id, 'global_funnel_start_condition' );
        if( $gb_funnel_settings && is_array( $gb_funnel_settings ) ) {
            $offer_mappings  = wpfnl()->meta->get_funnel_meta( $step_id, "global_funnel_{$step_type}_rules" );
            if( isset($offer_mappings['type']) ){
                if( 'specificProduct' == $offer_mappings['type'] ){
                    $product = isset( $offer_mappings['show'] ) && $offer_mappings['show'] ? wc_get_product( $offer_mappings['show'] ) : '';
                    if( $product ){
                        if( $product->get_type() == 'variable' ){
                            return $offer_product;
                        }
                    }
                }
                $param_type = Wpfnl_Pro_GBF_Offer_Conditions_Factory::build($offer_mappings['type']);
                $offer_product   = $param_type->get_offer_product( $offer_mappings, $order_id, $step_id, $gbf_product );
            }
        }
        return $offer_product;
    }


     /**
     * Get GBF product from cookie data
     * 
     * @return Array
     */
    private function get_gbf_product_from_cookie() {
        if( Wpfnl_functions::is_wc_active() ){
            return WC()->session->get('wpfunnels_global_funnel_specific_product') ? WC()->session->get('wpfunnels_global_funnel_specific_product') : [];
        }
        return [];
    }
}
