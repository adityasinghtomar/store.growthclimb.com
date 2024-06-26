/**
 * Variation Swatches
 *
 * @package Woostify Pro
 */

/* global woostify_variation_swatches_admin */

'use strict';

if (typeof woostifyEvent == 'undefined') {
	var woostifyEvent = {};
}
// Check variation is available.
var woostifyAvailableVariations = function (target) {
	var selector = target.closest('#woostify-quick-view-panel') ? document.getElementById('woostify-quick-view-panel') : document.getElementById('view');
	if (!selector) {
		return;
	}

	var composite_form = document.querySelectorAll('form.composite_form');

	if (composite_form.length) {
		var composite_component = selector.querySelectorAll('.composite_component');

		composite_component.forEach(
			function (item) {  
				var availableSelect = item.querySelectorAll('.variations [data-attribute_name^="attribute_"] option');
				if (availableSelect.length) {
					var availableValue = [];
					availableSelect.forEach(
						function (as) {
							var selectValue = as.getAttribute('value');
							if (!selectValue || as.disabled) {
								return;
							}
			
							availableValue.push(selectValue);
						}
					);

					availableValue = availableValue.filter(function (value, index, array) { 
						return array.indexOf(value) === index;
					});
			
					var availableSwatch = item.querySelectorAll('.variations .swatch');
					if (availableSwatch.length) {
						availableSwatch.forEach(
							function (awv) {
								var swatchValue = awv.getAttribute('data-value');
				
								if (availableValue.includes(swatchValue)) {
									awv.classList.remove('unavailable');
								} else {
									awv.classList.add('unavailable');
								}
							}
						);
					}
				}
			}
		);

	}else{
		var availableSelect = selector.querySelectorAll('.variations [name^="attribute_"] option');

		if (availableSelect.length) {
			var availableValue = [];
			availableSelect.forEach(
				function (as) {
					var selectValue = as.getAttribute('value');
					if (!selectValue || as.disabled) {
						return;
					}
	
					availableValue.push(selectValue);
				}
			);
			availableValue = availableValue.filter(function (value, index, array) { 
				return array.indexOf(value) === index;
			});
	
			var availableSwatch = selector.querySelectorAll('.variations .swatch');
			if (availableSwatch.length) {
				availableSwatch.forEach(
					function (awv) {
						var swatchValue = awv.getAttribute('data-value');
		
						if (availableValue.includes(swatchValue)) {
							awv.classList.remove('unavailable');
						} else {
							awv.classList.add('unavailable');
						}
					}
				);
			}
		}
	}
	
}	

// Variation swatches.
var woostifyVariationSwatches = function () {
	var composite_component =  document.querySelectorAll('form.composite_form .composite_component');
	var form = (composite_component.length)? composite_component : document.querySelectorAll('form.variations_form');

	if (!form.length) {
		return;
	}

	for (var i = 0, j = form.length; i < j; i++) {
		var element = form[i];

		var	swatch = element.querySelectorAll('.swatch');

		if (!swatch.length) {
			return;
		}

		var selected = [],
			change = new Event('change', { bubbles: true }),
			noMatching = new Event('woostify_no_matching_variations');
		
		swatch.forEach(
			function (el) {
				el.onclick = function (e) {
					e.preventDefault();

					if (el.classList.contains('unavailable')) {
						return;
					}

					var variations = el.closest('.variations'),
						parent = el.closest('.value'),
						allSelect = variations.querySelectorAll('select'),
						select = parent.querySelector('select'),
						attribute = select.getAttribute('data-attribute_name') || select.getAttribute('name'),
						value = el.getAttribute('data-value'),
						combi = select ? select.querySelectorAll('option[value="' + value + '"]') : [],
						sibs = siblings(el);

					// Check if this combination is available.
					if (!combi.length) {
						element.dispatchEvent(noMatching, el);

						return;
					}

					if (-1 === selected.indexOf(attribute)) {
						selected.push(attribute);
					}

					// Highlight swatch.
					if (el.classList.contains('selected')) {
						select.value = '';
						el.classList.remove('selected');

						delete selected[selected.indexOf(attribute)];
					} else {
						el.classList.add('selected');

						if (sibs.length) {
							sibs.forEach(
								function (sb) {
									sb.classList.remove('selected');
								}
							);
						}

						select.value = value;
					}

					// Trigger 'change' event.
					select.dispatchEvent(change);
				}
			}
		);

		// Reset variations.
		var reset = element.querySelector('.reset_variations');

		if (reset && !composite_component.length) {
			reset.addEventListener(
				'click',
				function () {
					var resetSwatches = element.querySelectorAll('.swatch');
					if (resetSwatches.length) {
						resetSwatches.forEach(
							function (rs) {

								// Remove all 'unavailable', 'selected' class.
								rs.classList.remove('unavailable', 'selected');
							}
						);
					}

					// Reset selected.
					selected = [];
				}
			);
		}
		
		// Warning if no matching variations.
		element.addEventListener(
			'woostify_no_matching_variations',
			function () {
				window.alert(wc_add_to_cart_variation_params.i18n_no_matching_variations_text);
			}
		);
	}
}

// Swatch list.
var woostifySwatchList = function () {
	var list = document.querySelectorAll('.swatch-list');
	if (!list.length) {
		return;
	}

	list.forEach(
		function (element) {
			var parent = element.closest('.product'),
				imageWrap = parent.querySelector('.product-loop-image-wrapper'),
				image = parent.querySelector('.product-loop-image'),
				items = element.querySelectorAll('.swatch');

			if (!items.length) {
				return;
			}

			items.forEach(
				function (item) {
					var sib = siblings(item),
						src = item.getAttribute('data-slug');

					// Set selected swatch.
					if (item.classList.contains('selected')) {
						image.setAttribute('srcset', '');
						image.src = src;
					}

					item.onclick = function () {
						if (!image.getAttribute('data-swatch')) {
							image.setAttribute('data-swatch', image.src);
						}

						imageWrap.classList.add('circle-loading');

						// Remove srcset attribute.
						image.setAttribute('srcset', '');

						// For siblings.
						if (sib.length) {
							sib.forEach(
								function (el) {
									el.classList.remove('selected');
								}
							);
						}

						// Highlight.
						if (item.classList.contains('selected')) {
							item.classList.remove('selected');
							image.src = image.getAttribute('data-swatch');
						} else {
							item.classList.add('selected');
							image.src = src;
						}

						// Image loading.
						var img = new Image();
						img.src = src;

						img.onload = function () {
							imageWrap.classList.remove('circle-loading');
						};
					}
				}
			);
		}
	);
}

// Woostify variation swatches - Add URL active.
var variationAddUrl = function () {
	var form = document.querySelectorAll('.product-summary form.variations_form');
	if (!form.length) {
		return;
	}

	for (var i = 0, j = form.length; i < j; i++) {
		var element = form[i],
			swatch = element.querySelectorAll('.swatch'),
			woostify_variation_swatches = element.querySelector('.woostify-variation-swatches');
		if (!woostify_variation_swatches) return;

		if (!woostify_variation_swatches.classList.contains('url-active')) {
			return;
		}

		if (!swatch.length) {
			return;
		}

		swatch.forEach(
			function (el) {
				el.addEventListener(
					'click',
					function (e) {
						e.preventDefault();
						var value = el.getAttribute('data-value'),
							attribute_name = el.closest('.woostify-variation-swatches').getAttribute('data-attribute_name'),
							queryParams = new URLSearchParams(window.location.search);

						if (this.classList.contains('selected')) {
							queryParams.set(attribute_name, value);
						} else {
							queryParams.delete(attribute_name);
						}
						history.replaceState(null, null, "?" + queryParams.toString());
					}
				)
			}
		);

		// Reset variations.
		var reset = element.querySelector('.reset_variations');
		if (reset) {
			reset.addEventListener(
				'click',
				function () {
					var uri = window.location.href,
						clean_uri = uri.substring(0, uri.indexOf('?'));
					window.history.replaceState({}, document.title, clean_uri);
				}
			);
		}
	}
}

jQuery(document).ajaxStop(function () {

	if ((!(woostifyEvent.productSwatchIconReady || 0))) {
		jQuery(document).on('click', '.swatch-list .swatch', function (e) {

			var imageWrap = jQuery(this).parents('.product-loop-wrapper').find('.product-loop-image-wrapper'),
				image = jQuery(this).parents('.product-loop-wrapper').find('.product-loop-image'),
				src = $(this).attr('data-slug');

			if (!image.attr('data-swatch')) {
				image.attr('data-swatch', image.attr('src'));
			}

			imageWrap.addClass('circle-loading');

			// Remove srcset attribute.
			image.attr('srcset', '');
			jQuery(this).siblings().removeClass('selected');


			// Highlight.
			if ($(this).hasClass('selected')) {
				$(this).removeClass('selected');
				image.attr('src', image.attr('data-swatch'));

			} else {
				$(this).addClass('selected');
				image.attr('src', src);
			}

			// Image loading.
			var img = new Image();
			img.src = src;

			img.onload = function () {
				imageWrap.removeClass('circle-loading');
			};
		});
		woostifyEvent.productSwatchIconReady = 1;
	}

	woostifySwatchList();
});

document.addEventListener(
	'DOMContentLoaded',
	function () {
		if ((woostifyEvent.productSwatchReady || 0)) {
			return;
		}

		woostifySwatchList();
		variationAddUrl();

		jQuery(document.body).on(
			'check_variations',
			function (e) {

				woostifyVariationSwatches();
				woostifyAvailableVariations(e.target);
			}
		);

		jQuery( document.body ).on( 'woocommerce_variation_select_change', function() {
			var composite_component =  document.querySelectorAll('form.composite_form .composite_component');

			if (composite_component.length) {
				for (let i = 0, j = composite_component.length; i < j; i++) {
					let element = composite_component[i];
					let select = element.querySelectorAll('.variations [data-attribute_name^="attribute_"]');
					let	swatch = element.querySelectorAll('.swatch');
					let reset = element.querySelector('.reset_variations');

					if (reset) {
						reset.addEventListener(
							'click',
							function (e) { 
								e.preventDefault();

								reset.classList.remove('opacity');
								let resetSwatches = element.querySelectorAll('.swatch');

								if (resetSwatches.length) {
									resetSwatches.forEach(
										function (rs) {
											// Remove all 'unavailable', 'selected' class.
											rs.classList.remove('unavailable', 'selected');
										}
									);
								}

								let availableSelect = element.querySelectorAll('.variations [data-attribute_name^="attribute_"] option');
								if (availableSelect.length) {
									let availableValue = [];
									availableSelect.forEach(
										function (as) {
											let selectValue = as.getAttribute('value');
											if (!selectValue || as.disabled) {
												return;
											}
							
											availableValue.push(selectValue);
										}
									);
				
									availableValue = availableValue.filter(function (value, index, array) { 
										return array.indexOf(value) === index;
									});

									let availableSwatch = element.querySelectorAll('.variations .swatch');
									if (availableSwatch.length) {
										availableSwatch.forEach(
											function (awv) {
												let swatchValue = awv.getAttribute('data-value');
								
												if (availableValue.includes(swatchValue)) {
													awv.classList.remove('unavailable');
												} else {
													awv.classList.add('unavailable');
												}
											}
										);
									}
								}
								
							}
						);												
					}
					let checked = false;
					if (select.length) {
						select.forEach(
							function (el) {
								el.addEventListener(
									'change',
									function (e) {
										e.preventDefault();
										
										if (reset) {
											if (this[this.selectedIndex].value != '') {
												this.classList.add('has-value');
												reset.classList.add('opacity');
												checked = true;
											}else{

												swatch.forEach(
													function (swatch_el) {  
														if (swatch_el.classList.contains('selected')) {
															checked = true;
														}
													}
												);
												
												if (!checked) {
													reset.classList.remove('opacity');
												}

												this.classList.remove('has-value');
											}
										}
									}
								);
							}
						);
					}
					if (swatch.length) {
						swatch.forEach(
							function (el) {
								el.addEventListener(
									'click',
									function (e) {
										e.preventDefault();
										if (reset) {
											if (el.classList.contains('selected')) {											
												reset.classList.add('opacity');
											}else{
												let swatch_selected = element.querySelectorAll('.swatch.selected');

												if (swatch_selected.length) {
													reset.classList.add('opacity');	
													checked = true;	
												}else{
	
													select.forEach(
														function (sl) {
															if (sl.classList.contains('has-value')) {
																checked = true;
															}
														}
													);
													
													if (!checked) {
														reset.classList.remove('opacity');
													}
												}
												
											}
										}
									}
								);
							}
						);
					}

				}
			}
		});

		woostifyEvent.productSwatchReady = 1;
	}
);
