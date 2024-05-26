<?php
$variation_product = wc_get_product($variation['variation_id']);
$title = $variation_product->get_title();
$formatted_name = $title.' - '.ucfirst(implode(" , ", $variation_product->get_variation_attributes()));
?>
<tr class="<?php echo $variation['variation_id'].'_variation'?>">
    <td class="product-title">
        <?php
            if($type == 'radio'){
                woocommerce_form_field('wpfnl-product-variation_'.$parent_id, array(
                    'type' => $type,
                    'default' => $default['default'],
                    'custom_attributes' => array(
                        'data-id' => $variation['variation_id'],
                        'data-qty' => $quantity,
                    ),
                    'options' => array($variation['variation_id']=>$formatted_name),
                    'class' => array(
                        'form-row-wide wpfnl-product-variation'
                    ),
                ) ,$checkout->get_value('wpfnl-product-variation_'.$parent_id));

            }else{
                woocommerce_form_field('wpfnl-product-variation_'.$parent_id, array(
                    'type' => $type,
                    'label' => '<span class="checkbox-title">'.$formatted_name .'</span>',
                    'default' => $default['default'],
                    'custom_attributes' => array(
                        'data-id' => $variation['variation_id'],
                        'data-qty' => $quantity,
                    ),
                    'class' => array(
                        'form-row-wide wpfnl-product-variation'
                    ),
                ) ,$checkout->get_value('wpfnl-product-variation_'.$parent_id));
            }
        ?>
    </td>
	<?php
	if ($isQuantity == 'yes'){
		?>
		<td class="product-quantity">
			<input type="number" class="set-quantity" min="1" product-id="<?php echo $variation['variation_id'] ?>" value="<?php echo $quantity ?>">
		</td>
		<?php
	}
	?>
    <td class="product-price">
        <span><?php echo get_woocommerce_currency_symbol().$variation['display_price'] ?></span>
    </td>
</tr>
