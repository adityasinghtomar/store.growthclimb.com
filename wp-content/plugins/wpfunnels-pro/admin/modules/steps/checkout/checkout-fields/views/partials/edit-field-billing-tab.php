<div class="checkout-edit-field" >
    <div class="single-tab-header">
        <h4 class="tab-title"><?php echo __('Billing Fields', 'wpfnl'); ?></h4>
        <div class="restore-btn">
            <span class="wpfnl-alert"></span>
            <button type="button" class="btn-default wpfnl_restore_btn" data-id="billing">
                <?php echo __('Restore to default', 'wpfnl'); ?>
            </button>
        </div>
        <div class="add-new-checkout-field-btn">
            <button type="button" class="btn-default custom_checkout_add_field" data-id="billing"><?php echo __('Add Field', 'wpfnl'); ?></button>
        </div>
    </div>
    <!-- /.single-tab-header -->

    <div class="checkout-edit-field-wrapper">
        <div class="checkout-field-header">
            <div class="field-item field-hamburger"></div>
            <div class="field-item field-name"><?php echo __('Name', 'wpfnl'); ?></div>
            <div class="field-item field-type"><?php echo __('Type', 'wpfnl'); ?></div>
            <div class="field-item field-label"><?php echo __('Label', 'wpfnl'); ?></div>
            <div class="field-item field-placeholder"><?php echo __('Placeholder', 'wpfnl'); ?></div>
            <div class="field-item field-validation"><?php echo __('Validations', 'wpfnl'); ?></div>
            <div class="field-item field-required"><?php echo __('Required', 'wpfnl'); ?></div>
            <div class="field-item field-status"><?php echo __('Status', 'wpfnl'); ?></div>
            <div class="field-item field-action"><?php echo __('Actions', 'wpfnl'); ?></div>
        </div>

        <?php require WPFNL_PRO_DIR . '/admin/modules/steps/checkout/views/edit-field/checkout-single-billing-field.php';  ?>
        <!-- /.checkout__single-field -->

        <div class="checkout-field-header down">
            <div class="field-item field-hamburger"></div>
            <div class="field-item field-name"><?php echo __('Name', 'wpfnl'); ?></div>
            <div class="field-item field-type"><?php echo __('Type', 'wpfnl'); ?></div>
            <div class="field-item field-label"><?php echo __('Label', 'wpfnl'); ?></div>
            <div class="field-item field-placeholder"><?php echo __('Placeholder', 'wpfnl'); ?></div>
            <div class="field-item field-validation"><?php echo __('Validations', 'wpfnl'); ?></div>
            <div class="field-item field-required"><?php echo __('Required', 'wpfnl'); ?></div>
            <div class="field-item field-status"><?php echo __('Status', 'wpfnl'); ?></div>
            <div class="field-item field-action"><?php echo __('Actions', 'wpfnl'); ?></div>
        </div>

    </div>
    <!-- /.checkout-edit-field-wrapper checkout-single-field.php -->
</div>