<?php

namespace WPFunnelsPro\Frontend\Modules\Analytics;


use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

class Analytics {

    use SingletonTrait;


    public function __construct() {
        global $user_login;
        $general_settings = Wpfnl_functions::get_general_settings();
        $user = wp_get_current_user();
        if(!$user_login){
            add_action( 'template_redirect', array( $this, 'save_analytics_data' ) );
            add_action( 'wpfunnels/funnel_order_placed', array($this, 'funnel_order_placed'), 10, 3 );
            add_action( 'wpfunnels/offer_accepted', array( $this, 'save_offer_conversion' ), 20, 2 );
            add_action( 'wpfunnels/after_optin_submit', array( $this, 'save_optin_conversion' ), 20, 4 );
        }
        foreach($user->roles as $role){
            if( !isset($general_settings['disable_analytics'][$role] )){
                add_action( 'template_redirect', array( $this, 'save_analytics_data' ) );
                add_action( 'wpfunnels/funnel_order_placed', array($this, 'funnel_order_placed'), 10, 3 );
                add_action( 'wpfunnels/offer_accepted', array( $this, 'save_offer_conversion' ), 20, 2 );
                add_action( 'wpfunnels/after_optin_submit', array( $this, 'save_optin_conversion' ), 10, 4 );
            }
        }

    }


    /**
     * save analytics data
     *
     * @since 1.0.0
     */
    public function save_analytics_data() {
        if ( Wpfnl_functions::is_funnel_step_page() && ( method_exists( 'WPFunnels\Wpfnl_functions','is_builder_edit_page' ) && !Wpfnl_functions::is_builder_edit_page() ) ) {
            global $post;
            $funnel_id          = Wpfnl_functions::get_funnel_id();
            $step_id            = $post->ID;
            if ( ! $funnel_id ) {
                return;
            }

            $cookie_name    = 'wpfunnels_visited_step_ids_' . $funnel_id;
            $cookie         = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
            $is_returning   = in_array( $step_id, $cookie );
            if( !$is_returning ) {
                $cookie[] = $step_id;
            }

            setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
            $this->save_conversion_info( $funnel_id, $step_id );
            $this->save_visit_info( $funnel_id, $step_id, $is_returning );
        }
    }


    /**
     * save conversion information
     *
     * @param $funnel_id
     * @param $step_id
     *
     * @since 1.0.0
     */
    private function save_conversion_info( $funnel_id, $step_id ) {
        
        $previous_step_id = $this->get_previous_step_id( $funnel_id, $step_id );
        
        $this->save_conversion( $funnel_id, $previous_step_id );
    }


    /**
     * save offer conversion
     *
     * @param $order
     * @param $offer_product
     *
     * @since 1.0.0
     */
    public function save_offer_conversion( $order, $offer_product ) {
        $step_id            = $offer_product['step_id'];
        $funnel_id          = get_post_meta($step_id, '_funnel_id', true);
        $this->save_conversion( $funnel_id, $step_id, '', 'yes', $order->get_id(), false );
    }


    /**
     * Main Order Tracking hook
     *
     * @param $order_id
     * @param $funnel_id
     */
    public function funnel_order_placed( $order_id, $funnel_id, $step_id ) {
        if($funnel_id){
            global $post;
            $step_id  = $_POST['_wpfunnels_checkout_id'] ? $_POST['_wpfunnels_checkout_id'] : $post->ID;
            if( $step_id ){
                $previous_step_id = $this->get_previous_step_id( $funnel_id, $step_id );
                $this->save_conversion( $funnel_id, $step_id, '', 'yes', $order_id );
            }
        }
    }

    /**
     * save conversion
     *
     * @param $funnel_id
     * @param $step_id
     * @param bool $exclude_offer_data
     *
     * @since 1.0.0
     */
    private function save_conversion( $funnel_id, $step_id, $isOptin = '', $isConversion = 'yes', $order_id = '', $exclude_offer_data = true ) {
        $cookie_name    = 'wpfunnels_visited_steps_data_' . $funnel_id;
        $cookie_data    = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();

        if( $cookie_data && isset( $cookie_data[$step_id] ) ) {
            $previous_step_data = $cookie_data[$step_id];
            $previous_step_type = $previous_step_data['step_type'];
            $offer_step_type    = array('upsell', 'downsell');
            $save_conversion    = true;

            if( $exclude_offer_data && in_array( $previous_step_type, $offer_step_type ) ) {
                $save_conversion = false;
                $funnel_type =  get_post_meta($funnel_id,'_wpfnl_funnel_type', true);

                if( 'lms' == $funnel_type && isset($_COOKIE['wpfunnels_automation_data']) ){
                    $lms_data    = isset( $_COOKIE['wpfunnels_automation_data'] ) ? json_decode( wp_unslash( $_COOKIE['wpfunnels_automation_data'] ), true ) : array();
                    if( isset($lms_data['offer']) ){
                        if( isset($lms_data['offer'][$step_id] )){
                            if( $lms_data['offer'][$step_id]['status'] == 'accepted' ){
                                $save_conversion = true;
                            }
                        }
                    }
                }
            }

            
            if( $save_conversion && 'no' === $previous_step_data['conversion'] ) {
                global $wpdb;

                $table_fix = $wpdb->prefix;

                $analytics_meta_db = $table_fix . 'wpfnl_analytics_meta';

                /** update conversion data in conversion meta table */
                $analytics_id = $previous_step_data['analytics_id'];
                if( $isConversion ){
                    $wpdb->update(
                        $analytics_meta_db,
                        array(
                            'analytics_id'   => $analytics_id,
                            'meta_key'   => 'conversion',
                            'meta_value' => 'yes',
                        ),
                        array(
                            'analytics_id' => $analytics_id,
                            'meta_key' => 'conversion',
                        )
                    );
                }

                if ( is_user_logged_in() ) {
                    $user_id = get_current_user_id();
                    $user = get_userdata( $user_id );
                    $user_roles = $user->roles;
                    $user_role  = $user_roles[0];
                }else{
                    $user_role  = '';
                }
                $wpdb->insert(
                    $analytics_meta_db,
                    array(
                        'analytics_id'  => $analytics_id,
                        'funnel_id'     => $funnel_id,
                        'step_id'       => $step_id,
                        'meta_key'      => 'user_role',
                        'meta_value'    => $user_role,
                    )
                );  
                
                if( $order_id ){
                    $wpdb->insert(
                        $analytics_meta_db,
                        array(
                            'analytics_id'  => $analytics_id,
                            'funnel_id'     => $funnel_id,
                            'step_id'       => $step_id,
                            'meta_key'      => 'wpfunnel_order_id',
                            'meta_value'    => $order_id,
                        )
                    );        
                }
                
                if( $isOptin ){
                    $wpdb->insert(
                        $analytics_meta_db,
                        array(
                            'analytics_id'  => $analytics_id,
                            'funnel_id'     => $funnel_id,
                            'step_id'       => $step_id,
                            'meta_key'      => 'wpfunnel_optin_submit',
                            'meta_value'    => 'yes',
                        )
                    );        
                }

                if( ! $exclude_offer_data ) {
                    $offer_type = $previous_step_type;
                    $wpdb->insert(
                        $analytics_meta_db,
                        array(
                            'analytics_id'  => $analytics_id,
                            'funnel_id'     => $funnel_id,
                            'step_id'       => $step_id,
                            'meta_key'      => 'offer-type',
                            'meta_value'    => $offer_type,
                        )
                    );
                }
                
                /** update cookie */
                $cookie_data[$step_id]['conversion'] = 'yes';
                setcookie( $cookie_name, wp_json_encode( $cookie_data ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
            }
              
        }
    }


    /**
     * save visit info
     *
     * @param $funnel_id
     * @param $step_id
     * @param $is_returning
     *
     * @since 1.0.0
     */
    private function save_visit_info( $funnel_id, $step_id, $is_returning ) {
        global $wpdb;

        $table_fix = $wpdb->prefix;
        $analytics_db = $table_fix . 'wpfnl_analytics';
        $analytics_meta_db = $table_fix . 'wpfnl_analytics_meta';

        $http_referer  = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
        $user_info     = $this->get_user_info_data( $is_returning );
        $analytics_data= $this->get_analytics_data( $funnel_id, $step_id, $user_info );
        /** save visit data on analytics db */
        $wpdb->insert(
            $analytics_db,
            array(
                'funnel_id'         => $funnel_id,
                'step_id'           => $step_id,
                'visitor_type'      => $user_info['visitor_type'],
                'user_id'           => $user_info['user_id'],
                'user_ip'           => $user_info['user_ip'],
                'analytics_data'    => json_encode($analytics_data),
                'date_created'      => current_time( 'Y-m-d H:i:s' ),
                'date_created_gmt'  => current_time( 'Y-m-d H:i:s', 1 ),
                'date_modified'     => current_time( 'Y-m-d H:i:s' ),
                'date_modified_gmt' => current_time( 'Y-m-d H:i:s', 1 ),
            )
        );
        $analytics_id = $wpdb->insert_id;


        /** save meta info on meta table */
        $meta_keys = array(
            'conversion'   => 'no',
            'http_referer' => $http_referer,
	        'bounced'      => 'yes'
        );
        foreach ( $meta_keys as $key => $value ) {
            $wpdb->insert(
                $analytics_meta_db,
                array(
                    'analytics_id'  => $analytics_id,
                    'funnel_id'     => $funnel_id,
                    'step_id'       => $step_id,
                    'meta_key'      => $key,
                    'meta_value'    => $value,
                )
            );
        }

        /** set cookie data */
        $cookie_name        = 'wpfunnels_visited_steps_data_' . $funnel_id;
        $cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
        $cookie[$step_id]   = array(
            'funnel_id'     => $funnel_id,
            'step_id'       => $step_id,
            'step_type'     => get_post_meta( $step_id, '_step_type', true ),
            'analytics_id'  => $analytics_id,
            'conversion'    => 'no'
        );
        setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
        do_action( 'wpfunnels/update_ab_testing_winner', $funnel_id, $step_id );
    }


    /**
     * get previous step id
     *
     * @param $funnel_id
     * @param $step_id
     * @return bool|int
     *
     * @since 1.0.0
     */
    private function get_previous_step_id( $funnel_id, $step_id ) {
        $prev_step_id   = false;
        $prev_step_type = '';
        $funnel_data = Wpfnl_functions::get_funnel_data($funnel_id);
        if ( $funnel_data ) {
            $node_id        = Wpfnl_functions::get_node_id( $funnel_id, $step_id );
            $node_data      = $funnel_data['drawflow']['Home']['data'];
            foreach ( $node_data as $node_key => $node_value ) {
                if ( $node_value['id'] == $node_id ) {
                    if( !empty($node_value['inputs']) && isset($node_value['inputs']['input_1']['connections'][0])){
                        $prev_node_id 	= $node_value['inputs']['input_1']['connections'][0]['node'];
                        $prev_step_id 	= Wpfnl_functions::get_step_by_node( $funnel_id, $prev_node_id );
                        $prev_step_type = Wpfnl_functions::get_node_type( $node_data, $prev_node_id );  
                    }
                    break;
                }
            }
        }
        if( 'conditional' === $prev_step_type ) {
            $prev_step_id = $this->get_previous_step_id( $funnel_id, $prev_step_id );
        }
        return $prev_step_id;
    }


    /**
     * get user ip
     *
     * @return mixed|void
     *
     * @since 1.0.0
     */
    function get_the_user_ip() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return apply_filters( 'wpfunnels/get_the_user_ip', $ip );
    }


    /**
     * Process activity data for api.
     *
     * @param $funnel_id
     * @param $step_id
     * @param $user_type
     * @param $user_status
     * @param $user_info array
     * @return array
     * @since 1.0.0
     */
    public function get_analytics_data( $funnel_id, $step_id, $user_info ) {
        $http_referer  = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
        return array(
            "v" => 1,
            "visitor" => array(
                "id"        => $user_info['user_id'],
                "type"      => $user_info['visitor_type'],
                "ip"        => $user_info['user_ip'],
                "referrer"  => $http_referer,
            ),
            "funnel" => array(
                "id"    => $funnel_id,
                "meta"  => array(),
            ),
            "step" => array(
                "id"    => $step_id,
                "meta"  => array(),
            ),
        );
    }


    /**
     * get user information
     *
     * @param $is_returning
     * @return array
     *
     * @since 1.0.0
     */
    public function get_user_info_data( $is_returning ) {
        $user_id = get_current_user_id();
        return array(
            "user_id"       => $user_id,
            "user_ip"       => $this->get_the_user_ip(),
            "visitor_type"  => $is_returning ? 'returning' : 'new',
        );
    }


    /**
     * @param $step_id
     * @param $post_action
     * @param $record
     */
    public function save_optin_conversion( $step_id, $post_action, $action_type, $record ) {
        $funnel_id          = get_post_meta($step_id, '_funnel_id', true);
        if('notification' === $post_action || ( 'redirect_to' == $post_action && 'redirect_to_url' == $action_type ) ) {
            $this->save_conversion( $funnel_id, $step_id, 'yes' ,'' );
        }elseif( ('redirect_to' == $post_action && 'next_step' == $action_type) || ('next_step' == $post_action && 'next_step' == $action_type)  ){
            $this->save_conversion( $funnel_id, $step_id, 'yes' ,'yes' );
        }
    }
}
