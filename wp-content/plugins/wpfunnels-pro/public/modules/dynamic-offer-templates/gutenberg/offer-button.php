<div class="wpfnl-offerbtn-wrapper" id="wpfnl-offerbtn-wrapper" >
    <?php 
    if( !isset($attributes['offerAction']) || 'reject' !== $attributes['offerAction'] ){
        
        if( $is_variable){
            if( isset($attributes['variationTblTitle']) && !empty( $attributes['variationTblTitle'] ) ){ 
                echo '<h5 class="wpfnl-product-variation-title">'.$attributes['variationTblTitle'].'</h5>';                       
            }
            ?>

            <div class="has-variation-product">
                <div class="wpfnl-product-variation">
                    <?php 
                        if( isset($attributes['showProductPrice']) && 'yes' === $attributes['showProductPrice'] ){
                            echo '<span class="offer-btn-loader"></span>';
                        }
                        echo do_shortcode('[wpf_variable_offer]');
                    ?>
                </div>
                <?php
        }
        
    }
    
    ?>

    <div class="wpfnl-offerbtn-and-price-wrapper <?php echo 'yes' === $attributes['showProductPrice'] ? $attributes['productPriceAlignment'] : '' ?> ">
        <?php if( isset($attributes['showProductPrice']) && 'yes' === $attributes['showProductPrice']  ){ ?>
            <span class="wpfnl-offer-product-price" id="wpfnl-offer-product-price">
                <?php
                    if( !$is_variable && (!isset($attributes['offerAction']) || 'reject' !== $attributes['offerAction'] ) ){
                       
                        if( !empty($product_info['id']) ){
                            $offer_product = wc_get_product($product_info['id']);
                            if( $offer_product ){
                                $step_type  = get_post_meta( get_the_ID(), '_step_type', true );
                                $discount   = get_post_meta( get_the_ID(), '_wpfnl_'.$step_type.'_discount', true );
                                $total_price = isset($response['quantity']) ? $offer_product->get_regular_price() * $response['quantity'] : $offer_product->get_regular_price();
                                if( isset($discount['discountApplyTo'], $discount['discountType']) && 'original' !== $discount['discountType'] ){
                                    if( 'sale' === $discount['discountApplyTo'] ){
                                        $sale_price = $offer_product->get_sale_price() ? $offer_product->get_sale_price() : $offer_product->get_regular_price();
                                    }elseif( 'regular' === $discount['discountApplyTo'] ){
                                        $sale_price = $offer_product->get_regular_price() ? $offer_product->get_regular_price() : $offer_product->get_price();
                                    }else{
                                        $sale_price = $offer_product->get_price();
                                    }
                                    $product_price 		= \WPFunnelsPro\Wpfnl_Pro_functions::calculate_discount_price_for_widget( $discount['discountType'] , $discount['discountValue'], $sale_price  );
                                    if( $product_price != $total_price ){
                                        echo wc_price(number_format( (float) $product_price, 2, '.', '' )).'<del>'.wc_price(number_format( (float) $total_price, 2, '.', '' )).'</del>';
                                    }else{
                                        echo wc_price(number_format( (float) $product_price, 2, '.', '' ));
                                    }
                                }else{
                                    if( $offer_product->get_sale_price() ){
                                        $sale_price = $offer_product->get_sale_price();
                                        $sale_price = isset($response['quantity']) ? $sale_price * $response['quantity'] : $sale_price;
                                        echo wc_price(number_format( (float) $sale_price, 2, '.', '' )).'<del>'.wc_price(number_format( (float) $total_price, 2, '.', '' )).'</del>';
                                    }else{
                                        echo wc_price(number_format( (float) $total_price, 2, '.', '' ));
                                    }    
                                }
                            }
                        }
                        
                    }
                ?>
            </span>
            <?php
        }

        echo $content;
        ?>
    </div>

    <?php
        if( $is_variable && (!isset($attributes['offerAction']) || 'reject' !== $attributes['offerAction'] ) ) {
            echo '</div>';
            //end ".has-variation-product"
        }
    ?>

</div>


<?php
    if( 'reject' !== $attributes['offerAction'] && \WPFunnelsPro\Wpfnl_Pro_functions::maybe_unsupported_payment_gateway() ){
        /**
         * Fires after the offer button is displayed.
         *
         * This action hook allows developers to add custom functionality after the offer button is rendered on the page.
         *
         * @since 2.0.5
         *
         * @param void
         */
        do_action( 'wpfunnels/after_offer_button' );
    }
?>