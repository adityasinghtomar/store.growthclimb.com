<?php
namespace WPFunnelsPro\Shortcodes;


use WPFunnels\Data_Store\Wpfnl_Steps_Store_Data;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Shortcodes\Wpfnl_Shortcode_Variable_product;



/**
 * Class Wpfnl_Shortcodes
 * @package WPFunnels\Shortcodes
 */
class Wpfnl_Pro_Shortcodes {

	use SingletonTrait;


	public static function init() {
		$shortcodes = array(
			'wpfunnels_offer_product_widget'	    => __CLASS__ . '::render_offer_product_widget',
            'wpfunnels_offer_product_title'	        => __CLASS__ . '::render_offer_product_title',
            'wpfunnels_offer_product_price'	        => __CLASS__ . '::render_offer_product_price',
            'wpfunnels_offer_product_description'	=> __CLASS__ . '::render_offer_product_description',
            'wpfunnels_offer_product_image'	        => __CLASS__ . '::render_offer_product_image',
            'wpf_offer_button'	                    => __CLASS__ . '::render_offer_button',
            'wpf_variable_offer'	                => __CLASS__ . '::render_variable_product_for_offer',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( $shortcode, $function );
		}
	}

    /**
     * Render Product Widget
     * Title ,Price , Description, Image
     * @param $attr
     * @return string|void
     */


    /**
     * render offer product title
     *
     * @param $atts
     */
    public static function render_offer_product_title( $atts ) {
        $shortcode	= new Wpfnl_Shortcode_Offer_Title( (array) $atts );
        return $shortcode->get_content();
    }


    /**
     * render offer product price
     *
     * @param $atts
     */
    public static function render_offer_product_price( $atts ) {
        $shortcode	= new Wpfnl_Shortcode_Offer_Price( (array) $atts );
        return $shortcode->get_content();
    }

    /**
     * render offer product description
     *
     * @param $atts
     */
    public static function render_offer_product_description( $atts ) {
        $shortcode	= new Wpfnl_Shortcode_Offer_Description( (array) $atts );
        return $shortcode->get_content();
    }
    /**
     * render offer product Image
     *
     * @param $atts
     */
    public static function render_offer_product_image( $atts ) {
        $shortcode	= new Wpfnl_Shortcode_Offer_Image( (array) $atts );
        return $shortcode->get_content();
    }

    public static function render_offer_product_widget($atts){
        $shortcode	= new Wpfnl_Shortcode_Offer_Widget( (array) $atts );
        return $shortcode->get_content();
    }
    
    /**
     * Render offer button
     * 
     * @param $atts
     */
    public static function render_offer_button($atts){
        $shortcode	= new Wpfnl_Shortcode_Offer_Button( (array) $atts );
        return $shortcode->get_content();
    }


    /**
     * Render variable product for offer step
     * 
     * @param $atts
     */
    public static function render_variable_product_for_offer($atts){
        $shortcode	= new Wpfnl_Shortcode_Variable_product( (array) $atts );
        return $shortcode->get_content();
    }



}
