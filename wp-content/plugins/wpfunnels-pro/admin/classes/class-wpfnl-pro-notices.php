<?php


namespace WPFunnelsPro\Notice;

use WPFunnels\Traits\SingletonTrait;
use WPFunnelsPro\Wpfnl_Pro_functions;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Frontend\Modules\Gateways\Payment_Gateways_Factory;

class Notices {
    use SingletonTrait;

    private static $core_notices = array(
        'inactive_license'  => 'inactive_license',
        'inactive_supported_payment_gateway'  => 'inactive_supported_payment_gateway',
        
    );


    public function __construct() {
        foreach ( self::$core_notices as $notice ) {
            add_action( 'admin_notices', array( __CLASS__, self::$core_notices[ $notice ] ) );
        }
    }


    /**
     * Inactive license notice
     */
    public static function inactive_license() {
        
        $disabled_notice_page = [
            'edit_funnel',
            'wp_funnels',
        ];

        if( !isset($_GET['page']) || (isset($_GET['page']) && !in_array( $_GET['page'],$disabled_notice_page )) ) {
            $active_status = get_option('wpfunnels_pro_license_status');
            if( 'active' === $active_status ) {
                return;
            }
            $message = sprintf( __( 'Your <strong>WPFunnels Pro</strong> License is not activate. Please, go to the WPFunnels > License menu and activate the license to use all the pro features of <strong>WPFunnels Pro </strong>. <a href="%1$s">Activate now.</a>', 'wpfnl-pro' ), esc_url(admin_url('admin.php?page=wpf-license')));

            $output  = '<div class="wpfunnels-notice notice notice-error is-dismissible">';
            $output .= '<p>' . wp_kses_post( $message ) . '</p>';
            $output .= '</div>';

            echo $output;
        }
    }
    

    /**
     * Inactive supported payment gateway notice
     */
    public static function inactive_supported_payment_gateway() {
        $is_woocommerce = Wpfnl_functions::is_wc_active();
        if( $is_woocommerce ){
            global $post, $typenow, $current_screen;
            $offer_settings =  Wpfnl_functions::get_offer_settings();
            if( is_admin() && 'wp_funnels' === $current_screen->parent_base && $offer_settings['show_supported_payment_gateway'] == 'on' ){
                $available_payment_methods = WC()->payment_gateways->get_available_payment_gateways();
                $supported_gateways = Payment_Gateways_Factory::getInstance()->get_supported_payment_gateways();
                
                $is_supported_activated = false;
                foreach($available_payment_methods as $key => $method ){

                    if( isset($supported_gateways[$key])){
                        $is_supported_activated = true;
                    }
                }
                if( !$is_supported_activated ){
                    $message = wp_sprintf( '%s - <b>%l</b>. %s <a href="%s" target="_blank"><strong>%s</strong></a>',
                        __( 'You have selected the option to show only supported payment gateways in the funnel checkouts, but you do not have any supported payment gateways active. Please enable Stripe, Paypal, Mollie, Authorize.net, or Cash On Delivery.', 'wpfnl-pro' ),
                        '',
                        'Please check the supported gateways',
                        'https://getwpfunnels.com/docs/connect-payment-gateways/',
                        'here.'
                    );
                    
                    $output  = '<div class="wpfunnels-notice notice notice-warning">';
                    $output .= '<p>' . wp_kses_post( $message ) . '</p>';
                    $output .= '</div>';

                    echo $output;
                }else{
                    return;
                }
            }else{
                return;
            }
        }
        return;
        
    }
    
}