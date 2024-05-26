<?php

namespace WPFunnelsPro\Order;


use WC_Order_Factory;
use WPFunnels\Traits\SingletonTrait;
use WPFunnelsPro\Wpfnl_Pro;

class Wpfnl_Order_Refund {

    use SingletonTrait;

    public $order_id;

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'order_refund_meta_box' ) );
        wp_ajax_helper()->handle('wpfnl-refund-offer')
            ->with_callback([ $this, 'refund_offer' ])
            ->with_validation($this->get_validation_data());
    }

    public function get_validation_data()
    {
        return [
            'logged_in' => true,
            'user_can' => 'manage_options',
        ];
    }

    /**
     * register offer refund meta box
     */
    public function order_refund_meta_box() {
        global $post;
        if ( 'shop_order' === get_current_screen()->id ) {
            $this->order_id = $post->ID;
            $order = wc_get_order($this->order_id);
            if( false !== is_a( $order, 'WC_Order' ) ){
                $funnel_id      = $order->get_meta('_wpfunnels_funnel_id');
                if( $funnel_id ) {
                    
                    if($this->should_show_refund_metabox($order)) {

                        add_action( 'admin_enqueue_scripts', array( $this, 'add_offer_refund_scripts' ) );
                        add_meta_box(
                            'wpfnl-offer-refund-metabox',
                            __( 'WPFunnels Refund Offer', 'wpfnl-pro' ),
                            array( $this, 'refund_offer_metabox_callback' ),
                            'shop_order',
                            'normal'
                        );
                        add_filter( 'get_user_option_meta-box-order_shop_order', array( $this, 'move_refund_offer_metabox' ),10,1 );
                    }
                }
            }
        }
    }


    /**
     * move offer refund meta box under shop order
     *
     * @param $metabox
     * @return mixed
     */
    public function move_refund_offer_metabox( $metabox ) {
        $metabox['normal'] = join( ',', array(
            'woocommerce-order-items',
            'wpfnl-offer-refund-metabox',
        ) );
        return $metabox;
    }

    /**
     * add scripts for refund meta box
     */
    public function add_offer_refund_scripts() {
        if ( ! is_admin() ) {
            return;
        }
        wp_enqueue_style( 'wpfnl-pro-offer-refund', WPFNL_PRO_URL . 'admin/assets/css/offer-refund-metabox.css', array(), WPFNL_PRO_VERSION );
        wp_enqueue_script( 'wpfnl-pro-offer-refund', WPFNL_PRO_URL . 'admin/assets/js/offer-refund-metabox.js', array( 'jquery' ), WPFNL_PRO_VERSION, true );
    }


    /**
     * should show refund meta
     *
     * @param \WC_Order $order
     * @return bool
     */
    public function should_show_refund_metabox( \WC_Order $order ) {
        $is_offer_exists = $this->is_offer_exits_in_order($order);
        if($is_offer_exists) {
            $order_gateway          = $order->get_payment_method();
            $payment_gateway_obj    = Wpfnl_Pro::instance()->payment_gateways->build_gateway($order_gateway);
            $is_refund_supported    = $payment_gateway_obj->refund_support;
            if($is_refund_supported) {
                return true;
            }
        }
        return false;
    }


    /**
     * check if offer exits in order
     *
     * @param \WC_Order $order
     * @return bool
     */
    private function is_offer_exits_in_order( \WC_Order $order ) {
        $is_offer   = false;
        $line_items = $order->get_items();
        foreach ($line_items as $item_id => $item) {
            $is_upsell_offer    = wc_get_order_item_meta( $item_id, '_wpfunnels_upsell', true );
            $is_downsell_offer  = wc_get_order_item_meta( $item_id, '_wpfunnels_downsell', true );
            $txn_id             = wc_get_order_item_meta( $item_id, '_wpfunnels_offer_txn_id', true );
            if ( 'yes' == $is_upsell_offer || 'yes' == $is_downsell_offer && ! empty( $txn_id ) ) {
                $is_offer = true;
                break;
            }
        }
        return $is_offer;
    }


    public function refund_offer_metabox_callback() {
        include WPFNL_PRO_DIR . 'admin/partials/refund-metabox.php';
    }


    /**
     * refund offer ajax action
     *
     * @param $payload
     * @return array
     * @throws \Exception
     */
    public function refund_offer($payload) {

        $order_id       = isset( $payload['order_id'] ) ? intval( $payload['order_id'] ) : 0;
        $step_id        = isset( $payload['step_id'] ) ? intval( $payload['step_id'] ) : 0;
        $product_id     = isset( $payload['product_id'] ) ? intval( $payload['product_id'] ) : 0;
        $quantity       = isset( $payload['quantity'] ) ? $payload['quantity'] : 0;
        $amount         = isset( $payload['amount'] ) ? $payload['amount']  : 0;
        $api_refund     = isset( $payload['api_refund'] ) ? $payload['api_refund']  : false;
        $transaction_id = isset( $payload['transaction_id'] ) ? $payload['transaction_id'] : '';
        $item_id        = isset( $payload['item_id'] ) ? $payload['item_id'] : '';
        $wpfnl_refund   = isset( $payload['wpfnl_refund'] ) ?  $payload['wpfnl_refund']  : '';
        $refund_reason  = isset( $payload['refund_reason'] ) ?  $payload['refund_reason']  : '';

        $data = array(
            'offer_id'          => $product_id,
            'transaction_id'    => $transaction_id,
            'amount'            => $amount,
            'step_id'           => $step_id,
            'reason'            => '',
        );

        $result = array(
            'success' => false,
            'msg'     => __( 'Refund unsuccessful', 'wpfnl-pro' ),
        );

        if( $order_id ) {
            $order                  = wc_get_order($order_id);
            $order_gateway          = $order->get_payment_method();
            $payment_gateway_obj    = Wpfnl_Pro::instance()->payment_gateways->build_gateway($order_gateway);
            if( $payment_gateway_obj->refund_support ) {
                $refunded_txn_id = $payment_gateway_obj->process_refund_offer( $order, $data );
            }

            if ( false !== $refunded_txn_id ) {

                $offer_item  = WC_Order_Factory::get_order_item( $item_id );
                $line_items[ $item_id ] = array(
                    'qty'          => max( $offer_item->get_quantity(), 0 ),
                    'refund_total' => wc_format_decimal( $offer_item->get_total() ),
                    'refund_tax'   => array(),
                );
                $order_taxes = $order->get_taxes();
                $tax_data = $offer_item->get_taxes();
                $tax_item_total                         = [];
                foreach ( $order_taxes as $tax_item ) {
                    $tax_item_id                    = $tax_item->get_rate_id();
                    $tax_item_total[ $tax_item_id ] = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : 0;
                }
                $line_items[ $item_id ]['refund_tax'] = array_filter( array_map( 'wc_format_decimal', $tax_item_total ) );

                $refund = wc_create_refund(
                    array(
                        'amount'         => $amount,
                        'reason'         => $refund_reason,
                        'order_id'       => $order_id,
                        'refund_payment' => false,
                        'line_items'     => $line_items,
                        'restock_items'  => true,
                    )
                );

                if ( is_wp_error( $refund ) ) {
                    $result['success'] = true;
                    $result['msg']     = __( 'Refund Unsuccessful', 'wpfnl-pro' );
                } else {
                    wc_update_order_item_meta( $item_id, '_wpfunnels_offer_refunded', 'yes' );
                    $result['success'] = true;
                    $result['msg']     = __( 'Refund Successful', 'wpfnl-pro' );
                }
            }
        }
        return $result;
    }
}