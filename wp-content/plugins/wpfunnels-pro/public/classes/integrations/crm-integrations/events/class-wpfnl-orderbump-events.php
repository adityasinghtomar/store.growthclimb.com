<?php

namespace WPFunnelsPro\Integrations\CRM\Event;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Order Bump event for CRM
 *
 * Class CookieData
 * @package WPFunnelsPro\Integrations
 * @version 2.0.0
 */
class OrderBump extends Event {

    public function run() {
        if($this->crm->is_connected()) {
            // if( isset( $this->data['main_order_id'] )) {
                // if( isset($this->data['orderbump_accepted']) && ( ($this->data['orderbump_accepted'] && $this->triggerObject->get_event_name() == 'orderbump_accepted') || (!$this->data['orderbump_accepted'] && $this->triggerObject->get_event_name() == 'orderbump_not_accepted')) ){
                    
                    if( isset($this->data['orderbump_accepted']) && $this->is_allow_to_send() ){
                    $order_id = isset($this->data['main_order_id']) ? $this->data['main_order_id'] : null;
                    $_user = isset($this->data['user_info']) ? $this->data['user_info'] : null;
                    $user_info = $this->crm->get_user_info_from_order( $order_id, $_user );
                    $user_info['tags']  = [$this->triggerObject->get_tag_id()];
                    $user_info['lists'] = [$this->triggerObject->get_list_id()];
                    $user_info['source'] = "order_bump";
                   
                    $this->crm->send_or_update_data($user_info);
                }
            // }
        }
    }


    private function is_allow_to_send(){

        $funnel_id = isset($this->data['funnel_id']) ? $this->data['funnel_id'] : '';
        
        if( $funnel_id ){
            $steps =  get_post_meta( $funnel_id, '_steps_order', true );
            $key = array_search('checkout', array_column($steps, 'step_type'));
            if( false !== $key ){
                    $step_id = isset($steps[$key]['id']) ? $steps[$key]['id'] : '';
                    
                    if( $step_id ){
                        $ob_settings = get_post_meta( $step_id, 'order-bump-settings', true );
                        
                        if( !empty($ob_settings) ){

                            if(strpos($this->event, '_orderbump_accepted') !== false){
                                if(strpos($this->event, '_enrolled_orderbump_accepted') !== false){
                                    $index = str_replace('_enrolled_orderbump_accepted','',$this->event);
                                }else{
                                    $index = str_replace('_orderbump_accepted','',$this->event);
                                }
                               
                                if( 'any' == $index ){
                                    return true;
                                }else{
                                    if(strpos($index, '_enrolled') !== false){
                                        $index = str_replace('_enrolled','',$index);
                                    }
                                   
                                    if(isset($ob_settings[(int)($index-1)])){

                                        $product_id = $ob_settings[(int)($index-1)]['product'];
                                        if( isset($this->data['ob_accepetd_products']) ){
                                            if( in_array($product_id,$this->data['ob_accepetd_products'])){
                                                return true;
                                            }
                                        }
                                    }
                                }
                            }elseif(strpos($this->event, '_orderbump_not_accepted') !== false){
                                $index = str_replace('_orderbump_not_accepted','',$this->event);

                                if(isset($ob_settings[(int)($index-1)])){ 
                                    $product_id = $ob_settings[(int)($index-1)]['product'];
                                    if( isset($this->data['ob_accepetd_products']) ){
                                        if( !in_array($product_id,$this->data['ob_accepetd_products'])){
                                            return true;
                                        }
                                    }
                                }
                            }
                        }
                        
                    }
            }
        }
        return false;
    }


}