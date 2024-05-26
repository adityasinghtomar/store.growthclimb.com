<?php
namespace WPFunnelsPro\Admin\OfferProduct;
use WPFunnels\Admin\FunnelType\Wpfnl_Funnel_Type;
use WPFunnels\Admin\Module\Wpfnl_Admin_Module;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;
use \WC_Subscriptions_Product;


class Wpfunnels_Pro_Wc_OfferProduct extends Wpfnl_Pro_OfferProduct
{

    /**
     * add upsell item to post meta 
     * 
     * @param String $id
     * @return Array
     * @since 2.4.6
     */
    public function add_upsell_items( $id, $data, $step_id ){

        $product = wc_get_product($id);
        if ( !empty($product) ) {
            $prefix = '_wpfnl_upsell_';
            $title      = $product->get_type() == 'variation' ? Wpfnl_functions::get_formated_product_name( $product ) : $product->get_name();
            $price      = $product->get_price();
            $signUpFee = 0;
            if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
                if( 'subscription_variation' === $product->get_type() || 'subscription' === $product->get_type() ){
                    $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee( $product );
                    $price = $price + $signUpFee;
                }
            }
            $pr_image   = wp_get_attachment_image_src($product->get_image_id(), 'single-post-thumbnail');
            update_post_meta( $step_id, $prefix.'products', $data );
            $sale_price     = $product->get_sale_price();
            if ($sale_price != "") {
                $sale_price = $product->get_sale_price() + floatval($signUpFee);
            }
            return array(
                'success' => true,
                'step_id' => $step_id,
                'products' => array(
                    'id'                => $id,
                    'title'             => $title,
                    'price'             => wc_price($price),
                    'numeric_price'     => $price,
                    'currency' 			=> get_woocommerce_currency_symbol(),
                    'quantity'          => 1,
                    'image'             => $pr_image ? $pr_image[0] : '',
                    'sale_price'        => $sale_price ? $sale_price : $price,
                    'html_price'        => $product->get_price_html(),
                    'regular_price'     => $product->get_regular_price() + floatval($signUpFee),
                    'product_edit_link' => in_array($product->get_type(), array( 'variation', 'subscription_variation' )) ? get_edit_post_link($product->get_parent_id()) : get_edit_post_link($product->get_id()),
                    'product_view_link' => in_array($product->get_type(), array( 'variation', 'subscription_variation' )) ? get_permalink($product->get_parent_id()) : get_permalink($product->get_id()),
                    'is_trash'          => 'trash' === $product->get_status() ? 'yes' : 'no',
                    'is_deleted'        => 'no',
                ),
            );
        }
    }



    /**
     * add upsell item to post meta 
     * 
     * @param String $id
     * @return Array
     * @since 2.4.6
     */
    public function get_upsell_items( $products, $step_id ){

        if( $products && count($products)) {
            $product_obj    = $products[0];
            $product        = wc_get_product($product_obj['id']);

            if( $product instanceof \WC_Product ) {
                $title         = $product->get_type() == 'variation' ? Wpfnl_functions::get_formated_product_name( $product ) : $product->get_name();
                $image         = wp_get_attachment_image_src( $product->get_image_id(), 'single-post-thumbnail' );
                $price         = $product->get_price();
                $regular_price = $product->get_type() == 'variable' ? $price : $product->get_regular_price();
                $signUpFee = 0;
                if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
                    if( 'subscription_variation' === $product->get_type() || 'subscription' === $product->get_type() ) {
                        $signUpFee     = \WC_Subscriptions_Product::get_sign_up_fee( $product );
                        $price         = $price + $signUpFee;
                        $regular_price = $regular_price + $signUpFee;
                    }
                }
                $sale_price = $product->get_type() == 'variable' ? $price : $product->get_sale_price();
               
                if ($sale_price != "") {
                    $sale_price = $product->get_sale_price() + floatval($signUpFee);
                }

                $response[ 'products' ][] = array(
                    'id'                => $product_obj[ 'id' ],
                    'title'             => $title,
                    'price'             => wc_price( $price ),
                    'numeric_price'     => $price,
                    'currency'          => get_woocommerce_currency_symbol(),
                    'quantity'          => $product_obj[ 'quantity' ],
                    'image'             => $image ? $image[ 0 ] : '',
                    'regular_price'     => $regular_price,
                    'sale_price'        => $sale_price ? $sale_price : $price,
                    'html_price'        => $product->get_price_html(),
                    'product_edit_link' => in_array( $product->get_type(), array( 'variation', 'subscription_variation' ) ) ? get_edit_post_link( $product->get_parent_id() ) : get_edit_post_link( $product->get_id() ),
                    'product_view_link' => in_array( $product->get_type(), array( 'variation', 'subscription_variation' ) ) ? get_permalink( $product->get_parent_id() ) : get_permalink( $product->get_id() ),
                    'is_trash'          => 'trash' === $product->get_status() ? 'yes' : 'no',
                    'is_deleted'        => 'no',
                );
                $discount   = get_post_meta( $step_id, '_wpfnl_upsell_discount', true );

                /** if offer product has discount */
                if( $discount ) {
                    $discount_type     = $discount[ 'discountType' ];
                    $discount_apply_to = $discount[ 'discountApplyTo' ];
                    $discount_value    = $discount[ 'discountValue' ];

                    if( 'discount-percentage' === $discount[ 'discountType' ] || 'discount-price' === $discount[ 'discountType' ] ) {
                        $regular_price = $product->get_regular_price();
                        if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
                            $signUpFee     = \WC_Subscriptions_Product::get_sign_up_fee( $product );
                            $regular_price = $signUpFee + $regular_price;
                        }
                        $product_price                   = $discount_apply_to === 'sale' ? ( $product->get_sale_price() ? $product->get_sale_price() : $product->get_price() ) : $regular_price;
                        $product_price                   = $product_price ? $product_price : $product->get_price();
                        $product_price                   = Wpfnl_functions::calculate_discount_price( $discount_type, $discount_value, $product_price * $product_obj[ 'quantity' ] );
                       
                        $product_price                   = preg_replace( '/[^\d.]/', '', $product_price );
                        $discount[ 'discountPrice' ]     = $product_price;
                        $discount[ 'discountPriceHtml' ] = wc_format_sale_price( floatval($product_price) * intval($product_obj[ 'quantity' ]), ( floatval($product->get_sale_price()) ? floatval($product->get_sale_price()) : floatval($product->get_price()) ) * intval($product_obj[ 'quantity' ] ));
                    }
                    else {
                        $discount[ 'discountPrice' ]     = $price * $product_obj[ 'quantity' ];
                        $discount[ 'discountPriceHtml' ] = wc_format_sale_price( floatval($price) * floatval($product_obj[ 'quantity' ]), ( $product->get_sale_price() ? floatval($product->get_sale_price()) : floatval($product->get_price()) ) * floatval($product_obj[ 'quantity' ]) );
                    }
                }

                $response[ 'discount' ] = $discount ? $discount : array(
                    'discountType'      => 'original',
                    'discountApplyTo'   => 'regular',
                    'discountValue'     => '0',
                    'discountPrice'     => $price * $product_obj[ 'quantity' ],
                    'discountPriceHtml' => wc_format_sale_price( floatval($price) * floatval($product_obj[ 'quantity' ]), ( $product->get_sale_price() ? $product->get_sale_price() : floatval($product->get_price()) ) * floatval($product_obj[ 'quantity' ]) ),
                );
            }
            else {
                $response[ 'products' ][] = array(
                    'is_trash'          => 'no',
                    'is_deleted'        => 'yes',
                );
                $response[ 'discount' ] = array(
                    'discountType'      => 'original',
                    'discountApplyTo'   => 'regular',
                    'discountValue'     => '0',
                    'discountPrice'     => '0',
                    'discountPriceHtml' => '0',
                );
            }
        }
        else {
            $response[ 'products' ] = array();
            $response[ 'discount' ] = array(
                'discountType'      => 'original',
                'discountApplyTo'   => 'regular',
                'discountValue'     => '0',
                'discountPrice'     => '0',
                'discountPriceHtml' => '0',
            );
        }

        $replaceSettings = get_post_meta( $step_id, '_wpfnl_upsell_replacement_settings', true );
        if( $replaceSettings == 'true' ) {
            $response[ 'replaceSettings' ] = $replaceSettings;
            $isOfferReplace                = get_post_meta( $step_id, '_wpfnl_upsell_replacement', true );
            $response[ 'isOfferReplace' ]  = array(
                'replacement_type' => $isOfferReplace[ 'replacement_type' ],
                'value'            => $isOfferReplace[ 'value' ],
            );
        }
        else {
            $response[ 'replaceSettings' ] = $replaceSettings;
            $response[ 'isOfferReplace' ]  = array(
                'replacement_type' => '',
                'value'            => '',
            );
        }
        $offer_settings             = Wpfnl_functions::get_offer_settings();
        $funnel_id                  = Wpfnl_functions::get_funnel_id_from_step( $step_id );
        $prev_step                  = Wpfnl_functions::get_prev_step( $funnel_id, $step_id );
        $response[ 'prevStep' ]     = isset( $prev_step[ 'step_type' ] ) && $prev_step[ 'step_type' ] ? $prev_step[ 'step_type' ] : '';
        $response[ 'success' ]      = true;
        $response[ 'isChildOrder' ] = $offer_settings[ 'offer_orders' ] == 'child-order' ? true : false;
        $response['columns']        = Wpfnl_functions::get_checkout_columns( $step_id );
        $time_bound_discount_settings 		=  get_post_meta($step_id, '_wpfnl_time_bound_discount_settings', true);
        if( !$time_bound_discount_settings ){
            $dateTime = new \DateTime();
            $time_bound_discount_settings = [
                'isEnabled' => 'no',
                'fromDate' => $dateTime->format('M d, Y'),
                'toDate' => $dateTime->add(new \DateInterval('P1D'))->format('M d, Y')
            ];
        }
        $response['time_bound_discount_settings'] = $time_bound_discount_settings;
        return  $response;
    }


    /**
     * Add upsell product
     * 
     * @param String $id
     * @return Array
     * 
     */
    public function add_downsell_items( $id, $data, $step_id ){
        $product = wc_get_product($id);
        if ( !empty($product) ) {
            $title      = $product->get_type() == 'variation' ? Wpfnl_functions::get_formated_product_name( $product ) : $product->get_name();
            $price      = $product->get_price();
            $signUpFee=0;
            if( 'subscription_variation' === $product->get_type() || 'subscription' === $product->get_type() ){
                $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee( $product );
                $price = $price + $signUpFee;
            }
            $pr_image   = wp_get_attachment_image_src($product->get_image_id(), 'single-post-thumbnail');
            $prefix = '_wpfnl_downsell_';
            update_post_meta( $step_id, $prefix.'products', $data );
            $sale_price     = $product->get_sale_price();
            if ($sale_price != "") {
                $sale_price = $product->get_sale_price() + floatval($signUpFee);
            }
            return array(
                'success' => true,
                'step_id' => $step_id,
                'products' => array(
                    'id'                => $id,
                    'title'             => $title,
                    'price'             => wc_price($price),
                    'numeric_price'     => $price,
                    'currency' 			=> get_woocommerce_currency_symbol(),
                    'quantity'          => 1,
                    'image'             => $pr_image ? $pr_image[0] : '',
                    'sale_price'        => $sale_price ? $sale_price : $price,
                    'html_price'        => $product->get_price_html(),
                    'regular_price'     => $product->get_regular_price(),
                    'product_edit_link' => in_array($product->get_type(), array( 'variation', 'subscription_variation' )) ? get_edit_post_link($product->get_parent_id()) : get_edit_post_link($product->get_id()),
                    'product_view_link' => in_array($product->get_type(), array( 'variation', 'subscription_variation' )) ? get_permalink($product->get_parent_id()) : get_permalink($product->get_id()),
                    'is_trash'          => 'trash' === $product->get_status() ? 'yes' : 'no',
                    'is_deleted'        => 'no',
                ),
            );
        }
    }
    

    /**
     * add downsell item to post meta 
     * 
     * @param String $id
     * @return Array
     * @since 2.4.6
     */
    public function get_downsell_items( $products, $step_id ){

        if( $products && count($products)) {
            $product_obj    = $products[0];
            $product        = function_exists( 'wc_get_product' ) ? wc_get_product($product_obj['id']) : [];
          
            if( $product instanceof \WC_Product ) {
                $title = $product->get_type() == 'variation' ? Wpfnl_functions::get_formated_product_name( $product ) : $product->get_name();
                $image = wp_get_attachment_image_src($product->get_image_id(), 'single-post-thumbnail');
                $price = $product->get_price();
                $regular_price  = $product->get_type() == 'variable' ? $price : $product->get_regular_price();
                $signUpFee=0;
                if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
                    if( 'subscription_variation' === $product->get_type() || 'subscription' === $product->get_type() ){
                        $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee( $product );
                        $price = $price + $signUpFee;
                        $regular_price = $regular_price + $signUpFee;
                    }
                }

                $sale_price     = $product->get_type() == 'variable' ? $price : $product->get_sale_price();
                if ($sale_price != "") {
                    $sale_price = $product->get_sale_price() + floatval($signUpFee);
                }
                $response['products'][] = array(
                    'id'            => $product_obj['id'],
                    'title'         => $title,
                    'price'         => wc_price($price),
                    'numeric_price' => $price,
                    'currency' 		=> get_woocommerce_currency_symbol(),
                    'quantity'      => $product_obj['quantity'],
                    'image'         => $image ? $image[0] : '',
                    'regular_price' => $regular_price,
                    'sale_price'    => $sale_price ? $sale_price : $price,
                    'html_price'    => $product->get_price_html(),
                    'product_edit_link' => in_array($product->get_type(), array( 'variation', 'subscription_variation' )) ? get_edit_post_link($product->get_parent_id()) : get_edit_post_link($product->get_id()),
                    'product_view_link' => in_array($product->get_type(), array( 'variation', 'subscription_variation' )) ? get_permalink($product->get_parent_id()) : get_permalink($product->get_id()),
                    'is_trash'          => 'trash' === $product->get_status() ? 'yes' : 'no',
                    'is_deleted'        => 'no',
                );
                $discount   = get_post_meta( $step_id, '_wpfnl_downsell_discount', true );
                /** if offer product has discount */
                if( $discount ) {
                    $discount_type	    = $discount['discountType'];
                    $discount_apply_to	= $discount['discountApplyTo'];
                    $discount_value	    = $discount['discountValue'];
                    if( 'discount-percentage' === $discount['discountType'] || 'discount-price' === $discount['discountType'] ) {
                        $regular_price = $product->get_regular_price();
                        if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ){
                            $signUpFee = \WC_Subscriptions_Product::get_sign_up_fee( $product );
                            $regular_price = $signUpFee + $regular_price;
                        }
                        $product_price      = $discount_apply_to === 'sale' ? ( $product->get_sale_price() ? $product->get_sale_price() : $product->get_price() ) : $regular_price;
                    
                        $product_price = $product_price ? $product_price : $product->get_price();
                        $product_price 		= Wpfnl_functions::calculate_discount_price( $discount_type , $discount_value, $product_price * $product_obj['quantity'] );
                        $product_price      = preg_replace('/[^\d.]/', '', $product_price );

                        $discount['discountPrice'] = $product_price;
                        $discount['discountPriceHtml'] = wc_format_sale_price( $product_price, ($product->get_sale_price() ? $product->get_sale_price() : $product->get_price() ) * $product_obj['quantity'] );
                    } else {
                        $discount['discountPrice']      = $price * $product_obj['quantity'];
                        $discount['discountPriceHtml']  = wc_format_sale_price( $price * $product_obj['quantity'], ($product->get_sale_price() ? $product->get_sale_price() : $product->get_price() ) * $product_obj['quantity'] );
                    }
                }

                $response['discount'] = $discount ? $discount : array(
                    'discountType'      => 'original',
                    'discountApplyTo'   => 'regular',
                    'discountValue'     => '0',
                    'discountPrice'     => $price * $product_obj['quantity'],
                    'discountPriceHtml' => wc_format_sale_price(  $price * $product_obj['quantity'], ($product->get_sale_price() ? $product->get_sale_price() : $product->get_price() ) * $product_obj['quantity'] ),
                );
            } else {
                $response[ 'products' ][] = array(
                    'is_trash'          => 'no',
                    'is_deleted'        => 'yes',
                );
                $response['discount']   = array(
                    'discountType'      => 'original',
                    'discountApplyTo'   => 'regular',
                    'discountValue'     => '0',
                    'discountPrice'     => '0',
                    'discountPriceHtml' => '0',
                );
            }
        } else {
            $response['products'] = array();
            $response['discount']   = array(
                'discountType'      => 'original',
                'discountApplyTo'   => 'regular',
                'discountValue'     => '0',
                'discountPrice'     => '0',
                'discountPriceHtml' => '0',
            );
        }
        $response['success'] = true;


        $replaceSettings   = get_post_meta( $step_id, '_wpfnl_downsell_replacement_settings', true );
        if( $replaceSettings == 'true' ){
            $response['replaceSettings'] = $replaceSettings;
            $isOfferReplace  = get_post_meta( $step_id, '_wpfnl_downsell_replacement', true );
            $response['isOfferReplace'] = array(
                'replacement_type'  => $isOfferReplace['replacement_type'],
                'value'             => $isOfferReplace['value'],
            );
        }else{
            $response['replaceSettings'] = $replaceSettings;
            $response['isOfferReplace'] = array(
                'replacement_type'  => '',
                'value'             => '',
            );
        }    
        $offer_settings  = Wpfnl_functions::get_offer_settings();
        $funnel_id = Wpfnl_functions::get_funnel_id_from_step( $step_id );
        $prev_step = Wpfnl_functions::get_prev_step( $funnel_id, $step_id );
        $response['prevStep'] = isset($prev_step['step_type']) && $prev_step['step_type'] ? $prev_step['step_type'] : '';
        $response['isChildOrder']  = $offer_settings['offer_orders'] == 'child-order' ? true : false;
        $response['columns'] = Wpfnl_functions::get_checkout_columns( $step_id );
        $time_bound_discount_settings 		=  get_post_meta($step_id, '_wpfnl_time_bound_discount_settings', true);
        if( !$time_bound_discount_settings ){
            $dateTime = new \DateTime();
            $time_bound_discount_settings = [
                'isEnabled' => 'no',
                'fromDate' => $dateTime->format('M d, Y'),
                'toDate' => $dateTime->add(new \DateInterval('P1D'))->format('M d, Y')
            ];
        }
        $response['time_bound_discount_settings'] = $time_bound_discount_settings;
        
        return $response;
    }
}