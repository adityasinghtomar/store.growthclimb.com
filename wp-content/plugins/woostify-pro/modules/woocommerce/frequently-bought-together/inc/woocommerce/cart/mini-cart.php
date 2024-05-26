<?php
/**
 * Mini-cart
 *
 * Contains the markup for the mini-cart, used by the cart widget.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/mini-cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_mini_cart' );

	if ( ! WC()->cart->is_empty() ) {
		?>
		<ul class="woocommerce-mini-cart cart_list product_list_widget">
			<?php
			do_action( 'woocommerce_before_mini_cart_contents' );

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
				if (
					( function_exists( 'wc_pb_get_bundled_cart_item_container' ) && wc_pb_get_bundled_cart_item_container( $cart_item ) ) /* Support WC bundle plugin */ ||
					defined( 'WOOCO_VERSION' ) && isset( $cart_item['wooco_pos'] ) && $cart_item['wooco_pos'] // Support WPC Composite Products for WooCommerce plugin.
				) {
					continue;
				}

				if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 ) {
					$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
					$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
					$product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
					$stock_quantity    = $_product->get_stock_quantity();
					if( get_post_meta( $product_id, '_stock_status', true ) == 'onpreorder' ){
						$stock_quantity = get_post_meta( $product_id, '_onpreorder_maximum_order', true );
					}
					?>
					<li class="woocommerce-mini-cart-item mini_cart_item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?>">
						<?php
						echo apply_filters( // phpcs:ignore
							'woocommerce_cart_item_remove_link',
							sprintf(
								'<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">&times;</a>',
								esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
								esc_attr__( 'Remove this item', 'woostify' ),
								esc_attr( $product_id ),
								esc_attr( $cart_item_key ),
								esc_attr( $_product->get_sku() )
							),
							$cart_item_key
						);
						?>
						<?php if ( empty( $product_permalink ) ) : ?>
							<?php echo $thumbnail . $product_name; // phpcs:ignore ?>
						<?php else : ?>
							<a href="<?php echo esc_url( $product_permalink ); ?>">
								<?php echo $thumbnail . $product_name; // phpcs:ignore ?>
							</a>
						<?php endif; ?>
						<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore ?>

						<span class="mini-cart-product-infor">
							<span class="mini-cart-quantity" <?php echo esc_attr( $_product->is_sold_individually() ? 'data-sold_individually' : '' ); ?>>
								<span class="mini-cart-product-qty" data-qty="minus">
								<?php Woostify_Icon::fetch_svg_icon( 'minus' ); ?>
								</span>

								<input type="number" data-cart_item_key="<?php echo esc_attr( $cart_item_key ); ?>" class="input-text qty" step="<?php echo esc_attr( apply_filters( 'woocommerce_quantity_input_step', 1, $_product ) ); ?>" min="<?php echo esc_attr( apply_filters( 'woocommerce_quantity_input_min', $_product->get_min_purchase_quantity(), $_product ) ); ?>" max="<?php echo esc_attr( $stock_quantity ? $stock_quantity : '' ); ?>" value="<?php echo esc_attr( $cart_item['quantity'] ); ?>" inputmode="numeric" <?php echo esc_attr( $_product->is_sold_individually() ? 'disabled' : '' ); ?>>

								<span class="mini-cart-product-qty" data-qty="plus">
								<?php Woostify_Icon::fetch_svg_icon( 'plus' ); ?>
								</span>
							</span>
							<?php
								if ( class_exists( 'Woostify_FBT' ) ) {
									$bundles       = get_post_meta( $product_id, 'woostify_fbt', true );
									$bundles_added = explode( ',', ( isset( $cart_item['bundle-products'] ) ? $cart_item['bundle-products'] : '' ) );

									if ( isset( $cart_item['bundle-products'] ) && $cart_item['bundle-products'] ) {
										$product_price = apply_filters( 'woocommerce_widget_cart_item_quantity', wc_price( round( $cart_item['custom-price'], 2 ) ), $cart_item, $cart_item_key );
									} else {
										$product_price = apply_filters( 'woocommerce_widget_cart_item_quantity',  $product_price, $cart_item, $cart_item_key );
									}
								}
							?>
							<span class="mini-cart-product-price"><?php echo wp_kses_post( $product_price ); ?></span>

							<?php do_action( 'woostify_mini_cart_item_after_price', $_product ); ?>
						</span>

						<?php 
							if ( class_exists( 'Woostify_FBT' ) ) {
								if ( isset( $cart_item['bundle-products'] ) && $cart_item['bundle-products'] != '' ) {
									$bundles       = get_post_meta( $product_id, 'woostify_fbt', true );
									$bundles_added = explode( ',', ( isset( $cart_item['bundle-products'] ) ? $cart_item['bundle-products'] : '' ) );									
									if ( $bundles ) {
										$custom_variable = $cart_item['bundle-variable'];

										echo '<ul class="product-bundle fr pd__0">';
										foreach( $bundles as $key => $val ) {										
											if ( isset($val['id']) && in_array( $val['id'], $bundles_added ) ) {
												$product_item = wc_get_product( intval( $val['id'] ) );

												echo '<li class="pr">';
													echo '<a href="'. $product_item->get_permalink() .'" title="'. $product_item->get_name() .'">'. $product_item->get_name() .'</a>';
													// Get variable
													if ( ! empty( $val['variable'] ) ) {
														$variable = wp_unslash( $val['variable'] );

														if ( isset( $custom_variable[$val['id']] ) && count( $custom_variable[$val['id']] ) > 0 ) {
															// Custom variable before add produt bundle to cart
															echo '<span class="db" style="text-transform: capitalize;">';
																echo wp_kses_post($custom_variable[$val['id']]['variable']);
															echo '</span>';
														} else {
															if ( ! empty( $val['variable'] ) ) {
																foreach ( $val['variable'] as $key => $value ) {
																	echo '<span class="db" style="text-transform: capitalize;">';
																		echo substr( $key, 13 ) . ': ' . $value;
																	echo '</span>';
																}
															}
														}
													}
												echo '</li>';
											}
										}
										echo '</ul>';
									}
								}
							}
						?>
					</li>
					<?php
				}
			}

			do_action( 'woocommerce_mini_cart_contents' );
			?>
		</ul>

		<div class="woocommerce-mini-cart__bottom">
			<p class="woocommerce-mini-cart__total total<?php echo class_exists( 'BM_Live_Price' ) ? ' bm-cart-total-price' : ''; ?>">
				<?php
				/**
				 * Hook: woocommerce_widget_shopping_cart_total.
				 *
				 * @hooked woocommerce_widget_shopping_cart_subtotal - 10
				 */
				do_action( 'woocommerce_widget_shopping_cart_total' );
				?>
			</p>

			<?php do_action( 'woocommerce_widget_shopping_cart_before_buttons' ); ?>

			<p class="woocommerce-mini-cart__buttons buttons"><?php do_action( 'woocommerce_widget_shopping_cart_buttons' ); ?></p>
			<?php do_action( 'woocommerce_widget_shopping_cart_after_buttons' ); ?>
		</div>
		<?php
	} else {
		$options       = woostify_options( false );
		$empty_msg     = $options['mini_cart_empty_message'];
		$enable_button = $options['mini_cart_empty_enable_button'];
		?>
		<div class="woocommerce-mini-cart__empty-message">
			<div class="woostify-empty-cart">
				<div class="message-icon"><?php Woostify_Icon::fetch_svg_icon( 'shopping-cart-2' ); ?></div>
				<p class="message-text"><?php echo esc_html( $empty_msg ); ?></p>
				<?php if ( $enable_button ) { ?>
					<a class="button continue-shopping" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php esc_html_e( 'Continue Shopping', 'woostify' ); ?></a>
				<?php } ?>
			</div>
		</div>
		<?php
	}

	do_action( 'woocommerce_after_mini_cart' );



