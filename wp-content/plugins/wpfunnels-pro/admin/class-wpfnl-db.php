<?php
namespace WPFunnelsPro\Db;

use WPFunnels\Traits\SingletonTrait;

/**
 * The database related functionality.
 *
 * @link       https://rextheme.com
 * @since      1.0.0
 *
 * @package    Wpfnl_Pro
 * @subpackage Wpfnl_Pro/db
 */

class Wpfnl_Pro_Db {


    use SingletonTrait;
	
	/**
	 * The database version
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $db_version;
	

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string
	 */
	public function __construct( $db_version ) {
		$this->db_version = $db_version;
	}

	
    /**
     * Creating Tables for WPFunnels
     */
    public function create_wpfnl_pro_tables() {

        if ( is_multisite() ) {
            global $wpdb;
            $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
            foreach ( $blog_ids as $blog_id ) {
                switch_to_blog( $blog_id );
                $this->create_analytics_table();
                $this->create_analytics_meta_table();
                update_option( 'wpfunnels_pro_tables_created', 'yes' );
                restore_current_blog();
            }
        } else {
            $this->create_analytics_table();
            $this->create_analytics_meta_table();
            update_option( 'wpfunnels_pro_tables_created', 'yes' );
        }
    }
	
    /**
     * WPFunnels Analytics TABLE
	 *
	 *@param NULL
	 *@return NULL
     */	
	public function create_analytics_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . "wpfnl_analytics";
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
				id BIGINT(20) NOT NULL AUTO_INCREMENT,
				funnel_id BIGINT(20),
				step_id BIGINT(20),
				user_id BIGINT(20) DEFAULT 0,
				user_ip VARCHAR(225) DEFAULT NULL,
				visitor_type ENUM( 'new','returning' ) NOT NULL DEFAULT 'new',
				analytics_data LONGTEXT NULL,
				date_created DATETIME DEFAULT '0000-00-00 00:00:00',
				date_created_gmt DATETIME DEFAULT '0000-00-00 00:00:00',
				date_modified DATETIME DEFAULT '0000-00-00 00:00:00',
				date_modified_gmt DATETIME DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY (id)
			  ) $charset_collate; ";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);
	}
	
    /**
     * WPFunnels Analytics Meta TABLE
	 *
	 *@param NULL
	 *@return NULL
     */		
	public function create_analytics_meta_table() {
		global $wpdb;

		$analytics_table        = $wpdb->prefix .WPFNL_PRO_ANALYTICS_TABLE;
        $analytics_meta_table   = $wpdb->prefix . WPFNL_PRO_ANALYTICS_META_TABLE;

		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS $analytics_meta_table (
				id BIGINT(20) NOT NULL AUTO_INCREMENT,
				analytics_id BIGINT(20),
				funnel_id BIGINT(20) DEFAULT 0,
				step_id BIGINT(20) DEFAULT 0,
				meta_key VARCHAR(255) NULL,
				meta_value LONGTEXT NULL,
				PRIMARY KEY (id),
				FOREIGN KEY (`analytics_id`) REFERENCES $analytics_table(`id`) ON DELETE CASCADE
			  ) $charset_collate; ";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);
	}
	
}
