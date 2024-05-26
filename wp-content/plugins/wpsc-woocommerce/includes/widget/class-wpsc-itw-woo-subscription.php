<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_ITW_WOO_Subscription' ) ) :

	final class WPSC_ITW_WOO_Subscription {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// edit widget settings.
			add_action( 'wp_ajax_wpsc_get_tw_woo_subscription', array( __CLASS__, 'get_tw_woo' ) );
			add_action( 'wp_ajax_wpsc_set_tw_woo_subscription', array( __CLASS__, 'set_tw_woo' ) );

			// view order.
			add_action( 'wp_ajax_wpsc_view_woo_subscription', array( __CLASS__, 'view_woo_order' ) );
			add_action( 'wp_ajax_nopriv_wpsc_view_woo_subscription', array( __CLASS__, 'view_woo_order' ) );
			add_action( 'wp_ajax_wpsc_view_more_woo_subscriptions', array( __CLASS__, 'view_more_orders' ) );
			add_action( 'wp_ajax_nopriv_wpsc_view_more_woo_subscriptions', array( __CLASS__, 'view_more_orders' ) );
		}

		/**
		 * Prints body of current widget
		 *
		 * @param object $ticket - ticket object.
		 * @param array  $settings -  setting array.
		 * @return void
		 */
		public static function print_widget( $ticket, $settings ) {

			$current_user = WPSC_Current_User::$current_user;

			if ( ! class_exists( 'WC_Subscriptions' ) ) {
				return;
			}

			$tw = get_option( 'wpsc-ticket-widget' );
			if ( ! $current_user->is_agent || ! in_array( $current_user->agent->role, $tw['woo-sub']['allowed-agent-roles'] ) || ! $ticket->customer->user ) {
				return;
			}

			$sub_orders = array();
			if ( $ticket->customer->user ) {

				$sub_orders = wc_get_orders(
					array(
						'customer_id'    => $ticket->customer->user->ID,
						'post_type'      => 'shop_subscription',
						'posts_per_page' => '-1',
					)
				);
			}
			$total = 0;
			foreach ( $sub_orders as $order ) :
				$total = $total + $order->get_total();
			endforeach;?>
			<div class="wpsc-it-widget wpsc-itw-woo-sub">
				<div class="wpsc-widget-header">
					<h2><?php echo esc_attr( $settings['title'] ); ?></h2>
				</div>
				<div class="wpsc-widget-body">
					<div class="info-list-item">
						<div class="info-label"><?php esc_attr_e( 'Total Spent:', 'wpsc-woo' ); ?></div>
						<div class="info-val"><?php echo wp_kses_post( wc_price( $total ) ); ?></div>
					</div>
					<?php
					$subscriptions = wc_get_orders(
						array(
							'customer_id'    => $ticket->customer->user->ID,
							'post_type'      => 'shop_subscription',
							'posts_per_page' => '-1',
						)
					);

					$flag          = count( $subscriptions ) > 5 ? true : false;
					$subscriptions = array_slice( $subscriptions, 0, 5 );

					if ( $subscriptions ) {
						?>
						<h5 style="margin: 0px 0 2px; font-size: 12px;"><?php esc_attr_e( 'Recent Orders:', 'wpsc-woo' ); ?></h5>
						<?php
						foreach ( $subscriptions as $subscription ) {
							$orders = wc_get_order( $subscription );
							$order  = wc_get_order( $subscription->get_id() );
							?>
							<a class="wpsc-link" href="javascript:wpsc_view_woo_subscription(<?php echo esc_attr( $ticket->id ); ?>, <?php echo esc_attr( $subscription->get_id() ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_view_woo_subscription' ) ); ?>')" style="margin-bottom: 2px;">
								<?php
								echo '#' . esc_attr( $order->get_order_number() ) . ' - ' . esc_attr( date_i18n( get_option( 'date_format' ), strtotime( $orders->get_date_created() ) ) ) . ' (' . esc_html( wc_get_order_status_name( $orders->get_status() ) ) . ')'
								?>
							</a>
							<?php
						}
						if ( $flag ) {
							?>
							<a class="wpsc-link" href="javascript:wpsc_view_more_woo_subscriptions(<?php echo esc_attr( $ticket->id ); ?>)" style="margin-bottom: 2px;"><?php esc_attr_e( 'View More', 'supportcandy' ); ?></a>
							<?php
						}
					}
					do_action( 'wpsc_itw_woo', $ticket )
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Get edit widget settings
		 *
		 * @return void
		 */
		public static function get_tw_woo() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$ticket_widgets = get_option( 'wpsc-ticket-widget', array() );
			$woo            = $ticket_widgets['woo-sub'];
			$title          = $woo['title'];
			$roles          = get_option( 'wpsc-agent-roles', array() );
			ob_start();
			?>

			<form action="#" onsubmit="return false;" class="wpsc-frm-edit-woo-sub">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?></label>
					</div>
					<input name="label" type="text" value="<?php echo esc_attr( $woo['title'] ); ?>" autocomplete="off">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Enable', 'supportcandy' ) ); ?></label>
					</div>
					<select name="is_enable">
						<option <?php selected( $woo['is_enable'], '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $woo['is_enable'], '0' ); ?>  value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Allowed agent roles', 'supportcandy' ) ); ?></label>
					</div>
					<select multiple id="wpsc-select-agents" name="agents[]" placeholder="">
						<?php
						foreach ( $roles as $key => $role ) {
							$selected = in_array( $key, $woo['allowed-agent-roles'] ) ? 'selected' : '';
							?>
							<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $role['label'] ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
				<script>
					jQuery('#wpsc-select-agents').selectWoo({
						allowClear: false,
						placeholder: ""
					});
				</script>
				<?php do_action( 'wpsc_get_woo_body' ); ?>
				<input type="hidden" name="action" value="wpsc_set_tw_woo_subscription">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_tw_woo_subscription' ) ); ?>">

			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_set_tw_woo_subscription(this);">
				<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?>
			</button>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?>
			</button>
			<?php
			do_action( 'wpsc_get_tw_woo_order_widget_footer' );
			$footer = ob_get_clean();

			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response );
		}

		/**
		 * Set edit widget settings
		 *
		 * @return void
		 */
		public static function set_tw_woo() {

			if ( check_ajax_referer( 'wpsc_set_tw_woo_subscription', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
			if ( ! $label ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			$is_enable = isset( $_POST['is_enable'] ) ? intval( $_POST['is_enable'] ) : 0;
			$agents    = isset( $_POST['agents'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['agents'] ) ) ) : array();

			$ticket_widgets                                   = get_option( 'wpsc-ticket-widget', array() );
			$ticket_widgets['woo-sub']['title']               = $label;
			$ticket_widgets['woo-sub']['is_enable']           = $is_enable;
			$ticket_widgets['woo-sub']['allowed-agent-roles'] = $agents;
			update_option( 'wpsc-ticket-widget', $ticket_widgets );
			wp_die();
		}

		/**
		 * View order modal
		 *
		 * @return void
		 */
		public static function view_woo_order() {

			if ( check_ajax_referer( 'wpsc_view_woo_subscription', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			global $woo_receipt_args;

			$current_user = WPSC_Current_User::$current_user;
			WPSC_Individual_Ticket::load_current_ticket();
			$tw = get_option( 'wpsc-ticket-widget' );

			if ( ! (
				WPSC_Individual_Ticket::$view_profile == 'agent' &&
				WPSC_Individual_Ticket::has_ticket_cap( 'view' ) &&
				in_array( $current_user->agent->role, $tw['woo-sub']['allowed-agent-roles'] ) )
			) {
				wp_send_json_error( new WP_Error( '004', 'Unauthorized!' ), 401 );
			}

			$order_id = isset( $_POST['order_id'] ) ? intval( wp_unslash( $_POST['order_id'] ) ) : 0;
			if ( ! $order_id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$order = wc_get_order( $order_id );
			if ( empty( $order ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			/* translators: %s: order id */
			$title = sprintf( esc_attr__( 'Order %s', 'wpsc-woo' ), '#' . $order->get_order_number() );
			ob_start();
			$woo_receipt_args = array(
				'error'          => 'Sorry, trouble retrieving payment receipt.',
				'price'          => true,
				'discount'       => true,
				'products'       => true,
				'date'           => true,
				'notes'          => true,
				'payment_key'    => false,
				'payment_method' => true,
				'order_id'       => true,
			);
			?>

			<div style="width: 100%;">
				<table id="woo_subscription_receipt" class="woo-table">
					<thead>
					<?php
					do_action( 'woo_payment_receipt_before', $order, $woo_receipt_args );
					if ( filter_var( $woo_receipt_args['order_id'], FILTER_VALIDATE_BOOLEAN ) ) {
						?>
						<tr>
							<th><strong><?php esc_attr_e( 'Order', 'wpsc-woo' ); ?>:</strong></th>
							<th><?php echo esc_attr( $order->get_order_number() ); ?></th>
						</tr>
						<?php
					}
					?>
					</thead>
					<tbody>
						<tr>
							<td class="woo_receipt_payment_status"><strong><?php esc_attr_e( 'Order Status', 'wpsc-woo' ); ?>:</strong></td>
							<td class="woo_receipt_payment_status <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></td>
						</tr>
						<?php
						if ( filter_var( $woo_receipt_args['payment_key'], FILTER_VALIDATE_BOOLEAN ) ) {
							?>
							<tr>
								<td><strong><?php esc_attr_e( 'Payment Key:', 'wpsc-woo' ); ?></strong></td>
								<td><?php echo esc_attr( $order->get_order_key() ); ?></td>
							</tr>
							<?php
						}
						if ( filter_var( $woo_receipt_args['payment_method'], FILTER_VALIDATE_BOOLEAN ) ) {
							?>
							<tr>
								<td><strong><?php echo esc_attr( wpsc__( 'Payment Method:', 'woocommerce' ) ); ?></strong></td>
								<td><?php echo esc_attr( $order->get_payment_method_title() ); ?></td>
							</tr>
							<?php
						}
						if ( filter_var( $woo_receipt_args['date'], FILTER_VALIDATE_BOOLEAN ) ) {
							?>
							<tr>
								<td><strong><?php echo esc_attr( wpsc__( 'Date:', 'woocommerce' ) ); ?></strong></td>
								<td><?php echo esc_attr( date_i18n( get_option( 'date_format' ), strtotime( $order->get_date_created() ) ) ); ?></td>
							</tr>
							<?php
						}
						$fees = $order->get_fees();
						if ( $fees ) {
							?>
							<tr>
								<td><strong><?php echo esc_attr( wpsc__( 'Fees:', 'woocommerce' ) ); ?></strong></td>
								<td>
									<ul class="woo_receipt_fees">
									<?php foreach ( $fees as $fee ) { ?>
										<li>
											<span class="woo_fee_label"><?php echo esc_attr( $fee->get_name() ); ?></span>
											<span class="woo_fee_sep">&nbsp;&ndash;&nbsp;</span>
											<span class="woo_fee_amount"><?php esc_attr( $fee->get_total() ); ?></span>
										</li>
									<?php } ?>
									</ul>
								</td>
							</tr>
							<?php
						}
						if ( filter_var( $woo_receipt_args['discount'], FILTER_VALIDATE_BOOLEAN ) && $order->get_discount_total() ) {
							?>
							<tr>
								<td><strong><?php echo esc_attr( wpsc__( 'Discount:', 'woocommerce' ) ); ?></strong></td>
								<td><?php echo esc_attr( $order->get_discount_total() ); ?></td>
							</tr>
							<?php
						}
						if ( $order->get_total_tax() ) {
							?>
							<tr>
								<td><strong><?php echo esc_attr( wpsc__( 'Taxes:', 'woocommerce' ) ); ?></strong></td>
								<td><?php echo wp_kses_post( wc_price( $order->get_total_tax() ) ); ?></td>
							</tr>
							<?php
						}
						if ( filter_var( $woo_receipt_args['price'], FILTER_VALIDATE_BOOLEAN ) ) {
							?>
							<tr>
								<td><strong><?php echo esc_attr( wpsc__( 'Subtotal:', 'woocommerce' ) ); ?></strong></td>
								<td>
									<?php echo wp_kses_post( wc_price( $order->get_subtotal() ) ); ?>
								</td>
							</tr>
							<tr>
								<td><strong><?php echo esc_attr( wpsc__( 'Total:', 'woocommerce' ) ); ?></strong></td>
								<td><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></td>
							</tr>
							<?php
						}
						do_action( 'woo_payment_receipt_after', $order, $woo_receipt_args );
						?>
					</tbody>
				</table>
				<?php

				do_action( 'woo_payment_receipt_after_table', $order, $woo_receipt_args );
				if ( filter_var( $woo_receipt_args['products'], FILTER_VALIDATE_BOOLEAN ) ) {
					?>
					<h3><?php echo esc_attr( apply_filters( 'woo_payment_receipt_products_title', esc_attr__( 'Products', 'wpsc-woo' ) ) ); ?></h3>
					<table id="woo_subscription_receipt_products" class="woo-table">
						<thead>
							<th><?php echo esc_attr( wpsc__( 'Name', 'woocommerce' ) ); ?></th>
							<th><?php echo esc_attr( wpsc__( 'Quantity', 'woocommerce' ) ); ?></th>
							<th><?php echo esc_attr( wpsc__( 'Price', 'woocommerce' ) ); ?></th>
						</thead>
						<tbody>
						<?php
						if ( $order->get_items() ) {
							foreach ( $order->get_items() as $item_id => $item ) {
								if ( ! empty( $item['product_id'] ) ) {
									?>
									<tr>
										<td><?php echo esc_attr( $item['name'] ); ?></td>    
										<td><?php echo esc_attr( $item['quantity'] ); ?></td>
										<td><?php echo wp_kses_post( wc_price( $item['total'] ) ); ?></td>
										<?php
										// Allow extensions to extend the product cell.
										do_action( 'woo_subscription_receipt_after_files', $item['order_id'], $order->get_id() );
										?>
									</tr>
									<?php
								}
							}
						}
						if ( $order->get_fees() ) {
							foreach ( $fees as $fee ) {
								?>
								<tr>
									<td class="woo_fee_label"><?php echo esc_attr( $fee->get_name() ); ?></td>
									<td class="woo_fee_amount"><?php echo esc_attr( $fee->get_total() ); ?></td>
								</tr>
								<?php
							}
						}
						?>
						</tbody>
					</table>
					<?php
				}
				?>
			</div>
			<?php
			$body = ob_get_clean();

			ob_start();
			if ( ! $current_user->is_guest && ( $current_user->user->has_cap( 'manage_options' ) || $current_user->user->has_cap( 'shop_manager' ) ) ) {
				?>
				<a href="<?php echo esc_url_raw( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ); ?>" target="_blank" style="text-decoration:none;">
					<button class="wpsc-button small primary">
						<?php esc_attr_e( 'View Order', 'wpsc-woo' ); ?>
					</button>
				</a>
				<?php
			}
			?>

			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php echo esc_attr( wpsc__( 'Close', 'supportcandy' ) ); ?>
			</button>
			<?php
			$footer = ob_get_clean();

			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response );
		}

		/**
		 * View more woo orders
		 *
		 * @return void
		 */
		public static function view_more_orders() {

			$current_user = WPSC_Current_User::$current_user;
			WPSC_Individual_Ticket::load_current_ticket();
			$tw = get_option( 'wpsc-ticket-widget' );

			if ( ! (
				WPSC_Individual_Ticket::$view_profile == 'agent' &&
				WPSC_Individual_Ticket::has_ticket_cap( 'view' ) &&
				in_array( $current_user->agent->role, $tw['woo-sub']['allowed-agent-roles'] ) )
			) {
				wp_send_json_error( new WP_Error( '004', 'Unauthorized!' ), 401 );
			}

			$customer = WPSC_Individual_Ticket::$ticket->customer;
			$ticket   = WPSC_Individual_Ticket::$ticket;
			$title    = esc_attr__( 'WooCommerce order payments', 'wpsc-woo' );

			ob_start();
			?>
			<div style="width: 100%">
				<table class="woo-table">
					<tr>
						<th><?php echo esc_attr( wpsc__( 'Order', 'woocommerce' ) ); ?></th>
						<th><?php echo esc_attr( wpsc__( 'Status', 'woocommerce' ) ); ?></th>
						<th><?php echo esc_attr( wpsc__( 'Date', 'woocommerce' ) ); ?></th>
						<th><?php echo esc_attr( wpsc__( 'Actions', 'woocommerce' ) ); ?></th>
					</tr>
					<?php

					if ( $customer->id ) {
						$subscriptions = wc_get_orders(
							array(
								'customer_id' => $ticket->customer->user->ID,
								'post_type'   => 'shop_subscription',
								'orderby'     => 'date',
								'order'       => 'DESC',
								'limit'       => -1,
							)
						);

						if ( $subscriptions ) {
							foreach ( $subscriptions as $subscription ) {
								$orders = wc_get_order( $subscription );
								$order  = wc_get_order( $subscription->get_id() );
								?>
								<tr>
									<td><?php echo esc_attr( $order->get_order_number() ); ?></td>
									<td><?php echo esc_attr( wc_get_order_status_name( $order->get_status() ) ); ?></td>
									<td><?php echo esc_attr( date_i18n( get_option( 'date_format' ), strtotime( $orders->get_date_created() ) ) ); ?></td>
									<td>
										<div style="display:flex; flex-direction: column;">
											<a class="wpsc-link" href="javascript:wpsc_view_woo_subscription_tbl(<?php echo esc_attr( WPSC_Individual_Ticket::$ticket->id ); ?>, <?php echo esc_attr( $subscription->get_id() ); ?>)" style="margin-bottom: 2px;">
												<?php echo esc_attr( wpsc_translate_common_strings( 'view-details' ) ); ?>
											</a>
											<?php
											if ( ! $current_user->is_guest && ( $current_user->user->has_cap( 'manage_options' ) || $current_user->user->has_cap( 'shop_manager' ) ) ) {
												$edit_order_details = admin_url( 'post.php?post=' . $subscription->get_id() . '&action=edit' );
												echo " - <a class='wpsc-link' href='" . esc_attr( $edit_order_details ) . "' target='_blank'>" . esc_attr__( 'View Order', 'wpsc-woo' ) . '</a>'
												?>
												<?php
											}
											?>
										</div>
									</td>
								</tr>
								<?php
							}
						} else {
							?>
							<tr>
								<td colspan="4"><?php echo esc_attr( wpsc__( 'No orders found', 'woocommerce' ) ); ?></td>
							</tr>
							<?php
						}
					} else {
						?>
						<tr>
							<td colspan="4"><?php echo esc_attr( wpsc__( 'No orders found', 'woocommerce' ) ); ?></td>
						</tr>
						<?php
					}
					?>
				</table>
			</div>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php echo esc_attr( wpsc__( 'Close', 'supportcandy' ) ); ?>
			</button>
			<?php
			$footer = ob_get_clean();

			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response );
		}

		/**
		 * Get subscriptions details of customer
		 *
		 * @return void
		 */
		public static function get_subscription_details() {

			$current_user = WPSC_Current_User::$current_user;
			WPSC_Individual_Ticket::load_current_ticket();
			$tw = get_option( 'wpsc-ticket-widget' );

			if ( ! (
				WPSC_Individual_Ticket::$view_profile == 'agent' &&
				WPSC_Individual_Ticket::has_ticket_cap( 'view' ) &&
				in_array( $current_user->agent->role, $tw['woo-sub']['allowed-agent-roles'] ) )
			) {
				wp_send_json_error( new WP_Error( '001', 'Unauthorized!' ), 401 );
			}

			$subscription_id = isset( $_POST['subscription_id'] ) ? intval( $_POST['subscription_id'] ) : 0; //phpcs:ignore
			if ( ! $subscription_id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$order = wc_get_order( $subscription_id );
			$title = esc_attr__( 'Subscriptions details', 'wpsc-woo' );
			ob_start();
			?>
			<div style="width: 100%">
				<p>
				<?php
					printf(
						esc_attr( wpsc__( 'Subscriptions #%1$s was taken on %2$s and is currently %3$s.', 'woocommerce' ) ),
						'<mark class="order-number">' . esc_attr( $order->get_order_number() ) . '</mark>',
						'<mark class="order-date">' . esc_attr( wc_format_datetime( $order->get_date_created() ) ) . '</mark>',
						'<mark class="order-status">' . esc_attr( wc_get_order_status_name( $order->get_status() ) ) . '</mark>'
					);
				?>
				</p>
				<?php
				$notes = $order->get_customer_order_notes();
				if ( $notes ) {
					?>
					<h2><?php esc_attr_e( 'Subscriptions Update', 'wpsc-woocommerce' ); ?></h2>
					<ol class="woocommerce-OrderUpdates commentlist notes">
					<?php
					foreach ( $notes as $note ) {
						?>
						<li class="woocommerce-OrderUpdate comment note">
							<div class="woocommerce-OrderUpdate-inner comment_container">
								<div class="woocommerce-OrderUpdate-text comment-text">
								<p class="woocommerce-OrderUpdate-meta meta">
									<?php echo esc_attr( date_i18n( 'l jS \o\f F Y, h:ia', strtotime( $note->comment_date ) ) ); ?>
								</p>
								<div class="woocommerce-OrderUpdate-description description">
									<?php echo esc_attr( wpautop( wptexturize( $note->comment_content ) ) ); ?>
								</div>
								<div class="clear"></div>
								</div>
								<div class="clear"></div>
							</div>
						</li>
						<?php
					}
					?>
					</ol>
					<?php
				}
				do_action( 'woocommerce_subscription_details_table', $order );
				do_action( 'woocommerce_subscription_totals_table', $order );
				wc_get_template( 'order/order-details-customer.php', array( 'order' => $order ) );
				?>

				<script>
					jQuery('.woocommerce-table').addClass('table table-striped table-hover wpsc-woo');
					jQuery('.order-again').remove();
				</script>
			</div>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php echo esc_attr( wpsc__( 'Close', 'supportcandy' ) ); ?>
			</button>
			<?php
			$footer = ob_get_clean();

			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response );
		}
	}
endif;

WPSC_ITW_WOO_Subscription::init();
