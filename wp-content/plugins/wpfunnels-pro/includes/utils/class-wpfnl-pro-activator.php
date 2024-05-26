<?php

/**
 * Fired during plugin activation
 *
 * @link       https://rextheme.com
 * @since      1.0.0
 *
 * @package    Wpfnl_Pro
 * @subpackage Wpfnl_Pro/includes
 */

use WPFunnelsPro\Db\Wpfnl_Pro_Db;
use WPFunnelsPro\Wpfnl_Pro_functions;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wpfnl_Pro
 * @subpackage Wpfnl_Pro/includes
 * @author     RexTheme <support@rextheme.com>
 */
class Wpfnl_Pro_Activator {


    protected static $wpfnl_pro_db;


    protected static $db_version = '1.0.0';

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        add_option('wpfunnels_pro_do_activation_redirect', true);

        if ( is_plugin_active('wpfunnels/wpfnl.php') ){
			// Wpfnl_Pro_functions::update_encrypt_key();
            self::$wpfnl_pro_db = new Wpfnl_Pro_Db( self::$db_version );
            self::$wpfnl_pro_db->create_wpfnl_pro_tables();

            // save security key
            $security_key = get_option( WPFNL_SECURITY_KEY, '');
            if(!isset($security_key)) update_option( WPFNL_SECURITY_KEY, md5( uniqid( wp_rand(), true ) ) );
            if (class_exists('ActionScheduler_StoreSchema') && class_exists('ActionScheduler_LoggerSchema')){
                $store_schema  = new ActionScheduler_StoreSchema();
                $logger_schema = new ActionScheduler_LoggerSchema();
                $store_schema->register_tables( true );
                $logger_schema->register_tables( true );
            }
        }
	}

}
