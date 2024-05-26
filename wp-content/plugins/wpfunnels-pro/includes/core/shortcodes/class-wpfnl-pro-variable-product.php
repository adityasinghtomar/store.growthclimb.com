<?php

namespace WPFunnelsPro\Shortcodes;


use WPFunnelsPro\OfferProduct\Wpfnl_Offer_Product;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;
/**
 * Class Wpfnl_Shortcode_Variable_product
 * @package WPFunnelsPro\Shortcodes
 */
class Wpfnl_Shortcode_Variable_product {
    
    /**
     * Attributes
     *
     * @var array
     */
    protected $attributes = array();

    /**
     * Wpfnl_Shortcode_Variable_product constructor.
     * @param array $attributes
     */
    public function __construct( $attributes = array() ) {
        $this->attributes = $this->parse_attributes( $attributes );

        /* Load WC templates from wpfunnels plugin */
        add_filter( 'woocommerce_locate_template', array( $this, 'override_woo_templates' ), 20, 3 );

    }



    public function override_woo_templates( $template, $template_name, $template_path ) {

        // if ( Wpfnl_Pro_functions::maybe_offer_step() || Wpfnl_Pro_functions::maybe_admin_on_edit_page() ) {

            $_template = $template;

            $plugin_path = WPFNL_PRO_DIR . 'woocommerce/templates/';

            if ( file_exists( $plugin_path . $template_name ) ) {
                $template = $plugin_path . $template_name;
            }

            if ( ! $template ) {
                $template = $_template;
            }
        // }

        return $template;
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
                'post_id' => '',
            ),
            $attributes
        );
        return $attributes;
    }


    /**
     * retrieve offer product
     */
    public function get_content() {
        if( isset($this->attributes['post_id']) && $this->attributes['post_id'] ){
            $step_type = get_post_meta($this->attributes['post_id'], '_step_type', true);
            $_funnel_id = get_post_meta($this->attributes['post_id'], '_funnel_id', true);
            $type = get_post_meta($_funnel_id, '_wpfnl_funnel_type', true);
            if( 'lms' === $type ){
                return false;
            }
            $offer_product_data = Wpfnl_Pro_functions::get_offer_product_data( $this->attributes['post_id']);
            $offer_product = null;
            if( is_array($offer_product_data) && isset($offer_product_data['id']) ) {
                $product_id    = $offer_product_data['id'];
                $offer_product = wc_get_product( $product_id );
            }
            
        }else{
            $offer_product = Wpfnl_Offer_Product::getInstance()->get_offer_product();
        }

        if( (!is_object($offer_product) || null === $offer_product) ) {
            return false;
        }


        $product_id = $offer_product->get_id();
        $product = wc_get_product($product_id);

        if( $product ){
            if( $product->get_type() !== 'variable' ) {
                return false;
            }
            
            wp_enqueue_script( 'wc-add-to-cart-variation' );
    
            // Get Available variations?
            $get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
    
            // Load the template.
            wc_get_template(
                'single-product/add-to-cart/variable.php',
                array(
                    'available_variations' => $get_variations ? $product->get_available_variations() : false,
                    'attributes'           => $product->get_variation_attributes(),
                    'selected_attributes'  => $product->get_default_attributes(),
                    'product'              => $product,
                )
            );
        }
    }


    



    /**
     * render variable markup
     */
    private function render_vaiable_markup( $key, $value, $product, $product_id ){
        require WPFNL_PRO_DIR . 'includes/core/shortcodes/variable-template/variable-select-box.php';
    }

}