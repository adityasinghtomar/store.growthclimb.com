<?php
namespace WPFunnelsPro\AnalyticsController;

use WPFunnels\Rest\AnalyticsController\TimeInterval;
use WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Compatibility\WcHpos;
use function cli\err;
class Wc {

    /**
     * get earning data matrix
     *
     * @param $orders
     * @return array
     * @since 1.0.0
     */
    public function get_earnings( $funnel_id, $orders, $start_date = '', $end_date ='', $all_data= '', $step_id = '',$step_type = '' ) {
        
        global $wpdb;
    
        $gross_sale                 = 0;
        $average_order_value        = 0;
        $total_order_bump_earnings  = 0;
        $order_count                = 0;
        $total_revenue              = 0;
        $revenue                    = 0;
        $offer_revenue              = 0;
        $total_offer_revenue        = 0;
        $upsell_revenue             = 0;
        $order_bump_earnings        = 0;
        global $wpdb;
        $counted_orders = [];
        if( !empty($orders) ) {
            foreach ( $orders as $order ) {
                
                if( !isset($order->ID) || (isset($order->ID) && in_array( $order->ID, $counted_orders )) ){
                    continue;
                }
                
                $counted_orders[] = $order->ID;
                // $funnel_id = Wpfnl_functions::get_funnel_id_from_order($order->ID) ? Wpfnl_functions::get_funnel_id_from_order($order->ID) : Wpfnl_functions::get_funnel_id_from_step($step_id);
                $rowCount = 0;
                if( $all_data == 'step_revenue'){
                    
                    $wpdb->get_results("
                        SELECT * FROM " . $wpdb->prefix . "wpfnl_analytics_meta
                        WHERE 
                        funnel_id       = '" . $funnel_id . "'
                        AND step_id	    = '" . $step_id . "'
                        AND meta_key	= 'wpfunnel_order_id'
                        AND meta_value	= '" . $order->ID . "'
                    "); 
                    $rowCount = $wpdb->num_rows;

                }elseif( $all_data == 'all_earning' || $all_data == 'intervals' ){
                    $wpdb->get_results("
                        SELECT * FROM " . $wpdb->prefix . "wpfnl_analytics_meta
                        WHERE 
                        funnel_id       = '" . $funnel_id . "'
                        AND meta_key	= 'wpfunnel_order_id'
                        AND meta_value	= '" . $order->ID . "'
                    "); 
                    $rowCount = $wpdb->num_rows;
                  
                }
                // $wpdb->get_results("
                //     SELECT * FROM " . $wpdb->prefix . "wpfnl_analytics_meta
                //     WHERE 
                //     funnel_id       = '" . $funnel_id . "'
                //     AND step_id	    = '" . $step_id . "'
                //     AND meta_key	= 'wpfunnel_order_id'
                //     AND meta_value	= '" . $order->ID . "'
                // "); 
                // $rowCount = $wpdb->num_rows;
                
                if( $rowCount == 0 ){
                    continue;
                }
                
                $order_count++;
                $order_id               = $order->ID;
                $order                  = wc_get_order( $order_id );
                $total_order            = $order->get_total();
          
                $general_settings = Wpfnl_functions::get_general_settings();
                

                $wpdb->get_results("
                    SELECT * FROM " . $wpdb->prefix . "wpfnl_analytics_meta
                    WHERE 
                    funnel_id       = '" . $funnel_id . "'
                    AND meta_key	= 'user_role'
                    AND meta_value	!= '" . $order->get_id() . "'
                "); 
                $rowCount = $wpdb->num_rows;
                
                
                if ( ! $order->has_status( 'cancelled' ) ) {
                    $gross_sale += (float) $total_order;
                }
                
                $order_bump_product_id  = $order->get_meta('_wpfunnels_order_bump_product');
                
                foreach ( $order->get_items() as $item_id => $item_data ) {
                   
                    $item_product_id = $item_data->get_product_id();
                    if ( $item_product_id == $order_bump_product_id ) {
                        $order_bump_earnings += $item_data->get_total() + $item_data->get_subtotal_tax();
                    }

                    if( 'checkout' === $step_type ){
                        $step = $order->get_meta('_wpfunnels_offer_'.$step_id);
                        if(empty($step)){
                            $total_revenue += $item_data->get_total() + $item_data->get_subtotal_tax();
                            if( 'yes' === $item_data->get_meta('_wpfunnels_upsell') || 'yes' === $item_data->get_meta('_wpfunnels_downsell') ){
                                $revenue += $item_data->get_total() + $item_data->get_subtotal_tax();
                            }
                            
                        }
                        
                    }
                    
                    if( 'upsell' === $step_type ){
                        
                        if( 'yes' === $item_data->get_meta('_wpfunnels_upsell') ){
                            $step = $order->get_meta('_wpfunnels_offer_'.$step_id);
                            if(!empty($step)){
                                $offer_revenue += $item_data->get_total() + $item_data->get_subtotal_tax();
                                $total_offer_revenue += $offer_revenue;
                              
                            }
                        }
                    }

                    if( 'downsell' === $step_type ){

                        if( 'yes' === $item_data->get_meta('_wpfunnels_downsell') ){

                            $step = $order->get_meta('_wpfunnels_offer_'.$step_id);
                            if(!empty($step)){
                                $offer_revenue += $item_data->get_total() + $item_data->get_subtotal_tax();
                                $total_offer_revenue += $offer_revenue;
                                
                            }  
                        }
                    }
                }  
                $total_revenue = $total_revenue + $order->get_shipping_total() + $order->get_shipping_tax();;
            }
          
            $total_order_bump_earnings = $order_bump_earnings;
            $gross_sale = $gross_sale + $total_offer_revenue;
            if ( 0 !== $order_count ) {
                $average_order_value = $gross_sale / $order_count;
            }
            
            
        }
        
        $revenue = $total_revenue-$revenue;
        
        
        $all_earnings = array(
            'order'                         => (int)$order_count,
            'gross_sale_with_html'          => wc_price(number_format( (float) $gross_sale, 2, '.', '' )),
            'gross_sale'                    => number_format( (float) $gross_sale, 2, '.', '' ),
            'order_bump_with_html'          => wc_price(number_format( (float) $total_order_bump_earnings, 2, '.', '' )),
            'order_bump'                    => number_format( (float) $total_order_bump_earnings, 2, '.', '' ),
            'avg_order_value_with_html'     => wc_price(number_format( (float) $average_order_value, 2, '.', '' )),
            'avg_order_value'               => number_format( (float) $average_order_value, 2, '.', '' ),
            'total_revenue'                 => number_format( (float) $total_revenue, 2, '.', '' ),
            'revenue'                       => number_format( (float) $revenue, 2, '.', '' ),
            'offer_revenue'                 => number_format( (float) $offer_revenue, 2, '.', '' ),
            'revenue_with_html'             => wc_price(number_format( (float) $revenue, 2, '.', '' )),
            'currency'                      => get_woocommerce_currency_symbol()
           
        );
        return $all_earnings;
    }


    /**
     * get orders from funnel id
     *
     * @param $funnel_id
     * @param \WP_REST_Request $request
     * @return array|object|null
     */
    public function get_orders_by_funnel( $funnel_id, $start_date, $end_date ) {
        global $wpdb;

        $start_date = date( 'Y-m-d H:i:s', strtotime( $start_date . '00:00:00' ) ); //phpcs:ignore
        $end_date   = date( 'Y-m-d H:i:s', strtotime( $end_date . '23:59:59' ) ); //phpcs:ignore
       
        $where = '';
        $where .= " WHERE ( (( wpft2.meta_key = '_wpfunnels_funnel_id' AND wpft2.meta_value = $funnel_id ) OR ( wpft2.meta_key = '_wpfunnels_parent_funnel_id' AND wpft2.meta_value = $funnel_id )) ";
        $where .= " AND wpft1.post_status IN ( 'wc-completed', 'wc-processing', 'wc-cancelled' ))";
        $where .= " AND wpft1.post_date >= '$start_date' ";
        $where .= " AND wpft1.post_date <= '$end_date' ";
        $query = 'SELECT wpft1.ID FROM ' . $wpdb->prefix . 'posts wpft1 
		INNER JOIN ' . $wpdb->prefix . 'postmeta wpft2
		ON wpft1.ID = wpft2.post_id 
		' . $where;
        
        return $wpdb->get_results( $query );
    }


    /**
     * get interval against start and end date
     *
     * @param $funnel_id
     * @param $start_date
     * @param $end_date
     * @param bool $db_interval
     * @return array|object|null
     */
    public function get_intervals( $funnel_id, $start_date, $end_date, $db_interval = false, $type='hour' ) {
        global $wpdb;

        // get total order within those dates from funnel
        $start_date = date( 'Y-m-d H:i:s', strtotime( $start_date . '00:00:00' ) ); //phpcs:ignore
        $end_date   = date( 'Y-m-d H:i:s', strtotime( $end_date . '23:59:59' ) ); //phpcs:ignore
        $where = '';
        $where .= " AND wpft1.post_date >= '$start_date' ";
        $where .= " AND wpft1.post_date <= '$end_date' ";
        $where .= " AND ( (( wpft2.meta_key = '_wpfunnels_funnel_id' AND wpft2.meta_value = $funnel_id ) OR ( wpft2.meta_key = '_wpfunnels_parent_funnel_id' AND wpft2.meta_value = $funnel_id ))";
        $where .= " AND wpft1.post_status IN ( 'wc-completed', 'wc-processing', 'wc-cancelled' ))";

        $group_by = '';
        $include_id = 'wpft1.ID,';
        $date_format = "'%Y-%m-%d'";
        if( $db_interval ) {
            $group_by   = ' GROUP BY time_interval';
            $include_id = '';
        }

        if( $type === 'hour' ) {
            $date_format = "'%Y-%m-%d %h'";
        }
        
        $query = 'SELECT '.$include_id.' DATE_FORMAT(wpft1.post_date, '.$date_format.') AS time_interval  FROM ' . $wpdb->prefix . 'posts wpft1 
		INNER JOIN ' . $wpdb->prefix . 'postmeta wpft2
		ON wpft1.ID = wpft2.post_id 
		' . $where . $group_by;

        $orders        = $wpdb->get_results($query);
        if( empty($orders) ){
            $wchpos_instance = new WcHpos();
            if( $wchpos_instance->maybe_hpos_enable() ){
                $where = '';
                $where .= " AND wpft1.date_created_gmt >= '$start_date' ";
                $where .= " AND wpft1.date_created_gmt <= '$end_date' ";
                $where .= " AND ( (( wpft2.meta_key = '_wpfunnels_funnel_id' AND wpft2.meta_value = $funnel_id ) OR ( wpft2.meta_key = '_wpfunnels_parent_funnel_id' AND wpft2.meta_value = $funnel_id ))";
                $where .= " AND wpft1.status IN ( 'wc-completed', 'wc-processing', 'wc-cancelled','wc-pending' ))";

                $group_by = '';
                $include_id = 'wpft1.ID,';
                $date_format = "'%Y-%m-%d'";
                if( $db_interval ) {
                    $group_by   = ' GROUP BY time_interval';
                    $include_id = '';
                }

                if( $type === 'hour' ) {
                    $date_format = "'%Y-%m-%d %h'";
                }
                
                $query = 'SELECT '.$include_id.' DATE_FORMAT(wpft1.date_created_gmt, '.$date_format.') AS time_interval  FROM ' . $wpdb->prefix . 'wc_orders wpft1 
                INNER JOIN ' . $wpdb->prefix . 'wc_orders_meta wpft2
                ON wpft1.ID = wpft2.order_id 
                ' . $where . $group_by;
                $orders        = $wpdb->get_results($query);
            }
        }
        $final_results  = array();
        $_temp_results  = array();
        if( $orders ) {
            foreach ($orders as $result) {
                if($db_interval) {
                    $final_results[] = $result->time_interval;
                } else {
                    $_temp_results[$result->time_interval][] = (object) array(
                        'ID'    => $result->ID,
                    );
                }
            }
        }
        
        if( $_temp_results ) {
            foreach ($_temp_results as $key => $temp_result) {
                $default = array(
                    'time_interval'   => '',
                    'gross_sale'      => 0,
                    'order_bump'      => 0,
                    'avg_order_value' => 0,
                    'all_steps'       => array(),
                );
                $totals                     = $this->get_earnings($funnel_id, $temp_result,$start_date, $end_date,'intervals','' );
               
                $totals['time_interval']    = $key;
                $totals                     = wp_parse_args( $totals, $default );
                $final_results[]            = $totals;
            }
        }

        return $final_results;
    }


    /**
     * Fills in interval gaps from DB with 0-filled objects.
     *
     * @param array    $db_intervals   Array of all intervals present in the db.
     * @param DateTime $start_datetime Start date.
     * @param DateTime $end_datetime   End date.
     * @param string   $time_interval  Time interval, e.g. day, week, month.
     * @param stdClass $data           Data with SQL extracted intervals.
     * @return stdClass
     */
    public function fill_in_missing_intervals( $db_intervals, $start_datetime, $end_datetime, $time_interval, &$data, $funnel_id ) {

        
        // @todo This is ugly and messy.
        $local_tz       = new \DateTimeZone( wc_timezone_string() );
        $time_ids       = array_flip( wp_list_pluck( $data->intervals, 'time_interval' ) );
        // At this point, we don't know when we can stop iterating, as the ordering can be based on any value.
        $db_intervals = array_flip( $db_intervals );
        // Totals object used to get all needed properties.
        $totals_arr = $data->earnings;
        foreach ( $totals_arr as $key => $val ) {
            $totals_arr[ $key ] = 0;
        }

        while ( $start_datetime <= $end_datetime ) {
            $next_start = TimeInterval::iterate( $start_datetime, $time_interval );
            $time_id    = TimeInterval::time_interval_id( $time_interval, $start_datetime );
            // Either create fill-zero interval or use data from db.
            if ( $next_start > $end_datetime ) {
                $interval_end = $end_datetime->format( 'Y-m-d H:i:s' );
            } else {
                $prev_end_timestamp = (int) $next_start->format( 'U' ) - 1;
                $prev_end           = new \DateTime();
                $prev_end->setTimestamp( $prev_end_timestamp );
                $prev_end->setTimezone( $local_tz );
                $interval_end = $prev_end->format( 'Y-m-d H:i:s' );
            }

            
            $start_date = $start_datetime->format( 'Y-m-d' );
            $year = date('Y',strtotime($start_date));
            $month = date('m',strtotime($start_date));
            $day = date('d',strtotime($start_date));
            $order_url = admin_url('edit.php?post_type=shop_order&m='.$year.$month.$day.'&id='.$funnel_id);

               
                
            if ( array_key_exists( $time_id, $time_ids ) ) {

                
                $record               = &$data->intervals[ $time_ids[ $time_id ] ];
                $record['date_start'] = $start_datetime->format( 'Y-m-d H:i:s' );
                $record['date_end']   = $interval_end;
                $record['order_url']   = $order_url;
                $record['steps_data'] = $this->get_steps_conversion_data( $funnel_id, $start_datetime->format('Y-m-d H:i:s'), $next_start->format('Y-m-d H:i:s') );
            } elseif ( ! array_key_exists( $time_id, $db_intervals ) ) {
                
                $record_arr                  = array();
                $record_arr['time_interval'] = $time_id;
                $record_arr['date_start']    = $start_datetime->format( 'Y-m-d H:i:s' );
                $record_arr['date_end']      = $interval_end;
                $record_arr['order_url']     = $order_url;
                $record_arr['steps_data']    = $this->get_steps_conversion_data( $funnel_id, $start_datetime->format('Y-m-d H:i:s'), $next_start->format('Y-m-d H:i:s') );
                $totals_arr['steps_data']    = $record_arr['steps_data'];
                $data->intervals[]           = array_merge( $record_arr, $totals_arr );
            }
            $start_datetime = $next_start;
        }
        return $data;
    }


    /**
     * @param $funnel_id
     * @param $start_date
     * @param $end_date
     * @return array
     */
    public function get_steps_conversion_data( $funnel_id, $start_date, $end_date ) {
        
        global $wpdb;
        $analytics_db       = $wpdb->prefix . WPFNL_PRO_ANALYTICS_TABLE;
       
        $analytics_meta_db  = $wpdb->prefix . WPFNL_PRO_ANALYTICS_META_TABLE;
        // get all steps
        $steps     = Wpfnl_functions::get_steps($funnel_id);
        $all_steps = array();
        $conversion         = [];
        $conversion_rate    = [];
        $revenue_array      = [];
        foreach ( $steps as $key => $step ) {
            $all_steps[] = $step['id'];
        }
        $step_ids   = implode( ', ', $all_steps );
        $analytics_columns  = array(
            'step_id'       => "wpft1.step_id",
            'total_visits'  => "COUNT( DISTINCT( wpft1.id ) ) AS total_visits",
            'unique_visits' => "COUNT( DISTINCT( CASE WHEN wpft1.visitor_type = 'new' THEN wpft1.id ELSE NULL END ) ) AS unique_visits",
            'conversion'    => "COUNT( CASE WHEN wpft2.meta_key = 'conversion' AND wpft2.meta_value = 'yes' AND wpft1.visitor_type = 'returning' THEN wpft1.step_id ELSE NULL END ) AS conversions ",
            'new_conversion'    => "COUNT( CASE WHEN wpft2.meta_key = 'conversion' AND wpft2.meta_value = 'yes' AND wpft1.visitor_type = 'new' THEN wpft1.step_id ELSE NULL END ) AS new_conversions ",
            'optin_submission'  => "COUNT( CASE WHEN wpft2.meta_key = 'wpfunnel_optin_submit' AND wpft2.meta_value = 'yes' THEN wpft1.step_id ELSE NULL END ) AS optin_submission ",
        );
        $where = '';
        $where .= " AND wpft1.funnel_id = %s ";
        $where .= " AND wpft1.date_created >= %s ";
        $where .= " AND wpft1.date_created <= %s ";
        $query = $wpdb->prepare(
            "SELECT {$analytics_columns['step_id']},
            {$analytics_columns['total_visits']},
            {$analytics_columns['unique_visits']},
            {$analytics_columns['conversion']},
            {$analytics_columns['new_conversion']},
            {$analytics_columns['optin_submission']}
            FROM $analytics_db as wpft1 INNER JOIN $analytics_meta_db as wpft2 ON wpft1.id = wpft2.analytics_id
            WHERE ( wpft1.step_id IN ( $step_ids ) )
            {$where}
            GROUP BY wpft1.step_id
            ORDER BY NULL
            ",$funnel_id,$start_date ,$end_date);


        $visits_data        = $wpdb->get_results( $query );
        
        
        $total_visit_data   = array();
        if( $steps ) {
            foreach ( $steps as $step ) {
                $step_type  = get_post_meta( $step['id'], '_step_type', true );
                $revenue    = 0;
                $offer_revenue    = 0;
                
                $variations = Wpfnl_Ab_Testing::get_all_variations( $step['id'] );
                if( is_array($variations) && count($variations) > 1 ){
                    $variations_revenue = 0.00;
                    $variations_offer_revenue = 0.00;
                    foreach( $variations as $variation ){
                        if( isset($variation['id']) ){
                            if( 'checkout' === $step_type || 'upsell' === $step_type || 'downsell' === $step_type ) {
                                $variations_rev = $this->get_step_revenue( $variation['id'], $step_type, $start_date, $end_date );
                                $variations_revenue = bcadd( $variations_revenue , floatval($variations_rev['revenue']), 2) ;
                                $variations_offer_revenue = bcadd( $variations_offer_revenue , floatval($variations_rev['offer_revenue']), 2) ;
                            }
                        }
                    }
                    
                    if( 'checkout' === $step_type || 'upsell' === $step_type || 'downsell' === $step_type ) {
                        $revenue = $variations_revenue;
                        $offer_revenue = $variations_offer_revenue;
                    }
                }else{
                    if( 'checkout' === $step_type || 'upsell' === $step_type || 'downsell' === $step_type ) {
                        $rev = $this->get_step_revenue( $step['id'], $step_type, $start_date, $end_date );
                        $revenue = $rev['revenue'];
                        $offer_revenue = $rev['offer_revenue'];
                    }
                }


                

                $conversion_rate = 0;
                
                if( $visits_data ) {
                    $key = array_search( $step['id'], array_column($visits_data, 'step_id') );
                    
                    if($key !== false){
                        if ( $visits_data[$key]->total_visits > 0 ) {
                            $conversion_rate = $visits_data[$key]->conversions / intval($visits_data[$key]->total_visits) * 100;
                        }
                        $total_visit_data[$step['id']] = array(
                            'step_id'           => $visits_data[$key]->step_id,
                            'step_name'         => $step['name'],
                            'step_type'         => $step['step_type'],
                            'total_visits'      => $visits_data[$key]->total_visits,
                            'returning_visits'  => $visits_data[$key]->total_visits-$visits_data[$key]->unique_visits,
                            'unique_visits'     => $visits_data[$key]->unique_visits,
                            'conversions'       => $visits_data[$key]->conversions,
                            'new_conversions'   => $visits_data[$key]->new_conversions,
                            'optin_submission'   => $visits_data[$key]->optin_submission,
                            'conversion_rate'   => $conversion_rate,
                            'revenue'           => $revenue,
                            'offer_revenue'  => $offer_revenue,
                        );
                    }
                    else{
                        $total_visit_data[$step['id']] = array(
                            'step_id'           => $step['id'],
                            'step_name'         => $step['name'],
                            'step_type'         => $step['step_type'],
                            'total_visits'      => 0,
                            'returning_visits'  => 0,
                            'unique_visits'     => 0,
                            'conversions'       => 0,
                            'new_conversions'   => 0,
                            'optin_submission'   => 0,
                            'conversion_rate'   => $conversion_rate,
                            'revenue'           => $revenue,
                            'offer_revenue'  => $offer_revenue,
                        );
                    }
                }
                else {
                    $total_visit_data[$step['id']] = array(
                        'step_id'           => $step['id'],
                        'step_name'         => $step['name'],
                        'step_type'         => $step['step_type'],
                        'total_visits'      => 0,
                        'returning_visits'  => 0,
                        'unique_visits'     => 0,
                        'conversions'       => 0,
                        'new_conversions'   => 0,
                        'optin_submission'  => 0,
                        'conversion_rate'   => $conversion_rate,
                        'revenue'           => $revenue,
                        'offer_revenue'     => $offer_revenue,
                    );
                }

            }
        }
        return $total_visit_data;
    }


    public function get_step_revenue( $step_id, $step_type, $start_date, $end_date ) {
        global $wpdb;
        $where      = '';
        $meta_key   = '_wpfunnels_checkout_id';
        switch ($step_type) {
            case 'checkout':
                $meta_key = '_wpfunnels_checkout_id';
                break;
            case 'upsell':
                $meta_key = '_wpfunnels_offer_'.$step_id;
                break;
            case 'downsell':
                $meta_key = '_wpfunnels_offer_'.$step_id;
                break;
        }
        
        $where .= "WHERE ( ( wpft2.meta_key = '%s' AND wpft2.meta_value = $step_id )";
        $where .= " AND wpft1.post_status IN ( 'wc-completed', 'wc-processing', 'wc-cancelled' ))";
        $where .= " AND wpft1.post_date >= '$start_date' ";
        $where .= " AND wpft1.post_date <= '$end_date' ";

        $query = $wpdb->prepare(
            "SELECT ID
            FROM {$wpdb->prefix}posts as wpft1 INNER JOIN {$wpdb->prefix}postmeta as wpft2 ON wpft1.ID = wpft2.post_id
            {$where}
            ",
            $meta_key);
        $orders = $wpdb->get_results( $query );
        if( empty($orders) ){
            $wchpos_instance = new WcHpos();
            if( $wchpos_instance->maybe_hpos_enable() ){
                $orders = $wchpos_instance->get_hpos_orders( $start_date, $end_date );
            }
        }
       
        
        $funnel_id = Wpfnl_functions::get_funnel_id_from_step($step_id);
        $earnings = $this->get_earnings($funnel_id,$orders,$start_date,$end_date, 'step_revenue', $step_id, $step_type);
        return $earnings;
    }


    /**
     * Reset analytics
     * 
     * @param $request
     * @return Array
     */
    public function reset_analytics_data( $request ){

        global $wpdb;
        $funnel_id          = $request['funnel_id'];
        $analytics_table    = 'wp_wpfnl_analytics';
        $analytics_meta     = 'wp_wpfnl_analytics_meta';
        $wpdb->delete( $analytics_table, array( 'funnel_id' => $funnel_id ) );
        $wpdb->delete( $analytics_meta, array( 'funnel_id' => $funnel_id ) );
        $response           = array(
            'success'   => true,
            'data'      => 'data reset successfully',
        );
        
        return $response;
    }
}