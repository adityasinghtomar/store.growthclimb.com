<?php

namespace WPFunnelsPro\Widgets\DiviModules\Modules;

use ET_Builder_Element;
use ET_Builder_Module;
use WPFunnelsPro\OfferProduct\Wpfnl_Offer_Product;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;

class WPFNL_Offer_Button extends ET_Builder_Module {

    public $slug       = 'wpfnl_offer_button';
    public $vb_support = 'on';

    // Module Credits (Appears at the bottom of the module settings modal)
    protected $module_credits = array(
        'module_uri' => '',
        'author'     => '',
        'author_uri' => '',
    );

    /**
     * Module properties initialization
     */
    function init() {
        $this->name             = __( 'WPF Offer Button', 'wpfnl-pro' );

        $this->icon_path        =  plugin_dir_path( __FILE__ ) . 'offer_button.svg';

        $this->main_css_element = '%%order_class%%';

        $this->settings_modal_toggles  = array(
            'general'  => array(
                'toggles' => array(
                    'button'       => __( 'Offer Button', 'wpfnl-pro' ),
                ),
            ),
            'advanced' => array(
                'toggles' => array(
                    'alignment' => __( 'Alignment', 'wpfnl-pro' ),
                    'text'      => array(
                        'title'    => __( 'Alignment', 'wpfnl-pro' ),
                        'priority' => 49,
                    ),
                    'variation_alignment' => array(
                        'title' => __( 'Variation Alignment', 'wpfnl-pro' ),
                        'priority' => 50,
                    ),
                    'template_layout_style' => array(
						'title'    => __( 'Layout Style', 'wpfnl-pro' ),
						'priority' => 60,
					),
                    'template_hedding_style' => array(
						'title'    => __( 'Heading Style', 'wpfnl-pro' ),
						'priority' => 65,
					),
                    'template_description_style' => array(
						'title'    => __( 'Description Style', 'wpfnl-pro' ),
						'priority' => 70,
					),
                ),
            ),
        );
        $this->wrapper_settings = array(
            // Flag that indicates that this module's wrapper where order class is declared
            // has another wrapper (mostly for button alignment purpose).
            'order_class_wrapper' => true,
        );

        $this->custom_css_fields = array(
            'main_element' => array(
                'label'                    => __( 'Main Element', 'wpfnl-pro' ),
                'no_space_before_selector' => true,
            ),
        );

        $this->advanced_fields = array(
            'text' =>array(
                'use_text_orientation'  => true, // default
                'css' => array(
                    'text_orientation' => '%%order_class%%',
                )
            ),
            'borders'        => array(
				'default' => array(
					'css'      => array(
						'main' => array(
							'border_radii' => "{$this->main_css_element} .dynamic-offer-template-default",
							'border_styles' => "{$this->main_css_element} .dynamic-offer-template-default",
						),
					),
					'defaults' => array(
						'border_radii' => 'off|0px|0px|0px|0px',
					),
				),
			),
            'button'          => array(
                'button' => array(
                    'label'          => __( 'Button', 'wpfnl-pro' ),
                    'css'            => array(
                        'limited_main' => "{$this->main_css_element} .et_pb_button",
                    ),
                    'box_shadow'     => false,
                    'text_shadow'     => false,
                    'margin_padding'  => array(
                        'css' => array(
                            'main'    => "{$this->main_css_element} .et_pb_button",
                            'important' => 'all',
                        ),
                    ),
                ),

            ),
            'box_shadow' => array(
				'default' => array(
					'css' => array(
                        'main'    => "{$this->main_css_element} .dynamic-offer-template-default",
                        'important' => 'all',
                    ),
				),
			),
            'margin_padding'  => array(
                'css' => array(
                    'main'    => "{$this->main_css_element} .dynamic-offer-template-default",
                    'important' => 'all',
                ),
            ),
            'text_shadow'     => array(
                'default' => false,
            ),
            'background'      => array(
                'css' => array(
                    'main'    => "{$this->main_css_element} .dynamic-offer-template-default",
                    'important' => 'all',
                ),
            ),
            'fonts'           => array(
                'template_hedding_style' => array(
					'label'           =>  __( ' Heading', 'wpfnl-pro' ),
					'css' => array(
                        'main' => "{$this->main_css_element} .dynamic-offer-template-default .template-content .template-product-title",
                    )
				),
                'template_description_style' => array(
					'label'           =>  __( ' Description', 'wpfnl-pro' ),
					'css' => array(
                        'main' => "{$this->main_css_element} .dynamic-offer-template-default .template-content .template-product-description",
                    )
				),
                'template_regular_price_style' => array(
					'label'           =>  __( ' Regular Price', 'wpfnl-pro' ),
					'css' => array(
                        'main' => "{$this->main_css_element} .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price del bdi, .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price del",
                    )
				),
                'template_discount_price_style' => array(
					'label'           =>  __( ' Discount Price', 'wpfnl-pro' ),
					'css' => array(
                        'main' => "{$this->main_css_element} .dynamic-offer-template-default #wpfnl-offerbtn-wrapper .wpfnl-offer-product-price bdi",
                    )
				),
            ),
            'width'           =>false,
            'height'          => false,
            'max_width'       => false,
            'link_options'    => false,
            'position_fields' => array(
                'css' => array(
                    'main' => "{$this->main_css_element}_wrapper,",
                ),
            ),
            'transform'       => array(
                'css' => array(
                    'main' => "{$this->main_css_element}_wrapper,",
                ),
            ),
        );

        $this->help_videos = array(
            array(
                'id'   => 'XpM2G7tQQIE',
                'name' => esc_html__( 'An introduction to the Button module', 'wpfnl' ),
            ),
        );
    }

    /**
     * Module's specific fields
     *
     *
     * The following modules are automatically added regardless being defined or not:
     *   Tabs     | Toggles          | Fields
     *   --------- ------------------ -------------
     *   Content  | Admin Label      | Admin Label
     *   Advanced | CSS ID & Classes | CSS ID
     *   Advanced | CSS ID & Classes | CSS Class
     *   Advanced | Custom CSS       | Before
     *   Advanced | Custom CSS       | Main Element
     *   Advanced | Custom CSS       | After
     *   Advanced | Visibility       | Disable On
     * @return array
     */
    
    function get_fields() {
        $basic_fields = array(
            'button_text' => array(
                'label'           => __( 'Button Text', 'wpfnl-pro' ),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'description'     => __( 'Input your desired button text, or leave blank for no button.', 'wpfnl-pro' ),
                'toggle_slug'     => 'button',
                'default'         => 'Accept Offer',
                'computed_affects' => array(
                    '__variationForm'
                ),
            ),
            'button_action'             => array(
                'label'            => __( 'Select Button Action', 'wpfnl-pro' ),
                'description'      => __( 'Offer Action', 'wpfnl-pro' ),
                'type'             => 'select',
                'options'          => array(
                    'accept'       => __( 'Accept Offer', 'wpfnl-pro' ),
                    'reject'       => __( 'Reject Offer', 'wpfnl-pro' ),
                ),
                'priority'         => 80,
                'default'          => 'accept',
                'default_on_front' => 'accept',
                'toggle_slug'      => 'button',
                'sub_toggle'       => 'ul',
                'mobile_options'   => true,
                'computed_affects' => array(
                    '__variationForm'
                ),
            ),
            'button_type'             => array(
                'label'            => __( 'Select Button Type', 'wpfnl-pro' ),
                'description'      => __( 'Offer Type', 'wpfnl-pro' ),
                'type'             => 'select',
                'options'          => array(
                    'upsell'       => __( 'Upsell', 'wpfnl-pro' ),
                    'downsell'       => __( 'Downsell', 'wpfnl-pro' ),
                ),
                'priority'         => 80,
                'default'          => 'upsell',
                'default_on_front' => 'upsell',
                'toggle_slug'      => 'button',
                'sub_toggle'       => 'ul',
                'mobile_options'   => true,
                'computed_affects' => array(
                    '__variationForm'
                ),
            ),
            'variation_alignment'             => array(
                'label'            => __( 'Select Variation Align', 'wpfnl-pro' ),
                'description'      => __( 'Offer Type', 'wpfnl-pro' ),
                'type'             => 'select',
                'options'          => array(
                    'left'       => __( 'Left', 'wpfnl-pro' ),
                    'center'       => __( 'Center', 'wpfnl-pro' ),
                    'right'       => __( 'Right', 'wpfnl-pro' ),
                ),
                'priority'         => 80,
                'default'          => 'left',
                'default_on_front' => 'left',
                'toggle_slug'      => 'variation_alignment',
                'sub_toggle'       => 'ul',
                'mobile_options'   => true,
                'show_if'          => array(
                    'button_action' => 'accept',
                ),
                'computed_affects' => array(
                    '__variationForm'
                ),
            ),

            'variation_tbl_title' => array(
                'label'           => __( 'Variation Table Title', 'wpfnl-pro' ),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'description'     => __( 'Input your Variation Table Title.', 'wpfnl-pro' ),
                'toggle_slug'     => 'button',
                'show_if'          => array(
                    'button_action' => 'accept',
                ),
                'computed_affects' => array(
                    '__variationForm'
                ),
            ),
            'show_product_price'   => array(
                'label'            => __( 'Show Product Price', 'wpfnl-pro' ),
                'type'             => 'select',
                'options'          => array(
                    'yes'          => __( 'Show', 'wpfnl-pro' ),
                    'no'           => __( 'Hide', 'wpfnl-pro' ),
                ),
                'priority'         => 80,
                'default'          => 'no',
                'default_on_front' => 'no',
                'toggle_slug'      => 'button',
                'sub_toggle'       => 'ul',
                'mobile_options'   => true,
                'show_if'          => array(
                    'button_action' => 'accept',
                ),
                'computed_affects' => array(
                    '__variationForm'
                ),
            ),

            'product_price_alignment'   => array(
                'label'            => __( 'Product Price Alignment', 'wpfnl-pro' ),
                'type'             => 'select',
                'options'          => array(
                    ''              => __('On The Left Of Button', 'wpfnl-pro' ),
                    'price-right'   => __('On The Right Of Button', 'wpfnl-pro'),
                    'price-top'     => __('Above The Button', 'wpfnl-pro'),
                    'price-bottom'  => __('Below The Button', 'wpfnl-pro'),
                ),
                'priority'         => 85,
                'default'          => 'no',
                'default_on_front' => 'no',
                'toggle_slug'      => 'button',
                'sub_toggle'       => 'ul',
                'mobile_options'   => true,
                'show_if'          => array(
                    'show_product_price' => 'yes',
                ),
                'computed_affects' => array(
                    '__variationForm'
                ),
            ),

            'show_product_data'   => array(
                'label'            => __( 'Show Product Data', 'wpfnl-pro' ),
                'type'             => 'select',
                'options'          => array(
                    'yes'          => __( 'Show', 'wpfnl-pro' ),
                    'no'           => __( 'Hide', 'wpfnl-pro' ),
                ),
                'priority'         => 90,
                'default'          => 'no',
                'default_on_front' => 'no',
                'toggle_slug'      => 'button',
                'sub_toggle'       => 'ul',
                'mobile_options'   => true,
                'show_if'          => array(
                    'button_action' => 'accept',
                ),
                'computed_affects' => array(
                    '__variationForm'
                ),
            ),
            'dynamic_data_template_layout'   => array(
                'label'            => __( 'Select Template Style', 'wpfnl-pro' ),
                'type'             => 'select',
                'options'          => array(
                    'style1'          => __( 'Left Image Right Content', 'wpfnl-pro' ),
                    'style2'          => __( 'Left Content Right Image', 'wpfnl-pro' ),
                    'style3'          => __( 'Top Image Bottom Content', 'wpfnl-pro' ),
                ),
                'priority'         => 95,
                'default'          => 'style1',
                'toggle_slug'      => 'button',
                'sub_toggle'       => 'ul',
                'mobile_options'   => true,
                'show_if'          => array(
                    'button_action' => 'accept',
                    'show_product_data' => 'yes',
                ),
                'computed_affects' => array(
                    '__variationForm'
                ),
            ),

            '__variationForm'        => array(
                'type'                => 'computed',
                'computed_callback'   => array(
                    'WPFunnelsPro\Widgets\DiviModules\Modules\WPFNL_Offer_Button',
                    'get_variation_form',
                ),
                'computed_depends_on' => array(
                    'button_action',
                    'button_type',
                    'button_text',
                    'variation_alignment',
                    'variation_tbl_title',
                    'show_product_price',
                    'product_price_alignment',
                    'show_product_data',
                    'dynamic_data_template_layout',
                )
            ),
        );

        return $basic_fields;
    }


    public static  function get_variation_form( $props, $render_slug ) {

        $offer_obj      = new WPFNL_Offer_Button;
        $multi_view     = et_pb_multi_view_options( $offer_obj );

        $button_alignment              = $offer_obj->get_button_alignment();
        $is_button_aligment_responsive = et_pb_responsive_options()->is_responsive_enabled( $props, 'button_alignment' );
        $button_alignment_tablet       = $is_button_aligment_responsive ? $offer_obj->get_button_alignment( 'tablet' ) : '';
        $button_alignment_phone        = $is_button_aligment_responsive ? $offer_obj->get_button_alignment( 'phone' ) : '';

        $custom_icon_values = et_pb_responsive_options()->get_property_values( $props, 'button_icon' );
        $custom_icon        = isset( $custom_icon_values['desktop'] ) ? $custom_icon_values['desktop'] : '';
        $custom_icon_tablet = isset( $custom_icon_values['tablet'] ) ? $custom_icon_values['tablet'] : '';
        $custom_icon_phone  = isset( $custom_icon_values['phone'] ) ? $custom_icon_values['phone'] : '';

        // Button Alignment.
        $button_alignments = array();
        if ( ! empty( $button_alignment ) ) {
            array_push( $button_alignments, sprintf( 'et_pb_button_alignment_%1$s', esc_attr( $button_alignment ) ) );
        }

        if ( ! empty( $button_alignment_tablet ) ) {
            array_push( $button_alignments, sprintf( 'et_pb_button_alignment_tablet_%1$s', esc_attr( $button_alignment_tablet ) ) );
        }

        if ( ! empty( $button_alignment_phone ) ) {
            array_push( $button_alignments, sprintf( 'et_pb_button_alignment_phone_%1$s', esc_attr( $button_alignment_phone ) ) );
        }

        $button_alignment_classes = join( ' ', $button_alignments );

        // Background layout data attributes.
        $data_background_layout = et_pb_background_layout_options()->get_background_layout_attrs( $props );

        // Background layout class names.
        $background_layout_class_names = et_pb_background_layout_options()->get_background_layout_class( $props );
        $offer_obj->add_classname( $background_layout_class_names );

        // Module classnames
        $offer_obj->remove_classname( 'et_pb_module' );

        $button_text           = $props['button_text'];
        $button_type           = $props['button_type'];
        $button_action         = $props['button_action'];

        // Render button
        $button = $offer_obj->render_button( array(
            'button_id'        => 'wpfunnels_'.$button_type.'_'.$button_action,
            'button_classname'    => array(
                'offer-btn-d-inline-block',
                'offer-button ',
                'wpfunnels_offer_button',$offer_obj->module_classname( $render_slug )
            ),
            'button_text'      => $button_text,
            'button_data'      => $button_type,


        ) );
        $step_id = isset($_POST['current_page']['id']) ? $_POST['current_page']['id'] : get_the_ID();

        ob_start();
        if ($props['variation_alignment'] == 'center'){
            echo '<style>
                    #et-boc .et-l #wpfnl-offerbtn-wrapper {
                        margin: 0 auto;
                        text-align: center;
                    }
                    #et-boc .et-l #wpfnl-offerbtn-wrapper .wpfnl-offerbtn-and-price-wrapper {
                        justify-content: center;
                    }
                    #et-boc .et-l #wpfnl-offerbtn-wrapper .wpfnl-offerbtn-and-price-wrapper.price-right {
                        justify-content: center;
                    }
                    #et-boc .et-l #wpfnl-offerbtn-wrapper .wpfnl-offerbtn-and-price-wrapper.price-top {
                        align-items: center;
                    }
                    #et-boc .et-l #wpfnl-offerbtn-wrapper .wpfnl-offerbtn-and-price-wrapper.price-bottom {
                        align-items: center;
                    }
                    
                </style>';
        }

        if($props['variation_alignment'] == 'right'){
            echo '<style>
                    #et-boc .et-l #wpfnl-offerbtn-wrapper {
                        margin-left: auto;
                        margin-right: 0;
                        text-align: right;
                    }
                    #et-boc .et-l #wpfnl-offerbtn-wrapper .wpfnl-offerbtn-and-price-wrapper {
                        justify-content: flex-end;
                    }
                    #et-boc .et-l #wpfnl-offerbtn-wrapper .wpfnl-offerbtn-and-price-wrapper.price-right {
                        justify-content: flex-start;
                    }
                    #et-boc .et-l #wpfnl-offerbtn-wrapper .wpfnl-offerbtn-and-price-wrapper.price-top {
                        align-items: flex-end;
                    }
                    #et-boc .et-l #wpfnl-offerbtn-wrapper .wpfnl-offerbtn-and-price-wrapper.price-bottom {
                        align-items: flex-end;
                    }
                </style>';
        }

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
        $builder = 'divi';
       
        if( !is_object($offer_product) || null === $offer_product) {
            return;
        }

        if( 'yes' === $is_gbf && isset($props['show_product_data']) &&  'yes' === $props['show_product_data'] && 'accept' === $button_action ){ 
            require WPFNL_PRO_DIR . 'public/modules/dynamic-offer-templates/styles/offer-'.$props['dynamic_data_template_layout'].'.php';
        }else{
            require WPFNL_PRO_DIR . 'public/modules/dynamic-offer-templates/divi/offer-button.php';
        }
        return ob_get_clean();

    }

    /**
     * Get button alignment.
     *
     * @since 3.23 Add responsive support by adding device parameter.
     *
     * @param  string $device Current device name.
     * @return string         Alignment value, rtl or not.
     */
    public function get_button_alignment( $device = 'desktop' ) {
        $suffix           = 'desktop' !== $device ? "_{$device}" : '';
        $text_orientation = isset( $this->props[ "button_alignment{$suffix}" ] ) ? $this->props[ "button_alignment{$suffix}" ] : '';

        return et_pb_get_alignment( $text_orientation );
    }
    /**
     * Helper method for rendering button markup which works compatible with advanced options' button
     * @param array $args button settings.
     *
     * @return string rendered button HTML
     */
    public function render_button( $args = array() ) {
        // Prepare arguments.
        $defaults = array(
            'button_id'           => '',
            'button_classname'    => array(),
            'button_custom'       => '',
            'button_rel'          => '',
            'button_text'         => '',
            'button_text_escaped' => false,
            'button_url'          => '',
            'custom_icon'         => '',
            'custom_icon_tablet'  => '',
            'custom_icon_phone'   => '',
            'display_button'      => true,
            'has_wrapper'         => true,
            'url_new_window'      => '',
            'multi_view_data'     => '',
            'button_data'         => '',
        );

        $args = wp_parse_args( $args, $defaults );

        // Do not proceed if display_button argument is false.
        if ( ! $args['display_button'] ) {
            return '';
        }

        $button_text = $args['button_text_escaped'] ? $args['button_text'] : esc_html( $args['button_text'] );

        // Do not proceed if button_text argument is empty and not having multi view value.
        if ( '' === $button_text && ! $args['multi_view_data'] ) {
            return '';
        }

        // Button classname.
        $button_classname = array( 'et_pb_button' );

        if ( ( '' !== $args['custom_icon'] || '' !== $args['custom_icon_tablet'] || '' !== $args['custom_icon_phone'] ) && 'on' === $args['button_custom'] ) {
            $button_classname[] = 'et_pb_custom_button_icon';
        }

        // Add multi view CSS hidden helper class when button text is empty on desktop mode.
        if ( '' === $button_text && $args['multi_view_data'] ) {
            $button_classname[] = 'et_multi_view_hidden';
        }

        if ( ! empty( $args['button_classname'] ) ) {
            $button_classname = array_merge( $button_classname, $args['button_classname'] );
        }

        // Custom icon data attribute.
        $use_data_icon = '' !== $args['custom_icon'] && 'on' === $args['button_custom'];
        $data_icon     = $use_data_icon ? sprintf(
            ' data-icon="%1$s"',
            esc_attr( et_pb_process_font_icon( $args['custom_icon'] ) )
        ) : '';

        $use_data_icon_tablet = '' !== $args['custom_icon_tablet'] && 'on' === $args['button_custom'];
        $data_icon_tablet     = $use_data_icon_tablet ? sprintf(
            ' data-icon-tablet="%1$s"',
            esc_attr( et_pb_process_font_icon( $args['custom_icon_tablet'] ) )
        ) : '';

        $use_data_icon_phone = '' !== $args['custom_icon_phone'] && 'on' === $args['button_custom'];
        $data_icon_phone     = $use_data_icon_phone ? sprintf(
            ' data-icon-phone="%1$s"',
            esc_attr( et_pb_process_font_icon( $args['custom_icon_phone'] ) )
        ) : '';
        $button_data = '' !== $args['button_data'];
        $button_data_type     = $button_data ? sprintf(
            ' data-offertype="%1$s"',
            esc_attr( et_pb_process_font_icon( $args['button_data'] ) )
        ) : '';


        // Render button.
        return sprintf(
            '%7$s<a%9$s class="%5$s" %13$s href="%1$s"%3$s%4$s%6$s%10$s%11$s%12$s>%2$s</a>%8$s',
            esc_url( $args['button_url'] ),
            et_core_esc_previously( $button_text ),
            ( 'on' === $args['url_new_window'] ? ' target="_blank"' : '' ),
            et_core_esc_previously( $data_icon ),
            esc_attr( implode( ' ', array_unique( $button_classname ) ) ), // #5
            et_core_esc_previously( $this->get_rel_attributes( $args['button_rel'] ) ),
            $args['has_wrapper'] ? '<div class="et_pb_button_wrapper">' : '',
            $args['has_wrapper'] ? '</div>' : '',
            '' !== $args['button_id'] ? sprintf( ' id="%1$s"', esc_attr( $args['button_id'] ) ) : '',
            et_core_esc_previously( $data_icon_tablet ), // #10
            et_core_esc_previously( $data_icon_phone ),
            et_core_esc_previously( $args['multi_view_data'] ),
            $button_data_type
        );
    }

    /**
     * Render module output
     * @param array  $attrs       List of unprocessed attributes
     * @param string $content     Content being processed
     * @param string $render_slug Slug of module that is used for rendering output
     *
     * @return string module's rendered output
     */
    function render( $attrs, $content = null, $render_slug ) {
        return  self::get_variation_form($this->props, $render_slug );
    }

    public function render_text( $text ){
        $html = '';
        $html .= '<span>';
        $html .= '<span>'.$text.'</span>';
        $html .= '</span>';
        return $html;
    }

    /**
     * Filter multi view value.
     *
     * @since 3.27.1
     *
     * @see ET_Builder_Module_Helper_MultiViewOptions::filter_value
     *
     * @param mixed                                     $raw_value Props raw value.
     * @param array                                     $args {
     *                                         Context data.
     *
     *     @type string $context      Context param: content, attrs, visibility, classes.
     *     @type string $name         Module options props name.
     *     @type string $mode         Current data mode: desktop, hover, tablet, phone.
     *     @type string $attr_key     Attribute key for attrs context data. Example: src, class, etc.
     *     @type string $attr_sub_key Attribute sub key that availabe when passing attrs value as array such as styes. Example: padding-top, margin-botton, etc.
     * }
     * @param ET_Builder_Module_Helper_MultiViewOptions $multi_view Multiview object instance.
     *
     * @return mixed
     */
    public function multi_view_filter_value( $raw_value, $args, $multi_view ) {
        $name    = isset( $args['name'] ) ? $args['name'] : '';
        $mode    = isset( $args['mode'] ) ? $args['mode'] : '';
        $context = isset( $args['context'] ) ? $args['context'] : '';

        $fields_need_escape = array(
            'title',
        );

        if ( $raw_value && 'content' === $context && in_array( $name, $fields_need_escape, true ) ) {
            return $this->_esc_attr( $multi_view->get_name_by_mode( $name, $mode ), 'none', $raw_value );
        }

        return $raw_value;
    }


    /**
     * render variable markup
     */
    private function render_vaiable_markup( $key, $value, $product, $product_id ){
        require WPFNL_PRO_DIR . 'includes/core/shortcodes/variable-template/variable-select-box.php';
    }
}

if(is_plugin_active('woocommerce/woocommerce.php')){
    new WPFNL_Offer_Button;
}