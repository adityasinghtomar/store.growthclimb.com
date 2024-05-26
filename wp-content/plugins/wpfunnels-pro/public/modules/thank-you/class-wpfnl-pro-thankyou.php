<?php

namespace WPFunnelsPro\Frontend\Modules\Thankyou;

use WPFunnels\Frontend\Module\Wpfnl_Frontend_Module;

class Module extends Wpfnl_Frontend_Module {


    public function __construct() {
        add_action( 'woocommerce_after_order_details', array( $this, 'display_child_orders' ), 20 );
        add_filter( 'wpfunnels/main_order_total_price', array( $this, 'update_main_order_total' ), 20, 2 );
    }


    /**
     * display child orders if exits
     *
     * @param $order
     * @since 1.0.0
     */
    public function display_child_orders( \WC_Order $order ) {
        if ( ! $order instanceof \WC_Order ) {
            return;
        }
	
        $child_orders = get_post_meta($order->get_id(), '_wpfunnels_offer_child_orders', true);
		
		if( !$child_orders ){
			$child_orders =  $order->get_meta('_wpfunnels_offer_child_orders');
		}
        
        if($child_orders) {
            foreach ($child_orders as $order_id => $child_order) {
                include plugin_dir_path(__FILE__).'templates/child-orders.php';
            }
        }
    }


    /**
     * @desc Update the main order total price in thank you page
     * @param $formatted_total
     * @param \WC_Order $order
     * @return mixed|string
     */
    public function update_main_order_total( $formatted_total, \WC_Order $order ) {
        if( $order && !is_wp_error( $order ) && !$order->get_parent_id() ) {
            $child_orders = get_post_meta( $order->get_id(), '_wpfunnels_offer_child_orders', true );
			if( !$child_orders ){
				$child_orders =  $order->get_meta('_wpfunnels_offer_child_orders');
			}
            $order_total = $order->get_total();
            if( $child_orders ) {
                foreach( $child_orders as $order_id => $offer_type ) {
                    $child_order = wc_get_order( $order_id );
                    if( $child_order ) {
                        $refund_order = $child_order->get_total_refunded();
                        $order_total += $child_order->get_total();
                        $order_total = $order_total - $refund_order;
                    }
                }
            }
            $refund_main_order  = $order->get_total_refunded();
            $order_total        = $order_total - $refund_main_order;
            $formatted_total = wc_price( $order_total, array( 'currency' => $order->get_currency() ) );
        }
        return $formatted_total;
    }
}