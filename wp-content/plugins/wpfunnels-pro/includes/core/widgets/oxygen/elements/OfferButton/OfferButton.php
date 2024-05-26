<?php

namespace WPFunnelsPro\Widgets\Oxygen;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;
use WPFunnelsPro\OfferProduct\Wpfnl_Offer_Product;
/**
 * Class OrderDetails
 */
class OfferButton extends Elements {

    function init() {
        // Do some initial things here.
    }

    function afterInit() {
        // Do things after init, like remove apply params button and remove the add button.
        $this->removeApplyParamsButton();
        // $this->removeAddButton();
    }

    function name() {
        return 'Offer Button';
    }

    function slug() {
        return "offer-button";
    }

    function icon() {
        return	plugin_dir_url(__FILE__) . 'icon/offer_button.svg';

    }

//    function button_place() {
//        // return "interactive";
//    }

    function button_priority() {
        // return 9;
    }


    function render($options, $defaults, $content) {
        $step_id  = get_the_ID();
        $step_type = get_post_meta($step_id, '_step_type', true);
        if ($step_type != 'upsell' && $step_type != 'downsell'){
            echo __('Sorry, Please place the element in WPFunnels Offer page');
        }else{
            $button_type           = $options['button_type'];
            $button_action         = $options['button_action'];
            $variation_tbl_title   = $options['variation_tbl_title'];
            $show_product_price    = $options['show_product_price'];
            $button_id =  'wpfunnels_'.$button_type.'_'.$button_action;

            if( $this->is_builder_mode() ) {
                $id = '';
            } else {
                $id = 'wpfunnels_next_step_controller';
            }

            $response = Wpfnl_Pro_functions::get_product_data_for_widget( get_the_ID() );
            $offer_product       = isset($response['offer_product']) && $response['offer_product'] ? $response['offer_product'] : '';
            $get_product_type    = isset($response['get_product_type']) && $response['get_product_type'] ? $response['get_product_type'] : '';
            $is_gbf              = isset($response['is_gbf']) && $response['is_gbf'] ? $response['is_gbf'] : '';
            $builder = 'oxygen';
            
            if( 'yes' == $is_gbf && 'yes' == $options['show_product_data'] && 'accept' == $button_action && $offer_product ){ 
                require WPFNL_PRO_DIR . 'public/modules/dynamic-offer-templates/styles/offer-' . $options['dynamic_data_template_layout'] . '.php';
            }else{
                require WPFNL_PRO_DIR . 'public/modules/dynamic-offer-templates/oxygen/offer-button.php';
            }
        }

    }

    function controls() {
        $offer_button_option = $this->addControlSection("offer_button_option", __("Button Options"), "assets/icon.png", $this);

        $offer_button_option->addOptionControl(
            array(
                "type" => "textfield",
                "name" => __("Button Text"),
                "slug" => "title_text",
                "default" => "Accept Offer"
            )
        )->rebuildElementOnChange();


        $offer_button_option->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Offer Action"),
                "slug" => 'button_action',
                "default" => "accept"
            )
        )->setValue(array(
            'accept'       => __('Accept Offer' ),
            'reject'       => __('Reject Offer' ),
        ))->rebuildElementOnChange();

        $offer_button_option->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Offer Type"),
                "slug" => 'button_type',
                "default" => "upsell"
            )
        )->setValue(array(
            'upsell' => __('Upsell'),
            'downsell' => __('Downsell'),
        ))->rebuildElementOnChange();


        $offer_button_option->addOptionControl(
            array(
                "type" => "textfield",
                "name" => __("Variation Table Title"),
                "slug" => "variation_tbl_title",
                "default" => ""
            )
        )->rebuildElementOnChange();

        $offer_button_option->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Show Product Price"),
                "slug" => 'show_product_price',
                "default" => "no"
            )
        )->setValue(array(
            'yes'       => __('Show' ),
            'no'       => __('Hide' ),
        ))->rebuildElementOnChange();

        $offer_button_option->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Product Price Alignment"),
                "slug" => 'product_price_alignment',
                "default" => ""
            )
        )->setValue(array(
            ''              => __('On The Left Of Button', 'wpfnl-pro'),
            'price-right'   => __('On The Right Of Button', 'wpfnl-pro'),
            'price-top'     => __('Above The Button', 'wpfnl-pro'),
            'price-bottom'  => __('Below The Button', 'wpfnl-pro'),
        ))->rebuildElementOnChange();

        $offer_button_option->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Show Product Data"),
                "slug" => 'show_product_data',
                "default" => "no"
            )
        )->setValue(array(
            'yes'       => __('Show' ),
            'no'       => __('Hide' ),
        ))->rebuildElementOnChange();


        $icon_selector = '.wpfnl-oxy-offer-btn';
        $offer_button_style = $this->addControlSection("offer_button_style", __(" Button Style"), "assets/icon.png", $this);

        $offer_button_style->addPreset(
            "padding",
            "menu_item_padding",
            __("Button Padding"),
            $icon_selector
        )->whiteList();

        $offer_button_style->addPreset(
            "margin",
            "menu_item_margin",
            __("Button Margin"),
            $icon_selector
        )->whiteList();

        $offer_button_style->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Button Alignment"),
                "slug" => 'button_alignment',
                "default" => "left"
            )
        )->setValue(array(
            'left'       => __('Left' ),
            'center'     => __('Center' ),
            'right'      => __('Right' ),
        ))->rebuildElementOnChange();

        $offer_button_style->addStyleControls(
            array(
                array(
                    "name" => __('Background Color'),
                    "selector" => $icon_selector."",
                    "property" => 'background-color',
                ),
                array(
                    "name" => __('Background Hover Color'),
                    "selector" => $icon_selector.":hover",
                    "property" => 'background-color',
                ),
                array(
                    "name" => __('Text Hover Color'),
                    "selector" => $icon_selector.":hover",
                    "property" => 'color',
                ),

            )
        );
        $offer_button_style->borderSection(
            __("Button Border"),
            $icon_selector."",
            $this
        );
        $offer_button_style->typographySection(
            __("Typography"),
            ".wpfnl-oxy-offer-btn",
            $this
        );

        
        /*---------dynamic offer template content option------- */
        $offer_template_option = $this->addControlSection("offer_template_option", __("Offer Template Option"), "assets/icon.png", $this);
        $offer_template_option->addOptionControl(
            array(
                "type" => 'dropdown',
                "name" => __("Select Template Style"),
                "slug" => 'dynamic_data_template_layout',
                "default" => "style1",
            )
        )->setValue(array(
            'style1'       => __('Left Image Right Content', 'wpfnl-pro' ),
            'style2'       => __('Left Content Right Image', 'wpfnl-pro' ),
            'style3'       => __('Top Image Bottom Content', 'wpfnl-pro' ),
        ))->rebuildElementOnChange();

        /*---------end dynamic offer template content option------- */

        /*---------dynamic offer template layout style option------- */
        $template_layout_style = $this->addControlSection("template_layout_style", __("Template Layout Style"), "assets/icon.png", $this);

        $template_layout_style->addStyleControls(
			array(
				array(
					"name" => __('Image Column Width', 'wpfnl-pro'),
					"selector" => '.dynamic-offer-template-default .template-left',
					"property" => 'width',
				),
                array(
					"name" => __('Content Column Width', 'wpfnl-pro'),
					"selector" => '.dynamic-offer-template-default .template-right',
					"property" => 'width',
				),

			)
		);
        
        $template_layout_style->addPreset(
            "padding",
            "template_col_gutter_width",
            __("Column Gutter Width",'wpfnl-pro'),
            '.dynamic-offer-template-default .template-right'
        )->whiteList();

        $template_layout_style->borderSection(
            __('Border', 'wpfnl-pro'),
            ".dynamic-offer-template-default",
            $this
        );
       
        $template_layout_style->addPreset(
            "padding",
            "template_layout_padding",
            __("Padding",'wpfnl-pro'),
            '.dynamic-offer-template-default'
        )->whiteList();

        $template_layout_style->addPreset(
            "margin",
            "template_layout_margin",
            __("Margin",'wpfnl-pro'),
            '.dynamic-offer-template-default'
        )->whiteList();
        /*---------end dynamic offer template layout style option------- */

        /*---------dynamic offer template Image style option------- */
        $template_image_style = $this->addControlSection("template_image_style", __("Template Image Style"), "assets/icon.png", $this);
        $template_image_style->addStyleControls(
			array(
				array(
					"name" => __('Image Width', 'wpfnl-pro'),
					"selector" => '.dynamic-offer-template-default .product-img img',
					"property" => 'width',
				),

			)
		);
        $template_image_style->borderSection(
            __('Border', 'wpfnl-pro'),
            ".dynamic-offer-template-default .product-img img",
            $this
        );
        /*---------end dynamic offer template Image style option------- */

        /*---------dynamic offer template Heading style option------- */
        $template_heading_style = $this->addControlSection("template_heading_style", __("Template Heading Style"), "assets/icon.png", $this);
        $template_heading_style->typographySection(
            __("Typography"),
            ".dynamic-offer-template-default .template-content .template-product-title",
            $this
        );
        $template_heading_style->addPreset(
            "margin",
            "template_heading_margin",
            __("Margin",'wpfnl-pro'),
            '.dynamic-offer-template-default .template-content .template-product-title'
        )->whiteList();
        /*---------end dynamic offer template Image style option------- */

        /*---------dynamic offer template Description style option------- */
        $template_description_style = $this->addControlSection("template_description_style", __("Template Description Style"), "assets/icon.png", $this);
        $template_description_style->typographySection(
            __("Typography"),
            ".dynamic-offer-template-default .template-content .template-product-description",
            $this
        );
        $template_description_style->addPreset(
            "margin",
            "template_description_margin",
            __("Margin",'wpfnl-pro'),
            '.dynamic-offer-template-default .template-content .template-product-description'
        )->whiteList();
        /*---------end dynamic offer template Description style option------- */

        /*---------dynamic offer template Price style option------- */
        $template_price_style = $this->addControlSection("template_price_style", __("Template Price Style"), "assets/icon.png", $this);
        $template_price_style->typographySection(
            __("Discount Price Typography"),
            ".dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price bdi",
            $this
        );
        $template_price_style->typographySection(
            __("Regular Price Typography"),
            ".dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price del bdi, .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price del",
            $this
        );
        $template_price_style->addPreset(
            "margin",
            "template_price_margin",
            __("Margin",'wpfnl-pro'),
            '.dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price'
        )->whiteList();
        /*---------end dynamic offer template Price style option------- */

    }


    function defaultCSS() {

    }

}