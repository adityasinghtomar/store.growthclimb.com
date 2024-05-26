<!-- <div class="wpfnl_shipping_section">

</div> -->
<div class="checkout__single-field">
    <div class="field-item field-hamburger">
        <span class="hamburger-bar"></span>
    </div>
    <div class="field-item field-name" data-label="Name :"><?php echo $a; ?></div>
    <div class="field-item field-type" data-label="Type :"><?php echo $bf['type']; ?></div>
    <div class="field-item field-label" data-label="Label :"><?php echo $bf['label'] ?></div>
    <div class="field-item field-placeholder" data-label="Placeholder :"><?php echo $bf['placeholder'] ?></div>
    <?php 
    if(isset($bf['validate'])){
        $validation_array = $bf['validate']; 
        $validation_str = implode(",",$validation_array);
    }else{
        $validation_str = '';
    } 
    ?>
    <div class="field-item field-validation" data-label="Validations :"><?php echo $validation_str ?></div>
    <div class="field-item field-required" data-label="Required :">
        <div class="wpfnl-switcher">
            <?php if($bf['required'] == true){ ?>
                <input type="checkbox" checked name="field-required" id="<?php echo $a ?>-field-required" class="wpfnl_change_required" value="1" data-id="'.$a.'" data-type="billing"/>
            <?php }else{ ?>
                <input type="checkbox" name="field-required" id="<?php echo $a ?>-field-required" class="wpfnl_change_required" value="1" data-id="<?php echo $a ?>" data-type="billing"/>
            <?php } ?>
            <label for="<?php echo $a ?>-field-required"></label>
        </div>
    </div>
    <div class="field-item field-status" data-label="Status :">
        <div class="wpfnl-switcher">
            <?php if($bf['enable'] == 1){ ?>
                <input type="checkbox" checked name="field-status" id="<?php echo $a ?>-field-status" class="wpfnl_change_enable" value="1" data-id="<?php echo $a ?>" data-type="billing"/>
            <?php }else{ ?>
                <input type="checkbox" name="field-status" id="<?php echo $a ?>-field-status" class="wpfnl_change_enable" value="1" data-id="<?php echo $a ?>" data-type="billing"/>
            <?php } ?>
            <label for="<?php echo $a ?>-field-status"></label>
        </div>
    </div>
    <div class="field-item field-action" data-label="Actions :">
        <button type="button" class="edit-field wpfnl_edit_row" title="Edit Field" data-id="<?php echo $a ?>" data-type="billing">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path  class="icon stroke" d="M4 20.0001H8L18.5 9.50006C19.6046 8.39549 19.6046 6.60463 18.5 5.50006C17.3954 4.39549 15.6046 4.39549 14.5 5.50006L4 16.0001V20.0001" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path  class="icon stroke" d="M13.5 6.5L17.5 10.5" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <button type="button" class="delete-checkout-field" title="Delete Field" data-id="<?php echo $a ?>" id="wpfnl_delete">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3.33333 5.83333H16.6667" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8.33334 9.16667V14.1667" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M11.6667 9.16667V14.1667" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M4.16667 5.83333L5.00001 15.8333C5.00001 16.7538 5.7462 17.5 6.66667 17.5H13.3333C14.2538 17.5 15 16.7538 15 15.8333L15.8333 5.83333" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M7.5 5.83333V3.33333C7.5 2.8731 7.8731 2.5 8.33333 2.5H11.6667C12.1269 2.5 12.5 2.8731 12.5 3.33333V5.83333" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>
</div>