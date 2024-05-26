/**
 * @todo: Convert this file to Vanilla JS.
 */

'use strict';

var frequently_bought_together_admin = function (){
    // Ajax search product by name.
    var inputSearch = document.querySelector('#frequently_bought_together_options .search-input');
    var searchResultElement = document.querySelector('#frequently_bought_together_options .search-result');

    searchResultElement.style.display = "none";

    inputSearch.addEventListener('click', function(){
        searchResultElement.style.display = "block";
    });
	document.addEventListener('click', function(e){
		var searchElm = document.querySelector('#frequently_bought_together_options .search-product');
		if( ! searchElm ) {
			return;
		}else{
			if( ! (searchElm.contains( e.target ) ) ) {
				searchResultElement.style.display = "none";
			}
		}
    });
	
	var inputSearch_timeout = 0;
    inputSearch.addEventListener('keyup', function(){
		// Cancel old action
		clearTimeout( inputSearch_timeout );

		// Wait 400ms for typing
		inputSearch_timeout = setTimeout( function(){
			var keyWord = inputSearch.value;
			var k_str = keyWord.toLowerCase().replace(/\s/g, '');
			inputSearch.classList.add('loading');
			var packedProducts = [];
			let packedElements = document.querySelectorAll('#frequently_bought_together_options .product-list .product__item');
			packedElements.forEach( function( elm ) {
				if( elm.hasAttribute('data-product-id') ) {
					packedProducts.push( elm.getAttribute('data-product-id') );
				}
			});

			var data = {
				'action': 'woostify_fbt_search_product',
				'keyword': keyWord,
				'_nonce': woostify_fbt._nonce // woostify_fbt in localize script.
			};
			searchResultElement.innerHTML = '';

			if (keyWord.length > 1) {
				data = new URLSearchParams( data ).toString();
				fetch(
					woostify_fbt.ajaxurl, {
						method: 'POST',
						body: data,
						credentials: 'same-origin',
						headers: new Headers(
							{
								'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
							}
						)
					}
				).then(
					function( res ) {
						if ( 200 !== res.status ) {
							console.log( 'Status Code: ' + res.status );
							throw res;
						}

						return res.json();
					}
				).then(
					function (response) {
						inputSearch.classList.remove('loading');
						var searchResult = response;
						if (searchResult.product_list && searchResult.product_list.length > 0) {
							var products = searchResult.product_list;
							var html = '';                        
							if (products.length > 0) {
								searchResultElement.style.display = "block";
								products.forEach(function (product) {
									// String compare
									if( packedProducts.indexOf( product.id.toString() ) == -1 ){
										html += '<li class="search-result-item ' + product.stock_status + '" data-id="' + product.id + '" onclick="frequently_add_product_to_list(this)">' +
											'<figure>' + product.image + '</figure>' +
											'<div class="product__info">' +
											'<h2>' + product.name + '</h2>' +
											product.price +
											'</div>' +
										'</li>';
									}
								});
							}
							searchResultElement.innerHTML = '<ul>' + html + '</ul>';
						} else {
							searchResultElement.innerHTML = '<p>' + woostify_fbt.no_result + '</p>';
						}
					}
				).catch(
					function (error) {
						searchResultElement.innerHTML = '<p>' + woostify_fbt.no_result + '</p>';                    
					}
				);
			}
		}, 400); // end function timeout
	}); // end keyup event

    // Variation change event.
    // document.querySelector('.product-list .product_variations select').addEventListener('change', function(){
    //     console.log('asd');
    //     var parent = $(this).closest('tr');
    //     var wrapper = $(this).closest('.product_variations');
    //     var variations_data = $(this).closest('.product_variations').data('variations');
    //     var attributes_selects = wrapper.find('select');
    //     var attributes = {};

    //     attributes_selects.each(function(){
    //         var attribute_name = $(this).data('attribute_name');
    //         var attribute_value = $(this).val();
    //         attributes[attribute_name] = attribute_value;
    //     });

    //     var variation = variations_data.find(function(variation){
    //         var variation_attributes = variation.attributes;
    //         var match = true;
    //         for (var attribute_name in attributes) {
    //             if (attributes[attribute_name] !== variation_attributes[attribute_name]) {
    //                 match = false;
    //             }
    //         }
    //         return match;
    //     });

    //     parent.find('.product__item--price').html(variation.price_html);
    // });
}

function frequently_add_product_to_list(obj){
    var searchResultElement = document.querySelector('#frequently_bought_together_options .search-result');  
    var productId = obj.getAttribute('data-id'),
    productTable = document.querySelector('#frequently_bought_together_options .product-list table'),
    item_id = 1;    

    productTable.querySelectorAll('tr').forEach(function(el){
        var _this = el,
            id = _this.getAttribute( 'data-item' );
        if( id >= item_id ) {
            item_id = parseInt( id ) + 1;
        }
    });

	obj.classList.add('loading');
    var data = {
        'action': 'woostify_fbt_add_product',
        'product_id': productId,
        'item_id': item_id,
        '_nonce': woostify_fbt._nonce
    };
    data = new URLSearchParams( data ).toString();
    fetch(
        woostify_fbt.ajaxurl, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            headers: new Headers(
                {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                }
            )
        }
    ).then(
        function( res ) {
            if ( 200 !== res.status ) {
                console.log( 'Status Code: ' + res.status );
                throw res;
            }

            return res.json();
        }
    ).then(
        function (response) {
			obj.remove();
            if (response.data) {
                var element = response.data;                             
                productTable.querySelector('tbody').innerHTML += element;

                
                const elementString = "<table><tbody>"+element+"</tbody></table>";
                var parser = new DOMParser();
	            var element = parser.parseFromString(elementString, 'text/html');
                
                var image = element.querySelector('.product__item--image').innerHTML,
                title = element.querySelector('.product__item--title').textContent;
                var price = element.querySelector('input').getAttribute('data-price');
    
                document.querySelector('.packed__product').innerHTML += '<td data-item="'+item_id+'">\n' +
                    '<div class="packed__product__image">' + image + '\n<span class="plus">+</span>\n</div>\n' +
                    '<div class="packed__product__name">' + title + '</div>\n' +
                '</td>';
    
                document.querySelector('.packed__percent').innerHTML += ' <td data-item="'+item_id+'">\n' +
                    '<input data-price="'+price+'" name="woostify_fbt['+item_id+'][percent]" type="number" value="5">\n' +
                '</td>';
            }
            searchResultElement.style.display = "none";
        }
    ).catch(
        function (error) {
            searchResultElement.style.display = "none";
            console.log( error );
        }
    );
}

function frequently_remove_product_list(obj){
    var parent = obj.closest('tr'),
    item_id = parent.getAttribute('data-item');    
    var remove_item = document.querySelectorAll('[data-item="'+item_id+'"]');    
    for (var i in remove_item) if (remove_item.hasOwnProperty(i)) {
        remove_item[i].remove();
    }
}

// Variation change event.
function frequently_variation_change_in_list(obj){    
    var parent = obj.closest('tr');
    var wrapper = obj.closest('.product_variations');   
    var variations_data = JSON.parse(wrapper.getAttribute('data-variations'));
    var attributes_selects = wrapper.querySelectorAll('select');
    var attributes = {};    

    attributes_selects.forEach(function(el){
        var attribute_name = el.getAttribute('data-attribute_name');
        var attribute_value = el.value;
        attributes[attribute_name] = attribute_value;
    });    

    var variation = variations_data.filter(function(variation){
        var variation_attributes = variation.attributes;
        var match = true;
        for (var attribute_name in attributes) {
            if (attributes[attribute_name] !== variation_attributes[attribute_name]) {
                match = false;
            }
        }
        return match;
    });
    
    if(variation.length != 0 ){
        parent.querySelector('.product__item--price').innerHTML= variation[0].price_html;
    }
}

// Toggle title and description message
var panelEditToggle = function () {   
    var editPanel = document.querySelector('.woostify_fbt-title-desciption-edit'),
    editAbleCheckbox = document.getElementById('woostify_fbt_enable');
    if (editAbleCheckbox.checked) {
        editPanel.classList.remove('hidden');
    } else {
        editPanel.classList.add('hidden');
    }
    editAbleCheckbox.addEventListener('change', function (event) {
        if (event.currentTarget.checked) {
            editPanel.classList.remove('hidden');
        } else {
            editPanel.classList.add('hidden');
        }
    });
};

document.addEventListener(
    'DOMContentLoaded',
    function () {
        frequently_bought_together_admin();
        panelEditToggle();
    }
);