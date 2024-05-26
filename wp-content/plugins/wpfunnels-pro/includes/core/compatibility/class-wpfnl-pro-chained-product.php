<?php
/**
 * This class is responsible for compatibility with woocommerce Chained Product
 * 
 */

namespace WPFunnelsPro\Compatibility;
use WPFunnels\Wpfnl_functions;

class ChainedProduct{


    /**
     * Retrieves the details of chained products associated with the given product ID.
     * 
     * @param int $product_id The ID of the product.
     * 
     * @since 1.8.4
     * @return array|false The chained product details on success, false on failure.
     */
    public function get_chain_product_details( $product_id ){
        if( !$product_id || !class_exists('\WC_Admin_Chained_Products') ){
            return false;
        }
		$chained_instance = new \WC_Admin_Chained_Products();
        $chained_products = $chained_instance->get_all_chained_product_details( $product_id );
        if( !is_array($chained_products) ){
            return false;
        }
        return $chained_products;
    }


    /**
     * Updates the order items with chained products.
     * 
     * @param WC_Order $order  The order object.
     * @param Array $chained_products An array of chained products.
     * @param Int $product_id The ID of the main product.
     * 
     * @since 1.8.8
     * @return Array An associative array with success status, overridden amount, total chained product price and details of added chained items.
     */
    public function update_order_item( $order, $chained_products, $product_id ){
        $response = [
            'success' => false
        ];
        if( !$order || empty($chained_products) ){
            return $response;
        }
        if ($chained_products){
            $chain_items = [];
            $total_chained_product_price = 0;
            foreach ($chained_products as $chained_product_id => $chianed_product){
                $new_product_price = 0; 
                if( !isset($chianed_product['unit']) ) {
                    continue;
                }
                $quantity = $chianed_product['unit'];
                $chained_product = wc_get_product( $chained_product_id );
                $chained_product_price = $chained_product->get_price();
                if ( ! empty( $chained_product_price ) ) {
                    if( isset($chianed_product['priced_individually']) && 'yes' === $chianed_product['priced_individually'] ){
                        $total_chained_product_price += $chained_product_price * $chianed_product['unit'];
                        $new_product_price = $chained_product_price * $chianed_product['unit']; 
                        $response['override_amount'] = true;
                    }
                }

                $chained_product->set_price( $new_product_price );
                $chain_item_id = $order->add_product( $chained_product, $quantity);
                wc_update_order_item_meta($chain_item_id, '_chained_product_of', $product_id);
                if( isset($chianed_product['priced_individually']) ){
                    wc_update_order_item_meta($chain_item_id, '_cp_priced_individually', $chianed_product['priced_individually']);
                }
                $chain_item_details = [
                    'id'    => $chain_item_id,
                    'price' => $new_product_price,
                ];
                array_push( $chain_items, $chain_item_details);
            }
            if( isset($response['override_amount'])  ){
                $response['total_chained_product_price'] = $total_chained_product_price;
            }
            $response['success'] = true;
            $response['chain_item_details'] = $chain_items;
        }
        return $response;
    }


    /**
     * Updates the tax amounts and related order item meta for chained items.
     * 
     * @param Array $chain_items An array containing details of chained items.
     * 
     * @since 1.8.8
     * @return void
     */
    public function update_tax_ammount( $chain_items ){
        if( is_array($chain_items) && !empty($chain_items) ){
            foreach( $chain_items as $chain_item ){
                if( isset($chain_item['id'], $chain_item['price']) ){
                    $tax = $chain_item['price'] - ( $chain_item['price'] /( ( $tax_rate/100 ) + 1 ));
                    $line_tax = [
                        'total' => [
                            $rate_id => $tax
                        ],
                        'subtotal' => [
                            $rate_id => $tax
                        ],
                    ];
                    $total_without_tax = $chain_item['price'] - $tax;

                    $meta_updates = [
                        '_line_tax_data'       => $line_tax,
                        '_line_subtotal_tax'   => $tax,
                        '_line_tax'            => $tax,
                        '_line_total'          => $total_without_tax,
                        '_line_subtotal'       => $total_without_tax,
                    ];
                    foreach( $meta_updates as $key=>$value ){
                        wc_update_order_item_meta( $chain_item['id'], $key, $value );
                    }
                }
            }
        }
    }

}