<?php

namespace WPFunnelsPro;

use Wpfnl_Pro_i18n;
use Wpfnl_Pro_Loader;
use WPFunnelsPro\Filters\Wpfnl_Pro_Hooks;
use WPFunnelsPro\Frontend\Modules\Analytics\Analytics;
use WPFunnelsPro\Frontend\Modules\Webhook\Wpfnl_Pro_Webhook;
use WPFunnelsPro\Frontend\Modules\Gateways\Payment_Gateways_Factory;
use WPFunnelsPro\Integration\Affiliate\Wpfnl_Pro_Integration_WPAffiliate;
use WPFunnelsPro\Integrations\CRM\CRM_Integrations;
use WPFunnelsPro\Modules\Wpfnl_Pro_Modules_Manager as Module_Manager;
use WPFunnelsPro\Frontend\Wpfnl_Pro_Public as Frontend;
use WPFunnelsPro\Notice\Notices;
use WPFunnelsPro\Offers\Offer_Subscription;
use WPFunnelsPro\Order\Wpfnl_Order_Refund;
use WPFunnelsPro\Orders\Orders;
use WPFunnelsPro\OrdersMeta\OrderMeta;
use WPFunnelsPro\Session\Wpfnl_Pro_Session;
use WPFunnelsPro\Shortcodes\Wpfnl_Pro_Shortcodes;
use WPFunnelsPro\Tracking\Pixel\Facebook_Pixel_Integration;
use WPFunnelsPro\Widgets\Wpfnl_Pro_Widgets_Manager as Widget_Manager;
use WPFunnelsPro\Admin\Modules\Steps\Checkout\CheckoutFields;
use WPFunnelsPro\Tracking\GTM;
use WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing_Hook;
use WPFunnelsPro\AbTesting\Backup_Ab_Testing_Hook;
use WPFunnelsPro\Mint\Wpfnl_Mint_Hook;
use WPFunnelsPro\Mint\Backup_Mint_Hook;
use WPFunnelsPro\Export\Wpfnl_Export;
use WPFunnelsPro\Import\Wpfnl_Import;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Admin\Wpfnl_Pro_Admin as Admin_Pro;


/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://rextheme.com
 * @since      1.0.0
 *
 * @package    Wpfnl_Pro
 * @subpackage Wpfnl_Pro/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wpfnl_Pro
 * @subpackage Wpfnl_Pro/includes
 * @author     RexTheme <support@rextheme.com>
 */
class Wpfnl_Pro
{
    /**
     * @var Wpfnl_Pro_Admin Instance.
     */
    public $admin;

    /**
     * Instance.
     *
     * Holds the plugin instance.
     *
     * @since 1.0.0
     * @access public
     * @static
     *
     * @var Wpfnl_Pro
     */
    public static $instance = null;

    /**
     * Holds the list of all registered modules
     *
     * @var Widget_Manager $widget_manager
     * @since 1.0.0
     */
    public $widget_manager;

    /**
     * Holds the list of all registered modules
     *
     * @var Module_Manager $module_manager
     * @since 1.0.0
     */
    public $module_manager;

    /**
     * Holds the list of all integrations
     *
     * @var integration_manager $integration_manager
     * @since 1.0.0
     */
    public $integration_manager;


    /**
     * factory class for payment gateways
     *
     * @var Payment_Gateways_Factory $payment_gateways
     * @since 1.0.0
     */
    public $payment_gateways;


    protected $license;

    /**
     *
     *
     * @var integration_manager $integration_manager
     * @since 1.0.1
     */
    public $checkout_fields;

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Wpfnl_Pro_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;
	
    /**
     * The database version.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string
     */
    protected $db_version;


    public $wpfnl_pro_db;
	
    public $session;


    /**
     * frontend module of funnel pro
     *
     * @var $frontend
     */
    public $frontend;


    public $analytics;

    public $webhook;


    public $order_meta;

    public $order_refund;

    /**
     * @var $orders
     */
    public $orders;


    public $notices;


    public $offer_subscription;

    /**
     * crm integrations
     *
     * @var $crm_integrations string
     * @access public
     */
    public $crm_integrations;

    public $gtm_integration;
    
    public $facebook_pixel;
    
    public $gtm;

    public $shortcodes;

    public $wp_affiliate;

    public $ab_testing;

    public $mint;

    public $export_funnel;

    public $import_funnel;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
		$this->version = '1.0.0';
		$this->db_version = '1.0.0';
		
        if (defined('WPFNL_PRO_VERSION')) {
            $this->version = WPFNL_PRO_VERSION;
        }
		
        if (defined('WPFNL_PRO_DB_VERSION')) {
            $this->db_version = WPFNL_PRO_DB_VERSION;
        }
        

        $this->plugin_name = 'wpfnl-pro';
        $this->load_dependencies();
        $this->set_locale();
        $this->initialize();
        add_action( 'plugins_loaded', array( $this, 'init' ), 100 );
    }



    public function initialize() {
        
        Wpfnl_Pro_Hooks::getInstance()->init();
    }


    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Wpfnl_Pro_Loader. Orchestrates the hooks of the plugin.
     * - Wpfnl_Pro_i18n. Defines internationalization functionality.
     * - Wpfnl_Pro_Admin. Defines all hooks for the admin area.
     * - Wpfnl_Pro_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for auto loading all files of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/utils/class-wpfnl-pro-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/utils/class-wpfnl-pro-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wpfnl-pro-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wpfnl-pro-public.php';

        $this->loader = new Wpfnl_Pro_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Wpfnl_Pro_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new Wpfnl_Pro_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
        add_filter( 'wpfunels/bricks_elements', array($this, 'add_bricks_elements'), 10 );
    }


    /**
     * Initialize the core of the plugin
     *
     * @since 1.0.0
     */
    public function init()
    {
        if ( ! did_action( 'wpfunnels/init' ) ) {
            return;
        }

        if ( ! version_compare( WPFNL_VERSION, WPFNL_REQUIRED_VERSION, '>=' ) ) {
            add_action( 'admin_notices', array( $this, 'may_be_failed_to_load_pro_plugin_notice' ) );
            return;
        }

        $this->admin                = new Admin_Pro( $this->get_plugin_name(), $this->get_version() );
        $this->frontend             = Frontend::getInstance();
        $this->module_manager       = new Module_Manager();
        $this->checkout_fields      = new CheckoutFields();
        $this->session              = new Wpfnl_Pro_Session();
        $this->payment_gateways     = Payment_Gateways_Factory::getInstance();
        $this->analytics            = Analytics::getInstance();
        $this->webhook              = Wpfnl_Pro_Webhook::getInstance();
        $this->license              = Wpfnl_Pro_Licensing::getInstance();
        $this->widget_manager       = Widget_Manager::getInstance()->init();
        $this->order_meta           = OrderMeta::getInstance();
        $this->order_refund         = Wpfnl_Order_Refund::getInstance();
        $this->orders               = Orders::getInstance();
        $this->notices              = Notices::getInstance();
        $this->offer_subscription   = Offer_Subscription::getInstance();
        $this->gtm                  = GTM::getInstance()->init_actions();
        $this->facebook_pixel       = Facebook_Pixel_Integration::getInstance()->init_actions();
        $this->shortcodes		    = Wpfnl_Pro_Shortcodes::getInstance()->init();
        $this->wp_affiliate		    = Wpfnl_Pro_Integration_WPAffiliate::getInstance();
        if ( !version_compare( WPFNL_VERSION, '3.0.0', '>=' ) ) {
            $this->ab_testing           = Backup_Ab_Testing_Hook::getInstance()->init();
        }else{
          
            $this->ab_testing           = Wpfnl_Ab_Testing_Hook::getInstance()->init();
        }
        
        $instance = new Wpfnl_functions();
        if( method_exists($instance,'is_mint_mrm_active') && Wpfnl_functions::is_mint_mrm_active() ){
            if ( !version_compare( WPFNL_VERSION, '3.0.0', '>=' ) ) {
                $this->mint             = Backup_Mint_Hook::getInstance()->init();
            }else{
                $this->mint             = Wpfnl_Mint_Hook::getInstance()->init();
            }
           
        }

        $this->export_funnel        = Wpfnl_Export::getInstance()->init_ajax();
        $this->import_funnel        = new Wpfnl_Import();

        $this->frontend->set_name($this->get_plugin_name());
        $this->frontend->set_version($this->get_version());
        $this->checkout_fields->init();
        do_action( 'wpfunnels/pro_init' );
    }


    /**
     * fires admin notice if
     * WPF version is not up to date
     *
     * @since 1.2.0
     */
    public function may_be_failed_to_load_pro_plugin_notice() {
        if ( ! current_user_can( 'update_plugins' ) ) {
            return;
        }
        $class 		= 'notice notice-error';
        $message    = __("It appears you have an older version of <strong>WPFunnels (Basic) </strong>. This may cause some issues with the plugin's functionality. Please update <strong>WPFunnels (Basic)</strong> to v". WPFNL_REQUIRED_VERSION ." and above.",'wpfnl-pro');
        printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
    }


    public function run_plugin_updater() {
        new Wpfnl_Pro_Updater(WPFNL_PRO_API_URL, 'wpfunnels-pro', 'wpfunnels-pro/wpfnl-pro.php');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Wpfnl_Pro_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version()
    {
        return $this->version;
    }
	
    /**
     * Get the Database Version of the plugin.
     *
     * @return    string
     * @since     1.0.0
     */
    public function get_db_version()
    {
        return $this->db_version;
    }

    /**
     * Instance.
     *
     * Ensures only one instance of the plugin class is loaded or can be loaded.
     *
     * @return Wpfnl_Pro An instance of the class.
     * @since 1.0.0
     * @access public
     * @static
     *
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * Adds bricks elements to the existing elements array.
     *
     * @param array $elements The array of elements to add bricks elements to.
     * 
     * @since 2.1.0
     */
    public function add_bricks_elements( $elements ){
        $element = [
            [
                'file'  => WPFNL_PRO_DIR. 'includes/core/widgets/bricks/OfferButton.php',
                'class' => '\WPFunnelsPro\Widgets\Bricks\OfferButton',
            ],
        ];
        $elements = array_merge($elements, $element);
        return $elements;
    }
}


Wpfnl_Pro::instance();



if ( ! function_exists( '_is_wpfunnels_installed' ) ) {

    /**
     * check if wpfunnels free plugin is installed
     *
     * @return bool
     */
    function _is_wpfunnels_installed() {

        $path    = 'wpfunnels/wpfnl.php';
        $plugins = get_plugins();

        return isset( $plugins[ $path ] );
    }
}