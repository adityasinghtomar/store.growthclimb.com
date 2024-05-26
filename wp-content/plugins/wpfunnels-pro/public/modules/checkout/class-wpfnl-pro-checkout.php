<?php
namespace WPFunnelsPro\Frontend\Modules\Checkout;

use WC_Countries;
use WPFunnels\Frontend\Module\Wpfnl_Frontend_Module;
use WPFunnelsPro\Modules\Frontend\Checkout\Single\Wpfnl_Single_Product;
use WPFunnelsPro\Modules\Frontend\Checkout\Variable\Wpfnl_Variable_Product;
use WPFunnelsPro\Frontend\Modules\Checkout\EditField\WPFunnel_Edit_field;
use WPFunnels\Wpfnl_functions;

class Module extends Wpfnl_Frontend_Module
{
    public function __construct()
    {
        
        new WPFunnel_Edit_field();
        
        /* create obj of simple product class */
        new Wpfnl_Single_Product();
        
        /* create obj of variable product class */
        new Wpfnl_Variable_Product();

        if( Wpfnl_functions::is_wc_active() ){
            add_filter( 'woocommerce_order_button_html', [$this, 'wpfnl_custom_order_button_html'], 10, 1 ); 
           
        }

    }


    /**
     * Change place order button text in checkout page
     * 
     * @param $button
     * @since 1.3.2
     */
    public function wpfnl_custom_order_button_html( $button ) {
        if( isset($_POST['post_data']) ){
			parse_str($_POST['post_data'], $values);
			$step_id = isset($values['_wpfunnels_checkout_id']) ? $values['_wpfunnels_checkout_id'] : '';
            if( $step_id ){
                $this->set_checkout_cookie();
                $additional_settings = get_post_meta( $step_id, '_wpfunnels_edit_field_additional_settings', true );
                if( isset($additional_settings['custom_order_text']) ){
                    $button = '<button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $additional_settings['custom_order_text'] ) . '" data-value="' . esc_attr( $additional_settings['custom_order_text'] ) . '">' . esc_html( $additional_settings['custom_order_text'] ) . '</button>';
                }
            }
		}
        return $button;
    }

    private function set_checkout_cookie(){
        $cookie_name        = 'wpfunnels_automation_data';
        $cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
        $cookie['orderbump_accepted']   = false;
        if( !isset($cookie['ob_accepetd_products']) ){
            $cookie['ob_accepetd_products'] = [];
        }
        setcookie( $cookie_name, wp_json_encode( $cookie ), time() + 3600 * 6, '/', COOKIE_DOMAIN );
    }


}
