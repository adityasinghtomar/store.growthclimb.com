<tr class="<?php echo $product_id.'-product'?>">
    <td class="variation-attr-name">
        <?php if(strpos($key, 'pa_') !== false){
            echo substr($key, 3);
            $update_key = substr($key, 3);
        }else{
            echo $key;  
            $update_key = $key;
        } ?>
    </td>
    
    <td class="variation-attr-value">
        <span class="variation-selectbox">
            <?php
            $default_attr = trim($default_attr);
          
            wc_dropdown_variation_attribute_options(
                array(
                    'options'   => $value,
                    'attribute' => $key,
                    'product'   => $product,
                    'class'     => 'wpfnl-variable-attribute',
                    'selected'  => $default_attr ? $default_attr : $value[0],
                )
            );
            ?>
        </span>
    </td>
</tr>