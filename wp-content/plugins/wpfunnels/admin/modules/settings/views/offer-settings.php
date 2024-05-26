<?php
/**
 * View offer settings
 * 
 * @package
 */

	$offer_settings = \WPFunnels\Wpfnl_functions::get_offer_settings();
?>

<h4 class="settings-title"><?php esc_html_e('Order Management For Upsell/Downsell Offers', 'wpfnl'); ?></h4>
<div class="wpfnl-box">
    <div class="wpfnl-field-wrapper">
        <label class="has-tooltip">
            <?php esc_html_e('Create a new child order for every accepted Upsell/Downsell offer', 'wpfnl'); ?>
            <span class="wpfnl-tooltip">
                <?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
                <p><?php esc_html_e('Enabling this will create separate orders for every post-purchase offers you make.', 'wpfnl'); ?></p>
            </span>
        </label>
        <div class="wpfnl-fields">
            <div class="wpfnl-radiobtn no-title">
                <input type="radio" name="offer-orders" id="wpfunnels-offer-child-order" value="child-order" <?php checked( $offer_settings['offer_orders'], 'child-order' ) ?>/>
                <label for="wpfunnels-offer-child-order"></label>
            </div>
        </div>
    </div>
    <!-- /field-wrapper -->

    <div class="wpfnl-field-wrapper">
        <label class="has-tooltip">
            <?php esc_html_e('Add all accepted offers (Upsell/Downsell) to the main order', 'wpfnl'); ?>
            <span class="wpfnl-tooltip">
                <?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
                <p><?php esc_html_e('All purchases including main product, order bump, upsell(s), and downsell(s), will be included as part of a single order in WooCommerce.', 'wpfnl'); ?></p>
            </span>
        </label>
        <div class="wpfnl-fields">
            <div class="wpfnl-radiobtn no-title">
                <input type="radio" name="offer-orders" id="wpfunnels-offer-main-order" value="main-order" <?php checked( $offer_settings['offer_orders'], 'main-order' ) ?>/>
                <label for="wpfunnels-offer-main-order"></label>
            </div>
        </div>
    </div>
    <!-- /field-wrapper -->
</div>

<h4 class="settings-title"><?php esc_html_e('Payment Management For Upsell/Downsell Offers', 'wpfnl'); ?></h4>
<div class="wpfnl-box">
    <div class="wpfnl-field-wrapper">
        <label class="has-tooltip">
            <?php esc_html_e('Only show supported payment gateways during funnel checkout', 'wpfnl'); ?>
            <span class="wpfnl-tooltip">
                <?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
                <p><?php esc_html_e('You may have several payment gateways in your site. But since we have limited supported payment gateways for post purchase offers, you can use this option to only view supported payment gateways during the funnel checkout page. This means, you do not have to disable other payment gateways for the funnel.', 'wpfnl'); ?></p>
            </span>
        </label>
        <div class="wpfnl-fields">
                <span class="wpfnl-checkbox no-title">
                    <input type="checkbox" name="wpfnl-show-supported-payment-gateway"  id="wpfnl-show-supported-payment-gateway" <?php if($this->offer_settings['show_supported_payment_gateway'] == 'on'){echo 'checked'; } ?>/>
                    <label for="wpfnl-show-supported-payment-gateway"></label>
                </span>
        </div>
    </div>
    <!-- /field-wrapper -->

    <div class="wpfnl-field-wrapper">
        <label class="has-tooltip">
            <?php esc_html_e('Skip upsell/downsell for unsupported payment gateways', 'wpfnl'); ?>
            <span class="wpfnl-tooltip">
                <?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
                <p><?php esc_html_e('Use this option so that if a buyer chooses to use a payment option that is not supported by WPFunnels, they will not get the post-purchase offers. This means, all payment options will be shown at the checkout page.', 'wpfnl'); ?></p>
            </span>
        </label>
        <div class="wpfnl-fields">
                <span class="wpfnl-checkbox no-title">
                    <input type="checkbox" name="wpfnl-skip-offer-step"  id="wpfnl-skip-offer-step" <?php if($this->offer_settings['skip_offer_step'] == 'on'){echo 'checked'; } ?>/>
                    <label for="wpfnl-skip-offer-step"></label>
                </span>
        </div>
    </div>


    <div class="wpfnl-field-wrapper">
        <label class="has-tooltip">
            <?php esc_html_e('Skip upsell/downsell for free products', 'wpfnl'); ?>
            <span class="wpfnl-tooltip">
                <?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
                <p><?php esc_html_e("Enable this option in case you want to use a $0 product as the main funnel product. In this case, Upsell and downsell steps will be skipped since they won't work without a successful payment during checkout.", 'wpfnl'); ?></p>
            </span>
        </label>
        <div class="wpfnl-fields">
                <span class="wpfnl-checkbox no-title">
                    <input type="checkbox" name="wpfnl-skip-offer-step-for-free"  id="wpfnl-skip-offer-step-for-free" <?php if($this->offer_settings['skip_offer_step_for_free'] == 'on'){echo 'checked'; } ?>/>
                    <label for="wpfnl-skip-offer-step-for-free"></label>
                </span>
        </div>
    </div>
    <!-- /field-wrapper -->

</div>
<!-- /settings-box -->
