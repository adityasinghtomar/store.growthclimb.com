/**
 * Get Woo Settings
 */
function wpsc_get_woo_settings(is_humbargar = false) {

	if (is_humbargar) {
		wpsc_toggle_humbargar( is_humbargar = false );
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.woo, .wpsc-humbargar-menu-item.woo' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.woo_settings );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=woo' );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = { action: 'wpsc_get_woo_settings' };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-body' ).html( response );
			wpsc_reset_responsive_style();
		}
	);
}

/**
 * Save woo settings
 */
function wpsc_set_woo_settings(el) {

	var form     = jQuery( '.wpsc-frm-woo-settings' )[0];
	var dataform = new FormData( form );
	jQuery( '.wpsc-modal-footer button' ).attr( 'disabled', true );
	jQuery( el ).text( supportcandy.translations.please_wait );
	jQuery.ajax(
		{
			url: supportcandy.ajax_url,
			type: 'POST',
			data: dataform,
			processData: false,
			contentType: false
		}
	).done(
		function (res) {
			wpsc_get_woo_settings();
		}
	);
}

/**
 * Reset woo settings
 */
function wpsc_reset_woo_settings(el, nonce) {

	jQuery( '.wpsc-modal-footer button' ).attr( 'disabled', true );
	jQuery( el ).text( supportcandy.translations.please_wait );
	var data = { action: 'wpsc_reset_woo_settings', _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_get_woo_settings();
		}
	);
}

/**
 * Get edit WooCommerce order widget
 */
function wpsc_get_tw_woo_order() {

	wpsc_show_modal();
	var data = { action: 'wpsc_get_tw_woo_order' };
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
 * Set edit WooCommerce order widget
 */
function wpsc_set_tw_woo_order(el) {

	var form     = jQuery( '.wpsc-frm-edit-woo-order' )[0];
	var dataform = new FormData( form );
	
	if (dataform.get( 'label' ).trim() == '') {
		alert( supportcandy.translations.req_fields_missing );
		return;
	}
	
	jQuery( '.wpsc-modal-footer button' ).attr( 'disabled', true );
	jQuery( el ).text( supportcandy.translations.please_wait );
	
	jQuery.ajax(
		{
			url: supportcandy.ajax_url,
			type: 'POST',
			data: dataform,
			processData: false,
			contentType: false
		}
	).done(
		function (res) {
			wpsc_close_modal();
			wpsc_get_ticket_widget();
		}
	);
}

/**
 * Get edit WooCommerce subscription widget
 */
function wpsc_get_tw_woo_subscription() {

	wpsc_show_modal();
	var data = { action: 'wpsc_get_tw_woo_subscription' };
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
 * Set edit WooCommerce order widget
 */
function wpsc_set_tw_woo_subscription(el) {

	var form     = jQuery( '.wpsc-frm-edit-woo-sub' )[0];
	var dataform = new FormData( form );
	
	if (dataform.get( 'label' ).trim() == '') {
		alert( supportcandy.translations.req_fields_missing );
		return;
	}
	
	jQuery( '.wpsc-modal-footer button' ).attr( 'disabled', true );
	jQuery( el ).text( supportcandy.translations.please_wait );

	jQuery.ajax(
		{
			url: supportcandy.ajax_url,
			type: 'POST',
			data: dataform,
			processData: false,
			contentType: false
		}
	).done(
		function (res) {
			wpsc_close_modal();
			wpsc_get_ticket_widget();
		}
	);
}

/**
 * Get woo product field report
 */
function wpsc_rp_get_cf_woo_product(cf_slug, nonce, is_humbargar = false) {

	// do not call if other report is still loading.
	if (supportcandy.temp.isReportProgress) {
		return;
	}

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.' + cf_slug + ', .wpsc-humbargar-menu-item.' + cf_slug ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.cf_slug );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-reports&section=' + cf_slug );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	// set run function.
	supportcandy.currentReportSlug       = cf_slug;
	supportcandy.currentReportRunFuntion = cf_slug;

	wpsc_scroll_top();

	var data = { action: 'wpsc_rp_get_woo_product', 'cf_slug': cf_slug, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-body' ).html( response );
			wpsc_reset_responsive_style();
		}
	);
}

/**
 * Run woo product reports
 */
function wpsc_rp_run_cfwp_report(nonce) {

	jQuery( '#wpscTicketStatisticsCanvas' ).remove();
	jQuery( '.wpscPrograssLoaderContainer' ).show();
	jQuery( '.wpscPrograssLoaderContainer' ).html( supportcandy.loader_html );

	// get filters (if any).
	let form     = jQuery( '.wpsc-report-filters' )[0];
	var dataform = new FormData( form );
	let filter   = dataform.get( 'filter' );
	var filters  = {};
	if (filter == 'custom') {
		filters = supportcandy.RpFilters;
	}
	filters = JSON.stringify( filters );

	let from = new Date( jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val() );
	let to   = new Date( jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val() );

	fromDate = from.toISOString().split('T')[0] + ' 00:00:00';
	toDate   = to.toISOString().split('T')[0] + ' 23:59:59';

	dataform = new FormData( form );
	dataform.append( 'action', 'wpsc_rp_run_cfwp_report' );
	dataform.append( 'filters', filters );
	dataform.append( 'from_date', fromDate );
	dataform.append( 'to_date', toDate );
	dataform.append( 'cf_slug', supportcandy.currentReportSlug );
	dataform.append( '_ajax_nonce', nonce );
	jQuery.ajax(
		{
			url: supportcandy.ajax_url,
			type: 'POST',
			data: dataform,
			processData: false,
			contentType: false
		}
	).done(
		function (res) {
			labels = [];
			data   = [];
			for (var key in res) {
				labels.push( key );
				data.push( res[key] );
			}

			height = (Math.ceil( labels.length / 10 ) * 100) + 500;

			jQuery( '.wpscPrograssLoaderContainer' ).hide();
			jQuery( '.wpsc-setting-section-body' ).children().last().before( '<canvas id="wpscTicketStatisticsCanvas" style="height:' + height + 'px !important;" class="wpscRpCanvas"></canvas>' );
			var data   = {
				labels: labels,
				datasets: [
					{
						label: 'Count',
						backgroundColor: '#e74c3c',
						borderColor: '#e74c3c',
						data: data
				}
				]
			};
			var config = {
				type: 'bar',
				data,
				options: {
					responsive: true,
					maintainAspectRatio: false,
					indexAxis: 'y',
					scales: {
						x: {
							beginAtZero: true,
							title: {
								display: true,
								'text': 'Number of tickets'
							}
						}
					}
				}
			};
			new Chart(
				document.getElementById( 'wpscTicketStatisticsCanvas' ),
				config
			);
		}
	);
}
