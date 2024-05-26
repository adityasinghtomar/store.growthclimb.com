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
class TriggerCTA extends Event {
    /**
     * Run the integrated CRM on Trigger CTA button
     * 
     * @since 2.0.5
     */
    public function run() {

        if($this->crm->is_connected()) {
            if( isset( $this->data['cta_clicked']) &&  $this->data['cta_clicked']  ) {
                $order_id = isset( $this->data['main_order_id'] ) ? $this->data['main_order_id'] : null;
                $user_info = $this->crm->get_user_info_from_order( $order_id , $this->data['user_info']);
                $user_info['tags']  = [$this->triggerObject->get_tag_id()];
                $user_info['lists'] = [$this->triggerObject->get_list_id()];
                $user_info['source'] = "trigger_cta";

                $this->crm->send_or_update_data($user_info);
            }
        }
 
    }

}