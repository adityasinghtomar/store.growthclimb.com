<?php

namespace WPFunnelsPro\Integrations\CRM\Event;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Offer event for CRM
 *
 * Class CookieData
 * @package WPFunnelsPro\Integrations
 * @version 2.0.0
 */
class MainOrder extends Event {

    /**
     * Run the integrated CRM on Main order
     * 
     * @since 2.0.5
     */
    public function run() {
        
        if($this->crm->is_connected()) {

           
            if( (isset($this->data['main_order_id']) && $this->data['main_order_id'] && 'main_order_accepted_enrolled' !== $this->event ) || ( 'main_order_accepted' !== $this->event && isset($this->data['already_enrolled']) && in_array('checkout',$this->data['already_enrolled']) ) ) {
                
                $type = isset($this->data['funnel_id']) ? get_post_meta($this->data['funnel_id'],'_wpfnl_funnel_type',true) : '';
                if( (isset($this->data['main_order_id']) && $this->data['main_order_id']) && 'lms' !== $type ){
                    $user_info = $this->crm->get_user_info_from_order( $this->data['main_order_id'] );
                }else{
                    $user_info = $this->crm->get_user_info_from_order( null, $this->data['user_info'] );
                }
                
                $user_info['tags']  = [$this->triggerObject->get_tag_id()];
                $user_info['lists'] = [$this->triggerObject->get_list_id()];
                $user_info['source'] = "main_order";

                $this->crm->send_or_update_data($user_info);
            }
        }
    }

}