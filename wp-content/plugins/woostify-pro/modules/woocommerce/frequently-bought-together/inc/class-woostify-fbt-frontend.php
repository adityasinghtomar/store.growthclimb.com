<?php
/**
 * Description
 *
 * Woostify Frequently Bought Together woocommerce
 *
 * @package Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_FBT_Frontend' ) ) {
	/**
	 * All functions and frontend hooks
	 */
	class Woostify_FBT_Frontend {
		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'add_script_assets' ) );
			self::display_frequently_products();

			// Add product to cart.
			add_action( 'wp_ajax_woostify_fbt_add_all_to_cart', array( __CLASS__, 'woostify_fbt_add_all_to_cart' ) );
			add_action( 'wp_ajax_nopriv_woostify_fbt_add_all_to_cart', array( __CLASS__, 'woostify_fbt_add_all_to_cart' ) );

			// Update mini cart.
			add_filter( 'wp_ajax_nopriv_woostify_fbt_update_mini_cart', array( __CLASS__, 'woostify_fbt_update_mini_cart' ) );
			add_filter( 'wp_ajax_woostify_fbt_update_mini_cart', array( __CLASS__, 'woostify_fbt_update_mini_cart' ) );

			// Change woocommerce checkout order detail.
			add_action( 'woocommerce_add_order_item_meta', array( __CLASS__, 'woostify_fbt_woocommerce_change_checkout_order_detail' ), 1, 3 );

			// add price after filter into variation data.
			add_filter( 'woocommerce_available_variation', array( __CLASS__, 'woostify_fbt_woocommerce_add_price_with_filter' ), 10, 3 );

			// Change price of product bundle in cart.
			add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'woostify_fbt_update_product_bundle_in_cart' ) );

			// Custom woocommerce template path.
			add_filter( 'woocommerce_locate_template', array( __CLASS__, 'woostify_fbt_woocommerce_locate_template' ), 10, 3 );
		}

		/**
		 * Assets and script to frontend
		 *
		 * @return void
		 */
		public static function add_script_assets() {
			wp_enqueue_style(
				'woostify-fbt-frontend',
				WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER_URL . 'assets/css/woostify-fbt.css',
				array(),
				WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER
			);

			wp_enqueue_script(
				'woostify-fbt-frontend',
				WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER_URL . 'assets/js/woostify-fbt-script' . woostify_suffix() . '.js',
				array( 'jquery' ),
				WOOSTIFY_PRO_FREQUENT_BOUGHT_TOGETHER,
				true
			);

			// Add inline script.
			wp_localize_script(
				'woostify-fbt-frontend',
				'woostify_fbt',
				self::localize_script()
			);

			// Add inline style.
			wp_add_inline_style( 'woostify-fbt-frontend', self::localize_style(), 'after' );
		}

		/**
		 * Localize script
		 *
		 * @return array
		 */
		public static function localize_script() {
			$setting = array();
			if( is_product() ){
				$setting = self::get_product_data();
			}
			return array(
				'ajaxurl'            => admin_url( 'admin-ajax.php' ),
				'decimal_separator'  => wc_get_price_decimal_separator(),
				'thousand_separator' => wc_get_price_thousand_separator(),
				'currency_pos'       => get_option( 'woocommerce_currency_pos' ),
				'number_decimals'    => wc_get_price_decimals(),
				'currency_symbol'    => get_woocommerce_currency_symbol(),
				'_nonce'             => wp_create_nonce( 'woostify_fbt_nonce' ),
				'options'            => get_option( 'woostify_fbt_options' ),
				'settings'           => $setting,
				'outofstock_text'    => esc_html__('Out of stock', 'woostify-pro'),
			);
		}

		/**
		 * Localize style.
		 *
		 * @return mixed
		 */
		public static function localize_style() {
			$button_bg_color   = Woostify_FBT_Settings::get_fbt_data( 'button_bg_color' );
			$button_text_color = Woostify_FBT_Settings::get_fbt_data( 'button_text_color' );
			$button_bg_hover   = Woostify_FBT_Settings::get_fbt_data( 'button_bg_hover_color' );
			$button_text_hover = Woostify_FBT_Settings::get_fbt_data( 'button_text_hover_color' );

			ob_start();
			?>
			<?php if ( $button_bg_color || $button_text_color ) : ?>
			.woostify-fbt__button {
				<?php echo $button_bg_color ? 'background-color:' . esc_attr( $button_bg_color ) . '!important;' : ''; ?>
				<?php echo $button_text_color ? 'color: ' . esc_attr( $button_text_color ) . '!important;' : ''; ?>
			}
			<?php endif; ?>
			<?php if ( $button_bg_hover || $button_text_hover ) : ?>
			.woostify-fbt__button:hover {
				<?php echo $button_bg_hover ? 'background-color: ' . esc_attr( $button_bg_hover ) . '!important;' : ''; ?>
				<?php echo $button_text_hover ? 'color: ' . esc_attr( $button_text_hover ) . '!important;' : ''; ?>
			}
			<?php endif; ?>
			<?php
			return ob_get_clean();
		}

		/**
		 * Return my plugin path
		 *
		 * @return json
		 */
		public static function woostify_fbt_plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Display frequently products
		 */
		public static function display_frequently_products() {
			$display_position = Woostify_FBT_Settings::get_fbt_data( 'position_display_setting' );
			$display_position = !empty($display_position) ? $display_position : 'below-product-summary';
			switch ( $display_position ) {
				case 'below-product-tabs':
					add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'products_list' ), 12 );
					break;
				case 'above-product-tabs':
					add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'products_list' ), 5 );
					break;
				case 'below-product-summary':
					// Fix conflict with kadence plugin
					// Do not add to action woocommerce_single_product_summary, should be add to woocommerce_after_add_to_cart_button
					add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'products_small_list' ), 100 );
					break;

				default:
			}
		}

		/**
		 * Display frequently products list
		 */
		public static function products_list($wg_settings = array() ) {
			global $product;
			$settings = self::get_product_data();
			// var_dump( $settings ); exit;
			$data     = $settings['data'];
			if ( empty( $data ) ) {
				return;
			}

			// ini discount value
			$percent_off_per_item = $amount_off_per_item = 0;
			$percent_off_all_item = $amout_off_all_item  = 0;
			$options = get_option( 'woostify_fbt_options' );
			$saved_pack      = isset( $options['display_products_saved'] ) ? $options['display_products_saved'] : 'percent_off';
			$saved_pack_name = ( $saved_pack == 'percent_off' ) ? 'percent' : 'amount' ;
			$discount_type   = isset( $options['type'] ) ? $options['type'] : 'discount-per-item';
			$product_discount = 0;
			if( $discount_type != 'discount-per-item' ){
				$product_discount = get_post_meta( $product->get_id(), 'woostify_fbt_total_discount', true );

			}
			$classes = array('woostify-fbt');
			if( is_string( $wg_settings ) || empty( $wg_settings ) ){
				$classes[] = $wg_settings ? ('woostify-fbt__' . $wg_settings ) : 'woostify-fbt__wide'; 
			}elseif( is_array( $wg_settings ) ){
				$classes[] = !empty( $wg_settings['fbt_size'] ) ? ( 'woostify-fbt__' . $wg_settings['fbt_size'] ) : 'woostify-fbt__wide';
				if( !empty( $wg_settings['fbt_size_tablet'] ) ) {
					$classes[] = 'woostify-fbt-tablet__' . $wg_settings['fbt_size_tablet'];
				}
				if( !empty( $wg_settings['fbt_size_mobile'] ) ) {
					$classes[] = 'woostify-fbt-mobile__' . $wg_settings['fbt_size_mobile'];
				}
				
			}
			if( !empty( $settings['position'] ) ){
				$classes[] = str_replace( '-', '_', $settings['position'] );
			}
			ob_start();
			?>

			<div class="<?php echo esc_attr( implode(' ', $classes ) ); ?>">
				<?php
				if ( 'above-product-tabs' === $settings['position'] ) {
					echo '<div class="woostify-container">';
				}
				?>
					<div class="woostify-fbt__title">
						<h2><?php echo esc_html( $settings['title'] ); ?></h2>
						<p class="woostify-fbt__description"><?php echo $settings['description']; //phpcs:ignore?></p>
					</div>
					<!-- <div class="woostify-fbt__preview"> -->
						<?php
							$percent_arr   = array();
							$percent_arr[] = 0;
							foreach ( $data as $datum ) {//phpcs:ignore
								if ( isset( $datum['percent'] ) && $datum['percent'] ) {//phpcs:ignore
									$percent_arr[] = $datum['percent'];
								}//phpcs:ignore
							}//phpcs:ignore
						?>
					<!-- </div> -->
					<div class="woostify-fbt__grid-container">
						<div class="woostify-fbt__products-list">

							<div class="woostify-fbt__products_content">
								<ul class="woostify-fbt__products  woostify-fbt__products-images" data-total-discount="<?php echo implode( ',', $percent_arr );//phpcs:ignore ?>" data-product-discount="<?php echo $product_discount;?>" data-product_count="<?php echo count($data) + 1;?>">
									<li <?php wc_product_class( 'woostify-fbt__product--item woostify-fbt__product--item-main woostify-fbt__product--item-image', $product ); ?> data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
										<div class="woostify-fbt__product-image">
											<?php echo $product->get_image( $settings['image_size'] ); //phpcs:ignore ?>
											<svg fill="currentColor" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M376 232H216V72c0-4.42-3.58-8-8-8h-32c-4.42 0-8 3.58-8 8v160H8c-4.42 0-8 3.58-8 8v32c0 4.42 3.58 8 8 8h160v160c0 4.42 3.58 8 8 8h32c4.42 0 8-3.58 8-8V280h160c4.42 0 8-3.58 8-8v-32c0-4.42-3.58-8-8-8z"></path></svg>
										</div>								
									</li>
									<?php
									$keys = array_keys ( $data );
									$endkey = end( $keys );
									foreach ( $data as $key => $datum ) {
										$product_data  = wc_get_product( $datum['id'] );
										
										?>
										<li <?php wc_product_class( 'woostify-fbt__product--item woostify-fbt__product--item-image', $product_data ); ?> data-product-id="<?php echo esc_attr( $datum['id'] ); ?>">
											<div class="woostify-fbt__product-image">
												<?php echo $product_data->get_image( $settings['image_size'] ); //phpcs:ignore ?>
												<?php if( $endkey != $key ) { ?>
													<svg fill="currentColor" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M376 232H216V72c0-4.42-3.58-8-8-8h-32c-4.42 0-8 3.58-8 8v160H8c-4.42 0-8 3.58-8 8v32c0 4.42 3.58 8 8 8h160v160c0 4.42 3.58 8 8 8h32c4.42 0 8-3.58 8-8V280h160c4.42 0 8-3.58 8-8v-32c0-4.42-3.58-8-8-8z"></path></svg>
												<?php } ?>
											</div>
										</li>
									<?php } ?>
								
									<?php
										$price_html    = $product->get_price_html();
										$variable_html = '';
										$price_filter  = '';

										$main_variable_attr = '';
										$checked = 'checked disabled';
										$outofstock = false;
										$outofstock_text = '';


									if ( $product->is_type( 'variable' ) ) {
										$variable_data = Woostify_FBT_Functions::get_product_attribute( $product, 0, array(), 'main-product' );
										$variable_html = $variable_data['html'];
										$price_html    = $variable_data['price_html'];
										$total_price   = (float) $variable_data['price'];
										$price_filter  = $total_price ? number_format( $variable_data['price'], wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) : 0;

										$main_product_attributes = $product->get_variation_attributes();
										if ( count( $main_product_attributes ) ) {
											foreach ( $main_product_attributes as $key => $value ) {
												$main_variable_attr .= ' attribute_' . $key . '=';
											}
										}
										$children_ids    = $product->get_children();
										$main_product_id = reset( $children_ids );
									} else {
										$outofstock = $product->get_stock_status() == 'outofstock';
										if( $outofstock ) {
											$checked = 'disabled';
											$outofstock_text = __('Out of stock', 'woostify-pro');
										}
										$main_product_id = $product->get_id();
										$total_price     = (float) $product->get_price();
										$price_filter    = $total_price ? number_format( $product->get_price(), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) : 0;
									}
									?>
									<li <?php wc_product_class( 'woostify-fbt__product--item woostify-fbt__product--content woostify-fbt__product--item-main', $product ); ?> data-product-id="<?php echo esc_attr( $main_product_id ); ?>" data-item-price="<?php echo esc_attr( $total_price ); ?>" data-item-price-with-filter="<?php echo esc_attr( $price_filter ); ?>" data-item-percent="0">
										<?php /* ?>
										<div class="woostify-fbt__product-image">
											<?php echo $product->get_image( $settings['image_size'] ); //phpcs:ignore ?>
											<svg fill="currentColor" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M376 232H216V72c0-4.42-3.58-8-8-8h-32c-4.42 0-8 3.58-8 8v160H8c-4.42 0-8 3.58-8 8v32c0 4.42 3.58 8 8 8h160v160c0 4.42 3.58 8 8 8h32c4.42 0 8-3.58 8-8V280h160c4.42 0 8-3.58 8-8v-32c0-4.42-3.58-8-8-8z"></path></svg>
										</div>
										<?php */ ?>
										<h3 for="woostify_fbt_product_main" title="<?php echo esc_html( $product->get_title() ); ?>">
											<?php printf( esc_html__( 'This product: %s', 'woostify-pro' ), $product->get_title() ); //phpcs:ignore?>
										</h3>
										<div class="woostify-fbt__product-price"><?php echo $price_html; //phpcs:ignore ?></div>
										<input id="woostify_fbt_product_main"  <?php echo esc_attr($checked); ?> type="checkbox" value="<?php echo esc_attr( $main_product_id ); ?>">	
										<div class="woostify-fbt_product__variable">
											<?php echo $variable_html; //phpcs:ignore ?>
										</div>
										<div class="woostify-fbt_product__outofstock"><?php echo esc_html($outofstock_text ); ?></div>
									</li>
									<?php
									foreach ( $data as $key => $datum ) {
										$product_data  = wc_get_product( $datum['id'] );
										$variable_html = '';
										$price_html    = $product_data->get_price_html();
										$discount      = 0;
										$checked = 'checked';
										$outofstock = false;
										$outofstock_text = '';
										if ( ( $discount_type == 'discount-per-item' ) && isset( $datum['discount'] ) && $datum['discount'] ) {
											$discount = $datum['discount'];
										}

										if ( $product_data->is_type( 'variable' ) ) {
											$variable_data = Woostify_FBT_Functions::get_product_attribute( $product_data );
											$variable_html = $variable_data['html'];
											$price_html    = $variable_data['price_html'];
											$total_price   = (float) $variable_data['price'];
											$price_filter  = $total_price ? number_format( $variable_data['price'], wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) : 0;
										} else {
											$outofstock = $product_data->get_stock_status() == 'outofstock';
											if( $outofstock ) {
												$checked = 'disabled';
												$outofstock_text = __('Out of stock', 'woostify-pro');
											}
											$total_price  = (float) $product_data->get_price();
											$price_filter = $total_price ? number_format( $product_data->get_price(), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) : 0;
										}
										?>
										<li <?php wc_product_class( 'woostify-fbt__product--item woostify-fbt__product--content', $product_data ); ?> data-product-id="<?php echo esc_attr( $datum['id'] ); ?>" data-item-price="<?php echo esc_attr( $total_price ); ?>" data-item-price-with-filter="<?php echo esc_attr( $price_filter ); ?>" data-item-discount="<?php echo esc_attr( $discount ); ?>">
											<?php /* 
											<div class="woostify-fbt__product-image">
												<?php echo $product_data->get_image( $settings['image_size'] ); //phpcs:ignore ?>
											</div>
											*/ ?>
											<h3 for="woostify-fbt_product_<?php echo esc_attr( $key ); ?>" title="<?php echo esc_html( $product_data->get_title() ); ?>"><?php echo esc_html( $product_data->get_title() ); ?></h3>
											<div class="woostify-fbt__product-price"> <?php echo $price_html //phpcs:ignore ?></div>
											<input id="woostify-fbt_product_<?php echo esc_attr( $key ); ?>"  <?php echo esc_attr($checked); ?> type="checkbox" value="<?php echo esc_attr( $datum['id'] ); ?>" onchange="if (!window.__cfRLUnblockHandlers) return false; woostify_fbt_onchange_input_check_total_discount()">
											<div class="woostify-fbt_product__variable">
												<?php echo $variable_html; //phpcs:ignore ?>
											</div>
											<div class="woostify-fbt_product__outofstock"><?php echo esc_html($outofstock_text ); ?></div>
										</li>
									<?php } ?>
								</ul>
							</div>
							<div class="woostify-fbt__products-action">
								<div class="woostify-fbt__discount">
									<div class="woostify-fbt__discount-price">
										<div>
										<span class="text-total-price"><?php esc_html_e( 'Total price: ', 'woostify-pro' ); ?></span>
										<?php $price = self::get_total_price(); ?>
										<del class="old-price"><?php echo $price['total_price']; ?></del> <span class="current-price"><?php echo $price['discount_price'];  //phpcs:ignore?></span>
										</div>
										<div><?php echo esc_html__('Saved: ','woostify-pro');?> <span class="saved"><?php echo esc_html__( '$0' );//phpcs:ignore?></span></div>
									</div>
								</div>
								<button class="single_add_all_to_cart_button button alt woostify-fbt__button" type="submit" onclick="if (!window.__cfRLUnblockHandlers) return false; woostify_fbt_add_all_to_cart( event, jQuery(this) )" data-variation_id="" <?php echo esc_attr( $main_variable_attr ); ?>>
									<?php echo esc_html( $settings['button_label'] ); ?>
								</button>
							</div>

						</div>
					</div>
				<?php
				if ( 'above-product-tabs' === $settings['position'] ) {
					echo '</div>';
				}
				?>
			</div>
			<?php
			echo ob_get_clean(); //phpcs:ignore
		}

		/**
		 * Small list of products.
		 *
		 * @return mixed
		 */
		public static function products_small_list() {
			self::products_list('small');
			return true;
			global $product;
			$settings = self::get_product_data();
			$data     = $settings['data'];
			if ( empty( $data ) ) {
				return;
			}
			ob_start();
			?>
			<script>window.__cfRLUnblockHandlers = true;</script>
			<div class="woostify-fbt woostify-fbt__small_list">
				<div class="woostify-fbt__title">
					<h2><?php echo esc_html( $settings['title'] ); ?></h2>
					<p class="woostify-fbt__description"><?php echo $settings['description']; //phpcs:ignore?></p>
				</div>
				<div class="woostify-fbt__preview">
					<ul class="woostify-fbt__preview--list">
						<li class="woostify-fbt--preview-item">
							<?php echo $product->get_image( $settings['image_size'] );  //phpcs:ignore ?>
							<span class="plus">+</span>
						</li>
						<?php
						$percent_arr   = array();
						$percent_arr[] = 0;
						foreach ( $data as $datum ) {
							$product_preview = wc_get_product( $datum['id'] );
							$percent_arr[]   = 0;
							if ( isset( $datum['percent'] ) && $datum['percent'] ) {
								$percent_arr[] = $datum['percent'];
							}
							?>
							<li class="woostify-fbt--preview-item">
								<a href="<?php echo esc_attr( $product_preview->get_permalink() ); ?>" target="_blank">
									<?php echo $product_preview->get_image( $settings['image_size'] ); //phpcs:ignore?>
								</a>
								<span class="plus">+</span>
							</li>
						<?php } ?>
					</ul>
				</div>
				<ul class="woostify-fbt__products" data-total-discount="<?php echo implode( ',', $percent_arr );//phpcs:ignore ?>">
					<?php
						$price_html    = $product->get_price_html();
						$variable_html = '';
						$price_filter  = '';

						$main_variable_attr = '';
						$checked = 'checked disabled';
						$outofstock = false;
						$outofstock_text = '';

					if ( $product->is_type( 'variable' ) ) {
						$variable_data = Woostify_FBT_Functions::get_product_attribute( $product, 0, array(), 'main-product' );
						$variable_html = $variable_data['html'];
						$price_html    = $variable_data['price_html'];
						$total_price   = (float) $variable_data['price'];
						$price_filter  = $total_price ? number_format( $variable_data['price'], wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) : 0;

						$main_product_attributes = $product->get_variation_attributes();
						if ( count( $main_product_attributes ) ) {
							foreach ( $main_product_attributes as $key => $value ) {
								$main_variable_attr .= ' attribute_' . $key .'='.'';//phpcs:ignore
							}
						}
						$children_ids    = $product->get_children();
						$main_product_id = reset( $children_ids );
					} else {
						$outofstock = $product->get_stock_status() == 'outofstock';
						if( $outofstock ) {
							$checked = 'disabled';
							$outofstock_text = __('Out of stock', 'woostify-pro');
						}
						$main_product_id = $product->get_id();
						$total_price  = (float) $product->get_price();//phpcs:ignore
						$price_filter = $total_price ? number_format( $product->get_price(), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) : 0;//phpcs:ignore
					}
					?>
					<li <?php wc_product_class( 'woostify-fbt__product--item woostify-fbt__product--item-main', $product ); ?> data-product-id="<?php echo esc_attr( $main_product_id ); ?>" data-item-price="<?php echo esc_attr( $total_price ); ?>" data-item-price-with-filter="<?php echo esc_attr( $price_filter ); ?>" data-item-percent="0">
						<input id="woostify_fbt_product_main"  <?php echo esc_attr($checked); ?> type="checkbox" value="<?php echo esc_attr( $main_product_id ); ?>">
						<label for="woostify_fbt_product_main">
							<?php printf( esc_html__( 'This product: %s', 'woostify-pro' ), $product->get_title() ); //phpcs:ignore?>
						</label>
						<div class="woostify-fbt__product-price"><?php echo $price_html; //phpcs:ignore ?></div>
						<div class="woostify-fbt_product__variable">
							<?php echo $variable_html; //phpcs:ignore ?>
						</div>
						<div class="woostify-fbt_product__outofstock"><?php echo esc_html($outofstock_text ); ?></div>
					</li>
					<?php
					foreach ( $data as $key => $datum ) {
						$product_data  = wc_get_product( $datum['id'] );
						$variable_html = '';
						$price_html    = $product_data->get_price_html();
						$percent       = 0;
						$checked = 'checked';
						$outofstock = false;
						$outofstock_text = '';
						if ( isset( $datum['percent'] ) && $datum['percent'] ) {
							$percent = $datum['percent'];
						}

						if ( $product_data->is_type( 'variable' ) ) {
							$variable_data = Woostify_FBT_Functions::get_product_attribute( $product_data );
							$variable_html = $variable_data['html'];
							$price_html    = $variable_data['price_html'];
							$total_price   = (float) $variable_data['price'];
							$price_filter  = $total_price ? number_format( $variable_data['price'], wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) : 0;
						} else {
							$outofstock = $product_data->get_stock_status() == 'outofstock';
							if( $outofstock ) {
								$checked = 'disabled';
								$outofstock_text = __('Out of stock', 'woostify-pro');
							}
							$total_price  = (float) $product_data->get_price();
							$price_filter = $total_price ? number_format( $product_data->get_price(), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) : 0;
						}
						?>
						<li <?php wc_product_class( 'woostify-fbt__product--item', $product_data ); ?> data-product-id="<?php echo esc_attr( $datum['id'] ); ?>" data-item-price="<?php echo esc_attr( $total_price ); ?>" data-item-price-with-filter="<?php echo esc_attr( $price_filter ); ?>" data-item-percent="<?php echo esc_attr( $percent ); ?>">
							<input id="woostify-fbt_product_<?php echo esc_attr( $key ); ?>"  <?php echo esc_attr($checked); ?> type="checkbox" value="<?php echo esc_attr( $datum['id'] ); ?>" onchange="if (!window.__cfRLUnblockHandlers) return false; woostify_fbt_onchange_input_check_total_discount()">
							<label for="woostify-fbt_product_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $product_data->get_title() ); ?></label>
							<div class="woostify-fbt__product-price"> <?php echo $price_html //phpcs:ignore ?></div>
							<div class="woostify-fbt_product__variable">
								<?php echo $variable_html; //phpcs:ignore ?>
							</div>
							<div class="woostify-fbt_product__outofstock"><?php echo esc_html($outofstock_text ); ?></div>
						</li>
					<?php } ?>
				</ul>
				<div class="woostify-fbt__products-action">
					<div class="woostify-fbt__discount">
						<div> <?php esc_html_e( 'Price for all:', 'woostify-pro' ); ?></div>
						<div class="woostify-fbt__discount-price">
							<?php $price = self::get_total_price(); ?>
							<span class="current-price"><?php echo $price['discount_price'];  //phpcs:ignore?></span> / <del class="old-price"><?php echo $price['total_price']; ?></del> <small><?php echo esc_html__('Saved: ','woostify-pro');?> <span class="saved"><?php echo esc_attr( $price['percent'] );?></span><?php echo esc_html__('%','woostify-pro');?></small>
						</div>
					</div>
					<button class="single_add_all_to_cart_button button alt woostify-fbt__button" type="submit" onclick="if (!window.__cfRLUnblockHandlers) return false; woostify_fbt_add_all_to_cart( event, jQuery(this) )" data-variation_id="" <?php echo esc_attr( $main_variable_attr ); ?>>
						<?php echo esc_html( $settings['button_label'] ); ?>
					</button>
				</div>
			</div>
			<?php
			echo ob_get_clean(); //phpcs:ignore
		}

		/**
		 * Get total price.
		 */
		public static function get_total_price() {
			global $product;
			$settings         = self::get_product_data();
			$data             = $settings['data'];
			$discount_price   = 0;
			$discount_percent = 0;
			$discount         = 0;
			$total_price      = 0;

			if ( empty( $data ) ) {
				return false;
			}

			// Get main product price.
			if ( $product->is_type( 'variable' ) ) {
				$variable_data  = Woostify_FBT_Functions::get_product_attribute( $product );
				$total_price    = (float) $variable_data['price'];
				$discount_price = (float) $variable_data['price'];
			} else {
				$total_price    = (float) $product->get_price();
				$discount_price = (float) $product->get_price();
			}

			// Get frequently bought together product price.
			foreach ( $data as $datum ) {
				$product_data = wc_get_product( $datum['id'] );
				$percent      = 0;
				if ( isset( $datum['percent'] ) && $datum['percent'] ) {
					$percent = $datum['percent'];
				}
				$discount          = $percent / 100;
				$discount_percent += (int) $percent;
				if ( $product_data->is_type( 'variable' ) ) {
					$variable_data   = Woostify_FBT_Functions::get_product_attribute( $product_data );
					$total_price    += (float) $variable_data['price'];
					$discount_price += $variable_data['price'] - ( (float) $variable_data['price'] * $discount );
				} else {
					$p_price         = (float) $product_data->get_price();
					$total_price    += $p_price;
					$discount_price += $p_price - ( $p_price * $discount );
				}
			}

			// @todo: need to check this.
			return array(
				'total_price'    => wc_price( $total_price ),
				'discount_price' => wc_price( $discount_price ),
				'saved'          => wc_price( $total_price - $discount_price ),
				'percent'        => $discount_percent,
			);
		}

		/**
		 * Get product data.
		 */
		public static function get_product_data() {
			global $product;
			$data                        = array();
			$widget_settings             = array();
			$check_enable                = false;
			$woostify_fbt_total_discount = 0;
			if( $product ) {
				if( is_string( $product )){
					$product_slug = $product;
					$product = get_page_by_path( $product_slug, OBJECT, 'product' );
					$product_id = $product->ID;
				}else{
					$product_id = get_the_ID();
				}
				$data                        = get_post_meta( $product_id, 'woostify_fbt', true );
				$widget_settings             = get_post_meta( $product_id, 'woostify_fbt_settings', true );
				$check_enable                = isset( $widget_settings['check_enable'] );
				$woostify_fbt_total_discount = get_post_meta( $product_id, 'woostify_fbt_total_discount', true );
			}
			$title           = Woostify_FBT_Settings::get_fbt_data( 'widget_title' );
			$description     = Woostify_FBT_Settings::get_fbt_data( 'widget_description' );
			$image_size      = Woostify_FBT_Settings::get_fbt_data( 'product_image_size', 'woocommerce_thumbnail' );
			if ( preg_match( '/^\d{1,}\s{0,}x\s{0,}\d{1,}$/', $image_size ) ) {
				$image_size = preg_replace( '/\s{0,}/', '', $image_size );
				$image_size = explode( 'x', $image_size );
			}

			if ( $check_enable ) {
				$title       = $widget_settings['title'];
				$description = $widget_settings['description'];
			}

			return array(
				'title'                           => $title,
				'description'                     => $description,
				'data'                            => $data,
				'image_size'                      => $image_size,
				'button_label'                    => Woostify_FBT_Settings::get_fbt_data( 'button_label', esc_html__('Add all to cart', 'woostify-pro') ),
				'position'                        => Woostify_FBT_Settings::get_fbt_data( 'position_display_setting', 'below-product-summary' ),
				'woostify_fbt_total_discount'     => $woostify_fbt_total_discount,
			);
		}

		/**
		 * Add product to cart
		 */
		public static function woostify_fbt_add_all_to_cart() {
			if ( ! ( isset( $_POST['_nonce'] ) && wp_verify_nonce( $_POST['_nonce'], 'woostify_fbt_nonce') ) ) {
				$message = __( 'The nonce check wrong.', 'woostify-pro' );
				self::woostify_fbt_message_error( $message );
			}

			if ( ( ! isset( $_POST['list_product_id'] ) ) || ( ! is_array( $_POST['list_product_id'] ) ) ) {
				self::woostify_fbt_message_error(  __( 'List product is empty.', 'woostify-pro' ) );
			}

			$arr_product_id        = $_POST['list_product_id'];
			// $main_pro_variable      = $_POST['variable'];
			// $main_pro_meta          = $_POST['main_pro_meta'];
			$custom_bundle_variable = $_POST['bundle_variable'];
			// $arr_product_id         = explode( ',', $list_product_id );

			foreach ( $arr_product_id as $product_id ) {
				try {
					$variation_id = 0;
					if ( 'product_variation' === get_post_type( $product_id ) ) {
						$variation_id = $product_id;
						$product_id   = wp_get_post_parent_id( $variation_id );
					}
					$p_data = !empty($custom_bundle_variable[$product_id]) ? $custom_bundle_variable[$product_id] : array();
					WC()->cart->add_to_cart( $product_id, '1' , $variation_id, $p_data);
					// This function die a json with content: "wp_send_json_success( $data );" .

				} catch ( Exception $e ) {
					if ( $e->getMessage() ) {
						wc_add_notice( $e->getMessage(), 'error' );
					}
					return false;
				}
			}
			die( true );
			if ( count( $arr_product_id ) > 0 && is_numeric( $arr_product_id[0] ) ) {
				$product_parent = wc_get_product( $arr_product_id[0] );
				if ( $product_parent ) {
					$parent_id = $product_parent->get_parent_id();
					$parent_id = $parent_id ? $parent_id : $product_parent->get_id();
					$product_bundle = get_post_meta( $parent_id, 'woostify_fbt', true );
					if ( $product_bundle ) {
						self::woostify_fbt_add_to_cart_total_discount( $arr_product_id, $product_bundle, $main_pro_variable, $custom_bundle_variable, $main_pro_meta );
					}
				}
			}
			die;
		}

		/**
		 * Add product to cart with total discount type
		 *
		 * @return
		 */
		public static function woostify_fbt_add_to_cart_total_discount( $arr_product_id, $product_bundle, $variation_id, $custom_bundle_variable, $main_pro_meta ) {

			$main_product_id = $main_product_custom_price = $bundle_variation_id = 0;

			$bundle_product_added = $variable = $bundle_variable = $percent_arr = array();

			// Get main product.
			if ( ! empty( $arr_product_id[0] ) ) {
				$main_product_id = $variation_id > 0 ? $variation_id : $arr_product_id[0];
				$main_product    = wc_get_product( $main_product_id );
				if ( $main_product && $main_product->is_in_stock() ) {
					$main_product_id = $main_product->get_id();
					$main_product_custom_price = $main_product->get_price();
					if ( 'variable' === $main_product->get_type() ) {
						$variable  = $main_pro_meta;
						$main_product_custom_price = $main_pro_meta['price'];
					}
				}
			}

			if ( is_array( $product_bundle ) ) {
				foreach( $product_bundle as $key => $val ) {
					$percent_arr[] = 0;
					if ( isset( $val['percent'] ) && $val['percent'] ) {
						$percent_arr[] = $val['percent'];
					}
					if ( in_array( $val['id'], $arr_product_id ) ) {
						$product              = wc_get_product( $val['id'] );
						$variation_product_id = $product->get_id();
						if ( $product && $product->is_in_stock() ) {
							$price                  = 0;
							$bundle_product_added[] = $variation_product_id;

							if ( $product->is_type( 'variable' ) && ! empty( $val['variable'] ) && is_array($val['variable']) ) {
								$variation_product = new WC_Product_Variable( $variation_product_id );
								$price             = $variation_product->get_price();

								$variations = $variation_product->get_available_variations();																

								if ( !empty($variations) && !empty( $custom_bundle_variable[$variation_product_id]['variable'] ) ){
									$variation_price_found = false;
									foreach ($variations as $single_variation){

										foreach( $single_variation['attributes'] as $single_variation_attr_key => $single_variation_attr_value ){
										if ( !empty($custom_bundle_variable[$variation_product_id][$single_variation_attr_key]) && $custom_bundle_variable[$variation_product_id][$single_variation_attr_key] == $single_variation_attr_value){
											$price = (float)$single_variation['display_price'];											
											$variation_price_found = true;
											break;
										}
										}
										if ($variation_price_found){
											break;
										}
									}
								}
							} else {
								$price = $product->get_price();
							}
							if ( isset( $custom_bundle_variable[$variation_product_id]['price'] ) && count( $custom_bundle_variable[$variation_product_id] ) > 0 ) {
								$custom_bundle_variable[$variation_product_id]['price'] = $price;
							}
							//var_dump($price);
							$main_product_custom_price = $main_product_custom_price + $price;						
						}
					}
				}
			}

			if ( ! empty( $main_product_custom_price ) ) {
				// Get percent of discount total price
				$percent = 0;
				for ( $i = count( $bundle_product_added ); $i >= 0 ; $i-- ) {
					if ( !empty( $percent_arr[$i-1] ) ) {
						$percent = $percent_arr[$i-1];
						break;
					} else {
						$percent = 0;
					}
				}

				// Set discount
				if ( count( $bundle_product_added ) > 0 && count( $bundle_product_added ) <= count( $product_bundle ) ) {	
					$main_product_custom_price = $main_product_custom_price - ( ( $main_product_custom_price * $percent ) / 100 );
				}

				$cart_item_data = array(
					'bundle-products' => implode( ",", $bundle_product_added ),
					'custom-price' => $main_product_custom_price,
					'custom-price-with-filter' => self::raw_price_with_filter($main_product_custom_price),
					'bundle-variable' => $custom_bundle_variable,
				);

				try {
					$data = WC()->cart->add_to_cart( $main_product_id, '1', $variation_id, $variable, $cart_item_data );
					// This function die a json with content: "wp_send_json_success( $data );" .
				} catch ( Exception $e ) {
					if ( $e->getMessage() ) {
						wc_add_notice( $e->getMessage(), 'error' );
					}
					return false;
				}
			}

		}

		/**
		 * Raw price with filter
		 *
		 * @param string $single_price Product price.
		 * @return string
		 */
		public static function raw_price_with_filter( $single_price ) {
			return str_replace( get_woocommerce_currency_symbol(), '', wp_strip_all_tags( wc_price( $single_price ) ) );
		}

		/**
		 * Message error as json
		 *
		 * @param string $message Message content.
		 * @return void
		 */
		public static function woostify_fbt_message_error( $message = '' ) {
			if ( ! empty( $message ) ) {
				exit( json_encode( array( 'message' => $message ) ) );
			} else {
				exit( json_encode( array( 'message' => __( 'Data not isset.', 'woostify-pro' ) ) ) );
			}
		}

		/**
		 * Update product bundle in cart
		 *
		 * @return json
		 */
		public static function woostify_fbt_update_product_bundle_in_cart( $cart_object ) {
			if ( count( $cart_object->cart_contents ) > 0 ) {
				if ( Woostify_FBT_Settings::get_fbt_data( 'woostify_fbt_discount_type' ) == 'discount-per-item' ) {
					foreach ( $cart_object->cart_contents as $key => $cart_item ) {
						if ( ! empty( $cart_item['bundle-parent'] ) ) {
							$cart_item['data']->set_price( $cart_item['custom-price'] );
						}
					}
				} else {
					foreach ( $cart_object->cart_contents as $key => $cart_item ) {
						if ( ! empty( $cart_item['bundle-products'] ) ) {
							$cart_item['data']->set_price( $cart_item['custom-price'] );
						}
					}
				}
			}
		}

		/**
		 * Update mini cart
		 *
		 * @return
		 */
		public static function woostify_fbt_update_mini_cart() {
			echo self::woostify_fbt_plugin_path() . '/woocommerce/cart/mini-cart.php';
			die();
		}

		/**
		 * add price after run filter.
		 *
		 * @return data array with price after run filter.
		 */
		public static function woostify_fbt_woocommerce_add_price_with_filter( $datas, $parent, $variation ) {
			$datas['price_with_filter'] = self::raw_price_with_filter( $datas['display_price'] );
			return $datas;
		}

		/**
		 * Change checkout order detail
		 *
		 * @return
		 */
		public static function woostify_fbt_woocommerce_change_checkout_order_detail( $item_id, $values, $cart ) {
			$custom_variable 	= $values['bundle-variable'];
			$bundles 			= get_post_meta( $values['product_id'], 'woostify_fbt', true );
			$bundles_added 		= explode( ',', $values['bundle-products'] );
			$order_extra_meta_data 	= '';
			if ( $bundles && count( $bundles_added ) > 0 ) {
				foreach( $bundles as $key => $val ){

					if ( in_array( $val['product_id'], $bundles_added ) ) {

						$product_item 			= wc_get_product( intval( $val['product_id'] ) );
						if ( $product_item ) {
							$order_extra_meta_data .= '<li><a href="'. $product_item->get_permalink() .'" title="'. $product_item->get_title() .'">' . $product_item->get_title() . '</a> ';

							// Get variable
							if ( isset( $custom_variable[$val['product_id']] ) && count( $custom_variable[$val['product_id']] ) > 0 ) {
								// Get variable default of product bundle
								$order_extra_meta_data .= '<span class="db" style="text-transform: capitalize;">' . $custom_variable[$val['product_id']]['variable'] . '</span> ';
							} else {
								if ( ! empty( $val['variable'] ) ) {
									$i = 0;
									foreach ( $val['variable'] as $key => $value ) { $i++;
										if ( $i == count( $val['variable'] ) ) {
											$order_extra_meta_data .= '<span class="db" style="text-transform: capitalize;">' . substr( $key, 13 ) . ': ' . $value . '</span>';
										}else {
											$order_extra_meta_data .= '<span class="db" style="text-transform: capitalize;">' . substr( $key, 13 ) . ': ' . $value . '</span> + ';
										}
									}
								}
							}

							$order_extra_meta_data .= '</li>';
						}
					}
				}
				if ($order_extra_meta_data != '') {
					wc_add_order_item_meta( $item_id, esc_html__( 'Product Bundles', 'woostify-pro' ), '<ul style="clear: both;">'.$order_extra_meta_data.'</ul>' );
				}
			}
		}

		/**
		 * Custom woocommerce template path
		 *
		 * @return string
		 */
		public static function woostify_fbt_woocommerce_locate_template( $template, $template_name, $template_path ) {

			global $woocommerce;
			$_template = $template;

			if ( ! $template_path ) $template_path = $woocommerce->template_url;
			$plugin_path  = self::woostify_fbt_plugin_path() . '/woocommerce/';

			// Look within passed path within the theme - this is priority
			$template = locate_template(
				array(
					$template_path . $template_name,
					$template_name
				)
			);

			// Modification: Get the template from this plugin, if it exists
			if ( ! $template && file_exists( $plugin_path . $template_name ) )
				$template = $plugin_path . $template_name;

			// Use default template
			if ( ! $template )
				$template = $_template;

			// Return what we found
			return $template;
		}

	}

	new Woostify_FBT_Frontend();
}
