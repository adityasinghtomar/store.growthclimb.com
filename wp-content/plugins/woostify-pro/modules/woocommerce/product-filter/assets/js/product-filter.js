/**
 * Elementor product filter
 *
 * @package Woostify Pro
 */

/* global woostify_product_filter, woostify_datepicker_data */
/* global woostifyEvent -> stop_ajax */
'use strict';

if( typeof woostifyEvent == undefined ) {
	woostifyEvent = {};
}
woostifyEvent.ajax_call = 0;
let listFilter = {};
// Date picker.
const woostifyFilterDatePicker = function() {
	let dateRangeFilter = document.querySelector( '.w-product-filter[data-type=date-range] .w-product-filter-inner' );
	if ( ! dateRangeFilter ) {
		return;
	}

	let options = {
		mode: 'dp-below'
	}

	if ( 'undefined' !== typeof( woostify_datepicker_data ) ) {
		options.lang        = {};
		options.lang.months = woostify_datepicker_data.months;
		options.lang.days   = woostify_datepicker_data.days;
		options.lang.today  = woostify_datepicker_data.today;
		options.lang.clear  = woostify_datepicker_data.clear;
		options.lang.close  = woostify_datepicker_data.close;
	}

	// Setup datepicker.
	let field = dateRangeFilter.querySelectorAll( '.w-filter-date-picker' );
	for ( let i = 0, j = field.length; i < j; i++ ) {
		let datePicker;
		if ( 'object' === datePicker ) {
			return;
		}

		datePicker = TinyDatePicker( field[i], options );
	}
}

// Toggle filter widget horizontal layout.
const woostifyToggleFilterHorizontal = function() {
	let item        = document.querySelectorAll( '.filter-area.filter-horizontal .w-product-filter .widget-title, .w-pro-smart-filter-layout-horizontal .elementor-widget-container .widget-title' );
	let filter_item = document.querySelectorAll( '.filter-area.filter-horizontal .w-product-filter, .w-pro-smart-filter-layout-horizontal .elementor-widget-container .w-product-filter' );

	if ( ! item.length ) {
		return;
	}

	item.forEach(
		function( el ) {
			el.onclick = function() {
				let curr_filter = el.parentNode;

				if ( curr_filter.classList.contains( 'open' ) ) {
					curr_filter.classList.remove( 'open' );
				} else {
					filter_item.forEach(
						function( f_el ) {
							f_el.classList.remove( 'open' );
						}
					);
					curr_filter.classList.add( 'open' );
				}
			}
		}
	);
}

// Toggle filter widget.
const woostifyToggleFilter = function() {
	let item = document.querySelectorAll( '.w-product-filter:not(.no-collapse) .widget-title' );

	if ( ! item.length ) {
		return;
	}

	item.forEach(
		function( el ) {
			let filter          = el.closest( '.w-product-filter' ),
				title           = el,
				filter_content  = filter.querySelector( '.w-product-filter-inner' ),
				speed           = 300,
				filter_collapse = filter.getAttribute( 'data-collapse' );

			// Set transition-duration.
			filter.style.transitionDuration = speed + 'ms';

			// Set initial height to transition from.
			if ( null === filter_collapse ) {
				filter.style.height = title.getBoundingClientRect().height + filter_content.getBoundingClientRect().height + 'px';
			} else {
				filter.style.height = title.getBoundingClientRect().height + 'px';
			}

			// Setup click handler.
			el.onclick = function() {
				let curr_filter   = el.parentNode,
					curr_content  = curr_filter.querySelector( '.w-product-filter-inner' ),
					curr_collapse = curr_filter.getAttribute( 'data-collapse' );
				if ( null === curr_collapse ) {
					// Close.
					// Update class.
					curr_filter.classList.remove( "is-opening" );
					curr_filter.classList.add( "is-closing" );
					let title_height = el.getBoundingClientRect().height;

					// Set the height so only the toggle is visible.
					curr_filter.style.height = title_height + "px";

					setTimeout(
						function() {
							if (curr_filter.classList.contains( "is-closing" )) {
								curr_filter.setAttribute( 'data-collapse', '' );
							}
							curr_filter.classList.remove( "is-closing" );
						},
						speed
					);
				} else {
					// Open.
					// Update class.
					curr_filter.classList.remove( "is-closing" );
					curr_filter.classList.add( "is-opening" );

					// Get height of toggle.
					let title_height = el.getBoundingClientRect().height;

					// Momentarily show the contents just to get the height.
					curr_filter.setAttribute( "data-collapse", '' );
					let content_height = curr_content.getBoundingClientRect().height;
					curr_filter.removeAttribute( 'data-collapse' );

					// Set the correct height and let CSS transition it.
					curr_filter.style.height = title_height + content_height + "px";

					setTimeout(
						function() {
							curr_filter.classList.remove( "is-opening" );
						},
						speed
					);
				}
			}
		}
	);
}

// Toggle child term.
const woostifyToggleChildTerm = function() {
	let item = document.querySelectorAll( '[data-type="checkbox"] .w-filter-item-depth' );
	if ( ! item.length ) {
		return;
	}

	// Add expand button.
	item.forEach(
		function( el ) {
			let parent = el.previousElementSibling;
			if ( ! parent ) {
				return;
			}

			let isVisible  = el.classList.contains( 'visible' ),
				icon       = isVisible ? woostify_product_filter.collapse_icon : woostify_product_filter.expand_icon,
				expandHtml = '<span class="expand-btn">' + icon + '</span>';

			parent.insertAdjacentHTML( 'beforeend', expandHtml );
		}
	);

	// Event click.
	let expandBtn = document.querySelectorAll( '[data-type="checkbox"] .expand-btn' );
	if ( expandBtn.length ) {
		expandBtn.forEach(
			function( btn ) {
				let filter_item_wrap = btn.closest( '.w-filter-item-wrap' );
				if ( ! filter_item_wrap ) {
					return;
				}
				filter_item_wrap.classList.add( 'is-parent-item' );

				btn.onclick = function() {
					btn.innerHTML = woostify_product_filter.expand_icon === btn.innerHTML ? woostify_product_filter.collapse_icon : woostify_product_filter.expand_icon;

					let parentBtn = btn.closest( '.w-filter-item-wrap' );
					if ( ! parentBtn ) {
						return;
					}
					let nextDepth = parentBtn.nextElementSibling;
					if ( ! nextDepth || ! nextDepth.classList.contains( 'w-filter-item-depth' ) ) {
						return;
					}

					if ( nextDepth.classList.contains( 'visible' ) ) {
						nextDepth.classList.remove( 'visible' );
					} else {
						nextDepth.classList.add( 'visible' );
					}

					let filter          = btn.closest( '.w-product-filter ' );
					filter.style.height = 'auto';
					let filter_height   = filter.getBoundingClientRect().height;
					filter.style.height = filter_height + 'px';
				}
			}
		);
	}
}

// Toggle soft limit.
const woostifyToggleSoftLimit = function() {
	let buttons = document.querySelectorAll( '.w-product-filter .w-filter-toggle-btn' );
	if ( ! buttons.length ) {
		return;
	}

	buttons.forEach(
		function( btn ) {
			btn.onclick = function() {
				let overflow = btn.parentNode.querySelector( '.w-filter-item-overflow' );
				if ( ! overflow ) {
					return;
				}

				// Sibling this button.
				let siblingBtn = btn.parentNode.querySelector( '.w-filter-toggle-btn.w-filter-hidden' );
				if ( siblingBtn ) {
					siblingBtn.classList.remove( 'w-filter-hidden' );
				}

				// Set class for this button.
				btn.classList.add( 'w-filter-hidden' );

				// Set class for overflow div.
				if ( overflow.classList.contains( 'w-filter-hidden' ) ) {
					overflow.classList.remove( 'w-filter-hidden' );
				} else {
					overflow.classList.add( 'w-filter-hidden' );
				}

				let filter          = btn.closest( '.w-product-filter ' );
				filter.style.height = 'auto';
				let filter_height   = filter.getBoundingClientRect().height;
				filter.style.height = filter_height + 'px';
			}
		}
	);
}

// Update data filter.
const woostifyUpdateFilters = function( obj ) {
	for ( let key in obj ) {
		if ( Array.isArray( obj[key] ) && ! obj[key].length ) {
			delete obj[key];
		}
	}

	if ( ! Object.keys( obj ).length ) {
		return {};
	}

	if ( 1 === Object.keys( obj ).length ) {
		if ( 'undefined' === typeof( obj['first_active_filter'] ) ) {
			obj['first_active_filter'] = Object.keys( obj )[0];
		} else {
			delete obj['first_active_filter'];
		}
	} else {
		let firstActiveFilter = obj['first_active_filter'];
		if ( 'undefined' !== typeof( firstActiveFilter ) && 'undefined' === typeof( obj[firstActiveFilter] ) ) {
			delete obj['first_active_filter'];
		}
	}

	return obj;
}

// Build query string.
const woostifyBuildQueryString = function( obj ) {
	let source = woostify_product_filter.filters_url,
		keys   = Object.keys( obj ),
		output = {};

	for ( let i = 0, j = keys.length; i < j; i++ ) {
		if ( 'undefined' !== typeof( source[ keys[i] ] ) ) {
			output[ source[ keys[i] ] ] = obj[ keys[i] ];
		}
	}

	return output;
}

// Quick search.
const woostifyFilterQuickSearch = function() {
	let search = document.querySelectorAll( '.w-filter-quick-search' );
	if ( ! search.length ) {
		return;
	}

	search.forEach(
		function( el ) {
			let items = el.parentNode.querySelectorAll( '.w-filter-item-wrap' );

			if ( ! items.length ) {
				return;
			}

			let itemDisplay = function() {
				for ( let i = 0, j = items.length; i < j; i++ ) {
					let name = items[i].querySelector( '.w-filter-item-name' );
					if ( ! name ) {
						continue;
					}

					let filter = el.value.toUpperCase(),
						text   = name.innerText.toUpperCase();
					if ( text.includes( filter ) ) {
						items[i].style.display = '';
					} else {
						items[i].style.display = 'none';
					}
				}
			}

			// For first load.
			itemDisplay();

			// For typing.
			el.addEventListener( 'input', itemDisplay );
		}
	);
}

/**
 * Merge tooltips when overlap
 *
 * @param slider HtmlElement with an initialized slider
 * @param threshold Minimum proximity (in pixels) to merge tooltips
 * @param separator String joining tooltips
*/
const mergeTooltips = function( slider, threshold, separator ) {
	var tooltips = slider.noUiSlider.getTooltips();

	tooltips.forEach(
		function( tooltip ) {
			tooltip.style.transition = 'none';
		}
	)

	slider.noUiSlider.on(
		'update',
		function ( values, handle, unencoded, tap, positions ) {

			var mergeTooltip = slider.querySelector( '.noUi-base .mergeTooltip' );

			if ( ! mergeTooltip ) {
				mergeTooltip = document.createElement( 'div' );
				mergeTooltip.setAttribute( 'class', 'noUi-tooltip mergeTooltip' );
				mergeTooltip.style.visibility = 'hidden';
				slider.querySelector( '.noUi-base' ).appendChild( mergeTooltip );
			}

			var pools         = [[]];
			var poolPositions = [[]];
			var poolValues    = [[]];
			var atPool        = 0;

			// Assign the first tooltip to the first pool, if the tooltip is configured.
			if (tooltips[0]) {
				pools[0][0]         = 0;
				poolPositions[0][0] = positions[0];
				poolValues[0][0]    = values[0];
			}

			for ( var i = 1, posLength = positions.length; i < posLength; i++ ) {

				var rightTooltipRect = tooltips[i].getBoundingClientRect();
				var leftTooltipRect  = tooltips[i - 1].getBoundingClientRect();

				if ( ! tooltips[i] || ( leftTooltipRect.right - rightTooltipRect.left + threshold ) < 0 ) {
					atPool++;
					pools[atPool]         = [];
					poolValues[atPool]    = [];
					poolPositions[atPool] = [];
				}

				if (tooltips[i]) {
					pools[atPool].push( i );
					poolValues[atPool].push( values[i] );
					poolPositions[atPool].push( positions[i] );
				}
			}

			var poolsLength = pools.length;
			if ( 1 >= poolsLength ) {
				pools.forEach(
					function ( pool, poolIndex ) {
						var offset        = ( poolPositions[poolIndex][0] + poolPositions[poolIndex][1] ) / 2;
						var tooltipValues = poolValues[poolIndex].filter( (v, i, a) => a.indexOf( v ) === i );

						// Center this tooltip over the affected handles.
						mergeTooltip.innerHTML        = tooltipValues.join( separator );
						mergeTooltip.style.visibility = 'visible';
						mergeTooltip.style.transform  = 'translate(-50%, -44%)';
						mergeTooltip.style.left       = offset + '%';
						mergeTooltip.style.transition = 'none';
					}
				);
				tooltips.forEach(
					function( tooltip ) {
						tooltip.style.visibility = 'hidden';
					}
				)
			} else {
				mergeTooltip.style.visibility = 'hidden';
				tooltips.forEach(
					function( tooltip ) {
						tooltip.style.visibility = 'visible';
					}
				)
			}
		}
	);
}
// Filter data.
const filterData = function( dataFilter, event ) {
	let getFilter = document.querySelectorAll( '.w-product-filter[data-type]' ); // Get all dynamic filter.
	if ( ! getFilter.length ) {
		return;
	}

	getFilter.forEach(
		function( fi ) {
			let type      = fi.getAttribute( 'data-type' ),
				filterId  = fi.getAttribute( 'data-id' ),
				tmpUniqId = 'tmp_' + filterId;

			// Create tmp array to store values.
			if ( ! window[tmpUniqId] ) {
				window[tmpUniqId] = 'undefined' === typeof( dataFilter[filterId] ) ? [] : dataFilter[filterId];
			}

			// Get all filter available on listFilter variable.
			listFilter[filterId] = type;

			switch ( type ) {
				case 'search':
					let searchField = fi.querySelector( '.w-product-filter-text-field' ),
						submitIcon  = fi.querySelector( '.w-product-filter-search-icon' );
					if ( ! searchField || ! submitIcon ) {
						return;
					}

					// Submit by enter button.
					searchField.onkeyup = function( e ) {
						if ( 13 !== e.keyCode ) {
							return;
						}

						submitIcon.click();
					}

					// Submit by click button.
					submitIcon.onclick = function() {
						let keyword   = searchField.value.trim(),
							prevValue = searchField.getAttribute( 'data-value' ) || '';
						if ( ! keyword || prevValue == keyword ) {
							return;
						}

						searchField.setAttribute( 'data-value', keyword );

						dataFilter[filterId] = keyword;

						document.body.dispatchEvent( event );
					}
					break;
				case 'date-range':
					let dateFrom   = fi.querySelector( '[data-from]' ),
						dateTo     = fi.querySelector( '[data-to]' ),
						dateSubmit = fi.querySelector( '.w-filter-item-submit' );

					if ( ! dateFrom || ! dateTo || ! dateSubmit ) {
						return;
					}

					dateSubmit.onclick = function() {
						if ( ! dateFrom.value || ! dateTo.value ) {
							return;
						}

						let date = [];

						date[0] = dateFrom.value;
						date[1] = dateTo.value;

						dataFilter[filterId] = date;

						document.body.dispatchEvent( event );
					}

					break;
				case 'rating':
					let star = fi.querySelectorAll( '.w-filter-rating-item' );
					if ( ! star.length ) {
						return;
					}

					for ( let i = 0, j = star.length; i < j; i++ ) {
						star[i].onclick = function() {
							if ( star[i].classList.contains( 'selected' ) ) {
								return;
							}

							// Remove old active.
							let oldStar = fi.querySelector( '.w-filter-rating-item.selected' );
							if ( oldStar ) {
								oldStar.classList.remove( 'selected' );
							}

							star[i].classList.add( 'selected' );

							// Update object.
							dataFilter[filterId] = [5 - i];

							document.body.dispatchEvent( event );
						}
					}
					break;
				case 'sort-order':
					let sortOrderField = fi.querySelector( '.w-product-filter-select-field.w-filter-ordering' );
					if ( ! sortOrderField ) {
						return;
					}

					sortOrderField.onchange = function( e ) {
						e.preventDefault();

						dataFilter[filterId] = sortOrderField.value;

						document.body.dispatchEvent( event );
					}
					break;
				case 'select':
					let selectField = fi.querySelector( '.w-product-filter-select-field' );
					if ( ! selectField ) {
						return;
					}

					selectField.onchange = function() {
						dataFilter[filterId] = selectField.value;

						document.body.dispatchEvent( event );
					}

					break;
				case 'radio':
					let radioField = fi.querySelectorAll( '[type="radio"]' );
					if ( ! radioField.length ) {
						return;
					}

					for ( let i = 0, j = radioField.length; i < j; i++ ) {
						radioField[i].onchange = function() {
							dataFilter[filterId] = radioField[i].parentNode.getAttribute( 'data-slug' );
							document.body.dispatchEvent( event );
						}
					}

					break;
				case 'range-slider':
					if ( ! fi ) {
						break;
					}

					let rangeSliderSetup = fi.querySelector( '.w-filter-range-slider' );
					if (
						! rangeSliderSetup ||
						( rangeSliderSetup && 'object' === typeof( fi.noUiSlider ) ) ||
						rangeSliderSetup.classList.contains( 'noUi-target' )
					) {
						break;
					}

					let rangeReset  = fi.querySelector( '.w-filter-range-slider-reset' ),
						rangeStart  = JSON.parse( rangeSliderSetup.getAttribute( 'data-start' ) ),
						rangeValue  = JSON.parse( rangeSliderSetup.getAttribute( 'data-range' ) ),
						rangeOption = {
							tooltips: true,
							connect: true,
							start: rangeStart,
							step: 1,
							range: rangeValue,
							format: {
								from: function( value ) {
									return Math.round( value );
								},
								to: function( value ) {
									return Math.round( value );
								}
							}
					};

					const slider = noUiSlider.create( rangeSliderSetup, rangeOption );
					slider.on(
						'change',
						function( values, handle, unencoded, tap, positions, noUiSlider ) {
							// Set previous range.
							// dataFilter['range-slider'] = noUiSlider.options.range;

							let button_reset_slider = fi.querySelector( '.w-filter-range-slider-reset' );
							button_reset_slider.style.display = "block";
							woostifyToggleFilter();
							
							dataFilter[filterId] = values

							document.body.dispatchEvent( event );
						}
					);

					if ( rangeReset ) {
						rangeReset.onclick = function() {
							let currentRange = rangeReset.previousElementSibling;
							if ( ! currentRange ) {
								return;
							}

							let rangeUpper = currentRange.querySelector( '.noUi-handle-upper' );
							if ( ! rangeUpper ) {
								return;
							}
							
							let filter_remove = document.querySelector('.w-filter-key-remove[data-id="' + filterId +'"]');
							if (filter_remove) {
								filter_remove.click();
							}
						}
					}

					mergeTooltips( rangeSliderSetup, 2, ' - ' );

					break;
				case 'check-range':
					let checkRangeInput = fi.querySelectorAll( '[type="checkbox"]' );
					if ( ! checkRangeInput.length ) {
						return;
					}

					for ( let i = 0, j = checkRangeInput.length; i < j; i++ ) {
						checkRangeInput[i].onclick = function() {
							let value = checkRangeInput[i].parentNode.getAttribute( 'data-value' );

							// For query filter.
							if ( window[tmpUniqId].includes( value ) ) {
								window[tmpUniqId] = window[tmpUniqId].filter(
									function( item ) {
										return item !== value;
									}
								);
							} else {
								window[tmpUniqId].push( value );
							}

							dataFilter[filterId] = window[tmpUniqId];

							// Trigger.
							document.body.dispatchEvent( event );
						}
					}
					break;
				case 'checkbox':
					let checkList = fi.querySelectorAll( '[type="checkbox"]' );
					if ( ! checkList.length ) {
						return;
					}

					for ( let i = 0, j = checkList.length; i < j; i++ ) {
						checkList[i].onclick = function() {
							let id   = Number( checkList[i].parentNode.getAttribute( 'data-id' ) ),
								slug = checkList[i].parentNode.getAttribute( 'data-slug' );

							if ( window[tmpUniqId].includes( slug ) ) {
								window[tmpUniqId] = window[tmpUniqId].filter(
									function( item ) {
										return item !== slug;
									}
								);
							} else {
								window[tmpUniqId].push( slug );
							}

							dataFilter[filterId] = window[tmpUniqId];

							// Trigger.
							document.body.dispatchEvent( event );
						}
					}
					break;
				case 'stock':
					let stockList = fi.querySelectorAll( '[type="checkbox"]' );

					if ( ! stockList.length ) {
						return;
					}

					for ( let i = 0, j = stockList.length; i < j; i++ ) {
						stockList[i].onclick = function() {
							let id   = Number( stockList[i].parentNode.getAttribute( 'data-id' ) ),
								slug = stockList[i].parentNode.getAttribute( 'data-slug' );

							if ( window[tmpUniqId].includes( slug ) ) {
								window[tmpUniqId] = window[tmpUniqId].filter(
									function( item ) {
										return item !== slug;
									}
								);
							} else {
								window[tmpUniqId].push( slug );
							}

							dataFilter[filterId] = window[tmpUniqId];

							// Trigger.
							document.body.dispatchEvent( event );
						}
					}
					break;
				case 'visual':
					let termId = fi.querySelectorAll( '.w-filter-item' );
					if ( ! termId.length ) {
						return;
					}

					for ( let i = 0, j = termId.length; i < j; i++ ) {
						termId[i].onclick = function() {
							let id = termId[i].getAttribute( 'data-slug' );

							if ( window[tmpUniqId].includes( id ) ) {
								window[tmpUniqId] = window[tmpUniqId].filter(
									function( item ) {
										return item !== id;
									}
								);
								termId[i].classList.remove( 'selected' );
							} else {
								window[tmpUniqId].push( id );
								termId[i].classList.add( 'selected' );
							}

							dataFilter[filterId] = window[tmpUniqId];

							// Trigger.
							document.body.dispatchEvent( event );
						}
					}
					break;
			}
		}
	);
}
// Filter.
const woostifyAjaxFilter = function() {
	let filter  = document.querySelectorAll( '.w-product-filter[data-type]' ),
		content = document.querySelector( '.w-result-filter' );

	if ( ! filter.length || ! content ) {
		return;
	}

	let filterKey      = document.querySelector( '.w-filter-key' ),
		products       = content.querySelector( '.products' ),
		adminBar       = document.getElementById( 'wpadminbar' ),
		adminBarHeight = adminBar ? adminBar.offsetHeight : 0,
		dataFilter     = Array.isArray( woostify_product_filter.active_params ) ? {} : woostify_product_filter.active_params,
		pagedVar       = 1,
		event          = new CustomEvent( 'filtered', { detail: true } );
	listFilter     = {};
	// Set current remome key.
	if ( filterKey && woostify_product_filter.remove_key ) {
		filterKey.innerHTML = woostify_product_filter.remove_key;
	}

	if (!content.querySelector('.woocommerce-pagination') && products) {
		let woocommerce_pagination = document.createElement('div');
		woocommerce_pagination.setAttribute('class','woocommerce-pagination');
		products.after(woocommerce_pagination);
	}

	// Pagination.
	const productPagination = function() {
		let pagiList = content.querySelectorAll( '.woocommerce-pagination .page-numbers a.page-numbers' );
		if ( ! pagiList.length ) {
			return;
		}

		for ( let p = 0, g = pagiList.length; p < g; p++ ) {
			pagiList[p].onclick = function( e ) {
				e.preventDefault();

				let currentItem = content.querySelector( '.woocommerce-pagination .page-numbers .page-numbers.current' ),
					prevItem    = pagiList[p].classList.contains( 'prev' ),
					nextItem    = pagiList[p].classList.contains( 'next' ),
					paged       = 1;

				if ( prevItem && currentItem ) {
					paged = Number( currentItem.innerText ) - 1;
				}

				if ( nextItem && currentItem ) {
					paged = Number( currentItem.innerText ) + 1;
				}

				if ( ! prevItem && ! nextItem ) {
					paged = Number( pagiList[p].innerText );
				}

				pagedVar = paged;
				document.body.dispatchEvent( new CustomEvent( 'filtered', { detail: false } ) );
			}
		}
	}
	productPagination();

	filterData( dataFilter, event );

	// Clear filter data.
	const clearFilterData = function() {
		let fedKey = filterKey ? filterKey.querySelectorAll( '.w-filter-key-remove' ) : [];
		if ( ! fedKey.length ) {
			return;
		}

		for ( let f = 0, s = fedKey.length; f < s; f++ ) {
			fedKey[f].onclick = function() {
				let filteredId    = fedKey[f].getAttribute( 'data-id' ),
					filteredType  = fedKey[f].getAttribute( 'data-type' ),
					filteredValue = fedKey[f].getAttribute( 'data-value' );

				if ( ! filteredType ) {
					return;
				}
	
				switch ( filteredType ) {
					case 'all':
						// Reset global 'dataFilter' data.
						let props = Object.getOwnPropertyNames( dataFilter );
						for ( let i = 0, j = props.length; i < j; i++ ) {
							delete dataFilter[ props[ i ] ];
						}

						let queryStr      = woostifyBuildQueryString( dataFilter ),
							urlString     = Object.fromEntries( new URLSearchParams( location.search ) );

						// Remove query from urlString
						for( const k in queryStr){
							if( ! (queryStr[k].length) && ( k in urlString ) ){
								delete( urlString[k] );
								delete( queryStr[k] );
							}
						}
						// END Remove query from urlString

						let	finalQuery    = {...urlString, ...queryStr },
							finalQueryStr = new URLSearchParams( finalQuery ).toString();

						if ( ! Object.keys( dataFilter ).length ) {
							finalQueryStr = '';
						}

						if ( history.pushState ) {
							let lang = typeof finalQuery['lang'] !== 'undefined' ? '?lang=' + finalQuery['lang'] : '';
							history.pushState( null, null, finalQueryStr ? '?' + finalQueryStr : (window.location.pathname + lang));
						}
						// Reset all tmp data.
						let clearTml = fedKey[f].parentNode.querySelectorAll( '[data-id]' );
						if ( clearTml.length ) {
							clearTml.forEach(
								function( tmp ) {
									let tmpId = tmp.getAttribute( 'data-id' );
									if ( window[ 'tmp_' + tmpId ] ) {
										window[ 'tmp_' + tmpId ] = [];
									}
								}
							);
						}

						// Reset html state.
						resetVisual();
						resetSelectField();
						resetSearchField();
						resetRangeSlider();
						resetDatePicker();
						resetRating();
						resetGeneralField();
						resetOdering();
						break;
					case 'sort-order':
						let filterSort = jQuery( '.w-product-filter[data-id="' + filteredId + '"] .w-filter-ordering' );
						if ( ! filterSort.length ) {
							return;
						}
						for ( let k = 0; k < filterSort.length; k++) {
							filterSort[k].selectedIndex = 0;
						}
						delete dataFilter[ filteredId ];
						break;
					case 'search':
						resetSearchField();
						delete dataFilter[ filteredId ];
						break;
					case 'date-range':
						resetDatePicker();
						delete dataFilter[ filteredId ];
						break;
					case 'check-range':
						if ( ! filteredValue ) {
							return;
						}

						let checkRangeFiltered = window[ 'tmp_' + filteredId ];

						if ( 'undefined' !== typeof( checkRangeFiltered ) ) {
							// Remove current check range data.
							let checkRangeValue = checkRangeFiltered.filter(
								function( checkr ) {
									return checkr !== filteredValue;
								}
							);

							window[ 'tmp_' + filteredId ] = checkRangeValue;
							dataFilter[ filteredId ]      = checkRangeValue;

							// Remove checked status on checkbox input.
							let checkedRangeInput = document.querySelector( '.w-product-filter[data-id="' + filteredId + '"] [data-value="' + filteredValue + '"] [type="checkbox"]:checked' );
							if ( checkedRangeInput ) {
								checkedRangeInput.checked = false;
							}
						}
						break;
					case 'range-slider':
						resetRangeSlider();
						delete dataFilter[ filteredId ];
						break;
					case 'rating':
						resetRating();
						delete dataFilter[ filteredId ];
						break;
					case 'select':
						let selectedField = jQuery( '.w-product-filter[data-id="' + filteredId + '"] .w-product-filter-select-field' );
						if ( ! selectedField.length ) {
							return;
						}
						for ( let k = 0; k < selectedField.length; k++) {
							selectedField[k].selectedIndex = 0;
						}
						delete dataFilter[ filteredId ];
					case 'radio':
						let selectedRadio = document.querySelector( '.w-product-filter[data-id="' + filteredId + '"] [data-id="' + filteredValue + '"] [type="radio"]:checked' );
						if ( selectedRadio ) {
							selectedRadio.checked = false;
						}

						delete dataFilter[ filteredId ];
						break;
					case 'checkbox':
					case 'stock':
					case 'visual':
						if ( ! filteredValue ) {
							return;
						}

						let checkedBox = window[ 'tmp_' + filteredId ];

						if ( 'undefined' !== typeof( checkedBox ) ) {
							// Remove current check range data.
							let checkboxValue = checkedBox.filter(
								function( checkr ) {
									return checkr !== filteredValue;
								}
							);

							window[ 'tmp_' + filteredId ] = checkboxValue;
							dataFilter[ filteredId ]      = checkboxValue;

							// Remove checked status on checkbox input.
							if (filteredType == 'visual') {
								let checkedBoxInput = document.querySelector( '.w-product-filter[data-id="' + filteredId + '"] [data-slug="' + filteredValue + '"].selected' );
								if ( checkedBoxInput ) {
									checkedBoxInput.classList.remove( 'selected' );
								}
							} else {
								let checkedBoxInput = document.querySelector( '.w-product-filter[data-id="' + filteredId + '"] [data-slug="' + filteredValue + '"] [type="checkbox"]:checked' );
								if ( checkedBoxInput ) {
									checkedBoxInput.checked = false;
								}
							}
						}
						break;
				}
				let queryStr      = woostifyBuildQueryString( woostifyUpdateFilters( dataFilter ) );
				let	urlString     = Object.fromEntries( new URLSearchParams( location.search ) );
				let entries = Object.entries(urlString);
				function arrayRemove(arr, value) { 
					return arr.filter(function(ele){ 
						return ele[1] != value; 
					});
				}
				let result = arrayRemove(entries, filteredValue);
				let obj = {};
				result.forEach(function(element, index){
					obj[element[0]] = element[1];
				});
				let	finalQuery    = {...obj, ...queryStr };
				let	finalQueryStr = new URLSearchParams( finalQuery ).toString();
		
				if ( ! Object.keys( dataFilter ).length ) {
					finalQueryStr = '';
				}
		
				if ( history.pushState ) {
					let lang = typeof finalQuery['lang'] !== 'undefined' ? '?lang=' + finalQuery['lang'] : '';
					history.pushState( null, null, finalQueryStr ? '?' + finalQueryStr : (window.location.pathname + lang) );
				}
				document.body.dispatchEvent( event );
			}
		}
		
	}
	clearFilterData();

	// Reset select field.
	const resetSelectField = function() {
		let element = document.querySelectorAll( '.w-product-filter-select-field' );
		if ( ! element.length ) {
			return;
		}

		element.forEach(
			function( el ) {
				el.value = '';
			}
		);
	}

	// Reset search field.
	const resetSearchField = function() {
		let element = document.querySelectorAll( '.w-product-filter-text-field' );
		if ( ! element.length ) {
			return;
		}

		element.forEach(
			function( el ) {
				el.value = '';
			}
		);
	}

	// Reset range slider.
	const resetRangeSlider = function() {
		let element = document.querySelectorAll( '.w-product-filter-type-range-slider' );
		if ( ! element.length ) {
			return;
		}

		element.forEach(
			function( el ) {
				if ( 'object' !== typeof( el.noUiSlider ) ) {
					return;
				}

				el.noUiSlider.set( el.noUiSlider.options.start );
			}
		);
	}

	// Reset date picker.
	const resetDatePicker = function() {
		let datePickerField = document.querySelectorAll( '.w-filter-date-picker' );
		if ( ! datePickerField.length ) {
			return;
		}

		datePickerField.forEach(
			function( el ) {
				el.value = '';
			}
		);
	}

	// Reset rating.
	const resetRating = function() {
		let element = document.querySelector( '.w-product-filter-type-rating .selected' );
		if ( ! element ) {
			return;
		}

		element.classList.remove( 'selected' );
	}

	// Reset input field.
	const resetGeneralField = function() {
		let element = document.querySelectorAll( '.w-product-filter-inner [type="checkbox"]:checked, .w-product-filter-inner [type="radio"]:checked' );
		if ( ! element.length ) {
			return;
		}

		element.forEach(
			function( el ) {
				el.checked = false;
			}
		);
	}

	// Reset visual.
	const resetVisual = function() {
		let visualSelected = document.querySelectorAll( '.w-filter-item.selected' );
		if ( ! visualSelected.length ) {
			return;
		}

		visualSelected.forEach(
			function( vs ) {
				vs.classList.remove( 'selected' );
			}
		);
	}

	// Reset odering.
	const resetOdering = function() {
		let filterOdering = document.querySelectorAll( '.w-filter-ordering' );
		if ( ! filterOdering.length ) {
			return;
		}
		filterOdering.forEach(
			function( item ) {
				item.selectedIndex = 0;
			}
		);
	}

	document.body.addEventListener(
		'filtered',
		function( e ) {
			if (typeof window.infScroll !== 'undefined') {
				infinite_filter();
			} else {
				ajax_filter(e);
			}
		}
	);
	
	function ajax_filter(e) {
		// Scroll to top content.
		let bodyOffsetTop    = document.body.getBoundingClientRect().top,
		contentOffsetTop = content.getBoundingClientRect().top,
		scrToTop         = ( -1 * bodyOffsetTop ) - ( -1 * contentOffsetTop ) - adminBarHeight;

		// Scroll to top with animation.
		if ( contentOffsetTop < adminBarHeight ) {
			window.scrollTo( { top: scrToTop, behavior: 'smooth' } );
		}

		// Add loading animation.
		let products = document.querySelector( '.site-content ul.products' );
		if ( products && ! products.querySelector( '.filter-loading' ) ) {
			products.insertAdjacentHTML( 'beforeend', '<span class="filter-loading"></span>' );
		}

		// Reset pagination number.
		if ( e.detail ) {
			pagedVar = 1;
		}

		// Add animation.
		document.body.classList.add( 'filter-updating' );
		let queryStr      = woostifyBuildQueryString( dataFilter ),
			urlString     = Object.fromEntries( new URLSearchParams( location.search ) );
		
		// Remove query from urlString
		for( const k in queryStr){
			if( ! (queryStr[k].length) && ( k in urlString ) ){
				delete( urlString[k] );
				delete( queryStr[k] );
			}
		}
		// END Remove query from urlString

		let	finalQuery    = {...urlString, ...queryStr },
			finalQueryStr = new URLSearchParams( finalQuery ).toString();



		if ( history.pushState ) {
			let lang = typeof finalQuery['lang'] !== 'undefined' ? '?lang=' + finalQuery['lang'] : '';
			history.pushState( null, null, finalQueryStr ? '?' + finalQueryStr : (window.location.pathname + lang));
		}

		let wc_orderby = document.querySelector('.orderby');
		let wc_orderby_value = ( wc_orderby.length != 0 ) ? wc_orderby.value : '';

		// Args.
		let orderby = urlString.orderby || content.getAttribute( 'data-posts-orderby' );
		orderby = ( wc_orderby_value != '' ) ? wc_orderby_value : orderby;
		let args = {
			action: 'woostify_product_filter',
			ajax_nonce: woostify_product_filter.ajax_nonce,
			per_page: content.getAttribute( 'data-posts' ) || '',
			paged: pagedVar,
			term_id: woostify_product_filter.term_id,
			taxonomy: woostify_product_filter.taxonomy,
			list_filter: JSON.stringify( listFilter ),
			search_param: urlString.s || '',
			data: JSON.stringify( woostifyUpdateFilters( dataFilter ) ),
			orderby: orderby || 'menu_order title',
			order: content.getAttribute( 'data-posts-order' ) || '',
			exclude_products: content.getAttribute( 'data-exclude-products' ) || '',
		};
		
		args = new URLSearchParams( args ).toString();
		// Request.
		let request = new Request(
			woostify_product_filter.ajax_url + location.search,
			{
				method: 'POST',
				body: args,
				credentials: 'same-origin',
				headers: new Headers(
					{
						'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
					}
				)
			}
		);
		
		woostifyEvent.ajax_call = 1;
		// Fetch API.
		fetch( request )
			.then(
				function( res ) {
					if ( 200 !== res.status ) {
						console.log( 'Status Code: ' + res.status );
						throw res;
					}

					return res.json();
				}
			).then(
				function( json ) {
					if ( ! json.success ) {
						return;
					}
		
					let r           = json.data,
						resultCount = content.querySelector( '.woostify-sorting .woocommerce-result-count' ),
						products    = content.querySelector( '.products' ),
						pagination  = content.querySelector( '.woocommerce-pagination' ),
						sidebar     = document.querySelector( '.shop-widget' ),
						overlay     = jQuery( '#woostify-overlay' ),
						html        = jQuery( 'html' ),
						orderingForm = jQuery('form.woocommerce-ordering');
					
					let orderby = r.query.orderby;
					let order = r.query.order;

					// Ordering
					if ( orderingForm.length && r.ordering ) {
						var ordering = jQuery(r.ordering);
						orderingForm.find(':not(.orderby)').remove();
						ordering.find('option').appendTo(orderingForm.find('select') );
						ordering.find('input').appendTo(orderingForm);
						orderingForm.find('select').val( orderingForm.find('select [selected=selected]').val());
					}

					// Result count.
					if ( resultCount && r.result_count ) {
						resultCount.innerHTML = r.result_count;
					}
		
					// Products.
					if ( products ) {
						products.innerHTML = r.content;
					}
					
					// Pagination.
					let urlRegex = /href="([^\'\"]+)/g;
					if ( pagination ) {

						let has_elementor_builder = '';
						if ( window.elementorFrontend || document.querySelector('body').classList.contains('woobuilder-active')) {
							has_elementor_builder = true;
						}

						if ( r.pagination  ) {
							let pagination_markup = r.pagination;
							let path_name = window.location.pathname;
					
							pagination_markup = pagination_markup.replace(urlRegex, function (url) {
								let params = url.split('?');
								let p_path = '';
								var check_url = params[0].match(/\page\/\d+/);
								
								if( check_url ){
									let check_path_name = path_name.match(/\/page\/\d+/);
									if( check_path_name ){
										p_path = path_name.replace(check_path_name[0], check_url[0]) + '?';
									} else {
										p_path = path_name + check_url[0] + '?';
									}
								}
								
								for (let index = 1; index < params.length; index++) {
									p_path += params[index].replace("paged","product-page");
								
									if ( index < (params.length - 1)) {
										p_path += '&';
									}
								}
								
								p_path = p_path.replace("&#038;","&");
								
								if ( !p_path.includes('orderby') && orderby != 'null' ) {

									p_path += '&orderby='+orderby.toLowerCase();
								}
								if (!p_path.includes('order')) {
									p_path += '&order='+order.toLowerCase();
								}
								
								if( p_path.includes('product-page=') ){
									if( ! check_url ) {
										let p_path_split = p_path.split("product-page=");
										let page = p_path_split[1].charAt(0);

										let match = path_name.match(/(\w{0,})\/{1,}$/);
										if( match ){
											// Loai bo day slash cuoi cung
											path_name = path_name.replace( match[0], match[1] );
										}
										let new_url = path_name + '/page/' + page + '/?' + p_path;
									}
									
									if (new_url.includes('&product-page=')) {
										p_path = new_url.replace("&product-page="+page,"");
									}else{
										p_path = new_url.replace("product-page="+page,"");
									}
								}
					
								return url.replace(url,'href="'+p_path);
							});

							// pagination.innerHTML = r.pagination;
							pagination.innerHTML = pagination_markup;
						} else {
							pagination.innerHTML = '';
						}
					}

					// Sidebar.
					// if ( sidebar && ( r.woostify_sidebar||0 ) ) {
					// 	sidebar.outerHTML = r.woostify_sidebar;
					// }

					// Filtered key.
					if ( filterKey ) {
						filterKey.innerHTML = r.filtered;
					}

					// Rnder template.
					if ( r.template && woostify_product_filter.enable_hierarchy == 1) {
						for ( let i in r.template ) {
							let item       = r.template[i],
								filterId   = item.id,
								filterType = item.type;
		
							let dom    = new DOMParser(),
								doc    = dom.parseFromString( item.template, 'text/html' ),
								filter = doc.querySelector( '.w-product-filter' );

							let selector = jQuery( '.w-product-filter[data-type="' + filterType + '"][data-id="' + filterId + '"]' );
							if ( item.template ) {
								if ( selector && filter ) {
									selector.html( filter.innerHTML );
									filterData( dataFilter, event );
								}
							} else {
								selector.innerHTML = '';
							}
						}
					}

					// Re-init toggle filter.
					woostifyToggleFilter();

					// Re-init toggle filter.
					woostifyToggleFilterHorizontal();

					// Re-init toggle term.
					woostifyToggleChildTerm();

					// Re-init toggle soft limit.
					woostifyToggleSoftLimit();

					// Re-init quick search.
					woostifyFilterQuickSearch();
					// sidebar && sidebar.classList.remove('active');
					// overlay.removeClass( 'active' );
					// html.removeClass( 'sidebar-mobile-open' );

					deactive_mobile_sidebar();

					woostify_swatch_list();

					var path      = window.location.href;

					if (path.includes('?')) {
						let url = new URL(path);
						if ( !url.searchParams.has('orderby') && orderby != 'null' ) {
							path += '&orderby='+orderby.toLowerCase();	
						}
						if ( !url.searchParams.has('order') ) {
							path += '&order='+order.toLowerCase();	
						}

						history.pushState(null, '', path);
					}
					woostifyEvent.ajax_call = 0;


				}
			).catch(
				function( err ) {
					console.log( err );
					woostifyEvent.ajax_call = 0;
				}
			).finally(
				function() {
					// Remove animation.
					document.body.classList.remove( 'filter-updating' );

					// Remove filter key.
					clearFilterData();

					// Re-init pagination.
					productPagination();

					// Infinite scroll.
					if ( 'function' === typeof( woostifyInfiniteScroll ) ) {

						if ( typeof window.infScroll !== 'undefined' ) {
							window.infScroll.destroy();
							
							woostifyInfiniteScroll( true, false );	

						}else{
							woostifyInfiniteScroll( true, false );	

						}
					}

					// Re-init quick-view.
					if ( 'function' === typeof( woostifyQuickView ) ) {
						woostifyQuickView();
					}

					// Re-init date picker.
					woostifyFilterDatePicker();
					woostifyEvent.ajax_call = 0;
				}
			);
	}

	function infinite_filter() {
		let queryStr      = woostifyBuildQueryString( dataFilter ),
			urlString     = Object.fromEntries( new URLSearchParams( location.search ) );
		
		// Remove query from urlString
		for( const k in queryStr){
			if( ! (queryStr[k].length) && ( k in urlString ) ){
				delete( urlString[k] );
				delete( queryStr[k] );
			}
		}
		// END Remove query from urlString

		let	finalQuery    = {...urlString, ...queryStr },
			finalQueryStr = new URLSearchParams( finalQuery ).toString();

		if ( history.pushState ) {
			history.pushState( null, null, finalQueryStr ? '?' + finalQueryStr : window.location.pathname );
		}

		var path = removePageInUrl(window.location.href);

		if ( typeof infScroll === 'undefined' ) {
			woostifyInfiniteScroll( true, false );
		}
		var elementHeight = infScroll.element.getBoundingClientRect().height;
	
		// Add loading animation.
		let products = document.querySelector( '.site-content ul.products' );
		if ( products && ! products.querySelector( '.filter-loading' ) ) {
			products.insertAdjacentHTML( 'beforeend', '<span class="filter-loading"></span>' );
		}
		// Add animation.
		if (document.body.classList.contains('filter-updating')) {
			return;
		}
		document.body.classList.add( 'filter-updating' );
		
		let { responseBody, domParseResponse, fetchOptions } = infScroll.options;
		if ( typeof fetchOptions == 'function' ) {
			fetchOptions = fetchOptions();
		}
		let domParser = new DOMParser();
		history.pushState(null, '', path);
		fetch( path, fetchOptions )
			.then( ( response ) => {
				if ( !response.ok ) {
					let error = new Error( response.statusText );
					infScroll.onPageError( error, path, response );
					return { response };
				}

				return response[ responseBody ]().then( ( body ) => {
					
					let canDomParse = responseBody == 'text' && domParseResponse;
					if ( canDomParse ) {
						body = domParser.parseFromString( body, 'text/html' );
					}
					var content_shop =  body.getElementById('view').innerHTML;
					jQuery('#view').empty().append(content_shop);
					
					var history = {
						top: 0,
						path: path,
						title: response.title
					};

					infScroll.scrollPages.unshift(history);
					for (var i = 1; i < infScroll.scrollPages.length; i++) {
						infScroll.scrollPages[i].top = infScroll.scrollPages[i].top + elementHeight;
					}
					// Infinite scroll.
					if ( 'function' === typeof( woostifyInfiniteScroll ) ) {
						if ( typeof window.infScroll !== 'undefined' ) {
							window.infScroll.destroy();
							woostifyInfiniteScroll( true, false );	
						}else{
							woostifyInfiniteScroll( true, false );	
	
						}
					}
					// Clear filter data
					filterKey = document.querySelector( '.w-filter-key' );
					clearFilterData();

					// Re-init pagination.
					productPagination();

					// Re-init quick-view.
					if ( 'function' === typeof( woostifyQuickView ) ) {
						woostifyQuickView();
					}

					// Re-init date picker.
					woostifyFilterDatePicker();

					// Re-init smart filter
					filterData( dataFilter, event );
					
					// Re-init toggle filter.
					woostifyToggleFilter();

					// Re-init toggle filter.
					woostifyToggleFilterHorizontal();

					// Re-init toggle term.
					woostifyToggleChildTerm();

					// Re-init toggle soft limit.
					woostifyToggleSoftLimit();

					// Re-init quick search.
					woostifyFilterQuickSearch();

					woostifySidebarMobile();

					deactive_mobile_sidebar();

					woostify_swatch_list();

					// Re-init ordering
					select_ordering_top();
					
					// Remove animation.
					document.body.classList.remove( 'filter-updating' );

				} );
			} )
			.catch( ( error ) => {
				console.log(error);
			} 
		);
	}
}
const resetPagedUrl = function (finalQueryStr) {
	var url = jQuery('.page-numbers');
	if (url.length > 0 && finalQueryStr !== '') {
		jQuery('.page-numbers').each( function(index, value) {
			var page_href = jQuery(value).attr('href');
			if (typeof page_href !== 'undefined') {
				var params = page_href.split('?');
				jQuery(value).attr("href", params[0] + '?' + finalQueryStr);
			}
		} )
	}
}
const select_ordering_top = function() {
	if (document.querySelector('select[name="orderby"]')) {
		document.querySelector('select[name="orderby"]').addEventListener('change', function() {
			document.querySelector('form.woocommerce-ordering').submit();
		});
	}
}
const deactive_mobile_sidebar = function () {
	var sidebar = document.querySelector( '#secondary.shop-widget' ),
		button  = document.querySelector( '#toggle-sidebar-mobile-button' ),
		overlay = document.getElementById( 'woostify-overlay' ),
		sidebar_widget = document.getElementById( 'sidebar-widgets' ),
		html    = document.documentElement;
	
	// Remove tooget button when using toongle of elementor widget
	if (button && !document.querySelector( '#toggle-sidebar-button' )) {
		button.classList.add( 'show' );
		button.classList.remove( 'active' );
	}
	if ( overlay ) {
		if (sidebar) {
			sidebar.classList.remove( 'active' );
		}
		if (sidebar_widget) {
			sidebar_widget.classList.remove( 'show' );
		}
		overlay.classList.remove( 'active' );
		html.classList.remove( 'sidebar-mobile-open' );
		document.body.classList.remove( 'sidebar-mobile-open' );
	}
}


// Swatch list.
var woostify_swatch_list = function () {
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

// Force use filter ordering.
const woostifyFilterOrdering = function() {
	let wcForm      = document.querySelector( 'form.woocommerce-ordering' ),
		wcOrder     = wcForm ? wcForm.querySelector( 'select.orderby' ) : false,
		filterOrder = document.querySelector( 'select.w-filter-ordering' );

	if ( ! wcOrder || ! filterOrder ) {
		return;
	}

	// Create custom event.
	let event = new CustomEvent( 'change' );

	wcOrder.addEventListener(
		'change',
		function( e ) {
			e.preventDefault();

			// Set current value.
			filterOrder.value = wcOrder.value;

			// Trigger.
			filterOrder.dispatchEvent( event );
		}
	);

	// Disabled WC ordering. WC using jQuery.
	jQuery( '.woocommerce-ordering' ).on(
		'submit',
		function( e ) {
			e.preventDefault();
		}
	);
}

document.addEventListener(
	'DOMContentLoaded',
	function() {
		// Frontend.
		woostifyAjaxFilter();
		woostifyFilterDatePicker();
		woostifyToggleFilter();
		woostifyToggleFilterHorizontal();
		woostifyToggleChildTerm();
		woostifyToggleSoftLimit();
		woostifyFilterQuickSearch();
		woostifyFilterOrdering();
		window.addEventListener(
			'click',
			function( e ) {
				var filters = document.querySelectorAll( '.filter-area.filter-horizontal .w-product-filter, .w-pro-smart-filter-layout-horizontal .elementor-widget-container .w-product-filter' );
				if ( ! filters.length || filters.length < 1) {
					return false;
				}
				filters.forEach(
					function( el ) {
						if ( ! el.contains( e.target ) ) {
							el.classList.remove( 'open' );
						}
					}
				);
			}
		);

		// Preview.
		if ( 'function' === typeof( onElementorLoaded ) ) {
			onElementorLoaded(
				function() {
					// Date picker init.
					window.elementorFrontend.hooks.addAction(
						'frontend/element_ready/woostify-filter-date-range.default',
						function() {
							woostifyFilterDatePicker();
						}
					);

					// Range slider init.
					window.elementorFrontend.hooks.addAction(
						'frontend/element_ready/woostify-filter-range-slider.default',
						function() {
							let rangeSlider = document.querySelectorAll( '.w-product-filter-type-range-slider' );
							if ( rangeSlider.length ) {
								rangeSlider.forEach(
									function( rs ) {
										if ( 'object' === typeof( rs.noUiSlider ) ) {
											return;
										}

										let from = Number( rs.getAttribute( 'data-from' ) ) || 0,
											to   = Number( rs.getAttribute( 'data-to' ) ) || 100;

										const slider = noUiSlider.create(
											rs,
											{
												tooltips: true,
												connect: true,
												start: [ from, to ],
												step: 1,
												range: {
													'min': from,
													'max': to
												},
												format: {
													from: function( value ) {
														return Math.round( value );
													},
													to: function( value ) {
														return Math.round( value );
													}
												}
											}
										);
									}
								);
							}
						}
					);
				}
			);
		}
	}
);
