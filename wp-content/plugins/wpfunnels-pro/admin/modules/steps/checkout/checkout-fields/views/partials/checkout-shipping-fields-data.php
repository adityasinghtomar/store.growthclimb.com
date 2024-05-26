<?php 
foreach($shipping_fields as $a => $bf){ 
    if($bf['delete'] == 0){
        $html .= '<div class="checkout__single-field" id="'.$a.'">';
        $html .=  '<div class="field-item field-hamburger">';
        $html .=  '<span class="hamburger-bar"></span>';
        $html .=  '</div>';
        $html .=  '<div class="field-item field-name" data-label="Name :">'.$a.'</div>';
        $html .=  '<div class="field-item field-type" data-label="Type :">'.$bf['type'].'</div>';
        $html .=  '<div class="field-item field-label" data-label="Label :">'.$bf['label'].'</div>';
        $html .=  '<div class="field-item field-placeholder" data-label="Placeholder :">'.$bf['placeholder'].'</div>';
        if(isset($bf['validate']) && is_array($bf['validate'])){
            $validation_array = $bf['validate']; 
            $validation_str = implode(",",$validation_array);
        }else{
            $validation_str = '';
        }
        $html .= '<div class="field-item field-validation" data-label="Validations :">'.$validation_str.'</div>';
        $html .= '<div class="field-item field-required" data-label="Required :">';
        $html .= '<div class="wpfnl-switcher">';
        if($bf['required'] == true){ 
            $html .= '<input type="checkbox" checked name="field-required" id="'.$a.'-field-required" class="wpfnl_change_required" value="1" data-id="'.$a.'" data-type="shipping"/>';
        }else{
            $html .= '<input type="checkbox" name="field-required" id="'.$a.'-field-required" class="wpfnl_change_required" value="1" data-id="'.$a.'" data-type="shipping"/>';
        }
        $html .= '<label for="'.$a.'-field-required"></label>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="field-item field-status" data-label="Status :">';
        $html .= '<div class="wpfnl-switcher">';
        if($bf['enable'] == 1){ 
            $html .= '<input type="checkbox" checked name="field-status" id="'.$a.'-field-status" class="wpfnl_change_enable" value="1" data-id="'.$a.'" data-type="shipping"/>';						
        }else{
            $html .= '<input type="checkbox" name="field-status" id="'.$a.'-field-status" class="wpfnl_change_enable" value="1" data-id="'.$a.'" data-type="shipping"/>';
        }
        $html .= '<label for="'.$a.'-field-status"></label>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="field-item field-action" data-label="Actions :">';
        $html .= '<button type="button" class="edit-field wpfnl_edit_row" title="Edit Field" data-id="'.$a.'" data-type="shipping">';
        $html .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
        $html .= '<path  class="icon stroke" d="M4 20.0001H8L18.5 9.50006C19.6046 8.39549 19.6046 6.60463 18.5 5.50006C17.3954 4.39549 15.6046 4.39549 14.5 5.50006L4 16.0001V20.0001" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        $html .= '<path  class="icon stroke" d="M13.5 6.5L17.5 10.5" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        $html .= '</svg>';
        $html .= '</button>';
        $html .= '<button type="button" class="delete-checkout-field" title="Delete Field" data-id="'.$a.'" data-type="shipping" id="wpfnl_delete">';
        $html .= '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">';
        $html .= '<path d="M3.33333 5.83333H16.6667" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        $html .= '<path d="M8.33334 9.16667V14.1667" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        $html .= '<path d="M11.6667 9.16667V14.1667" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        $html .=  '<path d="M4.16667 5.83333L5.00001 15.8333C5.00001 16.7538 5.7462 17.5 6.66667 17.5H13.3333C14.2538 17.5 15 16.7538 15 15.8333L15.8333 5.83333" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        $html .= '<path d="M7.5 5.83333V3.33333C7.5 2.8731 7.8731 2.5 8.33333 2.5H11.6667C12.1269 2.5 12.5 2.8731 12.5 3.33333V5.83333" stroke="#7A8B9A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
        $html .= '</svg>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';
    }
}