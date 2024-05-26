<?php
/**
 * Description
 *
 * Woostify Frequently Bought Together woocommerce
 *
 * @package Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_FBT_Admin' ) && is_admin() ) {
	/**
	 * Admin class for Woostify Frequently Bought Together
	 */
	class Woostify_FBT_Admin {


		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'scripts' ), 10 );
			self::add_product_panel_data();
			add_action( 'wp_ajax_woostify_fbt_search_product', array( __CLASS__, 'search_product' ) );
			add_action( 'wp_ajax_woostify_fbt_add_product', array( __CLASS__, 'add_frequently_products' ) );
			add_action( 'save_post_product', array( __CLASS__, 'save_post' ), 10, 1 );

			$woocommerce_helper = Woostify_Woocommerce_Helper::init();
			add_action( 'wp_ajax_woostify_save_fbt_options', array( $woocommerce_helper, 'save_options' ) );

		}

		/**
		 * Recursively sanitize text field
		 *
		 * @param array $array Array to sanitize.
		 */
		public static function recursive_sanitize_text_field( $array ) {
			foreach ( $array as $key => &$value ) {
				if ( is_array( $value ) ) {
					$value = self::recursive_sanitize_text_field( $value );
				} else {
					$value = sanitize_text_field( $value );
				}
			}

			return $array;
		}

		/**
		 * Save product data
		 *
		 * @param integer $post_id Post ID.
		 */
		public static function save_post( $post_id ) {

			if ( ! isset( $_POST['woostify_fbt_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['woostify_fbt_nonce'] ), 'woostify_fbt_nonce' ) ) {
				return;
			}

			if ( ! empty( $_POST['woostify_fbt'] ) ) {
				$woostify_fbt = self::recursive_sanitize_text_field( wp_unslash( $_POST['woostify_fbt'] ) );//phpcs:ignore
				update_post_meta( $post_id, 'woostify_fbt', $woostify_fbt );
			} else {
				update_post_meta( $post_id, 'woostify_fbt', '' );
			}

			if ( ! empty( $_POST['woostify_fbt_settings'] ) ) {
				$woostify_fbt_settings = self::recursive_sanitize_text_field( wp_unslash( $_POST['woostify_fbt_settings'] ) ); //phpcs:ignore
				update_post_meta( $post_id, 'woostify_fbt_settings', $woostify_fbt_settings );
			} else {
				update_post_meta( $post_id, 'woostify_fbt_settings', '' );
			}

			if ( ! empty( $_POST['woostify_fbt_total_discount'] ) ) {
				$woostify_fbt_total_discount = self::recursive_sanitize_text_field( wp_unslash( $_POST['woostify_fbt_total_discount'] ) ); //phpcs:ignore
				update_post_meta( $post_id, 'woostify_fbt_total_discount', $woostify_fbt_total_discount );
			} else {
				update_post_meta( $post_id, 'woostify_fbt_total_discount', 0 );
			}
		}

		/**
		 * Add frequently bought together product
		 */
		public static function add_frequently_products() {

			if ( ! ( isset( $_POST['_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['_nonce'] ), 'woostify_fbt_nonce' ) ) ) {
				exit( wp_json_encode( array( 'message' => esc_html__( 'The nonce check wrong.', 'woostify-pro' ) ) ) );
			}

			$product_id = isset( $_POST['product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) : '';
			$product    = wc_get_product( $product_id );
			$item_id    = isset( $_POST['item_id'] ) ? sanitize_text_field( wp_unslash( $_POST['item_id'] ) ) : 1;

			if ( ! $product ) {
				wp_send_json_error();
			}

			$html = self::product_item( $product, $item_id );
			wp_send_json_success( $html );
		}

		/**
		 * Search product ajax
		 *
		 * @return void
		 */
		public static function search_product() {
			if ( ! ( isset( $_POST['_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['_nonce'] ), 'woostify_fbt_nonce' ) ) ) {
				exit( wp_json_encode( array( 'message' => esc_html__( 'The nonce check wrong.', 'woostify-pro' ) ) ) );
			}

			if ( ! isset( $_POST['keyword'] ) ) {
				exit( wp_json_encode( array( 'message' => esc_html__( 'Data not isset.', 'woostify-pro' ) ) ) );
			}

			$keyword = sanitize_text_field( wp_unslash( $_POST['keyword'] ) );

			$products = new WP_Query(
				array(
					'post_type'        => 'product',
					'post_status'      => 'publish',
					's'                => $keyword,
					'orderby'          => 'post_title',
					'order'            => 'DESC',
					'posts_per_page'   => 10,
					'suppress_filters' => true,
				)
			);

			$data = array();

			if ( $products->have_posts() ) {
				while ( $products->have_posts() ) {
					$products->the_post();
					$product = wc_get_product( get_the_ID() );

					$data['product_list'][] = array(
						'id'    => $product->get_id(),
						'name'  => $product->get_name(),
						'image' => $product->get_image( array( 70, 70 ) ),
						'price' => $product->get_price_html(),
						'stock_status' => $product->get_stock_status(),
					);
				}

				exit( wp_json_encode( $data ) );
			}

			exit( wp_json_encode( array( 'message' => esc_html__( 'No data.', 'woostify-pro' ) ) ) );
		}

		/**
		 * Add scripts to product page and option page
		 */
		public static function scripts() {
			global $pagenow;

			$post_type = get_post_type();

			if ( ( ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) && 'product' === $post_type ) || 'options-general.php' === $pagenow ) {
				wp_enqueue_script(
					'woostify-frequently-bought-together-admin',
					WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER_URL . 'assets/js/woostify-fbt-admin' . woostify_suffix() . '.js',
					array(),
					WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER,
					true
				);

				wp_enqueue_style(
					'woostify-frequently-bought-together-admin',
					WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER_URL . 'assets/css/woostify-fbt-admin.css',
					array(),
					WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER
				);

				wp_localize_script( 'woostify-frequently-bought-together-admin', 'woostify_fbt', self::localize_script() );
			}
		}

		/**
		 * Add localize script
		 */
		public static function localize_script() {
			return array(
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'_nonce'    => wp_create_nonce( 'woostify_fbt_nonce' ),
				'no_result' => esc_html__( 'No result', 'woostify-pro' ),
			);
		}

		/**
		 * Add tab and panel to product page
		 */
		public static function add_product_panel_data() {
			add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'tabs' ) );
			add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'panels' ) );
		}

		/**
		 * Add new tab to product detail
		 *
		 * @param array $tabs get default tabs of product.
		 * @return array
		 */
		public static function tabs( $tabs ) {
			$data  = get_post_meta( get_the_ID(), 'woostify_fbt', true );
			$count = !empty( $data ) ? count( $data ) : 0;

			$tabs['frequently_bought_together'] = array(
				'label'    => esc_html__( 'Frequently Bought Together', 'woostify-pro' ),
				'label'    => esc_html__('Frequently Bought Together', 'woostify-pro') . " ($count)",
				'target'   => 'frequently_bought_together_options',
				'class'    => array( 'show_if_simple', 'show_if_variable' ),
				'priority' => 50,
			);
			return $tabs;
		}

		/**
		 * Add product data panels.
		 *
		 * @return  mixed
		 */
		public static function panels() {
			$product         = wc_get_product( get_the_ID() );
			$data            = get_post_meta( $product->get_id(), 'woostify_fbt', true );
			$widget_settings = get_post_meta( $product->get_id(), 'woostify_fbt_settings', true );
			$check_enable    = isset( $widget_settings['check_enable'] ) ? 'on' : 'off';
			$options         = get_option( 'woostify_fbt_options' );
			$discount_type   = isset( $options['type'] ) ? $options['type'] : 'discount-per-item';
			$saved_pack      = isset( $options['display_products_saved'] ) ? $options['display_products_saved'] : 'percent_off';
			$currency_symbol = get_woocommerce_currency_symbol();
			// var_dump( $options );
			// var_dump( $data );
			?>
			<div id="frequently_bought_together_options" class="panel woocommerce_options_panel hidden">
				<div class="group options_group">
					<div class="wrap">
						<div class="search-product form-field">
							<input type="text" class="search-input" placeholder="<?php esc_html_e( 'Enter product name', 'woostify-pro' ); ?>" />
							<div class="search-result">
								<p><?php esc_html_e( 'No result', 'woostify-pro' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<div class="group">
					<div class="wrap">
						<div class="title"><?php esc_html_e( 'Select frequently bought together products', 'woostify-pro' ); ?></div>
						<div class="product-list">
							<table>
								<tr class="product__item" data-product-id="<?php echo esc_attr( $product->get_id() );?>">
									<td class="product__item--title"><?php echo esc_html( $product->get_title() ); ?></td>
									<td class="product__item--image"><?php the_post_thumbnail( array( 70, 70 ) ); ?></td>
									<td>&nbsp;</td>
									<td>
										<?php echo $product->get_price_html(); // phpcs:ignore 
										?>
									</td>
									<td>&nbsp;</td>
								</tr>

								<?php
								if ( $data ) {
									foreach ( $data as $key => $datum ) :
										echo self::product_item( wc_get_product( (int) $datum['id'] ), $key, $datum ); // phpcs:ignore
									endforeach;
								}
								?>
							</table>
						</div>
					</div>
				</div>

				<div class="group">
					<div class="title"><?php esc_html_e( 'Frequently bought together package', 'woostify-pro' ); ?></div>
					<div class="description"><?php esc_html_e( '', 'woostify-pro' ); ?></div>
					<div class="wrap">
						<table class="packed">
							<tbody>
								<tr class="packed__product" data-item="1">
									<th><?php esc_html_e( 'Packed', 'woostify-pro' ); ?></th>
									<td>
										<div class="packed__product__image">
											<?php echo $product->get_image(array(70, 70)); // phpcs:ignore 
											?>
											<span class="plus">+</span>
										</div>
										<div class="packed__product__name"><?php echo esc_html( $product->get_title() ); ?></div>
									</td>
									<?php
									if ( $data ) :
										foreach ( $data as $key => $datum ) :
											$product_data = wc_get_product( $datum['id'] );
											?>
											<td data-item="<?php echo esc_attr( $key ); ?>">
												<div class="packed__product__image">
													<?php echo $product_data->get_image(array(70, 70)); // phpcs:ignore 
													?>
													<span class="plus">+</span>
												</div>
												<div class="packed__product__name"><?php echo esc_html( $product_data->get_title() ); ?></div>
											</td>
											<?php
										endforeach;
									endif;
									?>
								</tr>
								<?php
								$saved_pack_name = ( $saved_pack == 'percent_off' ) ? 'percent' : 'amount' ;
								?>

								<tr class="packed__<?php echo esc_attr( $saved_pack_name ); ?> packed__<?php echo esc_attr( $discount_type ); ?>">
									<th>
										<?php echo sprintf( ( $discount_type == 'discount-per-item' ) ? esc_html__('Enter discount per product (%s)', 'woostify-pro' ) :  esc_html__('Enter discount on total product (%s)', 'woostify-pro' ), ( $saved_pack == 'percent_off' ? '%' : $currency_symbol ) ); ?>
									</th>
									<?php
									if( $discount_type == 'discount-per-item' ){ ?>
										<td></td>
										<?php
										if ( $data ) {
											foreach ( $data as $key => $datum ) :
												$product_item = wc_get_product( $datum['id'] );
												$value        = 0;
												if ( isset( $datum['discount'] ) && $datum['discount'] ) {
													$value = $datum['discount'];
												}
												?>
												<td data-item="<?php echo esc_attr( $key ); ?>">
													<input data-price="<?php echo esc_attr( $product_item->get_price() ); ?>"
														name="woostify_fbt[<?php echo esc_attr( $key ); ?>][discount]" type="number"
														value="<?php echo esc_attr( $value ); ?>" min="0" step="0.01" />
												</td>
												<?php
											endforeach;
										}
									} else { 
										// update_post_meta( $product->get_id(), 'woostify_fbt_total_discount', 20 );
										$woostify_fbt_total_discount = get_post_meta( $product->get_id(), 'woostify_fbt_total_discount', true );
										$total_discount_value = !empty($woostify_fbt_total_discount) ? $woostify_fbt_total_discount : 0;
										?>
										<td data-item="total-discount" colspan="99">
											<input name="woostify_fbt_total_discount" type="number" value="<?php echo esc_attr( $total_discount_value ); ?>" min="0" step="0.01" />
										</tr>
									<?php
									}
									?>
								</tr>
								
							</tbody>
						</table>
					</div>
				</div>

				<div class="group">
					<div class="title"><?php esc_html_e( 'Edit frequently package title and description', 'woostify-pro' ); ?></div>
					<div class="wrap">
						<div class="woostify_fbt-title-desciption-option">
							<input type="checkbox" <?php checked( $check_enable, 'on' ); ?> name="woostify_fbt_settings[check_enable]" value="1" id="woostify_fbt_enable" class="check-title-desc">
							<label style="float: initial; width: initial; margin: initial;" for="woostify_fbt_enable">
								<?php esc_html_e( 'When you enable this option. This setting will override global setting of plugin at Woostify Options &gt; Frequently Bought Together.', 'woostify-pro' ); ?>
							</label>
						</div>
					</div>
				</div>

				<div class="group woostify_fbt-title-desciption-edit hidden">
					<div class="wrap">
						<table width="100%">
							<tbody>
								<tr>
									<td width="30%">
										<strong><?php esc_html_e( 'Widget Title', 'woostify-pro' ); ?></strong>
									</td>
									<td>
										<?php
											$widget_title = isset( $widget_settings['title'] ) ? $widget_settings['title'] : esc_attr__( 'Buy this pack to get off 15%', 'woostify-pro' );
										?>
										<input type="text" value="<?php echo esc_attr( trim( $widget_title ) ); ?>" class="woostify_fbt-title wide" name="woostify_fbt_settings[title]" 
										placeholder="<?php echo esc_attr__( 'Buy this pack and get 25% off', 'woostify-pro' ); ?>">
									</td>
								</tr>
								<tr>
									<td width="30%"><strong><?php esc_html_e( 'Widget Description', 'woostify-pro' ); ?></strong>
									</td>
									<td>
										<input type="hidden" name="woostify_fbt_nonce" value="<?php echo esc_attr( wp_create_nonce( 'woostify_fbt_nonce' ) ); ?>" />
										<textarea class="woostify_fbt-desciption wide" name="woostify_fbt_settings[description]" 
										placeholder="<?php esc_attr_e( 'Buy more save more. Save 15% when you purchase 4 products, save 10% when you purchase 3 products', 'woostify-pro' ); ?>"
										><?php echo isset( $widget_settings['description'] ) ? esc_attr( $widget_settings['description'] ) : esc_html__( 'Buy this pack to get off 15%', 'woostify-pro' ); ?></textarea>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

			</div>
			<?php
		}

		/**
		 * Single product item in list
		 *
		 * @param object $product product object to display.
		 * @param int    $item_id item id for item attribute.
		 * @param array  $data    data setting of the product.
		 * @return mixed
		 */
		public static function product_item( $product, $item_id, $data = array() ) {
			$product_id         = $product->get_id();
			$price              = $product->get_price();
			$price_html         = $product->get_price_html();
			$product_attributes = array();

			$data = array_merge( array(
				'id' => '',
				'percent' => '0',
				'variable' => array()
			), $data );
			if ( $product->is_type( 'variable' ) ) {
				$product_attributes = Woostify_FBT_Functions::get_product_attribute( $product, $item_id, $data['variable'] );
				$price              = $product_attributes['price'];
				$price_html         = $product_attributes['price_html'];
			}
			ob_start();
			?>
			<tr class="product__item" data-item="<?php echo esc_attr( $item_id ); ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>">
				<td class="product__item--title"><?php echo esc_html( $product->get_title() ); ?></td>
				<td class="product__item--image">
					<?php echo $product->get_image(array(70, 70)); // phpcs:ignore
					?>
				</td>
				<td class="product__item--variable">
					<?php echo isset( $product_attributes['html'] ) ? $product_attributes['html'] : ''; // phpcs:ignore ?>
				</td>
				<td class="product__item--price">
					<?php echo $price_html; // phpcs:ignore ?>
				</td>
				<td>
					<input type="hidden" 
					name="woostify_fbt[<?php echo esc_attr( $item_id ); ?>][id]" 
					value="<?php echo esc_attr( $product_id ); ?>" 
					data-price="<?php echo esc_attr( $price ); ?>" />
					<a href="#" class="remove-product" onclick="event.preventDefault();frequently_remove_product_list(this)"><?php esc_html_e( 'Remove', 'woostify-pro' ); ?></a>
				</td>
			</tr>
			<?php
			return ob_get_clean();
		}
	}

	$woostify_fbt_admin = new Woostify_FBT_Admin();
}
