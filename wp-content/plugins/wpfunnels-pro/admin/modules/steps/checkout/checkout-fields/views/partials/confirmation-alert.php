<div class="wpfnl-delete-alert-wrapper">
    <div class="wpfnl-delete-confirmation">
        <span class="icon">
            <?php require WPFNL_DIR . '/admin/partials/icons/cross-icon.php'; ?>
        </span>
        <h3><?php echo __('Are you sure you want to delete this Field?', 'wpfnl'); ?></h3>
        <ul class="wpfnl-delete-confirm-btn">
            <li><button class="btn-default cancel"><?php echo __('Cancel', 'wpfnl'); ?></button></li>
            <li>
                <button type="button" class="btn-default yes" data-index="">
                    <?php echo __('Yes', 'wpfnl'); ?>
                </button>
            </li>
        </ul>
    </div>
</div>