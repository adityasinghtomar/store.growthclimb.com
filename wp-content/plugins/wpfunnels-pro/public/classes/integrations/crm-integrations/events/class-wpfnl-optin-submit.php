<?php

namespace WPFunnelsPro\Integrations\CRM\Event;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Optin submission event for CRM
 *
 * Class CookieData
 * @package WPFunnelsPro\Integrations
 * @version 2.0.0
 */
class OptinSubmit extends Event {

    private $optin_data;

   
    /**
     * Run the integrated CRM on Optin submit
     * 
     * @since 2.0.5
     */
    public function run() {
       
        if($this->crm->is_connected()) {
            if( isset($this->data['after_optin_submit']['email']) && $this->data['after_optin_submit'] ) {
                
                $user_info['email'] = $this->data['after_optin_submit']['email'];
                $user_info['first_name'] = ( isset($this->data['after_optin_submit']['first_name']) && $this->data['after_optin_submit']['first_name'] ) ? $this->data['after_optin_submit']['first_name'] : '';
                $user_info['last_name'] = ( isset($this->data['after_optin_submit']['last_name']) && $this->data['after_optin_submit']['last_name'] ) ? $this->data['after_optin_submit']['last_name'] : '';
                $user_info['phone'] = ( isset($this->data['after_optin_submit']['phone']) && $this->data['after_optin_submit']['phone'] ) ? $this->data['after_optin_submit']['phone'] : '';
                $user_info['tags']  = [$this->triggerObject->get_tag_id()];
                $user_info['lists'] = [$this->triggerObject->get_list_id()];
                $user_info['source'] = "optin_submit";

                $this->crm->send_or_update_data($user_info);
            }
        }
 
    }




}