<?php
/**
 * This class is responsible for compatibility with woocommerce HPOS
 * 
 */

namespace WPFunnelsPro\Compatibility;
use WPFunnels\Wpfnl_functions;

class WcHpos{


    /**
     * Get HPOS settings
     * 
     * @since 1.8.4
     * @return Bool
     */
    public function maybe_hpos_enable(){

        if( 'yes' === get_option( 'woocommerce_custom_orders_table_enabled', true ) ) {
            return true;
        }
        return false;
    }


    /**
     * Get all funnel orders from wc order table 
     * 
     * @param String $start_date 
     * @param String $end_date 
     * 
     * @since 1.8.4
     * @return Array
     */
    public function get_hpos_orders( $start_date, $end_date ){
        
        global $wpdb;

        $where  = '';
        $where .= "WHERE ( ( wpft2.meta_key = '%s' AND wpft2.meta_value = '%s' )";
        $where .= " AND wpft1.status IN ( 'wc-completed', 'wc-processing', 'wc-pending', 'wc-on-hold' )";
        $where .= " AND wpft1.type >= '%s' ";
        $where .= " AND ( wpft1.date_created_gmt >= '%s' AND wpft1.date_created_gmt <= '%s'))";

        $query = $wpdb->prepare(
            "SELECT wpft1.ID
            FROM {$wpdb->prefix}wc_orders as wpft1 INNER JOIN {$wpdb->prefix}wc_orders_meta as wpft2 ON wpft1.ID = wpft2.order_id
            {$where}
            ", '_wpfunnels_order', 'yes', 'shop_order', $start_date, $end_date );
        $orders = $wpdb->get_results( $query );
        if( is_array($orders) && !empty($orders) ){
            return $orders;
        }
        return [];
    }


    /**
     * Get all funnel orders from wc order table based on funnel id, start_date, end_date
     * 
     * @param String $funnel_id
     * @param String $start_date 
     * @param String $end_date 
     * 
     * @since 1.8.4
     * @return Array
     */
    public function get_hpos_orders_for_a_funnel( $funnel_id, $start_date, $end_date ){
        global $wpdb;
        
        $where  = '';
        $where .= "WHERE ( ( wpft2.meta_key = '%s' AND wpft2.meta_value = %s ) OR ( wpft2.meta_key = '%s' AND wpft2.meta_value = %s )";
        $where .= " AND wpft1.status IN ( 'wc-completed', 'wc-processing', 'wc-pending', 'wc-on-hold' )";
        $where .= " AND wpft1.type = '%s' ";
        $where .= " AND ( wpft1.date_created_gmt >= '%s' AND wpft1.date_created_gmt <= '%s'))";
        
        $query = $wpdb->prepare(
            "SELECT wpft1.ID
            FROM {$wpdb->prefix}wc_orders as wpft1 INNER JOIN {$wpdb->prefix}wc_orders_meta as wpft2 ON wpft1.ID = wpft2.order_id
            {$where}
            ",'_wpfunnels_funnel_id', $funnel_id, '_wpfunnels_parent_funnel_id', $funnel_id, 'shop_order', $start_date ,$end_date );
        $orders = $wpdb->get_results( $query );
        if( is_array($orders) && !empty($orders) ){
            return $orders;
        }
        return [];
    }
}