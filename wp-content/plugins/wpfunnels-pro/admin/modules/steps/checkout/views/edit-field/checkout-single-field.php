<div class="checkout__single-field">
    <?php include('confirmation-alert.php');?>
    
    <div class="field-item field-hamburger">
        <span class="hamburger-bar"></span>
    </div>
    <div class="field-item field-name" data-label="Name :">shipping_first_name</div>
    <div class="field-item field-type" data-label="Type :">country</div>
    <div class="field-item field-label" data-label="Label :">First name</div>
    <div class="field-item field-placeholder" data-label="Placeholder :">Apartment, suite, unit, etc. (optional)</div>
    <div class="field-item field-validation" data-label="Validations :"></div>
    <div class="field-item field-required" data-label="Required :"></div>
    <div class="field-item field-status" data-label="Status :">
        <div class="wpfnl-switcher">
            <input type="checkbox" name="field-status" id="field-status" />
            <label for="field-status"></label>
        </div>
    </div>
    <div class="field-item field-action" data-label="Actions :">
        <button type="button" class="edit-field" title="Edit Field">
            <?php require WPFNL_DIR . '/admin/partials/icons/edit-icon.php'; ?>
        </button>
        <button type="button" class="delete-checkout-field" title="Delete Field">
            <?php require WPFNL_DIR . '/admin/partials/icons/delete-icon.php'; ?>
        </button>
    </div>
</div>