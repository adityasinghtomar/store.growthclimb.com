<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_CF_WOO_Order' ) ) :

	final class WPSC_CF_WOO_Order {

		/**
		 * Slug for this custom field type
		 *
		 * @var string
		 */
		public static $slug = 'cf_woo_order';

		/**
		 * Set whether this custom field type is of type date
		 *
		 * @var boolean
		 */
		public static $is_date = false;

		/**
		 * Set whether this custom field type has applicable to date range
		 *
		 * @var boolean
		 */
		public static $has_date_range = false;

		/**
		 * Set whether this custom field type has multiple values
		 *
		 * @var boolean
		 */
		public static $has_multiple_val = false;

		/**
		 * Data type for column created in tickets table
		 *
		 * @var string
		 */
		public static $data_type = 'BIGINT NULL DEFAULT 0';

		/**
		 * Set whether this custom field type has reference to other class
		 *
		 * @var boolean
		 */
		public static $has_ref = true;

		/**
		 * Reference class for this custom field type so that its value(s) return with object or array of objects automatically. Empty string indicate no reference.
		 *
		 * @var string
		 */
		public static $ref_class = 'wpsc_woo_order';

		/**
		 * Set whether this custom field field type is system default (no fields can be created from it).
		 *
		 * @var boolean
		 */
		public static $is_default = false;

		/**
		 * Set whether this field type has extra information that can be used in ticket form, edit custom fields, etc.
		 *
		 * @var boolean
		 */
		public static $has_extra_info = true;

		/**
		 * Set whether this custom field type can accept personal info.
		 *
		 * @var boolean
		 */
		public static $has_personal_info = false;

		/**
		 * Set whether fields created from this custom field type is allowed in create ticket form
		 *
		 * @var boolean
		 */
		public static $is_ctf = true;

		/**
		 * Set whether fields created from this custom field type is allowed in ticket list
		 *
		 * @var boolean
		 */
		public static $is_list = true;

		/**
		 * Set whether fields created from this custom field type is allowed in ticket filter
		 *
		 * @var boolean
		 */
		public static $is_filter = true;

		/**
		 * Set whether fields created from this custom field type can be given character limits
		 *
		 * @var boolean
		 */
		public static $has_char_limit = false;

		/**
		 * Set whether this custom field has user given custom options
		 *
		 * @var boolean
		 */
		public static $has_options = false;

		/**
		 * Set whether fields created from this custom field type can be available for ticket list sorting
		 *
		 * @var boolean
		 */
		public static $is_sort = true;

		/**
		 * Set whether fields created from this custom field type can be auto-filled
		 *
		 * @var boolean
		 */
		public static $is_auto_fill = true;

		/**
		 * Set whether fields created from this custom field type can have placeholder
		 *
		 * @var boolean
		 */
		public static $is_placeholder = true;

		/**
		 * Set whether fields created from this custom field type is applicable for visibility conditions in create ticket form
		 *
		 * @var boolean
		 */
		public static $is_visibility_conditions = false;

		/**
		 * Set whether fields created from this custom field type is applicable for macros
		 *
		 * @var boolean
		 */
		public static $has_macro = true;

		/**
		 * Set whether fields of this custom field type is applicalbe for search on ticket list page.
		 *
		 * @var boolean
		 */
		public static $is_search = true;

		/**
		 * Initialize the class
		 *
		 * @return void
		 */
		public static function init() {

			// Get object of this class.
			add_filter( 'wpsc_load_ref_classes', array( __CLASS__, 'load_ref_class' ) );

			// Set custom field type.
			add_filter( 'wpsc_cf_types', array( __CLASS__, 'add_cf_type' ), 100 );

			// create as.
			add_action( 'wpsc_js_after_change_create_as', array( __CLASS__, 'js_change_create_as' ) );
			add_action( 'wp_ajax_wpsc_woo_get_create_as_orders', array( __CLASS__, 'get_create_as_orders' ) );
			add_action( 'wp_ajax_nopriv_wpsc_woo_get_create_as_orders', array( __CLASS__, 'get_create_as_orders' ) );

			// TFF!
			add_action( 'wpsc_create_ticket_data', array( __CLASS__, 'set_create_ticket_data' ), 10, 3 );
			add_filter( 'wpsc_fun_get_object', array( __CLASS__, 'get_object' ), 10, 2 );

			// ticket search query.
			add_filter( 'wpsc_ticket_search', array( __CLASS__, 'ticket_search' ), 10, 5 );

			// create ticket data for rest api.
			add_filter( 'wpsc_rest_create_ticket', array( __CLASS__, 'set_rest_ticket_data' ), 10, 3 );

			// get customer fields order details.
			add_action( 'wp_ajax_wpsc_woo_customer_get_order_details', array( __CLASS__, 'customer_get_order_details' ) );
			add_action( 'wp_ajax_nopriv_wpsc_woo_customer_get_order_details', array( __CLASS__, 'customer_get_order_details' ) );
		}

		/**
		 * Load current class to ref classes
		 *
		 * @param array $classes - ref classes array.
		 * @return array
		 */
		public static function load_ref_class( $classes ) {

			$classes[ self::$slug ] = array(
				'class'    => __CLASS__,
				'save-key' => 'id',
			);
			return $classes;
		}

		/**
		 * Add custom field type to list
		 *
		 * @param array $cf_types - custom field types array.
		 * @return array
		 */
		public static function add_cf_type( $cf_types ) {

			$cf_types[ self::$slug ] = array(
				'label' => esc_attr__( 'WOO Order', 'wpsc-woo' ),
				'class' => __CLASS__,
			);
			return $cf_types;
		}

		/**
		 * Set object of order
		 *
		 * @param WPSC_CF_WOO_Order $value - value of object.
		 * @param string            $ref_class - wpsc ref class.
		 * @return mixed
		 */
		public static function get_object( $value, $ref_class ) {

			if ( $ref_class == 'wpsc_woo_order' ) {
				$order = wc_get_order( $value );
				return $order ? $order : $value;
			}
			return $value;
		}

		/**
		 * Print operators for ticket form filter
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param array             $filter - user filter.
		 * @return void
		 */
		public static function get_operators( $cf, $filter = array() ) {?>

			<div class="item conditional">
				<select class="operator" onchange="wpsc_tc_get_operand(this, '<?php echo esc_attr( $cf->slug ); ?>', '<?php echo esc_attr( wp_create_nonce( 'wpsc_tc_get_operand' ) ); ?>');">
					<option value=""><?php echo esc_attr( wpsc__( 'Compare As', 'supportcandy' ) ); ?></option>
					<option <?php isset( $filter['operator'] ) && selected( $filter['operator'], '=' ); ?> value="="><?php echo esc_attr( wpsc__( 'Equals', 'supportcandy' ) ); ?></option>
					<option <?php isset( $filter['operator'] ) && selected( $filter['operator'], 'IN' ); ?> value="IN"><?php echo esc_attr( wpsc__( 'Matches', 'supportcandy' ) ); ?></option>
					<option <?php isset( $filter['operator'] ) && selected( $filter['operator'], 'NOT IN' ); ?> value="NOT IN"><?php echo esc_attr( wpsc__( 'Not Matches', 'supportcandy' ) ); ?></option>
				</select>
			</div>
			<?php
		}

		/**
		 * Print operators for ticket form filter
		 *
		 * @param string            $operator - operator used.
		 * @param WPSC_Custom_field $cf - custom field object.
		 * @param string            $filter - user filter.
		 * @return void
		 */
		public static function get_operands( $operator, $cf, $filter = array() ) {

			$value = isset( $filter['operand_val_1'] ) ? stripslashes( $filter['operand_val_1'] ) : '';
			if ( in_array( $operator, array( 'IN', 'NOT IN' ) ) ) {
				?>

				<div class="item conditional operand single">
					<textarea class="operand_val_1" placeholder="<?php echo esc_attr( wpsc__( 'One condition per line!', 'supportcandy' ) ); ?>" style="width: 100%;"><?php echo esc_attr( $value ); ?></textarea>
				</div>
				<?php

			} else {
				?>

				<div class="item conditional operand single">
					<input type="number" class="operand_val_1" value="<?php echo intval( $value ); ?>"/>
				</div>
				<?php
			}
		}

		/**
		 * Parse filter and return sql query to be merged in ticket model query builder
		 *
		 * @param WPSC_Custom_Field $cf - custom field of this type.
		 * @param string            $compare - comparison operator.
		 * @param int               $val - value to compare.
		 * @return string
		 */
		public static function parse_filter( $cf, $compare, $val ) {

			$str = '';

			switch ( $compare ) {

				case '=':
					$str = self::get_sql_slug( $cf ) . '=\'' . esc_sql( $val ) . '\'';
					break;

				case 'IN':
					$str = 'CONVERT(' . self::get_sql_slug( $cf ) . ' USING utf8) IN(\'' . implode( '\', \'', esc_sql( $val ) ) . '\')';
					break;

				case 'NOT IN':
					$str = 'CONVERT(' . self::get_sql_slug( $cf ) . ' USING utf8) NOT IN(\'' . implode( '\', \'', esc_sql( $val ) ) . '\')';
					break;

				default:
					$str = '1=1';
			}

			return $str;
		}

		/**
		 * Return slug string to be used in where condition of ticket model for this type of field
		 *
		 * @param WPSC_Custom_field $cf - custom field object.
		 * @return string
		 */
		public static function get_sql_slug( $cf ) {

			$join_char = in_array( $cf->field, array( 'ticket', 'agentonly' ) ) ? 't.' : 'c.';
			return $join_char . $cf->slug;
		}

		/**
		 * Check condition for this type
		 *
		 * @param array             $condition - condition to check.
		 * @param WPSC_Custom_field $cf - custom field object.
		 * @param mixed             $val - value to compare.
		 * @return boolean
		 */
		public static function is_valid( $condition, $cf, $val ) {

			$value    = stripslashes( $value );
			$terms    = explode( PHP_EOL, $condition['operand_val_1'] );
			$response = false;
			switch ( $condition['operator'] ) {

				case '=':
					$response = $condition['operand_val_1'] == $val ? true : false;
					break;

				case 'IN':
					foreach ( $terms as $term ) {
						$term = intval( trim( $term ) );
						if ( $term == $value ) {
							$response = true;
							break;
						}
					}
					break;

				case 'NOT IN':
					$response = true;
					foreach ( $terms as $term ) {
						$term = intval( trim( $term ) );
						if ( $term == $value ) {
							$response = false;
							break;
						}
					}
					break;
			}
			return $response;
		}

		/**
		 * Print ticket form field
		 *
		 * @param WPSC_Custom_Field $cf - Custom field object.
		 * @param array             $tff - Array of ticket form field settings for this field.
		 * @return string
		 */
		public static function print_tff( $cf, $tff ) {

			$current_user = WPSC_Current_User::$current_user;
			if ( ! class_exists( 'WooCommerce' ) || $current_user->is_guest ) {
				return;
			}

			$purchases = wc_get_orders(
				array(
					'customer_id' => $current_user->user->ID,
					'orderby'     => 'date',
					'order'       => 'DESC',
					'limit'       => -1,
				)
			);

			$val = 0;
			if ( $cf->field == 'customer' ) {
				$val = $current_user->is_customer && $current_user->customer->{$cf->slug} ? $current_user->customer->{$cf->slug} : 0;
			}

			if ( $cf->field == 'ticket' && isset( $_POST['woo-order'] ) ) { // phpcs:ignore
				$order = wc_get_order( intval( $_POST['woo-order'] ) ); // phpcs:ignore
				$val   = $order ? $order : 0;
			}

			$woo_order = is_object( $val ) ? $val : false;
			$unique_id = uniqid( 'wpsc_' );

			ob_start();
			?>
			<div class="<?php echo esc_attr( WPSC_Functions::get_tff_classes( $cf, $tff ) ); ?>" data-cft="<?php echo esc_attr( self::$slug ); ?>">
				<div class="wpsc-tff-label">
					<span class="name"><?php echo esc_attr( $cf->name ); ?></span>
					<?php
					if ( $tff['is-required'] ) {
						?>
						<span class="required-indicator">*</span>
						<?php
					}
					?>
				</div>
				<span class="extra-info"><?php echo esc_attr( $cf->extra_info ); ?></span>
				<select class="<?php echo esc_attr( $unique_id ); ?>" name="<?php echo esc_attr( $cf->slug ); ?>">
					<option value=""></option>
					<?php
					foreach ( $purchases as $purchase ) {
						?>
						<option value="<?php echo esc_attr( $purchase->get_id() ); ?>" <?php $woo_order && selected( $woo_order->get_id(), $purchase->get_id() ); ?>><?php echo '#' . esc_attr( $purchase->get_order_number() ); ?></option>
						<?php
					}
					?>
				</select>
				<script>
					jQuery('select.<?php echo esc_attr( $unique_id ); ?>').selectWoo({
						allowClear: true,
						placeholder: "<?php echo esc_attr( $cf->placeholder_text ); ?>"
					});
				</script>
			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * Register JS function to call after change create as in ticket form
		 *
		 * @return void
		 */
		public static function js_change_create_as() {

			echo 'wpsc_woo_change_customer_orders(\'' . esc_attr( wp_create_nonce( 'wpsc_woo_get_create_as_orders' ) ) . '\');' . PHP_EOL;
		}

		/**
		 * Returns tff value available in $_POST
		 *
		 * @param string  $slug - Woo order slug.
		 * @param boolean $cf - custom field object.
		 * @return int
		 */
		public static function get_tff_value( $slug, $cf = false ) {

			return isset( $_POST[ $slug ] ) ? intval( $_POST[ $slug ] ) : 0; // phpcs:ignore
		}

		/**
		 * Check and return custom field value for new ticket to be created.
		 * Return false if do not want to be added in insert statement.
		 *
		 * @param array             $data - create ticket data.
		 * @param WPSC_Custom_Field $custom_fields - custom fields indexed by its type.
		 * @param boolean           $is_my_profile - set whether or not it is being used in my profile section.
		 * @return array
		 */
		public static function set_create_ticket_data( $data, $custom_fields, $is_my_profile ) {

			if ( isset( $custom_fields[ self::$slug ] ) ) {
				foreach ( $custom_fields[ self::$slug ] as $cf ) {
					$value = self::get_tff_value( $cf->slug );
					if ( $cf->field == 'ticket' && $value ) {

						$data[ $cf->slug ] = $value;

					} elseif ( $cf->field == 'customer' && $data['customer'] != 0 ) {

						$tff = get_option( 'wpsc-tff' );
						if ( ! $is_my_profile && ! isset( $tff[ $cf->slug ] ) ) {
							continue;
						}
						$customer     = new WPSC_Customer( $data['customer'] );
						$existing_val = $customer->{$cf->slug} ? $customer->{$cf->slug}->get_id() : '';
						$value        = $value === 0 ? '' : $value;
						if ( $existing_val != $value ) {
							$customer->{$cf->slug} = $value;
							$customer->save();

							// Set log for this change.
							WPSC_Log::insert(
								array(
									'type'         => 'customer',
									'ref_id'       => $customer->id,
									'modified_by'  => WPSC_Current_User::$current_user->customer->id,
									'body'         => wp_json_encode(
										array(
											'slug' => $cf->slug,
											'prev' => $existing_val,
											'new'  => $value,
										)
									),
									'date_created' => ( new DateTime() )->format( 'Y-m-d H:i:s' ),
								)
							);
						}
					}
				}
			}
			return $data;
		}

		/**
		 * Set create ticket data for rest api request
		 *
		 * @param array           $data - create ticket data array.
		 * @param WP_REST_Request $request - rest request object.
		 * @param array           $custom_fields - custom field objects indexed by unique custom field types.
		 * @return array
		 */
		public static function set_rest_ticket_data( $data, $request, $custom_fields ) {

			$current_user = WPSC_Current_User::$current_user;
			$tff = get_option( 'wpsc-tff' );

			if ( isset( $custom_fields[ self::$slug ] ) ) {
				foreach ( $custom_fields[ self::$slug ] as $cf ) {

					if (
						! in_array( $cf->field, array( 'ticket', 'agentonly', 'customer' ) ) ||
						( $cf->field == 'customer' && ! isset( $tff[ $cf->slug ] ) )
					) {
						continue;
					}

					$value = sanitize_text_field( $request->get_param( $cf->slug ) );

					// return if woo order id not available.
					if ( $value ) {
						$order = wc_get_order( $value );
					}

					if ( in_array( $cf->field, array( 'ticket', 'agentonly' ) ) ) {

						$data[ $cf->slug ] = $cf->field == 'ticket' && $value && $order->get_id() == $value ? $value : '';

					} else {

						$customer = new WPSC_Customer( $data['customer'] );
						$existing_val = $customer->{$cf->slug};
						$existing_val = is_object( $existing_val ) ? $existing_val->get_id() : '';
						$value = $value && $order->get_id() == $value ? $value : '';

						if ( $value && $value != $existing_val ) {

							$customer->{$cf->slug} = $value;
							$customer->save();

							// Set log for this change.
							WPSC_Log::insert(
								array(
									'type'         => 'customer',
									'ref_id'       => $customer->id,
									'modified_by'  => $current_user->customer->id,
									'body'         => wp_json_encode(
										array(
											'slug' => $cf->slug,
											'prev' => $existing_val,
											'new'  => $value,
										)
									),
									'date_created' => ( new DateTime() )->format( 'Y-m-d H:i:s' ),
								)
							);
						}
					}
				}
			}

			return $data;
		}

		/**
		 * Add ticket search compatibility for fields of this custom field type.
		 *
		 * @param array  $sql - Array of sql peices that can be joined later.
		 * @param array  $filter - User filter.
		 * @param array  $custom_fields - Custom fields array applicable for search.
		 * @param string $search - search string.
		 * @param array  $allowed_search_fields - Allowed search fields.
		 * @return array
		 */
		public static function ticket_search( $sql, $filter, $custom_fields, $search, $allowed_search_fields ) {

			if ( isset( $custom_fields[ self::$slug ] ) ) {
				foreach ( $custom_fields[ self::$slug ] as $cf ) {
					if ( in_array( $cf->slug, $allowed_search_fields ) ) {

						$join_char = in_array( $cf->field, array( 'ticket', 'agentonly' ) ) ? 't.' : 'c.';
						$sql[]     = $join_char . $cf->slug . ' = ' . $search;
					}
				}
			}

			return $sql;
		}

		/**
		 * Return val field for meta query of this type of custom field
		 *
		 * @param array $condition - condition operator.
		 * @return mixed
		 */
		public static function get_meta_value( $condition ) {

			$operator = $condition['operator'];
			switch ( $operator ) {

				case '=':
					return $condition['operand_val_1'];

				case 'IN':
				case 'NOT IN':
					$val = array_filter( array_map( 'intval', explode( PHP_EOL, $condition['operand_val_1'] ) ) );
					return $val ? $val : false;
			}
			return false;
		}

		/**
		 * Print edit ticket custom field in individual ticket
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param WPSC_Ticket       $ticket - ticket object.
		 * @return void
		 */
		public static function print_edit_ticket_cf( $cf, $ticket ) {

			if ( ! class_exists( 'WooCommerce' ) ) {
				return;
			}

			$purchases = array();

			if ( $ticket->customer->user ) {

				$customer = new WPSC_Customer( $ticket->customer->user->ID );
				$purchases = wc_get_orders(
					array(
						'customer_id' => $ticket->customer->user->ID,
						'orderby'     => 'date',
						'order'       => 'DESC',
						'limit'       => -1,
					)
				);
			}

			$unique_id = uniqid( 'wpsc_' );
			?>
			<div class="wpsc-tff wpsc-xs-12 wpsc-sm-12 wpsc-md-12 wpsc-lg-12 wpsc-visible" data-cft="<?php echo esc_attr( self::$slug ); ?>">
				<div class="wpsc-tff-label">
					<span class="name"><?php echo esc_attr( $cf->name ); ?></span>
				</div>
				<?php
				$extra_info = stripslashes( $cf->extra_info );
				if ( $extra_info ) {
					?>
					<span class="extra-info"><?php echo esc_attr( $cf->extra_info ); ?></span>
					<?php
				}
				?>
				<select class="<?php echo esc_attr( $unique_id ); ?>" name="<?php echo esc_attr( $cf->slug ); ?>">
					<option value=""></option>
					<?php
					foreach ( $purchases as $purchase ) {
						?>
						<option value="<?php echo esc_attr( $purchase->get_id() ); ?>" <?php $ticket->{$cf->slug} && selected( $ticket->{$cf->slug}->get_id(), $purchase->get_id() ); ?>><?php echo '#' . esc_attr( $purchase->get_order_number() ); ?></option>
						<?php
					}
					?>
				</select>
				<script>
					jQuery('select.<?php echo esc_attr( $unique_id ); ?>').selectWoo({
						allowClear: true,
						placeholder: "<?php echo esc_attr( $cf->placeholder_text ); ?>"
					});
				</script>
			</div>
			<?php
		}

		/**
		 * Set edit individual ticket for this custom field type
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param WPSC_Ticket       $ticket - ticket object.
		 * @return WPSC_Ticket
		 */
		public static function set_edit_ticket_cf( $cf, $ticket ) {

			$prev = $ticket->{$cf->slug} ? $ticket->{$cf->slug}->get_id() : '';
			$new  = isset( $_POST[ $cf->slug ] ) ? intval( $_POST[ $cf->slug ] ) : ''; // phpcs:ignore

			$new = $new === 0 ? '' : $new;

			// Exit if there is no change.
			if ( $prev == $new ) {
				return $ticket;
			}

			// Change value.
			$ticket->{$cf->slug} = $new;
			$ticket->save();

			return $ticket;
		}

		/**
		 * Modify ticket field value of this custom field type using rest api
		 *
		 * @param WPSC_Ticket       $ticket - ticket object.
		 * @param WPSC_Custom_Field $cf - custom field.
		 * @param mixed             $value - value to be set.
		 * @return void
		 */
		public static function set_rest_edit_ticket_cf( $ticket, $cf, $value ) {

			// return if woo product id not available.
			$order = wc_get_order( intval( $value ) );
			if ( $value && ! $order ) {
				return;
			}

			$prev = is_object( $ticket->{$cf->slug} ) ? $ticket->{$cf->slug}->get_id() : '';

			if ( $prev != $value ) {
				$ticket->{$cf->slug} = intval( $value );
			}
		}

		/**
		 * Insert log thread for this custom field type change
		 *
		 * @param WPSC_Custom_Field $cf - current custom field of this type.
		 * @param WPSC_Ticket       $prev - ticket object before making any changes.
		 * @param WPSC_Ticket       $new - ticket object after making changes.
		 * @param string            $current_date - date string to be stored as create time.
		 * @param int               $customer_id - current user customer id for blame.
		 * @return void
		 */
		public static function insert_ticket_log( $cf, $prev, $new, $current_date, $customer_id ) {

			// Exit if there is no change.
			if ( $prev->{$cf->slug} == $new->{$cf->slug} ) {
				return;
			}

			$prev_val = $prev->{$cf->slug} ? $prev->{$cf->slug}->get_id() : '';
			$new_val  = $new->{$cf->slug} ? $new->{$cf->slug}->get_id() : '';

			$thread = WPSC_Thread::insert(
				array(
					'ticket'       => $prev->id,
					'customer'     => $customer_id,
					'type'         => 'log',
					'body'         => wp_json_encode(
						array(
							'slug' => $cf->slug,
							'prev' => $prev_val,
							'new'  => $new_val,
						)
					),
					'date_created' => $current_date,
					'date_updated' => $current_date,
				)
			);
		}

		/**
		 * Return data for this custom field while creating duplicate ticket
		 *
		 * @param WPSC_Custom_field $cf - custom field object.
		 * @param WPSC_Ticket       $ticket - ticket object.
		 * @return mixed
		 */
		public static function get_duplicate_ticket_data( $cf, $ticket ) {

			return $ticket->{$cf->slug} ? $ticket->{$cf->slug}->get_id() : '';
		}

		/**
		 * Print edit field for this type in edit customer info
		 *
		 * @param WPSC_Custom_field $cf - custom field object.
		 * @param WPSC_Customer     $customer - customer object.
		 * @param string            $tff - ticket form field data.
		 * @return string
		 */
		public static function print_edit_customer_info( $cf, $customer, $tff ) {

			if ( ! class_exists( 'WooCommerce' ) ) {
				return;
			}

			$purchases = array();

			if ( $customer->user ) {

				$customer = new WPSC_Customer( $customer->user->ID );
				$purchases = wc_get_orders(
					array(
						'customer_id' => $customer->user->ID,
						'orderby'     => 'date',
						'order'       => 'DESC',
						'limit'       => -1,
					)
				);
			}

			$unique_id = uniqid( 'wpsc_' );

			ob_start();
			?>
			<div class="<?php echo esc_attr( WPSC_Functions::get_tff_classes( $cf, $tff ) ); ?>" data-cft="<?php echo esc_attr( self::$slug ); ?>">
				<div class="wpsc-tff-label">
					<span class="name"><?php echo esc_attr( $cf->name ); ?></span>
				</div>
				<span class="extra-info"><?php echo esc_attr( $cf->extra_info ); ?></span>
				<select class="<?php echo esc_attr( $unique_id ); ?>" name="<?php echo esc_attr( $cf->slug ); ?>">
					<option value=""></option>
					<?php
					$value = $customer->{$cf->slug} ? $customer->{$cf->slug}->get_id() : 0;
					foreach ( $purchases as $purchase ) {
						?>
						<option value="<?php echo esc_attr( $purchase->get_id() ); ?>" <?php selected( $value, $purchase->get_id() ); ?>><?php echo '#' . intval( $purchase->get_order_number() ); ?></option>
						<?php
					}
					?>
				</select>
				<script>
					jQuery('select.<?php echo esc_attr( $unique_id ); ?>').selectWoo({
						allowClear: true,
						placeholder: "<?php echo esc_attr( $cf->placeholder_text ); ?>"
					});
				</script>
			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * Options for custom field setting of this type
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param string            $type - custom field, e.g. ticket-fields, agentonly-fields, customer-fields, etc.
		 * @return void
		 */
		public static function edit_cf_setting_body( $cf, $type ) {
			?>

			<div class="wpsc-input-group personal-info">
				<div class="label-container">
					<label for=""><?php echo esc_attr( wpsc__( 'Has personal info?', 'supportcandy' ) ); ?></label>
				</div>
				<select name="is_personal_info">
					<option <?php selected( $cf->is_personal_info, '0' ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					<option <?php selected( $cf->is_personal_info, '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
				</select>
			</div>

			<div class="wpsc-input-group placeholder-text">
				<div class="label-container">
					<label for=""><?php echo esc_attr( wpsc__( 'Placeholder text', 'supportcandy' ) ); ?></label>
				</div>
				<input type="text" name="placeholder_text" value="<?php echo esc_attr( $cf->placeholder_text ); ?>" autocomplete="off" />
			</div>
			<?php

			if ( $cf->field == 'customer' ) :
				?>
				<div class="wpsc-input-group is-edit">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Allow in my profile?', 'supportcandy' ) ); ?></label>
					</div>
					<select name="allow_my_profile">
						<option <?php selected( $cf->allow_my_profile, '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $cf->allow_my_profile, '0' ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<div class="wpsc-input-group is-edit">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Allow in ticket form?', 'supportcandy' ) ); ?></label>
					</div>
					<select name="allow_my_profile">
						<option <?php selected( $cf->allow_ticket_form, '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $cf->allow_ticket_form, '0' ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<?php
			endif;
		}

		/**
		 * Create as customer field values
		 *
		 * @return void
		 */
		public static function get_create_as_orders() {

			if ( check_ajax_referer( 'wpsc_woo_get_create_as_orders', '_ajax_nonce', false ) !== 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'create-as' ) ) ) {
				wp_send_json_error( new WP_Error( '001', 'Unauthorized!' ), 400 );
			}

			$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			if ( ! $email ) {
				wp_send_json_error( new WP_Error( '002', 'Something went wrong!' ), 400 );
			}

			WPSC_Current_User::change_current_user( $email );

			$tff      = get_option( 'wpsc-tff' );
			$response = array();
			foreach ( $tff as $slug => $properties ) {

				$cf = WPSC_Custom_Field::get_cf_by_slug( $slug );
				if ( ! $cf ) {
					continue;
				}
				if ( $cf->type::$slug == self::$slug ) {
					$response[] = array(
						'slug' => $slug,
						'html' => $cf->type::print_tff( $cf, $properties ),
					);
				}
			}
			wp_send_json( $response );
		}

		/**
		 * Get customer purchase details
		 *
		 * @return void
		 */
		public static function customer_get_order_details() {

			if ( check_ajax_referer( 'wpsc_woo_customer_get_order_details', '_ajax_nonce', false ) !== 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$current_user = WPSC_Current_User::$current_user;

			$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
			if ( ! $order_id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$customer_id = isset( $_POST['customer_id'] ) ? intval( $_POST['customer_id'] ) : 0;
			if ( ! $customer_id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$customer = new WPSC_Customer( intval( $customer_id ) );
			if ( ! $customer->id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$order = wc_get_order( $order_id );
			if ( empty( $order ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			if ( $customer->email != $order->get_billing_email() ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$title = sprintf(
				/* translators: %s: payment id */
				esc_attr__( 'Order %s', 'wpsc-woo' ),
				'#' . $order_id
			);

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
			if ( ! $current_user->is_guest && $current_user->user->has_cap( 'manage_options' ) ) :
				?>
				<a href="<?php echo esc_url_raw( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ); ?>" target="_blank" style="text-decoration: none;">
					<button class="wpsc-button small primary">
						<?php esc_attr_e( 'View Order', 'wpsc_woo' ); ?>
					</button>
				</a>
				<?php
			endif;
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

			wp_send_json( $response, 200 );
		}

		/**
		 * Print add new custom field setting properties
		 *
		 * @param string $field_class - Class name of the field.
		 * @return void
		 */
		public static function get_add_new_custom_field_properties( $field_class ) {

			if ( in_array( 'extra_info', $field_class::$allowed_properties ) ) :
				?>
				<div data-type="textfield" data-required="false" class="wpsc-input-group extra-info">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Extra info', 'supportcandy' ) ); ?></label>
					</div>
					<input name="extra_info" type="text" autocomplete="off" />
				</div>
				<?php
			endif;

			if ( in_array( 'placeholder_text', $field_class::$allowed_properties ) ) :
				?>
				<div data-type="textfield" data-required="false" class="wpsc-input-group placeholder_text">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Placeholder', 'supportcandy' ) ); ?>
						</label>
					</div>
					<input type="text" name="placeholder_text" autocomplete="off">
				</div>
				<?php
			endif;

			if ( in_array( 'tl_width', $field_class::$allowed_properties ) ) :
				?>
				<div data-type="number" data-required="false" class="wpsc-input-group tl_width">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Ticket list width (pixels)', 'supportcandy' ) ); ?>
						</label>
					</div>
					<input type="number" name="tl_width" autocomplete="off">
				</div>
				<?php
			endif;

			if ( in_array( 'is_personal_info', $field_class::$allowed_properties ) ) :
				?>
				<div data-type="single-select" data-required="false" class="wpsc-input-group is_personal_info">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Has personal info', 'supportcandy' ) ); ?>
						</label>
					</div>
					<select name="is_personal_info">
						<option value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
						<option value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<?php
			endif;

			if ( in_array( 'allow_my_profile', $field_class::$allowed_properties ) ) :
				?>
				<div data-type="single-select" data-required="false" class="wpsc-input-group allow_my_profile">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Allow in my profile?', 'supportcandy' ) ); ?>
						</label>
					</div>
					<select name="allow_my_profile">
						<option value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
						<option value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<?php
			endif;

			if ( in_array( 'allow_ticket_form', $field_class::$allowed_properties ) ) :
				?>
				<div data-type="single-select" data-required="false" class="wpsc-input-group allow_ticket_form">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Allow in ticket form?', 'supportcandy' ) ); ?>
						</label>
					</div>
					<select name="allow_ticket_form">
						<option value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
						<option value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<?php
			endif;
		}

		/**
		 * Print edit custom field properties
		 *
		 * @param WPSC_Custom_Fields $cf - custom field object.
		 * @param string             $field_class - class name of field category.
		 * @return void
		 */
		public static function get_edit_custom_field_properties( $cf, $field_class ) {

			if ( in_array( 'extra_info', $field_class::$allowed_properties ) ) :
				?>
				<div data-type="textfield" data-required="false" class="wpsc-input-group extra-info">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Extra info', 'supportcandy' ) ); ?></label>
					</div>
					<input name="extra_info" type="text" value="<?php echo esc_attr( $cf->extra_info ); ?>" autocomplete="off" />
				</div>
				<?php
			endif;

			if ( in_array( 'placeholder_text', $field_class::$allowed_properties ) ) :
				?>
				<div data-type="textfield" data-required="false" class="wpsc-input-group placeholder_text">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Placeholder', 'supportcandy' ) ); ?>
						</label>
					</div>
					<input type="text" name="placeholder_text" value="<?php echo esc_attr( $cf->placeholder_text ); ?>" autocomplete="off">
				</div>
				<?php
			endif;

			if ( in_array( 'tl_width', $field_class::$allowed_properties ) ) :
				?>
				<div data-type="number" data-required="false" class="wpsc-input-group tl_width">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Ticket list width (pixels)', 'supportcandy' ) ); ?>
						</label>
					</div>
					<input type="number" name="tl_width" value="<?php echo intval( $cf->tl_width ); ?>" autocomplete="off">
				</div>
				<?php
			endif;

			if ( in_array( 'is_personal_info', $field_class::$allowed_properties ) ) :
				?>
				<div data-type="single-select" data-required="false" class="wpsc-input-group is_personal_info">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Has personal info', 'supportcandy' ) ); ?>
						</label>
					</div>
					<select name="is_personal_info">
						<option <?php selected( $cf->is_personal_info, '0' ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
						<option <?php selected( $cf->is_personal_info, '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<?php
			endif;

			if ( in_array( 'allow_my_profile', $field_class::$allowed_properties ) ) :
				?>
				<div data-type="single-select" data-required="false" class="wpsc-input-group allow_my_profile">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Allow in my profile?', 'supportcandy' ) ); ?>
						</label>
					</div>
					<select name="allow_my_profile">
						<option <?php selected( $cf->allow_my_profile, '0' ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
						<option <?php selected( $cf->allow_my_profile, '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<?php
			endif;

			if ( in_array( 'allow_ticket_form', $field_class::$allowed_properties ) ) :
				?>
				<div data-type="single-select" data-required="false" class="wpsc-input-group allow_ticket_form">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Allow in ticket form?', 'supportcandy' ) ); ?>
						</label>
					</div>
					<select name="allow_ticket_form">
						<option <?php selected( $cf->allow_ticket_form, '0' ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
						<option <?php selected( $cf->allow_ticket_form, '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<?php
			endif;
		}

		/**
		 * Set custom field properties. Can be used by add/edit custom field.
		 * Ignore phpcs nonce issue as we already checked where it is called from.
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param string            $field_class - class of field category.
		 * @return void
		 */
		public static function set_cf_properties( $cf, $field_class ) {

			// extra info.
			if ( in_array( 'extra_info', $field_class::$allowed_properties ) ) {
				$cf->extra_info = isset( $_POST['extra_info'] ) ? sanitize_text_field( wp_unslash( $_POST['extra_info'] ) ) : ''; // phpcs:ignore
			}

			// placeholder!
			if ( in_array( 'placeholder_text', $field_class::$allowed_properties ) ) {
				$cf->placeholder_text = isset( $_POST['placeholder_text'] ) ? sanitize_text_field( wp_unslash( $_POST['placeholder_text'] ) ) : ''; // phpcs:ignore
			}

			// personal info.
			if ( in_array( 'is_personal_info', $field_class::$allowed_properties ) ) {
				$cf->is_personal_info = isset( $_POST['is_personal_info'] ) ? intval( $_POST['is_personal_info'] ) : 0; // phpcs:ignore
			}

			// my-profile.
			if ( in_array( 'allow_my_profile', $field_class::$allowed_properties ) ) {
				$cf->allow_my_profile = isset( $_POST['allow_my_profile'] ) ? intval( $_POST['allow_my_profile'] ) : 0; // phpcs:ignore
			}

			// ticket form.
			if ( in_array( 'allow_ticket_form', $field_class::$allowed_properties ) ) {
				$cf->allow_ticket_form = isset( $_POST['allow_ticket_form'] ) ? intval( $_POST['allow_ticket_form'] ) : 0; // phpcs:ignore
			}

			// tl_width!
			if ( in_array( 'tl_width', $field_class::$allowed_properties ) ) {
				$tl_width     = isset( $_POST['tl_width'] ) ? intval( $_POST['tl_width'] ) : 0; // phpcs:ignore
				$cf->tl_width = $tl_width ? $tl_width : 100;
			}

			// save!
			$cf->save();
		}

		/**
		 * Return orderby string
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @return string
		 */
		public static function get_orderby_string( $cf ) {

			return self::get_sql_slug( $cf );
		}

		/**
		 * Returns printable ticket value for custom field. Can be used in export tickets, replace macros etc.
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param WPSC_Ticket       $ticket - ticket object.
		 * @param string            $module - module name.
		 * @return string
		 */
		public static function get_ticket_field_val( $cf, $ticket, $module = '' ) {

			if ( ! class_exists( 'WooCommerce' ) ) {
				return;
			}

			$order = $ticket->{$cf->slug};
			$value = $order ? $order->get_order_number() : '';
			return apply_filters( 'wpsc_ticket_field_val_woo_order', $value, $cf, $ticket, $module );
		}

		/**
		 * Print ticket value for given custom field on ticket list
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param WPSC_Ticket       $ticket - ticket object.
		 * @return void
		 */
		public static function print_tl_ticket_field_val( $cf, $ticket ) {

			$order = self::get_ticket_field_val( $cf, $ticket );
			echo $order ? '#' . esc_attr( $order ) : '';
		}

		/**
		 * Print ticket value for given custom field on widget
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param WPSC_Ticket       $ticket - ticket object.
		 * @return void
		 */
		public static function print_widget_ticket_field_val( $cf, $ticket ) {

			$order_id = self::get_ticket_field_val( $cf, $ticket );
			if ( ! $order_id ) {
				return;
			}
			$order = $ticket->{$cf->slug};
			$current_user = WPSC_Current_User::$current_user;
			if ( $current_user->is_agent ) {
				?>
				<a href="javascript:wpsc_view_woo_order( <?php echo intval( $ticket->id ); ?>, <?php echo intval( $order->get_id() ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_view_woo_order' ) ); ?>' )" class="wpsc-link">#<?php echo esc_attr( $order_id ); ?></a>
				<?php
			} else {
				echo '#' . esc_attr( $order_id );
			}
		}

		/**
		 * Returns printable customer value for custom field. Can be used in export tickets, replace macros etc.
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param WPSC_Customer     $customer - customer object.
		 * @return string
		 */
		public static function get_customer_field_val( $cf, $customer ) {

			$order = $customer->{$cf->slug};
			return $order ? $order->get_order_number() : '';
		}

		/**
		 * Print customer value for given custom field on ticket list
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param WPSC_Customer     $customer - customer object.
		 * @return void
		 */
		public static function print_tl_customer_field_val( $cf, $customer ) {

			$order = self::get_customer_field_val( $cf, $customer );
			echo $order ? '#' . esc_attr( $order ) : '';
		}

		/**
		 * Print customer value for given custom field on widget
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param WPSC_Customer     $customer - customer object.
		 * @return void
		 */
		public static function print_widget_customer_field_val( $cf, $customer ) {

			$order = self::get_customer_field_val( $cf, $customer );
			if ( ! $order ) {
				return;
			}
			?>
			<a href="javascript:wpsc_woo_customer_get_order_details( <?php echo intval( $customer->id ); ?>, <?php echo intval( $order ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_woo_customer_get_order_details' ) ); ?>' )" class="wpsc-link">#<?php echo intval( $order ); ?></a>
			<?php
		}

		/**
		 * Print given value for custom field
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param mixed             $val - value to convert and print.
		 * @return void
		 */
		public static function print_val( $cf, $val ) {

			echo $val ? '#' . intval( $val ) : esc_attr( wpsc__( 'None', 'supportcandy' ) );
		}

		/**
		 * Return printable value for history log macro
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param mixed             $val - value to convert and return.
		 * @return string
		 */
		public static function get_history_log_val( $cf, $val ) {

			ob_start();
			self::print_val( $cf, $val );
			return ob_get_clean();
		}
	}
endif;

WPSC_CF_WOO_Order::init();
