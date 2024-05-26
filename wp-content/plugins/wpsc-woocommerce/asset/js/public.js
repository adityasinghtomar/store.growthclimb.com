/**
 * Get edit WooCommerce widget
 */
function wpsc_view_woo_order(ticket_id, order_id, nonce) {

	wpsc_show_modal();
	var data = {
		action: 'wpsc_view_woo_order',
		ticket_id,
		order_id,
		_ajax_nonce: nonce
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {

			// Set to modal.
			jQuery( '.wpsc-modal-header' ).text( response.title );
			jQuery( '.wpsc-modal-body' ).html( response.body );
			jQuery( '.wpsc-modal-footer' ).html( response.footer );
			// Display modal.
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * View more WooCommerce orders
 */
function wpsc_view_more_woo_orders(ticket_id, nonce) {

	wpsc_show_modal();
	var data = {
		action: 'wpsc_view_more_woo_orders',
		ticket_id,
		_ajax_nonce: nonce
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {

			// Set to modal.
			jQuery( '.wpsc-modal-header' ).text( response.title );
			jQuery( '.wpsc-modal-body' ).html( response.body );
			jQuery( '.wpsc-modal-footer' ).html( response.footer );
			// Display modal.
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * View WooCommerce order from all orders list
 */
function wpsc_view_woo_order_tbl(ticket_id, order_id, nonce) {

	wpsc_close_modal();
	setTimeout(
		function () {
			wpsc_view_woo_order( ticket_id, order_id, nonce );
		},
		500
	);
}

/**
 * Change customer orders in WooCommerce Order custom fields while creating ticket create-as
 */
function wpsc_woo_change_customer_orders(nonce) {

	var curCustomer   = jQuery( '.wpsc-create-ticket input.email' ).val().trim();
	var url           = new URL( window.location.href );
	var search_params = url.searchParams;
	url.search        = search_params.toString();

	var data = {
		action: 'wpsc_woo_get_create_as_orders',
		email: curCustomer,
		_ajax_nonce: nonce
	};

	search_params.forEach(
		function (value, key) {
			data[key] = value;
		}
	);

	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {

			jQuery.each(
				response,
				function (key, field) {
					var currentEl = jQuery( '.wpsc-tff.' + field.slug );
					var nextEl    = currentEl.next();
					currentEl.remove();
					nextEl.before( field.html );
				}
			);
		}
	);
}

/**
 * Get view WooCommerce subscription
 */
function wpsc_view_woo_subscription(ticket_id, order_id, nonce) {

	wpsc_show_modal();
	var data = {
		action: 'wpsc_view_woo_subscription',
		ticket_id,
		order_id,
		_ajax_nonce: nonce
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {

			// Set to modal.
			jQuery( '.wpsc-modal-header' ).text( response.title );
			jQuery( '.wpsc-modal-body' ).html( response.body );
			jQuery( '.wpsc-modal-footer' ).html( response.footer );
			// Display modal.
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * View more WooCommerce subscriptions
 */
function wpsc_view_more_woo_subscriptions(ticket_id) {

	wpsc_show_modal();
	var data = {
		action: 'wpsc_view_more_woo_subscriptions',
		ticket_id
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {

			// Set to modal.
			jQuery( '.wpsc-modal-header' ).text( response.title );
			jQuery( '.wpsc-modal-body' ).html( response.body );
			jQuery( '.wpsc-modal-footer' ).html( response.footer );
			// Display modal.
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * View WooCommerce subscription from all orders list
 */
function wpsc_view_woo_subscription_tbl(ticket_id, order_id) {

	wpsc_close_modal();
	setTimeout(
		function () {
			wpsc_view_woo_subscription( ticket_id, order_id );
		},
		500
	);
}

/**
 * Get purchase details of customer order
 *
 * @param {INT} ticket_id
 * @param {INT} order_id
 */
function wpsc_woo_customer_get_order_details(customer_id, order_id, nonce) {

	wpsc_show_modal();
	var data = {
		action: 'wpsc_woo_customer_get_order_details',
		customer_id,
		order_id,
		_ajax_nonce: nonce
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {

			// Set to modal.
			jQuery( '.wpsc-modal-header' ).text( response.title );
			jQuery( '.wpsc-modal-body' ).html( response.body );
			jQuery( '.wpsc-modal-footer' ).html( response.footer );
			// Display modal.
			wpsc_show_modal_inner_container();
		}
	);
}
