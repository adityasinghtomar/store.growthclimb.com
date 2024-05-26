<?php
/**
 * Description
 *
 * Woostify Frequently Bought Together woocommerce
 * modules/woocommerce/frequently-bought-together/inc/class-woostify-fbt-functions.php
 *
 * @package Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woostify_FBT_Functions' ) ) {
	/**
	 * Woostify_FBT_Functions
	 */
	class Woostify_FBT_Functions {

		/**
		 * Constructor.
		 */
		public function __construct() {
			// add_action( 'woocommerce_add_to_cart', array( __CLASS__, 'update_fbt_discount_after_added' ), 100 );
			// add_action( 'woocommerce_cart_item_removed', array( __CLASS__, 'update_fbt_discount_after_removed' ), 100 );
			// add_action( 'woocommerce_cart_item_set_quantity', array( __CLASS__, 'update_fbt_discount_after_quantity' ), 100 );
			// add_action( 'woocommerce_cart_item_restored', array( __CLASS__, 'update_fbt_discount_after_removed' ), 100 );

			// Reference: https://wp-kama.com/plugin/woocommerce/hook/woocommerce_calculated_total.
			add_action( 'woocommerce_calculated_total', array( __CLASS__, 'fbt_calculate_totals_with_discount' ), 100 );

			add_action( 'woocommerce_review_order_before_order_total', array( __CLASS__, 'fbt_discount_html' ), 10 );
			add_action( 'woocommerce_cart_totals_before_order_total', array( __CLASS__, 'fbt_discount_html' ), 10 );
			// remove_action( 'woocommerce_widget_shopping_cart_total', 'woocommerce_widget_shopping_cart_subtotal', 10 );
			// add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'woocommerce_cart_totals', 10 );
		}

		/**
		 * Display FBT discount before total price
		 * place: before order total
		 */
		public static function fbt_discount_html() {
			$discount = self::calc_fbt_discount();
			$options = get_option( 'woostify_fbt_options' );
			$discount_text = !empty( $options['discount_text'] ) ? $options['discount_text'] : esc_html__( 'Frequently Bought Together Discount', 'woostify-pro' );
			// ob_start();
			?>
			<tr class="cart-discount woostify-fbt-discount">
				<th>
					<?php echo $discount_text; //phpcs:ignore ?>
				</th>
				<td data-title="<?php echo $discount_text; //phpcs:ignore ?>">-<?php echo wc_price( $discount ); ?></td>
			</tr>

			<?php
			// return ob_get_clean();
		}

		/**
		 * Update cart total
		 *
		 * @param string $total total proce of cart.
		 * @param array  $cart cart.
		 * @return float price after discount.
		 * Reference: https://wp-kama.com/plugin/woocommerce/hook/woocommerce_calculated_total.
		 */
		public static function fbt_calculate_totals_with_discount( $total, $cart = null ) {
			$discount = self::calc_fbt_discount();
			if ( ! empty( $discount ) && ( $total > $discount ) ) {
				$total -= $discount;
			}
			return $total;
		}

		/**
		 * Update FBT discount after add a product to cart.
		 *
		 * @param string $cart_item_key Cart Item key.
		 * @param int    $quantity quantity.
		 * @param array  $cart cart.
		 */
		public static function update_fbt_discount_after_quantity( $cart_item_key = '', $quantity = '', $cart = array() ) {
			self::calc_fbt_discount();

		}

		/**
		 * Update FBT discount after add a product to cart.
		 *
		 * @param string $cart_item_key Cart Item key.
		 * @param int    $product_id product_id.
		 * @param int    $quantity quantity.
		 * @param int    $variation_id variation_id.
		 * @param array  $variation variation.
		 * @param array  $cart_item_data cart_item_data.
		 */
		public static function update_fbt_discount_after_added( $cart_item_key = '', $product_id = '', $quantity = '', $variation_id = '', $variation = array(), $cart_item_data = array() ) {
			self::calc_fbt_discount();

		}

		/**
		 * Update FBT discount after removed a product from cart.
		 *
		 * @param string $cart_item_key Cart Item key.
		 * @param object $cart WC_Legacy_Cart Object.
		 */
		public static function update_fbt_discount_after_removed( $cart_item_key, $cart ) {
			self::calc_fbt_discount();

		}

		/**
		 * Add discount to cart total price.
		 * Logic 1: "FBT product" có số lượng >1 thì chỉ tính cho sp đầu tiên.
		 * Logic 2: Nếu có 2 product cùng chọn 1 product làm "FBT product" thì lấy discount lớn hơn.
		 * 1. Hiện tại không thấy code liên quan đến 2 options là
		 * ---- woostify_fbt_options[display_products_saved] --
		 * - và woostify_fbt_options[type] --------------------
		 * nên tạm thời chưa tính đến 2 options này, sẽ bổ sung sau.
		 * 15-09-2023 - DONE item 1
		 *
		 * 2. Hiện tại cũng không thấy phân biệt các attibute của product nên tạm thời áp dụng cho tất cả các variations
		 */
		public static function calc_fbt_discount() {
			$options = get_option( 'woostify_fbt_options' );
			$is_percent_off = ( empty( $options['display_products_saved'] ) || $options['display_products_saved'] == 'percent_off' ); // Default: percent
			$is_discount_item = ( empty( $options['type'] ) || $options['type'] == 'discount-per-item' ); // Default: discount-per-item
			$cart = WC()->cart;
			if ( $cart->is_empty() ) {
				self::remove_fbt_discount();
				return;
			}
			$cart_items = $cart->get_cart();
			$discount   = 0;

			// Giữa If và else có logic hơi ngược nhau. cẩn thận khi sửa
			if( $is_discount_item ){
				$cart_products = array();
				$fbts          = array();
				foreach ( $cart_items as $key => $cart_item ) {
					$product_id       = $cart_item['product_id'];
					$product          = wc_get_product( $product_id );
					$fbt              = get_post_meta( $product_id, 'woostify_fbt', true );
					$cart_item['fbt'] = $fbt;
					if ( ! empty( $fbt ) ) {
						foreach ( $fbt as $fbt_product ) {
							$p_id = $fbt_product['id'];
							if ( empty( $fbts[ $p_id ] ) ) {
								$fbts[ $p_id ] = array();
							}
							$_discount = !empty(  $fbt_product['discount'] ) ? floatval( $fbt_product['discount'] ) : 0;
							$fbts[ $p_id ][ $_discount . '_' . $product_id ] = array(
								'product_id' => $product_id,
								'discount'    => $_discount,
							);
						}
					}

					$cart_products[ $product_id ] = $cart_item;
				}
				$discount   = 0;
				$discount_r = array();

				foreach ( $cart_products  as $product_id => $cart_item ) {
					// $cart_item
					$product_id = $cart_item['product_id'];
					$_product   = wc_get_product( $product_id );
					$price      = $cart_item['data']->get_price();;

					// If current product is fbt of other product.
					if ( ! empty( $fbts[ $product_id ] ) ) {
						// List of products on which the current product depends. Order by discount percent desc.
						$_fbts = $fbts[ $product_id ];
						krsort( $_fbts, SORT_NATURAL );

						// Quantity of current project.
						$fbt_qty = intval( $cart_item['quantity'] );

						// Get quantity of origin project, check discount. org = origin product.
						foreach ( $_fbts as $k => $_fbt ) {
							$org_id       = $_fbt['product_id'];
							$_discount      = !empty( $_fbt['discount'] ) ? floatval( $_fbt['discount'] ) : 0;
							$org_qty      = intval( $cart_products[ $org_id ]['quantity'] );
							$discount_qty = min( $fbt_qty, $org_qty );
							if( $is_percent_off ){
								$_discount    = round( ( $discount_qty * $price * $_discount / 100 ), 2 );
							}
							$discount    += $_discount;
							$discount_r[] = array(
								'org_product_id' => $org_id,
								'product_id'     => $product_id,
								'product_title'  => $_product->get_title(),
								'price'          => $price,
								'discount_qty'   => $discount_qty,
								'discount'       => $_discount,

							);

							$fbt_qty -= $discount_qty;
							if ( $fbt_qty == 0 ) { // phpcs:ignore
								break;
							}
						}
					}
				}
			} else { // Discount total
				$cart_products = array();
				$fbts          = array();
				foreach ( $cart_items as $key => $cart_item ) {
					$product_id        = $cart_item['product_id'];
					$product           = wc_get_product( $product_id );
					$fbts              = get_post_meta( $product_id, 'woostify_fbt', true );
					$cart_item['fbts'] = $fbts;

					$cart_products[ $product_id ] = $cart_item;
				}
				foreach ( $cart_products as $product_id => $cart_item ) {
					if( !empty( $cart_item['fbts'] ) ){
						$will_discount = 1;
						foreach( $cart_item['fbts'] as $fbt_product ){
							$p_id = $fbt_product['id'];
							if ( empty( $cart_products[ $p_id ] ) ) {
								$will_discount = 0;
							}
						}
						if( $will_discount ){
							$_bundle_discount = get_post_meta( $product_id, 'woostify_fbt_total_discount', true );
							$_bundle_price = $cart_item['data']->get_price();
							$_bundle_qty = intval( $cart_item['quantity'] );
							foreach( $cart_item['fbts'] as $fbt_product ){
								$p_id = $fbt_product['id'];
								$fbt_qty = $cart_products[$p_id]['quantity'];
								$_bundle_qty = min( $_bundle_qty, $fbt_qty );
								$_bundle_price += $cart_products[$p_id]['data']->get_price();
							}

							// Remove cac product đã giảm giá
							foreach( $cart_item['fbts'] as $fbt_product ){
								$p_id = $fbt_product['id'];
								$cart_products[$p_id]['quantity'] -= $_bundle_qty;
							}

							$_bundle_discount = $is_percent_off ? round( $_bundle_price * $_bundle_discount / 100, 2 ) : $_bundle_discount;
							$_bundle_discount *= $_bundle_qty;
							$discount += $_bundle_discount;
						}
					}
				}
			}
			return $discount;
		}

		public static function remove_fbt_discount() {
			// WC()->cart->wooostify_fbt_discount = 0;
		}

		/**
		 * Get product attribute
		 *
		 * @param object  $product Product Object.
		 * @param integer $item_id Attribute.
		 * @param array   $data_selected Selected data.
		 * @param string  $class_main class string for product_variations item.
		 */
		public static function get_product_attribute( $product, $item_id = 0, $data_selected = array(), $class_main = '' ) {
			if( empty( $data_selected ) ) {
				$data_selected = array( ) ;
			}
			$product_attr_html = '';
			$price             = $product->get_price();
			$price_html        = $product->get_price_html();

			if ( $product->is_type( 'variable' ) ) {
				$attributes           = $product->get_variation_attributes();
				$available_variations = $product->get_available_variations();
				$selected_variations  = isset( $available_variations[0]['attributes'] ) ? $available_variations[0]['attributes'] : array();
				if ( $attributes ) {
					$product_attr_html .= '<div class="product_variations ' . $class_main . '" data-variations="' . htmlspecialchars( wp_json_encode( $available_variations ) ) . '" data-product_id="' . $product->get_id() . '">';
					foreach ( $attributes as $attribute => $options ) {
						if ( ! empty( $options ) ) {
							if ( count( $data_selected ) > 0 ) {
								$selected = $data_selected[ 'attribute_' . $attribute ];
							} else {
								$selected = isset( $selected_variations[ 'attribute_' . $attribute ] ) ? $selected_variations[ 'attribute_' . $attribute ] : '';
							}
							$onchange           = is_admin() ? 'onchange="frequently_variation_change_in_list(this)"' : '';
							$product_attr_html .= '<select ' . $onchange . ' data-attribute="' . esc_attr( sanitize_title( $attribute ) ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" name="woostify_fbt[' . $item_id . '][variable][attribute_' . esc_attr( $attribute ) . ']">';

							if ( $product && taxonomy_exists( $attribute ) ) {
								$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );

								foreach ( $terms as $term ) {
									if ( in_array( $term->slug, $options ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
										$product_attr_html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $selected ), $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>';
									}
								}
							} else {
								foreach ( $options as $option ) {
									$selected = sanitize_title( $selected ) === $selected ? selected( $selected, sanitize_title( $option ), false ) : selected( $selected, $option, false );

									$product_attr_html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $option ) . '</option>';
								}
							}
							$product_attr_html .= '</select>';
						}
					}
					$product_attr_html .= '</div>';
				}

				if ( isset( $available_variations[0]['display_price'] ) ) {
					$price      = $available_variations[0]['display_price'];
					$price_html = wc_price( $price );
				}
			}
			return array(
				'html'       => $product_attr_html,
				'price'      => $price,
				'price_html' => $price_html,
			);
		}
	}

	$woostify_fbt_functions = new Woostify_FBT_Functions();
}
