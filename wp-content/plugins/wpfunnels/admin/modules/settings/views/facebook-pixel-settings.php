<?php
/**
 * View FB pixel settings
 * 
 * @package
 */
?>
<div class="wpfnl-box">
	<!-- /field-wrapper -->
	<?php if( is_plugin_active( 'wpfunnels-pro/wpfnl-pro.php' ) ){?>
		<div class="wpfnl-field-wrapper analytics-stats">
			<label><?php esc_html_e('Track Events Using Facebook Pixel', 'wpfnl'); ?></label>
			<div class="wpfnl-fields">
					<span class="wpfnl-checkbox no-title">
                        <input type="checkbox" name="wpfnl-facebook-pixel-enable"  id="facebook-pixel-enable" <?php if($this->facebook_pixel_settings['enable_fb_pixel'] == 'on'){echo 'checked'; } ?>/>
                        <label for="facebook-pixel-enable"></label>
                    </span>
			</div>
		</div>
		<div id="wpfnl-facebook-pixel">
			<div class="wpfnl-field-wrapper facebook-tracking-code" id="facebook-tracking-code">
				<label>
					<?php esc_html_e('Facebook Pixel ID', 'wpfnl'); ?>
					<span class="wpfnl-tooltip">
						<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
						<p><?php esc_html_e('Enter your Facebook Pixel ID.', 'wpfnl'); ?></p>
					</span>
				</label>
				<div class="wpfnl-fields">
					<input type="text" name="wpfnl-facebook-tracking-code" id="wpfnl-facebook-tracking-code" value="<?php echo isset($this->facebook_pixel_settings['facebook_pixel_id']) ? sanitize_text_field($this->facebook_pixel_settings['facebook_pixel_id']) : '' ; ?>" />
				</div>
			</div>
			<div class="wpfnl-field-wrapper analytics-stats">
				<label>
					<?php esc_html_e('Facebook Pixel Events', 'wpfnl'); ?>
					<span class="wpfnl-tooltip">
						<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
						<p><?php esc_html_e('Choose what events to track in your funnels via Facebook Pixel.', 'wpfnl'); ?></p>
					</span>
				</label>
				<div class="wpfnl-fields">
					<?php foreach( $this->facebook_pixel_events as $key => $events ) { ?>
						<span class="wpfnl-checkbox">
                        <input type="checkbox" name="wpfnl-facebook_pixel_events[]"  id="<?php echo $key; ?>-facebook_pixel_events" data-role="<?php echo $key; ?>"
							<?php if(isset($this->facebook_pixel_settings['facebook_tracking_events'][$key])){checked( $this->facebook_pixel_settings['facebook_tracking_events'][$key], 'true' );} ?>
						/>
                        <label for="<?php echo $key; ?>-facebook_pixel_events"><?php echo ucfirst($events); ?></label>
                    </span>
					<?php } ?>
				</div>
			</div>
		</div>

	<?php } ?>

</div>
<!-- /settings-box -->