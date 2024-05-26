/**
 * Elementor my account
 *
 * @package Woostify Pro
 */

'use strict';

// My account widget.
var woostifyMyAccountWidget = function() {
	var element = document.querySelectorAll( '.woostify-my-account-widget' );
	if ( ! element.length ) {
		return;
	}

	element.forEach(
		function( ele ) {
			var navHead = ele.querySelectorAll( '.woostify-my-account-tab-head a' );
			if ( ! navHead.length ) {
				return;
			}

			for ( var i = 0, j = navHead.length; i < j; i++ ) {
				navHead[i].onclick = function( e ) {
					var t      = this,
						dataId = t.getAttribute( 'data-id' ),
						sibNav = 'function' === typeof( siblings ) ? siblings( t.parentNode ) : [],
						tabId  = ele.querySelector( '#' + dataId ),
						sibTab = 'function' === typeof( siblings ) && tabId ? siblings( tabId ) : [];
					
					if ( ! t.parentNode.classList.contains( 'account-menu-item-payment-methods' ) && ! t.parentNode.classList.contains( 'account-menu-item-customer-logout' ) ) {
						e.preventDefault();
					}

					t.parentNode.classList.add( 'active' );
					if ( sibNav.length ) {
						sibNav.forEach(
							function( sn ) {
								sn.classList.remove( 'active' );
							}
						);
					}

					if ( ! tabId ) {
						return;
					}

					if ( t.parentNode.classList.contains( 'no-prevent' ) ) {
						return;
					}

					tabId.classList.add( 'active' );
					if ( sibTab.length ) {
						sibTab.forEach(
							function( st ) {
								st.classList.remove( 'active' );
							}
						);
					}
				}
			}

			var buttonAddPayment = ele.querySelectorAll( '.payment-methods a.button' ),
				formPayment      = ele.querySelector( '#add_payment_method' ),
				paymentTab       = ele.querySelector( '.payment-methods-content' );
			
			if (buttonAddPayment.length) {
				buttonAddPayment[0].onclick = function( e ) {
					e.preventDefault();

					formPayment.classList.add('active');
					paymentTab.classList.add('hidden');
				}
			}
		}
	);

	var container = document.querySelector( '.woocommerce-account .woocommerce-orders-table tbody' );
	var pagination = document.querySelector('.woocommerce-account .order-pagination');
	var view_more_order = document.querySelector( '.woocommerce-account .woostify-view-more-order' );
	
	if (view_more_order) {
		let loading_status = view_more_order.querySelector( '.woostify-loading-status' );
		let view_more_btn = view_more_order.querySelector( '.w-view-more-button' );
		let loading_type = view_more_order.getAttribute('data-loading_type');
		let total_page = view_more_order.getAttribute('data-total_page');
		let options = {
			button: '.view-more-button',
			path: '.next.page-numbers',
			append: '.woocommerce-orders-table__row',
			history: false, // replace
			hideNav: '.woocommerce-pagination',
			checkLastPage: '.next.page-numbers',
			loadOnScroll: 'button' === loading_type ? false : true
		}

		if ( null == pagination || 'undefined' === typeof( pagination ) ) {

			if ( 'button' === loading_type ) {
				view_more_order.style.display = 'none';
			} else {
				options.loadOnScroll = false;
			}
		} else {
	
			if ( 'button' === loading_type ) {
				view_more_order.style.display = 'block';
				view_more_btn.style.display = 'inline-flex';
			} else {
				options.loadOnScroll = true;
			}
		}
	
		window.infScroll = new InfiniteScroll(
			container,
			options
		);

		infScroll.loadCount = 0;

		infScroll.on(
			'request',
			function( path, fetchPromise ) {
				if ( 'button' === loading_type ) {
					view_more_btn.classList.add( 'circle-loading' );
				} else {
					loading_status.style.display = 'inline-block';
				}
			}
		);

		infScroll.on(
			'load',
			function( body, path, fetchPromise ) {
				
				if ( 'button' === loading_type ) {
					view_more_btn.classList.remove( 'circle-loading' );
				}else {
					loading_status.style.display = 'none';
				}

				if ( infScroll.loadCount + 1 == total_page ) {
					view_more_order.style.display = 'none';
				}

			}
		);

		infScroll.on(
			'last',
			function( body, path ) {
				if ( 'button' === loading_type ) {
					view_more_btn.style.display = 'none';
				} else {
					loading_status.style.display = 'none';
				}
			}
		);

		if( 'button' === loading_type ){
			view_more_btn.addEventListener(
				'click',
				function() {
					infScroll.loadNextPage();
				}
			);
		}
	

	}


}

document.addEventListener(
	'DOMContentLoaded',
	function() {
		woostifyMyAccountWidget();

		// For preview mode.
		if ( 'function' === typeof( onElementorLoaded ) ) {
			onElementorLoaded(
				function() {
					window.elementorFrontend.hooks.addAction(
						'frontend/element_ready/global',
						function() {
							woostifyMyAccountWidget();
						}
					);
				}
			);
		}
	}
);
