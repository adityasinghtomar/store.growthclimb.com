<?php

namespace WPFunnelsPro\OfferProduct;

use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;

/**
 * Class Wpfnl_Offer_Product
 * @package WPFunnelsPro\OfferProduct
 */
class Wpfnl_Offer_Product {

    use SingletonTrait;


    /**
     * get step id
     *
     * @return int|mixed
     */
    private function get_step_id() {
        global $post;
        $step_id = 0;

        if( $post ) {
            $step_id = $post->ID;
        } elseif (is_admin() && isset( $_POST['id'] )) {
            $step_id = $_POST['id'];
        }
        return $step_id;
    }

    /**
     * get offer product object
     *
     * @return mixed|void
     */
    public function get_offer_product() {

        $step_id            = $this->get_step_id();
        $step_type          = get_post_meta( $step_id, '_step_type', true );
        $offer_product_data = Wpfnl_Pro_functions::get_offer_product( $step_id, $step_type );
        $product            = null;

        if( is_array($offer_product_data) ) {
            foreach ( $offer_product_data as $pr_index => $pr_data ) {
                $product_id = $pr_data['id'];
                $product    = wc_get_product( $product_id );
                break;
            }
        }

        return apply_filters( 'wpfunnels/offer_product', $product, $step_id, $step_type );
    }


    /**
     * @return string|void
     */
    public function get_offer_product_price() {
        $step_id            = $this->get_step_id();
        $offer_product      = $this->get_offer_product();
        $offer_product_data = Wpfnl_Pro_functions::get_offer_product_data( $step_id );
        $output             = '';

        if( !is_object($offer_product) || null === $offer_product) {
            return;
        }

        $price_args = array(
            'decimals' => wc_get_price_decimals(),
        );
        $output .= '<span class="wpfnl-offer-product-price">';

        if( $offer_product_data['discount'] ) {
            $discount_apply_to = $offer_product_data['discount_apply_to'];
            if( 'sale' === $discount_apply_to ) {
                $regular_price = $offer_product_data['sale_price'];
            } else {
                $regular_price = $offer_product_data['regular_price'];
            }

            $output .= '<span class="wpfnl-offer-regular-price del">'.wc_price( $regular_price, $price_args ).'</span>';
            $output .= '<span class="wpfnl-offer-discount-price">'.wc_price( $offer_product_data['price'], $price_args ).'</span>';
        } else {
            $output .= '<span class="wpfnl-offer-regular-price">' . wc_price( $offer_product_data['price'], $price_args ) . '</span>';
        }
        $output .= '</span>';

        return $output;
    }
}