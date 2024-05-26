<?php

namespace WPFunnelsPro\OrdersMeta;

use WPFunnels\Traits\SingletonTrait;

class OrderMeta {
    use SingletonTrait;


    public function __construct() {
        add_filter( 'woocommerce_admin_order_data_after_order_details', array( $this, 'show_linked_orders' ), 10, 1 );
    }

    /**
     * linked parent orders and child orders
     *
     * @param $order
     */
    public function show_linked_orders( $order ) {
        $order_id = $order->get_id();
        $is_wpfnl_order = $order->get_meta('_wpfunnels_offer') === 'yes';

        if( $is_wpfnl_order ) {
            $parent_order_id = $order->get_meta('_wpfunnels_offer_parent_id');
            if($parent_order_id) {
                $parent_order_html = '<p class="form-field form-field-wide wpfnl_parent_order" style="margin-top: 20px"><strong>'.__('Parent Order: ').'</strong></p>';
                $parent_order_html .= sprintf('<span style="text-transform: capitalize; display: block"><a href="%1s" target="_blank">#%2s</a></span>', get_edit_post_link($parent_order_id), $parent_order_id);
                echo $parent_order_html;
            }
        } else {
            $child_order_html   = '';
            $child_orders       = $order->get_meta( '_wpfunnels_offer_child_orders' );
            if( $child_orders ) {
                $child_order_html = '<p class="form-field form-field-wide wpfnl_child_orders" style="margin-top: 20px"><strong>'.__('Offer orders: ').'</strong>';
                foreach ($child_orders as $order_id => $child_order) {
                    $type = $child_order['type'];
                    $child_order_html .= sprintf('<span style="text-transform: capitalize; display: block"><a href="%1s" target="_blank">#%2s</a> - %3s</span>', get_edit_post_link($order_id), $order_id, $type);
                }
                $child_order_html .= '</p>';
            }
            echo $child_order_html;
        }
    }

}