/**
 * @todo: convert this file to Vanilla JS.
 */
/* global woostify_fbt */

'use strict';
window.__cfRLUnblockHandlers = true;
function math_round(number) {
	return (Math.round(number * 100) / 100).toFixed(2)
}

function formatMoney(number, decPlaces, decSep, thouSep) {
	decPlaces = isNaN(decPlaces = Math.abs(decPlaces)) ? 2 : decPlaces,
		decSep = typeof decSep === "undefined" ? "." : decSep;
	thouSep = typeof thouSep === "undefined" ? "," : thouSep;
	var sign = number < 0 ? "-" : "";
	var i = String(parseInt(number = Math.abs(Number(number) || 0).toFixed(decPlaces)));
	var j = (j = i.length) > 3 ? j % 3 : 0;

	return sign +
		(j ? i.substr(0, j) + thouSep : "") +
		i.substr(j).replace(/(\decSep{3})(?=\decSep)/g, "$1" + thouSep) +
		(decPlaces ? decSep + Math.abs(number - i).toFixed(decPlaces).slice(2) : "");
}

function woostify_fbt_onchange_input_check_total_discount() {
	var total_price = 0,
		total_new_price = 0,
		woostify_fbt_list = document.querySelector('.woostify-fbt'),
		product_bundles   = document.querySelector('.woostify-fbt__products');

	var options         = woostify_fbt.options,
		discount_type   = options['type']||'discount-per-item',
		saved_pack      = options['display_products_saved']||'percent_off';

	var is_discount_item = ( discount_type == 'discount-per-item' ),
		is_percent       = ( saved_pack == 'percent_off' );
	if (!product_bundles) return;

	var input_checked_lenght = document.querySelectorAll('.woostify-fbt__products input[type=checkbox]:checked').length,
		product_bundle_data = product_bundles.getAttribute('data-total-discount'),
		product_bundle_data_arr = product_bundle_data.split(','), 
		currencySymbol = '<span class="woocommerce-Price-currencySymbol">' + document.querySelector('.woostify-fbt__discount .current-price span.woocommerce-Price-amount .woocommerce-Price-currencySymbol', woostify_fbt_list).outerHTML + '</span>',
		remove_thousand_separator = '',
		remove_thousand_separator = woostify_fbt.thousand_separator == '.' ? '\\.' : woostify_fbt.thousand_separator,
		remove_thousand_separator = new RegExp(remove_thousand_separator, "g");

	var checkbox_checkbox = document.querySelectorAll('.woostify-fbt__products input[type=checkbox]:checked');
	checkbox_checkbox.forEach(
		function (currentElement) {
			var parent = currentElement.parentNode,
				price = parseFloat( parent.getAttribute('data-item-price-with-filter').replace(remove_thousand_separator, '').replace(',', '.') ),
				discount = parent.getAttribute('data-item-discount'),
				price_elm = parent.querySelector('.woostify-fbt__product-price .woocommerce-Price-amount'),
				priceHtml = '';
			total_price += price;
			var new_price = price;
			if ( is_discount_item ){
				new_price = is_percent ? parseFloat( new_price * ( 100 - discount ) / 100 ).toFixed(2) : parseFloat( new_price - discount ).toFixed(2);
				total_new_price += parseFloat( new_price );
				new_price = new_price.toString().replace('.', woostify_fbt.decimal_separator).replace(/\B(?=(\d{3})+(?!\d))/g, woostify_fbt.thousand_separator);

				var htmlSymbol = '<span class="woocommerce-Price-currencySymbol">' + document.querySelector('.woostify-fbt__discount span.woocommerce-Price-amount .woocommerce-Price-currencySymbol', parent).outerHTML + '</span>';
				if (woostify_fbt.currency_pos.indexOf('right') >= 0) {
					priceHtml = new_price + htmlSymbol
				} else {
					priceHtml = htmlSymbol + new_price
				}
				
			}
			// document.querySelectorAll('.woocommerce-Price-new').forEach( (t) => { t.remove() });
			// if( priceHtml ) {
			// 	let new_price_html = document.createElement('span');
			// 	new_price_html.classList.add('woocommerce-Price-new');
			// 	new_price_html.innerHTML = priceHtml;
			// 	price_elm.insertAdjacentElement('afterend', new_price_html);
			// 	document.querySelector('.woostify-fbt__discount span.woocommerce-Price-amount', parent).innerHTML = priceHtml;
			// 	document.querySelector('.woostify-fbt__discount span.woocommerce-Price-amount').classList.add('deleted');
			// 	total_price += parseFloat(price);
			// } else {
			// 	document.querySelector('.woostify-fbt__discount span.woocommerce-Price-amount').classList.remove('deleted');
			// }
		}
	);
	total_new_price = total_new_price.toFixed(2);
	var discount = is_percent ? ( ( total_price - total_new_price ) / total_price * 100 ).toFixed(2) : total_price - total_new_price;
	if( !is_discount_item ){
		discount = woostify_fbt.settings.woostify_fbt_total_discount||0;
		var checkbox_unchecked = document.querySelectorAll('.woostify-fbt__products input[type=checkbox]:not(:checked)');
		if( checkbox_unchecked.length ) {
			discount = 0;
			total_new_price = total_price;
		}else{
			total_new_price = is_percent ? ( total_price -  total_price * discount / 100 ).toFixed(2) : total_price - discount;
		}
	}

	discount = parseFloat( parseFloat(discount).toFixed(2) ); // Tranh loi dau phay dong example: discount = 0.1 + 0.2
	if( total_price == 0 ){
		total_new_price = discount = 0;
	}
	if (woostify_fbt.currency_pos.indexOf('right') >= 0) {
		var currencySymbolLeft = '';
		var currencySymbolRight = currencySymbol
	} else {
		var currencySymbolLeft = currencySymbol;
		var currencySymbolRight = ''
	}
	document.querySelector('.woostify-fbt__discount .current-price span.woocommerce-Price-amount', woostify_fbt_list).innerHTML = currencySymbolLeft + math_round(total_new_price).toString().replace('.', woostify_fbt.decimal_separator).replace(/\B(?=(\d{3})+(?!\d))/g, woostify_fbt.thousand_separator) + currencySymbolRight;
	let old_elm = document.querySelector('.woostify-fbt__discount .old-price span.woocommerce-Price-amount', woostify_fbt_list);
	if (total_price == total_new_price) {
		old_elm.parentNode.style.display = 'none';
	} else {
		old_elm.parentNode.style.display = '';
		old_elm.innerHTML = currencySymbolLeft + math_round(total_price).toString().replace('.', woostify_fbt.decimal_separator).replace(/\B(?=(\d{3})+(?!\d))/g, woostify_fbt.thousand_separator) + currencySymbolRight;
	}
	let saved_elm = document.querySelector('.woostify-fbt__discount .saved', woostify_fbt_list);
	if( discount != 0 ){
		saved_elm.parentNode.style.display = '';
		saved_elm.innerHTML = discount + ( is_percent ? '%' : currencySymbol );
	} else {
		saved_elm.parentNode.style.display = 'none';
	}
}

function woostify_fbt_add_all_to_cart(evt, obj) {
	evt.preventDefault();
	var parent = obj.closest('.woostify-fbt'),
		list_product_id = [],
		variable = obj.attr('data-variation_id'),
		main_pro_meta = {},
		bundle_variable = {},
		bundle_variable_json = '';

	if (!variable || variable == 'undefined') {
		variable = 0;
	}
	document.querySelectorAll('.woostify-fbt .woostify-fbt__products .woostify-fbt__product--item').forEach(function (event) {
		var checked = event.querySelectorAll('input[type="checkbox"]:checked').length;
		if (checked) {
			list_product_id.push(event.getAttribute('data-product-id'));
		}
	});

	bundle_variable[variable] = {};
	document.querySelectorAll('.woostify-fbt__products .product_variations').forEach(function (el1) {
		if (el1.classList.contains('main-product')) {
			el1.querySelectorAll('select').forEach(function (el, index) {
				if (el.value != '') {
					// main_pro_meta['attribute_' + el.getAttribute('data-attribute')] = el.value;  
					bundle_variable[variable]['attribute_' + el.getAttribute('data-attribute')] = el.value;
				}
				// var main_price = el.closest('.woostify-fbt__product--item').getAttribute('data-item-price');
				// main_pro_meta.price = main_price;
			});
		} else {
			var bundle_id = el1.getAttribute('data-product_id'),
				bundle_item_vari = {},
				// bundle_price = el1.closest('.woostify-fbt__product--item').getAttribute('data-item-price'),
				bundle_variation = '',
				attribute_count = el1.querySelectorAll('select').length;

			el1.querySelectorAll('select').forEach(function (el, index) {
				if (el.value != '') {
					bundle_variation += el.getAttribute('data-attribute').replace('pa_', '') + ': ' + el.value;
					if (index < attribute_count - 1) {
						bundle_variation += ' + '
					}
					bundle_item_vari['attribute_' + el.getAttribute('data-attribute')] = el.value;
				}
			});
			// bundle_item_vari.price = bundle_price;
			bundle_item_vari.variable = bundle_variation;
			bundle_variable[bundle_id] = bundle_item_vari;
		}
	});

	bundle_variable_json = JSON.parse(JSON.stringify(bundle_variable));

	if (list_product_id) {

		jQuery.ajax({
			type: "POST",
			url: woostify_fbt.ajaxurl + '?wc-ajax=woostify-fbt-add-to-cart',
			data: {
				'action': 'woostify_fbt_add_all_to_cart',
				'list_product_id': list_product_id,
				// 'variable': variable,
				// 'main_pro_meta': main_pro_meta,
				'bundle_variable': bundle_variable_json,
				'_nonce': woostify_fbt._nonce,
			},
			beforeSend: function () {
				obj.addClass('loading')
			},
			success: function (response) {
				woostifyAjaxSingleUpdateFragments();
				setTimeout(
					function () {
						obj.removeClass('loading');
						cartSidebarOpen();
						closeAll();
					}, 3000
				);
			}
		});
	}
}

function woostify_fbt_drag_to_scroll() {
	const slider = document.querySelector('.woostify-fbt__products-list .woostify-fbt__products');
	if (!slider) return;
	let isDown = false;
	let startX;
	let scrollLeft;
	slider.addEventListener('mousedown', (e) => {
		isDown = true;
		slider.classList.add('active');
		startX = e.pageX - slider.offsetLeft;
		scrollLeft = slider.scrollLeft;
	});
	slider.addEventListener('mouseleave', () => {
		isDown = false;
		slider.classList.remove('active');
	});
	slider.addEventListener('mouseup', () => {
		isDown = false;
		slider.classList.remove('active');
	});
	slider.addEventListener('mousemove', (e) => {
		if (!isDown) return;
		e.preventDefault();
		const x = e.pageX - slider.offsetLeft;
		const walk = (x - startX) * 1;
		slider.scrollLeft = scrollLeft - walk;
	});
}

document.addEventListener(
	'DOMContentLoaded',
	function () {
		var product_select = document.querySelectorAll('.woostify-fbt__products select');
		product_select.forEach(
			function (currentElement) {
				currentElement.addEventListener(
					'change',
					function (event) {
						var parent = event.currentTarget.closest('.product');
						var wrapper = event.currentTarget.closest('.product_variations');
						var variations_data = JSON.parse(wrapper.getAttribute('data-variations'));
						var attributes = {};

						// Lay tat ca attibute đang select
						wrapper.querySelectorAll('select').forEach(element => {
							var attribute_name = element.getAttribute('data-attribute_name');
							var attribute_value = element.value;
							attributes[attribute_name] = attribute_value;
						});

						var variation = variations_data.filter(function (variation) {
							var variation_attributes = variation.attributes;
							var match = true;
							for (var attribute_name in attributes) {
								if( variation_attributes[attribute_name] == '' ) {
									match = true; // any attributes
								} else if (attributes[attribute_name] !== variation_attributes[attribute_name]) {
									return false;
								}
							}
							return match;
						});
						var price_element = parent.querySelector('.woostify-fbt__product-price');
						let outofstock_elm = parent.querySelector('.woostify-fbt_product__outofstock');
						if( !( variation.length) || !variation[0]['is_in_stock'] ) {
							price_element.classList.add('unavailable');
							price_element.style.display = 'none';
							outofstock_elm.innerHTML = woostify_fbt.outofstock_text;

							let checkbox_elm = parent.querySelector('input[type=checkbox]');
							if (checkbox_elm.id != 'woostify_fbt_product_main') {
								checkbox_elm.checked = false;
								let event = new Event('change');
								checkbox_elm.dispatchEvent(event);
								checkbox_elm.disabled = true;
							}
							return;
							
						}
						if( variation[0].price_html) {
							price_element.classList.remove('unavailable');
							price_element.style.display = '';
							price_element.innerHTML = variation[0].price_html;
							outofstock_elm.innerHTML = '';

							let checkbox_elm = parent.querySelector('input[type=checkbox]');
							if (checkbox_elm.id != 'woostify_fbt_product_main') {
								checkbox_elm.disabled = false;
								if( ! checkbox_elm.checked ) {
									checkbox_elm.checked = true;
									let event = new Event('change');
									checkbox_elm.dispatchEvent(event);
								}
							}
						}

						parent.setAttribute('data-item-price', variation[0].display_price);
						var price_filter = formatMoney(variation[0].display_price, woostify_fbt.number_decimals, woostify_fbt.decimal_separator, woostify_fbt.thousand_separator);
						parent.setAttribute('data-item-price-with-filter', price_filter);

						if (parent.classList.contains('woostify-fbt__product--item-main')) {
							document.querySelector('.woostify-fbt__button').setAttribute('data-variation_id', variation[0].variation_id);
							parent.querySelector('#woostify_fbt_product_main').value = variation[0].variation_id;
						}
						parent.setAttribute('data-product-id', variation[0].variation_id);


						woostify_fbt_onchange_input_check_total_discount();
					}
				);
			}
		);
		if (product_select.length) {
			product_select.forEach(function (elm) {
				var event = new Event('change');
				elm.dispatchEvent(event);
			});
		}
		woostify_fbt_onchange_input_check_total_discount();
		woostify_fbt_drag_to_scroll();

		document.querySelectorAll('.woostify-fbt__products').forEach( function(element){
			var count = element.getAttribute("data-product_count");
			element.style.gridTemplateColumns = "repeat(" + count + ", 1fr)";
		});
	}
);
