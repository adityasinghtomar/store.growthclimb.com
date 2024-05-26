<?php

namespace WPFunnelsPro\AbTesting;

use Error;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;
use function WPFunnels\Rest\Controllers\wpfnl_pro_analytics_get_param_type;

/**
 * Class Wpfnl_Ab_testing
 * @package WPFunnelsPro\AbTesting
 */
class Wpfnl_Ab_Testing{


    /**
     * Update ab testing status
     * @param Number $step_id
     * 
     * @return Bool
     * @since 1.6.21
     */
    public static function update_ab_testing_status( $step_id = '' , $value = '' ){
        if( $step_id && $value ){
            update_post_meta( $step_id, '_wpfnl_is_ab_testing' , $value );
            return true;
        }
        return false;  
    }


    /**
     * Check if A/B testing is enabled for a specific step.
     *
     * @param Number $step_id The ID of the step.
     *
     * @return Bool False if A/B testing is not enabled, true if enabled.
     * @since 1.6.21
     */
    public static function maybe_ab_testing( $step_id ) {
        if ( !$step_id ) {
            return false;
        }

        // Get the A/B testing start settings for the step.
        $settings = self::get_start_settings( $step_id );

        // Check if the settings are an array and if A/B testing is enabled.
        if ( !is_array( $settings ) || !isset( $settings['isStart'] ) || 'no' === $settings['isStart'] ) {
            return false;
        }
        return true;
    }


    /**
     * Get default start settings
     * 
     */
    public static function get_default_start_setting(  $step_id ){
        if( $step_id ){
            $step_edit_link =  get_edit_post_link($step_id);
            $step_permalink =  get_the_permalink($step_id);
            $step_title     =  get_the_title($step_id);
            if( 'elementor' ==  Wpfnl_functions::get_builder_type() ){
                $step_edit_link = str_replace('&amp;','&',$step_edit_link);
                $step_edit_link = str_replace('edit','elementor',$step_edit_link);
            }
            
            $default_settings = [
                'isStart'         => 'no',
                'startDate'       => date( 'Y-m-d H:i:s' ),
                'endDate'       => date( 'Y-m-d H:i:s' ),
                'variations'  => [
                    self::make_variation_array( $step_id, true ),
                ],
                'archived_variations' => [],
            
            ];
            return $default_settings;
        }
        return false;
    }


    public static function get_default_data( $step_id ) {
        $step_edit_link =  get_edit_post_link($step_id);
        $step_permalink =  get_the_permalink($step_id);
        $step_title     =  get_the_title($step_id);
        if( 'elementor' ==  Wpfnl_functions::get_builder_type() ){
            $step_edit_link = str_replace('&amp;','&',$step_edit_link);
            $step_edit_link = str_replace('edit','elementor',$step_edit_link);
        }
        
        $default_settings = [
            'isStart'         => 'yes',
            'startDate'       => date( 'Y-m-d H:i:s' ),
            'endDate'       => date( 'Y-m-d H:i:s' ),
            'variations'  => [
                [
                    self::make_variation_array( $step_id, true )
                ],
            ],
            'archived_variations' => [],
        
        ];
        return $default_settings;

    }

    /**
     * Update start settings of  A/B testing 
     * 
     * @param Number $step_id
     * @param Array $data
     * 
     * @return Bool
     * @since 1.6.21
     */
    public static function update_start_settings( $step_id, $data = [] ){
        if( $step_id && !empty($data) ){
            update_post_meta( $step_id, '_wpfnl_ab_testing_start_settings' , $data );
            return true;
        }
        return false;
    }


    /**
     * @desc Get start settings of  A/B testing
     * @since 1.6.21
     *
     * @param $step_id
     * @param $key
     * @return false|mixed
     */
    public static function get_start_settings( $step_id, $key = '' ) {
        if( $step_id ) {
            $response = get_post_meta( $step_id, '_wpfnl_ab_testing_start_settings', true );
            if( $response ) {
                if( $key && isset( $response[ $key ] ) ) {
                    return $response[ $key ];
                }
                return $response;
            }

        }
        return false;
    }


    /**
     * @desc Get statistics for A/B testing.
     *
     * @param $funnel_id
     * @param $step_id
     * @return array
     */
    public static function get_settings_with_stats( $step_id, $start_settings ) {

        if( !$step_id || empty($start_settings['variations'])) {
            return $start_settings;
        }
        $funnel_id = get_post_meta( $step_id, '_funnel_id', true );
        if( !$funnel_id ){
            return $start_settings;
        }

        global $wpdb;
        $analytics_table      = $wpdb->prefix . WPFNL_PRO_ANALYTICS_TABLE;
        $analytics_meta_table = $wpdb->prefix . WPFNL_PRO_ANALYTICS_META_TABLE;
        $variations = $start_settings['variations'];
        $variation_ids = array_column( $variations, 'stepId' );
        $str_variation_ids = implode( ', ', $variation_ids );
        $variation_ids  = explode( ', ', $str_variation_ids );
        if( isset($start_settings['isStart']) && 'yes' == $start_settings['isStart'] ){
            if( $variations ) {
                $query       = "SELECT wpfnlt1.step_id AS step_id, ";
                $query       .= "COUNT( wpfnlt1.id ) AS total_visits, ";
                $query       .= "COUNT( DISTINCT( CASE WHEN wpfnlt1.visitor_type = 'new' THEN wpfnlt1.id ELSE NULL END ) ) AS unique_visits, ";
                $query       .= "COUNT( CASE WHEN wpfnlt2.meta_key = 'conversion' AND wpfnlt2.meta_value = %s ";
                $query       .= "THEN wpfnlt1.step_id ELSE NULL END ) AS conversions ";
                $query       .= "FROM {$analytics_table} AS wpfnlt1 ";
                $query       .= "INNER JOIN {$analytics_meta_table} AS wpfnlt2 ";
                $query       .= "ON wpfnlt1.id = wpfnlt2.analytics_id ";
                $query       .= "WHERE wpfnlt1.step_id IN (%s) ";
                $query       .= "AND wpfnlt2.meta_key = %s ";
                $query       .= "AND wpfnlt1.date_created >= %s ";
                $query       .= "GROUP BY wpfnlt1.step_id";
                $query       = $wpdb->prepare( $query, 'yes', $str_variation_ids, 'conversion', isset($start_settings['startDate']) ? $start_settings['startDate'] : date('Y-m-d H:i:s') );
                $query       = str_replace( '(\'', '(', $query );
                $query       = str_replace( '\')', ')', $query );
                $visits_data = $wpdb->get_results( $query, ARRAY_A );
                if( !empty( $visits_data ) ) {
                    $funnel_type   = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );
                    $funnel_type   = !$funnel_type ? 'wc' : $funnel_type;
                    $controllerInstance = new \Wpfnl_Analytics_Factory();
                    $param_type    = $controllerInstance->build( ucfirst($funnel_type) );
                    $funnel_orders = $param_type->get_orders_by_funnel( $funnel_id, '', '' );

                    foreach( $visits_data as $data ) {
                        $variation_id  = isset( $data[ 'step_id' ] ) ? $data[ 'step_id' ] : '';
                        $total_visit   = isset( $data[ 'total_visits' ] ) ? $data[ 'total_visits' ] : '';
                        $conversion    = isset( $data[ 'conversions' ] ) ? $data[ 'conversions' ] : '';
                        $earnings      = $param_type->get_earnings( $funnel_id, $funnel_orders, '', '', 'step_revenue', $variation_id );
                        
                        $variation_key = array_search($variation_id, array_column($variations, 'stepId'));
                        if( false === $variation_key ){
                            continue;
                        }
                       
                        $start_settings['variations'][$variation_key]['conversion'] = self::calculate_conversion_rate( $total_visit, $conversion );
                        $start_settings['variations'][$variation_key]['revenue'] = isset( $earnings[ 'gross_sale_with_html' ] ) ? $earnings[ 'gross_sale_with_html' ] : '';
                        $start_settings['variations'][$variation_key]['currency'] = isset( $earnings[ 'currency' ] ) ? $earnings[ 'currency' ] : '';
                        $start_settings['variations'][$variation_key]['visit'] = $total_visit;

                    
                    }
                }
            }
        }
        


        if( $variation_ids ) {
            foreach( $variation_ids as $variation_id ) {
                $is_winner = '';
                $variation_key = array_search($variation_id, array_column($variations, 'id'));
                if( false !== $variation_key ){
                    $start_settings['variations'][$variation_key]['conversion'] = 0;
                    $start_settings['variations'][$variation_key]['revenue'] = 0;
                    $start_settings['variations'][$variation_key]['currency'] = '';
                    $start_settings['variations'][$variation_key]['visit'] = 0;

                }
            }
        }
       
        return $start_settings;
    }


    /**
     * @desc Get statistics for A/B testing.
     *
     * @param $funnel_id
     * @param $step_id
     * @return array
     */
    public static function get_stats_of_a_step( $funnel_id, $step_id ) {
        $stats = [
            'total_visit'     => 0,
            'unique_visits'   => 0,
            'conversion'      => 0,
        ];
        if( $step_id && $funnel_id ) {
            global $wpdb;
            $analytics_table      = $wpdb->prefix . WPFNL_PRO_ANALYTICS_TABLE;
            $analytics_meta_table = $wpdb->prefix . WPFNL_PRO_ANALYTICS_META_TABLE;
            $query       = "SELECT wpfnlt1.step_id AS step_id, ";
            $query       .= "COUNT( wpfnlt1.id ) AS total_visits, ";
            $query       .= "COUNT( DISTINCT( CASE WHEN wpfnlt1.visitor_type = 'new' THEN wpfnlt1.id ELSE NULL END ) ) AS unique_visits, ";
            $query       .= "COUNT( CASE WHEN wpfnlt2.meta_key = 'conversion' AND wpfnlt2.meta_value = %s ";
            $query       .= "THEN wpfnlt1.step_id ELSE NULL END ) AS conversions ";
            $query       .= "FROM {$analytics_table} AS wpfnlt1 ";
            $query       .= "INNER JOIN {$analytics_meta_table} AS wpfnlt2 ";
            $query       .= "ON wpfnlt1.id = wpfnlt2.analytics_id ";
            $query       .= "WHERE wpfnlt1.step_id = %d ";
            $query       .= "AND wpfnlt2.meta_key = %s ";
            $query       .= "GROUP BY wpfnlt1.step_id";
            $query       = $wpdb->prepare( $query, 'yes', $step_id, 'conversion' );
            $query       = str_replace( '(\'', '(', $query );
            $query       = str_replace( '\')', ')', $query );
            $visits_data = $wpdb->get_results( $query, ARRAY_A );
            
            if( !empty( $visits_data ) ) {
        
                foreach( $visits_data as $data ) {
                    $variation_id  = isset( $data[ 'step_id' ] ) ? $data[ 'step_id' ] : '';
                    $total_visit   = isset( $data[ 'total_visits' ] ) ? $data[ 'total_visits' ] : '';
                    $unique_visits   = isset( $data[ 'unique_visits' ] ) ? $data[ 'unique_visits' ] : '';
                    $conversion    = isset( $data[ 'conversions' ] ) ? $data[ 'conversions' ] : '';
                    
                    $stats = [
                        'total_visit'     => $total_visit,
                        'unique_visits'   => $unique_visits,
                        'conversion'      => $conversion,
                    ];
                }
            }
            
        }
       
        return $stats;
    }


    /**
     * @desc Calculate and get conversion rate
     *
     * @param $total_visit
     * @param $conversion
     * @return float|int
     */
    public static function calculate_conversion_rate( $total_visit, $conversion ) {
        return number_format((float)( $conversion * 100 ) / $total_visit, 2, '.', '');
    }



    /**
     * Declear winner
     * 
     * @param Number $step_id
     * @return Bool
     * 
     */
    public static function set_winner( $step_id, $variation_id = '' ){
        if( !$step_id ){
            return false;
        }
        $settings = get_post_meta( $step_id, '_wpfnl_ab_testing_start_settings' , true );
        $settings = maybe_unserialize($settings);
        if( !is_array($settings) || !isset($settings['variations']) ){
            return false;
        }
        
        foreach( $settings['variations'] as $key=>$variation ){
            if( intval($variation_id) === intval($variation['stepId']) ){
                $settings['variations'][$key]['isWinner'] = 'yes';
            }else{
                $settings['variations'][$key]['isWinner'] = 'no';
            }
        }
        
        update_post_meta( $step_id, '_wpfnl_ab_testing_start_settings' , $settings );
        return true;
    }


    /**
     * Get the variation ID of the winning variation.
     *
     * @param Number $step_id The ID of the step.
     *
     * @return Bool|string The variation ID of the winner or false if not found.
     * @since 1.6.17
     */
    public static function get_winner( $step_id ) {
        if ( !$step_id ) {
            return false;
        }

        // Get the A/B testing start settings for the step.
        $settings = self::get_start_settings( $step_id );
        if ( !$settings || !isset($settings['variations']) || !is_array( $settings['variations'] ) ) {
            return false;
        }

        // Find the index of the winning variation (where 'isWinner' is 'yes').
        $key = array_search( 'yes', array_column( $settings['variations'], 'isWinner' ) );

        // Check if a winner was found.
        if ( false === $key ) {
            return false;
        }

        // Return the variation ID of the winning variation.
        return $settings['variations'][$key]['stepId'];
    }


    /**
     * Get variation step ID based on traffic distribution.
     *
     * @param Array $all_variations An array containing all variations and their traffic distribution.
     * @param Int   $base_step_id    The base step ID in case no variation is selected.
     *
     * @return Int The chosen variation step ID.
     * @since 1.6.17
     */
    public static function get_displayable_variation_id( $all_variations, $base_step_id ) {
        // Generate a random number between 0 and 100.
        $rand = function_exists( 'mt_rand' ) ? mt_rand( 0, 100 ) : rand( 0, 100 );

        // Initialize a variable to measure traffic distribution.
        $measurement = 0;

        // Iterate through each variation to determine which one to display.
        foreach ( $all_variations as $variation ) {
            $traffic = intval( $variation['traffic'] );

            // Check if the random number falls within the current variation's traffic range.
            if ( ( $rand >= $measurement ) && ( $rand <= ( $measurement + $traffic ) ) ) {
                return $variation['stepId']; // Return the variation's step ID.
            }

            // Increment the measurement for the next iteration.
            $measurement += $traffic;
        }

        // If no variation was selected based on the random number, return the base step ID.
        return $base_step_id;
    }


    /**
     * Get all A/B testing variations for a specific step.
     *
     * @param Int $step_id The ID of the step.
     * @return Array An array containing all A/B testing variations for the step.
     * @since 1.6.17
     */
    public static function get_all_variations( $step_id ) {
        // Get the A/B testing start settings for the specified step.
        $start_setting = self::get_start_settings( $step_id );

        // Initialize an empty array to store variations.
        $variations = [];

        // Check if the 'variations' key is set in the start settings.
        if ( !isset( $start_setting['variations'] ) ) {
            return $variations;
        }

        // If variations exist, assign them to the $variations array.
        $variations = $start_setting['variations'];

        // Return the array of variations.
        return $variations;
    }


    /**
     * Create or update varient of a step
     * @param Int @step_id
     * @param Int @varient_id
     * 
     */
	public static function update_variations( $step_id, $varient_id  ){
		$start_setting = self::get_start_settings( $step_id );

        $variations = [];
        
		if( $start_setting ){
            $variation = self::make_variation_array( $varient_id, false );
            if( $variation ){
                array_push( $start_setting['variations'], $variation);
                update_post_meta( $step_id, '_wpfnl_ab_testing_start_settings', $start_setting );
                $variations =  $start_setting['variations'];
            }
            
        }else{

            $variation = self::make_variation_array( $varient_id, false );
            if( $variation ){
                $settings = self::get_default_start_setting($step_id);
                array_push( $settings['variations'], $variation);
            
                update_post_meta( $step_id, '_wpfnl_ab_testing_start_settings', $settings );
                update_post_meta( $step_id, '_wpfnl_is_ab_testing' , 'yes' );
                $variations =  $settings['variations'];
            }
        }
    
		return $variations;
	}


    /**
     * Make variation array
     * 
     * @param int $step_id
     * 
     * @return array
     * @since 2.0.0
     */
    public static function make_variation_array( $step_id, $is_default ){
        if( !$step_id ){
            return false;
        }

        $step_edit_link =  get_edit_post_link($step_id);
        if( 'elementor' ==  Wpfnl_functions::get_builder_type() ){
            $step_edit_link = str_replace('&amp;','&',$step_edit_link);
            $step_edit_link = str_replace('edit','elementor',$step_edit_link);
        }
        $variation =[
            'stepId'                => $step_id,
            'stepName'              => get_the_title($step_id),
            'stepType'              =>  get_post_meta($step_id,'_step_type',true),
            'variationType'         => $is_default ? 'original' : 'variant',
            'stepEditLink'   		=> $step_edit_link,
            'stepViewLink'   		=> get_the_permalink($step_id),
            'isTrafficSet'   		=> 'no',
            'isWinner'   		    => 'no',
            'isLocked'   		    => 'no',
            'traffic'       		=> $is_default ? 100 : 0,
            'conversion'       		=> 0,
            'visit'       			=> 0,
            'revenue'       		=> 0,
        ];
        return $variation;
    }
    
    
    /**
	 * Get all ab-testing variations for each step
	 * 
	 * @param Int
	 * @param Int
	 * @return Array
	 * @since 1.6.17
	 * 
	 */
	public static function get_start_date( $funnel_id, $step_id ){
		$steps = get_post_meta( $funnel_id, '_steps_order', true );
		$key = array_search($step_id, array_column($steps, 'id'));
		if( false !== $key ){
			if( isset($steps[$key]['ab_test_start_time']) ){
                $date = strtotime($steps[$key]['ab_test_start_time']);
                $saved_date = date('d/M/Y h:i:s', $date);
				return $saved_date;
			}
		}
		return [];
	}


    /**
	 * Get all ab-testing conditions for each step
	 * 
	 * @param Int
	 * @param Int
	 * @return Bool
	 * @since 1.6.17
	 * 
	 */
	public static function get_all_conditions( $step_id ){
		$start_setting = self::get_start_settings( $step_id );
        $conditions = [];
        
		if( isset($start_setting['auto_winner'] ) ){
            if( isset($start_setting['auto_winner']['is_enabled']) && 'yes' === $start_setting['auto_winner']['is_enabled'] ){
                $conditions = isset($start_setting['auto_winner']['conditions']) ? $start_setting['auto_winner']['conditions'] : [];
            }
        }
		return $conditions;
	}


    /**
     * match condition
     * 
     * @param Array
     * @param Int
     * @param Int
     * 
     * @since 1.6.17
     */
    public static function match_condition( $all_conditions, $funnel_id, $step_id ){
        if(!empty($all_conditions) ){
            // foreach( $all_conditions as $key=>$condition ){
                if( 'conversion' === $all_conditions['index'] ){
                    global $wpdb;
                    $table_name = $wpdb->prefix.'wpfnl_analytics_meta';
                    $results = $wpdb->get_results( "SELECT * FROM ".$table_name." WHERE funnel_id = ".$funnel_id." AND step_id = ".$step_id." AND meta_key = 'conversion' AND meta_value = 'no'");
                    if( count($results) == $all_conditions['value'] ){
                        return true;
                    }
                }
                elseif( 'trafiic' === $all_conditions['index'] ){
                    global $wpdb;
                    $table_name = $wpdb->prefix.'wpfnl_analytics_meta';
                    $results = $wpdb->get_results( "SELECT * FROM ".$table_name." WHERE funnel_id = ".$funnel_id." AND step_id = ".$step_id." AND meta_key = 'conversion'");
                    
                    if( count($results) >= $all_conditions['value'] ){
                        
                        return true;
                    }
                }
                elseif( 'date' === $all_conditions['index'] ){
                    $current_date = date('d/M/Y');
                    $date = strtotime($all_conditions['value']);
                    $saved_date = date('d/M/Y', $date);
                    if( $current_date == $saved_date ){
                        return true;
                    }
                }
                
            // }
        }
        return false;
    }

    /**
     * @desc Get all ab-testing archived step ids for each step
     *
     * @param $funnel_id
     * @param $step_id
     * @return array|mixed
     */
	public static function get_all_archived_variations( $funnel_id, $step_id ){
		$steps = get_post_meta( $funnel_id, '_steps_order', true );
		$key = array_search($step_id, array_column($steps, 'id'));
		if( false !== $key ){
			if( isset($steps[$key]['archived_variations']) ){
				return $steps[$key]['archived_variations'];
			}
		}
		return [];
	}


    /**
     * Set ab-testings cookie
     * 
     * @param Int
     * 
     * @since 1.6.17
     * @return void
     */
    public static function set_cookie( $funnel_id, $step_id, $show_variation_id ){

        $cookie_name = WPFNL_AB_TESTING_COOKIE_KEY . $step_id;
        $cookiepath  = self::get_cookiepath();
        $expire_time = time() + ( 24 * 60 * MINUTE_IN_SECONDS );
        $value       = $show_variation_id;
        setcookie( $cookie_name, $value, $expire_time, $cookiepath, COOKIE_DOMAIN );

    }


    /**
     * Get ab-testings cookie
     * 
     * @param Int
     * 
     * @since 1.6.17
     * @return Mix Int or boolean 
    */
    public static function get_cookie( $step_id ) {
        $cookie_name = WPFNL_AB_TESTING_COOKIE_KEY . $step_id;
        if ( isset( $_COOKIE[ $cookie_name ] ) ) {
            return intval( $_COOKIE[ $cookie_name ] );
        }
        return false;
    }


    /**
     * get wp cookie path
     *
     * @return string|string[]|null
     */
    public static function get_cookiepath() {

        return COOKIEPATH ? COOKIEPATH : '/';
    }


    /**
     * Get a displayable variation ID for redirection in A/B testing.
     *
     * @param Int $funnel_id The ID of the funnel.
     * @param Int $step_id   The ID of the step.
     *
     * @return Mixed The displayable variation ID or false.
     * @since 1.6.17
     */
    public static function get_ab_testing_variation_id( $funnel_id, $step_id ) {

        if( !$step_id ){
            return false;
        }

        $is_winner = self::get_winner( $step_id );

        if ( $is_winner ) {
            return $is_winner;
        }

        $is_enable = self::maybe_ab_testing( $step_id );

        if ( !$is_enable ) {
            return false;
        }

        $step_id_from_cookie = self::get_cookie( $step_id );
        if ( $step_id_from_cookie ) {
            return $step_id_from_cookie;
        }

        $all_variations = self::get_all_variations( $step_id );
        $displayable_variation_id = self::get_displayable_variation_id( $all_variations, $step_id );
        self::set_cookie( $funnel_id, $step_id, $displayable_variation_id );
        return $displayable_variation_id;
        
    }


    /**
     * Convert AB testing variation to pulish from archive
     * 
     * @param Int
     * @param Int
     * @param Int
     * 
     * @since 1.6.17
     */
    public static function archive_to_publish( $funnel_id, $step_id, $archived_step_id ){
        $archived_steps = self::get_all_archived_variations( $funnel_id, $step_id );
        $key = array_search($archived_step_id, array_column($archived_steps, 'id'));
        if( false !== $key ){
            
            $steps = get_post_meta( $funnel_id, '_steps_order', true );
            $step_key = array_search($step_id, array_column($steps, 'id'));
            if( false !== $step_key ){
                
                array_push($steps[$step_key]['variations'],$archived_steps[$key]);
                unset($archived_steps[$key]);
                $steps[$step_key]['archived_variations'] = $archived_steps;
                update_post_meta( $funnel_id, '_steps_order', $steps );
                
            }
        }
    }


    /**
     * Convert AB testing variation to pulish from archive
     * 
     * @param Int
     * @param Int
     * @param Int
     * 
     * @since 1.6.17
     */
    public static function publish_to_archive( $funnel_id, $step_id, $variation_step_id ){
        $variations = self::get_all_variations( $step_id );
        $key = array_search($variation_step_id, array_column($variations, 'id'));
        if( false !== $key ){
            unset($variations[$key]);
            $steps = get_post_meta( $funnel_id, '_steps_order', true );
            $step_key = array_search($step_id, array_column($steps, 'id'));
            if( false !== $step_key ){
                array_push($steps[$step_key]['archived_variations'],$variations[$key]);
                unset($variations[$key]);
                $steps[$step_key]['variations'] = $variations;
                update_post_meta( $funnel_id, '_steps_order', $steps );
            }
        }
    }


    /**
     * @desc Reset the stats for the current step
     * and all of its variations
     *
     * @param $step_id
     * @return bool
     */
    public static function reset_stats( $step_id ) {
        if( $step_id ){
            $ab_start_settings = self::get_start_settings( $step_id );
            if( isset( $ab_start_settings[ 'start_date' ] ) ) {
                $ab_start_settings[ 'start_date' ] = date( 'Y-m-d H:i:s' );
            }
            return self::update_start_settings( $step_id, $ab_start_settings );
        }
        return false;
    }

    /**
     * @desc Reset the stats for the current step
     * and all of its variations
     *
     * @param $step_id
     * @return bool
     */
    public static function reset_settings( $step_id ) {
        if( $step_id ){
            $ab_start_settings = self::get_start_settings( $step_id );
            if( isset( $ab_start_settings[ 'variations' ], $ab_start_settings['auto_winner'] ) ) {
                $count = count($ab_start_settings['variations']);
                $avg = floor(100/$count);
                $total = 0;
                $ab_start_settings['auto_winner'] =  [
                    'is_enabled' => '',
                    'conditions' => 
                    [
                        'index'  => 'trafiic',
                        'value'  =>   70
                    ]
                ];
                foreach( $ab_start_settings[ 'variations' ] as $key=>$variation ){
                    $total = $total + $avg;
                    $ab_start_settings['variations'][$key]['traffic'] = $avg;
                }
                if( $total != 100 ){
                    $ab_start_settings[ 'variations' ][0]['traffic'] = $ab_start_settings[ 'variations' ][0]['traffic'] + ( 100 - $total );
                }
            } 
            return self::update_start_settings( $step_id, $ab_start_settings );
        }
        return false;
    }


    /**
     * duplicate all meta key and values
     *
     *
     * @param $parent_id
     * @param $post_id
     * @param $step_type
     */
    public static function duplicate_ab_testing_meta( $parent_id, $post_id, $exclude_meta = array(), $raw = false ) {
        global $wpdb;
        $exclude_sql = '';
        if( !empty($exclude_meta) ) {
            $metas 			= implode("', '",$exclude_meta );
            $exclude_sql 	= "AND meta_key NOT IN ('".$metas."')";
        }
        $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE (post_id=$parent_id {$exclude_sql})");
        if (count($post_meta_infos)!=0) {
            $insert_sql_query   = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES";
            $sql_query_arr      = [];
            foreach ($post_meta_infos as $meta_info) {
                $meta_key = $meta_info->meta_key;

                if( $meta_key == '_wp_old_slug' ) continue;
                if( $meta_key == 'funnel_automation_data' ) continue;

                if ( $raw ) {
                    $meta_value = get_post_meta( $parent_id, $meta_key,true );
                    update_post_meta($post_id, $meta_key, $meta_value);
                }
                $sql_query_arr[] = $wpdb->prepare( '( %d, %s, %s )', $post_id, $meta_key, $meta_info->meta_value );
            }

            if (!$raw) {
                $insert_sql_query .= implode( ',', $sql_query_arr );
                $wpdb->query( $insert_sql_query );
            }
        }

    }

    /**
     * Get Formatted ab testing settings
     * @param Integer $step_id
     * @return Array
     * @since 1.7.1
     */
    public static function get_formatted_settings( $step_id ){
        if( isset( $step_id ) ){
            $default_settings = self::get_default_start_setting( $step_id );

            //check A/B testing is enable or not
            $result = self::maybe_ab_testing( $step_id );
            
            $default_settings['is_ab_enabled'] = $result ? $result : '';
           
            // get start settings
            $result = self::get_start_settings( $step_id );
            $default_settings['start_settings'] = $result ? $result : $default_settings['start_settings'];            
            $response['data'] = $default_settings;
        }else{
            $response['data'] = '';
        }
        return $response;
    }

    /**
     * Get parent step ID by variant ID from postmeta
     * @param Integrer $variant_id
     * @return Mix
     * @since 1.7.1
     */
    public static function get_parent_step_id( $variant_id ){
        if( $variant_id ){
            $step_id = get_post_meta( $variant_id, '_parent_step_id', true );
            return $step_id;
        }
        return false;
    }


    /**
     * Archive all variants
     * Archive all variants except winner and make winner step as original step
     * 
     * @param Int $step_id
     * @param Int $variant_id
     * 
     * @return Bool
     * @since 1.7.4
     */
    public static function archive_all_variant( $step_id, $winner_id ){
        if( !$step_id || !$winner_id ){
            return false;
        }
        $settings = self::get_start_settings( $step_id );     
        if( !$settings || !is_array($settings) || !isset($settings['variations']) ){
            return false;
        }   

        $archived_steps = [];
        $variations = [];
        foreach( $settings['variations'] as $key => $variation ){
            if( isset($variation['stepId']) && intval($winner_id) === intval($variation['stepId']) ){
                array_push($variations,$variation);
                continue;
            }
            array_push($archived_steps,$variation);
        }
        $settings['variations']             = $variations;
        $settings['archived_variations']    = $archived_steps;

        update_post_meta( $step_id, '_wpfnl_ab_testing_start_settings', $settings );
        return true;
    }


    /**
     * Archive single variant
     * 
     * @param Int $step_id
     * @param Int $variant_id
     * 
     * @return Mix
     * @since  1.7.5
     */
    public static function single_archive( $step_id, $variant_id, $start_date = '', $stats_data = []){
        if( $step_id && $variant_id ){
            $funnel_id = Wpfnl_functions::get_funnel_id_from_step( $step_id );
            $start_settings = self::get_start_settings( $step_id );
            $archived_variations = isset($start_settings['archived_variations']) ? $start_settings['archived_variations'] : [];
            if( $start_settings && $funnel_id ){
                $is_original = $step_id == $variant_id; //Check the parent step id is equal to winner id or not
                foreach( $start_settings['variations'] as $key => $variations ){

                    if( $variations['id'] == $variant_id ){
                        $archived_variation = $variations;
                        $archived_variation['start_date'] = date('d M,Y',strtotime($start_date));
                        $archived_variation['start_time'] = date('H:i A',strtotime($start_date));
                        $index = array_search($variations['id'], array_column($stats_data, 'variation_id'));
                        if( false !== $index ){
                            $archived_variation['conversion_rate'] = $stats_data[$index]['conversion_rate'];
                            $archived_variation['revenue']         = $stats_data[$index]['float_revenue'];
                            $archived_variation['currency']         = $stats_data[$index]['currency'];
                            $archived_variation['visit']         = $stats_data[$index]['total_visit'];
                            $archived_variation['conversion']         = $stats_data[$index]['conversion'];
                        }

                        $archived_variation['end_date'] = date( 'd M,Y' );
                        $archived_variation['end_time'] = date( 'H:i A' );
                        array_push( $archived_variations, $archived_variation );
                        unset($start_settings['variations'][$key]);
                    }
                }

                $formatted_variations = [];
                foreach ( $start_settings['variations'] as $key=> $variation ){
                    array_push( $formatted_variations, $variation );
                }
                $is_multiple = count($formatted_variations) > 1 ? true : false;
                $start_settings['variations'] = $formatted_variations;
                $response = [];
                if( $is_original ){
                    $maybe_original_variant = isset(  $start_settings['variations'][0]['id'] ) ? $start_settings['variations'][0]['id'] : '';
                    if( $maybe_original_variant ){
                        $response = self::update_funnel_data( $funnel_id, $step_id, $maybe_original_variant, $is_multiple );
                        $step_id = $maybe_original_variant;
                    }
                }else{
                    $response = self::update_funnel_data( $funnel_id, $step_id, $step_id, $is_multiple );
                }

                $start_settings['archived_variations'] = $archived_variations;
                update_post_meta( $step_id, '_wpfnl_ab_testing_start_settings', $start_settings );
                $ab_start_settings = Wpfnl_Ab_Testing::get_formatted_settings( $step_id );
                $result = [
                    'funnel_data'       =>   isset($response['funnel_data']) ? $response['funnel_data'] : [],  
                    'ab_start_settings' =>   $ab_start_settings,
                    'step_id'           =>   $step_id,
                    'node_id'           =>   isset($response['node_id']) ? $response['node_id'] : '',  
                    'node_data'         =>   isset($response['node_data']) ? $response['node_data'] : [],  
                    'is_multiple_variant' =>   $is_multiple ? 'yes' : 'no',  
                ];
                
                return $result;
            }
        }
        return false;
    }

    
    /**
     * Restore archive
     * 
     * @param Int $step_id
     * @param Int $variant_id
     * 
     * @return Bool
     * @since  1.7.5
     */
    public static function restore_archive_variant( $step_id, $variant_id, $is_permanent_delete = true ){
        if( $step_id && $variant_id ){
            $start_settings = self::get_start_settings( $step_id );
            if( isset( $start_settings['archived_variations'] ) ){
                foreach( $start_settings['archived_variations'] as $key => $variations ){
                    if( $variant_id == $variations['id'] ){
                        array_splice($start_settings['archived_variations'], $key, 1);
                    }
                }
                update_post_meta( $step_id, '_wpfnl_ab_testing_start_settings', $start_settings );

                if( !$is_permanent_delete ){
                    self::update_variations( $step_id, $variant_id );
                }
                return true;
            }  
        }
        return false;
    } 


    /**
     * Delete archive variant permanently
     * 
     * @param Int $step_id
     * @param Int $variant_id
     * 
     * @return Array
     * @since  1.7.5
     */
    public static function delete_archive( $step_id, $variant_id ){
        if( $step_id && $variant_id ){
            $response = self::restore_archive_variant( $step_id, $variant_id, true );
            if( $response ){
                wp_delete_post( $variant_id );
                return  true;
            }
        }
        return false;
    }


    /**
     * Modify ab testing start settings
     * 
     * @param Obj $parent_post
     * @param Int $variant_id
     * @param Array $start_settings
     * 
     * @return Array
     * @since 1.7.4
     */
    public static function modify_start_settings( $parent_post, $variant_id, $start_settings ){

        wp_update_post([
            "ID" 			=> $variant_id,
            "post_title" 	=> wp_strip_all_tags( $parent_post->post_title ),
            "post_name" 	=> $parent_post->post_name,
        ]);

        $step_edit_link =  get_edit_post_link($variant_id);
        if( 'elementor' ==  Wpfnl_functions::get_builder_type() ){
            $step_edit_link = str_replace('&amp;','&',$step_edit_link);
            $step_edit_link = str_replace('edit','elementor',$step_edit_link);
        }
        $variation_key = array_search($variant_id, array_column($start_settings['variations'], 'id'));
        
        if( false !== $variation_key ){
            $start_settings['variations'][$variation_key]['variationType'] = 'original';
            $start_settings['variations'][$variation_key]['stepName'] = wp_strip_all_tags( $parent_post->post_title );
            $start_settings['variations'][$variation_key]['stepViewLink'] = get_the_permalink($variant_id);
            $start_settings['variations'][$variation_key]['stepEditLink'] = $step_edit_link;
        }

        return $start_settings;
    }


    /**
     * Update winner variation as original step
     * @param Array $payload
     * @return Array 
     * @since 1.7.4
     */
    public static function update_winner_variation( $payload ){
        $funnel_id = isset($payload['funnel_id']) ? $payload['funnel_id'] : '';
        $step_id = isset($payload['step_id']) ? $payload['step_id'] : '';
        $variant_id = isset($payload['variant_id']) ? $payload['variant_id'] : '';
        $start_settings = isset($payload['start_settings']) ? $payload['start_settings'] : [];

        $parent_post = get_post( $step_id );
        $start_settings = self::modify_start_settings( $parent_post, $variant_id, $start_settings );
    
        if( $funnel_id ){
            $response = self::update_funnel_data( $funnel_id, $step_id, $variant_id );
            $response['start_settings'] = $start_settings;
            update_post_meta($variant_id, '_funnel_id', $funnel_id);
            delete_post_meta( $step_id, '_wpfnl_ab_testing_start_settings' );
            // wp_delete_post( $step_id );
        }
        return $response;
    }


    /**
     * Update funnel data
     * @param Int $funnel_id
     * @param Int $step_id
     * @param Int $variant_id
     * 
     * @return Array
     * @since 1.7.4
     */
    public static function update_funnel_data( $funnel_id, $step_id, $variant_id, $is_multiple = false ){
        $funnel_data = get_post_meta( $funnel_id, '_funnel_data', true );
        $steps = isset($funnel_data['drawflow']['Home']['data']) ? $funnel_data['drawflow']['Home']['data'] : [];
        $node_data = [];
        $node_id = '';
        if( is_array($steps) && count($steps) ){
            foreach( $steps as $key=>$step ){
                $step_data = isset($step['data']) ? $step['data'] : [];
                if( isset($step_data['step_id']) && $step_id == $step_data['step_id'] ){
                    $node_id = $step['id'];           
                    $steps[$key]['html'] = trim(str_replace($step_id, $variant_id, $steps[$key]['html'] ));
                    $step_data['step_id'] = $variant_id;
                    $step_data['step_edit_link'] = base64_encode( get_edit_post_link( $variant_id ) );
                    $step_data['step_view_link'] = base64_encode( rtrim( get_the_permalink( $variant_id ), '/' ) );
                    $steps[$key]['data'] = $step_data;

                    $node_data = [
                        'class' => $steps[$key]['class'],
                        'html'  => trim(str_replace($step_id, $variant_id, $steps[$key]['html'] )),
                        'pos_x' => $steps[$key]['pos_x'],
                        'pos_y' => $steps[$key]['pos_y'],
                        'data'  => [
                            'step_id'           => $variant_id,
                            'step_edit_link'    => base64_encode( get_edit_post_link( $variant_id ) ),
                            'step_view_link'    => base64_encode( rtrim( get_the_permalink( $variant_id ), '/' ) ),
                            'step_name'         => get_the_title( $variant_id ),
                            'step_type'         => $steps[$key]['data']['step_type'],
                        ],
                    ];
                }
            }
            $funnel_data['drawflow']['Home']['data'] =   $steps;
            update_post_meta( $funnel_id, '_funnel_data', $funnel_data );
        }  

        $step_order = get_post_meta( $funnel_id, '_steps_order', true );
        $step_order = self::update_step_order( $step_order, $funnel_id, $step_id, $variant_id );
        return [
            'funnel_data'   => $funnel_data,
            'step_order'    => $step_order,
            'node_data'     => $node_data,
            'node_id'       => $node_id,
        ];
    }


    /**
     * Update step order for funnel
     * 
     * @param Array $step_order
     * @param Int $funnel_id
     * @param Int $step_id
     * @param Int $variant_id
     * 
     * @return Array
     * @since 1.7.4
     */
    public static function update_step_order( $step_order, $funnel_id, $step_id, $variant_id ){
        
        if( $step_id != $variant_id ){
            if( is_array($step_order && !empty($step_order)) ){
                foreach($step_order as $key=>$step ){
                    if( $step['id'] == $step_id ){
                        $step_order[$key]['id'] = $variant_id;
                    }
                }

                delete_post_meta($step_id, '_funnel_id' );
                update_post_meta( $variant_id, '_funnel_id', $funnel_id );
                update_post_meta( $funnel_id, '_steps_order', $step_order );
            }
        }
        return $step_order;
    }

    /**
     * Update the status of AB testing (start/pause) for a step.
     *
     * @param int $step_id The ID of the step.
     *
     * @return array|bool An array indicating success and the updated status, or false on failure.
     *
     * @since 2.0.0
     */
    public static function update_running_status( $step_id ) {
        if ( ! $step_id ) {
            return false;
        }

        // Get the current AB testing start settings from post meta.
        $settings = get_post_meta( $step_id, '_wpfnl_ab_testing_start_settings', true );
        // Check if the settings are valid and include 'isStart' key.
        if ( ! $settings || ! is_array( $settings ) || ! isset( $settings['isStart'] ) ) {
            return false;
        }

        // Toggle the AB testing start status.
        $settings['isStart'] = 'yes' === $settings['isStart'] ? 'no' : 'yes';

        if( 'yes' === $settings['isStart'] ){
            $settings['startDate'] = date('Y-m-d H:i:s');
        }else{
            $settings['endDate'] = date('Y-m-d H:i:s');
        }

        // Update the post meta with the updated settings.
        update_post_meta( $step_id, '_wpfnl_ab_testing_start_settings', $settings );

        // Return an array indicating success and the updated status.
        return [
            'success' => true,
            'status'  => $settings['isStart'],
        ];
    }


    /**
     * Check if AB testing is currently running for a step.
     *
     * @param int $step_id The ID of the step.
     *
     * @return bool True if AB testing is running, false otherwise.
     *
     * @since 2.0.0
     */
    public static function maybe_ab_testing_running( $step_id ) {
        if ( ! $step_id ) {
            return false;
        }

        // Get the current AB testing start settings from post meta.
        $settings = get_post_meta( $step_id, '_wpfnl_ab_testing_start_settings', true );
        
        // Check if the settings are valid and include 'isStart' key.
        if ( ! $settings || ! is_array( $settings ) || ! isset( $settings['isStart'] ) ) {
            return false;
        }

        // Return true if AB testing is running ('isStart' is 'yes'), otherwise false.
        return 'yes' === $settings['isStart'];
    }


    /**
     * Migrate A/B Testing Start Settings.
     *
     * This function is responsible for migrating A/B testing start settings from an older format
     * to a newer format for a specified step. It takes a step ID as input and processes the
     * migration, updating the start settings to a new structure. This migration ensures compatibility
     * with new settings structures and features.
     *
     * @param int $step_id The ID of the step to migrate the A/B testing start settings for.
     *
     * @return bool True if the migration is successful, false otherwise.
     * @since 2.0.0
     */
    public static function maybe_migrate_start_settings( $step_id ) {
        if ( ! $step_id ) {
            return false;
        }

        $is_migrated = get_post_meta( $step_id, '_wpfnl_is_migrated_ab_settings', true );

        if ( 'yes' === $is_migrated ) {
            return false;
        }

        $prev_settings = get_post_meta( $step_id, '_wpfnl_ab_testing_start_settings', true );

        if ( ! is_array( $prev_settings ) || ! isset( $prev_settings['variations'], $prev_settings['is_started'] ) || ! is_array( $prev_settings['variations'] ) ) {
            update_post_meta( $step_id, '_wpfnl_is_migrated_ab_settings', 'yes' );
            return false;
        }

        $new_settings                           = [];
        $new_settings['isStart']                = !empty($prev_settings['is_started']) ? $prev_settings['is_started'] : 'no';
        $new_settings['startDate']              = isset( $prev_settings['start_date'] ) ? $prev_settings['start_date'] : date( 'Y-m-d H:i:s' );
        $new_settings['endDate']                = '';
        $new_settings['variations']             = [];
        $new_settings['archived_variations']    = [];

        foreach ( $prev_settings['variations'] as $key => $variation ) {
            if ( !isset( $variation['id'], $variation['variation_type'] ) ) {
                continue;
            }

            $type = 'original' === $variation['variation_type'] ? true : false;
            $updated_variation = self::make_variation_array( $variation['id'], $type );
            array_push( $new_settings['variations'], $updated_variation );
            
        }
       
        if( isset($prev_settings['archived_variations']) && is_array($prev_settings['archived_variations']) ){
            foreach ( $prev_settings['archived_variations'] as $key => $archived_variation ) {
                if ( !isset( $archived_variation['id'], $archived_variation['variation_type'] ) ) {
                    continue;
                }
    
                $type = 'original' === $archived_variation['variation_type'] ? true : false;
                $updated_variation = self::make_variation_array( $archived_variation['id'], $type );
                array_push( $new_settings['archived_variations'], $updated_variation );
            }
        }
        
        update_post_meta( $step_id, '_wpfnl_ab_testing_start_settings' , $new_settings );
        update_post_meta( $step_id, '_wpfnl_is_migrated_ab_settings', 'yes' );

        return true;
    }


    /**
     * Updates the variation link for A/B testing.
     *
     * @param array $settings The settings for the A/B testing.
     * @return array The updated settings.
     * 
     * @since 2.1.0
     */
    public static function update_variation_link( $settings ){
        if( isset($settings['variations']) && is_array($settings['variations']) ){
            foreach( $settings['variations'] as $key => $variation ){
                $step_edit_link =  get_edit_post_link($variation['stepId']);
                if( 'elementor' ==  Wpfnl_functions::get_builder_type() ){
                    $step_edit_link = str_replace('&amp;','&',$step_edit_link);
                    $step_edit_link = str_replace('edit','elementor',$step_edit_link);
                }
                $settings['variations'][$key]['stepEditLink'] = $step_edit_link;
                $settings['variations'][$key]['stepViewLink'] = get_the_permalink($variation['stepId']);
            }
        }
        
        if( isset($settings['archived_variations']) && is_array($settings['archived_variations']) ){
            foreach( $settings['archived_variations'] as $key => $variation ){
                $step_edit_link =  get_edit_post_link($variation['stepId']);
                if( 'elementor' ==  Wpfnl_functions::get_builder_type() ){
                    $step_edit_link = str_replace('&amp;','&',$step_edit_link);
                    $step_edit_link = str_replace('edit','elementor',$step_edit_link);
                }
                $settings['archived_variations'][$key]['stepEditLink'] = $step_edit_link;
                $settings['archived_variations'][$key]['stepViewLink'] = get_the_permalink($variation['stepId']);
            }
        }

        return $settings;
    }
    
}