<?php
namespace WPFunnelsPro\Modules\Frontend\Checkout\Single;

use WPFunnels\Wpfnl_functions;
class Wpfnl_Single_Product
{

	private $is_funnel_checkout_page;
	private $step_id;
    public function __construct()
	{

		// check this checkout is funnel's checkout or not
		$this->is_funnel_checkout_page = Wpfnl_functions::is_funnel_checkout_page();
		

		// add_action( 'wp_ajax_wpfnl_single_product_quantity_ajax', [$this, 'wpfnl_single_product_quantity_ajax']);
		// add_action( 'wp_ajax_nopriv_wpfnl_single_product_quantity_ajax', [$this, 'wpfnl_single_product_quantity_ajax']);
		if( Wpfnl_functions::is_funnel_step_page() && Wpfnl_functions::is_wc_active() ) {
            add_filter('woocommerce_locate_template', array('WPFunnelsPro\Modules\Frontend\Checkout\Single\Wpfnl_Single_Product', 'wpfunnels_woocommerce_locate_template'), 20, 3);
        }

		if( Wpfnl_functions::is_wc_active() ){
			add_action( 'wp_ajax_wpfnl_update_quantity_ajax', [$this, 'wpfnl_update_quantity_ajax']);
			add_action( 'wp_ajax_nopriv_wpfnl_update_quantity_ajax', [$this, 'wpfnl_update_quantity_ajax']);
		}
		
		// add_action('woocommerce_after_order_notes', array($this, 'wpfnl_checkout_field_for_simple_product'));
		$values = array();
		if( isset($_POST['post_data']) ){
			parse_str($_POST['post_data'], $values);
			$this->step_id = isset($values['_wpfunnels_checkout_id']) ? $values['_wpfunnels_checkout_id'] : '';
		}


		if( ( isset($values['_wpfunnels_checkout_id']) && $values['_wpfunnels_checkout_id'] ) || get_the_ID() != 1 || $this->is_funnel_checkout_page['status'] ){
			if( Wpfnl_functions::is_wc_active() ){
				add_filter( 'woocommerce_checkout_cart_item_quantity', array($this, 'wpfnl_checkout_cart_item_quantity'), 10, 3 );
				add_filter( 'woocommerce_cart_item_name', array($this, 'wpfnl_checkout_cart_item_name'), 10, 3 ); 
			}
		}
		
	}


    /**
	 * Add custom checkout fields for simple products
	 *
	 * @param $checkout
	 */
	public function wpfnl_checkout_field_for_simple_product($checkout){
		
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
					if($product->get_type() == 'simple'){
						$products[$i]['product'] = $product;
						$products[$i]['quantity'] = $product_info['quantity'];
						$i++;
					}
				}
			}
			if(!empty($products)){
				$isQuantity =  get_post_meta(get_the_ID(), '_wpfnl_quantity_support', true);
				if($isQuantity){
					if($isQuantity == 'yes'){
						require WPFNL_PRO_DIR . 'public/modules/checkout/templates/single-product/header.php';
						require WPFNL_PRO_DIR . 'public/modules/checkout/templates/single-product/table-header.php';
						foreach($products as $product){
							$quantity = $product['quantity'];
							$product = $product['product'];
							$this->render_checkout_fields_body($checkout,$product->get_id(),$product,$quantity,$isQuantity);
						}
						require WPFNL_PRO_DIR . 'public/modules/checkout/templates/single-product/table-footer.php';
						require WPFNL_PRO_DIR . 'public/modules/checkout/templates/single-product/footer.php';
					}
				}
			}
		}
	}




	/**
	 * 
	 * Render Checkout fields body for simple product
	 *
	 * @param $checkout,$product_id,$product,$quantity,$isQuantity
	 */
	private function render_checkout_fields_body($checkout,$product_id,$product,$quantity,$isQuantity){
		require WPFNL_PRO_DIR . 'public/modules/checkout/templates/single-product/body.php';
	}


    /**
	 * wpfnl_single_product_quantity_ajax
	 * Select quantity option for simple product
	 *
	 * @since 1.1.
	 */
	public function wpfnl_single_product_quantity_ajax()
	{
		$step_id 		= filter_input(INPUT_POST, 'step_id', FILTER_VALIDATE_INT);
		$product_id 	= filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
		$quantity 		= filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_STRING);
		$isQuantity = false;
		if (isset($_POST['isQuantity'])){
			$isQuantity 	= filter_input(INPUT_POST, 'isQuantity', FILTER_SANITIZE_STRING);
		}
		$response = $this->wpfnl_add_simple_product_quantity($step_id,$product_id,$quantity,$isQuantity);
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
	 * wpfnl_single_product_quantity_ajax
	 * Select quantity option for simple product
	 *
	 * @since 1.1.
	 */
	public function wpfnl_update_quantity_ajax()
	{
		$step_id 		= filter_input(INPUT_POST, 'step_id', FILTER_VALIDATE_INT);
		$product_id 	= filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
		$quantity 		= filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_STRING);
		$variation_id	= filter_input(INPUT_POST, 'variation_id', FILTER_SANITIZE_STRING);

		$variations = array();

		if(isset($_POST['variation'])){
			$variations = $_POST['variation'];
		}

		$response = $this->wpfnl_add_product_quantity($step_id,$product_id,$quantity,$variation_id,$variations);
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
	 * wpfnl_add_simple_products
	 * Add simple product quantity
	 *
	 * @param $step_id,$product_id,$quantity,$isQuantity
	 */
	private function wpfnl_add_product_quantity($step_id,$product_id,$quantity,$variation_id,$variations){
		$order_bump_settings 	= get_post_meta($step_id, 'order-bump-settings', true);
		$_product 				= wc_get_product($product_id);
		$response = array();
		if( $_product ){
			$product_price 			= $_product->get_price();
			$ob_cart_item_data = [
				'custom_price' 		=> $variation_id ? get_post_meta($variation_id, '_regular_price', true) : $product_price
			];
			
			$woo_quantity = '';
			if($_product->get_type() == 'variable'){
				$prdct = wc_get_product($variation_id);
				$woo_quantity = $prdct ? $prdct->get_stock_quantity() : '';
				
			}else{
				$woo_quantity = $_product->get_stock_quantity();
			}
	
			
			if($woo_quantity == '' || $woo_quantity >= $quantity ){
				foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
					if( !isset($cart_item['wpfnl_order_bump']) || !$cart_item['wpfnl_order_bump'] ){
						if( !empty($cart_item['variation_id']) ){
							if( $cart_item['variation_id'] == $variation_id){
								WC()->cart->set_quantity($cart_item_key, $quantity);
							}
						}else{
							if ( isset($cart_item['product_id']) && $cart_item['product_id'] == $product_id) {
								WC()->cart->set_quantity($cart_item_key, $quantity);
							}
						}
					}
				}
			}
		}
		
		
        $response = [
            'status' => 'success',
            'message' => __('Successfully added', 'wpfnl'),
        ];
		
		
		return $response;
	}


	/**
	 * wpfnl_add_simple_products
	 * Add simple product quantity
	 *
	 * @param $step_id,$product_id,$quantity,$isQuantity
	 */
	private function wpfnl_add_simple_product_quantity($step_id,$product_id,$quantity,$isQuantity){
		$order_bump_settings 	= get_post_meta($step_id, 'order-bump-settings', true);
		$_product 				= wc_get_product($product_id);
		$product_price 			= $_product->get_price();

		$ob_cart_item_data = [
			'custom_price' 		=> $product_price,
		];
		$response = array();

        if ($isQuantity){
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
              
                if ( $cart_item['product_id'] == $product_id) {
                    WC()->cart->remove_cart_item($cart_item_key);
                }
            }
        }
        WC()->cart->add_to_cart( $product_id, $quantity, 0, [], $ob_cart_item_data );
        $response = [
            'status' => 'success',
            'message' => __('Successfully added', 'wpfnl'),
        ];
		
		
		return $response;
	}
	


	/**
	 * Update quantity from checkout page
	 * 
	 * @param $quantity, $cart_item, $cart_item_key
	 */
	public function wpfnl_checkout_cart_item_quantity( $quantity, $cart_item, $cart_item_key ) { 
		
		$step_id = 0;
		$isQuantity = 'no';
		if( wp_doing_ajax() ) {
            $checkout_step  = Wpfnl_functions::is_funnel_checkout_page();
            $step_id        = isset($checkout_step['id']) ? $checkout_step['id'] : '';
        } else {
            $step_id = get_the_ID();
        }
		
        $isQuantity = get_post_meta($step_id, '_wpfnl_quantity_support',true);
		$order_bump_products = get_post_meta($step_id,'order-bump-settings',true);
		if($isQuantity === 'yes'){
			
			if( !isset($cart_item['wpfnl_order_bump']) || !$cart_item['wpfnl_order_bump'] ){
				$variations = json_encode($cart_item['variation']);
				$product_id = isset($cart_item["product_id"]) ? $cart_item["product_id"] : '';
				$quantity = isset($cart_item["quantity"]) ? $cart_item["quantity"] : 1;
				$variation_id = isset($cart_item["variation_id"]) ? $cart_item["variation_id"] : '';
				$quantity = "× <input type='number' min='1' value='".esc_html__($quantity)."' class='wpfnl-quantity-setect' data-product-id='".esc_html__($product_id)."' data-variation='".esc_html__($variations)."' data-variation-id='".esc_html__($variation_id)."'/>";
                $cookie_name    = 'wpfunnels_global_funnel_product';
                $data = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
                $key = array_search($product_id, array_column($data, 'id'));
                if( false !== $key ){
                    $data[$key]['quantity'] = isset($cart_item["quantity"]) ? $cart_item["quantity"] : 1;
                    @setcookie( $cookie_name, wp_json_encode( $data ), time() + 3600, '/', COOKIE_DOMAIN );
                }
			}
		}
		return $quantity;
	}



	/**
	 * 
	 */
	public function wpfnl_checkout_cart_item_name( $product_get_name, $cart_item, $cart_item_key ){
		
		$step_id = $this->get_step_id();
		if( $step_id ){
            $funnel_id = get_post_meta($step_id, '_funnel_id', true);
            $type = get_post_meta($funnel_id, '_wpfnl_funnel_type', true);
            if('lms' == $type || !Wpfnl_functions::is_wc_active() ){
                return false;
            }
			$products = get_post_meta($step_id, '_wpfnl_checkout_products', true);
			if( is_array( $products )){
				foreach( $products as $product ){
					$__product = wc_get_product( $product['id'] );
					if( $__product ){
						if( $__product->get_type() == 'variation' ){
							$is_perfect_variation = Wpfnl_functions::is_perfect_variations( $product['id'] );
	
							if( !$is_perfect_variation['status'] ){
								if( $cart_item['variation_id'] == $product['id'] ){
									$select = '<input type="hidden" name="_wpfunnels_variable_product" value="">';
									foreach( $is_perfect_variation['data'] as $key=>$attr ){
										
										$select .= ' , ';
										$select .= '<select class="wpfnl-update-variation" data-attr="'.$key.'" data-quantity="'.$cart_item['quantity'].'" data-product-id="'.$cart_item['product_id'].'" data-variation-id="'.$cart_item['variation_id'].'">';
										foreach( $is_perfect_variation['data'][$key] as $option ){
											if( isset( $cart_item['variation']['attribute_'.$key] ) && trim($option) == trim($cart_item['variation']['attribute_'.$key]) ){
												$select .= '<option value="'.$option.'" selected >'.ucfirst($option).'</option>';
											}else{
												$select .= '<option value="'.$option.'" >'.ucfirst($option).'</option>';
											}
										}
										$select .= '</select>';
									}
									$product_get_name = $product_get_name.$select;
								}
							}
						}
					}
				}
				
			}
		}
		return $product_get_name; 
		
	}



	/**
     * Step id is get_the_ID() if Checkout is not enable ajax
     * @return  int
     */

    public function get_step_id()
    {	
		
		if( get_the_ID() == 1 ){
			$values = array();
			if( isset($_POST['post_data']) ){
				parse_str($_POST['post_data'], $values);
			}
			$step_id = isset($values['_wpfunnels_checkout_id']) ? $values['_wpfunnels_checkout_id'] : '';
			
        }else{
            $step_id = get_the_ID();
        }
		
        return $step_id;
    }


	/**
     * Get Custom  Woocommerce template
     * @param $template
     * @param $template_name
     * @param $template_path
     * @return mixed|string
     */

    public static function wpfunnels_woocommerce_locate_template($template, $template_name, $template_path)
    {
        global $woocommerce;
        $_template 		= $template;
        $plugin_path 	= WPFNL_DIR . '/woocommerce/templates/';

        if (file_exists($plugin_path . $template_name)) {
            $template = $plugin_path . $template_name;
        }

        if ( ! $template ) {
            $template = $_template;
        }

        return $template;
    }


}