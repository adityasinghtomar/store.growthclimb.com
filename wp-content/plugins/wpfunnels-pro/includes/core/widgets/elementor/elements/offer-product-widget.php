<?php

namespace WPFunnelsPro\Widgets\Elementor;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use WPFunnelsPro\OfferProduct\Wpfnl_Offer_Product;
use Elementor\Controls_Stack;
class Offer_Product_Widget extends Widget_Base {


    /**
     * Register the widget controls.
     *
     * Adds different input fields to allow the user to change and Wpvrize the widget settings.
     *
     * @since 1.0.0
     *
     * @access protected
     */
    protected function init_controls() {
        if ( version_compare(ELEMENTOR_VERSION, '3.1.0', '>=') ) {
            $this->register_controls();
        } else {
            $this->_register_controls();
        }
    }




    /**
     * @inheritDoc
     */
    public function get_name()
    {
        // TODO: Implement get_name() method.
        return 'wpfnl-offer-product-widget';
    }


    /**
     * widget title
     *
     * @return string|void
     */
    public function get_title()
    {
        return __('Offer Product Meta', 'wpfnl-pro');
    }


    /**
     * widget icon
     *
     * @return string
     */
    public function get_icon()
    {
        return 'icon-wpfnl sell-accept';
    }


    /**
     * get widget categories
     *
     * @return array
     */
    public function get_categories()
    {
        return ['wp-funnel'];
    }


    /**
     * register product Widget controls
     */
    protected function _register_controls(){
        $this->register_product_layout_meta();
        $this->register_product_layout_title();
        $this->register_product_layout_price();
        $this->register_product_layout_description();
        $this->register_product_layout_image();
    }


    /**
     * register product Widget controls
     */
    protected function register_controls(){
        $this->register_product_layout_meta();
        $this->register_product_layout_title();
        $this->register_product_layout_price();
        $this->register_product_layout_description();
        $this->register_product_layout_image();
    }



    /**
     * Register Product Meta Controls.
     * @access protected
     */
    protected function register_product_layout_meta(){
        $this->start_controls_section(
            'wpfnl_offer_product_meta',
            array(
                'label' => __( 'Offer Product Meta', 'wpfnl-pro' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );
        $this->add_control(
            'show_title',
            [
                'label' => __( 'Show title', 'wpfnl-pro' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __( 'Show', 'wpfnl-pro' ),
                'label_off' => __( 'Hide', 'wpfnl-pro' ),
                'return_value' => 'yes',
                'default' => 'yes',

            ]
        );
        $this->add_control(
            'show_price',
            [
                'label' => __( 'Show Price', 'wpfnl-pro' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __( 'Show', 'wpfnl-pro' ),
                'label_off' => __( 'Hide', 'wpfnl-pro' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        $this->add_control(
            'show_description',
            [
                'label' => __( 'Show Description', 'wpfnl-pro' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __( 'Show', 'wpfnl-pro' ),
                'label_off' => __( 'Hide', 'wpfnl-pro' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        $this->add_control(
            'show_image',
            [
                'label' => __( 'Show Image', 'wpfnl-pro' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __( 'Show', 'wpfnl-pro' ),
                'label_off' => __( 'Hide', 'wpfnl-pro' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register Product Layout Title
     * @access protected
     */
    protected function register_product_layout_title(){

        $this->start_controls_section(
            'wpfnl_offer_product_title',
            array(
                'label' => __( 'Offer Product Title', 'wpfnl-pro' ),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_title' => 'yes',
                ),
            )
        );

        $this->add_responsive_control(
            'title_align',
            array(
                'label'        => __( 'Alignment', 'wpfnl-pro' ),
                'type'         => Controls_Manager::CHOOSE,
                'options'      => array(
                    'left'    => array(
                        'title' => __( 'Left', 'wpfnl-pro' ),
                        'icon'  => 'fa fa-align-left',
                    ),
                    'center'  => array(
                        'title' => __( 'Center', 'wpfnl-pro' ),
                        'icon'  => 'fa fa-align-center',
                    ),
                    'right'   => array(
                        'title' => __( 'Right', 'wpfnl-pro' ),
                        'icon'  => 'fa fa-align-right',
                    )
                ),
                'default'      => 'left',
                'prefix_class' => 'elementor%s-align-',
            )
        );
        $this->add_control(
            'title_text_color',
            array(
                'label'     => __( 'Text Color', 'wpfnl-pro' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => array(
                    '{{WRAPPER}} .wpfnl-pro-elementor-offer-product-title' => 'color: {{VALUE}};',
                ),
            )
        );
        $this->end_controls_section();
    }
    /**
     * Register Product Layout Price
     * @access protected
     */
    protected function register_product_layout_price(){
        $this->start_controls_section(
            'wpfnl_offer_product_price',
            array(
                'label' => __( 'Offer Product Price', 'wpfnl-pro' ),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_price' => 'yes',
                ),
            )
        );
        $this->add_responsive_control(
            'price_align',
            array(
                'label'        => __( 'Alignment', 'wpfnl-pro' ),
                'type'         => Controls_Manager::CHOOSE,
                'options'      => array(
                    'left'    => array(
                        'title' => __( 'Left', 'wpfnl-pro' ),
                        'icon'  => 'fa fa-align-left',
                    ),
                    'center'  => array(
                        'title' => __( 'Center', 'wpfnl-pro' ),
                        'icon'  => 'fa fa-align-center',
                    ),
                    'right'   => array(
                        'title' => __( 'Right', 'wpfnl-pro' ),
                        'icon'  => 'fa fa-align-right',
                    )
                ),
                'default'      => 'left',
                'prefix_class' => 'elementor%s-align-',
            )
        );
        $this->add_control(
            'price_text_color',
            array(
                'label'     => __( 'Text Color', 'wpfnl-pro' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => array(
                    '{{WRAPPER}} .wpfnl-pro-elementor-offer-product-price' => 'color: {{VALUE}};',
                ),
            )
        );
        $this->end_controls_section();
    }
    /**
     * Register Product Layout Description
     * @access protected
     */
    protected function register_product_layout_description(){
        $this->start_controls_section(
            'wpfnl_offer_product_description',
            array(
                'label' => __( 'Offer Product Description', 'wpfnl-pro' ),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_description' => 'yes',
                ),
            )
        );
        $this->add_responsive_control(
            'description_align',
            array(
                'label'        => __( 'Alignment', 'wpfnl-pro' ),
                'type'         => Controls_Manager::CHOOSE,
                'options'      => array(
                    'left'    => array(
                        'title' => __( 'Left', 'wpfnl-pro' ),
                        'icon'  => 'fa fa-align-left',
                    ),
                    'center'  => array(
                        'title' => __( 'Center', 'wpfnl-pro' ),
                        'icon'  => 'fa fa-align-center',
                    ),
                    'right'   => array(
                        'title' => __( 'Right', 'wpfnl-pro' ),
                        'icon'  => 'fa fa-align-right',
                    )
                ),
                'default'      => 'left',
                'prefix_class' => 'elementor%s-align-',
            )
        );
        $this->add_control(
            'description_text_color',
            array(
                'label'     => __( 'Text Color', 'wpfnl-pro' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => array(
                    '{{WRAPPER}} .wpfnl-pro-elementor-offer-product-description' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();
    }
    /**
     * Register Product Layout Image
     * @access protected
     */
    protected function register_product_layout_image(){
        $this->start_controls_section(
            'wpfnl_offer_product_image',
            array(
                'label' => __( 'Offer Product Image', 'wpfnl-pro' ),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_image' => 'yes',
                ),
            )
        );


        $this->add_responsive_control(
            'align',
            array(
                'label'        => __( 'Alignment', 'wpfnl-pro' ),
                'type'         => Controls_Manager::CHOOSE,
                'options'      => array(
                    'left'    => array(
                        'title' => __( 'Left', 'wpfnl-pro' ),
                        'icon'  => 'fa fa-align-left',
                    ),
                    'center'  => array(
                        'title' => __( 'Center', 'wpfnl-pro' ),
                        'icon'  => 'fa fa-align-center',
                    ),
                    'right'   => array(
                        'title' => __( 'Right', 'wpfnl-pro' ),
                        'icon'  => 'fa fa-align-right',
                    )
                ),
                'default'      => 'left',
                'prefix_class' => 'elementor%s-align-',
            )
        );

        $this->add_control(
            'text_color',
            array(
                'label'     => __( 'Text Color', 'wpfnl-pro' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => array(
                    '{{WRAPPER}} .wpfnl-pro-elementor-offer-product-image' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render the Product  widget output on the frontend.
     * @access protected
     */

    protected function render() {
        $settings = $this->get_settings_for_display();
        $offer_product = Wpfnl_Offer_Product::getInstance()->get_offer_product();
        if( !is_object($offer_product) || null === $offer_product) {
            return;
        }
        $image      = wp_get_attachment_image_src(get_post_thumbnail_id($offer_product->get_id()), 'single-post-thumbnail');
            if ( 'yes' === $settings['show_title'] ) {
                echo '<div class = "wpfnl-pro-elementor-offer-product-title">
                        '.$offer_product->get_title().'
                    </div>';
            }
            if ( 'yes' === $settings['show_price'] ) {
                echo '<div class = "wpfnl-pro-elementor-offer-product-price">
                        '.Wpfnl_Offer_Product::getInstance()->get_offer_product_price().'
                    </div>';
            }
            if ( 'yes' === $settings['show_description'] ) {
                echo '<div class = "wpfnl-pro-elementor-offer-product-description">
                        '.$offer_product->get_description().'
                    </div>';
            }
            if ( 'yes' === $settings['show_image'] ) {
                echo '<div class = "wpfnl-pro-elementor-offer-product-image">
                        <img src="'.$image[0].'"
                    </div>';
            }

    }
}