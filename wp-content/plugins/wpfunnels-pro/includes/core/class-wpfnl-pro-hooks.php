<?php

namespace WPFunnelsPro\Filters;


use MintMail\App\Internal\Automation\Helper_functions;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;
use WPFunnelsPro\Frontend\Modules\Gateways\Payment_Gateways_Factory;
use Mint\MRM\DataBase\Tables\AutomationMetaSchema;
use Mint\MRM\DataBase\Tables\AutomationSchema;
use WPFunnelsPro\Wpfnl_Pro_functions;

class Wpfnl_Pro_Hooks {

    /**
     * Instance.
     *
     * Holds the plugin instance.
     *
     * @since 1.0.0
     * @access public
     * @static
     *
     * @var $instance Wpfnl_Pro_Hooks
     */
    public static $instance = null;


    /**
     * @var $offer_metas
     */
    public $offer_metas;

    /**
     * Instance.
     *
     * Ensures only one instance of the plugin class is loaded or can be loaded.
     *
     * @since 1.0.0
     * @access public
     * @static
     *
     * @return Wpfnl_Pro_Filters An instance of the class.
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     *
     * @since 1.0.0
     * @access private
     */
    public function init()
    {
        // hooks
        add_action( 'wpfunnels/after_clear_transient', array($this, 'clear_pro_plugin_transients') );


        // filters
        add_filter( 'wpfunnels/is_wpfnl_pro_active', array($this, 'is_wpfnl_pro') );
        add_filter( 'wpfunnels/is_pro_license_activated', array($this, 'is_pro_license_activated') );
        add_filter( 'is_wpf_pro_active', array($this, 'is_pro_license_activated') );

        add_filter( 'wpfunnels/supported_builders', array( $this, 'supported_builders' ) );

        add_filter( 'wpfnl_pro_modules', array($this, 'wpfnl_pro_modules') );

        add_filter( 'wpfunnels/funnel_window_admin_localize', array( $this, 'notice_for_payment_gateways' ) );
        add_action( 'wpfunnels/after_settings_saved' , array($this, 'save_offer_settings') );
        add_action( 'wpfunnels/after_settings_saved' , array($this, 'save_gtm_settings') );
        add_action( 'wpfunnels/after_settings_saved' , array($this, 'save_facebook_pixel_settings') );
        add_action( 'wpfunnels/after_settings_saved' , array($this, 'save_utm_settings') );
        add_filter( 'wpfnl_offer_meta', array($this, 'wpfnl_offer_meta'), 11, 1 );

        add_filter( 'woocommerce_order_item_display_meta_key', array($this, 'wpfnl_beautify_item_meta_on_order'), 10, 3 );
        add_filter( 'woocommerce_order_item_display_meta_value', array($this, 'wpfnl_update_order_item_display_meta_value'), 10, 3 );


        add_filter( 'wpfunnels/supported_step_type', array($this, 'update_supported_step_type'), 10 );
        
        
        add_filter( 'wpfunnels/supported_steps_key', [ $this, 'supported_steps_key' ] );
        add_filter( 'wpfunnels/before_delete_step', [ $this, 'before_delete_step' ], 10 );
        add_filter( 'wpfunnels/show_all_page_templates', [ $this, 'show_all_page_templates' ], 10 );
    }

    /**
     * clear pro transient data
     *
     * @since 1.2.8
     */
    public function clear_pro_plugin_transients() {
        delete_site_transient( 'update_plugins' );
        $this->clear_pro_update_check_transient();
        ob_start();
        do_action( 'wpfunnels/check_update_transient' );
        ob_get_clean();
    }


    /**
     * clear pro update check transient data
     *
     * @since 1.2.8
     */
    private function clear_pro_update_check_transient() {
        global $wpdb;
        $sql   = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
        WHERE a.option_name LIKE %s
        AND a.option_name NOT LIKE %s
        AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )";
        $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_site_transient_wpfunnelspro-check_for_plugin_update' ) . '%', $wpdb->esc_like( '_site_transient_timeout_' ) . '%' ) );
    }


    /**
     * check if the license is activated or not
     *
     * @return bool
     */
    public function is_pro_license_activated() {
        $status         = get_option( 'wpfunnels_pro_license_status' );
        return $status === 'active';

    }

    /**
     * Check if WPFNL pro is activated
     *
     * @return bool
     * @since 1.0.0
     */
    public function is_wpfnl_pro($is_pro) {
        return true;
    }


    /**
     * add pro builders
     *
     * @param $builders
     * @return mixed
     */
    public function supported_builders($builders) {
        $builders['divi-builder'] = 'Divi';
        return $builders;
    }

    /**
     * Pro module list
     *
     * @param $modules
     * @return array
     * @since 1.0.0
     */
    public function wpfnl_pro_modules( $modules ) {
        return array(
            'upsell',
            'downsell'
        );
    }


    /**
     * list of available and supported gateways by
     * WPFunnels
     *
     * @param $localize
     * @return mixed
     *
     * @since 1.0.0
     */
    public function notice_for_payment_gateways( $localize ) {

        if( !Wpfnl_functions::is_wc_active() ) {
            return $localize;
        }

        $wc_gateways            = WC()->payment_gateways->get_available_payment_gateways();
        $wc_enabled_gateways    = [];
        $supported_gateways     = Payment_Gateways_Factory::getInstance()->get_supported_payment_gateways();
        $offer_settings =  Wpfnl_functions::get_offer_settings();
        $not_supported_gateways = [];
        $is_supported_activated = false;

        if( $wc_gateways ) {
            foreach( $wc_gateways as $key => $gateway ) {
                if( $gateway->enabled == 'yes' ) {
                    $wc_enabled_gateways[$key] = $gateway->method_title;
                }
                if( isset($supported_gateways[$key])){
                    $is_supported_activated = true;
                }
            }
        }
        
        $not_supported_gateways         = array_diff_key( $wc_enabled_gateways, $supported_gateways );
        $not_supported_gateways_names   = is_array( $not_supported_gateways ) ? array_values( $not_supported_gateways ) : array();
        if( count( $not_supported_gateways ) ) {
            $message = wp_sprintf( '%s - <b>%l</b>. %s <a href="%s" target="_blank"><strong>%s</strong></a>',
                __( 'WPFunnels Upsell/Downsell does not support the following payment gateways which are enabled on your site', 'wpfnl-pro' ),
                $not_supported_gateways_names,
                'Please check the supported gateways',
                'https://getwpfunnels.com/docs/connect-payment-gateways/',
                'here.'
            );
            $localize['notices'][]  = array(
                'type'          => 'PaymentGatewaysNotice',
                'notice_type'   => 'warning',
                'notice_texts'  => $message
            );
            
        }

        if( !$is_supported_activated && $offer_settings['show_supported_payment_gateway'] == 'on' ){
            $message = wp_sprintf( '%s - <b>%l</b>. %s <a href="%s" target="_blank"><strong>%s</strong></a>',
                __( 'You have selected the option to show only supported payment gateways in the funnel checkouts, but you do not have any supported payment gateways active. Please enable Stripe, Paypal, Mollie, Authorize.net, or Cash On Delivery.', 'wpfnl-pro' ),
                '',
                'Please check the supported gateways',
                'https://getwpfunnels.com/docs/connect-payment-gateways/',
                'here.'
            );
            $localize['notices'][]  = array(
                'type'          => 'PaymentGatewaysNotice',
                'notice_type'   => 'warning',
                'notice_texts'  => $message
            );
        }

        return $localize;
    }


    /**
     * save offer settings
     *
     * @param $settings
     *
     * @since 1.0.0
     */
    public function save_offer_settings( $settings ) {
        
        $offer_orders = isset($settings['offer_orders']) ? $settings['offer_orders'] : 'main-order';
        $show_supported_payment_gateway = isset($settings['show_supported_payment_gateway']) ? $settings['show_supported_payment_gateway'] : 'off';
        $skip_offer_step = isset($settings['skip_offer_step']) ? $settings['skip_offer_step'] : 'off';
        $skip_offer_step_for_free = isset($settings['skip_offer_step_for_free']) ? $settings['skip_offer_step_for_free'] : 'off';
        $skip_offer_for_recurring_buyer = isset($settings['skip_offer_for_recurring_buyer']) ? $settings['skip_offer_for_recurring_buyer'] : 'off';
        $skip_offer_for_recurring_buyer_within_year = isset($settings['skip_offer_for_recurring_buyer_within_year']) ? $settings['skip_offer_for_recurring_buyer_within_year'] : 'off';
        $offer_settings = array(
            'offer_orders'  => $offer_orders,
            'show_supported_payment_gateway'  => $show_supported_payment_gateway,
            'skip_offer_step'  => $skip_offer_step,
            'skip_offer_step_for_free'  => $skip_offer_step_for_free,
            'skip_offer_for_recurring_buyer'  => $skip_offer_for_recurring_buyer,
            'skip_offer_for_recurring_buyer_within_year'  => $skip_offer_for_recurring_buyer_within_year,
        );
        Wpfnl_functions::update_admin_settings('_wpfunnels_offer_settings', $offer_settings );
    }
    /**
     * save Facebook pixel settings
     *
     * @param $settings
     *
     * @since 1.0.0
     */
    public function save_facebook_pixel_settings( $settings ) {
        $fb_pixel_enable        = isset($settings['enable_fb_pixel']) ? $settings['enable_fb_pixel'] : 'no';
        $fb_pixel_tracking_code = isset($settings['fb_tracking_code']) ? sanitize_text_field($settings['fb_tracking_code']) : '';
        $fb_pixel_tracking_events = isset($settings['fb_tracking_events']) ? $settings['fb_tracking_events'] : '';
        $fb_pixel_settings = array(
            'enable_fb_pixel'  => $fb_pixel_enable,
            'facebook_pixel_id'  => $fb_pixel_tracking_code,
            'facebook_tracking_events' => $fb_pixel_tracking_events
        );
        Wpfnl_functions::update_admin_settings('_wpfunnels_facebook_pixel', $fb_pixel_settings );
    }
    /**
     * 
     */
    public function wpfnl_offer_meta($offer_meta){
        array_push($offer_meta,'_wpfunnels_upsell');
        array_push($offer_meta,'_wpfunnels_downsell');
        $this->offer_metas = $offer_meta;
        return $offer_meta;
    }

    /**
     * save GTM settings
     *
     * @param $settings
     *
     * @since 1.0.0
     */
    public function save_gtm_settings( $settings ) {
        $gtm_enable        = isset($settings['gtm_enable']) ? $settings['gtm_enable'] : 'no';
        $gtm_container_id = isset($settings['gtm_container_id']) ? sanitize_text_field($settings['gtm_container_id']) : '';
        $gtm_events = isset($settings['gtm_events']) ? $settings['gtm_events'] : '';
        $gtm_settings = array(
            'gtm_enable'  => $gtm_enable,
            'gtm_container_id'  => $gtm_container_id,
            'gtm_events' => $gtm_events
        );
        Wpfnl_functions::update_admin_settings('_wpfunnels_gtm', $gtm_settings );
    }

    /**
     * save UTM settings
     *
     * @param $settings
     *
     * @since 1.0.0
     */
    public function save_utm_settings( $settings ) {
        $utm_enable    = isset($settings['utm_enable']) ? $settings['utm_enable'] : 'no';
        $utm_source    = isset($settings['utm_source']) ? $settings['utm_source'] : '';
        $utm_medium    = isset($settings['utm_medium']) ? $settings['utm_medium'] : '';
        $utm_campaign  = isset($settings['utm_campaign']) ? $settings['utm_campaign'] : '';
        $utm_content   = isset($settings['utm_content']) ? $settings['utm_content'] : '';
        $utm_settings  = array(
            'utm_enable'    => $utm_enable,
            'utm_source'    => $utm_source,
            'utm_medium'    => $utm_medium,
            'utm_campaign'  => $utm_campaign,
            'utm_content'   => $utm_content
        );
        Wpfnl_functions::update_admin_settings('_wpfunnels_utm_params', $utm_settings );
    }
    /**
     * beautify item meta on order
     * 
     * @param $display_key, $meta, $item
     * 
     * @return $display_key 
     */
    public function wpfnl_beautify_item_meta_on_order($display_key, $meta, $item){
        $offer_meta = ['_wpfunnels_order_bump','_wpfunnels_upsell','_wpfunnels_downsell'];
        if( is_admin() && $item->get_type() === 'line_item' && ( $meta->key === $offer_meta[0] || $meta->key === $offer_meta[1] || $meta->key === $offer_meta[2]) ) {
            $display_key = __("Offer Type", "woocommerce" );
        }
        return $display_key;
    }
    
    /**
     * Display customize meta value
     * 
     * @param $display_key, $meta, $item
     * 
     * @return $meta 
     */
    public function wpfnl_update_order_item_display_meta_value($display_key, $meta, $item){
        if( isset($item['order_id']) &&  $item['order_id'] ){
            $order = wc_get_order( $item['order_id'] );
            if ( $order && Wpfnl_functions::check_if_funnel_order( $order ) ) {
                $offer_metas = $this->offer_metas;
                if( is_admin() && ($item->get_type() === 'line_item')) {
                    foreach($offer_metas as $offer_meta){
                        
                        if($meta->key == $offer_meta){
                            $key = substr($offer_meta,11);
                            $meta = ucfirst(str_replace('_',' ',$key));
                            return $meta;
                        }
                    }
                }elseif( is_admin() && $item->get_type() === 'shipping' && $meta->key === 'Items') {
                    $meta = $item->get_meta('Items');
                    return $meta;
                }
            }else{
                $display_value = $meta->value;
                return $display_value;
            }
        }else{
            $display_value = $meta->value;
            return $display_value;
        }
        
    }



    /**
     * Hidden order item meta
     * 
     * @param $meta
     * 
     * @return $meta
     */
    public function wpfnl_woocommerce_hidden_order_itemmeta($meta) {
        $meta[] = '_wpfunnels_step_id';
        $meta[] = '_wpfnl_upsell';
        $meta[] = '_wpfnl_downsell';
        $meta[] = '_wpfnl_step_id';
        $meta[] = '_wpfunnels_offer_txn_id';
        $meta[] = '_wpfunnels_offer_refunded';
        return $meta;
    }

    /**
     * Update supported step for Pro
     * Add Upsell, Downsell and Custom steps for Pro plugin
     * 
     * @param Array $types
     * @return Array $types
     * @since 1.6.28
     */
    public function update_supported_step_type( $types ){

        if( Wpfnl_functions::is_pro_license_activated() ){
            $new_types = [
                [
                    'type' => 'custom',
                    'name' => 'Custom',
                ],
                [
                    'type' => 'upsell',
                    'name' => 'Upsell',
                ],
                [
                    'type' => 'downsell',
                    'name' => 'Downsell',
                ],
                
            ];    
            $types = array_merge( $types, $new_types );
        }
        return $types;
    }



    /**
     * Supported step keys
     * 
     * @return array $steps 
     */
    public function supported_steps_key( $meta_keys ){
        $meta_keys['addTag'] = [];
        $meta_keys['addList'] = [];
        $meta_keys['delay'] = [];
        $meta_keys['sendMail'] = [];
        $meta_keys['removeTag'] = [];
        $meta_keys['removeList'] = [];
        $meta_keys['wpf_optin_submit'] = [];
        $meta_keys['wpf_order_placed'] = [];
        $meta_keys['wpf_cta_triggered'] = [];
        $meta_keys['wpf_order_bump_accepted'] = [];
        $meta_keys['wpf_offer_trigger'] = [];
        return $meta_keys;
    }


    /**
     * After delete a funnel step
     * 
     * @param string $funnel_id
     * @param string $step_id
     * 
     * @return void
     */
    public function before_delete_step( $step_id ){
        if( $step_id ){
            try {
                $funnel_id = get_post_meta( $step_id, '_funnel_id', true );
                Wpfnl_Pro_functions::delete_automation_by_funnel_id( $funnel_id );
            }catch( \Exception $e ){

            }
        }
    }


    /**
     * This method is used to determine whether all page templates should be displayed or not for funnel's step.
     * It takes an argument $is_allow and sets it to true, indicating that all page templates are allowed.
     * Finally, it returns the updated value of $is_allow.
     * 
     * @param bool $is_allow  A variable indicating whether all page templates are allowed or not.
     * 
     * @since 1.8.7
     * @return bool
     */
    public function show_all_page_templates( $is_allow ){
        return true;
    }
}