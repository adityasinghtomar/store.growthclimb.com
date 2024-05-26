<?php

$title = $product->get_title();

?>
<tr class="<?php echo $product_id.'_single_product'?> single-product">
    <td class="product-title">
        <span> <?php echo $title ?></span>
    </td>
	<?php
	if ($isQuantity == 'yes'){
		?>
		<td class="product-quantity">
			<input type="number" class="set-quantity-single" min="1" product-id="<?php echo $product_id ?>" value="<?php echo $quantity ?>">
		</td>
		<?php
	}
	?>
    <td class="product-price">
        <span><?php echo get_woocommerce_currency_symbol().$product->get_price() ?></span>
    </td>
</tr>
