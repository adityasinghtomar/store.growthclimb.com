<?php
/**
 * Woostify Frequently Bought Together Settings
 *
 * @package  Woostify Pro
 */

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'Woostify_FBT_Settings' ) ) {
	/**
	 * Woostify_FBT_Settings
	 */
	class Woostify_FBT_Settings {

		/**
		 * Holds the values to be used in the fields callbacks.
		 *
		 * @var array
		 */
		private static $options;

		/**
		 * Initialize.
		 *
		 * @return  void
		 */
		public function __construct() {
			add_action( 'admin_menu', array( __CLASS__, 'add_plugin_page' ) );
			add_action( 'admin_init', array( __CLASS__, 'create_settings_page' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_color_picker_script' ) );

			self::$options = get_option( 'woostify_fbt_options' );
		}

		/**
		 * Get product bundle type.
		 *
		 * @return  string
		 */
		public static function get_fbt_type() {
			if ( ! empty( self::$options['type'] ) ) {
				return self::$options['type'];
			}
			return 'total-discount';
		}

		/**
		 * Get product attribute data.
		 *
		 * @param string $key option key.
		 * @param string $default default value.
		 * @return  string
		 */
		public static function get_fbt_data( $key, $default = '' ) {
			if ( ! empty( self::$options[ $key ] ) ) {
				return self::$options[ $key ];
			}
			return $default;
		}

		/**
		 * Enqueue color picker jquery
		 *
		 * @param   string $hook Hook.
		 * @return  void
		 */
		public static function add_color_picker_script( $hook ) {
			global $pagenow;

			if ( is_admin() ) {
				if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'frequently-bought-together' === $_GET['page'] ) {
					// Add the color picker css file.
					wp_enqueue_style( 'wp-color-picker' );

					// Include our custom jQuery file with WordPress Color Picker dependency.
					wp_enqueue_script( 'wp-color-picker' );

					wp_enqueue_script(
						'woostify-frequently-bought-together-settings',
						WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER_URL . 'assets/js/woostify-fbt-settings' . woostify_suffix() . '.js',
						array(),
						WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER,
						true
					);
				}
			}
		}


		/**
		 * Add options page.
		 */
		public static function add_plugin_page() {
			add_submenu_page(
				'woostify-welcome',
				'Settings',
				__( 'Frequently Bought Together', 'woostify-pro' ),
				'manage_options',
				'frequently-bought-together',
				array( __CLASS__, 'create_admin_page' )
			);
		}

		/**
		 * Options page callback.
		 */
		public static function create_admin_page() {
			?>
			<div class="woostify-options-wrap woostify-featured-setting woostify-frequently-bought-together-settings" id="woostify-fbt-options" data-id="fbt" data-nonce="<?php echo esc_attr( wp_create_nonce( 'woostify-fbt-setting-nonce' ) ); ?>">
				<?php Woostify_Admin::get_instance()->woostify_welcome_screen_header(); ?>
				<div class="wrap woostify-settings-box">
					<div class="woostify-welcome-container">
						<div class="woostify-settings-content">
							<h4 class="woostify-settings-section-title"><?php esc_html_e( 'Woostify - Frequently Bought Together Settings', 'woostify-pro' ); ?></h4>
							<form method="post" action="options.php">
								<div class="woostify-settings-section-content">
									<?php
									settings_fields( 'woostify-fbt_group' );
									do_settings_sections( 'woostify_frequently_bought_together' );
									?>
								</div>
								<div class="woostify-settings-section-footer">
									<span class="save-options button button-primary"><?php esc_html_e( 'Save', 'woostify-pro' ); ?></span>
									<span class="spinner"></span>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Register and add settings
		 */
		public static function create_settings_page() {
			register_setting(
				'woostify-fbt_group', // Option group.
				'woostify_fbt_options', // Option name.
				array( __CLASS__, 'sanitize' ) // Sanitize.
			);

			add_settings_section(
				'woostify_fbt_section_general_settings',
				esc_html__( '', 'woostify-pro' ),
				array(),
				'woostify_frequently_bought_together'
			);

			add_settings_field(
				'woostify_fbt_widget_title',
				esc_html__( 'Promotion Title', 'woostify-pro' ),
				array( __CLASS__, 'fbt_text_callback' ),
				'woostify_frequently_bought_together',
				'woostify_fbt_section_general_settings',
				array(
					'id'      => 'widget_title',
					'default' => esc_html__( 'Buy this bundle and get 25% off', 'woostify-pro' ),
				)
			);

			add_settings_field(
				'woostify_fbts_promo_text',
				esc_html__( 'Promotion description', 'woostify-pro' ),
				array( __CLASS__, 'fbt_textarea_callback' ),
				'woostify_frequently_bought_together',
				'woostify_fbt_section_general_settings',
				array(
					'id'      => 'widget_description',
					'default' => esc_html__( 'Buy more save more. Save 15% off when you purchase 4 products', 'woostify-pro' ),
				)
			);

			add_settings_field(
				'woostify_fbt_button_label',
				esc_html__( 'Button label', 'woostify-pro' ),
				array( __CLASS__, 'fbt_text_callback' ),
				'woostify_frequently_bought_together',
				'woostify_fbt_section_general_settings',
				array(
					'id'      => 'button_label',
					'default' => esc_html__( 'Add all to cart', 'woostify-pro' ),
				)
			);

			add_settings_field(
				'woostify_fbt_button_background_color',
				esc_html__( 'Button background', 'woostify-pro' ),
				array( __CLASS__, 'fbt_text_callback' ),
				'woostify_frequently_bought_together',
				'woostify_fbt_section_general_settings',
				array(
					'id'        => 'button_bg_color',
					'fbt_class' => 'woostify-color-picker',
				)
			);

			add_settings_field(
				'woostify_fbt_button_background_hover_color',
				esc_html__( 'Button background hover', 'woostify-pro' ),
				array( __CLASS__, 'fbt_text_callback' ),
				'woostify_frequently_bought_together',
				'woostify_fbt_section_general_settings',
				array(
					'id'        => 'button_bg_hover_color',
					'fbt_class' => 'woostify-color-picker',
				)
			);

			add_settings_field(
				'woostify_fbt_button_text_color',
				esc_html__( 'Button text', 'woostify-pro' ),
				array( __CLASS__, 'fbt_text_callback' ),
				'woostify_frequently_bought_together',
				'woostify_fbt_section_general_settings',
				array(
					'id'        => 'button_text_color',
					'fbt_class' => 'woostify-color-picker',
				)
			);

			add_settings_field(
				'woostify_fbt_button_text_hover_color',
				esc_html__( 'Button text hover', 'woostify-pro' ),
				array( __CLASS__, 'fbt_text_callback' ),
				'woostify_frequently_bought_together',
				'woostify_fbt_section_general_settings',
				array(
					'id'        => 'button_text_hover_color',
					'fbt_class' => 'woostify-color-picker',
				)
			);

			add_settings_field(
				'woostify_fbt_product_image_size',
				esc_html__( 'Product image size', 'woostify-pro' ),
				array( __CLASS__, 'fbt_image_size_callback' ),
				'woostify_frequently_bought_together',
				'woostify_fbt_section_general_settings',
				array(
					'id'      => 'product_image_size',
					'default' => '300x300',
				)
			);

			add_settings_field(
				'woostify_fbt_position_display_setting',
				esc_html__( 'Position', 'woostify-pro' ),
				array( __CLASS__, 'fbt_select_callback' ),
				'woostify_frequently_bought_together',
				'woostify_fbt_section_general_settings',
				array(
					'id'      => 'position_display_setting',
					'default' => 'below-product-summary',
					'val'     => array(
						'below-product-summary' => esc_html__( 'Below product summary', 'woostify-pro' ),
						'above-product-tabs'    => esc_html__( 'Above product tab', 'woostify-pro' ),
						'below-product-tabs'    => esc_html__( 'Below product tab', 'woostify-pro' ),
					),
				)
			);

			add_settings_field(
				'woostify_fbt_display_products_saved',
				esc_html__( 'Display saved pack', 'woostify-pro' ),
				array( __CLASS__, 'fbt_select_callback' ),
				'woostify_frequently_bought_together',
				'woostify_fbt_section_general_settings',
				array(
					'id'      => 'display_products_saved',
					'default' => 'percent_off',
					'val'     => array(
						'percent_off' => esc_html__( 'Percent off (%)', 'woostify-pro' ),
						'amount_off'  => esc_html__( 'Amount off', 'woostify-pro' ),
					),
				)
			);

			add_settings_field(
				'woostify_fbt_discount_type',
				esc_html__( 'Type of discount', 'woostify-pro' ),
				array( __CLASS__, 'fbt_select_callback' ),
				'woostify_frequently_bought_together',
				'woostify_fbt_section_general_settings',
				array(
					'id'      => 'type',
					'default' => 'total-discount',
					'val'     => array(
						'total-discount'    => esc_html__( 'Discount for total', 'woostify-pro' ),
						'discount-per-item' => esc_html__( 'Discount per item' , 'woostify-pro' ),
					),
				)
			);

			add_settings_field(
				'woostify_fbt_widget_discount_text',
				esc_html__( 'Discount text', 'woostify-pro' ),
				array( __CLASS__, 'fbt_text_callback' ),
				'woostify_frequently_bought_together',
				'woostify_fbt_section_general_settings',
				array(
					'id'      => 'discount_text',
					'default' => esc_html__( 'Frequently Bought Together Discount', 'woostify-pro' ),
				)
			);

		}

		/**
		 * Sanitize each setting field as needed
		 *
		 * @param array $input Contains all settings fields as array keys.
		 */
		public function sanitize( $input ) {
			$new_input = array();
			$data_key  = array(
				'widget_title',
				'widget_description',
				'button_label',
				'discount_text',
				'button_bg_color',
				'button_bg_hover_color',
				'button_text_color',
				'button_text_hover_color',
				'product_image_size',
				'position_display_setting',
				'display_products_saved',
				'type',
			);

			foreach ( $data_key as $value ) {
				if ( isset( $input[ $value ] ) ) {
					$new_input[ $value ] = sanitize_text_field( $input[ $value ] );
				}
			}
			return $new_input;
		}

		/**
		 * Get the settings option array and print one of its values.
		 *
		 * @param array $args Short code arguments.
		 */
		public static function fbt_select_callback( $args ) {
			extract( // phpcs:ignore
				shortcode_atts(
					array(
						'id'        => '',
						'default'   => '',
						'fbt_class' => '',
						'val'       => array(),
					),
					$args
				)
			);
			$select_value = isset( self::$options[ $id ] ) ? esc_attr( self::$options[ $id ] ) : $default;

			echo '<select id="' . $id . '" name="woostify_fbt_options[' . $id . ']">'; // phpcs:ignore
			if ( count( $val ) > 0 ) {
				foreach ( $val as $key => $value ) {
					echo '<option value="' . $key . '" ' . selected( $select_value, $key, false ) . '>' . $value . '</option>'; // phpcs:ignore
				}
			}
			echo '</select>';
			if( $id == 'position_display_setting' ){
				echo '<p class="description">' . esc_html__( 'If the product page template is built with Woobuilder or Elementor, this option is not applied, please use Frequently bought together widget!', 'woostify-pro' ) . '</p>';
			}
		}

		/**
		 * Get the settings option array and print one of its values
		 *
		 * @param array $args Short code arguments.
		 */
		public static function fbt_text_callback( $args = array() ) {
			extract( // phpcs:ignore
				shortcode_atts(
					array(
						'id'        => '',
						'default'   => '',
						'fbt_class' => '',
					),
					$args
				)
			);

			printf(
				'<input class="' . $fbt_class . ' regular-text ltr" type="text" id="' . $id . '" name="woostify_fbt_options[' . $id . ']" value="%s" />',
				isset( self::$options[ $id ] ) ? esc_attr( self::$options[ $id ] ) : esc_attr( $default )
			);
		}

		/**
		 * Get the settings option array and print one of its values
		 *
		 * @param array $args Short code arguments.
		 */
		public static function fbt_textarea_callback( $args = array() ) {
			extract( // phpcs:ignore
				shortcode_atts(
					array(
						'id'        => '',
						'default'   => '',
						'fbt_class' => '',
					),
					$args
				)
			);

			printf(
				'<textarea style="min-height: 70px" class="' . $fbt_class . ' regular-text ltr" id="' . $id . '" name="woostify_fbt_options[' . $id . ']"/>%s</textarea>',
				isset( self::$options[ $id ] ) ? esc_attr( self::$options[ $id ] ) : esc_attr( $default )
			);
		}

		/**
		 * Get the settings option array and print one of its values
		 *
		 * @param array $args Short code arguments.
		 */
		public static function fbt_image_size_callback( $args = array() ) {
			extract( // phpcs:ignore
				shortcode_atts(
					array(
						'id'        => '',
						'default'   => '',
						'fbt_class' => '',
					),
					$args
				)
			);

			printf(
				'<input class="' . $fbt_class . ' regular-text ltr" type="text" id="' . $id . '" name="woostify_fbt_options[' . $id . ']" value="%s" />
                <p class="description">' . esc_html__( 'Ex: 70x70 or can use product size defalut: shop_thumbnail, shop_catalog, shop_single', 'woostify-pro' ) . '</p>',
				isset( self::$options[ $id ] ) ? esc_attr( self::$options[ $id ] ) : esc_attr( $default )
			);
		}
	}

	$woostify_fbt_settings = new Woostify_FBT_Settings();
}
