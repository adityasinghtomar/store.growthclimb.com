<?php
namespace WPFunnelsPro\Widgets\Gutenberg\BlockTypes;

use WPFunnelsPro\OfferProduct\Wpfnl_Offer_Product;
use WPFunnelsPro\Wpfnl_Pro_functions;
/**
 * OrderDetails class.
 */
class OfferButton extends AbstractBlock {

    protected $defaults = array(
    );

    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'offer-button';


    public function __construct( $block_name = '' )
    {
        parent::__construct($block_name);
        add_action('wp_ajax_wpfnl_offer_variable_shortcode', [$this, 'render_offer_variable_shortcode']);
        add_action( 'wp_ajax_nopriv_wpfnl_offer_variable_shortcode', [$this, 'render_offer_variable_shortcode'] ); 
        
        add_action('wp_ajax_show_offer_layout_style_markup', [$this, 'render_offer_layout_style']);
        add_action( 'wp_ajax_nopriv_show_offer_layout_style_markup', [$this, 'render_offer_layout_style'] ); 
    }

    /**
     * Render the Featured Product block.
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Block content.
     * @return string Rendered block type output.
     */
    protected function render( $attributes, $content ) {
        $attributes = wp_parse_args( $attributes, $this->defaults );
        $dynamic_css = $this->generate_assets($attributes);

        $is_variable = Wpfnl_Pro_functions::check_is_variable_product( get_the_id() );
        $product_price_html = Wpfnl_Pro_functions::get_offer_product_price( get_the_id() );
        $response = Wpfnl_Pro_functions::get_product_data_for_widget( get_the_ID() );
        $style_layout = isset($attributes['offerTemplateLayout'])? $attributes['offerTemplateLayout'] : 'style1';
        $product_info = $this->get_dynamic_product_info_gutenberg( get_the_id());
        $funnel_id = get_post_meta( get_the_id(), '_funnel_id', true);
        $isGbf = get_post_meta( $funnel_id, 'is_global_funnel', true);
        

        ob_start();

        if( 'yes' == $isGbf && isset($attributes['showProductData']) &&  'yes' == $attributes['showProductData'] ){

            if( isset($attributes['templateImageWidth']) && !empty( $attributes['templateImageWidth'] ) ){
                $imageWidth = $attributes['templateImageWidth'].'px';

            }else{
                $imageWidth = '100%';
            }
        ?>
            
            <?php require_once WPFNL_PRO_DIR . 'public/modules/dynamic-offer-templates/gutenberg/offer-'.$style_layout.'.php'; ?>
            
            <style>
                .dynamic-offer-template-default {
                    padding-top: <?php echo isset($attributes['templateTopPadding']) ? $attributes['templateTopPadding'].'px' : '' ?>;
                    padding-right: <?php echo isset($attributes['templateRightPadding']) ? $attributes['templateRightPadding'].'px' : '' ?>;
                    padding-bottom: <?php echo isset($attributes['templateBottomPadding']) ? $attributes['templateBottomPadding'].'px' : '' ?>;
                    padding-left: <?php echo isset($attributes['templateLeftPadding']) ? $attributes['templateLeftPadding'].'px' : '' ?>;

                    margin-top: <?php echo isset($attributes['templateTopMargin']) ? $attributes['templateTopMargin'].'px' : '' ?>;
                    margin-right: <?php echo isset($attributes['templateRightMargin']) ? $attributes['templateRightMargin'].'px' : '' ?>;
                    margin-bottom: <?php echo isset($attributes['templateBottomMargin']) ? $attributes['templateBottomMargin'].'px' : '' ?>;
                    margin-left: <?php echo isset($attributes['templateLeftMargin']) ? $attributes['templateLeftMargin'].'px' : '' ?>;
                    border-radius: <?php echo isset($attributes['templateLayoutRadius']) ? $attributes['templateLayoutRadius'].'px' : '' ?>;
                }

                .dynamic-offer-template-default .template-left {
                    width: <?php echo isset($attributes['templateLeftColWidth']) ? $attributes['templateLeftColWidth'].'%' : '' ?>;
                }

                .dynamic-offer-template-default .product-img img {
                    border-radius: <?php echo isset($attributes['templateImageRadius']) ? $attributes['templateImageRadius'].'px' : '' ?>;
                    max-width: <?php echo $imageWidth; ?>;
                }

                .dynamic-offer-template-default .template-right {
                    width: <?php echo isset($attributes['templateRightColWidth']) ? $attributes['templateRightColWidth'].'%' : '' ?>;
                }
                
                /* ---gutter--- */
                <?php 
                    if( 'style2' == $attributes['offerTemplateLayout'] ){
                        ?>
                        .dynamic-offer-template-default .template-right {
                            padding-left: 0px!important;
                            padding-right: <?php echo $attributes['templateColGutterWidth'] ?>px!important;
                        }
                        <?php

                    }else if( 'style3' == $attributes['offerTemplateLayout'] ){
                        ?>
                        .dynamic-offer-template-default .template-right {
                            padding-left: 0px!important;
                            padding-right: 0px!important;
                            padding-top: <?php echo isset($attributes['templateColGutterWidth']) ? $attributes['templateColGutterWidth'].'px!important' : '' ?>;
                        }
                        <?php

                    }else{
                        ?>
                        .dynamic-offer-template-default .template-right {
                            padding-left: <?php echo isset($attributes['templateColGutterWidth']) ? $attributes['templateColGutterWidth'].'px!important' : '' ?>;
                            padding-right: 0px!important;
                        }
                        <?php
                    }
                ?>
                

                .dynamic-offer-template-default .template-content .template-product-title {
                    color: <?php echo isset($attributes['headingColor']) ? $attributes['headingColor'] : '' ?>;
                    margin-bottom: <?php echo isset($attributes['headingBottomMargin']) ? $attributes['headingBottomMargin'].'px' : ''?>;
                }

                .dynamic-offer-template-default .template-content .template-product-description {
                    color: <?php echo isset($attributes['descriptionColor']) ? $attributes['descriptionColor'] : '' ?>;
                    margin-bottom: <?php echo isset($attributes['descriptionBottomMargin']) ? $attributes['descriptionBottomMargin'].'px' : '' ?>;
                }

                .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price bdi {
                    color: <?php echo isset($attributes['regularPriceColor']) ? $attributes['regularPriceColor'] : '' ?>;
                }

                .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price del bdi, 
                .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price del {
                    color: <?php echo isset($attributes['discountPriceColor']) ? $attributes['discountPriceColor'] : '' ?>;
                }

                .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price {
                    margin-bottom: <?php echo isset($attributes['priceBottomMargin']) ? $attributes['priceBottomMargin'] : '' ?>px;
                }
            </style>

        <?php }else { ?>
            
            <div class="wp-block-wpfnl-offer-btn-<?php echo isset($attributes['buttonAlign']) ? $attributes['buttonAlign'] : ''; ?>" >
                <div class="wpfnl-offerbtn-wrapper" id="wpfnl-offerbtn-wrapper" >
                    <?php 
                        if( !isset($attributes['offerAction']) || 'reject' !== $attributes['offerAction'] ){
                            
                            if( $is_variable){
                                if( isset($attributes['variationTblTitle']) && !empty( $attributes['variationTblTitle'] ) ){ 
                                    echo '<h5 class="wpfnl-product-variation-title">'.$attributes['variationTblTitle'].'</h5>';                       
                                }
                                ?>

                                <div class="has-variation-product">
                                    <div class="wpfnl-product-variation">
                                        <?php 
                                            if( isset($attributes['showProductPrice']) && 'yes' === $attributes['showProductPrice'] ){
                                                echo '<span class="offer-btn-loader"></span>';
                                            }
                                            echo do_shortcode('[wpf_variable_offer]');
                                        ?>
                                    </div>
                                    <?php
                            }
                            
                        }
                    ?>

                    <div class="wpfnl-offerbtn-and-price-wrapper <?php echo 'yes' === $attributes['showProductPrice'] ? $attributes['productPriceAlignment'] : '' ?>">
                        <?php if( isset($attributes['showProductPrice']) && 'yes' === $attributes['showProductPrice'] ){ ?>
                            <span class="wpfnl-offer-product-price" id="wpfnl-offer-product-price">
                                <?php
                               
                                    if( !$is_variable && (!isset($attributes['offerAction']) || 'reject' !== $attributes['offerAction'] ) ){
                                        if( !empty($product_info['id']) ){
                                            $offer_product = wc_get_product($product_info['id']);
                                            if( $offer_product ){
                                                $step_type  = get_post_meta( get_the_ID(), '_step_type', true );
                                                $discount   = get_post_meta( get_the_ID(), '_wpfnl_'.$step_type.'_discount', true );
                                                $total_price = isset($response['quantity']) ? $offer_product->get_regular_price() * $response['quantity'] : $offer_product->get_regular_price();
                                                if( isset($discount['discountApplyTo'], $discount['discountType']) && 'original' !== $discount['discountType'] ){
                                                    if( 'sale' === $discount['discountApplyTo'] ){
                                                        $sale_price = $offer_product->get_sale_price() ? $offer_product->get_sale_price() : $offer_product->get_regular_price();
                                                    }elseif( 'regular' === $discount['discountApplyTo'] ){
                                                        $sale_price = $offer_product->get_regular_price() ? $offer_product->get_regular_price() : $offer_product->get_price();
                                                    }else{
                                                        $sale_price = $offer_product->get_price();
                                                    }
                                                    $product_price 		= \WPFunnelsPro\Wpfnl_Pro_functions::calculate_discount_price_for_widget( $discount['discountType'] , $discount['discountValue'], $sale_price  );
                                                    if( $product_price != $total_price ){
                                                        echo wc_price(number_format( (float) $product_price, 2, '.', '' )).'<del>'.wc_price(number_format( (float) $total_price, 2, '.', '' )).'</del>';
                                                    }else{
                                                        echo wc_price(number_format( (float) $product_price, 2, '.', '' ));
                                                    }
                                                }else{
                                                    if( $offer_product->get_sale_price() ){
                                                        $sale_price = $offer_product->get_sale_price();
                                                        $sale_price = isset($response['quantity']) ? $sale_price * $response['quantity'] : $sale_price;
                                                        echo wc_price(number_format( (float) $sale_price, 2, '.', '' )).'<del>'.wc_price(number_format( (float) $total_price, 2, '.', '' )).'</del>';
                                                    }else{
                                                        echo wc_price(number_format( (float) $total_price, 2, '.', '' ));
                                                    }    
                                                }
                                            }
                                        }
                                        
                                    }
                                ?>
                            </span>
                            <?php
                        }

                        echo $content;
                        ?>
                    </div>

                    <?php
                        if( $is_variable && (!isset($attributes['offerAction']) || 'reject' !== $attributes['offerAction'] ) ) {
                            echo '</div>';
                            //end ".has-variation-product"
                        }
                    ?>

                </div>
            </div>

        <?php } ?>

        <?php

        //return $this->inject_html_data_attributes( $new_content, $attributes );
        return ob_get_clean();
    }


    /**
     * Get the styles for the wrapper element (background image, color).
     *
     * @param array       $attributes Block attributes. Default empty array.
     * @return string
     */
    public function get_styles( $attributes ) {
        $style      = '';
        return $style;
    }


    /**
     * Get class names for the block container.
     *
     * @param array $attributes Block attributes. Default empty array.
     * @return string
     */
    public function get_classes( $attributes ) {
        $classes = array( 'wpfnl-block-' . $this->block_name );
        return implode( ' ', $classes );
    }


    /**
     * Extra data passed through from server to client for block.
     *
     * @param array $attributes  Any attributes that currently are available from the block.
     *                           Note, this will be empty in the editor context when the block is
     *                           not in the post content on editor load.
     */
    protected function enqueue_data( array $attributes = [] ) {
        parent::enqueue_data( $attributes );
    }


    /**
     * Get the frontend script handle for this block type.
     *
     * @see $this->register_block_type()
     * @param string $key Data to get, or default to everything.
     * @return array|string
     */
    protected function get_block_type_script( $key = null ) {
        $script = [
            'handle'       => 'wpfnl-offer-button-frontend',
            'path'         => $this->get_block_asset_build_path( 'offer-button-frontend' ),
            'dependencies' => [],
        ];
        return $key ? $script[ $key ] : $script;
    }

     /**
     * render offer product shortcode markup
     * based on type
     *
     */
    
    public function render_offer_variable_shortcode() {
        check_ajax_referer( 'wpfnl_gb_ajax_nonce', 'nonce' );
        $data = '';
        ob_start();
        echo do_shortcode('[wpf_variable_offer post_id="'.$_POST['id'].'" ]');
        $data = ob_get_clean();
        wp_send_json_success( $data );
    }

    /**
     * Get dynamic product info for Gutenberg.
	 * 
	 * @param String
	 * @return String
	 * 
	 * @since 2.4.14
	 * 
	*/
    public function get_dynamic_product_info_gutenberg( $step_id ){

        if( $step_id ){
            $response = Wpfnl_Pro_functions::get_product_data_for_widget( $step_id );
            $offer_product       = isset($response['offer_product']) && $response['offer_product'] ? $response['offer_product'] : '';

            if( $offer_product ){
                $product = [
                    'img' => get_the_post_thumbnail_url($offer_product->get_id()),
                    'title' => $offer_product->get_name(),
                    'description' => $offer_product->get_short_description(),
                    'price' => $offer_product->get_sale_price() ? $offer_product->get_sale_price() : $offer_product->get_regular_price(),
                    'id' => $offer_product->get_id(),
                ];

                return $product;
            }
        }
        
        return [
            'img' => '',
            'title' => 'What is Lorem Ipsum?',
            'description' => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.",
            'price' => "120",
        ];
    }


    
}