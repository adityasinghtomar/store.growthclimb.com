<?php

namespace WPFunnelsPro\Admin\Modules\Steps\Checkout;

use WPFunnels\Wpfnl_functions;
use WC_Countries;
class CheckoutFields
{
    public $type;
    public $step_id;
	public $billing_fields;
	public $shipping_fields;
	public $additional_fields;
    public function init(){
        $this->init_hooks();
        $this->init_ajax();
        $this->type = "checkout-editor";
    }

    public function init_hooks()
    {
        add_action('admin_enqueue_scripts', [ $this, 'load_scripts' ]);
        add_action('wpfunnels/checkout_pro_settings', array($this, 'render_checkout_fields'));
		// add_action( 'save_post', 'wpfnl_save_all_field_data', 10 );
    }
    public function get_validation_data()
    {
        return[
            'logged_in' => true,
            'user_can' => 'manage_options',
        ];
    }
    public function load_scripts($hook) {

        if( isset($_GET['page']) && $_GET['page'] === 'edit_funnel' && isset($_GET['step_id']) ) {
			$this->billing_fields = get_post_meta($_GET['step_id'],'wpfnl_wc_billing_fields',true);
			$this->shipping_fields = get_post_meta($_GET['step_id'],'wpfnl_wc_shipping_fields',true);
			$this->additional_fields = get_post_meta($_GET['step_id'],'wpfnl_wc_additional_fields',true);
            wp_enqueue_script($this->type.'-js', WPFNL_PRO_URL . 'admin/modules/steps/checkout/asset/js/main.js', [ 'jquery', 'wp-util'], '1.0.0', true);
            wp_localize_script( $this->type.'-js','CheckoutEditor',
				array(
					'ajaxurl'           => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
					'stepId'            => $_GET['step_id'],
					'billingFields'		=> $this->billing_fields,
					'shippingFields'	=> $this->shipping_fields,
					'additionalFields'	=> $this->additional_fields,
				)
            );

        }
    }
    public function init_ajax()
    {
		wp_ajax_helper()->handle('wpfn-show-checkout-fields')
            ->with_callback([ $this, 'wpfn_show_checkout_fields' ])
            ->with_validation($this->get_validation_data());

		wp_ajax_helper()->handle('wpfn-show-billing-fields')
            ->with_callback([ $this, 'wpfn_show_checkout_billing_fields' ])
            ->with_validation($this->get_validation_data());
			
		wp_ajax_helper()->handle('wpfn-show-shipping-fields')
            ->with_callback([ $this, 'wpfn_show_checkout_shipping_fields' ])
            ->with_validation($this->get_validation_data());
			
		wp_ajax_helper()->handle('wpfn-show-additional-fields')
            ->with_callback([ $this, 'wpfn_show_checkout_additional_fields' ])
            ->with_validation($this->get_validation_data());
			
		wp_ajax_helper()->handle('wpfn-add-checkout-field')
            ->with_callback([ $this, 'wpfn_add_checkout_fields' ])
            ->with_validation($this->get_validation_data());
			
		wp_ajax_helper()->handle('wpfn-update-checkout-field')
            ->with_callback([ $this, 'wpfn_update_checkout_fields' ])
            ->with_validation($this->get_validation_data());
			
		wp_ajax_helper()->handle('wpfn-restore-to-default-fields')
            ->with_callback([ $this, 'wpfn_restore_to_default_fields' ])
            ->with_validation($this->get_validation_data());
			
		wp_ajax_helper()->handle('wpfn-update-field-enable-status')
            ->with_callback([ $this, 'wpfn_update_field_enable_status' ])
            ->with_validation($this->get_validation_data());
			
		wp_ajax_helper()->handle('wpfn-delete-field')
            ->with_callback([ $this, 'wpfn_delete_field' ])
            ->with_validation($this->get_validation_data());
			
		wp_ajax_helper()->handle('wpfn-update-field-required')
            ->with_callback([ $this, 'wpfn_update_field_required' ])
            ->with_validation($this->get_validation_data());
			
		wp_ajax_helper()->handle('wpfn-update-field-order')
            ->with_callback([ $this, 'wpfn_update_field_order' ])
            ->with_validation($this->get_validation_data());
			
		wp_ajax_helper()->handle('wpfnl-edit-fields-additional-settings')
            ->with_callback([ $this, 'wpfnl_update_additional_settings' ])
            ->with_validation($this->get_validation_data());
			
		
    }

	/**
	 * wpfn_show_checkout_fields
	 * Show the billing, shipping and addotional fields
	 * 
	 * @param array $payload
	 * 
	 */
	public function wpfn_show_checkout_fields($payload){
        $data = [];
		if( isset( $payload['step_id'] )){

			$field_type = [
				'billing',
				'shipping',
				'additional'
			];
			$settings = get_post_meta( $payload['step_id'], '_wpfunnels_edit_field_additional_settings', true );
			$data['settings'] = $settings ? $settings : 'Data not found';
			foreach( $field_type as $type ){
				$method = 'wpfnl_get_'.$type.'_default_data';
				$data[$type.'_data'] = get_post_meta($payload['step_id'],'wpfnl_checkout_'.$type.'_fields', true ) ? get_post_meta($payload['step_id'],'wpfnl_checkout_'.$type.'_fields', true ) : $this->$method();
			}
			$data['success'] = true;
		}
		return $data ;
    }
	

	/**
     * wpfn_show_checkout_billing_fields
     * Show the billing fields
     * 
     * @param array $payload  
     * 
     */
    public function wpfn_show_checkout_billing_fields($payload){
		$get_billing_data = get_post_meta($payload['step_id'],'wpfnl_checkout_billing_fields', true );
        if( empty($get_billing_data) ){
            $get_billing_data = $this->wpfnl_get_billing_default_data();
			// update_post_meta($payload['step_id'],'wpfnl_checkout_billing_fields',$get_billing_data);
			return [
				'status' => 'success',
				'data'   => $get_billing_data
			];
        }
        return [
            'status' => 'success',
			'data'   => $get_billing_data
        ];
    }
	
	
	/**
     * wpfn_show_checkout_shipping_fields
     * Show the shipping fields
     * 
     * @param array $payload  
     * 
     */
    public function wpfn_show_checkout_shipping_fields($payload){

		$get_shipping_data = get_post_meta($payload['step_id'],'wpfnl_checkout_shipping_fields', true );
		
        if( empty($get_shipping_data) || !is_array($get_shipping_data) ){
            $get_shipping_data = $this->wpfnl_get_shipping_default_data();
			
			// update_post_meta($payload['step_id'],'wpfnl_checkout_shipping_fields',$get_shipping_data);
			return [
				'status' => 'success',
				'data'   => $get_shipping_data
			];
        }
        return [
            'status' => 'success',
			'data'   => $get_shipping_data
        ];
    }
	
	
	/**
     * wpfn_show_checkout_additional_fields
     * Show the additional fields
     * 
     * @param array $payload  
     * 
     */
    public function wpfn_show_checkout_additional_fields($payload){
		$get_additional_data = get_post_meta($payload['step_id'],'wpfnl_checkout_additional_fields', true );
        if( empty($get_additional_data) ){
            $get_additional_data = $this->wpfnl_get_additional_default_data();
			// update_post_meta($payload['step_id'],'wpfnl_checkout_additional_fields',$get_additional_data);
			return [
				'status' => 'success',
				'data'   => $get_additional_data
			];
        }
        return [
            'status' => 'success',
			'data'   => $get_additional_data
        ];
    }

	/**
	 * wpfn_add_checkout_billing_fields
	 * add checkout field in billing section
	 * 
	 * @param Array $payload  field data
	 */
	public function wpfn_add_checkout_fields( $payload ){
		$field_info['stepID'] = $payload['stepID'];
		$field_info['sectionType'] = $payload['sectionType'];
		$field_info['type'] = $payload['selectedFieldType'];
		$field_info['label'] = $payload['label'];
		$field_info['name'] = $payload['name'];
		$field_info['placeholder'] = $payload['placeholder'];
		$field_info['default'] = $payload['defaultValue'];
		$field_info['delete'] = 0;
		if(isset($payload['validate'])){
			$field_info['validate'] = $payload['validate'];
		}else{
			$field_info['validate'] = '';
		}
		if(isset($payload['options']) && is_array($payload['options']) && !empty($payload['options'])){
			$field_info['options'] = $payload['options'];
 		}
		
		$boolReq = json_decode($payload['isRequired']);
		$field_info['required'] = $boolReq;
		
		if($payload['isEnabled'] == 'true'){
			$field_info['enable'] = true;
		}else{
			$field_info['enable'] = false;
		}

		if($payload['isDisplayOrderPage'] == 'true'){
			$field_info['show'] = true;
		}else{
			$field_info['show'] = false;
		}
	
		$field_info['class'] = array('my-field-class form-row-wide');
		if( $payload['sectionType'] === 'billing' ){
			$response = $this->wpfn_add_checkout_billing_fields($field_info);
		}elseif( $payload['sectionType'] === 'shipping' ){
			$response = $this->wpfn_add_checkout_shipping_fields($field_info);
		}else{
			$response = $this->wpfn_add_checkout_additional_fields($field_info);
		}

		return $response;
	}

	/**
	 * wpfn_update_checkout_billing_fields
	 * update checkout field in billing section
	 * 
	 * @param Array $payload  field data
	 */
	public function wpfn_update_checkout_fields( $payload ){
		$field_info['stepID'] = $payload['stepID'];
		$field_info['sectionType'] = $payload['sectionType'];
		$field_info['type'] = $payload['selectedFieldType'];
		$field_info['label'] = $payload['label'];
		$field_info['name'] = $payload['name'];
		$field_info['placeholder'] = $payload['placeholder'];
		$field_info['default'] = $payload['defaultValue'];
		$field_info['delete'] = 0;
		
		if(isset($payload['validate'])){
			$field_info['validate'] = $payload['validate'];
		}else{
			$field_info['validate'] = '';
		}
		
		if(isset($payload['options']) && is_array($payload['options']) && !empty($payload['options'])){
			$field_info['options'] = $payload['options'];
 		}
		
		if(!empty($payload['isRequired']) && $payload['isRequired'] == 1){
			$field_info['required'] = true;
		}else{
			$field_info['required'] = false;
		}

		if(!empty($payload['isEnabled']) && $payload['isEnabled'] == 1){
			$field_info['enable'] = true;
		}else{
			$field_info['enable'] = false;
		}

		if(!empty($payload['isShow']) && $payload['isShow'] == 1){
			$field_info['show'] = true;
		}else{
			$field_info['show'] = false;
		}
		
		$field_info['class'] = array('my-field-class form-row-wide');
		
		if( $payload['sectionType'] === 'billing' ){
			$response = $this->wpfn_update_checkout_billing_fields($field_info);
		}elseif( $payload['sectionType'] === 'shipping' ){
			$response = $this->wpfn_update_checkout_shipping_fields($field_info);
		}else{
			$response = $this->wpfn_update_checkout_additional_fields($field_info);
		}

		return $response;
	}

	/**
	 * wpfn_add_checkout_shipping_fields
	 * add checkout field in shipping section
	 * 
	 * @param Array $payload  field data
	 */
	public function wpfn_add_checkout_billing_fields( $payload ){
		
		$billing_fields = get_post_meta( $payload['stepID'],'wpfnl_checkout_billing_fields', true );
		if(!empty($billing_fields)){
			if(isset($billing_fields[$payload['name']])){
				return [
					'status' => 'fail',
					'data'   => 'Name should be unique.'
				];
			}
			if ( preg_match('/\s/',$payload['name']) ){
				return [
					'status' => 'fail',
					'data'   => 'Name should not contain whitespace.'
				];
			}
			$billing_fields[$payload['name']] = $payload;
			update_post_meta( $payload['stepID'], 'wpfnl_checkout_billing_fields', $billing_fields);
		}else{
			$billing_default_field = $this->wpfnl_get_billing_default_data();
			if(isset($billing_default_field[$payload['name']])){
				return [
					'status' => 'fail',
					'data'   => 'Name should be unique.'
				];
			}
			if ( preg_match('/\s/',$payload['name']) ){
				return [
					'status' => 'fail',
					'data'   => 'Name should not contain whitespace.'
				];
			}
			$billing_default_field[$payload['name']] = $payload;
			update_post_meta( $payload['stepID'], 'wpfnl_checkout_billing_fields', $billing_default_field);
		}

		return [
            'status' => 'success',
			'data'   => 'Successfully saved'
        ];
	}

	/**
	 * wpfn_add_checkout_shipping_fields
	 * add checkout field in shipping section
	 * 
	 * @param Array $payload  field data
	 */
	public function wpfn_add_checkout_shipping_fields( $payload ){


		$shipping_fields = get_post_meta($payload['stepID'],'wpfnl_checkout_shipping_fields', true );
		if(!empty($shipping_fields)){
			if(isset($shipping_fields[$payload['name']])){
				return [
					'status' => 'fail',
					'data'   => 'Name should be unique.'
				];
			}
			if ( preg_match('/\s/',$payload['name']) ){
				return [
					'status' => 'fail',
					'data'   => 'Name should not contain whitespace.'
				];
			}
			$shipping_fields[$payload['name']] = $payload;
			update_post_meta( $payload['stepID'], 'wpfnl_checkout_shipping_fields', $shipping_fields);
		}else{
			$shipping_default_fields = $this->wpfnl_get_shipping_default_data();
			if(isset($shipping_default_fields[$payload['name']])){
				return [
					'status' => 'fail',
					'data'   => 'Name should be unique.'
				];
			}
			if ( preg_match('/\s/',$payload['name']) ){
				return [
					'status' => 'fail',
					'data'   => 'Name should not contain whitespace.'
				];
			}
			$shipping_default_fields[$payload['name']] = $payload;
			update_post_meta( $payload['stepID'], 'wpfnl_checkout_shipping_fields', $shipping_default_fields);
		}
		return [
            'status' => 'success',
			'data'   => 'Successfully saved'
        ];
	}

	/**
	 * wpfn_add_checkout_additional_fields
	 * add checkout field in additional section
	 * 
	 * @param Array $payload  field data
	 */
	public function wpfn_add_checkout_additional_fields( $payload ){

		$additional_fields = get_post_meta($payload['stepID'],'wpfnl_checkout_additional_fields', true );
		if(!empty($additional_fields)){
			if(isset($additional_fields[$payload['name']])){
				return [
					'status' => 'fail',
					'data'   => 'Name should be unique.'
				];
			}
			if ( preg_match('/\s/',$payload['name']) ){
				return [
					'status' => 'fail',
					'data'   => 'Name should not contain whitespace.'
				];
			}
			$additional_fields[$payload['name']] = $payload;
			update_post_meta( $payload['stepID'], 'wpfnl_checkout_additional_fields', $additional_fields);
		}else{
			$additional_default_fields = $this->wpfnl_get_additional_default_data();
			if(isset($additional_default_fields[$payload['name']])){
				return [
					'status' => 'fail',
					'data'   => 'Name should be unique.'
				];
			}
			if ( preg_match('/\s/',$payload['name']) ){
				return [
					'status' => 'fail',
					'data'   => 'Name should not contain whitespace.'
				];
			}
			$additional_default_fields[$payload['name']] = $payload;
			update_post_meta( $payload['stepID'], 'wpfnl_checkout_additional_fields', $additional_default_fields);
		}
		return [
            'status' => 'success',
			'data'   => 'Successfully saved'
        ];

		
	}

	/**
	 * wpfn_update_checkout_billing_fields
	 * update checkout field in billing section
	 * 
	 * @param Array $payload  field data
	 */
	public function wpfn_update_checkout_billing_fields( $payload ){
		$billing_fields = get_post_meta($payload['stepID'],'wpfnl_checkout_billing_fields', true );
        if(is_array($billing_fields)){
            if( isset($billing_fields[$payload['name']]['priority']) ){
                $payload['priority'] = $billing_fields[$payload['name']]['priority'];
            }
			$billing_fields[$payload['name']] = $payload;
			update_post_meta( $payload['stepID'], 'wpfnl_checkout_billing_fields', $billing_fields);
		}else{
			$billing_default_field = $this->wpfnl_get_billing_default_data();
            if( $billing_default_field[$payload['name']]['priority'] ){
                $payload['priority'] = $billing_default_field[$payload['name']]['priority'];
            }
			$billing_default_field[$payload['name']] = $payload;
			update_post_meta( $payload['stepID'], 'wpfnl_checkout_billing_fields', $billing_default_field);
		}
		return [
            'status' => 'success',
			'data'   => 'Successfully updated'
        ];
		
	}

	/**
	 * wpfn_update_checkout_shipping_fields
	 * update checkout field in shipping section
	 * 
	 * @param Array $payload  field data
	 */
	public function wpfn_update_checkout_shipping_fields( $payload ){

		$shipping_fields = get_post_meta($payload['stepID'],'wpfnl_checkout_shipping_fields', true );		
		if(is_array($shipping_fields)){
            if( $shipping_fields[$payload['name']]['priority'] ){
                $payload['priority'] = $shipping_fields[$payload['name']]['priority'];
            }
			$shipping_fields[$payload['name']] = $payload;
			update_post_meta( $payload['stepID'], 'wpfnl_checkout_shipping_fields', $shipping_fields);
		}else{
			$shipping_default_field = $this->wpfnl_get_shipping_default_data();
            if( $shipping_default_field[$payload['name']]['priority'] ){
                $payload['priority'] = $shipping_default_field[$payload['name']]['priority'];
            }
			$shipping_default_field[$payload['name']] = $payload;
			update_post_meta( $payload['stepID'], 'wpfnl_checkout_shipping_fields', $shipping_default_field);
		}
		return [
            'status' => 'success',
			'data'   => 'Successfully updated'
        ];
		
	}
	
	/**
	 * wpfn_update_checkout_additional_fields
	 * update checkout field in additional section
	 * 
	 * @param Array $payload  field data
	 */
	public function wpfn_update_checkout_additional_fields( $payload ){

		$additional_fields = get_post_meta($payload['stepID'],'wpfnl_checkout_additional_fields', true );		
		if(is_array($additional_fields)){
            if( $additional_fields[$payload['name']]['priority'] ){
                $payload['priority'] = $additional_fields[$payload['name']]['priority'];
            }
			$additional_fields[$payload['name']] = $payload;
			update_post_meta( $payload['stepID'], 'wpfnl_checkout_additional_fields', $additional_fields);
		}else{
			$additional_default_field = $this->wpfnl_get_additional_default_data();
            if( $additional_default_field[$payload['name']]['priority'] ){
                $payload['priority'] = $additional_default_field[$payload['name']]['priority'];
            }
			$additional_default_field[$payload['name']] = $payload;
			update_post_meta( $payload['stepID'], 'wpfnl_checkout_additional_fields', $additional_default_field);
		}
		return [
            'status' => 'success',
			'data'   => 'Successfully updated'
        ];
		
		
	}

	/**
	 * wpfn_restore_to_default_fields
	 * Resotre to woocommerce default fields 
	 * 
	 * @param Array $payload   Step id
	 */
	public function wpfn_restore_to_default_fields( $payload ){

		update_post_meta( $payload['stepID'], 'wpfnl_checkout_billing_fields', $this->wpfnl_get_billing_default_data() );
		update_post_meta( $payload['stepID'], 'wpfnl_checkout_additional_fields',$this->wpfnl_get_additional_default_data() );
		update_post_meta( $payload['stepID'], 'wpfnl_checkout_shipping_fields',$this->wpfnl_get_shipping_default_data() );
		
		return [
            'status' => 'success',
			'data'   => 'Successfully restored'
        ];
	}

	/**
	 * wpfn_update_field_enable_status
	 * 
	 */
	public function wpfn_update_field_enable_status($payload){

		$field_data = get_post_meta( $payload['stepID'], 'wpfnl_checkout_'.$payload['section'].'_fields', true );
		if(!empty($field_data)){
			if(isset($field_data[$payload['index']])){
				if($field_data[$payload['index']]['enable'] == 1){
					$field_data[$payload['index']]['enable'] = false;
				}else{
					$field_data[$payload['index']]['enable'] = true;
				}
				update_post_meta( $payload['stepID'], 'wpfnl_checkout_'.$payload['section'].'_fields', $field_data);
	
			}
		}else{
			if($payload['section'] == 'billing'){
				$field_data = $this->wpfnl_get_billing_default_data();
			}else if($payload['section'] == 'shipping'){
				$field_data = $this->wpfnl_get_shipping_default_data();
			}else{
				$field_data = $this->wpfnl_get_additional_default_data();
			}

			if(isset($field_data[$payload['index']])){
				if($field_data[$payload['index']]['enable'] == 1){
					$field_data[$payload['index']]['enable'] = false;
				}else{
					$field_data[$payload['index']]['enable'] = true;
				}
				update_post_meta( $payload['stepID'], 'wpfnl_checkout_'.$payload['section'].'_fields', $field_data);
	
			}
		}
		

		return [
            'status' => 'success',
			'data'   => 'Successfully updated'
        ];
	}


	

	/**
	 * wpfn_delete_field
	 */
	public function wpfn_delete_field($payload){

		$field_data = get_post_meta( $payload['stepID'], 'wpfnl_checkout_'.$payload['section'].'_fields', true );
		if(!empty($field_data)){
			unset($field_data[$payload['index']]);
			update_post_meta( $payload['stepID'], 'wpfnl_checkout_'.$payload['section'].'_fields',$field_data);
		}else{
			if($payload['section'] == 'billing'){
				$field_data = $this->wpfnl_get_billing_default_data();
			}else if($payload['section'] == 'shipping'){
				$field_data = $this->wpfnl_get_shipping_default_data();
			}else{
				$field_data = $this->wpfnl_get_additional_default_data();
			}
			unset($field_data[$payload['index']]);
			update_post_meta( $payload['stepID'], 'wpfnl_checkout_'.$payload['section'].'_fields',$field_data);
		}
		

		return [
            'status' => 'success',
			'data'   => 'Successfully deleted'
        ];
	}

	/**
	 * wpfn_update_field_required
	 */
	public function wpfn_update_field_required($payload){

		$field_data = get_post_meta( $payload['stepID'], 'wpfnl_checkout_'.$payload['section'].'_fields', true );
		if(!empty($field_data)){
			if(isset($field_data[$payload['index']])){
				if($field_data[$payload['index']]['required'] == true){
					$field_data[$payload['index']]['required'] = false;
				}else{
					$field_data[$payload['index']]['required'] = true;
				}
				update_post_meta( $payload['stepID'], 'wpfnl_checkout_'.$payload['section'].'_fields', $field_data);
	
			}
		}else{
			if($payload['section'] == 'billing'){
				$field_data = $this->wpfnl_get_billing_default_data();
			}else if($payload['section'] == 'shipping'){
				$field_data = $this->wpfnl_get_shipping_default_data();
			}else{
				$field_data = $this->wpfnl_get_additional_default_data();
			}

			if(isset($field_data[$payload['index']])){
				if($field_data[$payload['index']]['required'] == true){
					$field_data[$payload['index']]['required'] = false;
				}else{
					$field_data[$payload['index']]['required'] = true;
				}
				update_post_meta( $payload['stepID'], 'wpfnl_checkout_'.$payload['section'].'_fields', $field_data);
			}
		}
		
		return [
            'status' => 'success',
			'data'   => 'Successfully updated required fields'
        ];
	}


	/**
	 * wpfnl_get_billing_default_data
	 * get all default billing fields and return all fields
	 */
	function wpfnl_get_billing_default_data(){
		$checkout_instance = new \WC_Checkout();
		$fields = $checkout_instance->get_checkout_fields();
		$billing_default_field = isset( $fields['billing'] ) ? $fields['billing'] : [];
		
		if( $billing_default_field && !empty($billing_default_field) ){
			foreach($billing_default_field as $key => $sf){
				$billing_default_field[$key]['enable'] = 1;
				$billing_default_field[$key]['show'] = 1;
				$billing_default_field[$key]['delete'] = 0;
				if(!(isset($sf['type']))){
					$billing_default_field[$key]['type'] = null;
				}
				if(!(isset($sf['placeholder']))){
					$billing_default_field[$key]['placeholder'] = null;
				}
				if(!(isset($sf['label']))){
					$billing_default_field[$key]['label'] = null;
				}
				if(!(isset($sf['validate']))){
					$billing_default_field[$key]['validate'] = null;
				}
				if(!(isset($sf['default']))){
					$billing_default_field[$key]['default'] = null;
				}
			}
		}
		return 	$billing_default_field;
	}
	
	/**
	 * wpfnl_get_shipping_default_data
	 * get all default shipping fields and return all fields
	 */
	function wpfnl_get_shipping_default_data(){
		
		$checkout_instance = new \WC_Checkout();
		$fields = $checkout_instance->get_checkout_fields();
		
		$shipping_default_fields = isset( $fields['shipping'] ) ? $fields['shipping'] : [];
		// $shipping_default_fields = $countries->get_address_fields( $countries->get_base_country(),'shipping_');
		foreach($shipping_default_fields as $key => $sf){
			$shipping_default_fields[$key]['enable'] = 1;
			$shipping_default_fields[$key]['show'] = 1;
			$shipping_default_fields[$key]['delete'] = 0;
			if(!(isset($sf['type']))){
				$shipping_default_fields[$key]['type'] = null;
			}
			if(!(isset($sf['placeholder']))){
				$shipping_default_fields[$key]['placeholder'] = null;
			}
			if(!(isset($sf['label']))){
				$shipping_default_fields[$key]['label'] = null;
			}
			if(!(isset($sf['validate']))){
				$shipping_default_fields[$key]['validate'] = null;
			}
			if(!(isset($sf['default']))){
				$shipping_default_fields[$key]['default'] = null;
			}
		}
		return 	$shipping_default_fields;
	}
	
	/**
	 * wpfnl_get_additional_default_data
	 * get all default additional fields and return all fields
	 */
	function wpfnl_get_additional_default_data(){
		$checkout_instance = new \WC_Checkout();
		$fields = $checkout_instance->get_checkout_fields();
		$additional_default_fields = isset( $fields['order'] ) ? $fields['order'] : [];
		foreach( $additional_default_fields as  $key=>$field ){
			$additional_default_fields[$key]['required']    = false;
			$additional_default_fields[$key]['enable']      = 1;
			$additional_default_fields[$key]['show']        = 1;
			$additional_default_fields[$key]['default']     = null;
			$additional_default_fields[$key]['validate']    = null;
			$additional_default_fields[$key]['delete']      = 0;
		}
		return 	$additional_default_fields;
	}

	/**
	 * Update edit fields order
	 * 
	 * @param $payload
	 * 
	 */
	public function wpfn_update_field_order($payload){
	
		$field_data = get_post_meta( $payload['stepID'], 'wpfnl_checkout_'.$payload['index'].'_fields', true );
        if( empty($field_data) ){
			$get_deafult_field = 'wpfnl_get_'.$payload['index'].'_default_data';
            $field_data = $this->$get_deafult_field();
        }
		$orders = $payload['order'];
		$updatedOrder = array();
		$i = 1;
		foreach($orders as $order){
			$updatedOrder[$order] 				= $field_data[$order];
			$updatedOrder[$order]['priority'] 	= $i;
			$i++;
		}
		
		update_post_meta( $payload['stepID'], 'wpfnl_checkout_'.$payload['index'].'_fields', $updatedOrder);
		return [
            'status' => 'success',
			'data'   => 'Successfully updated'
        ];
	}


	/**
	 * Update edit field additional settings
	 * 
	 * @param Array $payload
	 * 
	 */
	public function wpfnl_update_additional_settings( $payload ){

		if( $payload['step_id'] ){
			$step_id = $payload['step_id'];
			unset($payload['step_id']);
			update_post_meta( $step_id, '_wpfunnels_edit_field_additional_settings', $payload );

			return [
				'status'  => 'success',
				'message' => 'Saved successful',
			];
		}
		return [
			'status'  => 'fail',
			'message' => 'Saved fail',
		];
	}
	
}
