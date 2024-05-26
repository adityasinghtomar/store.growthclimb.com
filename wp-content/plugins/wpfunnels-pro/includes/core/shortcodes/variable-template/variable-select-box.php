<div class="offer-product-single-variation <?php echo $product_id.'-offer-product'?>">
    <div class="variation-attr-name">
        <?php if(strpos($key, 'pa_') !== false){
            echo substr($key, 3);
            $update_key = substr($key, 3);
        }else{
            echo $key;  
            $update_key = $key;
        } ?>
    </div>

    <div class="variation-attr-value">
        <span class="variation-selectbox">
            <?php
            
            // $selected = isset( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) ? wc_clean( urldecode( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) ) : $product->get_variation_default_attribute( $attribute_name );
            wc_dropdown_variation_attribute_options(
                array(
                    'options'   => $value,
                    'attribute' => $key,
                    'product'   => $product,
                    'class'     => 'wpfnl-variable-attribute-offer',
                    'selected'  => __( 'Choose an option', 'wpfnl' )
                )
            );
            ?>
        </span>
    </div>
    
</div>