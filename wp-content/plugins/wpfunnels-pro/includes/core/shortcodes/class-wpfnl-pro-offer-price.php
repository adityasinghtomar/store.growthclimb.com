<?php

namespace WPFunnelsPro\Shortcodes;


use WPFunnelsPro\OfferProduct\Wpfnl_Offer_Product;

/**
 * Class Wpfnl_Shortcode_Offer_Title
 * @package WPFunnelsPro\Shortcodes
 */
class Wpfnl_Shortcode_Offer_Price {

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
     * retrieve offer product title
     */
    public function get_content() {
        return Wpfnl_Offer_Product::getInstance()->get_offer_product_price();
    }
}