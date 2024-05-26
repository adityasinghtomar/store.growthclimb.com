<ul class="edit-field-settings__tab-nav">
    <li class='active'>
        <a href="#checkout-billing-field"> <?php echo __('Billing Fields', 'wpfnl'); ?> </a>
    </li>
    <li>
        <a href="#checkout-shipping-field"> <?php echo __('Shipping Fields', 'wpfnl'); ?> </a>
    </li>
    <li>
        <a href="#checkout-additional-field"> <?php echo __('Additional Fields', 'wpfnl'); ?> </a>
    </li>
</ul>

<?php include_once('add-checkout-field-drawer.php'); ?>
<?php include_once('edit-checkout-field-drawer.php'); ?>
<?php include('wpfnl-pro-checkout-action.php');?>
<?php include('confirmation-alert.php'); ?>

<div class="edit-field-settings__tab-content-wrapper">
    <div class="edit-field-settings__single-tab-content billing-field" id="checkout-billing-field">
        <?php require WPFNL_PRO_DIR . '/admin/modules/steps/checkout/views/edit-field/edit-field-billing-tab.php'; ?>
    </div>
   

    <div class="edit-field-settings__single-tab-content shipping-field" id="checkout-shipping-field">
        <?php require WPFNL_PRO_DIR . '/admin/modules/steps/checkout/views/edit-field/edit-field-shipping-tab.php'; ?>
    </div>
   

    <div class="edit-field-settings__single-tab-content additional-field" id="checkout-additional-field">
        <?php require WPFNL_PRO_DIR . '/admin/modules/steps/checkout/views/edit-field/edit-field-additional-tab.php'; ?>
    </div>
    
</div>

<!-- <div class="checkout-edit-field__settings-option">
    <h4 class="settings-title"><?php echo __('Locale Override Settings', 'wpfnl'); ?></h4>

    <ul class="options-wrapper">
        <li class="single-option">
            <span class="wpfnl-checkbox">
                <input type="checkbox" name="enable-label-override" id="enable-label-override" value=""/>
                <label for="enable-label-override"><?php echo __('Enable label override for address fields.', 'wpfnl'); ?></label>
            </span>
        </li>

        <li class="single-option">
            <span class="wpfnl-checkbox">
                <input type="checkbox" name="enable-priority-override" id="enable-priority-override" value=""/>
                <label for="enable-priority-override"><?php echo __('Enable priority override for address fields.', 'wpfnl'); ?></label>
            </span>
        </li>

        <li class="single-option">
            <span class="wpfnl-checkbox">
                <input type="checkbox" name="enable-placeholder-override" id="enable-placeholder-override" value=""/>
                <label for="enable-placeholder-override"><?php echo __('Enable placeholder override for address fields.', 'wpfnl'); ?></label>
            </span>
        </li>

        <li class="single-option">
            <span class="wpfnl-checkbox">
                <input type="checkbox" name="enable-validation-override" id="enable-validation-override" value=""/>
                <label for="enable-validation-override"><?php echo __('Enable required validation override for address fields.', 'wpfnl'); ?></label>
            </span>
        </li>

        <li class="single-option">
            <span class="wpfnl-checkbox">
                <input type="checkbox" name="enable-class-override" id="enable-class-override" value=""/>
                <label for="enable-class-override"><?php echo __('Enable class override for address fields.', 'wpfnl'); ?></label>
            </span>
        </li>
    </ul>
</div>

