<?php

namespace WPFunnelsPro\Widgets\DiviModules\Modules;

use ET_Builder_Element;
use ET_Builder_Module;
use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;
use WPFunnelsPro\OfferProduct\Wpfnl_Offer_Product;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;

class WPFNL_Lms_Pay_Button extends ET_Builder_Module {

    public $slug       = 'wpfnl_lms_pay_button';
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
        $this->name             = __( 'WPF Lms Pay Button', 'wpfnl-pro' );

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
                    )
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
            'borders'         => array(
                'default' => false,
            ),
            'button'          => array(
                'button' => array(
                    'label'          => __( 'LMS Reject', 'wpfnl-pro' ),
                    'css'            => array(
//                        'main'         => $this->main_css_element.'.et_pb_button' ,
//                        'main'         => "{$this->main_css_element}.et_pb_button" ,
                        'limited_main' => "{$this->main_css_element}.et_pb_button",
                    ),
                    'box_shadow'     => false,
                    'text_shadow'     => false,
                    'margin_padding'  => array(
                        'css' => array(
                            'main'    => "{$this->main_css_element} .et_pb_button",
//                            'main'    => ".et_pb_button",
                            'important' => 'all',
                        ),
                    ),
                ),
                'button_offer_lms' => array(
                        'label'           =>  __( ' LMS Accept Button', 'wpfnl' ),
                        'css'             => array(
                            'main' => '%%order_class%% .btn-join',
                            'important' => 'all',
                        ),
                        'use_alignment'   => false,
                        'border_width'    => array(
                            'default' => '2px',
                        ),
                        'box_shadow'      => array(
                            'css' => array(
                                'main' => '%%order_class%% .btn-join',
                            ),
                        ),
                        'margin_padding'  => array(
                            'css' => array(
                                'important' => 'all',
                            ),
                        ),
                        'toggle_priority' => 80,
                    ),

            ),
            'box_shadow'     => false,
            'margin_padding' => false,
            'text_shadow'     => array(
                'default' => false,
            ),
            'background'      => false,
            'fonts'           => false,
            'max_width'       => false,
            'height'          => false,
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
                'default'         => 'I will pass',
                'computed_affects' => array(
                    '__variationForm'
                ),
                'show_if'          => array(
                    'button_action' => 'reject',
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
            '__variationForm'        => array(
                'type'                => 'computed',
                'computed_callback'   => array(
                    'WPFunnelsPro\Widgets\DiviModules\Modules\WPFNL_Lms_Pay_Button',
                    'get_variation_form',
                ),
                'computed_depends_on' => array(
                    'button_action',
                    'button_type',
                    'button_text',
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
                'lms-offer-button ',
                'wpfunnels_offer_button',$offer_obj->module_classname( $render_slug )
            ),
            'button_text'      => $button_text,
            'button_data'      => $button_type,


        ) );
        $step_id = isset($_POST['current_page']['id']) ? $_POST['current_page']['id'] : get_the_ID();
        $funnel_id = get_post_meta($step_id,'_funnel_id',true);
        $get_learn_dash_setting = Wpfnl_lms_learndash_functions::get_learndash_settings($funnel_id);
        ob_start();
        $lms_button_text = get_option( 'learndash_settings_custom_labels' );
        if ($get_learn_dash_setting == 'yes'){
            $step_type = get_post_meta($step_id, '_step_type', true);
            if ($step_type != 'upsell' && $step_type != 'downsell'){
                echo __('Sorry, Please place the element in WPFunnels Offer page');
            }else{
                if ($button_action == 'accept'){
                    $button_text = !empty($lms_button_text['button_take_this_course']) ? $lms_button_text['button_take_this_course'] : "Take this course";

                    $lms_product_id = get_post_meta($step_id,'_wpfnl_upsell_products',true);
                    if ( $step_type == 'downsell'){
                        $lms_product_id = get_post_meta($step_id,'_wpfnl_down_products',true);
                    }
                    $course_access = sfwd_lms_has_access( $lms_product_id[0]['id'], get_current_user_id() );
                    $next_step_url = Wpfnl_lms_learndash_functions::get_next_step_url($funnel_id,$step_id).'?wpfnl_ld_payment=free';
                    if ($course_access ){
                        echo '<a class="btn-default btn-join" href="'.$next_step_url.'" id="wpfnl-lms-access-course">'.$button_text.'</a>';
                        echo '<span class="wpfnl-lms-access-course-message"></span>';
                    }else{
                        if( self::is_buider_mode()) {
                            echo '<div class="learndash-block-inner"><a class="btn-join btn-default" href="#" id="btn-join"> '.$button_text.'</a></div>';
                        }else {
                            $course = isset($lms_product_id[0]['id']) ? Wpfnl_lms_learndash_functions::get_course_details_by_id($lms_product_id[0]['id']) : [];
                            if( !empty($course) ){
                                if( isset($course['type'])  && $course['type'] == 'free' ){
                                    $next_step_url = Wpfnl_lms_learndash_functions::get_next_step_url($funnel_id,$step_id);
                                    echo '<a class="btn-default" href="'.$next_step_url.'" user_id="'.get_current_user_id().'" step_id="'.$step_id.'" course_id="'.$course['id'].'" id="wpfnl-lms-free-course">'.$button_text.'</a>';
                                    echo '<span class="wpfnl-lms-free-course-message"></span>';
                                }
                                else{
                                    echo do_shortcode('[learndash_payment_buttons course_id='.$lms_product_id[0]['id'].']');
                                }
                            }
                           
                        }
                    }
                }else{
                    ?>
                    <?php echo $button; ?>
                    <?php
                }
            }
        }else{
            echo __('Sorry, Please place the element in WPFunnels when learnDash is active ');
        }
        return ob_get_clean();

    }

   public static function is_buider_mode(){
        if(isset($_POST['action']) == 'et_pb_process_computed_property'){
            return true;
        }
        return false;
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

new WPFNL_Lms_Pay_Button;
