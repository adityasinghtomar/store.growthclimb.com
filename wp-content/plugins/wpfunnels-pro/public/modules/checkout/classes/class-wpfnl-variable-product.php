<?php
namespace WPFunnelsPro\Modules\Frontend\Checkout\Variable;
use WPFunnels\Wpfnl_functions;

class Wpfnl_Variable_Product
{
    public function __construct()
	{
		add_action( 'wp_ajax_wpfnl_variable_ajax', [$this, 'wpfnl_variable_product_ajax']);
		add_action( 'wp_ajax_nopriv_wpfnl_variable_ajax', [$this, 'wpfnl_variable_product_ajax']);

        if(  !isset( $_GET['wc-ajax'] ) ){
            add_action('wpfunnel_review_order_before_cart_contents', array($this, 'wpfnl_checkout_field_for_variable'),9999);
        }
        
		add_action('woocommerce_form_field', array($this, 'wpfnl_remove_checkout_optional_text'), 9999, 4);
		add_action( 'wp_ajax_wpfnl_update_variable_ajax', [$this, 'wpfnl_update_variable_ajax']);
		add_action( 'wp_ajax_nopriv_wpfnl_update_variable_ajax', [$this, 'wpfnl_update_variable_ajax']);
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

	}


    /**
	 * wpfnl_variable_ajax
	 * Select multiple/one products from variations
	 *
	 * @since 1.1.
	 */
	public function wpfnl_update_variable_ajax()
	{
		$step_id 		= filter_input(INPUT_POST, 'step_id', FILTER_VALIDATE_INT);
		$product_id 	= filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
		$quantity 		= filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_STRING);
		$attributes 	= $_POST['attrs'];

		$response = $this->wpfnl_add_variable_product_to_cart($step_id,$product_id,$quantity,$attributes);
		if($response){
			wp_send_json_success($response);
		}else{
			$response = [
				'status'=>'fail'
			];
			wp_send_json_success($response);
		}

	}


	/**
	 * wpfnl_add_variable_product_to_cart
	 * Add variation to cart from variable product
	 *
	 * @param $step_id,$product_id,$quantity,$checker,$type
	 */
	private function wpfnl_add_variable_product_to_cart($step_id,$product_id,$quantity,$attributes){
		$order_bump_settings 	= get_post_meta($step_id, 'order-bump-settings', true);
		$response = [
			'status' => 'fail',
		];
		
		$_product 				= wc_get_product($product_id);
		if( $_product ){
			$product = wc_get_product($product_id);
			foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
				if( !isset($cart_item['product_type'])|| (isset($cart_item['product_type']) && ('variable' === $cart_item['product_type'] || 'variable-subscription' === $cart_item['product_type'] )) ){
					if ( $cart_item['product_id'] == $product_id ) {	
						WC()->cart->remove_cart_item($cart_item_key);
					}
				}
			}
			$formatted_attr = [];
			foreach($attributes as $attribute){
				foreach($attribute as $key=>$value){
					$formatted_attr["attribute_".$key] = $value;
				}
			}
			$variation_id = (new \WC_Product_Data_Store_CPT())->find_matching_product_variation(
					new \WC_Product($product_id),
					$formatted_attr
			);
			
			if($variation_id > 0){
				$ob_cart_item_data = [
					'custom_price' 	=> get_post_meta($variation_id, '_price', true) ? get_post_meta($variation_id, '_price', true) : get_post_meta($variation_id, '_regular_price', true)
				];

				$checkout_products = get_post_meta( $step_id, '_wpfnl_checkout_products', true );

				if(is_array( $checkout_products )){
					foreach( $checkout_products as $key=>$checkout_product ){
						
						if( $product_id == $checkout_product['id'] ){
						
							if( !empty($checkout_product['discount']['discountOptions']) && !empty($checkout_product['discount']['mutedDiscountValue']) ){
								if( 'discount-price' === $checkout_product['discount']['discountOptions'] ){
									$ob_cart_item_data['custom_price'] = $ob_cart_item_data['custom_price'] - $checkout_product['discount']['mutedDiscountValue'];
								}else{
									$ob_cart_item_data['custom_price'] = $ob_cart_item_data['custom_price'] - (($ob_cart_item_data['custom_price'] * $checkout_product['discount']['mutedDiscountValue'] ) / 100);
								}
								
							}
						}
					}
				}


				WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $formatted_attr, $ob_cart_item_data);
			}
			
			$response = [
				'status' => 'success',
				'message' => __('Successfully added', 'wpfnl'),
			];
		}
	
		
		return $response;
	}
	
	
	/**
	 * wpfnl_variable_ajax
	 * Select multiple/one products from variations
	 *
	 * @since 1.1.
	 */
	public function wpfnl_variable_product_ajax()
	{
		if( !Wpfnl_functions::is_wc_active() ){
			return false;
		}

		$step_id 		= filter_input(INPUT_POST, 'step_id', FILTER_VALIDATE_INT);
		$product_id 	= filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
		$checker 		= filter_input(INPUT_POST, 'checker', FILTER_SANITIZE_STRING);
		$quantity 		= filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_STRING);
		$type 			= filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
		$isQuantity = false;
		if (isset($_POST['isQuantity'])){
			$isQuantity 	= filter_input(INPUT_POST, 'isQuantity', FILTER_SANITIZE_STRING);
		}
		$response = ($type == 'checkbox') ? $this->wpfnl_add_variable_products($step_id,$product_id,$quantity,$checker,'checkbox',$isQuantity) : $this->wpfnl_add_variable_products($step_id,$product_id,$quantity,$checker,'radio',$isQuantity);
		if($response){
			wp_send_json_success($response);
		}else{
			$response = [
				'status'=>'fail'
			];
			wp_send_json_success($response);
		}

	}


	/**
	 * wpfnl_add_variable_products
	 * Add variation to cart from variable product
	 *
	 * @param $step_id,$product_id,$quantity,$checker,$type
	 */
	private function wpfnl_add_variable_products($step_id,$product_id,$quantity,$checker,$type,$isQuantity){
		$order_bump_settings 	= get_post_meta($step_id, 'order-bump-settings', true);
		$_product 				= wc_get_product($product_id);
		
		
		$response = array();
		if( $_product ){
			$discount_type			= $order_bump_settings['discountOption'];
			$discount_apply_to  	= $_product->is_on_sale() ? 'sale' :  'regular' ;
			$product_price 			= $_product->get_price();

			$ob_cart_item_data = [
				'custom_price' 		=> $product_price,
			];
			
			if ($checker == "true") {
				$product = wc_get_product($product_id);
				if( $product ){
					$parent_id = $product->get_parent_id();
					if($type == 'radio'){
						foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
							if ( $cart_item['product_id'] == $parent_id) {
								WC()->cart->remove_cart_item($cart_item_key);
							}
						}
					}
					if ($isQuantity){
						foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
							if ( $cart_item['variation_id'] == $product_id) {
								WC()->cart->remove_cart_item($cart_item_key);
							}
						}
					}
					WC()->cart->add_to_cart( $product_id, $quantity, 0, [], $ob_cart_item_data );
					$response = [
						'status' => 'success',
						'message' => __('Successfully added', 'wpfnl'),
					];
				}
			}
			elseif ($checker == "false") {
				foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
					if ($cart_item['variation_id'] == $product_id) {
						WC()->cart->remove_cart_item($cart_item_key);
					}
				}
				$response = [
					'status' => 'success',
					'message' => __('Successfully removed', 'wpfnl'),
				];

			}
		}
		return $response;
	}


    /**
	 * Remove optional text from checkout fields
	 *
	 * @param $field, $key, $args, $value
	 */
	public function wpfnl_remove_checkout_optional_text( $field, $key, $args, $value ) {
		if( Wpfnl_functions::is_wc_active() && is_checkout() && ! is_wc_endpoint_url() ) {
			$optional = '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'wpfnl' ) . ')</span>';
			$field = str_replace( $optional, '', $field );
		}
		return $field;
	}

	/**
	 * Add custom checkout fields for variable products
	 *
	 * @param $checkout
	 */
	public function wpfnl_checkout_field_for_variable($checkout){

        $funnel_id = get_post_meta(get_the_ID(), '_funnel_id', true);
        $type = get_post_meta($funnel_id, '_wpfnl_funnel_type', true);
        if('lms' == $type || !Wpfnl_functions::is_wc_active() ){
            return false;
        }
		global $woocommerce;
		$items = $woocommerce->cart->get_cart();
		$products_info = get_post_meta(get_the_ID(), '_wpfnl_checkout_products', true);
		
		$products = array();
		$i=0;
		if(!empty($products_info)){
			foreach($products_info as $product_info){
				$product = wc_get_product($product_info['id']);
				if( $product ){
					$product_id = $product_info['id'];
					
					if( 'variable-subscription' === $product->get_type() || 'variable' == $product->get_type() ){
						$products[$i]['product'] = $product;
						$products[$i]['quantity'] = $product_info['quantity'];
						$i++;
					}

				}
				
			}
			$isMultipleProduct =  get_post_meta(get_the_ID(), '_wpfnl_multiple_product', true);
			$isQuantity =  get_post_meta(get_the_ID(), '_wpfnl_quantity_support', true);
			if($isMultipleProduct){
				if($isMultipleProduct == 'yes'){
					$this->wpfnl_add_fields($checkout,$products,'checkbox',$isQuantity);

				}else{
					$this->wpfnl_add_fields($checkout,$products,'radio',$isQuantity);
				}
			}
		}

	}

	/**
	 * Add fields for variable products
	 *
	 * @param $checkout, $products, $type
	 */
	private function wpfnl_add_fields($checkout,$products,$type,$isQuantity){
		if(!empty($products)){
			
			require WPFNL_PRO_DIR . 'public/modules/checkout/templates/variable-product/header.php';
			$attr = [];
			wp_enqueue_script( 'wc-add-to-cart-variation' );
			$supported_types = [
				'variable',
				'variable-subscription'
			];

			foreach($products as $product){
				$quantity = $product['quantity'];
				$product = $product['product'];
				$variations = $product->get_available_variations();
				$product_id = $product->get_id();

				if( !in_array( $product->get_type() ,$supported_types) ) {
					return false;
				}
		
				?>
				<p class="product-title">
					<span><?php echo __('Product name: ','wpfnl-pro') ?></span>
					<span><?php echo $product->get_title() ?></span>
				</p>
				<?php
				// Get Available variations?
				$get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
				$formatted_attr = $this->get_formatted_attributes( $product->get_variation_attributes(), $product->get_attributes() );
				// Load the template.
				wc_get_template(
					'single-product/add-to-cart/variable.php',
					array(
						'available_variations' => $get_variations ? $product->get_available_variations() : false,
						'attributes'           => $formatted_attr,
						'selected_attributes'  => $product->get_default_attributes(),
						'class'     		   => '',
						'product'              => $product,
						'quantity'             => $quantity,
					)
				);
				

			}
			require WPFNL_PRO_DIR . 'public/modules/checkout/templates/variable-product/footer.php';
		}
	}


	/**
	 * Get formatted attributes.
	 * 
	 * This function filters the attributes and returns the formatted variation attributes
	 * based on the provided variation attributes and all attributes.
	 * 
	 * @param array $variation_attr The variation attributes.
	 * @param array $all_attr The all attributes.
	 * 
	 * @return array The formatted variation attributes.
	 * @since 1.9.3
	 */
	public function get_formatted_attributes( $variation_attr, $all_attr ){
		if ( ! is_array( $variation_attr ) || ! is_array( $all_attr ) ) {
			return [];
		}
		$filtered_attributes = array_filter($all_attr, function ($key) {
			return strpos($key, 'pa_') !== 0;
		}, ARRAY_FILTER_USE_KEY);
		
		foreach ($variation_attr as $index => $attr) {
			$lowercase_index = strtolower($index);
			if (array_key_exists($lowercase_index, $filtered_attributes)) {
				$variation_attr[$index] = $filtered_attributes[$lowercase_index]->get_options();
			}
		}
		return $variation_attr;
	}


	/**
	 * 
	 * Render Checkout fields body for variable prosuct
	 *
	 * @param $key,$value,$product,$default_attr
	 */
	private function render_checkout_attr_fields($key,$value,$product,$default_attr,$product_id){
		require WPFNL_PRO_DIR . 'public/modules/checkout/templates/variable-product/body.php';
	}



	/**
	 * Add fields for variable products
	 *
	 * @param $checkout, $products, $type
	 */
	private function wpfnl_add_fields_previous($checkout,$products,$type,$isQuantity){
		if(!empty($products)){
			require WPFNL_PRO_DIR . 'public/modules/checkout/templates/variable-product/header.php';
			foreach($products as $product){
				$quantity = $product['quantity'];
				$product = $product['product'];
				$variations = $product->get_available_variations();
				$i =0;
				$parent_id = $product->get_id();
				if(count($variations)>0){
					require WPFNL_PRO_DIR . 'public/modules/checkout/templates/variable-product/table-header.php';
					foreach ($variations as $variation) {
						if($variation['is_in_stock'] && array_search('', $variation['attributes']) == ''){

							$isDefVariation = false;
							$default['default'] = 0;
							if($type == 'radio'){
								if($product->get_default_attributes()){
									foreach($product->get_default_attributes() as $key=>$val){
										if($variation['attributes']['attribute_'.$key]==$val){
											$isDefVariation=true;
											$default['default'] = $variation['variation_id'];
										}
									}
								}else{
									if($i == 0){
										$default['default'] = $variation['variation_id'];
									}
								}
							}else{
								if($product->get_default_attributes()){
									foreach($product->get_default_attributes() as $key=>$val){
										if($variation['attributes']['attribute_'.$key]==$val){
											$isDefVariation=true;
											$default['default'] = 1;
										}
									}
								}else{
									if($i == 0){
										$default['default'] = 1;
									}
								}
							}
							$this->render_checkout_fields_body_prev($checkout,$variation,$parent_id,$product,$type,$default,$quantity,$isQuantity);
							$i++;
						}
					}
					require WPFNL_PRO_DIR . 'public/modules/checkout/templates/variable-product/table-footer.php';
				}

			}
			require WPFNL_PRO_DIR . 'public/modules/checkout/templates/variable-product/footer.php';
		}
	}

	/**
	 * 
	 * Render Checkout fields body for variable prosuct
	 *
	 * @param $checkout,$variation,$parent_id,$product,$type,$default
	 */
	private function render_checkout_fields_body_prev($checkout,$variation,$parent_id,$product,$type,$default,$quantity,$isQuantity){
		require WPFNL_PRO_DIR . 'public/modules/checkout/templates/variable-product/body.php';
	}

}
