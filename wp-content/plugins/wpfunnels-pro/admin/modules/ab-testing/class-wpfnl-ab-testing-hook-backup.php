<?php

namespace WPFunnelsPro\AbTesting;

use Error;

use WPFunnelsPro\Wpfnl_Pro_functions;
use WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;
use WPFunnelsPro\AbTesting\Backup_Ab_Testing;
use WPFunnels\Wpfnl;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

use function WPFunnels\Rest\Controllers\wpfnl_pro_analytics_get_param_type;


/**
 * Class Wpfnl_Ab_Testing_Hook
 * @package WPFunnelsPro\AbTesting
 */
class Backup_Ab_Testing_Hook{

    use SingletonTrait;

    /**
     * Initialize all hooks
     */
    public function init()
    {
        add_action( 'wpfunnels/update_ab_testing_settings', [$this,'update_ab_testing_settings'], 10, 2 );
        add_action( 'wpfunnels/before_update_step_meta', [$this,'update_step_meta'], 10, 3 );
        add_action( 'wpfunnels/before_update_step_meta_on_funnel_name_change', [$this,'update_step_meta_on_funnel_name_change'], 10, 3 );
        add_action( 'wpfunnels/update_ab_testing_start_settings', [$this, 'update_ab_testing_start_settings'], 10 );
        add_filter( 'wpfunnels/modify_import_variant_response', [$this, 'modify_import_variant_response'], 10, 2 );
        add_filter( 'wpfunnels/modify_funnel_data', [$this, 'modify_funnel_data'], 10 );
        add_filter( 'wpfunnels/update_ab_testing_winner', [$this, 'update_ab_testing_winner'], 10, 2 );
        add_filter( 'wpfunnels/modify_funnel_view_link', [$this, 'modify_funnel_view_link'], 10, 3 );
        add_filter( 'wpfunnels/update_funnel_data_response', [$this, 'update_funnel_data_response'], 10 );
        add_filter( 'wpfunnels/update_funnel_link', [$this, 'update_funnel_link'], 10 );
    }

    /**
     * Update ab testing settings
     * @param Int $step_id
     * @param Int $vaiant_id
     * 
     * @return void
     * @since 1.7.1
     */
    public function update_ab_testing_settings( $step_id, $variant_id ){

        Backup_Ab_Testing::update_variations( $step_id, $variant_id );
        update_post_meta( $variant_id, '_parent_step_id', $step_id );
		Backup_Ab_Testing::update_ab_testing_status( $step_id, 'yes');
    }


    /**
     * Update ab testing start settings
     * @param Int $step_id
     * 
     * @return void
     * @since 1.7.1
     */
    public function update_ab_testing_start_settings( $step_id ){
        update_post_meta( $step_id, '_wpfnl_ab_testing_start_settings' , Backup_Ab_Testing::get_default_data( $step_id ));
    }

    /**
     * Modify import variant response
     * @param Array $response
     * @param Int $step_id
     * 
     * @return Array
     * @since 1.7.1
     */
    public function modify_import_variant_response( $response, $step_id ){
        $response['abTestingSettingsData'] = Backup_Ab_Testing::get_formatted_settings( $step_id );
        return $response;
    }

    /**
     * Modify funnel data
     * @param Array $response
     * @param Int $step_id
     * 
     * @return Array
     * @since 1.7.1
     */
    public function modify_funnel_data( $step_data ){
        
        if( is_array($step_data) ){
            foreach( $step_data as $key=>$step){
                $node_data 			= isset($step['data']) ? $step['data'] : [];
                if( isset($node_data['step_id']) ){
                    $ab_testings_settings = Backup_Ab_Testing::get_formatted_settings( $node_data['step_id'] );
                    $class = $step['class'];
                    $triggers   = Wpfnl_functions::get_mint_triggers();
                    $actions    = Wpfnl_functions::get_mint_actions();
                    if( in_array( $node_data['step_type'], $triggers ) ){
                        $class = $class.' mint-trigger-action mint-trigger';
                        $step_data[$key]['class'] = $class;
                    }
                    if( in_array( $node_data['step_type'], $actions ) ){
                        $class = $class.' mint-trigger-action mint-action';
                        $step_data[$key]['class'] = $class;
                    }
                    
                    if( isset($ab_testings_settings['data']['start_settings']['variations']) && count($ab_testings_settings['data']['start_settings']['variations']) > 1 ) {
                        
                        $needle   = 'has-ab-variation';
                        if (strpos($class, $needle) === false) {
                            $step_data[$key]['class'] = $class.' has-ab-variation';
                        }
                    }else{
                        $needle   = 'has-ab-variation';
                        if (strpos($class, $needle) !== false) {
                            $step_data[$key]['class'] = trim(str_replace('has-ab-variation','',$class));
                        }
                    }
                    
                }
            }
        }
        return $step_data;
    }

    /**
     * set ab testing winner
     * @param Int $funnel_id
     * @param Int $step_id
     * 
     * @return void
     * @since 1.7.1
     */
    public function update_ab_testing_winner( $funnel_id, $step_id ){
        $parent_step_id = Backup_Ab_Testing::get_parent_step_id( $step_id ) ? Backup_Ab_Testing::get_parent_step_id( $step_id ) : $step_id;
        $is_enable = Backup_Ab_Testing::maybe_ab_testing( $parent_step_id );
        if( $is_enable ){
           
            $all_conditions = Backup_Ab_Testing::get_all_conditions( $parent_step_id );
            $is_matched = Backup_Ab_Testing::match_condition( $all_conditions, $funnel_id, $parent_step_id );
            $is_winner = Backup_Ab_Testing::get_winner( $parent_step_id );
     
            if( !$is_winner && $is_matched ){
                Backup_Ab_Testing::set_winner( $parent_step_id, $step_id );
                
                // stop AB testing after winner matched
                $data = get_post_meta( $parent_step_id, '_wpfnl_ab_testing_start_settings' , true );
                $data['start_date'] = date( 'Y-m-d H:i:s' );
                $data['is_started'] = '';
                Backup_Ab_Testing::update_start_settings( $parent_step_id, $data );
            }
        }
        
    }

    /**
     * Update funnel view link when ab testings is activated
     * @param String $link
     * @param Int $step_id
     * @param Int $funnel_id
     */
    public function modify_funnel_view_link( $link, $step_id, $funnel_id ){

        if (class_exists('\WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing')) {
            $instance = new \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;
            $function_exist = is_callable(array($instance, 'maybe_ab_testing'));
            if( $function_exist ){
                $is_enabled = \WPFunnelsPro\AbTesting\Backup_Ab_Testing::maybe_ab_testing( $step_id );
                $variations = \WPFunnelsPro\AbTesting\Backup_Ab_Testing::get_all_variations( $step_id );
                if( 'yes' == $is_enabled && count($variations) > 1 ){
                    $link = get_the_permalink($funnel_id).'?wpfnl-step-id='.$step_id;
                }
            }
        }
        return $link;
    }


    /**
     * Update the meta information for a step in a funnel.
     *
     * This function updates the meta information, such as the post title, post name (slug),
     * step title, and step view link for a specific step in a funnel. It utilizes the
     * Wpfnl_Ab_Testing class from the WPFunnelsPro\AbTesting namespace to handle the update.
     *
     * @param int    $step_id    The ID of the step to update.
     * @param int    $funnel_id  The ID of the funnel to which the step belongs.
     * @param array  $settings   An array of settings containing the title and slug for the step.
     * @return void
     * @since 1.7.1
     */
    public function update_step_meta( $step_id, $funnel_id, $settings ){
        $instance = new \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;
        $function_exist = is_callable(array($instance, 'get_formatted_settings'));
        
        if( $function_exist ){
            $parent_step_id = \WPFunnelsPro\AbTesting\Backup_Ab_Testing::get_parent_step_id( $step_id );
            $parent_step_id = $parent_step_id ? $parent_step_id : $step_id;
            $ab_settings = \WPFunnelsPro\AbTesting\Backup_Ab_Testing::get_formatted_settings( $parent_step_id );
            
            if( is_array($ab_settings) && isset($ab_settings['data']['start_settings']['variations']) ){
                update_post_meta( $step_id, '_wpf_step_title', $settings['title'] );
                update_post_meta( $step_id, '_wpf_step_slug', $settings['slug'] );
                
                wp_update_post([
                    "ID" 			=> $step_id,
                    "post_title" 	=> wp_strip_all_tags( $settings['title'] ),
                    "post_name" 	=> sanitize_title($settings['slug']),
                ]);
                foreach( $ab_settings['data']['start_settings']['variations'] as $key=>$variation ){
                    if( $variation['id'] == $step_id ){
                        $ab_settings['data']['start_settings']['variations'][$key]['step_title'] 	 = htmlspecialchars_decode(get_the_title($step_id));
                        $ab_settings['data']['start_settings']['variations'][$key]['step_view_link'] = rtrim( get_the_permalink($step_id), '/' );
                    }
                }
                $ab_settings = $ab_settings['data']['start_settings'];
                \WPFunnelsPro\AbTesting\Backup_Ab_Testing::update_start_settings( $parent_step_id, $ab_settings );
            }
        }
    }


    /**
     * Update the meta information for a step in a funnel on funnel name change.
     *
     *
     * @param int    $step_id    The ID of the step to update.
     * @param int    $funnel_id  The ID of the funnel to which the step belongs.
     * @param array  $settings   An array of settings containing the title and slug for the step.
     * @return void
     * @since 1.7.1
     */
    public function update_step_meta_on_funnel_name_change( $step_id, $funnel_id, $settings ){
        $instance = new \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;
        $function_exist = is_callable(array($instance, 'get_formatted_settings'));
        if( $function_exist ){
            $parent_step_id = \WPFunnelsPro\AbTesting\Backup_Ab_Testing::get_parent_step_id( $step_id );
            $parent_step_id = $parent_step_id ? $parent_step_id : $step_id;
            $ab_settings = \WPFunnelsPro\AbTesting\Backup_Ab_Testing::get_formatted_settings( $parent_step_id );
                
            if( is_array($ab_settings) && isset($ab_settings['data']['start_settings']['variations']) ){
                wp_update_post([
                    "ID" 			=> $parent_step_id,
                    "post_title" 	=> wp_strip_all_tags( $settings['title'] ),
                    "post_name" 	=> sanitize_title($settings['slug']),
                ]);
                foreach( $ab_settings['data']['start_settings']['variations'] as $key=>$variation ){
                    if( $variation['id'] == $step_id ){
                        $ab_settings['data']['start_settings']['variations'][$key]['step_title'] 	 = htmlspecialchars_decode(get_the_title($parent_step_id));
                        $ab_settings['data']['start_settings']['variations'][$key]['step_view_link'] = rtrim( get_the_permalink($parent_step_id), '/' );
                    }
                }
                $ab_settings = $ab_settings['data']['start_settings'];
                \WPFunnelsPro\AbTesting\Backup_Ab_Testing::update_start_settings( $parent_step_id, $ab_settings );
            }
        }
    }

    /**
     * update funnel data response
     * 
     * @param Array $response
     * 
     * @since 1.7.2
     * @return Array $response
     */
    public function update_funnel_data_response( $response ){
        
        $ab_data = [];
        if( is_array($response) && !empty($response['funnel_data']['drawflow']['Home']['data']) ){
            
            $steps_order = $response['funnel_data']['drawflow']['Home']['data'];
            if( is_array($steps_order) ){
                foreach( $steps_order as $key=>$step ){
                   
                    if( isset( $step['data']['step_id'] ) ){
                        $step_id = $step['data']['step_id'];
                        $default_settings = Backup_Ab_Testing::get_default_start_setting( $step_id );
                        //check A/B testing is enable or not
                        $result = Backup_Ab_Testing::maybe_ab_testing( $step_id );
                        
                        $default_settings['is_ab_enabled'] = $result ? $result : '';
                    
                        // get start settings
                        $result = Backup_Ab_Testing::get_start_settings( $step_id );
                        $default_settings['start_settings'] = $result ? $result : $default_settings['start_settings'];

                        if( isset($default_settings['start_settings']['variations']) && is_array($default_settings['start_settings']['variations']) ){
                            foreach( $default_settings['start_settings']['variations'] as $key => $variation ){
                                if( isset($variation['id'], $variation['step_type']) && ( 'checkout' ==  $variation['step_type'] || 'upsell' ==  $variation['step_type'] || 'downsell' ==  $variation['step_type'])){
                                    $step_products = get_post_meta( $variation['id'], '_wpfnl_'.$variation['step_type'].'_products', true );
                                    if( is_array($step_products) && count($step_products) ){
                                        $default_settings['start_settings']['variations'][$key]['is_product'] = 'yes'; 
                                    }

                                    if( 'checkout' ==  $variation['step_type'] ){
                                        $ob_products = get_post_meta( $variation['id'], 'order-bump-settings', true );
                                        if( is_array($ob_products) && count($ob_products) ){
                                            $default_settings['start_settings']['variations'][$key]['is_ob'] = 'yes'; 
                                        }
                                    }
                                }
                                if( count($default_settings['start_settings']['variations']) > 1 ){
                                    $funnel_id = get_post_meta( $variation['id'], '_funnel_id', true );
                                    $stats = Backup_Ab_Testing::get_stats_of_a_step( $funnel_id, $variation['id'] );
                                    if( isset($stats['total_visit'], $stats['conversion'] ) ){
                                        $default_settings['start_settings']['variations'][$key]['visit'] = $stats['total_visit'];
                                        $default_settings['start_settings']['variations'][$key]['conversion'] = $stats['conversion'];
                                    }
                                }else{
                                    foreach( $response['steps_order'] as $step_order ){
                                        if( isset( $step_order['id'], $variation['id'] ) &&  $variation['id'] == $step_order['id'] ){
                                            $default_settings['start_settings']['variations'][$key]['visit'] = $step_order['visit'];
                                            $default_settings['start_settings']['variations'][$key]['conversion'] = $step_order['conversion'];
                                        }
                                    }
                                }
                                
                            }
                        }
                        $ab_data['step_'.$step_id]['data']        = $default_settings;
                        $ab_data['step_'.$step_id]['step_type']  =  get_post_meta( $step_id, '_step_type', true );
                    
                    }
                }
            }
        }
        $response['ab_data'] = $ab_data;
        return $response;
    }


    /**
     * Update funnel link
     * 
     * @param Array $response
     * 
     * @return Array $response
     * @since 1.7.4
     */
    public function update_funnel_link( $response ){
        if (class_exists('\WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing')) {
            $instance = new \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;
            $function_exist = is_callable(array($instance, 'maybe_ab_testing'));
            if( $function_exist ){
                if( isset($response['funnel_id'],$response['step_id']) ){
                    $is_enabled = \WPFunnelsPro\AbTesting\Backup_Ab_Testing::maybe_ab_testing( $response['step_id'] );
                    $variations = \WPFunnelsPro\AbTesting\Backup_Ab_Testing::get_all_variations( $response['step_id'] );
                    if( 'yes' == $is_enabled && count($variations) > 1 ){
                        $utm_settings 		= Wpfnl_functions::get_funnel_utm_settings( $response['funnel_id'] );
                        if($utm_settings['utm_enable'] == 'on') {
                            unset($utm_settings['utm_enable']);
                            $view_link = add_query_arg($utm_settings,get_the_permalink($response['funnel_id']));
                            $view_link   = strtolower($view_link);
                            $response['link'] = $view_link;
                        }
                        $args = [
                            'wpfnl-step-id' => $response['step_id'],
                        ];
                        $response['link'] = add_query_arg($args,$response['link']);
                    }
                }
                
            }
        }
        return $response;
    }
}
