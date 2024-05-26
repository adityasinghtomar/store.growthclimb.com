<?php

global $post;

$order_id = $post->ID;
$order = wc_get_order( $order_id );
$products = array();

defined( 'ABSPATH' ) || exit;

$order_items  = $order->get_items( 'line_item' );
foreach ( $order_items as $item_id => $value ) {
    $is_upsell   = wc_get_order_item_meta( $item_id, '_wpfunnels_upsell', true );
    $is_downsell = wc_get_order_item_meta( $item_id, '_wpfunnels_downsell', true );
    $step_id     = wc_get_order_item_meta( $item_id, '_wpfunnels_step_id', true );
    $is_refunded = wc_get_order_item_meta( $item_id, '_wpfunnels_offer_refunded', true );
    if ( 'yes' == $is_upsell || 'yes' == $is_downsell ) {
        if( 'yes' == $is_upsell ) {
            $offer_type     = 'Upsell';
        } elseif ( 'yes' == $is_downsell ) {
            $offer_type     = 'Downsell';
        }
        $transaction_id = wc_get_order_item_meta( $item_id, '_wpfunnels_offer_txn_id', true );
        $product = new WC_Product($value['product_id']);
        $products[ $item_id ] = array(
            'order_id'              => $order_id,
            'product_id'            => $value['product_id'],
            'item'                  => $value,
            'step_id'               => $step_id,
            'offer_type'            => $offer_type,
            'order_item_id'         => $item_id,
            'product_name'          => get_the_title( $value['product_id'] ),
            'sku'                   => $product->get_sku(),
            'total'                 => $value->get_total(),
            'cost'                  => $product->get_price(),
            'quantity'              => $value->get_quantity(),
            'item_total'            => 0,
            'item_tax'              => 0,
            'transaction_id'        => $transaction_id,
            'is_refunded'           => $is_refunded ? true : false,
        );

        if ( get_option( 'woocommerce_calc_taxes' ) ) {
            $products[ $item_id ]['total']      = $products[ $item_id ]['total'] + $value->get_total_tax();
            $products[ $item_id ]['item_total'] = $value->get_total();
            $products[ $item_id ]['item_tax']   = $products[ $item_id ]['item_tax'] + $value->get_total_tax();
        }
    }
}
?>
<div class="woocommerce_order_items_wrapper wc-order-items-editable wpfnl-order-refund-wrapper">
    <?php if ( count( $order_items ) > 0 && count( $products ) > 0 ) { ?>
        <table cellpadding="0" cellspacing="0" class="woocommerce_order_items wpfnl-offer-table">
            <thead>
                <tr>
                    <th class="wpfnl-item" colspan="2"><?php esc_html_e( 'Item', 'wpfnl-pro' ); ?></th>
                    <th class="wpfnl-offer-type"><?php esc_html_e( 'Offer Type', 'wpfnl-pro' ); ?></th>
                    <th class="wpfnl-cost"><?php esc_html_e( 'Cost', 'wpfnl-pro' ); ?></th>
                    <th class="wpfnl-quantity"><?php esc_html_e( 'Quantity', 'wpfnl-pro' ); ?></th>
                    <th class="wpfnl-total"><?php esc_html_e( 'Total', 'wpfnl-pro' ); ?></th>
                    <th class="wpfnl-action" width="1%"><?php esc_html_e( 'Action', 'wpfnl-pro' ); ?></th>
                </tr>
            </thead>
            
            <tbody>
            <?php
            foreach ( $products as $key => $product_details ) {

                $offer_type     = $product_details['offer_type'];
                $product_id     = $product_details['product_id'];
                $item_id        = $key;
                $item           = $product_details['item'];
                $product_name   = $product_details['product_name'];
                $step_id        = $product_details['step_id'];
                $offer_type     = $product_details['offer_type'];
                $product_qty    = $product_details['quantity'];
                $cost           = $product_details['cost'];
                $is_refunded    = $product_details['is_refunded'];
                $sku            = $product_details['sku'];
                $product_amount = wc_price( $product_details['total'] );
                $product        = wc_get_product( $product_id );
                $thumbnail      = $product ? ($product->get_image( 'thumbnail' )) : '';
                ?>
                <tr>

                    <td class="thumb">
                        <div class="wc-order-item-thumbnail"><?php echo wp_kses_post( $thumbnail ); ?></div>
                    </td>

                    <td class="wpfnl-item" data-sort-value="<?php echo $product_name; ?>">
                        <a href="<?php echo get_edit_post_link( $product_id ); ?>" class="wc-order-item-name"><?php echo $product_name; ?></a>
                        <div class="wc-order-item-sku"><strong><?php echo __('SKU', 'wpfnl-pro'); ?></strong> <?php echo $sku; ?></div>
                    </td>


                    <td class="wpfnl-offer-type">
                        <div class="view">
                            <span class="offer_type"><?php echo $offer_type; ?></span>
                        </div>
                    </td>


                    <td class="wpfnl-cost">
                        <div class="view">
                                <span class="cost">
                                    <?php
                                    echo wc_price( $order->get_item_subtotal( $item, false, true ), array( 'currency' => $order->get_currency() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    ?>
                                </span>
                        </div>
                    </td>

                    <td class="wpfnl-quantity">
                        <div class="view">
                            <span class="quantity"><?php echo $product_qty; ?></span>
                        </div>
                    </td>

                    <td class="wpfnl-total">
                        <div class="view">
                            <strong><?php echo $product_amount; ?> </strong>
                        </div>
                    </td>

                    <td width="1%" class="wpfnl-action">
                        <div class="view">
                            <?php
                            if ( ! $is_refunded ) {
                                $button_markup = sprintf(
                                    "<a href='#' class='button wpfnl-offer-refund' data-order-id='%1s' data-step-id='%1s' data-item-id='%1s' data-item-quantity='%1s' data-item-amount='%1s' data-product-id='%1s' data-txn-id='%1s'>%8s</a>",
                                    $order_id,
                                    $step_id,
                                    $item_id,
                                    $product_qty,
                                    $product_details['total'],
                                    $product_id,
                                    $product_details['transaction_id'],
                                    __( 'Refund', 'wpfnl-pro' )
                                );
                            } else {
                                $button_markup = '<a href="#" class="button disabled">' . __( 'Refunded', 'wpfnl-pro' ) . '</a>';
                            }
                            echo $button_markup;
                            ?>
                        </div>
                    </td>
                </tr>
                <?php
            }
            ?>
            <input type="hidden" value="<?php echo $order_id; ?>" name="order_id">
            </tbody>
        </table>

        <div class="refund-note">
            <?php esc_html_e( 'In this section, you may refund the orders that you sold as Upsell /Downsell. To refund other orders, use the default refund feature in WooCommerce.', 'wpfnl-pro' ); ?>
            </br>
            <?php esc_html_e( '**P.S. When handling refunds, always refund upsell/dowsell offers first, and then refund the main order.', 'wpfnl-pro' ); ?>
        </div>
        <?php
    } else {
        echo "<div class='no-refund-offer'>" . __( 'Refunds are not available for any offer(s) against this order.', 'wpfnl-pro' ) . '</div>';
    }
    ?>
</div>
