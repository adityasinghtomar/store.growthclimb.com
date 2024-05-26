<?php

namespace WPFunnelsPro\Shortcodes;


use WPFunnelsPro\OfferProduct\Wpfnl_Offer_Product;

/**
 * Class Wpfnl_Shortcode_Offer_Image
 * @package WPFunnelsPro\Shortcodes
 */
class Wpfnl_Shortcode_Offer_Image {

    /**
     * Attributes
     *
     * @var array
     */
    protected $attributes = array();


    /**
     * Wpfnl_Shortcode_Order_details constructor.
     * @param array $attributes
     */
    public function __construct( $attributes = array() ) {
        $this->attributes = $this->parse_attributes( $attributes );
    }


    /**
     * Get shortcode attributes.
     *
     * @since  3.2.0
     * @return array
     */
    public function get_attributes() {
        return $this->attributes;
    }


    /**
     * parse attributes
     *
     * @param $attributes
     * @return array
     */
    protected function parse_attributes( $attributes ) {
        $attributes = shortcode_atts(
            array(
            ),
            $attributes
        );
        return $attributes;
    }


    /**
     * retrieve offer product Image
     */
    public function get_content() {
        $offer_product = Wpfnl_Offer_Product::getInstance()->get_offer_product();
        if( !is_object($offer_product) || null === $offer_product) {
            return;
        }
        $image = wp_get_attachment_image_src(get_post_thumbnail_id($offer_product->get_id()), 'single-post-thumbnail');
        $image = '<img src="'.$image[0].'" >';
        return $image;
    }
}