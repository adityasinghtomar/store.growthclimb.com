<?php
/**
 * Advance settings view
 * 
 * @package
 */
?>
<div class="basic-tools-field">
	<h4 class="settings-title"> <?php esc_html_e('Basic Tools', 'wpfnl'); ?> </h4>
	<div class="wpfnl-box">
		<div class="wpfnl-field-wrapper">
			<label>
				<?php esc_html_e( 'Remove WPF Transient Cache', 'wpfnl' ); ?>
				<span class="wpfnl-tooltip">
					<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
					<p><?php esc_html_e('If you are facing issues such as not getting plugin updates or license not working, clear the transient cache and try again.', 'wpfnl'); ?></p>
				</span>
			</label>

			<div class="wpfnl-fields">
				<button class="btn-default clear-template" id="clear-transients">
					<span class="sync-icon"><?php require WPFNL_DIR . '/admin/partials/icons/sync-icon.php'; ?></span>
					<span class="check-icon"><?php require WPFNL_DIR . '/admin/partials/icons/check-icon.php'; ?></span>
					<?php esc_html_e('Delete transients', 'wpfnl'); ?>
				</button>
				<span class="wpfnl-alert"></span>
			</div>

		</div>

		<?php if( apply_filters( 'wpfunnels/is_wpfnl_pro_active', false ) ){?>
			<div class="wpfnl-field-wrapper analytics-stats">
				<label><?php esc_html_e('Disable Analytics Tracking For', 'wpfnl'); ?>
					<span class="wpfnl-tooltip">
						<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
						<p><?php esc_html_e('If you want WPFunnels not to track traffic, conversion, & revenue count for Analytics from certain user roles in your site, then you may do so using these options.', 'wpfnl'); ?></p>
					</span>
				</label>

				<div class="wpfnl-fields">
					<?php foreach( $this->user_roles as $role ) { ?>
						<span class="wpfnl-checkbox">
							<input type="checkbox" name="analytics-role[]"  id="<?php echo $role; ?>-analytics-role" data-role="<?php echo $role; ?>" <?php if(isset($this->general_settings['disable_analytics'][$role])){checked( $this->general_settings['disable_analytics'][$role], 'true' );} ?> />
							<label for="<?php echo $role; ?>-analytics-role"><?php echo str_replace("_"," ",ucfirst($role)); ?></label>
						</span>
					<?php } ?>
				</div>
			</div>
		<?php } ?>

		<div class="wpfnl-field-wrapper analytics-stats">
			<label><?php esc_html_e('Disable Theme Styles in Funnel Pages', 'wpfnl'); ?>
				<span class="wpfnl-tooltip">
					<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
					<p><?php esc_html_e('When editing funnel pages, Enabling this option will mean the default theme styles will not be loaded when editing funnel pages and rather load the default style by WPFunnels.', 'wpfnl'); ?></p>
				</span>
			</label>

			<div class="wpfnl-fields">
				<span class="wpfnl-checkbox no-title">
					<input type="checkbox" name="disable-theme-style"  id="disable-theme-style" <?php if( $this->general_settings['disable_theme_style'] == 'on' ){ echo 'checked';} ?> />
					<label for="disable-theme-style"></label>
				</span>
			</div>
		</div>

		<div class="wpfnl-field-wrapper analytics-stats">
			<label><?php esc_html_e('Enable log status', 'wpfnl'); ?>
				<span class="wpfnl-tooltip">
					<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
					<p><?php esc_html_e('Enable logger status to save any log', 'wpfnl'); ?></p>
				</span>
			</label>

			<div class="wpfnl-fields">
				<span class="wpfnl-checkbox no-title">
					<input type="checkbox" name="enable-log-status"  id="enable-log-status" <?php if( $this->general_settings['enable_log_status'] == 'on' ){ echo 'checked';} ?> />
					<label for="enable-log-status"></label>
				</span>
			</div>
		</div>

		<!-- /field-wrapper -->
	</div>
</div>

<!-- Role management settings -->

<!-- End role management settings -->

<!-- rollback settings -->
<div class="wpfnl-recaptcha-setting basic-tools-field">
	<h4 class="settings-title"> <?php esc_html_e('reCAPTCHA Settings', 'wpfnl'); ?> </h4>
	<div class="wpfnl-box">

		<div class="wpfnl-field-wrapper analytics-stats">
			<label><?php esc_html_e('Connect reCAPTCHA (v3)', 'wpfnl'); ?></label>
			<div class="wpfnl-fields">
					<span class="wpfnl-checkbox no-title">
                        <input type="checkbox" name="wpfnl-recapcha-enable"  id="recapcha-pixel-enable" <?php if($this->recaptcha_settings['enable_recaptcha'] == 'on'){echo 'checked'; } ?>/>
                        <label for="recapcha-pixel-enable"></label>
                    </span>
			</div>
		</div>
		<div id="wpfnl-recapcha">
			<div class="wpfnl-field-wrapper recaptcha-tracking" id="recaptcha-tracking">
				<label>
					<?php esc_html_e('reCAPTCHA Site Key', 'wpfnl'); ?>
					<span class="wpfnl-tooltip">
						<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
						<p><?php esc_html_e('Collect the Site Key from your Google reCAPTCHA site settings under the reCAPTCHA keys.', 'wpfnl'); ?></p>
					</span>
				</label>
				<div class="wpfnl-fields">
					<input type="text" name="wpfnl-recapcha-site-key" id="wpfnl-recapcha-site-key" value="<?php echo isset($this->recaptcha_settings['recaptcha_site_key']) ? $this->recaptcha_settings['recaptcha_site_key']: '' ; ?>" />
				</div>
			</div>
			<div class="wpfnl-field-wrapper analytics-stats">
				<label>
					<?php esc_html_e('reCAPTCHA Site Secret', 'wpfnl'); ?>
					<span class="wpfnl-tooltip">
						<?php require WPFNL_DIR . '/admin/partials/icons/question-tooltip-icon.php'; ?>
						<p><?php esc_html_e('Collect the Secrect Key from your Google reCAPTCHA site settings under the reCAPTCHA keys.', 'wpfnl'); ?></p>
					</span>
				</label>
				<div class="wpfnl-fields">
					<input type="text" name="wpfnl-recapcha-site-secret" id="wpfnl-recapcha-site-secret" value="<?php echo isset($this->recaptcha_settings['recaptcha_site_secret']) ? $this->recaptcha_settings['recaptcha_site_secret']: '' ; ?>" />
				</div>
			</div>
		</div>
	</div>
</div>

<!-- rollback settings -->
<div class="rollback-field">
	<h4 class="settings-title"> <?php esc_html_e('Rollback Settings', 'wpfnl'); ?> </h4>
	<div class="wpfnl-box">
		<div class="wpfnl-field-wrapper">
			<label><?php esc_html_e('Current Version', 'wpfnl'); ?></label>
			<div class="wpfnl-fields">
				<b>v<?php echo WPFNL_VERSION; ?></b>
			</div>
		</div>
		<!-- /field-wrapper -->

		<div class="wpfnl-field-wrapper wpfnl-align-top">
			<label><?php esc_html_e('Rollback to older Version', 'wpfnl'); ?></label>
			<div class="wpfnl-fields">
				<select name="wpfnl-rollback" id="wpfnl-rollback">
					<?php
						foreach ( $rollback_versions as $version ) {
							echo "<option value='{$version}'>$version</option>";
						}
					?>
				</select>
				<?php
					echo sprintf(
						'<a data-placeholder-text="%s v{VERSION}" href="#" data-placeholder-url="%s" class="wpfnl-button-spinner wpfnl-rollback-button btn-default">%s</a>',
                        __( 'Reinstall', 'wpfnl' ),
                        wp_nonce_url( admin_url( 'admin-post.php?action=wpfunnels_rollback&version=VERSION' ), 'wpfunnels_rollback' ),
						__( 'Reinstall', 'wpfnl' )
					);
				?>
				<span class="hints wpfnl-error">
                    <?php
                    _e(
                            sprintf(
                                    '%sWarning:%s Please backup your database before rolling back to an older version of the plugin.',
                        '<b>', '</b>'
                    ), 'wpfnl' );
                    ?>
				</span>
			</div>
		</div>
		<!-- /field-wrapper -->
	</div>
</div>
