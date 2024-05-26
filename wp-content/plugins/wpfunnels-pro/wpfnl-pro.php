<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://getwpfunnels.com
 * @since             1.0.0
 * @package           Wpfnl_Pro
 *
 * @wordpress-plugin
 * Plugin Name:       WPFunnels Pro
 * Plugin URI:        https://getwpfunnels.com
 * Description:       Get advanced WPFunnels features such as 🔥 one-click upsell 🔥, premium funnel templates, custom steps, detailed analytics, and many more.
 * Version:           2.2.1
 * Author:            WPFunnels Team
 * Author URI:        https://getwpfunnels.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpfnl-pro
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
use WPFunnelsPro\Wpfnl_Pro;
use WPFunnelsPro\Wpfnl_Pro_Dependency;
use WPFunnelsPro\Wpfnl_Pro_Updater;

if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WPFNL_PRO_VERSION', '2.2.1');

if ( ! defined( 'WPFNL_SECURITY_KEY' ) ) {
	define( 'WPFNL_SECURITY_KEY', get_option( '_wpfnl_security_key', '' ) );
}

define('WPFNL_PRO_FILE', __FILE__);
define('WPFNL_PRO_BASE', plugin_basename(WPFNL_PRO_FILE));
define('WPFNL_PRO_DIR', plugin_dir_path(WPFNL_PRO_FILE));
define('WPFNL_PRO_URL', plugins_url('/', WPFNL_PRO_FILE));
define('WPFNL_PRO_DIR_URL', plugin_dir_url(WPFNL_PRO_FILE));
define('WPFNL_PRO_DB_VERSION', '1.0');
define('WPFNL_PRO_ANALYTICS_TABLE', 'wpfnl_analytics');
define('WPFNL_PRO_ANALYTICS_META_TABLE', 'wpfnl_analytics_meta');
define('WPFNL', '/wpfunnels/wpfnl.php' );
define('WPFNL_REQUIRED_VERSION', '2.1.9' );
define('WPFNL_AB_TESTING_COOKIE_KEY', 'wpfnl_ab_testings_' );

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || !empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
define('WPFNL_PRO_INSTANCE', str_replace($protocol, "", get_bloginfo('wpurl')));
//define('WPFNL_PRO_INSTANCE', get_bloginfo('wpurl'));


//license middleman api url
define('WPFNL_PRO_LICENSE_URL', 'https://license.getwpfunnels.com/api/v1/licence');


//the url where the WooCommerce Software License plugin is being installed
define('WPFNL_PRO_API_URL', 'https://useraccount.getwpfunnels.com/');


//the Software Unique ID as defined within product admin page
define('WPFNL_PRO_PRODUCT_ID', 'wpf');



/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpfnl-pro-activator.php
 */
function activate_wpfnl_pro()
{
    require_once plugin_dir_path(__FILE__) . 'includes/utils/class-wpfnl-pro-activator.php';
    Wpfnl_Pro_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpfnl-pro-deactivator.php
 */
function deactivate_wpfnl_pro()
{
    require_once plugin_dir_path(__FILE__) . 'includes/utils/class-wpfnl-pro-deactivator.php';
    Wpfnl_Pro_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wpfnl_pro');
register_deactivation_hook(__FILE__, 'deactivate_wpfnl_pro');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wpfnl-pro.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpfnl_pro()
{
    /**
     * deactivate webhook addon if activated
     * @since 1.3.2
     */
    if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if( is_plugin_active( 'wpfunnels-pro-webhook/wpfunnels-pro-webhook.php' )){
        Wpfnl_Pro_Dependency::deactivate_self( 'wpfunnels-pro-webhook/wpfunnels-pro-webhook.php' );
    }

    $installed_plugins = get_plugins();
    $dependency        = new Wpfnl_Pro_Dependency('wpfunnels/wpfnl.php', WPFNL_PRO_FILE, '2.4.7', 'wpfnl-pro');

    if( !isset($installed_plugins['wpfunnels/wpfnl.php']) || !is_plugin_active( 'wpfunnels/wpfnl.php' ) ){
        $is_active = $dependency->is_active();
        
        if (isset($installed_plugins['wpfunnels/wpfnl.php']) && !empty($is_active)){
            // Plugin install but not active Do Nothing.
        }else {
            Wpfnl_Pro_Dependency::deactivate_self( 'wpfunnels-pro/wpfnl-pro.php' );
        }
    }

    $plugin = new Wpfnl_Pro();
    $plugin->run();
}

run_wpfnl_pro();


/**
 * redirect after plugin activation
 */
function wpfnl_pro_redirect() {
    if (get_option('wpfunnels_pro_do_activation_redirect', false)) {
        delete_option('wpfunnels_pro_do_activation_redirect');

        // On these pages, or during these events, postpone the redirect.
        $do_redirect = true;
        if ( wp_doing_ajax() || is_network_admin() ) {
            $do_redirect = false;
        }
        if( $do_redirect ) {
            wp_redirect("admin.php?page=wpf-license");
        }
    }
}
add_action('admin_init', 'wpfnl_pro_redirect');

function wpfnl_pro_run_updater() {
    new Wpfnl_Pro_Updater(WPFNL_PRO_API_URL, 'wpfunnels-pro', 'wpfunnels-pro/wpfnl-pro.php');
}

add_action('after_setup_theme', 'wpfnl_pro_run_updater');


function wpf_get_total_visit( $step_id ) {
    global $wpdb;
    $analytics_db       = $wpdb->prefix . WPFNL_PRO_ANALYTICS_TABLE;
    $analytics_meta_db  = $wpdb->prefix . WPFNL_PRO_ANALYTICS_META_TABLE;
    $analytics_columns  = array(
        'step_id'       => "wpft1.step_id",
        'total_visits'  => "COUNT( DISTINCT( wpft1.id ) ) AS total_visits",
        'conversion'    => "COUNT( CASE WHEN wpft2.meta_key = 'conversion' AND wpft2.meta_value = 'yes' THEN wpft1.step_id ELSE NULL END ) AS conversions ",
    );

    // calculate total visits
    $query = $wpdb->prepare(
        "SELECT {$analytics_columns['step_id']},
            {$analytics_columns['total_visits']}
            FROM $analytics_db as wpft1
            WHERE wpft1.step_id= %s
            ORDER BY NULL",$step_id
    );
    $visits_data        = $wpdb->get_row( $query );


    // calculate unique visit
    $query = $wpdb->prepare(
        "SELECT {$analytics_columns['step_id']},
            {$analytics_columns['conversion']}
            FROM $analytics_db as wpft1 INNER JOIN $analytics_meta_db as wpft2 ON wpft1.id = wpft2.analytics_id
            WHERE wpft1.step_id=%s
            ORDER BY NULL",$step_id
    );
    $conversion_data        = $wpdb->get_row( $query );

    $total = array(
        'visit'         => 0,
        'conversion'    => 0,
    );
    if($visits_data) {
        $total['visit'] = $visits_data->total_visits;
    }

    if($conversion_data) {
        $total['conversion'] = $conversion_data->conversions;
    }

    return $total;

}
function wpfunnels_step_data( $step, $step_id ) {
    $total = wpf_get_total_visit( $step_id );
    $step['visit']              = $total['visit'];
    $step['conversion']         = $total['conversion'];
    $controllers['automation']  = 'AutomationController';
    $controllers['mint']        = 'MintController';

    return $step;
}
add_filter( 'wpfunnels/step_data', 'wpfunnels_step_data', 10, 2 );


function wpfunnels_add_analytics_controller($controllers) {
    $controllers['analytics']    = 'AnalyticsController';
    $controllers['offer']        = 'OfferController';
    $controllers['automation']   = 'AutomationController';
    $controllers['webhook']      = 'WebhookController';
    $controllers['abtesting']    = 'AbTestingController';
    $controllers['mint']         = 'MintController';
    $controllers['importexport'] = 'ImportExportController';
    return $controllers;
}
add_filter('wpfunnels/rest_api_controllers', 'wpfunnels_add_analytics_controller');


function wpfunnel_order_query( $query ){
    global $pagenow;
    $type = 'shop_order';
    if (isset($_GET['post_type'])) {
        $type = $_GET['post_type'];
    }
    if ( 'shop_order' == $type && is_admin() && $pagenow=='edit.php' && isset($_GET['id'])) {

        $query->query_vars['meta_key'] = '_wpfunnels_funnel_id';
        $query->query_vars['meta_value'] = $_GET['id'];
    }
}
add_filter( 'parse_query', 'wpfunnel_order_query' );

