<?php
/**
 * Namespace for the OfferButton class.
 * This class is part of the WPFunnels\Widgets\Bricks namespace.
 */
namespace WPFunnelsPro\Widgets\Bricks;

require_once get_template_directory() . '/includes/elements/base.php';

use \Bricks\Element;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Class OfferButton
 * 
 * Represents a OfferButton element in the WP Funnels plugin.
 * This class extends the Element class.
 * 
 * @package WPFunnels\Widgets\Bricks
 */
class OfferButton extends Element {

    // Element properties
    public $category     = 'wpfunnels'; // Use predefined element category 'general'
    public $name         = 'wpfnl_offer_button'; // Make sure to prefix your elements
    public $icon         = 'fa-solid fa-cart-shopping'; // Themify icon font class
    public $scripts      = []; // Script(s) run when element is rendered on frontend or updated in builder
    public $tag         = 'button';


     /**
     * Return localised element label
     * 
     * @return string
     * @since 2.1.0
     */
    public function get_label()
    {
        return esc_html__('Offer Button', 'wpfnl-pro');
    }


    /**
     * Set builder controls
     * 
     * @since 2.1.0
     */
    public function set_controls() {
		$this->controls['offer_button_type'] = [
			'tab' => 'content',
			'label' => esc_html__( 'Button Type', 'wpfnl-pro' ),
			'type' => 'select',
			'options' => [
			  'upsell' => esc_html__( 'Upsell', 'wpfnl-pro' ),
			  'downsell' => esc_html__( 'Downsell', 'wpfnl-pro' ),
			],
			'inline' => true,
			'default' => 'upsell',
		];
		$this->controls['offer_type'] = [
			'tab' => 'content',
			'label' => esc_html__( 'Button action', 'wpfnl-pro' ),
			'type' => 'select',
			'options' => [
			  'accept' => esc_html__( 'Accept', 'wpfnl-pro' ),
			  'reject' => esc_html__( 'Reject', 'wpfnl-pro' ),
			],
			'inline' => true,
		];

        //separator
        $this->controls['buttonTypeSeparator'] = [
			'type'  => 'separator',
		];

        //---button content---
		$this->controls['text'] = [
			'label'       => esc_html__( 'Button Title', 'wpfnl-pro' ),
			'type'        => 'text',
			'default'     => esc_html__( 'Accept Offer', 'wpfnl-pro' ),
			'placeholder' => esc_html__( 'Accept Offer', 'wpfnl-pro' ),
		];

        // separator
		$this->controls['gbfSeparator'] = [
			'type'  => 'separator',
		];

        $get_product_type = Wpfnl_Pro_functions::get_offer_product_type( $this->post_id );
        
        if( $get_product_type == 'variable' ){
            $this->controls['variation_tbl_title'] = [
                'tab' => 'content',
                'label' => esc_html__( 'Variation Table Title', 'wpfnl-pro' ),
                'type' => 'textarea',
                'spellcheck' => true,
                'inlineEditing' => true,
                'required' => [ 'offer_type', '=', 'accept' ],
            ];
        }

        $this->controls['show_product_price'] = [
			'label' => esc_html__( 'Show Product Price', 'wpfnl-pro' ),
			'type'  => 'checkbox',
			'reset' => true,
            'required' => [ 'offer_type', '=', 'accept' ],
		];

        $this->controls['product_price_alignment'] = [
			'tab' => 'content',
			'label' => esc_html__( 'Price Alignment', 'wpfnl-pro' ),
			'type' => 'select',
			'options' => [
			  '' => esc_html__( 'On The Left Of Button', 'wpfnl-pro' ),
			  'price-right' => esc_html__( 'On The Right Of Button', 'wpfnl-pro' ),
			  'price-top' => esc_html__( 'Above The Button', 'wpfnl-pro' ),
			  'price-bottom' => esc_html__( 'Below The Button', 'wpfnl-pro' ),
			],
			'inline' => false,
            'required' => [ 'show_product_price' ],
		];

		// separator
		$this->controls['dynamicDataSeparator'] = [
			'type'  => 'separator',
			'required' => [ 'offer_type', '=', 'accept' ],
		];

        $funnel_id  = get_post_meta( $this->post_id, '_funnel_id', true );
        $is_gbf = get_post_meta( $funnel_id, 'is_global_funnel', true );

        if( 'yes' === $is_gbf ){
            $this->controls['show_product_data'] = [
                'label' => esc_html__( 'Show Product Data', 'wpfnl-pro' ),
                'type'  => 'checkbox',
                'reset' => true,
                'required' => [ 'offer_type', '=', 'accept' ],
            ];
        }

		$this->controls['dynamic_data_template_layout'] = [
			'tab' => 'content',
			'label' => esc_html__( 'Select Template Style', 'wpfnl-pro' ),
			'type' => 'select',
			'options' => [
			  'style1' => esc_html__( 'Left Image Right Content', 'wpfnl-pro' ),
			  'style2' => esc_html__( 'Left Content Right Image', 'wpfnl-pro' ),
			  'style3' => esc_html__( 'Top Image Bottom Content', 'wpfnl-pro' ),
			],
			'default' => 'style1',
			'inline' => false,
            'required' => [ 'show_product_data' ],
		];


		//---------offer button style controls---------
        $this->register_offer_button_style();

		//---------daynamic data template style controls---------
        $this->register_dynamic_data_template_style();

	}


	/**
     * Set builder control groups
     * 
     * @since 3.1.0
      * @access public
     */
    public function set_control_groups(){
		$this->control_groups['offer_button_style'] = [
            'title' => esc_html__('Offer Button Style', 'wpfnl-pro'), // Localized control group title
            'tab' => 'style', // Set to either "content" or "style"
        ];

		$this->control_groups['dynamic_data_template_style'] = [
            'title' => esc_html__('Dynamic Template Style', 'wpfnl-pro'), // Localized control group title
            'tab' => 'style', // Set to either "content" or "style"
			'required' => [ 'show_product_data' ],
        ];

	}


	/**
     * Register Offer Button Style Control.
     *
     * @since 3.1.0
     * @access public
     */
    protected function register_offer_button_style(){
		$this->controls['offer_button_Typography'] = [
			'group' => 'offer_button_style',
			'label' => esc_html__( 'Typography', 'wpfnl-pro' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-button',
				]
			],
		];
		$this->controls['offer_button_bg'] = [
			'group' => 'offer_button_style',
			'label' => esc_html__( 'Background', 'wpfnl-pro' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-button',
				]
			],
		];
		$this->controls['offer_button_border'] = [
			'group' => 'offer_button_style',
			'label' => esc_html__( 'Border', 'wpfnl-pro' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-button',
				],
			],
		];
		$this->controls['offer_button_margin'] = [
			'group' => 'offer_button_style',
			'label' => esc_html__( 'Margin', 'wpfnl-pro' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.bricks-button',
				],
			],
		];
		$this->controls['offer_button_padding'] = [
			'group' => 'offer_button_style',
			'label' => esc_html__( 'Padding', 'wpfnl-pro' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.bricks-button',
				],
			],
		];

        // separator
		$this->controls['iconSeparator'] = [
			'group' => 'offer_button_style',
            'type'  => 'separator',
		];

        // Icon
		$this->controls['icon'] = [
			'group' => 'offer_button_style',
			'label' => esc_html__( 'Icon', 'wpfnl-pro' ),
			'type'  => 'icon',
		];
		$this->controls['iconTypography'] = [
			'group' => 'offer_button_style',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => 'i',
				],
			],
			'exclude'  => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'text-decoration',
				'text-transform',
				'line-height',
				'letter-spacing',
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];
		$this->controls['iconPosition'] = [
			'group' => 'offer_button_style',
			'label'       => esc_html__( 'Position', 'wpfnl-pro' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'wpfnl-pro' ),
			'required'    => [ 'icon', '!=', '' ],
		];
		$this->controls['iconGap'] = [
			'group' => 'offer_button_style',
			'label'    => esc_html__( 'Gap', 'wpfnl-pro' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'gap',
					'selector' => '.bricks-button',
				],
			],
			'required' => [ 'icon', '!=', '' ],
		];
        $this->controls['iconSpace'] = [
			'group' => 'offer_button_style',
			'label'    => esc_html__( 'Space between', 'wpfnl' ),
			'type'     => 'checkbox',
			'css'      => [
				[
					'property' => 'justify-content',
					'value'    => 'space-between',
					'selector' => '.bricks-button',
				],
			],
			'required' => [ 'icon', '!=', '' ],
		];
	}
	

	/**
     * Register Dynamic Data Template Style Control.
     *
     * @since 3.1.0
     * @access public
     */
    public function register_dynamic_data_template_style(){
		// separator
		$this->controls['layout_separator'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Layout Style', 'wpfnl-pro' ),
			'type'  => 'separator',
		];

		$this->controls['template_layout_bg'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Background', 'wpfnl-pro' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.dynamic-offer-template-default',
				]
			],
		];

		$this->controls['template_left_col_width'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Image Column Width', 'wpfnl-pro' ),
			'type'  => 'number',
			'min' => 0,
			'inline' => true,
			'css' => [
				[
					'property' => 'width',
					'selector' => '.dynamic-offer-template-default .template-left',
				],
			],
		];
		$this->controls['template_right_col_width'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Content Column Width', 'wpfnl-pro' ),
			'type'  => 'number',
			'min' => 0,
			'inline' => true,
			'css' => [
				[
					'property' => 'width',
					'selector' => '.dynamic-offer-template-default .template-right',
				],
			],
		];
		$this->controls['template_col_gutter_width'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Column Gap', 'wpfnl-pro' ),
			'type'  => 'number',
			'min' => 0,
			'inline' => true,
			'css' => [
				[
					'property' => 'gap',
					'selector' => '.dynamic-offer-template-default',
				]
			],
		];

		$this->controls['template_layout_border'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Border', 'wpfnl-pro' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.dynamic-offer-template-default',
				],
			],
		];

		$this->controls['template_layout_shadow'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Box Shadow', 'wpfnl-pro' ),
			'type' => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.dynamic-offer-template-default',
				]
				
			],
			'inline' => true,
			'small' => true,
			'default' => [
			  'values' => [
				'offsetX' => 0,
				'offsetY' => 0,
				'blur' => 2,
				'spread' => 0,
			  ],
			  'color' => [
				'rgb' => 'rgba(0, 0, 0, .1)',
			  ],
			],
		];

		$this->controls['template_layout_padding'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Padding', 'wpfnl-pro' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.dynamic-offer-template-default',
				]
			],
		];

		$this->controls['template_layout_margin'] = [
			'group' => 'dynamic_data_template_style',
			'label'       => esc_html__( 'Margin', 'wpfnl-pro' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.dynamic-offer-template-default',
				],
			],
		];

		// separator
		$this->controls['template_image_separator'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Template Image Style', 'wpfnl-pro' ),
			'type'  => 'separator',
		];
		$this->controls['template_image_radius'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Border Radius', 'wpfnl-pro' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.dynamic-offer-template-default .product-img img',
				],
			],
			'exclude'  => [
				'width',
				'style',
				'color',
			],
		];

		// separator
		$this->controls['template_heading_separator'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Heading Style', 'wpfnl-pro' ),
			'type'  => 'separator',
		];
		$this->controls['template_heading_typography'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Typography', 'wpfnl-pro' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.dynamic-offer-template-default .template-content .template-product-title',
				]
			],
		];
		$this->controls['template_heading_margin'] = [
			'group' => 'dynamic_data_template_style',
			'label'       => esc_html__( 'Margin', 'wpfnl-pro' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.dynamic-offer-template-default .template-content .template-product-title',
				],
			],
		];

		// separator
		$this->controls['template_description_style'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Description Style', 'wpfnl-pro' ),
			'type'  => 'separator',
		];
		$this->controls['template_description_typography'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Typography', 'wpfnl-pro' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.dynamic-offer-template-default .template-content .template-product-description',
				]
			],
		];
		$this->controls['template_description_margin'] = [
			'group' => 'dynamic_data_template_style',
			'label'       => esc_html__( 'Margin', 'wpfnl-pro' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.dynamic-offer-template-default .template-content .template-product-description',
				],
			],
		];

		// separator
		$this->controls['template_price_style'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Price Style', 'wpfnl-pro' ),
			'type'  => 'separator',
		];
		$this->controls['template_discount_price_typography'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Discount Price Typography', 'wpfnl-pro' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price bdi',
				]
			],
		];
		$this->controls['template_price_typography'] = [
			'group' => 'dynamic_data_template_style',
			'label' => esc_html__( 'Regular Price Typography', 'wpfnl-pro' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price del bdi',
				],
				[
					'property' => 'font',
					'selector' => '.dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price',
				]
			],
		];
		$this->controls['template_price_margin'] = [
			'group' => 'dynamic_data_template_style',
			'label'       => esc_html__( 'Margin', 'wpfnl-pro' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price',
				],
			],
		];
		

	}

    /**
     * Render the widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 2.1.0
     *
     * @access public
     */
    public function render() {
		$settings = $this->settings;

		// offer button
		$offer_button_classes[] = 'bricks-button wpfunnels_offer_button';


		$this->set_attribute( 'offer-button', 'class', $offer_button_classes );

        $response = Wpfnl_Pro_functions::get_product_data_for_widget( $this->post_id );
        $offer_product       = isset($response['offer_product']) && $response['offer_product'] ? $response['offer_product'] : '';
        $get_product_type    = isset($response['get_product_type']) && $response['get_product_type'] ? $response['get_product_type'] : '';
        $is_gbf              = isset($response['is_gbf']) && $response['is_gbf'] ? $response['is_gbf'] : '';
        $builder = 'bricks';
        
		echo '<div ' . $this->render_attributes( '_root' ) . '>';
			if( 'yes' == $is_gbf && isset($settings['show_product_data']) && $settings['show_product_data'] && $settings['offer_type'] == 'accept' ){ 
				require WPFNL_PRO_DIR . 'public/modules/dynamic-offer-templates/styles/offer-' . $settings['dynamic_data_template_layout'] . '.php';
			}else{
				require WPFNL_PRO_DIR . 'public/modules/dynamic-offer-templates/bricks/offer-button.php';
			}
		echo '</div>';
	}

}
