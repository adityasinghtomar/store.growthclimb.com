<?php

namespace WPFunnelsPro\AbTesting;

use Error;

use WPFunnelsPro\Wpfnl_Pro_functions;
use WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;
use WPFunnels\Wpfnl;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

use function WPFunnels\Rest\Controllers\wpfnl_pro_analytics_get_param_type;


/**
 * Class Wpfnl_Ab_Testing_Hook
 * @package WPFunnelsPro\AbTesting
 */
class Wpfnl_Ab_Testing_Hook{

    use SingletonTrait;

    /**
     * Initialize all hooks
     */
    public function init()
    {
        add_action( 'wpfunnels/update_ab_testing_settings', [$this,'update_ab_testing_settings'], 10, 2 );
        add_filter( 'wpfunnels/update_ab_testing_winner', [$this, 'update_ab_testing_winner'], 10, 2 );
        add_filter( 'wpfunnels/modify_funnel_view_link', [$this, 'modify_funnel_view_link'], 10, 3 );
        add_filter( 'wpfunnels/update_funnel_data_response', [$this, 'update_funnel_data_response'], 10 );
        add_filter( 'wpfunnels/update_funnel_link', [$this, 'update_funnel_link'], 10 );
        add_filter( 'wpfunnels/step_data', [$this, 'update_step_data'], 10, 2 );
        add_action( 'wpfunnels/before_update_step_meta', [$this,'update_step_meta'], 10, 3 );
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

        Wpfnl_Ab_Testing::update_variations( $step_id, $variant_id );
        update_post_meta( $variant_id, '_parent_step_id', $step_id );
		Wpfnl_Ab_Testing::update_ab_testing_status( $step_id, 'yes');
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
        $response['abTestingSettingsData'] = Wpfnl_Ab_Testing::get_formatted_settings( $step_id );
        return $response;
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
        $parent_step_id = Wpfnl_Ab_Testing::get_parent_step_id( $step_id ) ? Wpfnl_Ab_Testing::get_parent_step_id( $step_id ) : $step_id;
        $is_enable = Wpfnl_Ab_Testing::maybe_ab_testing( $parent_step_id );
        if( $is_enable ){
           
            $all_conditions = Wpfnl_Ab_Testing::get_all_conditions( $parent_step_id );
            $is_matched = Wpfnl_Ab_Testing::match_condition( $all_conditions, $funnel_id, $parent_step_id );
            $is_winner = Wpfnl_Ab_Testing::get_winner( $parent_step_id );
     
            if( !$is_winner && $is_matched ){
                Wpfnl_Ab_Testing::set_winner( $parent_step_id, $step_id );
                
                // stop AB testing after winner matched
                $data = get_post_meta( $parent_step_id, '_wpfnl_ab_testing_start_settings' , true );
                $data['start_date'] = date( 'Y-m-d H:i:s' );
                $data['is_started'] = '';
                Wpfnl_Ab_Testing::update_start_settings( $parent_step_id, $data );
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
                $is_enabled = \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing::maybe_ab_testing( $step_id );
                $variations = \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing::get_all_variations( $step_id );
                if( $is_enabled && count($variations) ){
                    $link = get_the_permalink($funnel_id).'?wpfnl-step-id='.$step_id;
                }
            }
        }
        return $link;
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
            $parent_step_id = \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing::get_parent_step_id( $step_id );
            $parent_step_id = $parent_step_id ? $parent_step_id : $step_id;
            $ab_settings = \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing::get_formatted_settings( $parent_step_id );
                
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
                \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing::update_start_settings( $parent_step_id, $ab_settings );
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
        
        if( is_array($response) && !empty($response['funnel_data']['drawflow']['Home']['data']) ){
            
            $steps_order = $response['funnel_data']['drawflow']['Home']['data'];
            if( is_array($steps_order) ){
                foreach( $steps_order as $key=>$step ){
                    if( isset( $step['data']['step_id'], $response['funnel_data']['drawflow']['Home']['data'][$key]['data']['step_view_link'] ) ){
                        $step_id = $step['data']['step_id'];
                        if( Wpfnl_Ab_Testing::maybe_ab_testing_running( $step_id ) ){
                            $url = base64_decode($response['funnel_data']['drawflow']['Home']['data'][$key]['data']['step_view_link']);
                            if( false === strpos($url, '?wpfnl-step-id') ){
                                $response['funnel_data']['drawflow']['Home']['data'][$key]['data']['step_view_link'] = base64_encode(base64_decode($step['data']['step_view_link']).'?wpfnl-step-id='.$step_id);
                            }
                        }else{
                            $url = base64_decode($response['funnel_data']['drawflow']['Home']['data'][$key]['data']['step_view_link']);
                            if( false !== strpos($url, '?wpfnl-step-id') ){
                                $response['funnel_data']['drawflow']['Home']['data'][$key]['data']['step_view_link'] = base64_encode($this->remove_query_param( $url, 'wpfnl-step-id' ));
                            }
                        }
                    }
                }
            }
        }
        return $response;
    }


    private function remove_query_param($url, $paramToRemove) {
        // Parse the URL into components
        $urlParts = parse_url($url);
    
        if(isset($urlParts['query'])) {
            // Parse the query string into an array
            parse_str($urlParts['query'], $queryParams);
    
            // Remove the specified parameter
            if(isset($queryParams[$paramToRemove])) {
                unset($queryParams[$paramToRemove]);
            }
    
            // Rebuild the query string
            $newQuery = http_build_query($queryParams);
    
            // Reconstruct the URL
            $newUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'];
            if (!empty($newQuery)) {
                $newUrl .= '?' . $newQuery;
            }
            if(isset($urlParts['fragment'])) {
                $newUrl .= '#' . $urlParts['fragment'];
            }
    
            return $newUrl;
        } else {
            // No query parameters, return the original URL
            return $url;
        }
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
                    $is_enabled = \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing::maybe_ab_testing( $response['step_id'] );
                    $variations = \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing::get_all_variations( $response['step_id'] );
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


    /**
     * update step data if ab testings enable
     * 
     * @param array $step_data
     * @param int $step_id
     * 
     * @return array $step_data
     * @since 2.0.0
     */
    public function update_step_data( $step_data, $step_id ){
        if( !$step_id ){
            return $step_data;
        }
        $maybe_ab_testing = Wpfnl_Ab_Testing::maybe_ab_testing_running($step_id);
        $step_data['maybe_ab_testing'] = $maybe_ab_testing ? 'yes' : 'no';
        return $step_data;
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
       
        $parent_id = get_post_meta( $step_id, '_parent_step_id', true );
        $ab_settings = get_post_meta( $parent_id, '_wpfnl_ab_testing_start_settings', true );
        
        foreach( $ab_settings['variations'] as $key=>$variation ){
            if( $variation['stepId'] == $step_id ){
                $ab_settings['variations'][$key]['stepName'] 	 = htmlspecialchars_decode(get_the_title($step_id));
                $ab_settings['variations'][$key]['stepViewLink'] = rtrim( get_the_permalink($step_id), '/' );
            }
        }
        update_post_meta( $parent_id, '_wpfnl_ab_testing_start_settings', $ab_settings );
    }
}

