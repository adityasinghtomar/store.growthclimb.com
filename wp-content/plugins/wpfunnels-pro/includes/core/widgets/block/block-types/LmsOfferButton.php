<?php
namespace WPFunnelsPro\Widgets\Gutenberg\BlockTypes;

use WPFunnels\lms\helper\Wpfnl_lms_learndash_functions;
use WPFunnelsPro\OfferProduct\Wpfnl_Offer_Product;
use WPFunnelsPro\Wpfnl_Pro_functions;
/**
 * OrderDetails class.
 */
class LmsOfferButton extends AbstractBlock {

    protected $defaults = array(
        'buttonAlign' => 'center',
        'buttonTextAlign' => 'center',
        'buttonText' => 'I will pass',
        'offerAction' => 'accept',
        'offerType' => 'upsell',
        'buttonColor' => '#39414d',
        'buttonTextColor' => '#fff',
        'paddingTopBottom' => 14,
        'paddingLeftRight' => 25,
        'buttonRadius' => 5,

    );

    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'lms-offer-button';


    public function __construct( $block_name = '' )
    {
        parent::__construct($block_name);
        add_action('wp_ajax_wpfnl_offer_variable_shortcode', [$this, 'render_offer_variable_shortcode']);
        add_action( 'wp_ajax_nopriv_wpfnl_offer_variable_shortcode', [$this, 'render_offer_variable_shortcode'] );
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
        
        ob_start();

        $step_id  = get_the_ID();
        $funnel_id = get_post_meta($step_id,'_funnel_id',true);
        $get_learn_dash_setting = Wpfnl_lms_learndash_functions::get_learndash_settings($funnel_id);
        $lms_button_text = get_option( 'learndash_settings_custom_labels' );

        if ($get_learn_dash_setting == 'yes'){
            $step_type = get_post_meta($step_id, '_step_type', true);
            if ($step_type != 'upsell' && $step_type != 'downsell'){
                echo __('Sorry, Please place the element in WPFunnels Offer page');
            }else{
                $button_action         = isset($attributes['offerAction']) ? $attributes['offerAction'] : 'accept' ;

                if ($button_action == 'accept'){
                    $button_text = !empty($lms_button_text['button_take_this_course']) ? $lms_button_text['button_take_this_course'] : "Take this course";
                    $lms_product_id = get_post_meta($step_id,'_wpfnl_upsell_products',true);
                    if ($step_type == 'downsell'){
                        $lms_product_id = get_post_meta($step_id,'_wpfnl_downsell_products',true);
                    }
                    $course_access = sfwd_lms_has_access( $lms_product_id[0]['id'], get_current_user_id() );
                    $next_step_url = Wpfnl_lms_learndash_functions::get_next_step_url($funnel_id,$step_id).'?wpfnl_ld_payment=free';
                    if ($course_access ){
                        echo '<a class="btn-default btn-join asdfgasd" href="'.$next_step_url.'" id="wpfnl-lms-access-course">'.$button_text.'</a>';
                        echo '<span class="wpfnl-lms-access-course-message"></span>';
                    }else{

                        if( $this->is_learndash_bilder_mode() ) {
                            echo '<div class="learndash-block-inner"><a class="btn-join" href="#" id="btn-join"> '.$button_text.'</a></div>';

                        } else {
                            echo '<div class="wpfnl-lms-offer-btn-wrapper align-'.$attributes['buttonAlign'].'">';
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
                            $attributes['lmsTypography']['spacing']['unit']  = isset($attributes['lmsTypography']['spacing']['unit']) ? $attributes['lmsTypography']['spacing']['unit'] : '';
                            $attributes['lmsTypography']['size']['unit']  = isset($attributes['lmsTypography']['size']['unit']) ? $attributes['lmsTypography']['size']['unit'] : '';
                            $attributes['lmsTypography']['type']  = isset($attributes['lmsTypography']['type']) ? $attributes['lmsTypography']['type'] : '';
                            $attributes['lmsTypography']['family']  = isset($attributes['lmsTypography']['family']) ? $attributes['lmsTypography']['family'] : '';
                            $attributes['lmsTypography']['weight']  = isset($attributes['lmsTypography']['weight']) ? $attributes['lmsTypography']['weight'] : '';
                            $attributes['lmsTypography']['size']['md']  = isset($attributes['lmsTypography']['size']['md']) ? $attributes['lmsTypography']['size']['md'] : '';
                            $attributes['lmsTypography']['transform']  = isset($attributes['lmsTypography']['transform']) ? $attributes['lmsTypography']['transform'] : '';
                            $attributes['lmsTypography']['spacing']['md']  = isset($attributes['lmsTypography']['spacing']['md']) ? $attributes['lmsTypography']['spacing']['md'] : '';
                            
                            echo '<style>
                                    .wpfnl-lms-offer-btn-wrapper.align-right{
                                        text-align:right;
                                    }
                                    .wpfnl-lms-offer-btn-wrapper.align-center{
                                        text-align:center;
                                    }
                                    .wpfnl-lms-offer-btn-wrapper.align-left{
                                        text-align:left;
                                    }
                                    .wpfnl-lms-offer-btn-wrapper input[type=button],
                                    .wpfnl-lms-offer-btn-wrapper .btn-join, 
                                    .wpfnl-lms-offer-btn-wrapper #btn-join {
                                        background-color:'.$attributes['buttonColor'].'!important;
                                        color:'.$attributes['buttonTextColor'].'!important;
                                        padding:'.$attributes['paddingTopBottom'].'px '.$attributes['paddingLeftRight'].'px;
                                        border-radius:'.$attributes['buttonRadius'].'px;
                                        font-family:'.$attributes['lmsTypography']['family'].','.$attributes['lmsTypography']['type'].';
                                        font-weight:'.$attributes['lmsTypography']['weight'].';
                                        font-size:'.$attributes['lmsTypography']['size']['md'].''.$attributes['lmsTypography']['size']['unit'].';
                                        text-transform:'.$attributes['lmsTypography']['transform'].';
                                        letter-spacing:'.$attributes['lmsTypography']['spacing']['md'].''.$attributes['lmsTypography']['spacing']['unit'].';
                                    }
                            </style>';

                            if( isset($attributes['outline']) ){
                                echo '<style>
                                    .wpfnl-lms-offer-btn-wrapper input[type=button],
                                    .wpfnl-lms-offer-btn-wrapper .btn-join {
                                        background-color: transparent;
                                        color:'.$attributes['buttonTextColor'].';
                                        border: 2px solid '.$attributes['buttonTextColor'].';
                                    }
                            </style>';
                            }

                            echo '</div>';
                        }
                    }

                }else{
                    echo $content;
                }
            }
        }else{
            echo __('Sorry, Please place the element in WPFunnels when learnDash is active ');
        }
        
        return ob_get_clean();
    }

    public function is_learndash_bilder_mode()
    {

        if (isset($_GET['action']) && $_GET['action'] == 'edit'){
            return  true;
        }
        return false;

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



}