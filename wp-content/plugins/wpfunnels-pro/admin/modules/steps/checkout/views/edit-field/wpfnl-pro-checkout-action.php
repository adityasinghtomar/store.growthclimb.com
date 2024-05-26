<?php    
/**
 * add field
 */
if(isset($_POST['wpfnl_submit'])){
    $cf['type'] = $_POST['wpfnl_type'];
    if(isset($_POST['wpfnl_label'])){
        $cf['label'] = $_POST['wpfnl_label'];
    }else{
        $cf['label'] = null;
    }
    if($_POST['wpfnl_name']){
        $cf['name'] = $_POST['wpfnl_name'];
    }else{
        $cf['name'] = null;
    }
    if(isset($_POST['wpfnl_placeholder'])){
        $cf['placeholder'] = $_POST['wpfnl_placeholder'];
    }else{
        $cf['placeholder'] = null;
    }
    if(isset($_POST['wpfnl_default'])){
        $cf['default'] = $_POST['wpfnl_default'];
    }else{
        $cf['default'] = null;
    }
    $cf['delete'] = 0;
    if(isset($_POST['validate'])){
        $cf['validate'] = $_POST['validate'];
    }else{
        $cf['validate'] = null;
    }
    if(isset($_POST['wpfnl_edit_type_option'])){
        $number = count($_POST['wpfnl_edit_type_option']);
        if( $number > 0 ){
            for($i = 0; $i< $number; $i++){
                $option_value = str_replace(' ', '_', $_POST['wpfnl_edit_type_option'][$i]);
                $cf['option_text'][$i] =  $_POST['wpfnl_edit_type_option'][$i];
                $cf['option_value'][$i] =  $option_value;
            }
        }else{
            echo __('please enter option value with option text','checkout-field-editor');
        }
    }
    $cf['class'] = array('my-field-class form-row-wide');
    if(isset($_POST['wpfnl_required'])){
        $cf['required'] = true;
    }else{
        $cf['required'] = false;
    }
    if(isset($_POST['wpfnl_show'])){
        $cf['show'] = 1;
    }else{
        $cf['show'] = 0;
    }

    if(isset($_POST['wpfnl_enable'])){
        $cf['enable'] = 1;
    }else{
        $cf['enable'] = 0;
    }
    // additional field add
    if($_POST['hidden_field'] == 'additional'){
        $get_data = get_option( 'wpfnl_custom_fields_additional');
        if($get_data){
            if(!(in_array($_POST['wpfnl_name'], $get_data))){
                $get_data[$_POST['wpfnl_name']] = $cf;
                update_option( 'wpfnl_custom_fields_additional', $get_data );
            }else{ 
                $get_data[$_POST['wpfnl_name']] = $cf;
                update_option( 'wpfnl_custom_fields_additional', $get_data );
            }
        }else{
            $get_e_data[$_POST['wpfnl_name']] = $cf;
            update_option( 'wpfnl_custom_fields_additional', $get_e_data );
        }
    }
    // billing field add
    if($_POST['hidden_field'] == 'billing' ){
        $get_billing_data = get_option( 'wpfnl_wc_billing_default_fields');
        $get_billing_updated_data = get_option( 'wpfnl_wc_billing_fields');
        if($get_billing_updated_data){
            if(!(in_array($_POST['wpfnl_name'], $get_billing_data))){
                $get_billing_updated_data[$_POST['wpfnl_name']] = $cf;
                update_option( 'wpfnl_wc_billing_fields', $get_billing_updated_data );
            }
        }else{
            $get_billing_updated_data[$_POST['wpfnl_name']] = $cf;
            update_option( 'wpfnl_wc_billing_fields', $get_billing_updated_data );
        } 
    }
    // shipping field add
    if($_POST['hidden_field'] == 'shipping'){
        $get_shipping_data = get_option( 'wpfnl_wc_shipping_default_fields');
        $get_shipping_updated_data = get_option( 'wpfnl_wc_shipping_fields');
        if($get_shipping_updated_data){
            if(!(in_array($_POST['wpfnl_name'], $get_shipping_data))){
                $get_shipping_updated_data[$_POST['wpfnl_name']] = $cf;
                update_option( 'wpfnl_wc_shipping_fields', $get_shipping_updated_data );
            }
        }else{
            $get_shipping_updated_data[$_POST['wpfnl_name']] = $cf;
            update_option( 'wpfnl_wc_shipping_fields', $get_shipping_updated_data );
        }
    }
    echo "<meta http-equiv='refresh' content='0'>"; 
}


/**
 * individual edit field
 */
if(isset($_POST['wpfnl_edit_submit'])){
    if(isset($_POST['wpfnl_edit_type'])){
        $this->edit_cf['type'] = $_POST['wpfnl_edit_type'];
    }else{
        $this->edit_cf['type'] = null;
    }

    if(isset($_POST['wpfnl_edit_label'])){
        $this->edit_cf['label'] = $_POST['wpfnl_edit_label'];
    }else{
        $this->edit_cf['label'] = null;
    }

    if(isset($_POST['wpfnl_edit_placeholder'])){
        $this->edit_cf['placeholder'] = $_POST['wpfnl_edit_placeholder'];
    }else{
        $this->edit_cf['placeholder'] = null;
    }

    if(isset($_POST['wpfnl_edit_id'])){
        $this->edit_cf['id'] = $_POST['wpfnl_edit_id'];
    }else{
        $this->edit_cf['id'] = null;
    }
    
    if(isset($_POST['wpfnl_edit_default'])){
        $this->edit_cf['default'] = $_POST['wpfnl_edit_default'];
    }else{
        $this->edit_cf['default'] = null;
    }
    if(isset($_POST['edit_validate'])){
        $this->edit_cf['validate'] = $_POST['edit_validate'];
    }else{
        $this->edit_cf['validate'] = null;
    }
    if(isset($_POST['wpfnl_edit_type_option'])){
        $number = count($_POST['wpfnl_edit_type_option']);
        if( $number > 0 ){
            for($i = 0; $i< $number; $i++){
                $option_value = str_replace(' ', '_', $_POST['wpfnl_edit_type_option'][$i]);
                $this->edit_cf['option_text'][$i] =  $_POST['wpfnl_edit_type_option'][$i];
                $this->edit_cf['option_value'][$i] =  $option_value;
            }
        }else{
            echo __('please enter option value with option text','checkout-field-editor');
        }
    }

    $this->edit_cf['delete'] = 0;
    $this->edit_cf['class'] = array('form-row-wide');
    
    
            if($_POST['wpfnl_hidden_value'] == 'custom'){
                if(isset($_POST['wpfnl_edit_required'])){
                    $this->edit_cf['required'] = true;
                }else{
                    $this->edit_cf['required'] = false;
                }
                
                if(isset($_POST['wpfnl_edit_show'])){
                    $this->edit_cf['show'] = 1;
                }else{
                    $this->edit_cf['show'] = 0;
                }
                
                if(isset($_POST['wpfnl_edit_enable'])){
                    $this->edit_cf['enable'] = 1;
                }else{
                    $this->edit_cf['enable'] = 0;
                }

                $get_data = get_option( 'wpfnl_wc_billing_fields');
                $get_data_shipping = get_option( 'wpfnl_wc_shipping_fields');
                $get_data_additional = get_option( 'wpfnl_custom_fields_additional');

                if($_POST['wpfnl_hidden_type'] == 'billing'){
                    if($get_data){
                
                    unset($get_data[$_POST['wpfnl_edit_name']]);
                    $get_data[$_POST['wpfnl_edit_name']] = $this->edit_cf;
                    update_option( 'wpfnl_wc_billing_fields', $get_data );
            
                    }else{
                        unset($get_data[$_POST['wpfnl_edit_name']]);
                        $get_e_data[$_POST['wpfnl_edit_name']] = $this->edit_cf;
                        update_option( 'wpfnl_wc_billing_fields', $get_e_data );
                    }           
                }

                if($_POST['wpfnl_hidden_type'] == 'shipping'){
                    if($get_data_shipping){
                
                        unset($get_data_shipping[$_POST['wpfnl_edit_name']]);
                        $get_data_shipping[$_POST['wpfnl_edit_name']] = $this->edit_cf;
                        update_option( 'wpfnl_wc_shipping_fields', $get_data_shipping );
                
                    }else{
                            unset($get_data_shipping[$_POST['wpfnl_edit_name']]);
                            $get_e_data[$_POST['wpfnl_edit_name']] = $this->edit_cf;
                            update_option( 'wpfnl_wc_shipping_fields', $get_e_data );
                    } 
                }

                if($_POST['wpfnl_hidden_type'] == 'additional'){
                    if($get_data_additional){
                        unset($get_data_additional[$_POST['wpfnl_edit_name']]);
                        $get_data_additional[$_POST['wpfnl_edit_name']] = $this->edit_cf;
                        update_option( 'wpfnl_custom_fields_additional', $get_data_additional );
                
                    }else{
                            unset($get_data_additional[$_POST['wpfnl_edit_name']]);
                            $get_e_data[$_POST['wpfnl_edit_name']] = $this->edit_cf;
                            update_option( 'wpfnl_custom_fields_additional', $get_e_data );
                    } 
                }


            }elseif($_POST['wpfnl_hidden_value'] == 'default'){

                if(isset($_POST['wpfnl_edit_required'])){
                    $this->edit_cf['required'] = true;
                }else{
                    $this->edit_cf['required'] = false;
                }
                
                if(isset($_POST['wpfnl_edit_show'])){
                    $this->edit_cf['show'] = 1;
                }else{
                    $this->edit_cf['show'] = 0;
                }
                
                if(isset($_POST['wpfnl_edit_enable'])){
                    $this->edit_cf['enable'] = 1;
                }else{
                    $this->edit_cf['enable'] = 0;
                }
                if(isset($_POST['edit_validate'])){
                    $this->edit_cf['validate'] = $_POST['edit_validate']; 
                }else{
                    $this->edit_cf['validate'] = null;
                }
                
                if($_POST['wpfnl_hidden_type'] == 'billing'){
                    $get_data = get_option( 'wpfnl_wc_billing_fields');
                    unset($get_data[$_POST['wpfnl_hidden_index']['placeholder']]);
                    unset($get_data[$_POST['wpfnl_hidden_index']['label']]);
                    unset($get_data[$_POST['wpfnl_hidden_index']['required']]);
                    unset($get_data[$_POST['wpfnl_hidden_index']['show']]);
                    unset($get_data[$_POST['wpfnl_hidden_index']['enable']]);
                    unset($get_data[$_POST['wpfnl_hidden_index']['name']]);
                    $get_data[$_POST['wpfnl_hidden_index']]['placeholder'] = $this->edit_cf['placeholder'];
                    $get_data[$_POST['wpfnl_hidden_index']]['name'] = $this->edit_cf['name'];
                    $get_data[$_POST['wpfnl_hidden_index']]['label'] = $this->edit_cf['label'];
                    $get_data[$_POST['wpfnl_hidden_index']]['required'] = $this->edit_cf['required'];
                    $get_data[$_POST['wpfnl_hidden_index']]['show'] = $this->edit_cf['show'];
                    $get_data[$_POST['wpfnl_hidden_index']]['enable'] = $this->edit_cf['enable'];
                    $get_data[$_POST['wpfnl_hidden_index']]['validate'] = $this->edit_cf['validate'];
                    $get_data[$_POST['wpfnl_hidden_index']]['default'] = $this->edit_cf['default'];
                    update_option( 'wpfnl_wc_billing_fields', $get_data );
                }

                if($_POST['wpfnl_hidden_type'] == 'shipping'){
                    $get_data = get_option( 'wpfnl_wc_shipping_fields');
                    unset($get_data[$_POST['wpfnl_hidden_index']['placeholder']]);
                    unset($get_data[$_POST['wpfnl_hidden_index']['label']]);
                    unset($get_data[$_POST['wpfnl_hidden_index']['required']]);
                    unset($get_data[$_POST['wpfnl_hidden_index']['show']]);
                    unset($get_data[$_POST['wpfnl_hidden_index']['enable']]);
                    unset($get_data[$_POST['wpfnl_hidden_index']['name']]);
                    $get_data[$_POST['wpfnl_hidden_index']]['placeholder'] = $this->edit_cf['placeholder'];
                    $get_data[$_POST['wpfnl_hidden_index']]['name'] = $this->edit_cf['name'];
                    $get_data[$_POST['wpfnl_hidden_index']]['label'] = $this->edit_cf['label'];
                    $get_data[$_POST['wpfnl_hidden_index']]['required'] = $this->edit_cf['required'];
                    $get_data[$_POST['wpfnl_hidden_index']]['show'] = $this->edit_cf['show'];
                    $get_data[$_POST['wpfnl_hidden_index']]['enable'] = $this->edit_cf['enable'];
                    $get_data[$_POST['wpfnl_hidden_index']]['validate'] = $this->edit_cf['validate'];
                    $get_data[$_POST['wpfnl_hidden_index']]['default'] = $this->edit_cf['default'];
                    update_option( 'wpfnl_wc_shipping_fields', $get_data );

                }

                if($_POST['wpfnl_hidden_type'] == 'additional'){
                    $number = count($_POST['wpfnl_option_edit_text']);
                    $number2 = count($_POST['wpfnl_option_edit_value']);
                    if($number > 0){
                        if($number == $number2){
                            for($i = 0; $i< $number; $i++){
                            $this->edit_cf['option_text'][$i] =  $_POST['wpfnl_option_edit_text'][$i];
                            $this->edit_cf['option_value'][$i] =  $_POST['wpfnl_option_edit_value'][$i];
                            }
                        }else{
                            echo __('please enter option value with option text','checkout-field-editor');
                        }
                    }
                    if(isset($_POST['wpfnl_edit_required'])){
                        $this->edit_cf['required'] = true;
                    }else{
                        $this->edit_cf['required'] = false;
                    }
                    
                    if(isset($_POST['wpfnl_edit_show'])){
                        $this->edit_cf['show'] = 1;
                    }else{
                        $this->edit_cf['show'] = 0;
                    }
                    
                    if(isset($_POST['wpfnl_edit_enable'])){
                        $this->edit_cf['enable'] = 1;
                    }else{
                        $this->edit_cf['enable'] = 0;
                    }
                    $get_data = get_option( 'wpfnl_custom_fields_additional');

                    unset($get_data[$_POST['wpfnl_hidden_index']['placeholder']]);
                    unset($get_data[$_POST['wpfnl_hidden_index']['label']]);
                    unset($get_data[$_POST['wpfnl_hidden_index']['required']]);
                    unset($get_data[$_POST['wpfnl_hidden_index']['show']]);
                    unset($get_data[$_POST['wpfnl_hidden_index']['enable']]);


                    $get_data[$_POST['wpfnl_hidden_index']]['placeholder'] = $this->edit_cf['placeholder'];
                    $get_data[$_POST['wpfnl_hidden_index']]['label'] = $this->edit_cf['label'];
                    $get_data[$_POST['wpfnl_hidden_index']]['required'] = $this->edit_cf['required'];
                    $get_data[$_POST['wpfnl_hidden_index']]['show'] = $this->edit_cf['show'];
                    $get_data[$_POST['wpfnl_hidden_index']]['enable'] = $this->edit_cf['enable'];
                    $get_data[$_POST['wpfnl_hidden_index']]['validate'] = $this->edit_cf['validate'];
                    $get_data[$_POST['wpfnl_hidden_index']]['default'] = $this->edit_cf['default'];
                    $get_data[$_POST['wpfnl_hidden_index']]['name'] = $this->edit_cf['name'];
                    update_option( 'wpfnl_custom_fields_additional', $get_data );
                }
            }else{
                $number = count($_POST['wpfnl_option_edit_text']);
                $number2 = count($_POST['wpfnl_option_edit_value']);
                if($number > 0){
                    if($number == $number2){
                        for($i = 0; $i< $number; $i++){
                        $this->edit_cf['option_text'][$i] =  $_POST['wpfnl_option_edit_text'][$i];
                        $this->edit_cf['option_value'][$i] =  $_POST['wpfnl_option_edit_value'][$i];
                        }
                    }else{
                        echo __('please enter option value with option text','checkout-field-editor');
                    }
                }
            

                if(isset($_POST['wpfnl_edit_required'])){
                    $this->edit_cf['required'] = true;
                }else{
                    $this->edit_cf['required'] = false;
                }
                
                if(isset($_POST['wpfnl_edit_show'])){
                    $this->edit_cf['show'] = 1;
                }else{
                    $this->edit_cf['show'] = 0;
                }
                
                if(isset($_POST['wpfnl_edit_enable'])){
                    $this->edit_cf['enable'] = 1;
                }else{
                    $this->edit_cf['enable'] = 0;
                }
                $get_data = get_option( 'wpfnl_custom_fields_additional');
                if($get_data){
                
                        unset($get_data[$_POST['wpfnl_hidden_value']]);
                        $get_data[$_POST['wpfnl_edit_name']] = $this->edit_cf;
                        update_option( 'wpfnl_custom_fields_additional', $get_data );
                
                }else{
                    unset($get_data[$_POST['wpfnl_hidden_value']]);
                    $get_e_data[$_POST['wpfnl_edit_name']] = $this->edit_cf;
                    update_option( 'wpfnl_custom_fields_additional', $get_e_data );
                }
                

                
                
            }
    echo "<meta http-equiv='refresh' content='0'>";

}