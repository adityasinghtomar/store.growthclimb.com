<?php
/**
 * View general settings
 * 
 * @package
 */

$builders = \WPFunnels\Wpfnl_functions::get_supported_builders();
?>
<div class="wpfnl-box">
    <div class="wpfnl-field-wrapper">
        <label><?php esc_html_e('Funnel Type', 'wpfnl'); ?></label>
        <div class="wpfnl-fields">
            <select name="page-builder" id="wpfunnels-funnel-type">
                <?php if( $this->is_allow_sales ){ ?>
                    <option value="sales" <?php selected($this->general_settings['funnel_type'], 'sales'); ?> ><?php esc_html_e('Sales', 'wpfnl'); ?></option>
                <?php } ?>
                <option value="lead" <?php selected($this->general_settings['funnel_type'], 'lead'); ?> ><?php esc_html_e('Lead Gen', 'wpfnl'); ?></option>
            </select>
        </div>
    </div>
    <!-- /field-wrapper -->

    <div class="wpfnl-field-wrapper">
        <label><?php esc_html_e('Page Builder', 'wpfnl'); ?></label>
        <div class="wpfnl-fields">
            <select name="page-builder" id="wpfunnels-page-builder">
				<?php
					foreach ( $builders as $key => $value ) { ?>
						<option value="<?php echo $key; ?>" <?php selected($this->general_settings['builder'], $key); ?> ><?php echo $value; ?></option>
					<?php }
				?>
			</select>
        </div>
    </div>


    <div class="wpfnl-field-wrapper sync-template">
        <label class="has-tooltip">
            <?php esc_html_e('Sync Template', 'wpfnl'); ?>

            <span class="wpfnl-tooltip">
                <?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
                <p><?php esc_html_e('Click to get the updated funnel templates, made using your preferred page builder, when creating funnels.', 'wpfnl'); ?></p>
            </span>
        </label>
        <div class="wpfnl-fields">
            <button class="btn-default clear-template" id="clear-template">
                <span class="sync-icon"><?php require WPFNL_DIR . '/admin/partials/icons/sync-icon.php'; ?></span>
                <span class="check-icon"><?php require WPFNL_DIR . '/admin/partials/icons/check-icon.php'; ?></span>
                <?php esc_html_e( 'Sync Templates', 'wpfnl' );?>
            </button>
            <span class="wpfnl-alert"></span>
        </div>
    </div>

    <!-- <div class="wpfnl-field-wrapper">
        <label class="has-tooltip">
            <?php 
            // esc_html_e('Clear Funnel Data on Plugin Uninstall', 'wpfnl'); 
            ?>
            <span class="wpfnl-tooltip">
                <?php 
                // require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; 
                ?>
                <p><?php 
                // esc_html_e('All the funnel data will be cleared when you uninstall the plugin', 'wpfnl'); 
                ?></p>
            </span>
        </label>
        <div class="wpfnl-fields">
                <span class="wpfnl-checkbox no-title">
                    <input type="checkbox" name="wpfnl-data-cleanup"  id="wpfnl-data-cleanup" <?php 
                    // echo $this->general_settings['uninstall_cleanup'] == 'on' ? 'checked' : ''; 
                    ?> />
                    <label for="wpfnl-data-cleanup"></label>
                </span>
        </div>
    </div> -->
    <!-- /field-wrapper -->
</div>
<!-- /settings-box -->
