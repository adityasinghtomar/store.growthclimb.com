(function( $ ) {
	'use strict';

	/**
	 * All the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered the best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$( document ).on( 'click', 'div[data-notice_id="wpfnl-import-notice"] button.notice-dismiss', dismissFunnelImportNotice );

	/**
	 * Create an ajax post request to hide funnel import notice.
	 *
	 * @since 1.9.7
	 */
	function dismissFunnelImportNotice() {
		const payload = {
			action: 'wpfnl_hide_import_funnel_notice',
			security: WPFunnelProVars.admin_nonce
		};

		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: WPFunnelProVars.ajaxurl,
			data: payload
		});
	}

})( jQuery );


