<table>
    <tr>
        <th class="product-title"><?php echo __('Product','wpfnl') ?></th>
		<?php
		if ($isQuantity == 'yes'){
			?>
			<th class="product-title"><?php echo __('Quantity','wpfnl') ?></th>
		<?php
		}
		?>

        <th class="product-price"><?php echo __('Price','wpfnl') ?></th>
    </tr>
