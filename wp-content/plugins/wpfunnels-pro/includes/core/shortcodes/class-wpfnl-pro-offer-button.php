<?php

namespace WPFunnelsPro\Shortcodes;


use WPFunnelsPro\OfferProduct\Wpfnl_Offer_Product;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;
/**
 * Class Wpfnl_Shortcode_Offer_Description
 * @package WPFunnelsPro\Shortcodes
 */
class Wpfnl_Shortcode_Offer_Button {

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
                'btn_text' 	                            => '',
				'offer_type'                            => 'upsell',
				'action'                                => 'accept',
				'class'                                 => '',
                'dynamic_data_template_layout'          => 'style1',
                'show_product_price'                    => 'no',
                'variation_tbl_title'                   => '',
                'show_product_data'                     => 'no',
                'btn_font_size'                         => '',
                'btn_margin'                            => '',
                'btn_padding'                           => '',
                'btn_background_color'                  => '',
                'btn_color'                             => '',
                'btn_radius'                            => '',
                'price_class'                           => '',
            ),
            $attributes
        );
        return $attributes;
    }


    /**
     * retrieve offer product description
     */
    public function get_content() {
        if( Wpfnl_functions::check_if_this_is_step_type('upsell') || Wpfnl_functions::check_if_this_is_step_type('downsell')) {
            
            $offer_product = Wpfnl_Offer_Product::getInstance()->get_offer_product();
            if( !is_object($offer_product) || null === $offer_product) {
                return;
            }
            ob_start();
            $data        = \WPFunnels\Wpfnl_functions::get_sanitized_get_post();
            $step_id 	= isset($data['post']['current_page']['id']) ? $data['post']['current_page']['id'] : get_the_ID();

            if( isset($step_id) && $step_id ){
                $step_type = get_post_meta($step_id, '_step_type', true);
                $offer_product_data = Wpfnl_Pro_functions::get_offer_product( $step_id, $step_type );
                $offer_product = null;

                if( is_array($offer_product_data) ) {
                    foreach ( $offer_product_data as $pr_index => $pr_data ) {
                        $product_id = $pr_data['id'];
                        $offer_product    = wc_get_product( $product_id );
                        break;
                    }
                }

            }else{
                $offer_product = Wpfnl_Offer_Product::getInstance()->get_offer_product();
            }

            $response = Wpfnl_Pro_functions::get_product_data_for_widget( $step_id );
            $offer_product       = isset($response['offer_product']) && $response['offer_product'] ? $response['offer_product'] : '';
            $get_product_type    = isset($response['get_product_type']) && $response['get_product_type'] ? $response['get_product_type'] : '';
            $is_gbf              = isset($response['is_gbf']) && $response['is_gbf'] ? $response['is_gbf'] : '';
            $builder = 'shortcode';

            if( !is_object($offer_product) || null === $offer_product) {
                return;
            }
            $button_style = '';
            if ('' != $this->attributes['btn_font_size'] || '' != $this->attributes['btn_margin'] || '' != $this->attributes['btn_padding']
                || '' != $this->attributes['btn_background_color'] || '' != $this->attributes['btn_color'] || '' != $this->attributes['btn_radius']){
                $button_style = ' style="font-size:'.$this->attributes['btn_font_size'].'; margin: '.$this->attributes['btn_margin'].'; padding: '.$this->attributes['btn_padding'].'; 
                                    background-color: '.$this->attributes['btn_background_color'].'; color: '.$this->attributes['btn_color'].'; border-radius: '.$this->attributes['btn_radius'].';"';
            }

            if( 'yes' === $is_gbf && isset($this->attributes['show_product_data']) &&  'yes' === $this->attributes['show_product_data'] && 'accept' === $this->attributes['action'] ){
                require WPFNL_PRO_DIR . 'public/modules/dynamic-offer-templates/styles/offer-'.$this->attributes['dynamic_data_template_layout'].'.php';
            }else{
                require WPFNL_PRO_DIR . 'public/modules/dynamic-offer-templates/shortcode/offer-button.php';
            }
            return ob_get_clean();
        }
        return false;
    }

    public function render_text( $text ){
        $html = '';
        $html .= '<span>';
        $html .= '<span>'.$text.'</span>';
        $html .= '</span>';
        return $html;
    }
}