<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_RP_WOO_Product' ) ) :

	final class WPSC_RP_WOO_Product {

		/**
		 * Initialize this class
		 */
		public static function init() {

			add_filter( 'allowed_cft_reports', array( __CLASS__, 'allowed_cft_report' ) );
			add_filter( 'wpsc_reports_sections', array( __CLASS__, 'add_section' ) );
			add_action( 'wp_ajax_wpsc_rp_get_woo_product', array( __CLASS__, 'layout' ) );
			add_action( 'wp_ajax_wpsc_rp_run_cfwp_report', array( __CLASS__, 'run_cfwp_reports' ) );
		}

		/**
		 * Allow product report in settings
		 *
		 * @param array $allowed_cft - List of allowed reports.
		 * @return array
		 */
		public static function allowed_cft_report( $allowed_cft ) {
			$allowed_cft[] = 'cf_woo_product';
			return $allowed_cft;
		}

		/**
		 * Add custom field menu in reports
		 *
		 * @param array $sections - report menu.
		 * @return array
		 */
		public static function add_section( $sections ) {

			$settings = get_option( 'wpsc-rp-settings' );
			foreach ( $settings['cf-reports'] as $id ) {
				$cf = new WPSC_Custom_Field( $id );
				if ( $cf->id && class_exists( $cf->type ) && $cf->type::$slug == 'cf_woo_product' ) {
					$sections[ $cf->slug ] = array(
						'slug'     => $cf->slug,
						'icon'     => 'chart-bar',
						'label'    => $cf->name,
						'callback' => 'wpsc_rp_get_cf_woo_product',
						'run'      => array(
							'shortname' => 'cfwp',
							'function'  => 'wpsc_rp_run_cfwp_report',
						),
					);
				}
			}

			return $sections;
		}

		/**
		 * Print woo product type custom field report layout
		 *
		 * @return void
		 */
		public static function layout() {

			$cf_slug = isset( $_POST['cf_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['cf_slug'] ) ) : '';
			if ( ! $cf_slug ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			if ( check_ajax_referer( $cf_slug, '_ajax_nonce', false ) !== 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'view-reports' ) ) ) {
				wp_die();
			}

			$cf = WPSC_Custom_Field::get_cf_by_slug( $cf_slug );
			if ( ! $cf ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}?>

			<div class="wpsc-setting-header">
				<h2><?php echo esc_attr( $cf->name ); ?></h2>
			</div>
			<div class="wpsc-setting-filter-container">
				<?php WPSC_RP_Filters::get_durations(); ?>
				<div class="setting-filter-item from-date" style="display: none;">
					<span class="label"><?php echo esc_attr( wpsc__( 'From Date', 'wpsc-reports' ) ); ?></span>
					<input type="text" name="from-date" value="">
				</div>
				<div class="setting-filter-item to-date" style="display: none;">
					<span class="label"><?php echo esc_attr( wpsc__( 'To Date', 'wpsc-reports' ) ); ?></span>
					<input type="text" name="to-date" value="">
				</div>
				<script>
					jQuery('select[name=duration]').trigger('change');
					jQuery('.setting-filter-item.from-date').find('input').flatpickr();
					jQuery('.setting-filter-item.from-date').find('input').change(function(){
						let minDate = jQuery(this).val();
						jQuery('.setting-filter-item.to-date').find('input').flatpickr({
							minDate,
							defaultDate: minDate
						});
					});
				</script>
			</div>
			<?php WPSC_RP_Filters::layout( $cf->slug ); ?>
			<div class="wpsc-setting-section-body">
				<div class="wpscPrograssLoaderContainer">
					<div class="wpscPrograssLoader">
						<strong>0<small>%</small></strong>
					</div>
				</div>
				<canvas id="wpscTicketStatisticsCanvas" class="wpscRpCanvas"></canvas>
			</div>
			<script>
				jQuery('form.wpsc-report-filters').find('select[name=filter]').val('').trigger('change');
			</script>
			<?php
			wp_die();
		}

		/**
		 * Run woo product report
		 *
		 * @return void
		 */
		public static function run_cfwp_reports() {

			$cf_slug = isset( $_POST['cf_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['cf_slug'] ) ) : '';
			if ( ! $cf_slug ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			if ( check_ajax_referer( $cf_slug, '_ajax_nonce', false ) !== 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'view-reports' ) ) ) {
				wp_send_json_error( __( 'Unauthorized', 'supportcandy' ), 401 );
			}

			$from_date = isset( $_POST['from_date'] ) ? sanitize_text_field( wp_unslash( $_POST['from_date'] ) ) : '';
			if (
				! $from_date ||
				! preg_match( '/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $from_date )
			) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$to_date = isset( $_POST['to_date'] ) ? sanitize_text_field( wp_unslash( $_POST['to_date'] ) ) : '';
			if (
				! $to_date ||
				! preg_match( '/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $to_date )
			) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$cf = WPSC_Custom_Field::get_cf_by_slug( $cf_slug );
			if ( ! $cf ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			// current filter (default 'All').
			$filter = isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : '';

			// custom filters.
			$filters = isset( $_POST['filters'] ) ? stripslashes( sanitize_textarea_field( wp_unslash( $_POST['filters'] ) ) ) : '';

			// filter arguments.
			$args = array(
				'is_active'      => 1,
				'items_per_page' => 0,
			);

			// meta query.
			$meta_query = array( 'relation' => 'AND' );

			// custom filters (if any).
			if ( $filter == 'custom' ) {
				if ( ! $filters ) {
					wp_send_json_error( 'Bad Request', 400 );
				}
				$filters_arr = json_decode( html_entity_decode( $filters ), true );
				$meta_query  = array_merge( $meta_query, WPSC_Ticket_List::get_meta_query( $filters_arr ) );
			}

			// saved filter (if applied).
			if ( is_numeric( $filter ) ) {
				$saved_filters = get_user_meta( $current_user->user->ID, get_current_blog_id() . '-wpsc-rp-saved-filters', true );
				if ( ! isset( $saved_filters[ intval( $filter ) ] ) ) {
					wp_send_json_error( 'Bad Request', 400 );
				}
				$filter_str  = $saved_filters[ intval( $filter ) ]['filters'];
				$filter_str  = str_replace( '^^', '\n', $filter_str );
				$filters_arr = json_decode( html_entity_decode( $filter_str ), true );
				$meta_query  = array_merge( $meta_query, WPSC_Ticket_List::get_meta_query( $filters_arr ) );
			}

			$response = array();

			// created.
			$created_meta_query = array(
				array(
					'slug'    => 'date_created',
					'compare' => 'BETWEEN',
					'val'     => array(
						'operand_val_1' => ( new DateTime( $from_date ) )->format( 'Y-m-d H:i:s' ),
						'operand_val_2' => ( new DateTime( $to_date ) )->format( 'Y-m-d H:i:s' ),
					),
				),
			);
			$args['meta_query'] = array_merge( $meta_query, $created_meta_query );
			$results            = WPSC_Ticket::find( $args );

			$args = array(
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			);

			$products = wc_get_products( $args );

			$records = array();
			foreach ( $products as $key => $product ) {
				$records[ $product->post_title ] = 0;
			}

			if ( $results['total_items'] ) {
				foreach ( $results['results'] as $ticket ) {
					if ( $ticket->$cf_slug ) {
						$records[ $ticket->$cf_slug->post_title ] = $records[ $ticket->$cf_slug->post_title ] + 1;
					}
				}
			}

			wp_send_json( $records, 200 );
		}
	}
endif;

WPSC_RP_WOO_Product::init();
