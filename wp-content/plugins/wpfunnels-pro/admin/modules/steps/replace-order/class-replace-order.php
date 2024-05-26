<?php

namespace WPFunnelsPro\ReplaceOrder;

class OrderReplacement {


    /**
     * Save order replacement condition
     * 
     * @param $step_info, $replacement_type, $value
     * 
     * @return Boolean
     */
    public static function save_replace_order_condition( $step_info, $replacement_type, $value ){

        if( empty($step_info) || !isset($step_info['step_id']) || !isset($step_info['step_type']) || !$step_info['step_id'] || !$step_info['step_type'] || !$replacement_type || !$value ){
            return false;
        }
        $data = array(
            'replacement_type' => $replacement_type,
            'value' => $value
        );
        update_post_meta( $step_info['step_id'], '_wpfnl_'.$step_info['step_type'].'_replacement', $data );
    }

}