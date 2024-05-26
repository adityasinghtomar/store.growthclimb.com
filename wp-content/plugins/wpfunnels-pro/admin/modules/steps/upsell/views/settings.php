<?php

?>

<div class="steps-settings upsell">
    <?php require WPFNL_DIR . '/admin/modules/steps/general/step-title.php'; ?>
    <!-- /steps-page__content-title-wrapper -->

    <ul class="steps-settings__tab-nav">
        <li class="active">
            <a href="#upsell-products">
                <?php
                    require WPFNL_DIR . '/admin/partials/icons/product-icon.php';
                    echo __('Products', 'wpfnl');
                ?>
            </a>
        </li>
        <li class="conditonal-redirect">
            <a href="#upsell-redirect">
                <?php
                    require WPFNL_DIR . '/admin/partials/icons/repeat-icon.php';
                    echo __('Conditional Redirect', 'wpfnl');
                ?>
            </a>
        </li>
    </ul>

    <div class="step-settings__tab-content-wrapper">

        <div class="step-settings__single-tab-content upsell-products" id="upsell-products">
            <div class="wpfnl-box single__settings-box">
                <div class="wpfnl-field-wrapper">
                    <label><?php echo __('Select Product', 'wpfnl'); ?></label>
                    <div class="wpfnl-fields">
                        <?php
                            $product_module = \WPFunnels\Wpfnl::$instance->module_manager->get_admin_modules('product');
                            $product_array = $this->get_internal_metas_by_key('_wpfnl_upsell_products');
                            // $product_array = get_post_meta( $this->get_id(), '_wpfnl_upsell_product', true );

                            if (count($product_array)) {
                                $quantity = $product_array[0]['quantity'];
                            } else {
                                $quantity = '1';
                            }
                            $product_module->set_products($product_array);
                            $product_module->get_view();
                        ?>
                    </div>
                </div>
                <!-- /field-wrapper -->

                <div class="wpfnl-field-wrapper">
                    <label><?php echo __('Product Quantity', 'wpfnl'); ?></label>
                    <div class="wpfnl-fields">
                        <input type="number" name="upsell-product-quantity" id="upsell-product-quantity" value="<?php echo $quantity; ?>" min="1" />
                    </div>
                </div>
                <!-- /field-wrapper -->
            </div>
            <!-- /settings-box -->


            <!-- <div class="wpfnl-box single__settings-box">
                <div class="wpfnl-field-wrapper">
                    <label><?php echo __('Discount Type', 'wpfnl'); ?></label>
                    <div class="wpfnl-fields">
                        <select name="" id="upsell-discount-type">
                            <option value="" <?php selected($this->get_internal_metas_by_key('_wpfnl_upsell_discount_type'), ''); ?>>Original</option>
                            <option value="discount_percent" <?php selected($this->get_internal_metas_by_key('_wpfnl_upsell_discount_type'), 'discount_percent'); ?>>Discount Percentage</option>
                            <option value="discount_price" <?php selected($this->get_internal_metas_by_key('_wpfnl_upsell_discount_type'), 'discount_price'); ?>>Discount Price</option>
                        </select>
                    </div>
                </div> -->
                <!-- /field-wrapper -->

                <!-- <div class="wpfnl-field-wrapper">
                    <label><?php echo __('Discount value', 'wpfnl'); ?></label>
                    <div class="wpfnl-fields">
                        <input type="number" name="upsell-discount-value" id="upsell-discount-value" value="<?php echo $this->get_internal_metas_by_key('_wpfnl_upsell_discount_value'); ?>" min="1"/>
                    </div>
                </div> -->
                <!-- /field-wrapper -->

                <!-- <div class="wpfnl-field-wrapper">
                    <label><?php echo __('Original Price', 'wpfnl'); ?></label>
                    <div class="wpfnl-fields">
                        <input type="number" name="upsell-original-price" id="upsell-original-price" value="<?php echo $this->get_internal_metas_by_key('_wpfnl_upsell_product_price'); ?>" readonly />
                    </div>
                </div> -->
                <!-- /field-wrapper -->

                <!-- <div class="wpfnl-field-wrapper">
                    <label><?php echo __('Sell Price', 'wpfnl'); ?></label>
                    <div class="wpfnl-fields">
                        <input type="number" name="upsell-sell-price" id="upsell-sale-price" value="<?php echo $this->get_internal_metas_by_key('_wpfnl_upsell_product_sale_price'); ?>" readonly />
                    </div>
                </div> -->
                <!-- /field-wrapper -->

                <!-- <div class="wpfnl-field-wrapper">
                    <label><?php echo __('Hide Image on Tab and Mobile', 'wpfnl'); ?></label>
                    <div class="wpfnl-fields">
                        <div class="wpfnl-checkbox no-title">
                            <input type="checkbox" name="img-hide-mobile" id="img-hide-mobile" <?php checked($this->get_internal_metas_by_key('_wpfnl_upsell_hide_image'), 'on'); ?>/>
                            <label for="img-hide-mobile"></label>
                        </div>
                    </div>
                </div>
                <!-- /field-wrapper -->
            <!-- </div>  -->
            <!-- /settings-box -->
        </div>
        <!-- /products tab content -->

        <div class="step-settings__single-tab-content upsell-redirect" id="upsell-redirect">
            <div class="wpfnl-box single__settings-box">
                <div class="wpfnl-field-wrapper">
                    <label><?php echo __('Offer - Yes Next Step', 'wpfnl'); ?></label>
                    <div class="wpfnl-fields">
                        <select name="" id="next-step-yes">
                            <?php
                                foreach ($prev_next_options as $group => $value) {
                                    if (count($value)) {?>
                                        <optgroup label="<?php echo ucfirst($group); ?>">
                                            <?php
                                            foreach ($value as $val) { ?>
                                                <option value="<?php echo $val['id']; ?>"  <?php selected($this->get_internal_metas_by_key('_wpfnl_upsell_next_step_yes'), $val['id']); ?>><?php echo $val['title']; ?></option>
                                            <?php }
                                            ?>
                                        </optgroup>
                                    <?php }
                                }
                            ?>
                        </select>
                    </div>
                </div>
                <!-- /wpfnl-field-wrapper -->

                <div class="wpfnl-field-wrapper">
                    <label><?php echo __('Offer - No Next Step', 'wpfnl'); ?></label>
                    <div class="wpfnl-fields">
                        <select name="" id="next-step-no">
                            <?php
                                foreach ($prev_next_options as $group => $value) {
                                    if (count($value)) {?>
                                        <optgroup label="<?php echo ucfirst($group); ?>">
                                            <?php
                                            foreach ($value as $val) { ?>
                                                <option value="<?php echo $val['id']; ?>" <?php selected($this->get_internal_metas_by_key('_wpfnl_upsell_next_step_no'), $val['id']); ?>><?php echo $val['title']; ?></option>
                                            <?php }
                                            ?>
                                        </optgroup>
                                    <?php }
                                }
                            ?>
                        </select>
                    </div>
                </div>
                <!-- /wpfnl-field-wrapper -->

            </div>
            <!-- /wpfnl-settings-box -->
        </div>
        <!-- /conditional redirect tab content -->
    </div>

    <?php
        $back2_edit = add_query_arg(
                                [
                'page' => WPFNL_EDIT_FUNNEL_SLUG,
                'id' => $this->step->get_funnel_id(),
                'step_id' => $this->get_id(),
            ],
                                admin_url('admin.php')
                            );
    ?>

    <div class="settings-content__footer">
        <a href="<?php echo $back2_edit; ?>" class="btn-default back2-edit"><?php echo __(' Back to Step', 'wpfnl'); ?></a>
        <button class="btn-default update" id="wpfnl-update-upsell-settings" data-id="<?php echo $this->get_id(); ?>">
            <?php echo __('Update', 'wpfnl'); ?>
            <span class="wpfnl-loader"></span>
        </button>
        <span class="wpfnl-alert box"></span>
    </div>
</div>
