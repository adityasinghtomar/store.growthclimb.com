<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WOO_Settings' ) ) :

	final class WPSC_WOO_Settings {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// woo icon.
			add_filter( 'wpsc_icons', array( __CLASS__, 'woo_icons' ) );

			// woo tab in settings.
			add_filter( 'wpsc_settings_page_sections', array( __CLASS__, 'woo_setting_tab' ) );

			// woo settings.
			add_action( 'wp_ajax_wpsc_get_woo_settings', array( __CLASS__, 'get_woo_settings' ) );
			add_action( 'wp_ajax_wpsc_set_woo_settings', array( __CLASS__, 'save_settings' ) );
			add_action( 'wp_ajax_wpsc_reset_woo_settings', array( __CLASS__, 'reset_settings' ) );
		}

		/**
		 * Reset settings
		 *
		 * @return void
		 */
		public static function reset() {

			$tab_label = esc_attr__( 'Support Tickets', 'wpsc-woo' );
			$help_label = esc_attr__( 'Help', 'wpsc-woo' );
			$product_help_label = esc_attr__( 'Help', 'wpsc-woo' );
			$product_help_tab_label = esc_attr__( 'Help', 'wpsc-woo' );
			$settings = apply_filters(
				'wpsc_woo_settings',
				array(
					'dashboard-support-tab'       => 1,
					'dashboard-support-tab-label' => $tab_label,
					'order-help-button'           => 1,
					'order-help-button-label'     => $help_label,
					'product-help-button'         => 1,
					'product-help-button-label'   => $product_help_label,
					'product-help-tab'            => 1,
					'product-help-tab-label'      => $product_help_tab_label,
					'dashboard-as-ticket-url'     => 0,
				)
			);
			update_option( 'wpsc-woo-settings', $settings );

			// string traslations.
			WPSC_Translations::add( 'wpsc-woo-dashboard-tab-label', $tab_label );
			WPSC_Translations::add( 'wpsc-woo-order-help-button-label', $help_label );
			WPSC_Translations::add( 'wpsc-woo-product-help-button-label', $product_help_label );
			WPSC_Translations::add( 'wpsc-woo-product-help-tab-label', $product_help_tab_label );
		}

		/**
		 * Add icons to library
		 *
		 * @param array $icons - icon name.
		 * @return array
		 */
		public static function woo_icons( $icons ) {

			$icons['shopping-cart'] = file_get_contents( WPSC_WOO_ABSPATH . 'asset/icons/shopping-cart.svg' ); //phpcs:ignore
			return $icons;
		}

		/**
		 * Woo Settings tab
		 *
		 * @param array $sections - sections array.
		 * @return array
		 */
		public static function woo_setting_tab( $sections ) {

			$sections['woo'] = array(
				'slug'     => 'woo_settings',
				'icon'     => 'shopping-cart',
				'label'    => esc_attr( wpsc__( 'WooCommerce', 'woocommerce' ) ),
				'callback' => 'wpsc_get_woo_settings',
			);
			return $sections;
		}

		/**
		 * Get Woo settings
		 *
		 * @return void
		 */
		public static function get_woo_settings() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$woo_settings = get_option( 'wpsc-woo-settings', array() );?>

			<div class="wpsc-setting-header">
				<h2><?php echo esc_attr( wpsc__( 'WooCommerce', 'woocommerce' ) ); ?></h2>
			</div>
			<div class="wpsc-setting-section-body">
				<form action="#" onsubmit="return false;" class="wpsc-frm-woo-settings">
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php esc_attr_e( 'Dashboard support tab', 'wpsc-woo' ); ?>
							</label>
						</div>
						<select name="dashboard-support-tab">
							<option <?php selected( $woo_settings['dashboard-support-tab'], 1 ); ?> value="1"><?php echo esc_attr( wpsc__( 'Enabled', 'supportcandy' ) ); ?></option>
							<option <?php selected( $woo_settings['dashboard-support-tab'], 0 ); ?> value="0"><?php echo esc_attr( wpsc__( 'Disabled', 'supportcandy' ) ); ?></option>
						</select>
					</div>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php esc_attr_e( 'Dashboard support tab label', 'wpsc-woo' ); ?>
							</label>
						</div>
						<input type="text" name="dashboard-support-tab-label" value="<?php echo esc_attr( WPSC_Translations::get( 'wpsc-woo-dashboard-tab-label', $woo_settings['dashboard-support-tab-label'] ) ); ?>" autocomplete="off">
					</div>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php esc_attr_e( 'Order help button', 'wpsc-woo' ); ?>
							</label>
						</div>
						<select name="order-help-button">
							<option <?php selected( $woo_settings['order-help-button'], 1 ); ?> value="1"><?php echo esc_attr( wpsc__( 'Enabled', 'supportcandy' ) ); ?></option>
							<option <?php selected( $woo_settings['order-help-button'], 0 ); ?> value="0"><?php echo esc_attr( wpsc__( 'Disabled', 'supportcandy' ) ); ?></option>
						</select>
					</div>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php esc_attr_e( 'Order help button label', 'wpsc-woo' ); ?>
							</label>
						</div>
						<input type="text" name="order-help-button-label" value="<?php echo esc_attr( WPSC_Translations::get( 'wpsc-woo-order-help-button-label', $woo_settings['order-help-button-label'] ) ); ?>" autocomplete="off">
					</div>

					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php esc_attr_e( 'Product help button', 'wpsc-woo' ); ?>
							</label>
						</div>
						<select name="product-help-button">
							<option <?php selected( $woo_settings['product-help-button'], 1 ); ?> value="1"><?php echo esc_attr( wpsc__( 'Enabled', 'supportcandy' ) ); ?></option>
							<option <?php selected( $woo_settings['product-help-button'], 0 ); ?> value="0"><?php echo esc_attr( wpsc__( 'Disabled', 'supportcandy' ) ); ?></option>
						</select>
					</div>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php esc_attr_e( 'Product help button label', 'wpsc-woo' ); ?>
							</label>
						</div>
						<input type="text" name="product-help-button-label" value="<?php echo esc_attr( WPSC_Translations::get( 'wpsc-woo-product-help-button-label', $woo_settings['product-help-button-label'] ) ); ?>" autocomplete="off">
					</div>

					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php esc_attr_e( 'Product help tab', 'wpsc-woo' ); ?>
							</label>
						</div>
						<select name="product-help-tab">
							<option <?php selected( $woo_settings['product-help-tab'], 1 ); ?> value="1"><?php echo esc_attr( wpsc__( 'Enabled', 'supportcandy' ) ); ?></option>
							<option <?php selected( $woo_settings['product-help-tab'], 0 ); ?> value="0"><?php echo esc_attr( wpsc__( 'Disabled', 'supportcandy' ) ); ?></option>
						</select>
					</div>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php esc_attr_e( 'Product help tab label', 'wpsc-woo' ); ?>
							</label>
						</div>
						<input type="text" name="product-help-tab-label" value="<?php echo esc_attr( WPSC_Translations::get( 'wpsc-woo-product-help-tab-label', $woo_settings['product-help-tab-label'] ) ); ?>" autocomplete="off">
					</div>

					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php esc_attr_e( 'Use woo dashboad as ticket URL', 'wpsc-woo' ); ?>
							</label>
						</div>
						<select name="dashboard-as-ticket-url">
							<option <?php selected( $woo_settings['dashboard-as-ticket-url'], 1 ); ?> value="1"><?php echo esc_attr( wpsc__( 'Enabled', 'supportcandy' ) ); ?></option>
							<option <?php selected( $woo_settings['dashboard-as-ticket-url'], 0 ); ?> value="0"><?php echo esc_attr( wpsc__( 'Disabled', 'supportcandy' ) ); ?></option>
						</select>
					</div>
					<?php do_action( 'wpsc_get_add_woo_settings' ); ?>
					<input type="hidden" name="action" value="wpsc_set_woo_settings">
					<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_woo_settings' ) ); ?>">
				</form>    
				<div class="setting-footer-actions">
					<button 
						class="wpsc-button normal primary margin-right"
						onclick="wpsc_set_woo_settings(this)">
						<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
					<button 
						class="wpsc-button normal secondary"
						onclick="wpsc_reset_woo_settings(this, '<?php echo esc_attr( wp_create_nonce( 'wpsc_reset_woo_settings' ) ); ?>');">
						<?php echo esc_attr( wpsc__( 'Reset default', 'supportcandy' ) ); ?></button>
				</div>
			</div>
			<?php
			wp_die();
		}

		/**
		 * Save woo settings
		 *
		 * @return void
		 */
		public static function save_settings() {

			if ( check_ajax_referer( 'wpsc_set_woo_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$woo_settings = apply_filters(
				'wpsc_set_woo_settings',
				array(
					'dashboard-support-tab'       => isset( $_POST['dashboard-support-tab'] ) ? intval( $_POST['dashboard-support-tab'] ) : 1,
					'dashboard-support-tab-label' => ! empty( $_POST['dashboard-support-tab-label'] ) ? sanitize_text_field( wp_unslash( $_POST['dashboard-support-tab-label'] ) ) : 'Support Tickets',
					'order-help-button'           => isset( $_POST['order-help-button'] ) ? intval( $_POST['order-help-button'] ) : 1,
					'order-help-button-label'     => ! empty( $_POST['order-help-button-label'] ) ? sanitize_text_field( wp_unslash( $_POST['order-help-button-label'] ) ) : 'Help',
					'product-help-button'         => isset( $_POST['product-help-button'] ) ? intval( $_POST['product-help-button'] ) : 1,
					'product-help-button-label'   => ! empty( $_POST['product-help-button-label'] ) ? sanitize_text_field( wp_unslash( $_POST['product-help-button-label'] ) ) : 'Help',
					'product-help-tab'            => isset( $_POST['product-help-tab'] ) ? intval( $_POST['product-help-tab'] ) : 1,
					'product-help-tab-label'      => ! empty( $_POST['product-help-tab-label'] ) ? sanitize_text_field( wp_unslash( $_POST['product-help-tab-label'] ) ) : 'Help',
					'dashboard-as-ticket-url'     => isset( $_POST['dashboard-as-ticket-url'] ) ? intval( $_POST['dashboard-as-ticket-url'] ) : 0,
					'default-order-field'         => isset( $_POST['default-order-field'] ) ? intval( $_POST['default-order-field'] ) : '',
				)
			);
			update_option( 'wpsc-woo-settings', $woo_settings );

			// Add new translatopn.
			WPSC_Translations::add( 'wpsc-woo-dashboard-tab-label', $woo_settings['dashboard-support-tab-label'] );
			WPSC_Translations::add( 'wpsc-woo-order-help-button-label', $woo_settings['order-help-button-label'] );
			WPSC_Translations::add( 'wpsc-woo-product-help-button-label', $woo_settings['product-help-button-label'] );
			WPSC_Translations::add( 'wpsc-woo-product-help-tab-label', $woo_settings['product-help-tab-label'] );
			wp_die();
		}

		/**
		 * Reset settings to default
		 *
		 * @return void
		 */
		public static function reset_settings() {

			if ( check_ajax_referer( 'wpsc_reset_woo_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}
			self::reset();
			wp_die();
		}
	}
endif;

WPSC_WOO_Settings::init();
