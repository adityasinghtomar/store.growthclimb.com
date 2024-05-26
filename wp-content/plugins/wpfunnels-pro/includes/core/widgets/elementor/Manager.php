<?php

namespace WPFunnelsPro\Widgets\Elementor;

use Elementor\Plugin;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Widgets\Elementor;
use WPFunnels\Widgets\Elementor\Controls\Product_Control;




if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Main Elementor Test Extension Class
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 1.0.0
 */
final class Manager
{

    /**
     * Plugin Version
     *
     * @since 1.0.0
     *
     * @var string The plugin version.
     */
    const VERSION = '1.0.0';

    /**
     * Minimum Elementor Version
     *
     * @since 1.0.0
     *
     * @var string Minimum Elementor version required to run the plugin.
     */
    const MINIMUM_ELEMENTOR_VERSION = '2.0.0';

    /**
     * Minimum PHP Version
     *
     * @since 1.0.0
     *
     * @var string Minimum PHP version required to run the plugin.
     */
    const MINIMUM_PHP_VERSION = '7.0';

    /**
     * Instance
     *
     * @since 1.0.0
     *
     * @access private
     * @static
     *
     * @var Manager The single instance of the class.
     */
    private static $_instance = null;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @return Manager An instance of the class.
     * @since 1.0.0
     *
     * @access public
     * @static
     *
     */
    public static function instance()
    {

        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;

    }

    /**
     * Constructor
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function __construct()
    {
        if( $this->is_compatible() ) {
            if ( version_compare(ELEMENTOR_VERSION, '3.5.0', '>=') ) {
                add_action('elementor/widgets/register', [$this, 'init_widgets']);
            } else {
                add_action('elementor/widgets/widgets_registered', [$this, 'init_widgets']);
            }
            add_action('elementor/editor/before_enqueue_scripts', [$this, 'enqueue_elementor_custom_style']);
        }

    }


    /**
     * On Plugins Loaded
     *
     * Checks if Elementor has loaded, and performs some compatibility checks.
     * If All checks pass, inits the plugin.
     *
     * Fired by `plugins_loaded` action hook.
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function on_plugins_loaded()
    {
        if ($this->is_compatible()) {
            $this->init();
        }
    }


    /**
     * Add css file on  Elementor admin
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function enqueue_elementor_custom_style()
    {
        wp_enqueue_style('elementor-icon', WPFNL_URL . 'includes/core/widgets/elementor/assets/css/elemetor-icon-style.css');
    }

    /**
     * Compatibility Checks
     *
     * Checks if the installed version of Elementor meets the plugin's minimum requirement.
     * Checks if the installed PHP version meets the plugin's minimum requirement.
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function is_compatible()
    {

        // Check if Elementor installed and activated
        if (!did_action('elementor/loaded')) {
//            add_action('admin_notices', [$this, 'admin_notice_missing_main_plugin']);
            return false;
        }

        // Check for required Elementor version
        if (!version_compare(ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=')) {
//            add_action('admin_notices', [$this, 'admin_notice_minimum_elementor_version']);
            return false;
        }

        // Check for required PHP version
        if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '<')) {
//            add_action('admin_notices', [$this, 'admin_notice_minimum_php_version']);
            return false;
        }

        return true;

    }

    /**
     * Initialize the plugin
     *
     * Load the plugin only after Elementor (and other plugins) are loaded.
     * Load the files required to run the plugin.
     *
     * Fired by `plugins_loaded` action hook.
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function init()
    {
        if ( version_compare(ELEMENTOR_VERSION, '3.5.0', '>=') ) {
            add_action('elementor/widgets/register', [$this, 'init_widgets']);
        } else {
            add_action('elementor/widgets/widgets_registered', [$this, 'init_widgets']);
        }
    }


    /**
     * Register Category
     *
     * @since 1.0.0
     *
     * @access private
     */
    public function add_elementor_widget_categories($elements_manager)
    {


        $elements_manager->add_category(
            'wp-funnel',
            [
                'title' => __('WP FUnnels', 'wpfnl'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    /**
     * Init Widgets
     *
     * Include widgets files and register them
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function init_widgets()
    {
        if( wp_doing_ajax() ) {
            if( isset($_POST['step_id']) ) {
                $step_id = $_POST['step_id'];
            } elseif ( isset($_POST['editor_post_id'] ) ) {
                $step_id = $_POST['editor_post_id'];
            } else {
                $step_id = '';
            }

            if( !$step_id && isset($_POST['initial_document_id'] ) ) {
                $step_id = $_POST['initial_document_id'];
            }

        }elseif(wp_get_theme()->get('Name') == 'Woodmart'){
            $step_id = isset($_GET['post']) ? $_GET['post'] : get_the_ID();
        } else {
            $step_id = get_the_ID();
        }

        $step_type = get_post_meta($step_id, '_step_type', true);
        $funnel_id = get_post_meta($step_id,'_funnel_id',true);
        
        if (Wpfnl_functions::is_plugin_activated('woocommerce/woocommerce.php')) {
            if($step_type){
                if ($step_type == 'upsell' || $step_type == 'downsell') {
                    if ( version_compare(ELEMENTOR_VERSION, '3.5.0', '>=') ) {
                        Plugin::instance()->widgets_manager->register(new Offer_Button() );
                    } else {
                        Plugin::instance()->widgets_manager->register_widget_type(new Offer_Button() );
                    }
                }
            }elseif(wp_get_theme()->get('Name') == 'Woodmart'){
                if ( version_compare(ELEMENTOR_VERSION, '3.5.0', '>=') ) {
                    Plugin::instance()->widgets_manager->register(new Offer_Button() );
                } else {
                    Plugin::instance()->widgets_manager->register_widget_type(new Offer_Button() );
                }
            }else{
                if( !$step_id ){
                    $step_id = isset($_GET['post']) ? $_GET['post'] : get_the_ID();
                }

                $step_type = get_post_meta($step_id, '_step_type', true);
                $funnel_id = get_post_meta($step_id,'_funnel_id',true);
                
                if ($step_type == 'upsell' || $step_type == 'downsell') {
                    if ( version_compare(ELEMENTOR_VERSION, '3.5.0', '>=') ) {
                        Plugin::instance()->widgets_manager->register(new Offer_Button() );
                    } else {
                        Plugin::instance()->widgets_manager->register_widget_type(new Offer_Button() );
                    }
                }
            }
        }
        

        
    }


    /**
     * Init Controls
     *
     * Include controls files and register them
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function init_controls()
    {
        Plugin::$instance->controls_manager->register_control(\WPFunnels\Widgets\Elementor\Controls\Product_Control::ProductSelector, new Product_Control());
    }


    /**
     * Admin notice
     *
     * Warning when the site doesn't have Elementor installed or activated.
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function admin_notice_missing_main_plugin()
    {

        if (isset($_GET['activate'])) unset($_GET['activate']);

        $message = sprintf(
        /* translators: 1: Plugin name 2: Elementor */
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'elementor-test-extension'),
            '<strong>' . esc_html__('Elementor Test Extension', 'elementor-test-extension') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'elementor-test-extension') . '</strong>'
        );

        $settings = get_option('_wpfunnels_general_settings');

        if (isset($settings['builder']) && $settings['builder'] == 'elementor') {
            printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
        }

    }

    /**
     * Admin notice
     *
     * Warning when the site doesn't have a minimum required Elementor version.
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function admin_notice_minimum_elementor_version()
    {

        if (isset($_GET['activate'])) unset($_GET['activate']);

        $message = sprintf(
        /* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'elementor-test-extension'),
            '<strong>' . esc_html__('Elementor Test Extension', 'elementor-test-extension') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'elementor-test-extension') . '</strong>',
            self::MINIMUM_ELEMENTOR_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);

    }

    /**
     * Admin notice
     *
     * Warning when the site doesn't have a minimum required PHP version.
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function admin_notice_minimum_php_version()
    {

        if (isset($_GET['activate'])) unset($_GET['activate']);

        $message = sprintf(
        /* translators: 1: Plugin name 2: PHP 3: Required PHP version */
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'elementor-test-extension'),
            '<strong>' . esc_html__('Elementor Test Extension', 'elementor-test-extension') . '</strong>',
            '<strong>' . esc_html__('PHP', 'elementor-test-extension') . '</strong>',
            self::MINIMUM_PHP_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);

    }


    /**
     * Widget Registration manager
     *
     * @since 1.0.0
     *
     * @access private
     */
    public function widget_registration_manager($page_id)
    {
        return get_post_meta($page_id, '_step_type', true);
    }

    public function elementor_save_camps($post_id, $editor_data)
    {
        $products = array();
        foreach ($editor_data as $data) {
            foreach ($data['elements'] as $data) {
                foreach ($data['elements'] as $data) {
                    if ($data['widgetType'] == 'wpfnl-sell-accept') {
                        $product = $data['settings']['product'];
                    } elseif ($data['widgetType'] == 'wpfnl-order-detail') {
                        if (isset($data['settings']['enable_order_review'])) {
                            update_post_meta($post_id, '_wpfnl_thankyou_order_overview', 'off');
                        } else {
                            update_post_meta($post_id, '_wpfnl_thankyou_order_overview', 'on');
                        }

                        if (isset($data['settings']['enable_order_details'])) {
                            update_post_meta($post_id, '_wpfnl_thankyou_order_details', 'off');
                        } else {
                            update_post_meta($post_id, '_wpfnl_thankyou_order_details', 'on');
                        }

                        if (isset($data['settings']['enable_billing_details'])) {
                            update_post_meta($post_id, '_wpfnl_thankyou_billing_details', 'off');
                        } else {
                            update_post_meta($post_id, '_wpfnl_thankyou_billing_details', 'on');
                        }

                        if (isset($data['settings']['enable_shipping_details'])) {
                            update_post_meta($post_id, '_wpfnl_thankyou_shipping_details', 'off');
                        } else {
                            update_post_meta($post_id, '_wpfnl_thankyou_shipping_details', 'on');
                        }
                    } elseif ($data['widgetType'] == 'wpfnl-upsell-downsell') {
                        if (isset($data['settings']['upsell_downsell_selector']) && $data['settings']['upsell_downsell_selector'] == 'upsell') {
                            $organizer = $this->reorganize_funnel_order($post_id, $data['settings']['upsell_downsell_selector']);
                            update_post_meta($post_id, '_step_type', $data['settings']['upsell_downsell_selector']);
                            if (isset($data['settings']['upsell_accept_reject_selector']) && $data['settings']['upsell_accept_reject_selector'] == 'accept') {
                                if (isset($data['settings']['upsell_product_selector'])) {
                                    $product = $data['settings']['upsell_product_selector'];
                                }
                                if (isset($data['settings']['upsell_accept_next_step_selector'])) {
                                    update_post_meta($post_id, '_wpfnl_upsell_next_step_yes', $data['settings']['upsell_accept_next_step_selector']);
                                }
                            } else {
                                if (isset($data['settings']['upsell_reject_next_step_selector'])) {
                                    update_post_meta($post_id, '_wpfnl_upsell_next_step_no', $data['settings']['upsell_reject_next_step_selector']);
                                }
                            }
                        } elseif (isset($data['settings']['upsell_downsell_selector']) && $data['settings']['upsell_downsell_selector'] == 'downsell') {
                            $organizer = $this->reorganize_funnel_order($post_id, $data['settings']['upsell_downsell_selector']);
                            update_post_meta($post_id, '_step_type', $data['settings']['upsell_downsell_selector']);
                            if (isset($data['settings']['downsell_accept_reject_selector']) && $data['settings']['downsell_accept_reject_selector'] == 'accept') {
                                if (isset($data['settings']['downsell_product_selector'])) {
                                    $product = $data['settings']['downsell_product_selector'];
                                }
                                if (isset($data['settings']['downsell_accept_next_step_selector'])) {
                                    update_post_meta($post_id, '_wpfnl_downsell_next_step_yes', $data['settings']['downsell_accept_next_step_selector']);
                                }
                            } else {
                                if (isset($data['settings']['downsell_reject_next_step_selector'])) {
                                    update_post_meta($post_id, '_wpfnl_downsell_next_step_no', $data['settings']['downsell_reject_next_step_selector']);
                                }
                            }
                        }
                    }
                }
            }
        }
//        if ($product) {
//            update_post_meta(get_the_ID(), 'elementor_product_selector', $product);
//            $products[] = $product = array(
//                'id' => $product,
//                'quantity' => '1',
//            );
//
//            $get_type = get_post_meta($post_id, '_step_type', true);
//            if ($get_type == 'upsell') {
//                // update_post_meta($post_id, '_wpfnl_upsell_product', $products);
//            } elseif ($get_type == 'downsell') {
//                // update_post_meta($post_id, '_wpfnl_downsell_product', $products);
//            }
//        }
    }

    public function reorganize_funnel_order($step_id, $step_type)
    {
        $funnel_id = get_post_meta($step_id, '_funnel_id', true);
        $funnel_order = get_post_meta($funnel_id, '_steps_order', true);
        foreach ($funnel_order as $key => $data) {
            if ($data['id'] == $step_id) {
                $funnel_order[$key]['type'] = $step_type;
            }
        }
        update_post_meta($funnel_id, '_steps_order', $funnel_order);
    }
}

Manager::instance();
