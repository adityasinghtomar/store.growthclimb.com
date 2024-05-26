<?php
namespace WPFunnelsPro\Frontend\Modules\Checkout\EditField;

use WC_Countries;
use WPFunnels\Frontend\Module\Wpfnl_Frontend_Module;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;
use function cli\err;


class WPFunnel_Edit_field
{
    public function __construct()
    {
        if( Wpfnl_functions::is_wc_active() ){
            add_action('woocommerce_after_order_notes', [ $this, 'wpfnl_add_custom_checkout_field_for_additional_section'],999);
            add_action('woocommerce_checkout_update_order_meta', [ $this, 'wpfnl_custom_checkout_field_update_order_meta'],9999);

            add_action('woocommerce_admin_order_data_after_billing_address', [ $this,'wpfnl_display_additional_field_custom'],9999);
            add_action('woocommerce_admin_order_data_after_billing_address', [ $this,'wpfnl_display_billing_field_custom'],9999);
            add_action('woocommerce_admin_order_data_after_shipping_address', [ $this,'wpfnl_display_shipping_field_custom'],9999);
            
            // add_filter('woocommerce_order_formatted_shipping_address', [ $this,'wpfnl_display_extra_field_value'],10,2);
            add_filter( 'woocommerce_order_formatted_billing_address' , [ $this,'wpfnl_display_extra_field_value_in_billing'], 9999, 2 );
            add_filter( 'woocommerce_order_formatted_shipping_address' , [ $this,'wpfnl_display_extra_field_value_in_shipping'], 9999, 2 );

            add_filter( 'woocommerce_localisation_address_formats' , [ $this,'wpfnl_localisation_address_formats'], 9999, 1 );
            add_filter('woocommerce_formatted_address_replacements', [ $this,'wpfnl_address_replacements'],9999,2);
        

            add_filter('woocommerce_get_country_locale_default', [$this, 'unset_default_priority'],9999);
            add_action('woocommerce_checkout_process', [ $this,'wpfnl_custom_checkout_field_process'],9999);
            add_filter('woocommerce_checkout_fields', [ $this,'wpfnl_add_custom_checkout_field'],9999);

            add_filter('woocommerce_get_country_locale_default', array($this, 'wpfnl_prepare_country_locale'));
            add_filter('woocommerce_get_country_locale_base', array($this, 'wpfnl_prepare_country_locale'));
            add_filter('woocommerce_get_country_locale', array($this, 'wpfnl_get_country_locale'));
        }
    }

    /**
     * Get country local value
     * @param array country local
     */

    public function wpfnl_get_country_locale($locale) {
        if( !Wpfnl_functions::is_wc_active() ){
            return false;
        }
        $countries = array_merge( WC()->countries->get_allowed_countries(), WC()->countries->get_shipping_countries() );
        $countries = array_keys($countries);
        if(is_array($locale) && is_array($countries)){
            foreach($countries as $country){
                if(isset($locale[$country])){
                    $locale[$country] = $this->wpfnl_prepare_country_locale($locale[$country]);
                }
            }
        }

        return $locale;
    }

    /**
     * Prepare country local value for custom edit
     * @param array country local fields
     */
    public function wpfnl_prepare_country_locale($fields) {
        if(is_array($fields)){
            foreach($fields as $key => $props){
                if( isset($props['label'])){
                    unset($fields[$key]['label']);
                }

                if( isset($props['placeholder'])){
                    unset($fields[$key]['placeholder']);
                }

                if( isset($props['class'])){
                    unset($fields[$key]['class']);
                }

                if( isset($props['priority'])){
                    unset($fields[$key]['priority']);
                }
            }
        }
        return $fields;
    }
    /**
     * add custom checkout field for additional section
     * @param array checkout for field
     */
    public function wpfnl_add_custom_checkout_field_for_additional_section($checkout)
    {
        $get_custom_data = get_post_meta(get_the_ID(), 'wpfnl_checkout_additional_fields', true);
        $custom_field_count = 0;
        if( is_array($get_custom_data) && !empty($get_custom_data) ){
            foreach($get_custom_data as $key=>$field_data){
                if( 'order_comments' !== $key && $field_data['enable'] == 1){
                    $custom_field_count ++;
                }
            }
            if(!$custom_field_count) return false;
            echo '<div class="woocommerce-additional-fields__field-wrapper extra-field">';
            foreach($get_custom_data as $key=>$field_data){
                $tcf_option = array();
                if( isset($field_data['type']) && ($field_data['type'] == 'radio' || $field_data['type'] == 'select' || $field_data['type'] == 'checkbox')){
                    $cf_option = [];
                    if (isset($field_data['type']) && ($field_data['type'] == 'select' || $field_data['type'] == 'radio')) {
                        for ($i=0; $i < count($field_data['options']); $i++) {
                            $cf_option[$field_data['options'][$i]['optionValue']] = $field_data['options'][$i]['optionTitle'];
                        }
                    }
                }

                if( !('order_comments' == $key )){
                    if($field_data['enable'] == 1){
                        if( $field_data['type'] == 'radio' || $field_data['type'] == 'select' || $field_data['type'] == 'checkbox' ){
                            if( $field_data['type'] == 'radio' || $field_data['type'] == 'checkbox'){
                                woocommerce_form_field( ($field_data['name']), $this->set_field_for_additional_section($field_data,$cf_option), $checkout->get_value( ($field_data['name'] )));
                            }else{
                                $default_select = array("" => "Please Select...");
                                $new_arr = $default_select+$cf_option;
                                woocommerce_form_field( ($field_data['name']),$this->set_field_for_additional_section($field_data,$new_arr), $checkout->get_value( ($field_data['name'] )));
                            }
                        }else{
                            woocommerce_form_field( $field_data['name'],$this->set_field_for_additional_section($field_data) , $checkout->get_value( $field_data['name'] ));
                        }
                    }
                }

            }
            echo '</div>';
        }
    }


    /**
     * Update the order meta with field value
     * @param int order_id for update order meta by order id
     */

    public function wpfnl_custom_checkout_field_update_order_meta($order_id)
    {
        $step_id = $this->get_step_id();
        
        if( $step_id ){
            $get_custom_data = get_post_meta($step_id, 'wpfnl_checkout_additional_fields', true);
            $get_billing_data = get_post_meta($step_id, 'wpfnl_checkout_billing_fields', true);
            $shipping_updated_field = get_post_meta($step_id, 'wpfnl_checkout_shipping_fields', true);
            
            if ($get_custom_data) {
                foreach ($get_custom_data as $custom_data) {
                    if(isset($custom_data['name'], $_POST[$custom_data['name']] )){
                        update_post_meta($order_id, $custom_data['name'], sanitize_text_field($_POST[$custom_data['name']]));
                        update_post_meta($step_id, $custom_data['name'], sanitize_text_field($_POST[$custom_data['name']]));
                    }
                    
                }
            }

            if ($get_billing_data) {
                foreach ($get_billing_data as $key => $value) {
                    if(isset($value['name'], $_POST[$value['name']])){
                       
                        update_post_meta($order_id, $key, sanitize_text_field($_POST[$value['name']]));
                        update_post_meta($step_id, $key, sanitize_text_field($_POST[$value['name']]));
                    }
                }
            }

            if ($shipping_updated_field) {
                foreach ($shipping_updated_field as $key => $value) {
                    if(isset($value['name'], $_POST[$value['name']])){
                        update_post_meta($order_id, $key, sanitize_text_field($_POST[$value['name']]));
                        update_post_meta($step_id, $key, sanitize_text_field($_POST[$value['name']]));
                    }
                }
            }
        }
        
    }
    /**
     * wpfnl_display_additional_field_custom
     * Display additional field value on the order edit page
     *
     * @param array order for get order_id
     */
    public function wpfnl_display_additional_field_custom($order)
    {
        $step_id = Wpfnl_functions::get_checkout_id_from_order($order->get_id());

        if( $step_id ){
            $get_custom_data = get_post_meta($step_id, 'wpfnl_checkout_additional_fields', true);
            if ($get_custom_data) {
                foreach ($get_custom_data as $gccd=>$value) {
                    if ($value['show'] == 1) {
                        
                        echo '<p><strong>'.($value['label']).':</strong > ' . get_post_meta($order->get_id(), $gccd, true) . '</p>';
                    }
                }
            }
        }
    }
    /**
     * wpfnl_display_shipping_field_custom
     * Display shipping field value on the order edit page
     *
     * @param array order for get order_id
     */
    public function wpfnl_display_shipping_field_custom($order)
    {
        $step_id = Wpfnl_functions::get_checkout_id_from_order($order->get_id());
        if( $step_id ){
            $get_shipping_data = get_post_meta($step_id, 'wpfnl_checkout_shipping_fields', true);
            $countries = new WC_Countries();
            $get_shipping_default_data = $countries->get_address_fields($countries->get_base_country(), 'shipping_');
            $key_array = [];
            foreach ($get_shipping_default_data as $key=> $avlue) {
                array_push($key_array, $key);
            }
            if ($get_shipping_data) {
                foreach ($get_shipping_data as $gccd => $value) {
                    if (!(in_array($gccd, $key_array))) {
                        if ($value['show'] == 1) {
                            echo '<p><strong>'.__($value['label']).':</strong> ' . get_post_meta($order->get_id(), $gccd, true) . '</p>';
                        }
                    }
                }
            }
        }
    }
    /**
     * wpfnl_display_billing_field_custom
     * Display billing field value on the order edit page
     *
     * @param array order for get order_id
     */
    public function wpfnl_display_billing_field_custom($order)
    {
        $step_id = Wpfnl_functions::get_checkout_id_from_order($order->get_id());
        if( $step_id ){
            $get_billing_data = get_post_meta($step_id, 'wpfnl_checkout_billing_fields', true);
            $get_billing_default_data = WC()->countries->get_address_fields();

            $key_array = [];
            foreach ($get_billing_default_data as $key=> $value) {
                array_push($key_array, $key);
            }
           
            if ($get_billing_data) {
                foreach ($get_billing_data as $gccd => $value) {
                    
                    if (!(in_array($gccd, $key_array))) {
                        if ($value['show'] == 1) {
                            echo '<p><strong>'.__($value['label']).':</strong> ' . get_post_meta($order->get_id(), $gccd, true) . '</p>';
                        }
                    }
                }
            }
        }else{
            return false;
        }
    }
    /**
     * wpfnl_custom_checkout_field_process
     * Process the checkout notice for additional field
     */
    public function wpfnl_custom_checkout_field_process()
    {
        if (isset($_POST['_wpfunnels_checkout_id'])){
            $get_custom_data = get_post_meta($_POST['_wpfunnels_checkout_id'], 'wpfnl_checkout_additional_fields', true);

            if ( $get_custom_data != '' ){
                $this->wpfnl_custom_fields_validation_notice($get_custom_data);
            }
        }
    }

    
    
    /**
     * Get checkout field error  notice
     * @param $get_field_data
     */
    public function wpfnl_custom_fields_validation_notice($get_field_data){
        if ($get_field_data) {
            foreach ($get_field_data as $gccd) {
                if ($gccd['required'] == 1 && $gccd['enable'] == true) {
                    if (! $_POST[$gccd['name']] || $_POST[$gccd['name']] == "") {
                        wc_add_notice(__('Please enter something into this '.$gccd['label'].' field.'), 'error');
                    }
                }
            }
        }
    }

    /**
     *  Step id is get_the_ID() if Checkout is not enable ajax
     * @return  int
     */

    public function get_step_id()
    {
        if (isset($_POST['_wpfunnels_checkout_id'])){
            $step_id = $_POST['_wpfunnels_checkout_id'];
        }else{
            $step_id = get_the_ID();
        }
        return $step_id;
    }

    /**
     * wpfnl_add_fields
     * add billing,shipping field in checkout page
     *  @param array fields of all fields
     */
    public function wpfnl_add_custom_checkout_field($fields){
        if( Wpfnl_functions::is_funnel_step_page() ) {
            $step_id = $this->get_step_id();
        } else {
            if ( Wpfnl_Pro_functions::is_doing_ajax() ) {
                $step_id = Wpfnl_functions::get_checkout_id_from_post_data();
            } else {
                return $fields;
            }
        }
        
        $get_additional_data = get_post_meta( $step_id, 'wpfnl_checkout_additional_fields', true );

        if($get_additional_data) {
            if( !isset($get_additional_data['order_comments']) ){
                unset($fields['order']['order_comments']);
                add_filter( 'woocommerce_enable_order_notes_field', '__return_false', 9999 );
            }
            else{
                if($get_additional_data['order_comments']['enable'] == 0){
                    unset($fields['order']['order_comments']);
                    add_filter( 'woocommerce_enable_order_notes_field', '__return_false', 9999 );
                }else{
                    $i = 10;
                    foreach($get_additional_data as $key => $updated_field){
                        if( $key == 'order_comments' ){
                            $fields['order'][$key] = $this->set_checkout_field_for_order_comment($updated_field, '', $i);
                        }
                    }
                }
            }
        }


        /**
         * for billing
         */
        $fields = $this->billing_checkout_fields($step_id,$fields);
        /**
         * for shipping
         */
        $fields = $this->shipping_checkout_fields($step_id,$fields);

        if( isset($fields['billing']['billing_state']) ){
            $country_code = isset($_POST['billing_country']) ? sanitize_text_field($_POST['billing_country']) : '';
            if( $country_code ){
                $is_state_less = $this->is_country_stateless($country_code);
                $fields['billing']['billing_state']['required'] = !$is_state_less;
            }
        }
        return $fields;
    }


    /**
     * Check if a country is stateless.
     * This function checks whether the selected country is stateless, 
     * meaning it does not have any associated states/provinces.
     * 
     * @param string $country_code The country code.
     * 
     * @return bool Returns true if the country is stateless, false otherwise.
     * 
     * @since 1.9.3
     */
    public function is_country_stateless( $country_code ){
        $wc_countries = WC()->countries;
        $countries = $wc_countries->get_countries();
        $states = $wc_countries->get_states();
        $countries_without_states = array();
        
        if( is_array($countries) ){
            foreach ($countries as $code => $name) {
                if (!isset($states[$code]) || empty($states[$code])) {
                    $countries_without_states[$code] = $name;
                }
            }
        }
        return array_key_exists($country_code, $countries_without_states) ? true : false;
    }


    /**
     * @param $updated_field
     * @param $options
     * @return array
     */

    public function set_checkout_field($updated_field, $options = '', $priority = '', $key = '' )
    {
     
        $name = preg_replace('/[0-9\/\,\.\;\" "]+/', ' ', strtolower($updated_field['label']));
        $name = str_replace(" ","-",($name));

        if( isset($updated_field['validate']) && !is_array($updated_field['validate']) ){
            $updated_field['validate'] = explode(" ",$updated_field['validate']);
        }
        $updated_field['validate'] = is_array($updated_field['validate']) ? $updated_field['validate'] : explode(" ",$updated_field['validate']);


        $type = '';
        if( 'billing_country' == $key || 'shipping_country' == $key ){
            $type = 'country';
        }elseif( 'billing_state' == $key || 'shipping_state' == $key ){
            $type = 'state';
        }elseif( isset($updated_field['type']) && '' !== $updated_field['type'] ){
            $type = $updated_field['type'];
        }else{
            $type = 'text';
        }

        
        $class = $key == 'billing_country' || $key == 'billing_state' || $key == 'billing_postcode' || $key == 'billing_state' || $key == 'billing_address_1' || $key == 'billing_address_2' ? array('form-row-wide', 'address-field', 'wpfnl-form-row', $name, 'field-'.$updated_field['type'].'') : array('form-row-wide','wpfnl-form-row', $name, 'field-'.$updated_field['type'].'');
      
        // if( is_array($updated_field['class'] ) ){
        //     $class = array_merge($class, $updated_field['class'] );
        // }
        $fields =   array(
            'type'          => $type,
            'label'         => isset($updated_field['label']) ? $updated_field['label'] : '',
            'placeholder'   => isset($updated_field['placeholder']) ? $updated_field['placeholder'] : '',
            'required'      => $updated_field['required'],
            'class'         => $class,
            'priority'      => isset($updated_field['priority']) ? $updated_field['priority'] : 1000,
            'clear'         => true,
            'default'       => isset($updated_field['default']) ? $updated_field['default'] : '',
            'validate'      => isset($updated_field['validate']) ? $updated_field['validate'] : '',
            'options'       => $options,
        );

        $cookie_name        = 'wpfunnels_send_data_checkout';
        $cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
        
        if(!empty($cookie)){
            if(isset($cookie['after_optin_submit_send_for_checkout'])){
                $first_name  	= isset($cookie['after_optin_submit_send_for_checkout']['first_name']) ? $cookie['after_optin_submit_send_for_checkout']['first_name'] : '';
                $last_name  	= isset($cookie['after_optin_submit_send_for_checkout']['last_name']) ? $cookie['after_optin_submit_send_for_checkout']['last_name'] : '';
                $phone   		= isset($cookie['after_optin_submit_send_for_checkout']['phone']) ? $cookie['after_optin_submit_send_for_checkout']['phone'] : '';
                $email   		= isset($cookie['after_optin_submit_send_for_checkout']['email']) ? $cookie['after_optin_submit_send_for_checkout']['email'] : '';
                if( 'billing_first_name' === $key ){
                    $fields['default'] = $first_name;
                }elseif( 'billing_last_name' === $key ){
                    $fields['default'] = $last_name;
                }elseif( 'billing_phone' === $key ){
                    $fields['default'] = $phone;
                }elseif( 'billing_email' === $key ){
                    $fields['default'] = $email;
                }
            }
        }
        
        return $fields;

    }


    


    /**
     * @param $updated_field
     * @param $options
     * @return array
     */

    public function set_checkout_field_for_order_comment( $updated_field, $options = '', $priority = 10 )
    {
        $name = preg_replace('/[0-9\/\,\.\;\" "]+/', ' ', strtolower($updated_field['label']));
        $name = str_replace(" ","-",($name));
        if( isset($updated_field['validate']) && !is_array($updated_field['validate']) ){
            $updated_field['validate'] = explode(" ",$updated_field['validate']);
        }
        $updated_field['validate'] = is_array($updated_field['validate']) ? $updated_field['validate'] : explode(" ",$updated_field['validate']);
        $fields =   array(
            'type'          => isset($updated_field['type']) && $updated_field['type'] !== '' ? $updated_field['type'] : 'textarea',
            'label'         => isset($updated_field['label']) ? $updated_field['label'] : '',
            'placeholder'   => isset($updated_field['placeholder']) ? $updated_field['placeholder'] : '',
            'required'      => $updated_field['required'],
            'class'         => array('form-row-wide', 'wpfnl-form-row', $name, 'field-'.$updated_field['type'].''),
            'priority'      => isset($updated_field['priority']) ? $updated_field['priority'] : 1000,
            'clear'         => true,
            'default'       => isset($updated_field['default']) ? $updated_field['default'] : '',
            'validate'      => isset($updated_field['validate']) ? $updated_field['validate'] : '',
            'options'      => $options,
        );
        
        return $fields;

    }


    /**
     * @param $field_data
     * @param string $options
     * @return array
     */
    public function set_field_for_additional_section ($field_data, $options = '')
    {
        $name = preg_replace('/[0-9\/\,\.\;\" "]+/', ' ', strtolower($field_data['label']));
        $name = str_replace(" ","-",($name));
        $fields = array(
            'type'          => isset($field_data['type']) && $field_data['type'] != '' ? $field_data['type'] : 'text',
            'class'         => array('form-row-wide', 'wpfnl-form-row', $name, 'field-'.$field_data['type'].''),
            'clear'         => true,
            'label'         => __($field_data['label']),
            'placeholder'   => __($field_data['placeholder']),
            'required'      => $field_data['required'],
            'priority'      =>  isset($field_data['priority']) ? $field_data['priority'] : 1000,
            'default'       =>  __($field_data['default']),
            'options'       =>    $options,
            'validate'      =>  explode(" ",$field_data['validate'])
        );

        return $fields;
    }

    /**
     * @param $step_id
     * @param $fields
     * @return array
     */
    public function billing_checkout_fields($step_id,$fields)
    {
		$billing_default_field = isset( $fields['billing'] ) ? $fields['billing'] : [];
        $billing_updated_field = get_post_meta($step_id, 'wpfnl_checkout_billing_fields', true);
        $billing_updated_field_check = $billing_updated_field;
        if (is_array($billing_updated_field)) {
            $formatted_fields = array();
            
            foreach($billing_updated_field as $key => $updated_field){
                
                if($updated_field['enable'] == 1){
                    $i = 10;
                    if(isset($billing_default_field[$key]) != isset($billing_updated_field_check[$key])){
                        $default_select = array("" => "Please Select...");
                        $cf_option = [];
                        if (isset($updated_field['type']) && ( $updated_field['type'] == 'select' || $updated_field['type'] == 'multiselect' || $updated_field['type'] == 'radio') ) {
                            for ($i=0; $i < count($updated_field['options']); $i++) {
                                $cf_option[$updated_field['options'][$i]['optionValue']] = $updated_field['options'][$i]['optionTitle'];
                            }
                        }
                        
                        if( isset($updated_field['type']) && $updated_field['type'] == 'select' || $updated_field['type'] == 'multiselect'){
                            $formatted_fields[$key] = $this->set_checkout_field($updated_field,$default_select+$cf_option, $i , $key);
                        }
                        elseif( isset($updated_field['type']) && ($updated_field['type'] == 'radio' || $updated_field['type'] == 'checkbox')){
                            $formatted_fields[$key] = $this->set_checkout_field($updated_field,$cf_option, $i, $key);
                        }
                        else{
                            $formatted_fields[$key] = $this->set_checkout_field($updated_field,'', $i, $key);
                        }
                    }
                    else{
                        $default_select = array("" => "Please Select...");
                        $cf_option = [];
                        if (isset($updated_field['type']) && ( $updated_field['type'] == 'select' || $updated_field['type'] == 'multiselect' || $updated_field['type'] == 'radio') ) {
                            for ($i=0; $i < count($updated_field['options']); $i++) {
                                $cf_option = $updated_field['options'];
                            }
                        }
                        if( isset($updated_field['type']) && $updated_field['type'] == 'select' || $updated_field['type'] == 'multiselect'){
                            $formatted_fields[$key] = $this->set_checkout_field($updated_field,$cf_option, $i , $key);
                        }else{
                            $formatted_fields[$key] = $this->set_checkout_field($updated_field,'',$i, $key);
                        }
                        
                    }
                    $i += 10;
                }
                else{
                    
                    unset($formatted_fields[$key]);
                }
                
                
            }
            
            $fields['billing'] = $formatted_fields;
            
        }
        return $fields;

    }

    /**
     * @param $step_id
     * @param $fields
     * @return array
     */
    public function shipping_checkout_fields($step_id,$fields)
    {

        $countries = new WC_Countries();
        $shipping_default_field = $countries->get_address_fields($countries->get_base_country(), 'shipping_');
        $shipping_updated_field = get_post_meta($step_id, 'wpfnl_checkout_shipping_fields', true);
        $shipping_updated_field_check = $shipping_updated_field;
        
        if (is_array($shipping_updated_field)) {
            $formatted_fields = array();
            foreach($shipping_updated_field as $key => $updated_field){
                if( $updated_field['enable'] == 1){
                    
                    if(isset($shipping_default_field[$key]) != isset($shipping_updated_field_check[$key])){
                        $default_select = array("" => "Please Select...");
                        $cf_option = [];
                        if (isset($updated_field['type']) && ($updated_field['type'] == 'select' || $updated_field['type'] == 'radio') ) {
                            for ($i=0; $i < count($updated_field['options']); $i++) {
                                $cf_option[$updated_field['options'][$i]['optionValue']] = $updated_field['options'][$i]['optionTitle'];
                            }
                        }
                        if($updated_field['type'] == 'select'){
                            $formatted_fields[$key] = $this->set_checkout_field($updated_field,$default_select + $cf_option , '' ,$key);
                        }elseif($updated_field['type'] == 'radio' || $updated_field['type'] == 'checkbox'){
                            $formatted_fields[$key] = $this->set_checkout_field($updated_field,$cf_option, '', $key);
                        }else{
                            
                            $formatted_fields[$key] = $this->set_checkout_field($updated_field,'','', $key);
                        }
                    }else{
                        
                        $formatted_fields[$key] = $this->set_checkout_field($updated_field, '', '', $key);
                    }
                }
                else{
                    unset($formatted_fields[$key]);
                }
            }
            $fields['shipping'] = $formatted_fields;
        }
        return $fields;

    }

    /**
     * Unset default priority of checkout fields
     * 
     * @param $fields
     * @return $fields 
     */
    public function unset_default_priority($fields) {
		if(is_array($fields)){
			foreach($fields as $key => $props){
               if( isset($fields[$key]['priority']) ){
                    unset($fields[$key]['priority']);
               }
			}
		}
		return $fields;
	}


    /**
     * Add custom address field value for billing section in thankyou page
     * 
     * @param $address, $WC_order
     * 
     */
    public function wpfnl_display_extra_field_value_in_billing( $address, $WC_Order ) {
        $address = $this->add_billing_custom_field_value_in_thankyou( 'address', '' , '', '' , $address, $WC_Order );
        return $address;
    }
    

    /**
     * Add custom address field value for shipping section in thankyou page
     * 
     * @param $address, $WC_order
     * 
     */
    public function wpfnl_display_extra_field_value_in_shipping( $address, $WC_Order ) {
        $address = $this->add_shipping_custom_field_value_in_thankyou( 'address', '' , '', '' , $address, $WC_Order );
        return $address;
    }


    /**
     * Adress replacement for thank you page
     * 
     * @param $replacements, $args
     * 
     */
    public function wpfnl_address_replacements( $replacements, $args ){
        $replacements = $this->add_custom_field_value_in_thankyou( 'replacement', '' , $replacements, $args,'', '' );
        return $replacements;
    }


    /**
     * Change address format for custom checkout fields in thankyou page
     * 
     * @param $address-formats
     */
    public function wpfnl_localisation_address_formats( $address_formats ){
        $address_formats['ES'] = "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}"; // Address format for Spain
        $address_formats['IN'] = "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}"; // Address format for India
        $address_formats['default'] = "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}"; // default address format
        $address_formats = $this->add_custom_field_value_in_thankyou( 'address_format', $address_formats, '', '','','' );
        return $address_formats;
    }


    /**
     * Add custom field in thankyou page
     * 
     * @param $change_type, $address_formats = '', $replacements = '', $args = '', $address='', $WC_Order=''
     */
    private function add_custom_field_value_in_thankyou( $change_type, $address_formats = '', $replacements = '', $args = '', $address='', $WC_Order='' ){

        $is_thankyou = Wpfnl_functions::check_if_this_is_step_type('thankyou');
        if( !$is_thankyou ){
            if( $change_type == 'address_format' ){
                return $address_formats;
            }
            if( $change_type == 'replacement' ){
                return $replacements;
            }
            if( $change_type == 'address' ){
                return $address;
            }
        }
        $funnel_id = Wpfnl_functions::get_funnel_id_from_step( get_the_ID() );
        $steps = Wpfnl_functions::get_steps( $funnel_id );
        $key = array_search('checkout', array_column($steps, 'step_type'));
        
        $step_id = $steps[$key]['id'];
        if( $step_id ){

            //for billing
            $get_billing_data = get_post_meta($step_id, 'wpfnl_checkout_billing_fields', true);
            $get_billing_default_data = WC()->countries->get_address_fields();
            $key_array = [];
            foreach ($get_billing_default_data as $key=> $value) {
                array_push($key_array, $key);
            }
           
            if ($get_billing_data) {
                foreach ($get_billing_data as $gccd => $value) {
                    
                    if (!(in_array($gccd, $key_array))) {
                        if ($value['show'] == 1) {
                            if( $change_type == 'address_format'){
                                $address_formats['default'] .= "\n{".$gccd."}";
                            }
                            if( $change_type == 'replacement' ){
                                $replacements['{'.$gccd.'}'] = isset($args[$gccd]) ? $args[$gccd] : '';
                            }
                            if( $change_type == 'address' ){
                                $address[$gccd] = get_post_meta($step_id, $gccd, true);
                            }
                        }
                    }
                }
            }

            //for shipping
            $get_shipping_data = get_post_meta($step_id, 'wpfnl_checkout_shipping_fields', true);
            $countries = new WC_Countries();
            $get_shipping_default_data = $countries->get_address_fields($countries->get_base_country(), 'shipping_');
            $key_array = [];
            foreach ($get_shipping_default_data as $key=> $avlue) {
                array_push($key_array, $key);
            }
            if ($get_shipping_data) {
                foreach ($get_shipping_data as $gccd => $value) {
                    if (!(in_array($gccd, $key_array))) {
                        
                        if( $change_type == 'address_format'){
                            $address_formats['default'] .= "\n{".$gccd."}";
                        }
                        if( $change_type == 'replacement' ){
                            $replacements['{'.$gccd.'}'] = isset($args[$gccd]) ? $args[$gccd] : '';
                        }
                        if( $change_type == 'address' ){
                            $address[$gccd] = get_post_meta($step_id, $gccd, true);
                        }
                        
                    }
                }
            }


            if( $change_type == 'address_format'){
                return $address_formats;
            }
            if( $change_type == 'replacement' ){
                return $replacements;
            }
            if( $change_type == 'address' ){
                return $address;
            }
        }


    }


    /**
     * Add address field for shipping
     * 
     * @param $change_type, $address_formats = '', $replacements = '', $args = '', $address='', $WC_Order=''
     */
    private function add_shipping_custom_field_value_in_thankyou( $change_type, $address_formats = '', $replacements = '', $args = '', $address='', $WC_Order='' ){

        $is_thankyou = Wpfnl_functions::check_if_this_is_step_type('thankyou');
        if( !$is_thankyou ){
            
            if( $change_type == 'address' ){
                return $address;
            }
        }
        $funnel_id = Wpfnl_functions::get_funnel_id_from_step( get_the_ID() );
        $steps = Wpfnl_functions::get_steps( $funnel_id );
        $key = array_search('checkout', array_column($steps, 'step_type'));
        
        $step_id = $steps[$key]['id'];
        if( $step_id ){

            //for shipping
            $get_shipping_data = get_post_meta($step_id, 'wpfnl_checkout_shipping_fields', true);
            $countries = new WC_Countries();
            $get_shipping_default_data = $countries->get_address_fields($countries->get_base_country(), 'shipping_');
            $key_array = [];
            foreach ($get_shipping_default_data as $key=> $avlue) {
                array_push($key_array, $key);
            }
            if ($get_shipping_data) {
                foreach ($get_shipping_data as $gccd => $value) {
                    if (!(in_array($gccd, $key_array))) {
                        
                        
                        if( $change_type == 'address' ){
                            $address[$gccd] = get_post_meta($step_id, $gccd, true);
                        }
                       
                    }
                }
            }

            if( $change_type == 'address' ){
                return $address;
            }
        }
    }

    /**
     * Add address field for billing
     * 
     * @param $change_type, $address_formats = '', $replacements = '', $args = '', $address='', $WC_Order=''
     */
    private function add_billing_custom_field_value_in_thankyou( $change_type, $address_formats = '', $replacements = '', $args = '', $address='', $WC_Order='' ){

        $is_thankyou = Wpfnl_functions::check_if_this_is_step_type('thankyou');
        if( !$is_thankyou ){
            
            if( $change_type == 'address' ){
                return $address;
            }
        }
        $funnel_id = Wpfnl_functions::get_funnel_id_from_step( get_the_ID() );
        $steps = Wpfnl_functions::get_steps( $funnel_id );
        $key = array_search('checkout', array_column($steps, 'step_type'));
        
        $step_id = $steps[$key]['id'];
        if( $step_id ){

            $get_billing_data = get_post_meta($step_id, 'wpfnl_checkout_billing_fields', true);
            $get_billing_default_data = WC()->countries->get_address_fields();
            $key_array = [];
            foreach ($get_billing_default_data as $key=> $value) {
                array_push($key_array, $key);
            }
        
            if ($get_billing_data) {
                foreach ($get_billing_data as $gccd => $value) {
                    
                    if (!(in_array($gccd, $key_array))) {
                        if ($value['show'] == 1) {
                            
                            if( $change_type == 'address' ){
                                $address[$gccd] = get_post_meta($step_id, $gccd, true);
                            }
                        }
                    }
                }
            }
            
            if( $change_type == 'address' ){
                return $address;
            }
        }
    }


}

