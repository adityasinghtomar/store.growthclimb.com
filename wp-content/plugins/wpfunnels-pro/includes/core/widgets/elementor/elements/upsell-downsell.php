<?php

namespace WPFunnelsPro\Widgets\Elementor;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Icons_Manager;
use Elementor\Core\Schemes\Color as Scheme_Color;
use Elementor\Controls_Stack;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Funnel sell accept button
 *
 * @since 1.0.0
 */
class Upsell_Downsell extends Widget_Base
{

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
     * Retrieve the widget name.
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name()
    {
        return 'wpfnl-upsell-downsell';
    }

    /**
     * Retrieve the widget title.
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title()
    {
        return __('Upsell/Downsell', 'wpfnl');
    }

    /**
     * Retrieve the widget icon.
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon()
    {
        return 'icon-wpfnl sell-accept';
    }

    /**
     * Retrieve the list of categories the widget belongs to.
     *
     * Used to determine where to display the widget in the editor.
     *
     * Note that currently Elementor supports only one category.
     * When multiple categories passed, Elementor uses the first one.
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return array Widget categories.
     */
    public function get_categories()
    {
        return [ 'wp-funnel' ];
    }

    /**
     * Retrieve the list of scripts the widget depended on.
     *
     * Used to set scripts dependencies required to run the widget.
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return array Widget scripts dependencies.
     */
    public function get_script_depends()
    {
        return [ 'upsell-downsell-widget' ];
    }

    /**
     * Get button sizes.
     *
     * Retrieve an array of button sizes for the button widget.
     *
     * @since 1.0.0
     * @access public
     * @static
     *
     * @return array An array containing button sizes.
     */
    public static function get_button_sizes()
    {
        return [
        'xs' => __('Extra Small', 'elementor'),
        'sm' => __('Small', 'elementor'),
        'md' => __('Medium', 'elementor'),
        'lg' => __('Large', 'elementor'),
        'xl' => __('Extra Large', 'elementor'),
      ];
    }

    /**
     * Register the widget controls.
     * @since 1.0.0
     *
     * @access protected
     */
    protected function _register_controls()
    {
        $this->wpfnl_upsell_downsell_controls();
    }


    /**
     * Register the widget controls.
     * @since 1.0.0
     *
     * @access protected
     */
    protected function register_controls()
    {
        $this->wpfnl_upsell_downsell_controls();
    }

    /**
     * Upsell Downsell controls.
     * @since 1.0.0
     *
     * @access protected
     */
    protected function wpfnl_upsell_downsell_controls(){
        $this->start_controls_section(
            'section_button',
            [
                'label' => __('Upsell/Downsell', 'wpfnl'),
            ]
        );

        $this->add_control(
            'upsell_downsell_selector',
            [
            'label' => __('Select Upsell/Downsell', 'wpfnl'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => [
              'upsell'  => __('Upsell', 'wpfnl'),
              'downsell' => __('Downsell', 'wpfnl'),
            ],
          ]
        );

        $this->add_control(
            'upsell_accept_reject_selector',
            [
            'label' => __('Select Accept/Reject', 'wpfnl'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => [
              'accept'  => __('Accept', 'wpfnl'),
              'reject' => __('Reject', 'wpfnl'),
            ],
            'condition' => [
                'upsell_downsell_selector' => 'upsell',
            ]
          ]
        );

        $this->add_control(
            'downsell_accept_reject_selector',
            [
            'label' => __('Select Accept/Reject', 'wpfnl'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => [
              'accept'  => __('Accept', 'wpfnl'),
              'reject' => __('Reject', 'wpfnl'),
            ],
            'condition' => [
                'upsell_downsell_selector' => 'downsell',
            ]
          ]
        );

        $upsell_product = get_post_meta(get_the_ID(), '_wpfnl_upsell_products', true);

        // $this->add_control(
        //     'upsell_product_selector',
        //     [
        //         'label' => __( ' Upsell Product', 'wpfnl' ),
        //         'type' => \WPFunnels\Widgets\Elementor\Controls\Product_Control::ProductSelector,
        //         'options' => $this->get_products_array(),
        //         'default' => $upsell_product['id'],
        //         'condition' => [
        //           'upsell_downsell_selector' => 'upsell',
        //           'upsell_accept_reject_selector' => 'accept',
        //         ]
        //     ]
        // );
        //
        // $downsell_product = get_post_meta(get_the_ID(), '_wpfnl_downsell_product', true);
        //
        // $this->add_control(
        //     'downsell_product_selector',
        //     [
        //         'label' => __( ' Downsell Product', 'wpfnl' ),
        //         'type' => \WPFunnels\Widgets\Elementor\Controls\Product_Control::ProductSelector,
        //         'options' => $this->get_products_array(),
        //         'default' => $downsell_product['id'],
        //         'condition' => [
        //           'upsell_downsell_selector' => 'downsell',
        //           'downsell_accept_reject_selector' => 'accept',
        //         ]
        //     ]
        // );

        // $this->add_control(
        //     'upsell_accept_next_step_selector',
        //     [
        //         'label' => __( 'Select Next Step if accept', 'wpfnl' ),
        //         'type' => \Elementor\Controls_Manager::SELECT,
        //         'groups' => array(
        //             array(
        //                 'label' => __('Downsell', 'wpfnl'),
        //                 'options' => self::get_steps_array('downsell')
        //             ),
        //             array(
        //                 'label' => __('Thank You', 'wpfnl'),
        //                 'options' => self::get_steps_array('thankyou')
        //             ),
        //         ),
        //         'condition' => [
        //           'upsell_downsell_selector' => 'upsell',
        //           'upsell_accept_reject_selector' => 'accept',
        //       ]
        //    ]
        // );

        // $this->add_control(
        //     'upsell_reject_next_step_selector',
        //     [
        //         'label' => __( 'Select Next Step if reject', 'wpfnl' ),
        //         'type' => \Elementor\Controls_Manager::SELECT,
        //         'groups' => array(
        //             array(
        //                 'label' => __('Downsell', 'wpfnl'),
        //                 'options' => self::get_steps_array('downsell')
        //             ),
        //             array(
        //                 'label' => __('Thank You', 'wpfnl'),
        //                 'options' => self::get_steps_array('thankyou')
        //             ),
        //         ),
        //         'condition' => [
        //           'upsell_downsell_selector' => 'upsell',
        //           'upsell_accept_reject_selector' => 'reject',
        //       ]
        //    ]
        // );

        // $this->add_control(
        //     'downsell_accept_next_step_selector',
        //     [
        //         'label' => __( 'Select Next Step', 'wpfnl' ),
        //         'type' => \Elementor\Controls_Manager::SELECT,
        //         'groups' => array(
        //             array(
        //                 'label' => __('Upsell', 'wpfnl'),
        //                 'options' => self::get_steps_array('upsell')
        //             ),
        //             array(
        //                 'label' => __('Thank You', 'wpfnl'),
        //                 'options' => self::get_steps_array('thankyou')
        //             ),
        //         ),
        //         'condition' => [
        //           'upsell_downsell_selector' => 'downsell',
        //           'downsell_accept_reject_selector' => 'accept',
        //       ]
        //    ]
        // );
        //
        // $this->add_control(
        //     'downsell_reject_next_step_selector',
        //     [
        //         'label' => __( 'Select Next Step', 'wpfnl' ),
        //         'type' => \Elementor\Controls_Manager::SELECT,
        //         'groups' => array(
        //             array(
        //                 'label' => __('Upsell', 'wpfnl'),
        //                 'options' => self::get_steps_array('upsell')
        //             ),
        //             array(
        //                 'label' => __('Thank You', 'wpfnl'),
        //                 'options' => self::get_steps_array('thankyou')
        //             ),
        //         ),
        //         'condition' => [
        //           'upsell_downsell_selector' => 'downsell',
        //           'downsell_accept_reject_selector' => 'reject',
        //       ]
        //    ]
        // );

        $this->add_control(
            'text',
            [
                'label' => __('Text', 'elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Buy offer', 'elementor'),
                'placeholder' => __('Buy offer', 'elementor'),
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'align',
            [
                'label' => __('Alignment', 'elementor'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left'    => [
                        'title' => __('Left', 'elementor'),
                        'icon' => 'fa fa-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'elementor'),
                        'icon' => 'fa fa-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'elementor'),
                        'icon' => 'fa fa-align-right',
                    ],
                    'justify' => [
                        'title' => __('Justified', 'elementor'),
                        'icon' => 'fa fa-align-justify',
                    ],
                ],
                'prefix_class' => 'elementor%s-align-',
                'default' => '',
            ]
        );

        $this->add_responsive_control(
            'size',
            [
                'label' => __('Size', 'elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => 'sm',
                'options' => self::get_button_sizes(),
            ]
        );

        $this->add_control(
			'upsell_downsell_button_icon',
			[
				'label' => __( 'Icon', 'wpfnl' ),
				'type' => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
			]
        );

        $this->add_control(
			'upsell_downsell_button_icon_align',
			[
				'label' => __( 'Icon Position', 'elementor' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => [
					'left' => __( 'Before', 'elementor' ),
					'right' => __( 'After', 'elementor' ),
				],
				'condition' => [
					'upsell_downsell_button_icon[value]!' => '',
				],
			]
		);

        $this->add_control(
            'upsell_downsell_button_icon_indent',
            [
                'label' => __( 'Icon Spacing', 'wpfnl' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                    'max' => 50,
                    ],
                ],
                'condition' => [
                    'upsell_downsell_button_icon!' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button .elementor-align-icon-right' => 'margin-left: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .elementor-button .elementor-align-icon-left' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'view',
            [
                'label' => __('View', 'elementor'),
                'type' => Controls_Manager::HIDDEN,
                'default' => 'traditional',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => __('Button', 'elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'upsell_downsell_button_typography',
                'label'    => 'Typography',
                'selector' => '{{WRAPPER}} a.elementor-button',
            ]
        );

        $this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'upsell_downsell_button_text_shadow',
				'selector' => '{{WRAPPER}} a.elementor-button',
			]
		);

        $this->start_controls_tabs('tabs_button_style');

        $this->start_controls_tab(
            'tab_button_normal',
            [
                'label' => __('Normal', 'elementor'),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Text Color', 'elementor'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} a.elementor-button, {{WRAPPER}} .elementor-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'background_color',
            [
                'label' => __('Background Color', 'elementor'),
                'type' => Controls_Manager::COLOR,
                'scheme' => [
                    'type' => Scheme_Color::get_type(),
                    'value' => Scheme_Color::COLOR_4,
                ],
                'selectors' => [
                    '{{WRAPPER}} a.elementor-button, {{WRAPPER}} .elementor-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_button_hover',
            [
                'label' => __('Hover', 'elementor'),
            ]
        );

        $this->add_control(
            'hover_color',
            [
                'label' => __('Text Color', 'elementor'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} a.elementor-button:hover, {{WRAPPER}} .elementor-button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_hover_color',
            [
                'label' => __('Background Color', 'elementor'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} a.elementor-button:hover, {{WRAPPER}} .elementor-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_border_color',
            [
                'label' => __('Border Color', 'elementor'),
                'type' => Controls_Manager::COLOR,
                'condition' => [
                    'border_border!' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}} a.elementor-button:hover, {{WRAPPER}} .elementor-button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'hover_animation',
            [
                'label' => __('Animation', 'elementor'),
                'type' => Controls_Manager::HOVER_ANIMATION,
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'border',
                'label' => __('Border', 'elementor'),
                'placeholder' => '1px',
                'default' => '1px',
                'selector' => '{{WRAPPER}} .elementor-button',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'border_radius',
            [
                'label' => __('Border Radius', 'elementor'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} a.elementor-button, {{WRAPPER}} .elementor-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'selector' => '{{WRAPPER}} .elementor-button',
            ]
        );

        $this->add_responsive_control(
            'upsell_downsell_padding',
            [
                'label' => __('Padding', 'elementor'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} a.elementor-button, {{WRAPPER}} .elementor-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }



    public function get_prev_next_link_options()
    {
        $associate_funnel_id = get_post_meta(get_the_ID(), '_funnel_id', true);
        $steps_array = [
            'upsell' => 'Upsell',
            'downsell' => 'Downsell',
            'thankyou' => 'Thankyou'
        ];
        $option_group = [];
        foreach ($steps_array as $key=>$value) {
            $args = [
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'post_type'      => WPFNL_STEPS_POST_TYPE,
                'post_status'    => 'publish',
                'post__not_in'   => [ $this->get_id() ],
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key'     => '_step_type',
                        'value'   => $key,
                        'compare' => '=',
                    ],
                    [
                        'key'     => '_funnel_id',
                        'value'   => $associate_funnel_id,
                        'compare' => '=',
                    ],
                ],
            ];
            $query = new \WP_Query($args);
            $steps = $query->posts;
            if ($steps) {
                foreach ($steps as $s) {
                    $option_group[$key][] = [
                        'id'    => $s->ID,
                        'title' => $s->post_title,
                    ];
                }
            }
        }
        return $option_group;
    }

    /**
     * Get all WC products
     * @since 1.0.0
     *
     * @access protected
     */
    protected function get_products_array() {
        $products = array();
        if ( in_array( 'woocommerce/woocommerce.php', WPFNL_ACTIVE_PLUGINS ) ) {
          $ids = wc_get_products( array( 'return' => 'ids', 'limit' => -1 ) );
          if( !empty($ids) ){
            foreach ($ids as $id) {
                $title = get_the_title( $id );
                $products[$id] = $title;
            }
          }
        }
        return $products;
    }

    /**
     * Get all funnel steps
     * @since 1.0.0
     *
     * @access protected
     */
    protected function get_steps_array($type = 'upsell') {
        $options = $this->get_prev_next_link_options();
        $response = array();
        if(isset($options[$type])) {
            $prime_data = $options[$type];
            foreach ($prime_data as $data) {
                $response[$data['id']] = $data['title'];
            }
        }

        return $response;
    }

    /**
     * Render the widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 1.0.0
     *
     * @access protected
     */
    protected function render()
    {
      $settings = $this->get_settings();
      $this->add_render_attribute('wrapper', 'class', 'elementor-button-wrapper');

      $this->add_render_attribute('button', 'class', 'elementor-button');

      if (! empty($settings['size'])) {
          $this->add_render_attribute('button', 'class', 'elementor-size-' . $settings['size']);
      }

      if ($settings['hover_animation']) {
          $this->add_render_attribute('button', 'class', 'elementor-animation-' . $settings['hover_animation']);
      }


      if (isset($settings['upsell_accept_reject_selector']) || isset($settings['downsell_accept_reject_selector'])) {
        if($settings['upsell_accept_reject_selector'] == 'accept' || $settings['downsell_accept_reject_selector'] == 'accept') {
          ?>
          <div  <?php echo $this->get_render_attribute_string('wrapper'); ?>>
              <a href="#" id="upsell_funnel_target" data-id="<?php echo get_the_ID(); ?>" <?php echo $this->get_render_attribute_string('button'); ?>>
                  <?php $this->render_text(); ?>
                  <span class="wpfnl-loader" id="wpfnl-loader-accept"></span>
              </a>
          </div>
          <span class="wpfnl-alert" id="wpfnl-alert-accept"></span>
          <?php
        }
        else {
          ?>
          <div  <?php echo $this->get_render_attribute_string('wrapper'); ?>>
            <a style="cursor: pointer;display: inline-flex;align-items: center;" id="upsell_funnel_reject" data-id="<?php echo get_the_ID(); ?>" <?php echo $this->get_render_attribute_string('button'); ?>>
              <?php $this->render_text(); ?>
              <span class="wpfnl-loader" id="wpfnl-loader-reject"></span>
            </a>
          </div>
          <span class="wpfnl-alert" id="wpfnl-alert-reject"></span>
          <?php
        }
      }
    }

    /**
     * Render button text.
     *
     * Render button widget text.
     *
     * @since 1.5.0
     * @access protected
     */
    protected function render_text() {
        $settings = $this->get_settings();

        $migrated = isset( $settings['__fa4_migrated']['upsell_downsell_button_icon'] );
        $is_new = empty( $settings['icon'] ) && Icons_Manager::is_migration_allowed();

        if ( ! $is_new && empty( $settings['upsell_downsell_button_icon_align'] ) ) {

			$settings['upsell_downsell_button_icon_align'] = $this->get_settings( 'upsell_downsell_button_icon_align' );
		}

		$this->add_render_attribute( [
			'content-wrapper' => [
				'class' => 'elementor-button-content-wrapper',
			],
			'icon-align' => [
				'class' => [
					'elementor-button-icon',
					'elementor-align-icon-' . $settings['upsell_downsell_button_icon_align'],
				],
			],
			'text' => [
				'class' => 'elementor-button-text',
			],
		] );


        $this->add_render_attribute( 'content-wrapper', 'class', 'elementor-button-content-wrapper' );
        $this->add_render_attribute( 'icon-align', 'class', 'elementor-button-icon' );

        $this->add_render_attribute( 'text', 'class', 'elementor-button-text' );
        $this->add_inline_editing_attributes( 'text', 'none' );
        ?>
        <span <?php echo $this->get_render_attribute_string( 'content-wrapper' ); ?>>
            <?php if ( ! empty( $settings['icon'] ) || ! empty( $settings['upsell_downsell_button_icon']['value'] ) ) : ?>
                <span <?php echo $this->get_render_attribute_string( 'icon-align' ); ?>>
                    <?php if ( $is_new || $migrated ) :
                        Icons_Manager::render_icon( $settings['upsell_downsell_button_icon'], [ 'aria-hidden' => 'true' ] );
                    else : ?>
                        <i class="<?php echo esc_attr( $settings['icon'] ); ?>" aria-hidden="true"></i>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
            <span <?php echo $this->get_render_attribute_string( 'text' ); ?>><?php echo $settings['text']; ?></span>
        </span>
        <?php
    }
}
