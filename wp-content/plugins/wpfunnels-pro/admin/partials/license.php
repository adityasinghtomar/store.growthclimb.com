<?php
if(is_multisite()) {
	$license 		= get_option( 'wpfunnels_pro_license_key' );
	$status  		= get_option( 'wpfunnels_pro_license_status' );
	$status_data  	= get_option( 'wpfunnels_pro_licence_data' );
} else {
	$license 		= get_option( 'wpfunnels_pro_license_key' );
	$status  		= get_option( 'wpfunnels_pro_license_status' );
	$status_data  	= get_option( 'wpfunnels_pro_licence_data' );
}
$wp_function =  new \WPFunnelsPro\Wpfnl_Pro_functions();
if( is_callable(array($wp_function, 'encrypt_key')) ){
    $license = \WPFunnelsPro\Wpfnl_Pro_functions::encrypt_key($license);
}

$addon          = \WPFunnelsPro\Addons::getInstance();
$addon_lists    = $addon->get_addons();

?>

<div class="wpfnl wpfnl-license-page">
    <div class="wpfnl-license-wrapper">
        <div class="wpfnl-license-filed">
            <div class="field-area">
                <div class="field-header">
                    <div class="single-field product-title"><?php echo __( 'Plugin / Addons', 'wpfnl-pro' );?></div>
                    <div class="single-field input-field"><?php echo __( 'License Input', 'wpfnl-pro' );?></div>
                    <div class="single-field btn-area"></div>
                </div>

                <form name="wpfnl-license" id="wpfnl-license" action="options.php" method="post">
                    <div class="input-field-area">
                        <div class="single-field product-title">
							<span class="addon-icon">
								<svg width="38" height="28" viewBox="0 0 38 28" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M7.01532 18H31.9847L34 11H5L7.01532 18Z" fill="#EE8134"/>
									<path d="M11.9621 27.2975C12.0923 27.7154 12.4792 28 12.9169 28H26.0831C26.5208 28 26.9077 27.7154 27.0379 27.2975L29 21H10L11.9621 27.2975Z" fill="#6E42D3"/>
									<path d="M37.8161 0.65986C37.61 0.247888 37.2609 0 36.8867 0H1.11326C0.739128 0 0.390003 0.247888 0.183972 0.65986C-0.0220592 1.07193 -0.0573873 1.59277 0.0898627 2.04655L1.69781 7H36.3022L37.9102 2.04655C38.0574 1.59287 38.022 1.07193 37.8161 0.65986Z" fill="#6E42D3"/>
								</svg>
							</span>
                            <div class="icon-info">
                                <h3><?php echo __( 'WPFunnels', 'wpfnl-pro' );?></h3>
                            </div>
                        </div>

                        <div class="single-field input-field">
                            <?php
                            
                            if( is_callable(array($wp_function, 'encrypt_key')) ){
                                $license = \WPFunnelsPro\Wpfnl_Pro_functions::encrypt_key($license);
                            }
                            ?>
                            <input id="wpfunnels_license_key" name="wpfunnels_license_key" type="password" placeholder="<?php esc_attr_e( 'Enter your license code', 'wpfnl-pro' ) ?>" value="<?php esc_attr_e( $license ); ?>"/>

                            <span class="license-status">
								<?php
                                if( 'active' === $status ) {
                                    $start_date = isset($status_data['start_date']) ? $status_data['start_date'] : '';
                                    $end_date 	= isset($status_data['end_date']) ? $status_data['end_date'] : '';
                                    if ( $end_date ) {
                                        echo sprintf( '%s %s', __('Your license key will be expired on ', 'wpfnl-pro'), $end_date );
                                    }
                                }
                                ?>
							</span>
                        </div>

                        <div class="single-field btn-area">
                            <?php if( $status !== false && $status == 'active' ) { ?>
                                <?php wp_nonce_field( 'wpfunnels_pro_licensing_nonce', 'wpfunnels_pro_licensing_nonce' ); ?>
                                <input type="submit" class="btn-default" name="wpfunnels_pro_license_deactivate" value="<?php _e('Deactivate License', 'wpfnl'); ?>" required/>
                            <?php } else {
                                wp_nonce_field( 'wpfunnels_pro_licensing_nonce', 'wpfunnels_pro_licensing_nonce' ); ?>
                                <input type="submit" class="btn-default" name="wpfunnels_pro_license_activate" value="<?php _e('Activate License', 'wpfnl'); ?>"/>
                            <?php } ?>
                        </div>
                    </div>
                </form>

                <?php if ( is_array($addon_lists) && !empty($addon_lists) ) { ?>
                    <div class="addons-license">
                        <?php foreach ( $addon_lists as $key => $addon ) { ?>
                            <form name="wpfnl-<?php echo $addon['key'] ?>-license" id="wpfnl-<?php echo $addon['key'] ?>-license" action="options.php" method="post" style="padding-top:15px">
                                <div class="single-addons <?php echo ($addon['plugin_status'] == 'installed' && $addon['key'] == 'lms') || ($addon['plugin_status'] == 'installed' && $addon['key'] == 'gbf') || ($addon['plugin_status'] == 'installed' && $addon['key'] == 'integrations') || ($addon['plugin_status'] === 'active' && $addon['key'] != 'lms' && $addon['key'] != 'integrations' )  ? '' : 'not-get-addons'; ?>">
                                    <div class="input-field-area">
                                        <div class="single-field product-title">
                                        <span class="addon-icon">
                                            <?php 
                                                if( !empty($addon['icon']) ){
                                                    include $addon['icon']; 
                                                }  
                                            ?>
                                        </span>
                                            <div class="icon-info">
                                                <h3><?php echo !empty($addon['name']) ? $addon['name'] : ''; ?></h3>
                                                <?php
                                                    if( $addon['plugin_status'] !== 'installed' ){
                                                        echo '<span class="license-status">'.$addon['description'].'</span>';
                                                    }
                                                ?>
                                            </div>
                                        </div>
                                        <?php
                                       
                                        if( $addon['plugin_status'] === 'installed' ) {
                                            
                                            ?>
                                            <div class="single-field input-field">
                                                <?php if( $addon['license_status'] == 'active' ) {
                                                  
                                                    ?>
                                                    <input id="wpfunnels_pro_<?php echo $addon['key'] ?>_license_key" name="wpfunnels_pro_<?php echo $addon['key'] ?>_license_key" type="password" placeholder="<?php echo __('Enter your license code', 'wpfnl-pro'); ?>" value="<?php esc_attr_e( $addon['license_key'] ); ?>"/>
                                                <?php } else { 
                                                    ?>
                                                    <input id="wpfunnels_pro_<?php echo $addon['key'] ?>_license_key" name="wpfunnels_pro_<?php echo $addon['key'] ?>_license_key" type="password" placeholder="<?php echo __('Enter your license code', 'wpfnl-pro'); ?>" value="<?php esc_attr_e( $addon['license_key'] ); ?>"/>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                        <div class="single-field btn-area">
                                            <?php if($addon['plugin_status'] === 'installed' ) { 
                                                
                                                ?>

                                                <?php if( $addon['key'] === 'lms' || $addon['key'] === 'gbf' || $addon['key'] === 'integrations' ) { if( $addon['license_status'] == 'active' ) { ?>
                                                    <?php wp_nonce_field( 'wpfunnels_pro_'.$addon['key'].'_licensing_nonce', 'wpfunnels_pro_'.$addon['key'].'_licensing_nonce' ); ?>
                                                    <input type="submit" class="btn-default" name="wpfunnels_pro_<?php echo $addon['key']; ?>_license_deactivate" value="<?php _e('Deactivate License', 'wpfnl'); ?>" required/>
                                                <?php } else {
                                                    
                                                    wp_nonce_field( 'wpfunnels_pro_'.$addon['key'].'_licensing_nonce', 'wpfunnels_pro_'.$addon['key'].'_licensing_nonce' ); ?>
                                                    <input type="submit" class="btn-default" name="wpfunnels_pro_<?php echo $addon['key']; ?>_license_activate" value="<?php _e('Activate License', 'wpfnl'); ?>"/>
                                                <?php } }else{?>
                                               

                                                <?php if( $addon['license_status'] == 'active' ) { ?>
                                                    <?php wp_nonce_field( "wpfunnels_pro_{$addon['key']}_licensing_nonce", "wpfunnels_pro_{$addon['key']}_licensing_nonce" ); ?>
                                                    <input type="submit" class="btn-default" name="wpfunnels_pro_<?php echo $addon['key']; ?>_license_deactivate" value="<?php _e('Deactivate License', 'wpfnl'); ?>" required/>
                                                <?php } else {
                                                        if( 'gbf' === $addon['key'] ){
                                                            ?>
                                                            <a target="_blank" href="<?php echo $addon['btn_link']; ?>" class="btn-default"><?php _e('Activate License', 'wpfnl'); ?></a><?php
                                                        }else{
                                                            wp_nonce_field( "wpfunnels_pro_{$addon['key']}_licensing_nonce", "wpfunnels_pro_{$addon['key']}_licensing_nonce" ); ?>
                                                            <input type="submit" class="btn-default" name="wpfunnels_pro_<?php echo $addon['key']; ?>_license_activate" value="<?php _e('Activate License', 'wpfnl'); ?>"/>
                                                        }
                                                <?php  }}}?>
                                            <?php } else { 
                                                
                                                ?>
                                                <a target="_blank" href="<?php echo $addon['btn_link']; ?>" class="btn-default"><?php echo $addon['btn_txt']; ?></a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </form>

                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="promo-text-area">
            <div class="single-area manage-license-area">
                <span class="logo">
                    <svg width="38" height="28" viewBox="0 0 38 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7.01532 18H31.9847L34 11H5L7.01532 18Z" fill="#EE8134"/>
                        <path d="M11.9621 27.2975C12.0923 27.7154 12.4792 28 12.9169 28H26.0831C26.5208 28 26.9077 27.7154 27.0379 27.2975L29 21H10L11.9621 27.2975Z" fill="#6E42D3"/>
                        <path d="M37.8161 0.65986C37.61 0.247888 37.2609 0 36.8867 0H1.11326C0.739128 0 0.390003 0.247888 0.183972 0.65986C-0.0220592 1.07193 -0.0573873 1.59277 0.0898627 2.04655L1.69781 7H36.3022L37.9102 2.04655C38.0574 1.59287 38.022 1.07193 37.8161 0.65986Z" fill="#6E42D3"/>
                    </svg>
                </span>

                <h4 class="title"><?php echo __('Manage license', 'wpfnl'); ?></h4>
                <p><?php echo __('Manage your license and subscriptions in your personalized user account.', 'wpfnl'); ?></p>
               
                <a href="https://useraccount.getwpfunnels.com/orders/" class="btn-default" target="_blank"><?php echo __('manage license', 'wpfnl'); ?></a>
            </div>
        </div>

        <!-- /promo-text-area -->
    </div>

    <div class="cl-doc-row">
        <div class="single-col manage-license">
            <span class="icon">
                <svg width="38" height="28" viewBox="0 0 38 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7.01532 18H31.9847L34 11H5L7.01532 18Z" fill="#EE8134"/>
                    <path d="M11.9621 27.2975C12.0923 27.7154 12.4792 28 12.9169 28H26.0831C26.5208 28 26.9077 27.7154 27.0379 27.2975L29 21H10L11.9621 27.2975Z" fill="#6E42D3"/>
                    <path d="M37.8161 0.65986C37.61 0.247888 37.2609 0 36.8867 0H1.11326C0.739128 0 0.390003 0.247888 0.183972 0.65986C-0.0220592 1.07193 -0.0573873 1.59277 0.0898627 2.04655L1.69781 7H36.3022L37.9102 2.04655C38.0574 1.59287 38.022 1.07193 37.8161 0.65986Z" fill="#6E42D3"/>
                </svg>
            </span>
            <h4 class="title"><?php echo __('Manage license', 'wpfnl'); ?></h4>
            <p><?php echo __('Manage your license and subscriptions in your personalized user account.', 'wpfnl'); ?></p>
            <a href="https://useraccount.getwpfunnels.com/orders/" class="btn-default" target="_blank"><?php echo __('manage license', 'wpfnl'); ?></a>
        </div>

        <div class="single-col">
            <span class="icon">
				<?php include WPFNL_DIR . '/admin/partials/icons/doc-icon2.php'; ?>
            </span>
            <h4 class="title"><?php echo __('Documentation', 'wpfnl'); ?></h4>
            <p><?php echo __('Get detailed guide and documentation on WPFunnels and create highly converting sales funnels easily.', 'wpfnl'); ?></p>
            <a href="https://getwpfunnels.com/docs/" class="btn-default" target="_blank"><?php echo __('Documentation', 'wpfnl'); ?></a>
        </div>

        <div class="single-col">
            <span class="icon">
				<?php include WPFNL_DIR . '/admin/partials/icons/support-icon.php'; ?>
            </span>
            <h4 class="title"><?php echo __('Support', 'wpfnl'); ?></h4>
            <p><?php echo __('Can’t find solution with our documentation? Just post a ticket. Our professional team is here to solve your problems.', 'wpfnl'); ?></p>
            <a href="https://wordpress.org/support/plugin/wpfunnels/" target="_blank" class="btn-default"><?php echo __('Post A Ticket', 'wpfnl'); ?></a>
        </div>

        <div class="single-col">
            <span class="icon">
				<?php include WPFNL_DIR . '/admin/partials/icons/heart-icon.php'; ?>
            </span>
            <h4 class="title"><?php echo __('Show Your Love', 'wpfnl'); ?></h4>
            <p><?php echo __('We love to have you in WPFunnels family. Take your 2 minutes to review  and spread the love to encourage us to keep it going.', 'wpfnl'); ?></p>
            <a href="https://wordpress.org/plugins/wpfunnels/#reviews" class="btn-default"  target="_blank"><?php echo __('Leave a Review', 'wpfnl'); ?></a>
        </div>
    </div>
</div>
