<?php

namespace WPFunnels\Rest\Controllers;

use DateInterval;
use DatePeriod;
use DateTime;
use WP_REST_Request;
use WP_REST_Server;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;

class AnalyticsController extends Wpfnl_REST_Controller {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wpfunnels/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'analytics';

    /**
     * Registers a REST API route for retrieving funnel analytics data.
     *
     * @since  1.9.6
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            $this->rest_base . '/(?P<funnel_id>\d+)/(?P<filter>[a-zA-Z]+)',
            [
                'methods'             => WP_REST_Server::READABLE,
                'args'                => [
                    'funnel_id' => [
                        'description' => __( 'Specific Funnel ID.', 'wpfnl-pro' ),
                        'type'        => 'integer',
                        'required'    => true
                    ],
                    'filter'    => [
                        'description' => __( 'Filter name (weekly/monthly/yearly)', 'wpfnl-pro' ),
                        'type'        => 'string',
                        'required'    => true
                    ]
                ],
                'callback'            => [ $this, 'get_funnel_analytics_data' ],
                'permission_callback' => [ $this, 'rest_permissions_check' ]
            ]
        );

        register_rest_route(
            $this->namespace,
            $this->rest_base . '/reset/(?P<funnel_id>\d+)',
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'args'                => [
                    'funnel_id' => [
                        'description' => __( 'Specific Funnel ID.', 'wpfnl-pro' ),
                        'type'        => 'integer',
                        'required'    => true
                    ]
                ],
                'callback'            => [ $this, 'reset_analytics_data' ],
                'permission_callback' => [ $this, 'rest_permissions_check' ]
            ]
        );
    }

    /**
     * Makes sure the current user has access to READ the settings APIs.
     *
     * @return \WP_Error|bool
     *
     * @since  1.9.6
     */
    public function rest_permissions_check() {
        if( !Wpfnl_functions::wpfnl_rest_check_manager_permissions( 'settings' ) ) {
            return new \WP_Error( 'wpfunnels_rest_cannot_get', __( 'Sorry, you cannot list resources.', 'wpfnl' ), [ 'status' => rest_authorization_required_code() ] );
        }
        return true;
    }

    /**
     * Sanitizes and retrieves API parameter values from a WP_REST_Request object.
     *
     * This function sanitizes and extracts the parameter values from a WP_REST_Request object,
     * returning an array of sanitized values.
     *
     * @param WP_REST_Request $request The REST request object containing API parameters.
     * @return array An array of sanitized parameter values retrieved from the request.
     * @since  1.9.6
     */
    private function get_api_params_values( WP_REST_Request $request ) {
        if( $request->sanitize_params() ) {
            return filter_var_array( $request->get_params() );
        }
        return [];
    }

    /**
     * Controller function for wpfunnels/v1/analytics route
     *
     * @param WP_REST_Request $request
     *
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     * @since  1.9.6
     */
    public function get_funnel_analytics_data( WP_REST_Request $request ) {
        $params                                    = $this->get_api_params_values( $request );
        $funnel_id                                 = $params[ 'funnel_id' ] ?? null;
        $filter                                    = $params[ 'filter' ] ?? 'weekly';
        $start_date                                = $params[ 'dateFrom' ] ?? '';
        $end_date                                  = $params[ 'dateTo' ] ?? '';
        $funnel_order_ids                          = Wpfnl_Pro_functions::get_order_ids_by_funnel_id( $funnel_id );
        $order_function                            = "get_{$filter}_orders";
        $filtered_order_ids                        = method_exists( __CLASS__, $order_function ) ? $this->$order_function( $funnel_order_ids, $start_date, $end_date ) : [];
        $funnel_order_ids                          = !empty( $filtered_order_ids[ 'current' ] ) ? call_user_func_array( 'array_merge', array_filter( array_values( $filtered_order_ids[ 'current' ] ) ) ) : [];
        $prev_funnel_order_ids                     = !empty( $filtered_order_ids[ 'previous' ] ) ? call_user_func_array( 'array_merge', array_filter( array_values( $filtered_order_ids[ 'previous' ] ) ) ) : [];
        $header_section_summery                    = $this->get_header_summery( $funnel_order_ids, $prev_funnel_order_ids );
        $revenue_section_summery                   = $this->get_revenue_section_summery( $header_section_summery );
        $revenue_section_bar_data_cur              = !empty( $filtered_order_ids[ 'current' ] ) ? $this->get_revenue_section_bar_data( $filtered_order_ids[ 'current' ] ) : [];
        $revenue_section_bar_data_prev             = !empty( $filtered_order_ids[ 'previous' ] ) ? $this->get_revenue_section_bar_data( $filtered_order_ids[ 'previous' ] ) : [];
        $optin_function                            = "get_{$filter}_optin_submission";
        $optin_submission                          = method_exists( __CLASS__, $optin_function ) ? $this->$optin_function( $funnel_id, $start_date, $end_date ) : [];
        $visitor_function                          = "get_{$filter}_visitors";
        $visitors                                  = method_exists( __CLASS__, $visitor_function ) ? $this->$visitor_function( $funnel_id, $start_date, $end_date ) : [];
	    $visitors                                  = $this->filter_visitor_data( $funnel_id, $visitors );
        $header_section_summery[ 'optin_summery' ] = $optin_submission;

	    return rest_ensure_response(
		    [
			    'headerSection'  => $header_section_summery,
			    'revenueSection' => [
				    'summery'  => $revenue_section_summery,
				    'bar_data' => [
					    'previous' => $revenue_section_bar_data_prev,
					    'current'  => $revenue_section_bar_data_cur
				    ]
			    ],
			    'visitors'       => $visitors
		    ]
	    );
    }

    /**
     * Retrieves a summary of analytics header section information for a specific funnel.
     *
     * This function calculates and compiles various header-related statistics for a given funnel, such as
     * gross sales, average order value, and offer sales.
     *
     * @param array $funnel_order_ids An array of order IDs linked to the specific funnel.
     * @return array An array containing the header summary information including gross sale, average order value,
     *               and additional offer sales data.
     * @since  1.9.6
     */
    private function get_header_summery( $funnel_order_ids, $prev_funnel_order_ids = [] ) {
        // Calculate gross sale amount.
        $gross_sale_current     = $this->get_total_sales( $funnel_order_ids );
        $gross_sale_prev        = $this->get_total_sales( $prev_funnel_order_ids );
        $gross_sale_change_rate = $this->get_data_change_rate( $gross_sale_current, $gross_sale_prev );

        // Retrieve offer sales data.
        $offer_sales_current = $this->get_offer_sales( $funnel_order_ids );
        $offer_sales_prev    = $this->get_offer_sales( $prev_funnel_order_ids );

        $order_bump = [
            'sale' => $offer_sales_current[ 'order_bump_sales' ],
            'rate' => $this->get_data_change_rate( $offer_sales_current[ 'order_bump_sales' ], $offer_sales_prev[ 'order_bump_sales' ] )
        ];

        $upsell_sales = [
            'sale' => $offer_sales_current[ 'upsell_sales' ][ 'total_sale' ] ?? 0,
            'rate' => $this->get_data_change_rate( $offer_sales_current[ 'upsell_sales' ][ 'total_sale' ] ?? 0, $offer_sales_prev[ 'upsell_sales' ][ 'total_sale' ] ?? 0 )
        ];

        $downsell_sales = [
            'sale' => $offer_sales_current[ 'downsell_sales' ][ 'total_sale' ] ?? 0,
            'rate' => $this->get_data_change_rate( $offer_sales_current[ 'downsell_sales' ][ 'total_sale' ] ?? 0, $offer_sales_prev[ 'downsell_sales' ][ 'total_sale' ] ?? 0 )
        ];

        // Calculate average order value.
        $avg_order_value      = count( $funnel_order_ids ) > 0 ? $gross_sale_current / count( $funnel_order_ids ) : 0.00;
        $avg_order_value_prev = count( $prev_funnel_order_ids ) > 0 ?$gross_sale_prev / count( $prev_funnel_order_ids ) : 0.00;
        $avg_order_value_rate = $this->get_data_change_rate( $avg_order_value, $avg_order_value_prev );

        $checkout_sales_current    = $gross_sale_current - ( ( $offer_sales_current[ 'upsell_sales' ][ 'total_sale' ] ?? 0 ) + ( $offer_sales_current[ 'downsell_sales' ][ 'total_sale' ] ?? 0 ) );
        $checkout_sales_prev       = $gross_sale_prev - ( ( $offer_sales_prev[ 'upsell_sales' ][ 'total_sale' ] ?? 0 ) + ( $offer_sales_prev[ 'downsell_sales' ][ 'total_sale' ] ?? 0 ) );
        $checkout_sale_change_rate = $this->get_data_change_rate( $checkout_sales_current, $checkout_sales_prev );

        return [
            'gross_sale'      => [
                'sale' => $gross_sale_current,
                'rate' => $gross_sale_change_rate
            ],
            'avg_order_value' => [
                'value' => $avg_order_value,
                'rate'  => $avg_order_value_rate
            ],
            'order_bump'      => $order_bump,
            'upsell_sales'    => $upsell_sales,
            'downsell_sales'  => $downsell_sales,
            'checkout'        => [
                'sale' => $checkout_sales_current,
                'rate' => $checkout_sale_change_rate
            ]
        ];
    }

    /**
     * Calculates the total sales amount for the provided funnel order IDs.
     *
     * This function calculates the sum of net total sales amount for the orders associated with the given funnel order IDs.
     *
     * @param array $funnel_order_ids An array of order IDs linked to the specific funnel.
     * @return float The total sales amount for the provided funnel order IDs.
     * @since  1.9.6
     */
    private function get_total_sales( $funnel_order_ids ) {
        if( is_array( $funnel_order_ids ) && !empty( $funnel_order_ids ) ) {
            global $wpdb;
            $funnel_order_ids = implode( ', ', $funnel_order_ids );

            // Retrieve and calculate the sum of net total sales amount.
            $total_sales = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT SUM(net_total) FROM {$wpdb->prefix}wc_order_stats WHERE order_id IN(%1s) OR parent_id IN(%1s)",
                    [ $funnel_order_ids, $funnel_order_ids ]
                )
            );
        }
        return !empty( $total_sales ) ? (float)$total_sales : 0; // Return 0 if no funnel order IDs are provided.
    }

    /**
     * Calculates and retrieves offer sales amounts for the provided funnel order IDs.
     *
     * This function calculates the sales amounts for different types of offers (order bump, upsell, downsell)
     * within the orders associated with the given funnel order IDs.
     *
     * @param array $funnel_order_ids An array of order IDs linked to the specific funnel.
     * @return array An array containing offer sales amounts for order bump, upsell, and downsell.
     * @since  1.9.6
     */
    private function get_offer_sales( $funnel_order_ids ) {
        $order_bump_sales = 0.00;
        $upsell_sales     = [];
        $downsell_sales   = [];

        if( is_array( $funnel_order_ids ) && !empty( $funnel_order_ids ) ) {
            foreach( $funnel_order_ids as $order_id ) {
                $order = wc_get_order( $order_id );
                $items = $order instanceof \WC_Order ? $order->get_items() : [];

                if( is_array( $items ) && !empty( $items ) ) {
                    foreach( $items as $item ) {
                        if( $item instanceof \WC_Order_Item_Product ) {
                            $item_data = $item->get_data();
                            $step_id   = $item->get_meta( '_wpfunnels_step_id' );
                            $step_type = get_post_meta( $step_id, '_step_type', true );

                            if( $step_id && $step_type ) {
                                if( 'yes' === $item->get_meta( '_wpfunnels_upsell' ) ) {
                                    $sale_value                                        = $upsell_sales[ $step_id ][ ucfirst( $step_type ) ] ?? 0;
                                    $upsell_sales[ $step_id ][ ucfirst( $step_type ) ] = (float)$sale_value + (float)$item_data[ 'total' ];
                                    $total_sale                                        = $upsell_sales[ 'total_sale' ] ?? 0;
                                    $upsell_sales[ 'total_sale' ]                      = (float)$total_sale + (float)$item_data[ 'total' ];
                                }
                                elseif( 'yes' === $item->get_meta( '_wpfunnels_downsell' ) ) {
                                    $sale_value                                          = $downsell_sales[ $step_id ][ ucfirst( $step_type ) ] ?? 0;
                                    $downsell_sales[ $step_id ][ ucfirst( $step_type ) ] = (float)$sale_value + (float)$item_data[ 'total' ];
                                    $total_sale                                          = $downsell_sales[ 'total_sale' ] ?? 0;
                                    $downsell_sales[ 'total_sale' ]                      = (float)$total_sale + (float)$item_data[ 'total' ];
                                }
                            }
                            if( 'yes' === $item->get_meta( '_wpfunnels_order_bump' ) ) {
                                $order_bump_sales += (float)$item_data[ 'total' ];
                            }
                        }
                    }
                }
            }
        }

        return [
            'order_bump_sales' => $order_bump_sales,
            'upsell_sales'     => $upsell_sales,
            'downsell_sales'   => $downsell_sales
        ];
    }

    /**
     * Retrieves revenue section summary based on sale data.
     *
     * This function calculates and returns a revenue section summary based on the provided sale data.
     * It calculates the checkout revenue, upsell revenue, and downsell revenue based on the given
     * gross sale, upsell sales, and downsell sales values.
     *
     * @param array $sale_data An associative array containing sale data with keys 'gross_sale',
     *                         'upsell_sales', and 'downsell_sales'.
     * @return array An associative array with keys 'checkout', 'upsell', and 'downsell',
     *               containing formatted revenue values for each section.
     * @since  1.9.6
     */
    private function get_revenue_section_summery( $sale_data ) {
        return [
            'checkout' => $sale_data[ 'checkout' ] ?? [],
            'upsell'   => $sale_data[ 'upsell_sales' ] ?? [],
            'downsell' => $sale_data[ 'downsell_sales' ] ?? []
        ];
    }

    /**
     * Retrieves revenue section bar data based on filtered funnel order IDs.
     *
     * This function calculates and returns revenue section bar data for each label in the filtered
     * funnel order IDs array. It calculates the total revenue for each label and formats the data
     * into an associative array with labels as keys and revenue values as values.
     *
     * @param array $filtered_funnel_order_ids An associative array containing filtered funnel order IDs.
     *                                         The array structure should have labels as keys and arrays of order IDs as values.
     * @return array An associative array with labels as keys and revenue section summary values as values.
     * @since  1.9.6
     */
    private function get_revenue_section_bar_data( $filtered_funnel_order_ids ) {
        $funnel_order_revenues = [];
        if( is_array( $filtered_funnel_order_ids ) && !empty( $filtered_funnel_order_ids ) ) {
            foreach( $filtered_funnel_order_ids as $label => $order_ids ) {
                if( is_array( $order_ids ) && !empty( $order_ids ) ) {
                    $total_sale_summery              = $this->get_header_summery( $order_ids );
                    $funnel_order_revenues[ $label ] = $this->get_revenue_section_summery( $total_sale_summery );
                }
                else {
                    $funnel_order_revenues[ $label ] = '0.00';
                }
            }
        }
        return $funnel_order_revenues;
    }

    /**
     * Retrieve orders based on funnel order IDs and a custom date range.
     *
     * This function queries the WooCommerce order stats table to retrieve custom orders.
     * It filters orders based on the provided funnel order IDs and a date range if specified.
     *
     * @param array  $funnel_order_ids An array of funnel order IDs to filter orders.
     * @param string $start_date       The start date of the date range (optional).
     * @param string $end_date         The end date of the date range (optional).
     *
     * @global object $wpdb WordPress database object.
     *
     * @return array An associative array containing 'previous' and 'current' custom orders.
     * @since  1.9.6
     */
    private function get_custom_orders( $funnel_order_ids, $start_date = '', $end_date = '' ) {
        if( !is_array( $funnel_order_ids ) || empty( $funnel_order_ids ) ) {
            return [
                'previous' => [],
                'current'  => []
            ];
        }
        global $wpdb;
        // Determine the appropriate order meta table and column based on WooCommerce settings.
        $order_stats_table = "{$wpdb->prefix}wc_order_stats";
        $select_column     = 'order_id';

        $query = $wpdb->prepare( "SELECT DATE_FORMAT(%1s, '%b %e') AS label", [ 'date_created' ] );
        $query .= $wpdb->prepare( ', %i AS order_id ', [ $select_column ] );                                        //phpcs:ignore
        $query .= $wpdb->prepare( "FROM %i ", [ $order_stats_table ] );                                             //phpcs:ignore
        $query .= $wpdb->prepare( "WHERE %i IN( %1s ) ", [ $select_column, implode( ', ', $funnel_order_ids ) ] );  //phpcs:ignore

        $custom_where   = $this->get_custom_where_query( $start_date, $end_date );
        $current_query  = $query . $custom_where[ 'current' ] ?? '';
        $previous_query = $query . $custom_where[ 'previous' ] ?? '';

        $current  = $current_query ? $wpdb->get_results( $current_query, ARRAY_A ) : [];   //phpcs:ignore
        $previous = $previous_query ? $wpdb->get_results( $previous_query, ARRAY_A ) : []; //phpcs:ignore
        $current  = $this->prepare_orders_format( $current );
        $previous = $this->prepare_orders_format( $previous );

        $current_labels = $custom_where[ 'labels' ][ 'current' ] ?? [];
        $previous_labels = $custom_where[ 'labels' ][ 'previous' ] ?? [];

        return [
            'previous' => array_merge( $previous_labels, $previous ),
            'current'  => array_merge( $current_labels, $current )
        ];
    }

    /**
     * Retrieves weekly order data based on funnel order IDs.
     *
     * This function retrieves weekly order data for both the current week and the previous week,
     * based on the provided funnel order IDs. It calculates order counts for each day of the week
     * and formats the data into an associative array.
     *
     * @param array $funnel_order_ids An array containing funnel order IDs.
     * @return array An associative array with order data for the current and previous week.
     *               The array contains 'current' and 'previous' sub-arrays, each having day labels as keys
     *               and order counts as values.
     * @since  1.9.6
     */
    private function get_weekly_orders( $funnel_order_ids ) {
        if( !is_array( $funnel_order_ids ) || empty( $funnel_order_ids ) ) {
            return [
                'previous' => [],
                'current'  => []
            ];
        }
        global $wpdb;
        // Determine the appropriate order meta table and column based on WooCommerce settings.
        $order_stats_table = "{$wpdb->prefix}wc_order_stats";
        $select_column     = 'order_id';
        $date_column       = 'date_created';
        $format            = 'Y-m-d H:i:s';

        $query = $wpdb->prepare( "SELECT DATE_FORMAT(%1s, '%b %e') AS label", [ $date_column ] );
        $query .= $wpdb->prepare( ', %i AS order_id ', [ $select_column ] );                                        //phpcs:ignore
        $query .= $wpdb->prepare( "FROM %i ", [ $order_stats_table ] );                                             //phpcs:ignore
        $query .= $wpdb->prepare( "WHERE %i IN( %1s ) ", [ $select_column, implode( ', ', $funnel_order_ids ) ] );  //phpcs:ignore

        $current_week   = get_weekstartend( date_i18n( $format, strtotime( 'now' ) ), get_option( 'start_of_week', 1 ) );
        $current_query  = $query . $this->get_weekly_order_query( $date_column, $current_week );
        $prev_week      = get_weekstartend( date_i18n( $format, strtotime( '-7 day' ) ), get_option( 'start_of_week', 1 ) );
        $previous_query = $query . $this->get_weekly_order_query( $date_column, $prev_week );

        $current  = $current_query ? $wpdb->get_results( $current_query, ARRAY_A ) : [];   //phpcs:ignore
        $previous = $previous_query ? $wpdb->get_results( $previous_query, ARRAY_A ) : []; //phpcs:ignore
        $current  = $this->prepare_orders_format( $current );
        $previous = $this->prepare_orders_format( $previous );

        $current_week_days  = $this->get_week_days_with_label( $current_week );
        $previous_week_days = $this->get_week_days_with_label( $prev_week );

        return [
            'previous' => array_merge( $previous_week_days, $previous ),
            'current'  => array_merge( $current_week_days, $current )
        ];
    }

    /**
     * Generates an associative array with week day labels and initializes counts to 0.
     *
     * This function generates an associative array representing week day labels and initializes counts to 0.
     * It uses the provided start date of the week to calculate the labels for each day of the week.
     *
     * @param array $week An associative array containing the start date of the week.
     *
     * @return array An associative array with week day labels as keys and initialized counts as values.
     * @since  1.9.6
     */
    private function get_week_days_with_label( $week ) {
        if( !empty( $week[ 'start' ] ) ) {
            $start_of_week = date_i18n( 'Y-m-d', $week[ 'start' ] );
            $interval      = 0;
            while( $interval < 7 ) {
                $label               = date_i18n( 'M j', strtotime( $start_of_week . '+' . $interval . 'day' ) ); //phpcs:ignore
                $week_days[ $label ] = 0;
                $interval++;
            }
        }
        return $week_days ?? [];
    }

    /**
     * Generates a SQL query to retrieve weekly order data.
     *
     * This function generates a SQL query to retrieve order data for a specified week, using the provided parameters.
     * The query retrieves order data from the specified order table based on the given date column and order IDs.
     *
     * @param string $date_column The name of the date column in the order table.
     * @param array $week An associative array representing the start and end dates of the week.
     * @return string The generated SQL query to retrieve weekly order data.
     * @since  1.9.6
     */
    private function get_weekly_order_query( $date_column, $week ) {
        global $wpdb;

        if( !empty( $week[ 'start' ] ) && !empty( $week[ 'end' ] ) ) {
            $start_of_week = date_i18n( 'Y-m-d', $week[ 'start' ] );
            $end_of_week   = date_i18n( 'Y-m-d', $week[ 'end' ] );

            $query = $wpdb->prepare( "AND DATE_FORMAT(%1s, ", [ $date_column ] );
            $query .= "'%Y-%m-%d') >= ";
            $query .= $wpdb->prepare( '%s ', [ $start_of_week ] ); //phpcs:ignore
            $query .= $wpdb->prepare( "AND DATE_FORMAT(%1s, ", [ $date_column ] );
            $query .= "'%Y-%m-%d') <= ";
            return $query . $wpdb->prepare( '%s ', [ $end_of_week ] ); //phpcs:ignore
        }
        return '';
    }

    /**
     * Retrieves monthly order data for a set of funnel order IDs.
     *
     * This function retrieves monthly order data for a given set of funnel order IDs,
     * both for the current month and the previous month. It queries the appropriate order meta table
     * and column based on WooCommerce settings, and prepares the data for presentation.
     *
     * @param array $funnel_order_ids An array containing funnel order IDs for which to retrieve order data.
     * @return array An associative array containing monthly order data for the current and previous months.
     *               The array is structured with 'previous' and 'current' keys, each containing an array of order data.
     * @since  1.9.6
     */
    private function get_monthly_orders( $funnel_order_ids ) {
        if( !is_array( $funnel_order_ids ) || empty( $funnel_order_ids ) ) {
            return [
                'previous' => [],
                'current'  => []
            ];
        }
        global $wpdb;
        // Determine the appropriate order meta table and column based on WooCommerce settings.
        $order_meta_table = "{$wpdb->prefix}wc_order_stats";
        $column           = 'order_id';
        $where_column     = 'date_created';

        $query          = $wpdb->prepare( "SELECT DATE_FORMAT(%1s, '%b %e') AS label", [ $where_column ] );                                         //phpcs:ignore
        $query          .= $wpdb->prepare( ', %i AS order_id ', [ $column ] );                                                                      //phpcs:ignore
        $query          .= $wpdb->prepare( "FROM %i ", [ $order_meta_table ] );                                                                     //phpcs:ignore
        $query          .= $wpdb->prepare( "WHERE %i IN( %1s ) ", [ $column, implode( ', ', $funnel_order_ids ) ] );                                //phpcs:ignore
        $query_current  = $query . $wpdb->prepare( "AND (EXTRACT(YEAR_MONTH FROM %1s) = EXTRACT(YEAR_MONTH FROM now())) ", [ $where_column ] );     //phpcs:ignore
        $query_previous = $query . $wpdb->prepare( "AND (EXTRACT(YEAR_MONTH FROM %1s) = EXTRACT(YEAR_MONTH FROM now()) - 1) ", [ $where_column ] ); //phpcs:ignore

        $current  = $wpdb->get_results( $query_current, ARRAY_A );  //phpcs:ignore
        $previous = $wpdb->get_results( $query_previous, ARRAY_A ); //phpcs:ignore
        $current  = $this->prepare_orders_format( $current );
        $previous = $this->prepare_orders_format( $previous );

        $current_monthly_days  = $this->get_month_label_with_days();
        $previous_monthly_days = $this->get_month_label_with_days( '-1 month' );

        return [
            'previous' => array_merge( $previous_monthly_days, $previous ),
            'current'  => array_merge( $current_monthly_days, $current )
        ];
    }

    /**
     * Generates a label array for a specified month with corresponding days.
     *
     * This function generates an associative array with day labels as keys and initial values set to 0,
     * for the specified month. The labels are in the format "Month Day".
     *
     * @param string $datetime The date and time for which to generate the label array. Defaults to 'now'.
     * @return array An associative array containing day labels for the specified month with initial values of 0.
     * @since  1.9.6
     */
    private function get_month_label_with_days( $datetime = 'now' ) {
        $current_datetime    = strtotime( $datetime );
        $month               = (int)date_i18n( 'n', $current_datetime );
        $current_month_label = date_i18n( 'M', $current_datetime );
        $days                = $this->get_month_days( $month );

        for( $day = 1; $day <= $days; $day++ ) {
            $monthly_days[ $current_month_label . ' ' . $day ] = 0;
        }

        return $monthly_days ?? [];
    }

    /**
     * Determines the number of days in a given month.
     *
     * This function calculates the number of days in a given month based on its numerical value.
     * February has 28 days, months with odd numbers less than 9 and even numbers greater than 9 have 31 days,
     * and other months have 30 days.
     *
     * @param int $month The numerical value representing the month (1 to 12).
     * @return int The number of days in the specified month.
     * @since  1.9.6
     */
    private function get_month_days( $month ) {
        if( $month >= 1 && $month <= 12 ) {
            if( 2 === $month ) {
                $days = 28;
            }
            elseif( 8 === $month || ( 0 !== $month % 2 && 9 > $month ) || ( 0 === $month % 2 && 9 < $month ) ) {
                $days = 31;
            }
            else {
                $days = 30;
            }
            return $days;
        }
        return 0;
    }

    /**
     * Retrieves yearly order data for a given list of funnel order IDs.
     *
     * This function fetches yearly order data based on the provided funnel order IDs. It groups the order
     * data by months within the current year and the previous year, calculating the count of orders for
     * each month. The function also handles WooCommerce's custom order tables if enabled.
     *
     * @param array $funnel_order_ids An array of funnel order IDs.
     * @return array An associative array containing the count of orders for each month in the current
     *              and previous years.
     * @since  1.9.6
     */
    private function get_yearly_orders( $funnel_order_ids ) {
        if( !is_array( $funnel_order_ids ) || empty( $funnel_order_ids ) ) {
            return [
                'previous' => [],
                'current'  => []
            ];
        }
        global $wpdb;
        // Determine the appropriate order meta table and column based on WooCommerce settings.
        $order_meta_table = "{$wpdb->prefix}wc_order_stats";
        $column           = 'order_id';
        $where_column     = 'date_created';

        $query          = $wpdb->prepare( "SELECT DATE_FORMAT(%1s, '%b') AS label", [ $where_column ] );             //phpcs:ignore
        $query          .= $wpdb->prepare( ', %i AS order_id ', [ $column ] );                                       //phpcs:ignore
        $query          .= $wpdb->prepare( "FROM %i ", [ $order_meta_table ] );                                      //phpcs:ignore
        $query          .= $wpdb->prepare( "WHERE %i IN( %1s ) ", [ $column, implode( ', ', $funnel_order_ids ) ] ); //phpcs:ignore
        $query_current  = $query . $wpdb->prepare( "AND YEAR(%1s) = YEAR(now()) ", [ $where_column ] );
        $query_previous = $query . $wpdb->prepare( "AND YEAR(%1s) = YEAR(now()) - 1 ", [ $where_column ] );

        $current  = $wpdb->get_results( $query_current, ARRAY_A );  //phpcs:ignore
        $previous = $wpdb->get_results( $query_previous, ARRAY_A ); //phpcs:ignore
        $current  = $this->prepare_orders_format( $current );
        $previous = $this->prepare_orders_format( $previous );

        $months = [
            'Jan' => 0,
            'Feb' => 0,
            'Mar' => 0,
            'Apr' => 0,
            'May' => 0,
            'Jun' => 0,
            'Jul' => 0,
            'Aug' => 0,
            'Sep' => 0,
            'Oct' => 0,
            'Nov' => 0,
            'Dec' => 0
        ];

        return [
            'previous' => array_merge( $months, $previous ),
            'current'  => array_merge( $months, $current )
        ];
    }

    /**
     * Prepares formatted order data from a query result.
     *
     * This function takes an array of query data and prepares formatted order data
     * grouped by their labels. It extracts order IDs from the data and groups them
     * according to their corresponding labels.
     *
     * @param array $query_data The query data array to be formatted.
     * @return array An associative array containing formatted order data.
     *               The array is structured with labels as keys and arrays of order IDs as values.
     * @since  1.9.6
     */
    private function prepare_orders_format( $query_data ) {
        $formatted_data = [];
        if( is_array( $query_data ) && !empty( $query_data ) ) {
            foreach( $query_data as $data ) {
                if( !empty( $data[ 'label' ] ) && !empty( $data[ 'order_id' ] ) ) {
                    $formatted_data[ $data[ 'label' ] ][] = $data[ 'order_id' ];
                }
            }
        }
        return $formatted_data;
    }

    /**
     * Calculate the rate of change between two data points as a percentage.
     *
     * This function calculates the percentage change between the current data point
     * and the previous data point, expressing it as a percentage with one decimal place.
     *
     * @param float $current_data The current data point.
     * @param float $previous_data The previous data point.
     *
     * @return string A string representing the rate of change as a percentage.
     *               If current_data <= 0 and previous_data > 0, it returns '-100.0'.
     *               If current_data > 0 and previous_data <= 0, it returns '100.0'.
     *               For other cases, it calculates the rate of change as
     *               ((current_data - previous_data) / previous_data) * 100
     *               and returns it as a string with one decimal place.
     *
     * @example
     *   Input: get_data_change_rate(75, 100)
     *   Output: '33.3' // Indicates a 33.3% decrease from 100 to 75
     *
     *   Input: get_data_change_rate(125, 100)
     *   Output: '25.0' // Indicates a 25% increase from 100 to 125
     *
     *   Input: get_data_change_rate(0, 100)
     *   Output: '-100.0' // Indicates a 100% decrease from 100 to 0
     *
     *   Input: get_data_change_rate(100, 0)
     *   Output: '100.0' // Indicates a 100% increase from 0 to 100
     *
     * @return string
     * @since  1.9.6
     */
    private function get_data_change_rate( $current_data, $previous_data ) {
        if( 0 >= $current_data && 0 < $previous_data ) {
            return '-100.0';
        }
        if( 0 < $current_data && 0 >= $previous_data ) {
            return '100.0';
        }

        $rate = ( $current_data - $previous_data ) * 100;
        if( 0 < $previous_data ) {
            return $rate / $previous_data;
        }
        return $rate;
    }

    /**
     * Get opt-in submission statistics for a specific funnel within a custom date range.
     *
     * This function queries the WordPress database to retrieve the count of opt-in submissions
     * for a given funnel within a specified date range. It calculates the submission rate
     * change between the current and previous periods.
     *
     * @param int    $funnel_id   The ID of the funnel to retrieve opt-in statistics for.
     * @param string $start_date  The start date of the date range (optional).
     * @param string $end_date    The end date of the date range (optional).
     *
     * @global object $wpdb WordPress database object.
     *
     * @return array An associative array containing 'visitor' (opt-in count) and 'rate' (submission rate change).
     */
	private function get_custom_optin_submission( $funnel_id, $start_date = '', $end_date = '' ) {
		global $wpdb;
		$query          = "SELECT COUNT(analytics_meta.id) AS optin_submitted FROM {$wpdb->prefix}wpfnl_analytics_meta AS analytics_meta ";
		$query          .= "JOIN {$wpdb->prefix}wpfnl_analytics AS analytics ON analytics_meta.analytics_id = analytics.id ";
		$query          .= $wpdb->prepare( 'WHERE analytics.funnel_id = %d ', $funnel_id );
		$query          .= $wpdb->prepare( 'AND analytics_meta.meta_key = %s ', 'wpfunnel_optin_submit' );
		$query          .= $wpdb->prepare( 'AND analytics_meta.meta_value = %s ', 'yes' );
		$custom_where   = $this->get_custom_where_query( $start_date, $end_date );
		$current_query  = $query . $custom_where[ 'current' ] ?? '';
		$previous_query = $query . $custom_where[ 'previous' ] ?? '';
		$current_optin  = $current_query ? $wpdb->get_var( $current_query ) : 0;
		$current_optin  = !empty( $current_optin ) ? (float)$current_optin : 0;
		$previous_optin = $previous_query ? $wpdb->get_var( $previous_query ) : 0;
		$previous_optin = !empty( $previous_optin ) ? (float)$previous_optin : 0;
		$rate           = $this->get_data_change_rate( $current_optin, $previous_optin );
		return [
			'visitor' => $current_optin,
			'rate'    => $rate
		];
	}

    /**
     * Retrieves weekly opt-in submission counts for a specific funnel.
     *
     * This function retrieves weekly opt-in submission counts for a given funnel,
     * including the current week and the previous week, and prepares the data.
     *
     * @param int $funnel_id The ID of the funnel for which to retrieve opt-in submission data.
     * @return array An array containing weekly opt-in submission counts for the current and previous weeks.
     * @since  1.9.6
     */
    private function get_weekly_optin_submission( $funnel_id ) {
        global $wpdb;
        $query = "SELECT COUNT(analytics_meta.id) AS optin_submitted FROM {$wpdb->prefix}wpfnl_analytics_meta AS analytics_meta ";
        $query .= "JOIN {$wpdb->prefix}wpfnl_analytics AS analytics ON analytics_meta.analytics_id = analytics.id ";
        $query .= $wpdb->prepare( 'WHERE analytics.funnel_id = %d ', $funnel_id );
        $current_query  = $query . $this->get_optin_submission_weekly_query();
        $previous_query = $query . $this->get_optin_submission_weekly_query('-7 day' );
	    $current_optin  = $current_query ? $wpdb->get_var( $current_query ) : 0;
	    $current_optin  = !empty( $current_optin ) ? (float)$current_optin : 0;
	    $previous_optin = $previous_query ? $wpdb->get_var( $previous_query ) : 0;
	    $previous_optin = !empty( $previous_optin ) ? (float)$previous_optin : 0;
        $rate           = $this->get_data_change_rate( $current_optin, $previous_optin );
        return [
            'visitor' => $current_optin,
            'rate'    => $rate
        ];
    }

    /**
     * Generates a weekly opt-in submission query for a specific funnel and date range.
     *
     * This function generates a database query to retrieve weekly opt-in submission counts for a given funnel
     * and a specified date range (week).
     *
     * @param string $date_time The date and time for which to generate the query. Defaults to 'now'.
     * @return string The generated SQL query to retrieve weekly opt-in submission counts.
     *                Returns an empty string if the date range information is missing.
     * @since  1.9.6
     */
    private function get_optin_submission_weekly_query( $date_time = 'now' ) {
        global $wpdb;
        $week = get_weekstartend( date_i18n( 'Y-m-d H:i:s', strtotime( $date_time ) ), get_option( 'start_of_week', 1 ) );
        if( !empty( $week[ 'start' ] ) && !empty( $week[ 'end' ] ) ) {
            $start_of_week = date_i18n( 'Y-m-d', $week[ 'start' ] );
            $end_of_week   = date_i18n( 'Y-m-d', $week[ 'end' ] );

            $query = $wpdb->prepare( "AND DATE_FORMAT(%1s, ", [ 'analytics.date_modified' ] );
            $query .= "'%Y-%m-%d') >= ";
            $query .= $wpdb->prepare( '%s ', [ $start_of_week ] ); //phpcs:ignore
            $query .= $wpdb->prepare( "AND DATE_FORMAT(%1s, ", [ 'analytics.date_modified' ] );
            $query .= "'%Y-%m-%d') <= ";
            $query .= $wpdb->prepare( '%s ', [ $end_of_week ] ); //phpcs:ignore
            $query .= "AND analytics_meta.meta_key = 'wpfunnel_optin_submit' ";
            return $query . "AND analytics_meta.meta_value = 'yes'";
        }
        return '';
    }

    /**
     * Retrieves monthly opt-in submission counts for a specific funnel.
     *
     * This function retrieves monthly opt-in submission counts for a given funnel,
     * including the current month, and prepares the data.
     *
     * @param int $funnel_id The ID of the funnel for which to retrieve opt-in submission data.
     * @return array An array containing monthly opt-in submission counts for the current month.
     * @since  1.9.6
     */
    private function get_monthly_optin_submission( $funnel_id ) {
        global $wpdb;
        $query          = "SELECT COUNT(analytics_meta.id) AS optin_submitted FROM {$wpdb->prefix}wpfnl_analytics_meta AS analytics_meta ";
        $query          .= "JOIN {$wpdb->prefix}wpfnl_analytics AS analytics ON analytics_meta.analytics_id = analytics.id ";
        $query          .= "WHERE analytics.funnel_id = {$funnel_id} ";
        $query          .= "AND analytics_meta.meta_key = 'wpfunnel_optin_submit' ";
        $query          .= "AND analytics_meta.meta_value = 'yes' ";
        $current_query  = $query . 'AND (EXTRACT(YEAR_MONTH FROM analytics.date_modified) = EXTRACT(YEAR_MONTH FROM now()))';
        $previous_query = $query . 'AND (EXTRACT(YEAR_MONTH FROM analytics.date_modified) = EXTRACT(YEAR_MONTH FROM now()) - 1)';
	    $current_optin  = $current_query ? $wpdb->get_var( $current_query ) : 0;
	    $current_optin  = !empty( $current_optin ) ? (float)$current_optin : 0;
	    $previous_optin = $previous_query ? $wpdb->get_var( $previous_query ) : 0;
	    $previous_optin = !empty( $previous_optin ) ? (float)$previous_optin : 0;
        $rate           = $this->get_data_change_rate( $current_optin, $previous_optin );
        return [
            'visitor' => $current_optin,
            'rate'    => $rate
        ];
    }

    /**
     * Retrieves yearly opt-in submission counts for a specific funnel.
     *
     * This function retrieves yearly opt-in submission counts for a given funnel,
     * including the current year, and prepares the data.
     *
     * @param int $funnel_id The ID of the funnel for which to retrieve opt-in submission data.
     * @return array An array containing yearly opt-in submission counts for the current year.
     * @since  1.9.6
     */
    private function get_yearly_optin_submission( $funnel_id ) {
        global $wpdb;
	    $query          = "SELECT COUNT(analytics_meta.id) AS optin_submitted FROM {$wpdb->prefix}wpfnl_analytics_meta AS analytics_meta ";
	    $query          .= "JOIN {$wpdb->prefix}wpfnl_analytics AS analytics ON analytics_meta.analytics_id = analytics.id ";
	    $query          .= "WHERE analytics.funnel_id = {$funnel_id} ";
	    $query          .= "AND analytics_meta.meta_key = 'wpfunnel_optin_submit' ";
	    $query          .= "AND analytics_meta.meta_value = 'yes' ";
	    $current_query  = $query . 'AND YEAR(analytics.date_modified) = YEAR(now())';
	    $previous_query = $query . 'AND YEAR(analytics.date_modified) = YEAR(now()) - 1';
	    $current_optin  = $current_query ? $wpdb->get_var( $current_query ) : 0;
	    $current_optin  = !empty( $current_optin ) ? (float)$current_optin : 0;
	    $previous_optin = $previous_query ? $wpdb->get_var( $previous_query ) : 0;
	    $previous_optin = !empty( $previous_optin ) ? (float)$previous_optin : 0;
	    $rate           = $this->get_data_change_rate( $current_optin, $previous_optin );
        return [
            'visitor' => $current_optin,
            'rate'    => $rate
        ];
    }

    /**
     * Get custom visitor statistics for a specific funnel within a date range.
     *
     * This function queries the WordPress database to retrieve visitor statistics for a given funnel
     * within a specified date range. It returns an array of visitor data, including date labels,
     * step IDs, visitor types, and visitor counts.
     *
     * @param int    $funnel_id   The ID of the funnel to retrieve visitor statistics for.
     * @param string $start_date  The start date of the date range (optional).
     * @param string $end_date    The end date of the date range (optional).
     *
     * @global object $wpdb WordPress database object.
     *
     * @return array An associative array containing visitor data with date labels, step IDs, visitor types, and visitor counts.
     * @since  1.9.6
     */
	private function get_custom_visitors( $funnel_id, $start_date = '', $end_date = '' ) {
		global $wpdb;
		$custom_where = $this->get_custom_where_query( $start_date, $end_date );
		$date_range   = $custom_where[ 'current' ] ?? '';
		$query        = "SELECT DATE_FORMAT(date_created, '%b %e') AS label, step_id, visitor_type, COUNT(id) AS visitors ";
		$query        .= "FROM {$wpdb->prefix}wpfnl_analytics ";
		$query        .= $wpdb->prepare( 'WHERE funnel_id = %d ', $funnel_id );
		$query        .= $date_range;
		$query        .= ' GROUP BY label, step_id, visitor_type';
		$visitors     = $wpdb->get_results( $query, ARRAY_A );
		$conversions  = $this->get_other_visitor_conversion_data( $funnel_id, 'conversion', 'conversions', $date_range, '%b %e' );
		$bounced      = $this->get_other_visitor_conversion_data( $funnel_id, 'bounced', 'bounced', $date_range, '%b %e' );
		return $this->prepare_visitor_data( $visitors, $conversions, $bounced );
	}

    /**
     * Retrieves weekly visitors data for a specific funnel.
     *
     * This function retrieves weekly visitors data for a given funnel, including the current week,
     * and prepares the data with day labels and visitor statistics.
     *
     * @param int $funnel_id The ID of the funnel for which to retrieve visitors data.
     * @return array An array containing weekly visitors data for the current week.
     *               The array is structured with daily visitor information.
     * @since  1.9.6
     */
    private function get_weekly_visitors( $funnel_id ) {
        global $wpdb;
        $format              = 'Y-m-d H:i:s';
        $current_week        = get_weekstartend( date_i18n( $format, strtotime( 'now' ) ), get_option( 'start_of_week', 1 ) );
        $query               = "SELECT DATE_FORMAT(date_created, '%b %e') AS label, step_id, visitor_type, COUNT(id) AS visitors ";
        $query               .= "FROM {$wpdb->prefix}wpfnl_analytics ";
        $query               .= $wpdb->prepare( 'WHERE funnel_id = %d ', $funnel_id );
        $query               .= $this->get_weekly_visitor_where_query( $current_week );
        $visitors            = $wpdb->get_results( $query, ARRAY_A );
	    $conversions         = $this->get_weekly_visitor_conversion_data( $funnel_id, 'conversion', 'conversions' );
	    $bounced             = $this->get_weekly_visitor_conversion_data( $funnel_id, 'bounced', 'bounced' );
        return $this->prepare_visitor_data( $visitors, $conversions, $bounced );
    }

    /**
     * Generates a weekly visitor query for a specific funnel and week.
     *
     * This function generates a database query to retrieve weekly visitor data for a given funnel and a specified week.
     *
     * @param array $week An array containing the start and end timestamps of the week.
     * @return string The generated SQL query to retrieve weekly visitor data.
     *                Returns an empty string if the week information is missing.
     * @since  1.9.6
     */
    private function get_weekly_visitor_where_query( $week ) {
        global $wpdb;

        if( !empty( $week[ 'start' ] ) && !empty( $week[ 'end' ] ) ) {
            $start_of_week = date_i18n( 'Y-m-d', $week[ 'start' ] );
            $end_of_week   = date_i18n( 'Y-m-d', $week[ 'end' ] );

            $query = $wpdb->prepare( "AND DATE_FORMAT(%1s, ", [ 'date_created' ] );
            $query .= "'%Y-%m-%d') >= ";
            $query .= $wpdb->prepare( '%s ', [ $start_of_week ] ); //phpcs:ignore
            $query .= $wpdb->prepare( "AND DATE_FORMAT(%1s, ", [ 'date_created' ] );
            $query .= "'%Y-%m-%d') <= ";
            return $query . $wpdb->prepare( '%s ', [ $end_of_week ] ) . ' GROUP BY label, step_id, visitor_type'; //phpcs:ignore
        }
        return '';
    }

    /**
     * Retrieves monthly visitors data for a specific funnel.
     *
     * This function queries the database to retrieve monthly visitors data for a given funnel, grouped by month,
     * step ID, visitor type, and step type. It also fetches and merges monthly conversion data if available.
     *
     * @param int $funnel_id The ID of the funnel for which to retrieve visitors data.
     * @return array An array containing monthly visitors data for the current month.
     *               The array is structured with daily visitor information.
     * @since  1.9.6
     */
	private function get_monthly_visitors( $funnel_id ) {
		global $wpdb;
		$date_range       = 'AND (EXTRACT(YEAR_MONTH FROM date_created) = EXTRACT(YEAR_MONTH FROM now()))';
		$groupby          = "GROUP BY label, step_id, visitor_type";
		$query            = "SELECT DATE_FORMAT(date_created, '%b %e') AS label, step_id, visitor_type, COUNT(id) AS visitors ";
		$query            .= "FROM {$wpdb->prefix}wpfnl_analytics ";
		$query            .= "WHERE funnel_id = {$funnel_id} ";
		$current_query    = "{$query} {$date_range} {$groupby}";
		$current_visitors = $wpdb->get_results( $current_query, ARRAY_A );
		$conversions      = $this->get_other_visitor_conversion_data( $funnel_id, 'conversion', 'conversions', $date_range, '%b %e' );
		$bounced          = $this->get_other_visitor_conversion_data( $funnel_id, 'bounced', 'bounced', $date_range, '%b %e' );
		return $this->prepare_visitor_data( $current_visitors, $conversions, $bounced );
	}

    /**
     * Retrieves yearly visitors data for a specific funnel.
     *
     * This function queries the database to retrieve yearly visitors data for a given funnel, grouped by month,
     * step ID, visitor type, and step type.
     *
     * @param int $funnel_id The ID of the funnel for which to retrieve visitors data.
     * @return array An array containing yearly visitors data for the current and previous years.
     *               The array is structured with monthly visitor information.
     * @since  1.9.6
     */
	private function get_yearly_visitors( $funnel_id ) {
		global $wpdb;
		$date_range       = 'AND YEAR(date_created) = YEAR(now())';
		$groupby          = "GROUP BY label, step_id, visitor_type";
		$query            = "SELECT DATE_FORMAT(date_created, '%b') AS label, step_id, visitor_type, COUNT(id) AS visitors ";
		$query            .= "FROM {$wpdb->prefix}wpfnl_analytics ";
		$query            .= "WHERE funnel_id = {$funnel_id} ";
		$current_query    = "{$query} {$date_range} {$groupby}";
		$current_visitors = $wpdb->get_results( $current_query, ARRAY_A );
		$conversions      = $this->get_other_visitor_conversion_data( $funnel_id, 'conversion', 'conversions', $date_range, '%b' );
		$bounced          = $this->get_other_visitor_conversion_data( $funnel_id, 'bounced', 'bounced', $date_range, '%b' );
		return $this->prepare_visitor_data( $current_visitors, $conversions, $bounced );
	}

    /**
     * Get custom visitor conversion statistics for a specific funnel within a date range.
     *
     * This function queries the WordPress database to retrieve visitor conversion statistics for a given funnel
     * within a specified date range. It returns an array of conversion data, including date labels, step IDs,
     * and the number of conversions.
     *
     * @param int    $funnel_id   The ID of the funnel to retrieve conversion statistics for.
     * @param string $start_date  The start date of the date range (optional).
     * @param string $end_date    The end date of the date range (optional).
     *
     * @global object $wpdb WordPress database object.
     *
     * @return array An associative array containing conversion data with date labels, step IDs, and conversion counts.
     * @since  1.9.6
     */
    private function get_custom_visitor_conversions( $funnel_id, $start_date = '', $end_date = '' ) {
        global $wpdb;
        $custom_where = $this->get_custom_where_query( $start_date, $end_date );
        $groupby      = "GROUP BY label, step_id";
        $query        = "SELECT DATE_FORMAT(date_created, '%b %e') AS label, analytics_meta.step_id AS step_id, COUNT(analytics_meta.id) AS conversions ";
        $query        .= "FROM {$wpdb->prefix}wpfnl_analytics AS analytics ";
        $query        .= "JOIN {$wpdb->prefix}wpfnl_analytics_meta AS analytics_meta ";
        $query        .= "ON analytics.id = analytics_meta.analytics_id ";
        $query        .= "WHERE analytics.funnel_id = {$funnel_id} ";
        $query        .= "AND analytics_meta.meta_key = 'conversion' ";
        $query        .= "AND analytics_meta.meta_value = 'yes' ";
        $query        .= $custom_where[ 'current' ] ?? '';
        $query        .= $groupby;
        return $wpdb->get_results( $query, ARRAY_A ) ?? [];
    }

	/**
	 * Get weekly visitor conversion data for a specific funnel.
	 *
	 * This function queries the database to retrieve data related to weekly visitor conversions
	 * within a specific funnel. It calculates the number of conversions for each week and step
	 * within the funnel.
	 *
	 * @param int    $funnel_id        The ID of the funnel.
	 * @param string $data_field       The name of the data field to retrieve (e.g., 'conversion_rate').
	 * @param string $data_field_label The label for the data field (e.g., 'conversion_rate_label').
	 *
	 * @return array An array of data containing weekly conversion statistics.
	 * @since 1.9.6
	 */
	private function get_weekly_visitor_conversion_data( $funnel_id, $data_field, $data_field_label ) {
		$week = get_weekstartend( date_i18n( 'Y-m-d H:i:s', strtotime( 'now' ) ), get_option( 'start_of_week', 1 ) );;
		if ( !empty( $week[ 'start' ] ) && !empty( $week[ 'end' ] ) ) {
			$start_of_week = date_i18n( 'Y-m-d', $week[ 'start' ] );
			$end_of_week   = date_i18n( 'Y-m-d', $week[ 'end' ] );
			global $wpdb;
			$groupby = "GROUP BY label, step_id";
			$query   = $wpdb->prepare( "SELECT DATE_FORMAT(date_created, '%b %e') AS label, analytics_meta.step_id AS step_id, COUNT(analytics_meta.id) AS %s ", $data_field_label );
			$query   .= "FROM {$wpdb->prefix}wpfnl_analytics AS analytics ";
			$query   .= "JOIN {$wpdb->prefix}wpfnl_analytics_meta AS analytics_meta ";
			$query   .= "ON analytics.id = analytics_meta.analytics_id ";
			$query   .= $wpdb->prepare( 'WHERE analytics.funnel_id = %d ', $funnel_id );
			$query   .= $wpdb->prepare( 'AND analytics_meta.meta_key = %s ', $data_field );
			$query   .= "AND analytics_meta.meta_value = 'yes' ";
			$query   .= $wpdb->prepare( "AND DATE_FORMAT(%1s, ", [ 'date_created' ] );
			$query   .= "'%Y-%m-%d') >= ";
			$query   .= $wpdb->prepare( '%s ', [ $start_of_week ] ); //phpcs:ignore
			$query   .= $wpdb->prepare( "AND DATE_FORMAT(%1s, ", [ 'date_created' ] );
			$query   .= "'%Y-%m-%d') <= ";
			$query   .= $wpdb->prepare( '%s ', [ $end_of_week ] ) . $groupby;

			return $wpdb->get_results( $query, ARRAY_A ) ?? [];
		}
		return [];
	}

	/**
	 * Get monthly or yearly visitor conversion data for a specific funnel.
	 *
	 * This function queries the database to retrieve data related to monthly or yearly visitor
	 * conversions within a specific funnel. It calculates the number of conversions for each
	 * specified date range and step within the funnel.
	 *
	 * @param int    $funnel_id        The ID of the funnel.
	 * @param string $data_field       The name of the data field to retrieve (e.g., 'conversion_rate').
	 * @param string $data_field_label The label for the data field (e.g., 'conversion_rate_label').
	 * @param string $date_range       The date range condition for the query (e.g., 'WHERE date_created >= "2023-01-01" AND date_created <= "2023-12-31"').
	 * @param string $date_label       The date format label for grouping (e.g., '%Y-%m' for monthly or '%Y' for yearly).
	 *
	 * @return array An array of data containing monthly or yearly conversion statistics.
	 * @since 1.9.6
	 */
	private function get_other_visitor_conversion_data( $funnel_id, $data_field, $data_field_label, $date_range, $date_label ) {
		global $wpdb;
		$groupby = "GROUP BY label, step_id";
		$query   = $wpdb->prepare( "SELECT DATE_FORMAT(date_created, %s) AS label, analytics_meta.step_id AS step_id, COUNT(analytics_meta.id) AS %s ", $date_label, $data_field_label );
		$query   .= "FROM {$wpdb->prefix}wpfnl_analytics AS analytics ";
		$query   .= "JOIN {$wpdb->prefix}wpfnl_analytics_meta AS analytics_meta ";
		$query   .= "ON analytics.id = analytics_meta.analytics_id ";
		$query   .= $wpdb->prepare( 'WHERE analytics.funnel_id = %d ', $funnel_id );
		$query   .= $wpdb->prepare( 'AND analytics_meta.meta_key = %s ', $data_field );
		$query   .= "AND analytics_meta.meta_value = 'yes'";

		return $wpdb->get_results( "{$query} {$date_range} {$groupby}", ARRAY_A ) ?? [];
	}

    /**
     * Prepares and organizes visitor data with conversion and bounce rate calculations.
     *
     * This function takes visitor data and associated conversions, calculates conversion rates and bounce rates,
     * and arranges the data in a structured format.
     *
     * @param array $visitor_data An array of visitor data containing information about visitors and their behavior.
     * @param array $conversions An array of conversion data containing information about conversions.
     * @param array $bounced An array of bounced data containing information about visitor bouncing.
     * @return array An organized array of visitor data with calculated conversion rates and bounce rates.
     * @since  1.9.6
     */
    private function prepare_visitor_data( $visitor_data, $conversions, $bounced ) {
        $final_data = [];
        if( is_array( $visitor_data ) && !empty( $visitor_data ) ) {
            foreach( $visitor_data as $visitor ) {
	            if ( !empty( $visitor[ 'label' ] ) && !empty( $visitor[ 'step_id' ] ) && !empty( $visitor[ 'visitors' ] ) && !empty( $visitor[ 'visitor_type' ] ) ) {
		            $label                                                 = $visitor[ 'label' ];
		            $step_id                                               = $visitor[ 'step_id' ];
		            $step_type                                             = get_post_meta( $step_id, '_step_type', true );
		            $visitors                                              = $visitor[ 'visitors' ];
		            $visitor_type                                          = $visitor[ 'visitor_type' ];
		            $final_data[ $step_id ][ $step_type ][ 'step_title' ]  = get_the_title( $step_id );
		            $final_data[ $step_id ][ $step_type ][ $visitor_type ] = $visitors;
		            $total                                                 = $final_data[ $step_id ][ $step_type ][ 'total' ] ?? 0;
		            $total                                                 = (int)$total + (int)$visitors;
		            $final_data[ $step_id ][ $step_type ][ 'total' ]       = $total;

		            $conversion_label_keys  = array_keys( array_column( $conversions, 'label' ), $label, true );
		            $conversion_stepid_keys = array_keys( array_column( $conversions, 'step_id' ), $step_id, true );
		            $conversion_key         = array_values( array_intersect( $conversion_label_keys, $conversion_stepid_keys ) );
		            $conversion_key         = $conversion_key[ 0 ] ?? null;
		            $conversion             = is_numeric( $conversion_key ) ? (int)$conversions[ $conversion_key ][ 'conversions' ] : 0;
		            $conversion             = ( $conversion / $total ) * 100;

		            $bounce_label_keys  = array_keys( array_column( $bounced, 'label' ), $label, true );
		            $bounce_stepid_keys = array_keys( array_column( $bounced, 'step_id' ), $step_id, true );
		            $bounce_key         = array_values( array_intersect( $bounce_label_keys, $bounce_stepid_keys ) );
		            $bounce_key         = $bounce_key[ 0 ] ?? null;
		            $bounce             = is_numeric( $bounce_key ) ? (int)$bounced[ $bounce_key ][ 'bounced' ] : 0;
		            $bounce             = ( $bounce / $total ) * 100;

		            $final_data[ $step_id ][ $step_type ][ 'bounce_rate' ]      = $bounce;
		            $final_data[ $step_id ][ $step_type ][ 'conversions_rate' ] = 'thankyou' === $step_type ? 0 : $conversion;
	            }
            }
        }
        return $final_data;
    }

    /**
     * Get where query for custom date range
     *
     * @param string $start_date Start date.
     * @param string $end_date End date.
     *
     * @return array
     * @since  1.9.6
     */
    private function get_custom_where_query( $start_date, $end_date ) {
        global $wpdb;

        $start_date = date_format( date_create( $start_date ), 'Y-m-d H:i:s' );
        $end_date   = date_format( date_create( $end_date ), 'Y-m-d' ) . ' 23:59:59';
        $range      = strtotime( $end_date ) - strtotime( $start_date );
        $time_span  = round( $range / ( 60 * 60 * 24 ) );

        $prev_start_date = date_create( $start_date );
        date_sub( $prev_start_date, date_interval_create_from_date_string( $time_span . ' days' ) );
        $prev_start_date = date_format( $prev_start_date, 'Y-m-d H:i:s' );

        $prev_end_date = date_create( $end_date );
        date_sub( $prev_end_date, date_interval_create_from_date_string( $time_span . ' days' ) );
        $prev_end_date = date_format( $prev_end_date, 'Y-m-d' ) . ' 23:59:59';

        $conditions1 = $wpdb->prepare( 'AND (date_created BETWEEN %s AND %s) ', $start_date, $end_date );           //phpcs:ignore
        $conditions2 = $wpdb->prepare( 'AND (date_created BETWEEN %s AND %s) ', $prev_start_date, $prev_end_date ); //phpcs:ignore

        return [
            'current'  => $conditions1,
            'previous' => $conditions2,
            'labels'   => [
                'current'  => $this->get_custom_dates_label( $start_date, $end_date ),
                'previous' => $this->get_custom_dates_label( $prev_start_date, $prev_end_date ),
            ]
        ];
    }

    /**
     * Generate date labels within a specified date range.
     *
     * This function generates date labels within a specified date range, typically for use in charting or data
     * aggregation. It creates an associative array with date labels as keys and initial values set to zero.
     *
     * @param string $start_date The start date of the date range.
     * @param string $end_date   The end date of the date range.
     *
     * @return array An associative array with date labels as keys and initial values set to zero.
     * @since  1.9.6
     */
    private function get_custom_dates_label( $start_date = '', $end_date = '' ) {
        $labels  = [];
        $periods = new DatePeriod(
            new DateTime( $start_date ),
            new DateInterval( 'P1D' ),
            new DateTime( $end_date )
        );
        foreach( $periods as $date ) {
            $labels[ $date->format( 'M j' ) ] = 0;
        }
        return $labels;
    }

	/**
	 * Filters visitor data for a specific funnel.
	 *
	 * This function rearranges visitor data for a given funnel in a specific order.
	 * It starts with the first step of the funnel and arranges visitor data for each
	 * subsequent step in the order they appear within the funnel.
	 *
	 * @param int   $funnel_id The ID of the funnel.
	 * @param array $visitors  An associative array containing visitor data for each step.
	 *
	 * @return array An associative array of rearranged visitor data for the funnel.
	 * @since 1.9.6
	 */
	private function filter_visitor_data( $funnel_id, $visitors ) {
		$funnel_steps = Wpfnl_functions::get_steps( $funnel_id );
		$funnel_step_ids = array_column( $funnel_steps, 'id' );
		if ( !empty( $funnel_step_ids ) ) {
			foreach( $visitors as $step_id => $step_data ) {
				if ( !in_array( $step_id, $funnel_step_ids ) ) {
					unset( $visitors[ $step_id ] );
				}
			}
		}
		return $visitors_rearranged ?? $visitors;
	}


    /**
     * Delete/Reset all the analytics data for the specified
     * funnel id
     *
     * @param WP_REST_Request $request
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     * @since 2.1.1
     */
    public function reset_analytics_data( \WP_REST_Request $request ) {
        $params     = $this->get_api_params_values( $request );
        $funnel_id  = $params['funnel_id'];

        global $wpdb;

        // Delete analytics meta
        $meta_table = $wpdb->prefix . 'wpfnl_analytics_meta';
        $wpdb->delete( $meta_table, array( 'funnel_id' => $funnel_id ) );

        // Delete analytics
        $post_table = $wpdb->prefix . 'wpfnl_analytics';
        $wpdb->delete( $post_table, array( 'funnel_id' => $funnel_id ) );

        $response = array(
            'message'   => 'Analytics with funnel id ' . $funnel_id . ' have been deleted successfully.',
            'success'   => true
        );
        return rest_ensure_response( $response );
    }
}