
<tr class="wpfnl-quantity-tr">
    <td>
        <?php if($isQuantity == 'yes'){ ?>
            <span style="display:none"><?php echo __('Quantity: ','wpfnl-pro') ?></span>
        <?php } ?>
    </td>
    
    <td>
        <input type="hidden" class="wpfnl-varition-qty" value="<?php echo $quantity ?>" >
        <button class="wpfnl-cart-update no-quantity" data-id="<?php echo $product_id ?>"><?php echo __('Update cart','wpfnl-pro') ?></button>
    </td>
</tr>
