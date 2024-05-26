<?php
namespace WPFunnelsPro\Integration\Affiliate;

use Affiliate_WP_WooCommerce;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

class Wpfnl_Pro_Integration_WPAffiliate {
    use SingletonTrait;

    public function __construct() {
        add_filter('wpfunnels/offer_accepted', [$this, 'wpfnl_offer_product_affiliate_support'], 10, 2);
    }

    /**
     * Added Affiliate data when added upsell and downsell product
     * @param $order
     * @param $offer_product
     */
    public function wpfnl_offer_product_affiliate_support($order, $offer_product)
    {
        if( $this->is_wp_affiliate_active() ){
            global $wpdb;
            $offer_settings                 = Wpfnl_functions::get_offer_settings();
            $affilate_woocommerce           = new Affiliate_WP_WooCommerce;
            $offer_order = isset($offer_settings['offer_orders']) ? $offer_settings['offer_orders'] : 'main-order';
            if( 'main-order' == $offer_order ){
                $table = $wpdb->prefix.'affiliate_wp_referrals';
                $wpdb->delete( $table, array( 'reference' => $order->get_id() ) );
                $affilate_woocommerce->add_pending_referral($order->get_id());
            }else{
                $affilate_woocommerce->add_pending_referral($order->get_id());
            }
        }

    }

    /**
     * Checking when Affiliate Plugin is active
     * @return bool
     */

    public function is_wp_affiliate_active()
    {
       if (is_plugin_active('affiliate-wp/affiliate-wp.php')) {
           return true;
       }
       return false;
    }

}