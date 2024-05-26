<div class="add-checkout-field-wrapper">
    <div class="field-header">
        <h4><?php echo __('Add New Field', 'wpfnl'); ?></h4>
        <span class="add-checkout-field-close">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11.1667 1.72266L1.72223 11.1671" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M1.72223 1.72266L11.1667 11.1671" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
    </div>

    <form method="post" class="add_field_form">
        <div class="field-body">
            <div class="wpfnl-field-wrapper">
                <label><?php echo __('Type', 'wpfnl'); ?></label>
                <div class="wpfnl-fields">
                    <select class="wpfnl-edit-field-type wpfnl_type" name="wpfnl_type">
                        <option value="text" ><?php echo __('Text', 'wpfnl'); ?></option>
                        <option value="password" ><?php echo __('Password', 'wpfnl'); ?></option>
                        <option value="email" ><?php echo __('Email', 'wpfnl'); ?></option>
                        <option value="phone" ><?php echo __('Phone', 'wpfnl'); ?></option>
                        <option value="select" ><?php echo __('Select', 'wpfnl'); ?></option>
                        <option value="textarea" ><?php echo __('Textarea', 'wpfnl'); ?></option>
                        <option value="radio" ><?php echo __('Radio', 'wpfnl'); ?></option>
                    </select>
                </div>
            </div>

            <!-- field type option -->
            <div class="wpfnl-field-wrapper field-type-options dynamic_option">
                <div class="options-label">
                    <label> <?php echo __('Options', 'wpfnl'); ?> </label>
                    <button class="btn-default wpfnl_add_option" title="Add More">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5.99994 1V11" stroke="#6E42D3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M0.999947 6.00022H10.9999" stroke="#6E42D3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>

                <div class="wpfnl-fields" id="row1">
                    <input type="text" value="" name="wpfnl_edit_type_option[]" class="wpfnl_edit_type_option" id="wpfnl_edit_type_option" />
                    <button class="btn-default remove wpfnl_btn_remove" title="Remove" id="1">
                        <svg width="11" height="11" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.1667 1.72266L1.72223 11.1671" stroke="#842029" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M1.72223 1.72266L11.1667 11.1671" stroke="#842029" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>
            <!-- /field type option -->

            <div class="wpfnl-field-wrapper">
                <label><?php echo __('Name', 'wpfnl'); ?></label>
                <div class="wpfnl-fields">
                    <input type="text" name="wpfnl_name" id="wpfnl_name" class= "wpfnl_name" value="" />
                </div>
            </div>

            <div class="wpfnl-field-wrapper">
                <label><?php echo __('Label', 'wpfnl'); ?></label>
                <div class="wpfnl-fields">
                    <input type="text" name="wpfnl_label" id="wpfnl_label" class="wpfnl_label"  value="" />
                </div>
            </div>

            <div class="wpfnl-field-wrapper">
                <label><?php echo __('Placeholder', 'wpfnl'); ?></label>
                <div class="wpfnl-fields">
                    <input type="text" name="wpfnl_placeholder" id="wpfnl_placeholder" class="wpfnl_placeholder" value="" />
                </div>
            </div>

            <div class="wpfnl-field-wrapper">
                <label><?php echo __('Default Value', 'wpfnl'); ?></label>
                <div class="wpfnl-fields">
                    <input type="text" name="wpfnl_default" id="wpfnl_default"  class="wpfnl_default" value="" />
                </div>
            </div>

            <!-- <div class="wpfnl-field-wrapper">
                <label><?php echo __('Class', 'wpfnl'); ?></label>
                <div class="wpfnl-fields">
                    <input type="text" name="field-class" value="" />
                </div>
            </div> -->

            <div class="wpfnl-field-wrapper">
                <label><?php echo __('Validation', 'wpfnl'); ?></label>
                <div class="wpfnl-fields">
                    <select class="wpfnl_muliple_select wpfnl_validation" name="validate[]"
                        multiple="multiple" id="wpfunnels-funnel-type">
                        <option value="email">Email</option>
                        <option value="phone">Phone</option>
                        <option value="postcode">Postcode</option>
                        <option value="state">State</option>
                        <option value="number">Number</option>
                    </select>
                </div>
            </div>

            <div class="wpfnl-field-wrapper">
                <span class="wpfnl-checkbox">
                    <input type="checkbox" value="1" name="wpfnl_required" id="field-is-required" class="wpfnl_required" />
                    <label for="field-is-required"><?php echo __('Required', 'wpfnl'); ?></label>
                </span>
            </div>

            <div class="wpfnl-field-wrapper">
                <span class="wpfnl-checkbox">
                    <input type="checkbox" name="wpfnl_enable" id="field-is-enabled" class="wpfnl_enable" value="1"/>
                    <label for="field-is-enabled"><?php echo __('Enabled', 'wpfnl'); ?></label>
                </span>
            </div>

            <!-- <div class="wpfnl-field-wrapper">
                <span class="wpfnl-checkbox">
                    <input type="checkbox" name="field-display-in-email" id="field-display-in-email" value=""/>
                    <label for="field-display-in-email"><?php echo __('Display in Emails', 'wpfnl'); ?></label>
                </span>
            </div> -->

            <div class="wpfnl-field-wrapper">
                <span class="wpfnl-checkbox">
                    <input type="checkbox" name="wpfnl_show" id="field-display-in-order-details" class="wpfnl_show" value="1"/>
                    <label for="field-display-in-order-details"><?php echo __('Display in Order Detail Pages', 'wpfnl'); ?></label>
                </span>
            </div>
            <input type="hidden" name="hidden_field" id="hidden_field_add" class="wpfnl_hidden_field">
        </div>
        
        <div class="field-footer save-checkout-field">
            <button class="btn-default wpfnl_submit" type="submit">
                <?php echo __('save', 'wpfnl'); ?>
                <span class="wpfnl-loader"></span>
            </button>

            <span class="wpfnl-alert"></span>
        </div>
    </form>
    
</div>