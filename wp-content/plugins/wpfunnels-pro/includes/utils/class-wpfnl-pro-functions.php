<?php

namespace WPFunnelsPro;


use WPFunnels\Wpfnl_functions;
use Wpfunnels\Wpfunnels_AES_Encription\Wpfunnels_Aes_Ctr;
use WPFunnelsPro\OfferProduct\Wpfnl_Offer_Product;
use Wpfnl_Pro_GB_Functions;
use WPFunnelsPro\Frontend\Modules\Gateways\Payment_Gateways_Factory;
use WPFunnels\Discount\WpfnlDiscount;

class Wpfnl_Pro_functions {


    /**
     * check if the the page is offer page or not
     *
     * @return bool
     *
     * @since 1.3.4
     */
    public static function maybe_offer_step() {
        $is_offer_type = false;
        if( Wpfnl_functions::is_funnel_step_page() ) {
            global $post;
            $step_type = get_post_meta( $post->ID, '_step_type', true );
            if ( 'upsell' === $step_type || 'downsell' === $step_type ) {
                $is_offer_type = true;
            }
        }
        return $is_offer_type;
    }


    /**
     * check if the user is on edit mode
     *
     * @return bool
     *
     * @since 1.3.4
     */
    public static function maybe_admin_on_edit_page() {
        $is_edit_mode = false;
        global $pagenow;
        if( isset($_REQUEST['post']) && $_REQUEST['post'] ){
            
            $funnel_id = Wpfnl_functions::get_funnel_id_from_step($_REQUEST['post']);

            if (( $pagenow == 'post.php' ) && $funnel_id ) {
                return true;
            }
        }
       
        return $is_edit_mode;
    }


    /**
     * get checkout id from post data
     *
     * @param $post_data
     * @return false|int
     */
    public static function get_checkout_id_from_post_data( $post_data ) {
        if (isset($post_data['_wpfunnels_checkout_id'])) {
            $checkout_id = filter_var(wp_unslash($post_data['_wpfunnels_checkout_id']), FILTER_SANITIZE_NUMBER_INT);
            return intval($checkout_id);
        }
        return false;
    }


    /**
     * get funnel if from post data
     *
     * @param $post_data
     * @return false|int
     */
    public static function get_funnel_id_from_post_data( $post_data ) {
        if (isset($post_data['_wpfunnels_funnel_id'])) {
            $funnel_id = filter_var(wp_unslash($post_data['_wpfunnels_funnel_id']), FILTER_SANITIZE_NUMBER_INT);
            return intval($funnel_id);
        }
        return false;
    }

    /**
     * get offer product data
     *
     * @param $step_id
     * @param bool $default
     * @return array|bool|mixed
     *
     * @since 1.0.0
     */
    public static function get_offer_product( $step_id, $type = 'upsell', $default = false ) {
        if($default) {
            return $default;
        }
        $value = get_post_meta( $step_id, "_wpfnl_{$type}_products", true );
        if ( ! $value ) {
            return array();
        }
        return $value;
    }

    /**
     * check if the current step is upsell/downsell
     *
     * @return bool
     */
    public static function is_upsell_downsell_step( $step_id = false ) {
        $is_upsell_downsell = false;
        if ( Wpfnl_functions::is_funnel_step_page() ) {
            if( !$step_id ) {
                global $post;
                $step_id = $post->ID;
            }
            $step_type = get_post_meta( $step_id, '_step_type', true );
            if ( $step_type === 'upsell' || $step_type === 'downsell' ) {
                $is_upsell_downsell = true;
            }
        }

        return $is_upsell_downsell;
    }


    /**
     * @param $step_id
     * @return bool
     */
    public static function is_offer_page( $step_id  ) {
        if( !$step_id ) {
            return false;
        }
        return Wpfnl_functions::check_if_this_is_step_type_by_id( $step_id, 'upsell') || Wpfnl_functions::check_if_this_is_step_type_by_id( $step_id, 'downsell');
    }


    /**
     * checck if offer exists in funnel
     *
     * @param $funnel_id
     * @return bool
     *
     * @since 2.0.5
     */
    public static function is_offer_exists_in_funnel( $funnel_id ) {
        $is_offer_exists = false;
        $steps = Wpfnl_functions::get_steps($funnel_id);
        if ( is_array( $steps ) ) {
            foreach ( $steps as $index => $step ) {
                $step_type = $step['step_type'];
                if(in_array($step_type,array('upsell', 'downsell'))) {
                    $is_offer_exists = true;
                    break;
                }
            }
        }
        return $is_offer_exists;
    }


    /**
     * Get upsell/downsell offer product information
     *
     * @param $step_id
     * @param string $selected_product_id
     * @param string $input_qty
     * @param int $order_id
     * @return array
     *
     * @since 1.0.0
     */
    public static function get_offer_product_data( $step_id, $selected_product_id = '', $input_qty = '', $order_id = 0 ) {
        $funnel_id      = get_post_meta( $step_id, '_funnel_id', true );
        
        return apply_filters( 'wpfunnels/offer_product_data', self::get_offer_data( $step_id, $selected_product_id , $input_qty, $order_id = 0 ), $funnel_id, $step_id );
    }



    public static function get_offer_data( $step_id, $selected_product_id = '', $input_qty = '', $order_id = 0 ) {
        $data               = array();
        $amount_diff        = 0;
        $cancel_main_order  = false;
        $product_id         = 0;
        $product_qty        = 0;

        $step_type      = get_post_meta( $step_id, '_step_type', true );
        $offer_product  = get_post_meta( $step_id, '_wpfnl_'.$step_type.'_products', true );
        $discount       = get_post_meta( $step_id, '_wpfnl_'.$step_type.'_discount', true );
        if ( isset( $offer_product[0] ) && !$selected_product_id) {
            $product_id = $offer_product[0]['id'];
            $product_qty = $offer_product[0]['quantity'];
        } else {
            $product_id     = $selected_product_id;
            $product_qty    = !empty($input_qty) ? intval($input_qty) : ( isset($offer_product[0]['quantity'] ) ? $offer_product[0]['quantity'] : 1);
        }
       
        if( $product_id ){
           
            $product        = wc_get_product( $product_id );
                if($product) {
                    $main_qty       = $product->get_stock_quantity();
                    if ($main_qty <= 0 && $product->get_manage_stock() == true){
                        $product_qty    =  0;
                    }
                    $product_qty    = intval( $product_qty );
                    $order          = wc_get_order($order_id);

                    $product_type   = $product->get_type();
                    $original_price = $product->get_price( 'edit' );
                    $custom_price   = $original_price;


                    if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
                        if( 'subscription_variation' === $product->get_type() || 'subscription' === $product->get_type() ){
                            $signUpFee      = \WC_Subscriptions_Product::get_sign_up_fee( $product );
                            $custom_price   = $custom_price + $signUpFee;
                        }
                    }

                    $unit_price     = $custom_price;
                    $unit_price_tax = $custom_price;
                    $product_price  = $custom_price * $product_qty;
                   
                    /** tax calculation */
                    $tax_enabled            = get_option( 'woocommerce_calc_taxes' );
                    $shipping_fee           = 0;
                    $shipping_incl_tax      = 0;
                    $shipping_excl_tax      = 0;
                    $shipping_method_name   = 0;
                    if( $order ) {
                        $shipping_fee           = $order->get_shipping_total();
                        $shipping_method_name   = $order->get_shipping_method();
                    }

                    
                    if ( 'yes' === $tax_enabled ) {
                        if ( !wc_prices_include_tax() ) {
                            $product_price     = wc_get_price_excluding_tax( $product, array( 'price' => $custom_price ) ) * floatval($product_qty);
                            $shipping_excl_tax = wc_get_price_excluding_tax( $product, array( 'price' => $shipping_fee ) );
                        } else {
                            $custom_price       = wc_get_price_including_tax( $product, array( 'price' => $custom_price ) ) * floatval($product_qty);
                            $shipping_incl_tax  = wc_get_price_including_tax( $product, array( 'price' => $shipping_fee ) );
                        }
                        $unit_price_tax = $custom_price;
                    }
                    
                    $shipping_incl_tax = $shipping_incl_tax  ? $shipping_incl_tax : $shipping_fee;
                    $shipping_excl_tax = $shipping_excl_tax ? $shipping_excl_tax : $shipping_fee;

                    /** if offer product has discount */
                    if($discount) {
                        $discount_instance = new WpfnlDiscount();
                        
                        if( !$discount_instance->maybe_time_bound_discount( $step_id ) || ($discount_instance->maybe_time_bound_discount( $step_id ) && $discount_instance->maybe_validate_discount_time( $step_id )) ) {
                            
                            $discount_type	    = $discount['discountType'];
                            $discount_apply_to	= $discount['discountApplyTo'];
                            $discount_value	    = $discount['discountValue'];
                            if( 'discount-percentage' === $discount_type || 'discount-price' === $discount_type ) {
                                $regular_price = $product->get_regular_price();
                                if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
                                    if( 'subscription_variation' === $product->get_type() || 'subscription' === $product->get_type() ){
                                        $signUpFee      = \WC_Subscriptions_Product::get_sign_up_fee( $product );
                                        $regular_price   = $regular_price + $signUpFee;
                                    }
                                }
                            
                                $product_price      = $discount_apply_to === 'sale' ? $product->get_sale_price() : $regular_price;
                                $product_price      = $product_price ? $product_price : $product->get_price();
                                
                                $product_price 		= \WPFunnelsPro\Wpfnl_Pro_functions::calculate_discount_price_for_widget( $discount_type , $discount_value, floatval($product_price) * floatval($product_qty) );
                            
                            
                                $custom_price       = $product_price;
                                $unit_price         = $product_price;
                                $unit_price_tax     = $product_price;
                            }
                        }
                    }
                    
                $data = array(
                    'step_id'                 => $step_id,
                    'id'                      => $product_id,
                    'name'                    => $product->get_title(),
                    'desc'                    => self::get_item_description($product),
                    'qty'                     => $product_qty,
                    'original_price'          => $original_price,
                    'regular_price'           => $product->get_regular_price(),
                    'sale_price'              => $product->get_sale_price(),
                    'unit_price'              => $unit_price,
                    'unit_price_tax'          => $unit_price_tax,
                    'args'                    => array(
                        'subtotal' => $product_price,
                        'total'    => $product_price,
                    ),
                    'shipping_fee'            => $shipping_excl_tax,
                    'shipping_fee_incl_tax'   => $shipping_incl_tax,
                    'shipping_method_name'    => $shipping_method_name,
                    'price'                   => $custom_price,
                    'url'                     => $product->get_permalink(),
                    'total_unit_price_amount' => floatval(preg_replace('/[^\d.]/', '', $unit_price_tax )) * floatval($product_qty),
                    'total'                   => $custom_price,
                    'cancel_main_order'       => $cancel_main_order,
                    'amount_diff'             => $amount_diff,
                    'discount'                => $discount ? true : false,
                    'discount_type'           => isset($discount['discountType']) ? $discount['discountType'] : '',
                    'discount_apply_to'       => isset($discount['discountApplyTo']) ? $discount['discountApplyTo'] : '',
                    'discount_value'          => isset($discount['discountValue']) ? $discount['discountValue'] : '',
                );
            }
        }
        return $data;
    }

        
    /**
     * Helper method to return the item description, which is composed of item
     * meta flattened into a comma-separated string, if available. Otherwise the
     * product SKU is included.
     *
     * The description is automatically truncated to the 127 char limit.
     *
     * @param array $item cart or order item
     * @param \WC_Product $product product data
     *
     * @return string
     * @since 1.0.0
     */
    public static function get_item_description($product)
    {

        if (is_string($product)) {
            $str = $product;
        } else {
            $str = $product->get_short_description();
        }
        $item_desc = wp_strip_all_tags(wp_specialchars_decode(wp_staticize_emoji($str)));
        $item_desc = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $item_desc);
        $item_desc = str_replace("\n", ', ', rtrim($item_desc));
        if (strlen($item_desc) > 127) {
            $item_desc = substr($item_desc, 0, 124) . '...';
        }

        return html_entity_decode($item_desc, ENT_NOQUOTES, 'UTF-8');

    }


    /**
     * Returns a string with all non-ASCII characters removed. This is useful for any string functions that expect only
     * ASCII chars and can't safely handle UTF-8
     *
     * Based on the SV_WC_Helper::str_to_ascii() method developed by the masterful SkyVerge team
     *
     * Note: We must do a strict false check on the iconv() output due to a bug in PHP/glibc {@link https://bugs.php.net/bug.php?id=63450}
     *
     * @param string $string string to make ASCII
     *
     * @return string|null ASCII string or null if error occurred
     */
    public static function str_to_ascii( $string ) {

        $ascii = false;

        if ( function_exists( 'iconv' ) ) {
            $ascii = iconv( 'UTF-8', 'ASCII//IGNORE', $string );
        }

        return false === $ascii ? preg_replace( '/[^a-zA-Z0-9_\-]/', '', $string ) : $ascii;
    }


    /**
     * get product amount/price
     *
     * @param $total
     * @return int
     */
    public static function get_amount_for_comparisons( $total ) {
        return absint( wc_format_decimal( ( (float) $total * 100 ), wc_get_price_decimals() ) );
    }


    /**
     * Actions after offer charge completes.
     *
     * @param $step_id
     * @param $order_id
     * @param $order_key
     * @param bool $is_charge_success
     * @param string $variation_id
     * @param string $input_qty
     * @param $offer_product
     * @return array
     * @throws \Exception
     */
    public static function after_offer_charged( $funnel_id ,$step_id, $order_id, $order_key, $offer_product, $is_charge_success = false, $attr = null, $variation_id = '', $input_qty = '', $shipping_data = [] ) {
        $offer_settings = Wpfnl_functions::get_offer_settings();
        $result = array();

        if ( $is_charge_success ) {

            $order = wc_get_order( $order_id );

            $transaction_id = $order->get_meta('_wpfunnels_offer_txn_resp_' . $offer_product['step_id']);
            $step_type = get_post_meta($step_id, '_step_type', true);
            
            
            if ( $offer_settings['offer_orders'] == 'main-order' ) {
                if( $attr ){
                    $offer_product['args']['variation'] = $attr;
                }
                
                $product = wc_get_product($offer_product['id']);
                if( $product ){
                    $product->set_price( ($offer_product['args']['total']/$offer_product['qty']) );
                }
                $_order_total = $order->get_total() + $offer_product['total'];
                $cart_discount = $order->get_meta('_cart_discount');
                $order_shipping = $order->get_meta('_order_shipping');
                $order_shipping_tax = $order->get_meta('_order_shipping_tax');
                
                $item_id = $order->add_product( $product, $offer_product['qty'] );
                $order->update_meta_data('_wpfunnels_order', 'yes');
                
                if( Wpfnl_functions::is_wc_active() ){
                    $chained_product_class_instance = new \WPFunnelsPro\Compatibility\ChainedProduct();
                    $chained_products = $chained_product_class_instance->get_chain_product_details( $offer_product['id'] );
                    $response = $chained_product_class_instance->update_order_item( $order, $chained_products, $offer_product['id'] );
                    if( isset($response['override_amount'], $response['total_chained_product_price'] ) && $response['override_amount']  ){
                        $_order_total = $_order_total + $response['total_chained_product_price'];
                    }
                }

                if( $item_id ) {
                    wc_add_order_item_meta( $item_id, "_wpfunnels_{$step_type}", 'yes' );
                    wc_add_order_item_meta( $item_id, '_wpfunnels_step_id', $offer_product[ 'step_id' ] );
                    wc_add_order_item_meta( $item_id, '_wpfunnels_offer_txn_id', $transaction_id );
                    $order->update_meta_data( '_wpfunnels_offer_' . $step_id, $step_id );
                }
                
                
                $tax_enabled  = get_option( 'woocommerce_calc_taxes' );
                $prev_tax   = 0;
                $tax = 0;
                
                if ( 'yes' === $tax_enabled ) {
                    if ( !wc_prices_include_tax() ) {
                        foreach ($order->get_items(array('tax')) as $item_id => $line_item) {
                            $order_product_detail = $line_item->get_data();
                            if( isset($order_product_detail['tax_total']) &&  isset($order_product_detail['rate_percent']) ){
                                $prev_tax = $order_product_detail['tax_total'];
                                $tax = ($offer_product['args']['total']*$order_product_detail['rate_percent'])/100;
                            }	
                        }
                    }
                }

                
                $order = self::update_offer_order_shipping( $order, $shipping_data );
                ob_start();
                do_action( 'woocommerce_order_before_calculate_totals', true, $order );
                ob_get_clean();

                ob_start();
                do_action( 'woocommerce_order_after_calculate_totals', true, $order );
                ob_get_clean();

                if( $cart_discount ){
                    $order->update_meta_data('_order_total', $_order_total );
                    $order->update_meta_data('_cart_discount', $cart_discount );    
                    if ( 'yes' === $tax_enabled ) {
                        if ( !wc_prices_include_tax() ) {
                            foreach ($order->get_items(array('tax')) as $item_id => $line_item) {
                                $order_product_detail = $line_item->get_data();
                                if( isset($order_product_detail['tax_total']) ){
                                   
                                    wc_update_order_item_meta($item_id, 'tax_total', $prev_tax + $tax);
                                    wc_update_order_item_meta($item_id, 'tax_amount', $prev_tax + $tax);
                                    $order->update_meta_data('_order_tax', $prev_tax + $tax );
                             
                                }	
                            }
                        }
                    }
                    
                }
                if( $order_shipping ){
                    $order->update_meta_data('_order_shipping', $order_shipping );    
                    if( $order_shipping_tax ){
                        $order->update_meta_data('_order_shipping_tax', $order_shipping_tax );    
                    }
                }
                $order->save();
                
            }
            else {
                $offer_product['transaction_id'] = $transaction_id;
                $child_order = self::create_child_order($order, $offer_product, $step_type ,$attr, $shipping_data);
               
                $user_id = $child_order->get_user_id();
                $child_order->update_meta_data('_wpfunnels_offer_'.$step_id, $step_id );
                $child_order->update_meta_data('_wpfunnels_parent_funnel_id', $funnel_id);
                $child_order->save();
               
                $replaceSettings  = get_post_meta( $step_id, '_wpfnl_'.$step_type.'_replacement_settings', true );
                if( $replaceSettings == 'true' ){
                    $isOfferReplace  = get_post_meta( $step_id, '_wpfnl_'.$step_type.'_replacement', true );

                    $order_ids = WC()->session->get('wpfnl_orders_'.$user_id.'_'.$funnel_id);
                    if( !empty($isOfferReplace['value']) && $isOfferReplace['value'] == 'true' ){
                        $child_order_items = $child_order->get_items();
                        /** check any physcial product is avialable in child order item */
                        $any_physical_item = false;
                        foreach( $child_order_items as $id => $child_order_item ){
                            $product = $child_order_item->get_product();
                            if( !$product->is_virtual() || !$product->is_downloadable() ){
                                $any_physical_item = true;
                            }
                        }
                       
                        foreach( $order_ids as $order_id ){
                            $order = wc_get_order( $order_id );
                            if( $order->get_meta('_wpfunnels_offer_parent_id') && ( $order->get_id() != $order->get_meta('_wpfunnels_offer_parent_id') ) ){
                                foreach( $order->get_items() as $id => $_order_item ){
                                    $product = $_order_item->get_product();
                                    if( !$product->is_virtual() || !$product->is_downloadable() ){
                                        $any_physical_item = true;
                                    }
                                }
                            }
                        }
                        
                        if( $isOfferReplace['replacement_type'] == 'all_prior_order' ){
                            foreach( $order_ids as $order_id ){
                                $shipping_cost_with_tax  = 0;
                                $order = wc_get_order( $order_id );
                                if( $order->get_shipping_total() && $any_physical_item ){
                                    $shipping_cost_with_tax = $order->get_shipping_tax() + $order->get_shipping_total();
                                }
                                if( $order->get_status() != 'cancelled' ){
                                    self::replace_order( $order , $isOfferReplace['replacement_type'], $shipping_cost_with_tax );
                                }
                            }
                        }elseif( $isOfferReplace['replacement_type'] == 'previous_step' ){
                            $step_info = Wpfnl_functions::get_prev_step($funnel_id, $step_id );
                            if( isset($order_ids[$step_info['step_id']]) ){
                                $shipping_cost_with_tax  = 0;
                                $order = wc_get_order( $order_ids[$step_info['step_id']] );
                                if( $order->get_shipping_total() && $any_physical_item){
                                    $shipping_cost_with_tax = $order->get_shipping_tax() + $order->get_shipping_total();
                                }
                                if( $order->get_status() != 'cancelled' ){
                                    self::replace_order( $order, $isOfferReplace['replacement_type'], $shipping_cost_with_tax);
                                }
                            }
                        }elseif( $isOfferReplace['replacement_type'] == 'main_order_with_order_bump' || $isOfferReplace['replacement_type'] == 'main_order_without_order_bump' ){
                            ob_start();
                            do_action( 'wpfunnels/before_main_order_cancelled', $order );
                            ob_get_clean();
                            $shipping_cost_with_tax  = 0;
                            $parent_order_id = $child_order->get_meta('_wpfunnels_offer_parent_id', true );
                            if( $parent_order_id ){
                                $order = wc_get_order( $parent_order_id );
                                if( $order ){
                                    if( $order->get_shipping_total() && $any_physical_item ){
                                        $shipping_cost_with_tax = $order->get_shipping_tax() + $order->get_shipping_total();
                                    }
                                    if( $order->get_status() != 'cancelled' ){
                                        self::replace_order( $order, $isOfferReplace['replacement_type'], $shipping_cost_with_tax );
                                    }
                                }
                            } 
                        }
                    }
                }
                $order = $child_order;
                
                if( is_plugin_active( 'woocommerce-software-license/software-license.php' ) ){
                    $license_obj = new \WOO_SL_functions;
                    self::create_license($order->get_id(), $license_obj);
                    $license_obj->generate_license_keys($order->get_id());
                }

                // start set session for replace order
                $orders = WC()->session->get('wpfnl_orders_'.$user_id.'_'.$funnel_id);
                $orders[$step_id] = $child_order->get_id();
                WC()->session->set('wpfnl_orders_'.$user_id.'_'.$funnel_id, $orders);
                // end set session for replace order

                // start destroy session for replace order if next step is thankyou
                $step_info = Wpfnl_functions::get_next_step($funnel_id, $step_id );
                if( $step_info['step_type'] == 'thankyou' ){
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    if ( isset( $_SESSION ) || $_SESSION ) {
                        unset($_SESSION['wpfnl_orders_'.$user_id.'_'.$funnel_id]);
                    }
                }
                // end destroy session for replace order if next step is thankyou
            }
            $result = array(
                'status'   => 'success',
                'message'  => __( 'Product Added Successfully.', 'wpfnl-pro' ),
            );


            $offer_settings['offer_type'] = $step_type;

            /**
             * $order /WC_Order object
             * $step_type String
             * $offer_product Array
             * $offer_product['step_id'] String represent the associate step id
             * $offer_product['id'] String represent the id of the product
             * $offer_product['name'] String represent the name of the product
             * $offer_product['desc'] String represent the description of the product
             * $offer_product['qty'] String represent the quantity of the product
             * $offer_product['original_price'] String represent the price of the product
             * $offer_product['unit_price'] String represent the unit_price of the product
             * $offer_product['unit_price_tax'] String represent the unit_price with tax of the product
             * $offer_product['args'] Array represent the extra arguments of the product
             * $offer_product['args']['subtotal'] String represent the subtotal of the product
             * $offer_product['args']['total'] String represent the total of the product
             * $offer_product['price'] String represent the price of the product
             * $offer_product['url'] String represent the url of the product
             * $offer_product['total_unit_price_amount'] String represent the $unit_price_tax * $product_qty of the product
             * $offer_product['total'] String represent the $custom_price of the product if any
             * $offer_product['cancel_main_order'] Bool checker if cancel main order is enabled/disabled from funnel settings
             */
            ob_start();
            do_action( 'wpfunnels/offer_accepted', $order, $offer_product );
            ob_get_clean();
        }

        return $result;
    }


    /**
     * Replace order
     */
    private static function replace_order( $order, $replacement_type, $shipping_cost = 0 ){
        if( false === is_a( $order, 'WC_Order' ) ){
            return false;
        }
        $refunded_item_id = '';
        $refunds = $order->get_refunds();
        if( !empty($refunds) ){
            foreach( $refunds as $refund ){
                foreach( $refund->get_items() as $item_id => $item ){
                    $refunded_quantity      = $item->get_quantity(); // Quantity: zero or negative integer
                    $refunded_line_subtotal = $item->get_subtotal(); // line subtotal: zero or negative number
                    $refunded_item_id       = $item->get_meta('_refunded_item_id'); // line subtotal: zero or negative number
                }
            }
        }
        $order_items = $order->get_items();
        $refund_amount = 0;
        $line_items = array();

        $order_fee_total = 0;
        foreach ( $order->get_fees() as $fee_id => $fee ) {
           $order_fee_total += $fee->get_total();
        }

        if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
            \WC_Subscriptions_Manager::cancel_subscriptions_for_order( $order );
        }
        
        if ( $order_items ) {
            $ob_item_ids = [];
            foreach( $order_items as $item_id => $item ) {
               

               
                if( $replacement_type !== 'main_order_without_order_bump' ){
                    if( $refunded_item_id && $refunded_item_id == $item_id ){
                        continue;
                    }
                    $refund_tax = wc_get_order_item_meta($item_id, '_line_tax', true);
                    $line_items[$item_id] = array(
                        'qty'           => $item->get_quantity(),
                        'refund_total'  => wc_format_decimal(wc_get_order_item_meta($item_id, '_line_total', true)),
                        'refund_tax'    => $refund_tax);

                }else{
                    $ob_meta = wc_get_order_item_meta($item_id, '_wpfunnels_order_bump', true);
                   
                    if( $refunded_item_id && $refunded_item_id == $item_id ){
                        continue;
                    }
                    
                    if( $ob_meta !== 'yes' ){
                       
                        $refund_tax = wc_get_order_item_meta($item_id, '_line_tax', true);

                        $refund_tax = wc_get_order_item_meta($item_id, '_line_tax', true);
                        $amount = wc_get_order_item_meta($item_id, '_line_total', true);
                        $refund_amount = $refund_amount  + ( wc_format_decimal($amount) + wc_format_decimal($refund_tax) );
                        $refund_amount = filter_var(number_format($refund_amount, 2), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                        
                        $line_items[$item_id] = array(
                            'qty'           => $item->get_quantity(),
                            'refund_total'  => wc_format_decimal(wc_get_order_item_meta($item_id, '_line_total', true)),
                            'refund_tax'    => $refund_tax
                        );
                      
                    }else{
                        array_push( $ob_item_ids, $item_id );
                    }
                }
                

            }
        }
        $refund_reason = '';

        if( $replacement_type == 'main_order_without_order_bump' ){
            $refund_reason = __( "The main product is replaced by the offer product", "wpfnl_pro" );
        }elseif( $replacement_type == 'main_order_with_order_bump' ){
            $refund_reason = __( "The main product and the order bump are replaced by the offer product", "wpfnl_pro" );
        }elseif( $replacement_type == 'all_prior_order' ){
            $refund_reason = __( "All products are replaced by the offer product", "wpfnl_pro" );
        }elseif( $replacement_type == 'previous_step' ){
            $refund_reason = __( "The product is replaced by the next offer product", "wpfnl_pro" );
        }else{
            $refund_reason = __( "The main product is replaced by the offer product", "wpfnl_pro" );
        }
        if( $replacement_type !== 'main_order_without_order_bump' ){
            $refund_amount = $order->get_total() - $shipping_cost;
        }else{
            if( empty($ob_item_ids) ){
                $refund_amount = $order->get_total() - $shipping_cost;
            }
                
        }
        $refund_amount = $refund_amount - abs($order_fee_total);
        $refund = wc_create_refund( array(
            'amount'         => $refund_amount,
            'reason'         => $refund_reason ? $refund_reason : '',
            'order_id'       => $order->get_id(),
            'line_items'     => $line_items,
            'refund_payment' => false
        ));
       

        $all_gateways       = WC()->payment_gateways->payment_gateways();
        $payment_method     = $order->get_payment_method();
        $gateway            = isset( $all_gateways[ $payment_method ] ) ? $all_gateways[ $payment_method ] : false;
        if($gateway){
            $result = $gateway->process_refund( $order->get_id(), $refund_amount, $refund_reason );
        }

        if( $replacement_type !== 'main_order_without_order_bump' ){
            $order->update_status('cancelled');
        }
    }


    /**
     * add shipping fee to order
     *
     * @param $order
     * @param $offer_product
     * @throws \WC_Data_Exception
     */
    public static function add_shipping_fee_to_order( \WC_Order $order, $offer_product ) {

        $item = new \WC_Order_Item_Shipping();
        $item->set_method_title( $offer_product['shipping_method_name'] );
        $item->set_method_id( "" );
        $item->set_total( $offer_product['shipping_fee'] );
        $item->save();
        $order->add_item( $item );

        // Show product details in shipping rates section of order data.
        $product_name         = $offer_product['name'] . ' &times; ' . $offer_product['qty'];
        $offer_shipping_items = array( $product_name );
        $item_id              = $item->get_id();
        $offer_itmes          = implode( ',', $offer_shipping_items );
        wc_add_order_item_meta( $item_id, 'Items', $offer_itmes );
        $order->calculate_totals();
        $order->save();
    }


    /**
     * create child order for offer
     *
     * @param $parent_order
     * @param $product_data
     * @param string $type
     * @return bool|\WC_Order|\WP_Error
     * @throws \WC_Data_Exception
     *
     * @since 1.0.0
     */
    public static function create_child_order( $parent_order, $product_data, $type = 'upsell' , $attr = [], $shipping_data = [] )
    {
        $order = false;

        if (!empty($parent_order)) {
            $parent_order->update_meta_data('_wpfunnels_order', 'yes');
            $parent_order_id = $parent_order->get_id();
            $parent_order_billing = $parent_order->get_address('billing');
            $funnel_id = $parent_order->get_meta('_wpfunnels_funnel_id');
            if( !$funnel_id && isset( $product_data['step_id'] ) ){
                $funnel_id = get_post_meta( $product_data['step_id'] ,'_funnel_id', true );
            }
            if (!empty($parent_order_billing['email'])) {
                $customer_id = $parent_order->get_customer_id();

                $order = wc_create_order(
                    [
                        'customer_id' => $customer_id,
                        'status' => 'wc-pending',
                        'parent' => $parent_order_id,
                    ]
                );

                $order->update_meta_data('_wpfunnels_offer', 'yes');
                $order->update_meta_data('_wpfunnels_order', 'yes');
                $order->update_meta_data('_wpfunnels_offer_type', $type);
                $order->update_meta_data('_wpfunnels_parent_funnel_id', $funnel_id);
                $order->update_meta_data('_wpfunnels_funnel_id', $funnel_id);
                $parent_order->update_meta_data('_wpfunnels_funnel_id', $funnel_id);
                $order->update_meta_data('_wpfunnels_offer_step_id', $product_data['step_id']);
                $order->update_meta_data('_wpfunnels_offer_parent_id', $parent_order_id);
                
                if( $attr ){
                    $product_data['args']['variation'] =  $attr;
                }
                
                $new_item_id = $order->add_product( wc_get_product($product_data['id']), $product_data['qty'], $product_data['args']);
                if( $new_item_id ) {
                    wc_add_order_item_meta( $new_item_id, '_wpfnl_' . $type, 'yes' );
                    wc_add_order_item_meta( $new_item_id, '_wpfunnels_' . $type, 'yes' );
                    wc_add_order_item_meta( $new_item_id, '_wpfunnels_step_id', $product_data[ 'step_id' ] );
                }

                $chained_product_class_instance = new \WPFunnelsPro\Compatibility\ChainedProduct();
                $response = [];
                if( Wpfnl_functions::is_wc_active() ){
                    $chained_products = $chained_product_class_instance->get_chain_product_details( $product_data['id'] );
                    $response = $chained_product_class_instance->update_order_item( $order, $chained_products, $product_data['id'] );
                    if( isset($response['override_amount'], $response['total_chained_product_price'] ) && $response['override_amount']  ){
                        $product_data['args']['total'] = $product_data['args']['total'] + $response['total_chained_product_price'];
                    }
                }
                $order->set_address($parent_order->get_address('billing'), 'billing');
                $order->set_address($parent_order->get_address('shipping'), 'shipping');

                $order->set_payment_method($parent_order->get_payment_method());
                $order->set_payment_method_title($parent_order->get_payment_method_title());

                if (!wc_tax_enabled()) {
                    $order->set_shipping_tax(0);
                    $order->set_cart_tax(0);
                    $total = $product_data['total'];
                }else{
                    if ( !wc_prices_include_tax() ) {
                        $tax = 0;
                        foreach ($order->get_items(array('tax')) as $item_id => $line_item) {
                            $order_product_detail = $line_item->get_data();
                            if( isset($order_product_detail['tax_total']) &&  isset($order_product_detail['rate_percent']) ){
                                
                                $tax = ($offer_product['total']*$order_product_detail['rate_percent'])/100;
                                wc_update_order_item_meta($item_id, 'tax_total', $tax);
                                wc_update_order_item_meta($item_id, 'tax_amount', $tax);
                            }	
                        }  
                        $total = $product_data['total'] + $tax;

                    }else{
                        $total = $product_data['total'];
                        
                    }
                }


                $order->calculate_taxes();
                $order->set_total($total);
        
                $offer_orders_meta = $parent_order->get_meta('_wpfunnels_offer_child_orders');
                if ( !is_array($offer_orders_meta) ) {
                    $offer_orders_meta = [];
                }

                $offer_orders_meta[$order->get_id()] = ['type' => $type];
                $parent_order->update_meta_data('_wpfunnels_offer_child_orders', $offer_orders_meta);

                // Save the order.
                $parent_order->save();
                $order->save();
                if ( $parent_order && ( $parent_order->get_total_tax() > 0 || count( $parent_order->get_tax_totals() ) > 0 ) ) {
                    if ( wc_tax_enabled()) {
                        if ( wc_prices_include_tax() ) {
                            $tax_rate = 0;
                            $tax = 0;
                            foreach ($order->get_items(array('tax')) as $item_id => $line_item) {
                                $order_product_detail = $line_item->get_data();
                                if( isset($order_product_detail['rate_percent']) ){
                                    $tax_rate = $order_product_detail['rate_percent'];
                                }	
                            }  
                            
                            $tax = $product_data['args']['total'] - ( $product_data['args']['total'] /( ( $tax_rate/100 ) + 1 ));
                            $rate_id = 0;
                            foreach ($order->get_items(array('tax')) as $item_id => $line_item) {
                                $order_product_detail = $line_item->get_data();
                                if( isset($order_product_detail['rate_id']) ){
                                    $rate_id = $order_product_detail['rate_id'];
                                }
                                wc_update_order_item_meta($item_id, 'tax_amount', $tax);
                                
                            }  
                            $line_tax = [
                                'total' => [
                                    $rate_id => $tax
                                ],
                                'subtotal' => [
                                    $rate_id => $tax
                                ],
                            ];

                            $total_without_tax = $product_data['args']['total'] - $tax;
                            
                            wc_update_order_item_meta($new_item_id, '_line_tax_data', $line_tax);
                            wc_update_order_item_meta($new_item_id, '_line_tax_data', $line_tax);
                            wc_update_order_item_meta($new_item_id, '_line_subtotal_tax', $tax);
                            wc_update_order_item_meta($new_item_id, '_line_tax', $tax);
                            wc_update_order_item_meta($new_item_id, '_line_total', $total_without_tax);
                            wc_update_order_item_meta($new_item_id, '_line_subtotal', $total_without_tax);

                            if(isset( $response['chain_item_details'] )){
                                $chained_product_class_instance->update_tax_ammount( $response['chain_item_details'] );
                            }

                            $order->update_meta_data('_order_total', $product_data['args']['total'] );
                        }
                    }
                }
                $order->save();


            }
        }

        if ($order) {
            $transaction_id = $product_data['transaction_id'];
            ob_start();
            do_action( 'wpfunnels/before_offer_new_child_order_before_completed', $order, $product_data, $parent_order );
            ob_get_clean();
            

            $order->set_transaction_id($transaction_id);
            $order->save();

            $transaction_id_note = '';

            if (!empty($transaction_id)) {
                $transaction_id_note = sprintf(' (Transaction ID: %s)', $transaction_id);
            }

            $order->add_order_note('Offer Accepted | ' . $type . ' | Step ID - ' . $product_data['step_id'] . ' | ' . $transaction_id_note);
            ob_start();
            do_action( 'wpfunnels/child_order_created', $parent_order, $order, $transaction_id );
            ob_get_clean();

            ob_start();
            do_action( 'wpfunnels/child_order_created_' . $parent_order->get_payment_method(), $parent_order, $order, $transaction_id );
            ob_get_clean();
            
            self::payment_complete($order, $transaction_id);
            return $order;
        }
        return false;
    }


    /**
     * check if offer will be added to parent order or will create a separate one
     *
     * @return bool
     */
    public static function is_separate_offer() {
        $offer_settings = Wpfnl_functions::get_offer_settings();
        return $offer_settings['offer_orders'] !== 'main-order';
    }

    /**
     * after payment completed action
     *
     * @param $order
     * @param string $transaction_id
     *
     * @since 1.0.0
     */
    public static function payment_complete($order, $transaction_id = '')
    {
        $payment_method = $order->get_payment_method();
        
        if ('cod' === $payment_method) {
            $order->set_status('processing');
            wc_reduce_stock_levels($order);
        } elseif ('bacs' === $payment_method) {
            $order->set_status('on-hold');
            wc_reduce_stock_levels($order);
        } else {
            $order->payment_complete($transaction_id);
        }
    }


    /**
     * check if any upsell/downsell step is added on that funnel
     *
     * @param $order
     * @return bool
     */
    public static function is_offer_exists( $order ) {
        $funnel_id          = Wpfnl_functions::get_funnel_id_from_order($order->get_id());
        $is_offer_exists    = false;
        if ( $funnel_id ) {
            $steps             = get_post_meta( $funnel_id, '_steps_order', true );
            if($steps) {
                foreach ($steps as $step) {
                    if( 'upsell' === $step['step_type'] || 'downsell' === $step['step_type'] ) {
                        $is_offer_exists = true;
                        break;
                    }
                }
            }
        }

        return $is_offer_exists;
    }


    /**
     * check if there is any upsell/downsell in the funnel
     *
     * @param $order
     * @return bool
     */
    public static function check_if_offer_exists( $order ) {
        $exists     = false;
        $funnel_id  = Wpfnl_functions::get_funnel_id_from_order($order->get_id());
        if( $funnel_id ) {
            $steps      = Wpfnl_functions::get_steps($funnel_id);
            if($steps) {
                foreach ( $steps as $step ) {
                    if( 'upsell' === $step['step_type'] || 'downsell' === $steps['step_type']) {
                        $exists = true;
                        break;
                    }
                }
            }
        }
        return $exists;
    }


    /**
     * Get all order from funnel id
     * 
     * @param $funnel_id
     * 
     * @return $orders
     */
    public static function get_orders_from_funnel_id( $funnel_id )
    {
        global $wpdb;
        $where = '';
        $where .= " WHERE ( (( wpft2.meta_key = '_wpfunnels_funnel_id' AND wpft2.meta_value = $funnel_id ) OR ( wpft2.meta_key = '_wpfunnels_parent_funnel_id' AND wpft2.meta_value = $funnel_id )) ";
        $where .= " AND wpft1.post_status IN ( 'wc-completed', 'wc-processing'))";
        $query = 'SELECT wpft1.ID FROM ' . $wpdb->prefix . 'posts wpft1 
		INNER JOIN ' . $wpdb->prefix . 'postmeta wpft2
		ON wpft1.ID = wpft2.post_id 
		' . $where;

        return $wpdb->get_results($query);
    }


    /**
     * check if doing ajax
     *
     * @return bool
     */
    public static function is_doing_ajax() {
        if ( wp_doing_ajax() || isset( $_GET['wc-ajax'] ) ) {
            if ( isset( $_GET['wc-ajax'] ) && isset( $_POST['_wpfunnels_checkout_id'] ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Covert to xml from array
     * 
     * @param $array
     */
    public static function array_to_xml($array, $rootElement = null, $xml = null) {
        $_xml = $xml;
          
        // If there is no Root Element then insert root
        if ($_xml === null) {
            $_xml = new \SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>');
        }
          
        // Visit all key value pair
        foreach ($array as $k => $v) {
              
            // If there is nested array then
            if (is_array($v)) { 
                  
                // Call function for nested array
                self::array_to_xml($v, $k, $_xml->addChild($k));
            }
                  
            else {
                  
                // Simply add child element. 
                $_xml->addChild($k, $v);
            }
        }
          
        return $_xml->asXML();
    }

    /**
     * Prepare common request body for webhook
     * 
     * @param Array $settings, $content_type, $request_body
     * @return Array
     */
    public static function prepare_common_request_args( $settings, $content_type, $request_body, $event_name, $url ){

        if( $settings['request']['method'] !== 'GET' ){
            if( isset($settings['request']['format']) ){
                if( $settings['request']['format'] == 'FORM' ){
                    $content_type = 'application/x-www-form-urlencoded';
                }elseif( $settings['request']['format'] == 'JSON' ){
                    $content_type = 'application/json';
                    $request_body = json_encode($request_body);
                }else{
                    $content_type = 'text/xml';
                    $request_body = self::array_to_xml( $request_body );
                }
            }
        }

        $request_args = array(
            'body'      => $request_body,
            'method'    => $settings['request']['method'],
            'headers'   => array(
                'Content-Type'        => $content_type,
                'X-WC-Webhook-Source' => $url,
                'X-WC-Webhook-Event'  => $event_name,
                'X-WC-Webhook-ID'     => $settings['id'],
            ),
            'sslverify' => 1
        );
        return $request_args;
    }


    /**
     * Variable product price update after select variation in offer page
     */
    public static function update_variation_product_details( $step_id, $selected_product_id, $input_qty, $order_id ){
        $product_id = $selected_product_id;
        $product_qty = $input_qty;
        $step_type      = get_post_meta( $step_id, '_step_type', true );
        $funnel_id      = get_post_meta( $step_id, '_funnel_id', true );
        $discount    = get_post_meta( $step_id, '_wpfnl_'.$step_type.'_discount', true );
        $cancel_main_order  = false;
        $amount_diff        = 0;
        $data = '';
        
        if( $product_id ){
            $data = self::prepare_offer_data( $step_id , $product_id, $product_qty, $step_type, $discount, $cancel_main_order, $amount_diff, $order_id);
        }
        return apply_filters( 'wpfunnels/offer_product_data', $data, $funnel_id, $step_id );
    }


    /**
     * prepare offer data for accept
     */
    public static function prepare_offer_data( $step_id , $product_id, $product_qty, $step_type, $discount, $cancel_main_order, $amount_diff, $order_id){
        $product        = wc_get_product( $product_id );
        if($product) {
            $main_qty       = $product->get_stock_quantity();
            if ($main_qty <= 0 && $product->get_manage_stock() == true){
                $product_qty    =  0;
            }
            $product_qty    = intval( $product_qty );
            $order          = wc_get_order($order_id);

            $product_type   = $product->get_type();
            $original_price = $product->get_price( 'edit' );
            $custom_price   = $original_price;


            if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
                if( 'subscription_variation' === $product->get_type() || 'subscription' === $product->get_type() ){
                    $signUpFee      = \WC_Subscriptions_Product::get_sign_up_fee( $product );
                    $custom_price   = $custom_price + $signUpFee;
                }
            }

            $unit_price     = $custom_price;
            $unit_price_tax = $custom_price;
            $custom_price 		= floatval($custom_price);
            $product_price  = $custom_price * $product_qty;

            /** tax calculation */
            $tax_enabled            = get_option( 'woocommerce_calc_taxes' );
            $shipping_fee           = 0;
            $shipping_incl_tax      = 0;
            $shipping_excl_tax      = 0;
            $shipping_method_name   = 0;
            if( $order ) {
                $shipping_fee           = $order->get_shipping_total();
                $shipping_method_name   = $order->get_shipping_method();
            }

            
            if ( 'yes' === $tax_enabled ) {
                if ( !wc_prices_include_tax() ) {
                    $product_price     = wc_get_price_excluding_tax( $product, array( 'price' => $product_price ) );
                    $shipping_excl_tax = wc_get_price_excluding_tax( $product, array( 'price' => $shipping_fee ) );
                } else {
                    $product_price       = wc_get_price_including_tax( $product, array( 'price' => $product_price ) ) ;
                    $shipping_incl_tax  = wc_get_price_including_tax( $product, array( 'price' => $shipping_fee ) );
                }
                $unit_price_tax = $custom_price;
            }

            $shipping_incl_tax = $shipping_incl_tax  ? $shipping_incl_tax : $shipping_fee;
            $shipping_excl_tax = $shipping_excl_tax ? $shipping_excl_tax : $shipping_fee;

            $product_price = apply_filters('wpfunnels/modify_offer_product_price_data_without_discount', $product_price);

            /** if offer product has discount */
            if($discount) {
                $discount_instance = new WpfnlDiscount();
                        
                if( !$discount_instance->maybe_time_bound_discount( $step_id ) || ($discount_instance->maybe_time_bound_discount( $step_id ) && $discount_instance->maybe_validate_discount_time( $step_id )) ) {
                    $discount_type	    = $discount['discountType'];
                    $discount_apply_to	= $discount['discountApplyTo'];
                    $discount_value	    = $discount['discountValue'];
                    if( 'discount-percentage' === $discount_type || 'discount-price' === $discount_type ) {
                        $regular_price =  $product->get_type() == 'variable' ? $product->get_price() : $product->get_regular_price();
                        $sale_price    =  $product->get_type() == 'variable' ? $product->get_price() : $product->get_sale_price();
                        if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
                            if( 'subscription_variation' === $product->get_type() || 'subscription' === $product->get_type() ){
                                $signUpFee      = \WC_Subscriptions_Product::get_sign_up_fee( $product );
                                $regular_price   = $regular_price + $signUpFee;
                            }
                        }

                       

                        $product_price      = $discount_apply_to === 'sale' ? $sale_price : $regular_price;
                        $product_price      = $product_price ? $product_price : $regular_price;
                        $product_price      = $product_price ? $product_price : $product->get_price();
                        $product_price 		= floatval($product_price);
                        $product_price 		= \WPFunnelsPro\Wpfnl_Pro_functions::calculate_discount_price_for_widget( $discount_type , $discount_value, $product_price * $product_qty );
                        
                        $product_price = apply_filters('wpfunnels/modify_offer_product_price_data_with_discount', $product_price);
                        $custom_price       = $product_price;
                        $unit_price         = $product_price;
                        $unit_price_tax     = $product_price;
                    }
                }
            }

            
            $data = array(
                'step_id'                 => $step_id,
                'id'                      => $product_id,
                'name'                    => $product->get_type() == 'variable' ? Wpfnl_functions::get_formated_product_name( $product ) : $product->get_title(),
                'desc'                    => self::get_item_description($product),
                'qty'                     => $product_qty,
                'original_price'          => $original_price,
                'regular_price'           => $product->get_regular_price(),
                'sale_price'              => $product->get_sale_price(),
                'unit_price'              => $unit_price,
                'unit_price_tax'          => $unit_price_tax,
                'args'                    => array(
                    'subtotal' => $product_price,
                    'total'    => $product_price,
                ),
                'shipping_fee'            => $shipping_excl_tax,
                'shipping_fee_incl_tax'   => $shipping_incl_tax,
                'shipping_method_name'    => $shipping_method_name,
                'price'                   => $custom_price,
                'url'                     => $product->get_permalink(),
                'total_unit_price_amount' => preg_replace('/[^\d.]/', '', $unit_price_tax ) * $product_qty,
                'total'                   => $product_price,
                'cancel_main_order'       => $cancel_main_order,
                'amount_diff'             => $amount_diff,
                'discount'                => $discount ? true : false,
                'discount_type'           => isset($discount['discountType']) ? $discount['discountType'] : '',
                'discount_apply_to'       => isset($discount['discountApplyTo']) ? $discount['discountApplyTo'] : '',
                'discount_value'          => isset($discount['discountValue']) ? $discount['discountValue'] : '',
                'offer_button'            => 'yes'
            );
            
            return $data;
        }
        return false;
    }
    
    /*
     * Get offer product type
     * 
     * @param String $post_id
     *  
     */
    public static function get_offer_product_type( $step_id = '' ){

        if( $step_id ){
            $step_type = get_post_meta( $step_id, '_step_type', true );
            if( $step_type == 'upsell' || $step_type == 'downsell' ){
                $offer_product = self::get_offer_product( $step_id, $step_type );
                if( is_array($offer_product) ) {
                    $product_type = '';
                    foreach ( $offer_product as $pr_index => $pr_data ) {
                        $product_id = $pr_data['id'];
                        $product    = wc_get_product( $product_id );
                        if( $product ){
                            $product_type = $product->get_type();
                            break;
                        }
                    }
                    return $product_type;
                }
            }
        }
        return false;
    }
    


    /**
     * Check product is variable or not
    */
    public static function check_is_variable_product( $step_id ) {
        $funnel_id = get_post_meta($step_id, '_funnel_id', true);
        $type = get_post_meta($funnel_id, '_wpfnl_funnel_type', true);

        if( $type == 'lms' ){
            return false;
        }


        $step_type = get_post_meta($step_id, '_step_type', true);
        $products = get_post_meta($step_id, '_wpfnl_'.$step_type.'_products', true);
        

        if(is_array($products) && !empty($products)){
            foreach( $products as $product ){
                $get_product = wc_get_product($product['id']);
                if( $get_product ){
                    if($get_product->get_type() == 'variable'){
                        return true;
                    }
                }
            }
        }

        return false;
        
    }

    /**
     * Get offer product price
    */
    public static function get_offer_product_price( $step_id ) {

        $funnel_id = get_post_meta($step_id, '_funnel_id', true);
        $type = get_post_meta($funnel_id, '_wpfnl_funnel_type', true);

        if( $type == 'lms' ){
            return false;
        }

        $step_type = get_post_meta($step_id, '_step_type', true);
        $products = get_post_meta($step_id, '_wpfnl_'.$step_type.'_products', true);
        
        $product_price = '';
        if( $products ){
            foreach( $products as $product ){
                $get_product = wc_get_product($product['id']);
                if( $get_product ){
                    $product_price = $get_product->get_price_html();
                }
            }
    
        }
        
        return $product_price;
        
    }



	/**
	 * Encrypt key
	 */
	public static function encrypt_key( $key )
	{
		$encrypted_key = Wpfunnels_Aes_Ctr::encrypt( $key, WPFNL_SECURITY_KEY, 256 );
		return $encrypted_key;
	}

    
    /**
     * Decrypt a key with AES
     *
     * @param $key
     * @return string
     */
    public static function decrypt_key( $key )
    {
        $encrypted_key = Wpfunnels_Aes_Ctr::decrypt( $key, WPFNL_SECURITY_KEY, 256 );
        return $encrypted_key;
    }

    
    /**
     * Senitize request data
     * 
     * @param array $data
     * 
     * @return array
     */
    public static function get_sanitized_get_post( $data = [] )
    {
        if ( is_array( $data ) && !empty( $data ) ) {
            return filter_var_array( $data, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        }
        return array(
            'get' => filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'post' => filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'request' => filter_var_array( $_REQUEST, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        );
    }


    /**
     * Retrive dynamic offer product for GBF
     * 
     * @return int|mixed
     * 
     * @since 1.6.9
     */
    public static function get_gbf_product_from_cookie() {
        return WC()->session->get('wpfunnels_global_funnel_specific_product') ? WC()->session->get('wpfunnels_global_funnel_specific_product') : [];
    }


    /**
     * Get random product for showing dummy data in editor backend
     * 
     * @return Array || Bool
     * 
     * @since 1.6.9
     * 
     */
    public static function get_random_product(){

        if( Wpfnl_functions::is_wc_active() ){
            global $post; // setup_postdata will not work without this being set (outside of the foreach loop)
            $args = array(
                'posts_per_page'   => 1,
                'orderby'          => 'rand',
                'post_type'        => 'product' ); 

            $random_products = get_posts( $args );
            if( is_array($random_products) ){
                if( isset( $random_products[0]->ID ) ){
                    return $random_products[0]->ID;
                }
            }
        }
        return false;
    }


    /**
     * Get product data for widget
     * 
     * @param String 
     * 
     * @return Array
     * @since 1.6.8
     * 
     */
    public static function get_product_data_for_widget( $step_id = '' ){

        if( $step_id && Wpfnl_functions::is_wc_active() ){
            $funnel_id  = get_post_meta( $step_id, '_funnel_id', true );
            $step_type  = get_post_meta( $step_id, '_step_type', true );
            $is_gbf     = get_post_meta( $funnel_id, 'is_global_funnel', true );
            $offer_product = '';
            if( 'yes' === $is_gbf && ( 'upsell' === $step_type || 'downsell' === $step_type ) ){
                if( is_plugin_active('wpfunnels-pro-gbf/wpfnl-pro-gb.php') ){
                    $instance = new Wpfnl_Pro_GB_Functions();
                    $offer_rules = get_post_meta( $step_id, 'global_funnel_'.$step_type.'_rules' , true );
                    $quantity = isset($offer_rules['quantity']) ? $offer_rules['quantity'] : 1;
                    $rules_type = isset( $offer_rules['type'] ) ? $offer_rules['type'] : '';
                    if( 'moreQuantity' == $rules_type ){
                        $rand_product_id     =  self::get_random_product();
                        $offer_product       = wc_get_product( $rand_product_id );
                        $get_product_type    = $offer_product ? $offer_product->get_type() : '';
                        $quantity = isset( $gbf_product[0]['quantity'] ) ? $quantity + $gbf_product[0]['quantity'] : $quantity; 

                    }elseif( 'specificProduct' == $rules_type ){
                        $rules  = get_post_meta( $step_id, 'global_funnel_'.$step_type.'_rules', true );
                        $offer_product = '';
                        if( is_array($rules) ){
                            if( isset($rules['show']) && $rules['show'] ){
                                $offer_product = wc_get_product($rules['show']);
                            }
                        }
                        $get_product_type = $offer_product ?  $offer_product->get_type() : '';
                        
                    }elseif( 'randomProduct' == $rules_type ){
                        $offer_mappings  = get_post_meta( $step_id, 'global_funnel_'.$step_type.'_rules', true );
                        if( $offer_mappings ){
                            $category        = isset($offer_mappings['category']) ? $offer_mappings['category'] : null;
                            $function_exist = is_callable(array($instance, 'get_random_product_in_category_for_offer'));
                            if( $function_exist ){
                                $id = "";
                                if( is_admin() ){
                                    $dynamic_product = Wpfnl_Pro_GB_Functions::get_random_product_in_shop_for_widget( 1 );
                                    if( isset($dynamic_product[0]->ID) ){
                                        $id = $dynamic_product[0]->ID;
                                    }
                                }else{
                                    $dynamic_product   = json_decode( wp_unslash( get_option( 'wpfunnels_dynamic_offer_data' ) ), true );
                                    if( is_array($dynamic_product) && isset($dynamic_product[0]['ID'])){
                                        $id = $dynamic_product[0]['ID'];
                                    }
                                }
                                    
                                if( $id ){
                                    $offer_product = wc_get_product($id);
                                    $get_product_type = $offer_product ?  $offer_product->get_type() : '';
                                }
                                
                            }
                            
                        }
                        
                    }elseif( 'randomProductInsideCategoryWithinPrice' == $rules_type ){
                        $offer_mappings  = get_post_meta( $step_id, 'global_funnel_'.$step_type.'_rules', true );
                        if( $offer_mappings ){
                            $category        = isset($offer_mappings['category']) ? $offer_mappings['category'] : null;
                            $function_exist = is_callable(array($instance, 'get_random_product_in_category_within_price_for_offer_for_widget'));
                            
                            if( $function_exist ){
                            $id = "";
                                if( is_admin() ){
                                    $dynamic_product = Wpfnl_Pro_GB_Functions::get_random_product_in_category_within_price_for_offer_for_widget( 1 );
                                    if( isset($dynamic_product[0]->ID) ){
                                        $id = $dynamic_product[0]->ID;
                                    }
                                }else{
                                    $dynamic_product   = json_decode( wp_unslash( get_option( 'wpfunnels_dynamic_offer_data' ) ), true );
                                    if( is_array($dynamic_product) && isset($dynamic_product[0]['ID'])){
                                        $id = $dynamic_product[0]['ID'];
                                    }
                                }
                                
                                if( $id ){
                                    $offer_product = wc_get_product($id);
                                    $get_product_type = $offer_product ?  $offer_product->get_type() : '';
                                }
                            }
                            
                        }
                        
                    }elseif( 'highestSoldInsideCategory' == $rules_type ){
                        $offer_mappings  = get_post_meta( $step_id, 'global_funnel_'.$step_type.'_rules', true );
                        if( $offer_mappings ){
                            $category        = isset($offer_mappings['category']) ? $offer_mappings['category'] : null;
                            $function_exist = is_callable(array($instance, 'get_highest_sold_product_in_category_for_offer'));
                            if( $function_exist ){
                                $highestSoldInsideCategory  = Wpfnl_Pro_GB_Functions::get_highest_sold_product_in_category_for_offer( 1, $category );
                                if( is_array($highestSoldInsideCategory) && isset($highestSoldInsideCategory['posts'][0]->ID)){
                                    $id = $highestSoldInsideCategory['posts'][0]->ID;  
                                    $offer_product = wc_get_product($id);
                                    $get_product_type = $offer_product ?  $offer_product->get_type() : '';
                                }
                            }
                        }
                        
                    }elseif( 'highestSoldInsideTag' == $rules_type ){
                        $offer_mappings  = get_post_meta( $step_id, 'global_funnel_'.$step_type.'_rules', true );
                        if( $offer_mappings ){
                            $tag        = isset($offer_mappings['tag']) ? $offer_mappings['tag'] : null;
                            $function_exist = is_callable(array($instance, 'get_highest_sold_product_in_tag_for_offer'));
                            if( $function_exist ){
                                $highestSoldInsideTag  = Wpfnl_Pro_GB_Functions::get_highest_sold_product_in_tag_for_offer(  $tag , 1 );
                                if( is_array($highestSoldInsideTag) && isset($highestSoldInsideTag['posts'][0]->ID)){
                                    $id = $highestSoldInsideTag['posts'][0]->ID;  
                                    $offer_product = wc_get_product($id);
                                    $get_product_type = $offer_product ?  $offer_product->get_type() : '';
                                }
                            }
                        }
                        
                    }elseif( 'highestSold' == $rules_type ){
                        $function_exist = is_callable(array($instance, 'get_highest_sold_product_for_offer'));
                        if( $function_exist ){
                            $highestSold  = Wpfnl_Pro_GB_Functions::get_highest_sold_product_for_offer(1);
                            if( is_array($highestSold) && isset($highestSold['posts'][0]->ID)){
                                $id = $highestSold['posts'][0]->ID;  
                                $offer_product = wc_get_product($id);
                                $get_product_type = $offer_product ?  $offer_product->get_type() : '';
                            }
                        }
                    }elseif( 'randomProductInTag' == $rules_type ){
                        $function_exist = is_callable(array($instance, 'get_random_product_in_tag_for_offer_for_widget'));
                        if( $function_exist ){
                            $id = '';
                            if( is_admin() ){
                                $dynamic_product = Wpfnl_Pro_GB_Functions::get_random_product_in_tag_for_offer_for_widget( 1 );
                                if( isset($dynamic_product[0]->ID) ){
                                    $id = $dynamic_product[0]->ID;
                                }
                            }else{
                                $dynamic_product   = json_decode( wp_unslash( get_option( 'wpfunnels_dynamic_offer_data' ) ), true );
                                if( is_array($dynamic_product) && isset($dynamic_product[0]['ID'])){
                                    $id = $dynamic_product[0]['ID'];
                                }
                            }
                            if( $id ){
                                $offer_product = wc_get_product($id);
                                $get_product_type = $offer_product ?  $offer_product->get_type() : '';
                            }
                        }
                    }elseif( 'randomProductInsideTagWithinPrice' == $rules_type ){
                        $function_exist = is_callable(array($instance, 'get_random_product_in_tag_within_price_for_offer_for_widget'));
                        if( $function_exist ){
                            $id = '';
                            if( is_admin() ){
                                $dynamic_product = Wpfnl_Pro_GB_Functions::get_random_product_in_tag_within_price_for_offer_for_widget( 1 );
                                
                                if( isset($dynamic_product[0]->ID) ){
                                    $id = $dynamic_product[0]->ID;
                                }
                            }else{
                                $dynamic_product   = json_decode( wp_unslash( get_option( 'wpfunnels_dynamic_offer_data' ) ), true );
                                if( is_array($dynamic_product) && isset($dynamic_product[0]['ID'])){
                                    $id = $dynamic_product[0]['ID'];
                                }
                                

                            }
                            if( $id ){
                                $offer_product = wc_get_product($id);
                                $get_product_type = $offer_product ?  $offer_product->get_type() : '';
                            }
                        }
                    }elseif( 'randomInShop' == $rules_type ){
                        $id = '';
                        if( is_admin() ){
                            $dynamic_product = Wpfnl_Pro_GB_Functions::get_random_product_in_shop_for_widget( 1 );
                            if( isset($dynamic_product[0]->ID) ){
                                $id = $dynamic_product[0]->ID;
                            }
                        }else{
                            $dynamic_product   = json_decode( wp_unslash( get_option( 'wpfunnels_dynamic_offer_data' ) ), true );
                            if( is_array($dynamic_product) && isset($dynamic_product[0]['ID'])){
                                $id = $dynamic_product[0]['ID'];
                            }
                        }
                        if( $id ){
                            $offer_product = wc_get_product($id);
                            $get_product_type = $offer_product ?  $offer_product->get_type() : '';
                        }
                    }
                }
            }else{
                $offer_product_data = self::get_offer_product( $step_id, $step_type );
                $quantity = isset($offer_product_data['quantity']) ? $offer_product_data['quantity'] : 1;
                $product            = null;
                if( is_array($offer_product_data) ) {
                    foreach ( $offer_product_data as $pr_index => $pr_data ) {
                        $product_id = $pr_data['id'];
                        $product    = wc_get_product( $product_id );
                        break;
                    }
                }

                $offer_product = $product;
                $get_product_type = $offer_product ?  $offer_product->get_type() : '';
            }

           
            if( $offer_product && $get_product_type ){
                
                return [
                    'offer_product'     => $offer_product,
                    'get_product_type'  => $get_product_type,
                    'is_gbf'            => $is_gbf,
                    'quantity'          => $quantity,
                ];
            }
        }

        return [
            'offer_product'     => '',
            'get_product_type'  => '',
            'is_gbf'            => 'no',
            'quantity'          => 1,
        ];

    }


    /**
     * @desc update order shipping values with offer order shipping
     * @since 1.6.21
     * @param \WC_Order $order
     * @param $offer_shipping
     * @param $update
     * @return \WC_Order
     * @throws \WC_Data_Exception
     */
    private static function update_offer_order_shipping( \WC_Order $order, $offer_shipping, $update = true ) {

        $order->calculate_shipping();
        $order->calculate_totals();
        return $order;
    }


    /**
     * Get supported CRM
     * 
     * @return Array $integrations
     * @since 1.6.27
     */
    public static function get_supported_crm(){
        $integrations   = array(
            'fluent_crm' => array(
                'class_name'    => 'FluentCRM',
            )
        );
        return apply_filters('wpfunnels/supported_crm_integrations', $integrations );;
    }


    /**
     * Modify offer product if type is variable
     * 
     * @param array $payload
     * 
     * @return mix
     * @since 1.7.6
     */
    public static function modify_offer_product( $payload ){
        if( isset($payload['product_id'], $payload['data']) ){
            $variation_id = (new \WC_Product_Data_Store_CPT())->find_matching_product_variation(
                new \WC_Product($payload['product_id']),
                $payload['data']
            );
            return $variation_id;
        }
        return false;
    }


    /**
     * Delete automation by funnel id
     * 
     * @param int $funnel_id
     */
    public static function delete_automation_by_funnel_id( $funnel_id ){
        if(  Wpfnl_functions::is_mint_mrm_active() && class_exists("Mint\\MRM\\DataBase\\Tables\\AutomationMetaSchema") && class_exists("MintMail\\App\\Internal\\Automation\\AutomationModel") && class_exists("Mint\\MRM\\DataBase\\Tables\\AutomationSchema")  ) {
            
            $automationSchema = "Mint\\MRM\\DataBase\\Tables\\AutomationSchema";
            $automationMetaSchema = "Mint\\MRM\\DataBase\\Tables\\AutomationMetaSchema";
            $automationModel = "MintMail\\App\\Internal\\Automation\\AutomationModel";
            
            global $wpdb;
            $automation_table = $wpdb->prefix . $automationSchema::$table_name;
            $automation_meta_table = $wpdb->prefix . $automationMetaSchema::$table_name;

            $automations = $wpdb->get_results( $wpdb->prepare( "SELECT automation.id as id FROM $automation_table as automation INNER JOIN $automation_meta_table as automation_meta ON automation.id = automation_meta.automation_id WHERE automation_meta.meta_key = %s AND automation_meta.meta_value = %s", array( 'funnel_id', $funnel_id ) ), ARRAY_A ); // db call ok. ; no-cache ok.   
            $wpdb->query( $wpdb->prepare( "DELETE automation FROM $automation_table as automation INNER JOIN $automation_meta_table as automation_meta ON automation.id = automation_meta.automation_id WHERE automation_meta.meta_key = %s AND automation_meta.meta_value = %s", array( 'funnel_id', $funnel_id ) ) );
            if( $automations && is_array( $automations ) ){
                foreach( $automations as $automation ){
                    if( isset($automation['id']) ){
                        $function_exist = is_callable(array($automationModel, 'delete_child_row_by_autoamtion_id'));
                        if( $function_exist ){
                            $automationModel::delete_child_row_by_autoamtion_id( $automation['id'] );
                        }
                        
                    }
                }
            }
        }
    }
    


    /**
     * Create licenses for products in an order that contain licensed products.
     *
     * @param int    $order_id     The ID of the order.
     * @param object $license_obj  The license object.
     * 
     * @since 1.8.8
     * @return void
     */
    public static function create_license($order_id, $license_obj){
        // Check if the order contains any licensed products
        $order_data = new \WC_Order($order_id);
        $order_products = $order_data->get_items();

        $found_licensed_product = false;
        foreach ($order_products as $key => $order_product) {
            if (\WOO_SL_functions::is_product_licensed($order_product->get_product_id())) {
                $found_licensed_product = true;
                break;
            }
        }

        if (false === $found_licensed_product) {
            return;
        }

        // Iterate through the order items and create licenses for licensed products
        $_woo_sl = array();
        foreach ($order_products as $key => $order_product) {
            if (!$license_obj->is_product_licensed($order_product->get_product_id())) {
                continue;
            }

            $is_licence_extend = false;
            $_woo_sl_extend = wc_get_order_item_meta($key, '_woo_sl_extend', true);

            if (!empty($_woo_sl_extend)) {
                $is_licence_extend = true;
            }

            // Skip processing if it is a license extend
            if (true === $is_licence_extend) {
                continue;
            }

            // Check against the variation if it is assigned a license group
            if ($order_product->get_variation_id() > 0) {
                $variation_license_group_id = get_post_meta($order_product->get_variation_id(), '_sl_license_group_id', true);

                if ('' === $variation_license_group_id) {
                    continue;
                }
            }

            // Get product licensing details
            $product_sl_groups = \WOO_SL_functions::get_product_licensing_groups($order_product->get_product_id());

            // If it is a variation, filter out the license groups
            if ($order_product->get_variation_id() > 0) {
                if (isset($product_sl_groups[$variation_license_group_id])) {
                    $_product_sl_groups = $product_sl_groups;
                    $product_sl_groups = array();
                    $product_sl_groups[$variation_license_group_id] = $_product_sl_groups[$variation_license_group_id];
                } else {
                    $product_sl_groups = array();
                }
            }

            // Prepare data arrays for each licensing group
            $_group_title = array();
            $_licence_prefix = array();
            $_max_keys = array();
            $_max_instances_per_key = array();
            $_use_predefined_keys = array();
            $_product_use_expire = array();
            $_product_expire_renew_price = array();
            $_product_expire_units = array();
            $_product_expire_time = array();
            $_product_expire_starts_on_activate = array();
            $_product_expire_disable_update_link = array();
            $_product_expire_limit_api_usage = array();
            $_product_expire_notice = array();

            foreach ($product_sl_groups as $product_sl_group) {
                $_group_title[] = $product_sl_group['group_title'];
                $_licence_prefix[] = $product_sl_group['licence_prefix'];
                $_max_keys[] = $product_sl_group['max_keys'];
                $_max_instances_per_key[] = $product_sl_group['max_instances_per_key'];
                $_use_predefined_keys[] = $product_sl_group['use_predefined_keys'];

                $_product_use_expire[] = $product_sl_group['product_use_expire'];
                $_product_expire_renew_price[] = $product_sl_group['product_expire_renew_price'];
                $_product_expire_units[] = $product_sl_group['product_expire_units'];
                $_product_expire_time[] = $product_sl_group['product_expire_time'];
                $_product_expire_starts_on_activate[] = $product_sl_group['product_expire_starts_on_activate'];
                $_product_expire_disable_update_link[] = $product_sl_group['product_expire_disable_update_link'];
                $_product_expire_limit_api_usage[] = $product_sl_group['product_expire_limit_api_usage'];
                $_product_expire_notice[] = $product_sl_group['product_expire_notice'];
            }

            // Prepare the license data array
            $data['group_title'] = $_group_title;
            $data['licence_prefix'] = $_licence_prefix;
            $data['max_keys'] = $_max_keys;
            $data['max_instances_per_key'] = $_max_instances_per_key;
            $data['use_predefined_keys'] = $_use_predefined_keys;
            $data['product_use_expire'] = $_product_use_expire;
            $data['product_expire_renew_price'] = $_product_expire_renew_price;
            $data['product_expire_units'] = $_product_expire_units;
            $data['product_expire_time'] = $_product_expire_time;
            $data['product_expire_starts_on_activate'] = $_product_expire_starts_on_activate;
            $data['product_expire_disable_update_link'] = $_product_expire_disable_update_link;
            $data['product_expire_limit_api_usage'] = $_product_expire_limit_api_usage;
            $data['product_expire_notice'] = $_product_expire_notice;

            // Apply filters to the license data
            $data = apply_filters('woo_sl/order_processed/product_sl', $data, $order_product, $order_id);

            // Update order item meta with the license data
            wc_update_order_item_meta($key, '_woo_sl', $data);

            // Set the licensing status as active
            wc_update_order_item_meta($key, '_woo_sl_licensing_status', 'active');

            // Process the licensing expiration for each data block
            foreach ($data['product_use_expire'] as $data_key => $data_block_value) {
                if ('no' !== $data_block_value) {
                    wc_update_order_item_meta($key, '_woo_sl_licensing_using_expire', $data_block_value);

                    // Continue only if expire_starts_on_activate is not set to yes
                    $expire_starts_on_activate = $data['product_expire_starts_on_activate'][$data_key];
                    if ('yes' === $expire_starts_on_activate) {
                        // Set the licensing status as not-activated
                        wc_update_order_item_meta($key, '_woo_sl_licensing_status', 'not-activated');
                        continue;
                    }

                    if ('yes' === $data_block_value) {
                        $today = date("Y-m-d", current_time('timestamp'));
                        $start_at = strtotime($today);
                        wc_update_order_item_meta($key, '_woo_sl_licensing_start', $start_at);

                        $_sl_product_expire_units = $data['product_expire_units'][$data_key];
                        $_sl_product_expire_time = $data['product_expire_time'][$data_key];
                        $expire_at = strtotime("+ " . $_sl_product_expire_units . " " . $_sl_product_expire_time, $start_at);
                        wc_update_order_item_meta($key, '_woo_sl_licensing_expire_at', $expire_at);
                    }
                }
            }
        }
    }

    /**
	 * Check if woocommerce payment is activated or not
	 *
	 * @return bool
	 * @since  1.9.0
	 */
	public static function is_wc_payment_active()
	{
		if (defined('WCPAY_PLUGIN_FILE')) {
           return true;
        }
		return false;
	}


    /**
	 * Calculate discount price
	 *
	 * @param $discount_type
	 * @param $discount_value
	 * @param $product_price
	 * 
	 * @return string
	 */
	public static function calculate_discount_price_for_widget( $discount_type, $discount_value, $product_price ) {

		$custom_price = $product_price;
		if (!empty($discount_type)) {
			if ('discount-percentage' === $discount_type) {
				if ( $discount_value > 0 && $discount_value <= 100) {
					$custom_price = (float)$product_price - ((float)($product_price * $discount_value) / 100);
				}
			} elseif ('discount-price' === $discount_type) {
				if ($discount_value > 0) {
					$custom_price = $product_price - $discount_value;
				}
			}
		}
		return $custom_price;
	}

    /**
     * Retrieves an array of order IDs associated with a specific funnel ID.
     *
     * This function queries the WordPress database to retrieve order IDs linked to a given funnel ID.
     *
     * @param int $funnel_id The ID of the funnel to retrieve order IDs for.
     * @return array An array of order IDs linked to the provided funnel ID.
     * @since  1.9.6
     */
    public static function get_order_ids_by_funnel_id( $funnel_id ) {
        global $wpdb;

        // Determine the appropriate order meta table and column based on WooCommerce settings.
        $order_meta_table = $wpdb->postmeta;
        $column           = 'post_id';
        if( 'yes' === get_option( 'woocommerce_custom_orders_table_enabled', 'no' ) ) {
            $order_meta_table = "{$wpdb->prefix}wc_orders_meta";
            $column           = 'order_id';
        }

        // Query the database to retrieve order IDs associated with the provided funnel ID.
        $order_ids = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT %i FROM {$order_meta_table} WHERE `meta_key`=%s AND `meta_value`=%d",
                [ $column, '_wpfunnels_funnel_id', $funnel_id ]
            ),
            ARRAY_A
        );

        // Extract and return an array of order IDs.
        return is_array( $order_ids ) && !empty( $order_ids ) ? array_column( $order_ids, $column ) : [];
    }


    /**
     * Checks if a payment gateway is potentially unsupported.
     *
     * @return void
     * @since  2.0.5
     */
    public static function maybe_unsupported_payment_gateway(){
       
        if( !Wpfnl_functions::is_wc_active() || !isset( $_GET['wpfnl-order'] ) ){
            return false;
        }

        $order_id  = ( isset( $_GET['wpfnl-order'] ) ) ? intval( $_GET['wpfnl-order'] ) : 0;
        $order     = wc_get_order( $order_id );
        if ( false === is_a( $order, 'WC_Order' ) ) {
            return false;
        }
        $payment_method = $order->get_payment_method();
       
        if ( $payment_method ) {
            $payment_gateways     = Payment_Gateways_Factory::getInstance()->get_supported_payment_gateways();
            if ( !isset( $payment_gateways[$payment_method] ) ) {
                return true;
            }else{
                return false;
            }
        }
        return true;
    }

    /**
	 * get_available_payment_methods: fetched available payment methods
	 *
     * @since 2.1.1
	 * @return array
	 */
	public static function get_available_payment_methods(){
		if( Wpfnl_functions::is_wc_active() ) {
			$gateways = WC()->payment_gateways->get_available_payment_gateways();
			$enabled_gateways = [];

			if( $gateways ) {
				foreach( $gateways as $key =>  $gateway ) {
					if( $gateway->enabled == 'yes' ) {
						$enabled_gateways[$key] = $gateway->method_title;
					}
				}
			}
			return $enabled_gateways;
		}
		return [];
	}

	    /**
     * generate_guid: generate 32 character unique GUID
     *
     * @since 2.1.1
     * @return string
     */
    public static function generate_guid() {

        $unique_id = uniqid(mt_rand(), true);
        $md5_hash = md5($unique_id);
        $guid = substr($md5_hash, 0, 32);
    
        return $guid;
    }
}