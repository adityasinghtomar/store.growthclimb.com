<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_ITW_WOO' ) ) :

	final class WPSC_ITW_WOO {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// edit widget settings.
			add_action( 'wp_ajax_wpsc_get_tw_woo_order', array( __CLASS__, 'get_tw_woo' ) );
			add_action( 'wp_ajax_wpsc_set_tw_woo_order', array( __CLASS__, 'set_tw_woo' ) );

			// get purchase details.
			add_action( 'wp_ajax_wpsc_woo_get_purchase_details', array( __CLASS__, 'get_purchase_details' ) );
			add_action( 'wp_ajax_nopriv_wpsc_woo_get_purchase_details', array( __CLASS__, 'get_purchase_details' ) );

			// view order.
			add_action( 'wp_ajax_wpsc_view_woo_order', array( __CLASS__, 'view_woo_order' ) );
			add_action( 'wp_ajax_nopriv_wpsc_view_woo_order', array( __CLASS__, 'view_woo_order' ) );
			add_action( 'wp_ajax_wpsc_view_more_woo_orders', array( __CLASS__, 'view_more_orders' ) );
			add_action( 'wp_ajax_nopriv_wpsc_view_more_woo_orders', array( __CLASS__, 'view_more_orders' ) );
		}

		/**
		 * Prints body of current widget
		 *
		 * @param object $ticket - ticket object.
		 * @param array  $settings - settings array.
		 * @return void
		 */
		public static function print_widget( $ticket, $settings ) {

			$current_user = WPSC_Current_User::$current_user;
			if ( ! class_exists( 'WooCommerce' ) ) {
				return;
			}

			$tw = get_option( 'wpsc-ticket-widget' );
			if ( ! ( $current_user->agent && in_array( $current_user->agent->role, $tw['woo-order']['allowed-agent-roles'] ) ) || ! $ticket->customer->user ) {
				return;
			}

			$woo_orders = array();
			if ( $ticket->customer->user ) {

				$woo_orders = wc_get_orders(
					array(
						'customer_id'    => $ticket->customer->user->ID,
						'post_type'      => 'shop_order',
						'posts_per_page' => '-1',
						'orderby'        => 'date',
						'order'          => 'DESC',
					)
				);
			}
			$total = 0;
			$temp = array();
			foreach ( $woo_orders as $order ) :
				$total = $total + $order->get_total();
				$temp[] = $order->get_order_number();
			endforeach;?>
			<div class="wpsc-it-widget wpsc-itw-woo-order">
				<div class="wpsc-widget-header">
					<h2><?php echo esc_attr( $settings['title'] ); ?></h2>
				</div>
				<div class="wpsc-widget-body">
					<div class="info-list-item">
						<div class="info-label"><?php esc_attr_e( 'Total Spent:', 'wpsc-woo' ); ?></div>
						<div class="info-val"><?php echo wp_kses_post( wc_price( $total ) ); ?></div>
					</div>
					<?php
					if ( $woo_orders ) {
						$flag = count( $woo_orders ) > 5 ? true : false;
						$woo_orders = array_slice( $woo_orders, 0, 5 );
						if ( count( $temp ) > 0 ) {
							?>
							<h5 style="margin: 0px 0 2px; font-size: 12px;"><?php esc_attr_e( 'Recent Orders:', 'wpsc-woo' ); ?></h5>
							<?php
						}
						foreach ( $woo_orders as $order ) {
							?>
							<a class="wpsc-link" href="javascript:wpsc_view_woo_order(<?php echo esc_attr( $ticket->id ); ?>, <?php echo esc_attr( $order->get_id() ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_view_woo_order' ) ); ?>')" style="margin-bottom: 2px;">
								<?php
								echo '#' . esc_attr( $order->get_order_number() ) . ' - ' . esc_attr( date_i18n( get_option( 'date_format' ), strtotime( $order->get_date_created() ) ) ) . ' (' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . ')'
								?>
							</a>
							<?php
						}
						if ( $flag ) {
							?>
							<a class="wpsc-link" href="javascript:wpsc_view_more_woo_orders(<?php echo esc_attr( $ticket->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_view_more_woo_orders' ) ); ?>')" style="margin-bottom: 2px;"><?php esc_attr_e( 'View More', 'supportcandy' ); ?></a>
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
			$woo            = $ticket_widgets['woo-order'];
			$title          = $woo['title'];
			$roles          = get_option( 'wpsc-agent-roles', array() );
			ob_start();
			?>

			<form action="#" onsubmit="return false;" class="wpsc-frm-edit-woo-order">
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
							$selected = in_array( $key, $woo['allowed-agent-roles'] ) ? 'selected' : ''
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
				<input type="hidden" name="action" value="wpsc_set_tw_woo_order">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_tw_woo_order' ) ); ?>">

			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_set_tw_woo_order(this);">
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

			if ( check_ajax_referer( 'wpsc_set_tw_woo_order', '_ajax_nonce', false ) != 1 ) {
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

			$ticket_widgets                                     = get_option( 'wpsc-ticket-widget', array() );
			$ticket_widgets['woo-order']['title']               = $label;
			$ticket_widgets['woo-order']['is_enable']           = $is_enable;
			$ticket_widgets['woo-order']['allowed-agent-roles'] = $agents;
			update_option( 'wpsc-ticket-widget', $ticket_widgets );
			wp_die();
		}

		/**
		 * View order modal
		 *
		 * @return void
		 */
		public static function view_woo_order() {

			if ( check_ajax_referer( 'wpsc_view_woo_order', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			global $woo_receipt_args;

			$current_user = WPSC_Current_User::$current_user;
			WPSC_Individual_Ticket::load_current_ticket();
			$tw = get_option( 'wpsc-ticket-widget' );

			if ( ! (
				WPSC_Individual_Ticket::$view_profile == 'agent' &&
				WPSC_Individual_Ticket::has_ticket_cap( 'view' ) &&
				in_array( $current_user->agent->role, $tw['woo-order']['allowed-agent-roles'] ) )
			) {
				wp_send_json_error( new WP_Error( '004', 'Unauthorized!' ), 401 );
			}

			$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
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
			?>
			<div style="width: 100%;">
				<?php
				printf(
					esc_html( wpsc__( 'Order #%1$s was placed on %2$s and is currently %3$s.', 'woocommerce' ) ),
					'<mark class="order-number">' . $order->get_order_number() . '</mark>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'<mark class="order-date">' . wc_format_datetime( $order->get_date_created() ) . '</mark>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'<mark class="order-status">' . wc_get_order_status_name( $order->get_status() ) . '</mark>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
				do_action( 'woocommerce_view_order', $order_id );
				?>
				<style>
					table.woocommerce-table {
						width: 100%;
					}
					table.woocommerce-table thead th {
						text-align: left;
					}
					table.woocommerce-table th,
					table.woocommerce-table td {
						padding: 5px;
					}
					p.order-again {
						display: none;
					}
				</style>
			</div>
			<?php
			$body = ob_get_clean();

			ob_start();
			if ( ! $current_user->is_guest && ( $current_user->user->has_cap( 'manage_options' ) || $current_user->user->has_cap( 'shop_manager' ) ) ) {
				?>
				<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ); ?>" target="_blank" style="text-decoration: none;">
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

			if ( check_ajax_referer( 'wpsc_view_more_woo_orders', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			$current_user = WPSC_Current_User::$current_user;
			WPSC_Individual_Ticket::load_current_ticket();
			$tw = get_option( 'wpsc-ticket-widget' );

			if ( ! (
				WPSC_Individual_Ticket::$view_profile == 'agent' &&
				WPSC_Individual_Ticket::has_ticket_cap( 'view' ) &&
				in_array( $current_user->agent->role, $tw['woo-order']['allowed-agent-roles'] ) )
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
						$purchases = wc_get_orders(
							array(
								'customer_id' => $ticket->customer->user->ID,
								'post_type'   => 'shop_order',
								'orderby'     => 'date',
								'order'       => 'DESC',
								'limit'       => -1,
							)
						);

						if ( $purchases ) {
							foreach ( $purchases as $purchase ) {
								$courses = tutor_utils()->get_course_enrolled_ids_by_order_id( $purchase->get_id() );
								if ( tutor_utils()->count( $courses ) ) {
									continue;
								}
								?>
								<tr>
									<td>#<?php echo esc_attr( $purchase->get_id() ); ?></td>
									<td><?php echo esc_attr( wc_get_order_status_name( $purchase->get_status() ) ); ?></td>
									<td><?php echo esc_attr( date_i18n( get_option( 'date_format' ), strtotime( $purchase->get_date_created() ) ) ); ?></td>
									<td>
										<div style="display:flex; flex-direction: row;">  
											<a class="wpsc-link" href="javascript:wpsc_view_woo_order_tbl(<?php echo esc_attr( WPSC_Individual_Ticket::$ticket->id ); ?>, <?php echo esc_attr( $purchase->get_id() ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_view_woo_order' ) ); ?>' )" style="margin-bottom: 2px;">
												<?php echo esc_attr( wpsc_translate_common_strings( 'view-details' ) ); ?>
											</a>
											<?php
											if ( ! $current_user->is_guest && ( $current_user->user->has_cap( 'manage_options' ) || $current_user->user->has_cap( 'shop_manager' ) ) ) {
												$edit_order_details = admin_url( 'post.php?post=' . $purchase->get_id() . '&action=edit' );
												echo "&nbsp;|&nbsp;<a class='wpsc-link' href='" . esc_attr( $edit_order_details ) . "' target='_blank'>" . esc_attr__( 'View Order', 'wpsc-woo' ) . '</a>'
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
	}
endif;

WPSC_ITW_WOO::init();
