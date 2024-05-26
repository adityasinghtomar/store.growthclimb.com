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
class OfferEvent extends Event {
    
    /**
     * Run the integrated CRM on Offer event(upsell, downsell)
     * 
     * @since 2.0.5
     */
    public function run() {
        if($this->crm->is_connected()) {
            $offer_data = $this->get_offer_data();
            $event_status = '';
  
            if( (strpos($this->triggerObject->get_event_name(), 'upsell_accepted_enrolled' ) !== false && isset($this->data['already_enrolled']) && in_array('upsell',$this->data['already_enrolled'])) ||  (strpos($this->triggerObject->get_event_name(), 'downsell_accepted_enrolled' ) !== false && isset($this->data['already_enrolled']) && in_array('downsell',$this->data['already_enrolled']))){
                $event_status = 'accepted';
            }
           
            $step_type = get_post_meta( $this->triggerObject->get_step_id(), '_step_type', true );
            
            if( $step_type === 'upsell' ){
                if( !isset($this->data['already_enrolled']) || (!in_array('upsell',$this->data['already_enrolled']) )){
                    if( strpos($this->triggerObject->get_event_name(), 'upsell_accepted') !== false || strpos($this->triggerObject->get_event_name(), 'downsell_accepted') !== false ){
                        $event_status = 'accepted';
                    }elseif(strpos($this->triggerObject->get_event_name(), 'upsell_rejected') !== false || strpos($this->triggerObject->get_event_name(), 'downsell_rejected') !== false ){
                        $event_status = 'rejected';
                    }
                }
            }

            if( $step_type === 'downsell' ){
                if( !isset($this->data['already_enrolled']) || !in_array('downsell',$this->data['already_enrolled']) ){
                    if( strpos($this->triggerObject->get_event_name(), 'upsell_accepted') !== false || strpos($this->triggerObject->get_event_name(), 'downsell_accepted') !== false ){
                        $event_status = 'accepted';
                    }elseif(strpos($this->triggerObject->get_event_name(), 'upsell_rejected') !== false || strpos($this->triggerObject->get_event_name(), 'downsell_rejected') !== false ){
                        $event_status = 'rejected';
                    }
                }
            }
            
            

            if( isset( $offer_data['status'] ) && $offer_data['status'] == $event_status ){
                $order_id = null;
                if( isset($this->data['main_order_id']) ){
                    $order_id = $offer_data['status'] === 'rejected' ? $this->data['main_order_id'] : $offer_data['order_id'];
                }else{
                    if( isset($this->data['already_enrolled']) ){
                        $order_id = null;
                    }
                }
                
                $_user = isset($this->data['user_info']) ? $this->data['user_info'] : null;
                $user_info = $this->crm->get_user_info_from_order( $order_id , $_user );
                $user_info['tags']  = [$this->triggerObject->get_tag_id()];
                $user_info['lists'] = [$this->triggerObject->get_list_id()];
                $src = '';
                if ( isset( $offer_data['type'] ) && isset( $offer_data['status'] )){
                    $src = $offer_data['type'].'_'.$offer_data['status'];
                }
                $user_info['source'] = $src;

                $this->crm->send_or_update_data($user_info);
            }
            
        }
    }


    private function get_offer_data() {
        if( isset( $this->data['offer'] ) && isset($this->data['offer'][$this->triggerObject->get_step_id()]) ) {
            return $this->data['offer'][$this->triggerObject->get_step_id()];
        }
        return false;
    }

}