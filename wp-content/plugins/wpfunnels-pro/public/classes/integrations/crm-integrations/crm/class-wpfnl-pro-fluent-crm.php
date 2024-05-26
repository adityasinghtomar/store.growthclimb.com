<?php

namespace WPFunnelsPro\Integrations;

use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Integrations\CRM;


/**
 * integration class for Fluent CRM
 *
 * Class WPF_Integrations_Fluent_CRM
 */
class FluentCRM extends CRM {

    use SingletonTrait;

    /**
     * Get Fluent CRM Name
     * 
     * @param null
     * @return string
     */
    public function get_name(){
        return 'Fluent CRM';
    }

    /**
     * Retrieve user information from db
     * 
     * @param order_id
     * @return array
     */
    public function get_user_info_from_order( $order_id = null, $user_info = [] ) {
        
        $user = [];

        $funnel_id = Wpfnl_functions::get_funnel_id_from_order( $order_id );
        
        if( $funnel_id ){
            $type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );
           
            if( 'lms' === $type ){
                $user = $this->get_user_info_for_lms( $user_info );
            }elseif( 'wc' === $type ){
                $user = $this->get_user_info_for_woo( $order_id, $user_info);
            }elseif( 'lead' === $type ){
                $user = $this->get_user_info_for_lead( $$user_info );
            }
        }else{
            $user = $this->get_user_info_for_woo( $order_id, $user_info );
        }
        $user['status']     = 'pending';

        return $user;
    }


    /**
     * Get user for woocommerce
     * @param Int $order_id
     * @param Array $user_info
     * @return Array
     */
    private function get_user_info_for_woo( $order_id, $user_info ){
        $user = [];
        if( $order_id ){
            $order              = wc_get_order( $order_id );
            if( $order ){
                $customer_id        = $order->get_customer_id();
                $user               = array();
    
                if( 0 != $customer_id ) {
                    $customer           = new \WC_Customer( $customer_id );
                    $user['email']      = $customer->get_email();
                    $user['first_name'] = $customer->get_first_name();
                    $user['last_name']  = $customer->get_last_name();
                } else {
                    $user['email']      = $order->get_billing_email();
                    $user['first_name'] = $order->get_billing_first_name();
                    $user['last_name']  = $order->get_billing_last_name();
                }
            }else{
                $user['email']       = isset($user_info['email']) ? $user_info['email'] : '';
                $user['first_name']  = isset($user_info['first_name']) ? $user_info['first_name'] : '';
                $user['last_name']   = isset($user_info['last_name']) ? $user_info['last_name'] : '';
            }
        }else{
            $user['email']       = isset($user_info['email']) ? $user_info['email'] : '';
            $user['first_name']  = isset($user_info['first_name']) ? $user_info['first_name'] : '';
            $user['last_name']   = isset($user_info['last_name']) ? $user_info['last_name'] : '';
        }
        return $user;
    }


    /**
     * Get user for woocommerce
     */
    private function get_user_info_for_lms( $user_info ){

        $user = [];
        $user['email']       = isset($user_info['email']) ? $user_info['email'] : '';
        $user['first_name']  = isset($user_info['first_name']) ? $user_info['first_name'] : '';
        $user['last_name']   = isset($user_info['last_name']) ? $user_info['last_name'] : '';
        return $user;
        
    }

    /**
     * Get user for woocommerce
     * 
     * @param Array
     * @return Array
     */
    private function get_user_info_for_lead( $user_info ){

        $user = [];
        $user['email']       = isset($user_info['email']) ? $user_info['email'] : '';
        $user['first_name']  = isset($user_info['first_name']) ? $user_info['first_name'] : '';
        $user['last_name']   = isset($user_info['last_name']) ? $user_info['last_name'] : '';
        return $user;
        
    }


    /**
     * Sending data to Fluent CRM to create or update contact, contact lists and tags
     * 
     * @param data
     * @return array
     */
    public function send_or_update_data( $data )
    {   
        if (isset( $data['source'] )){
            json_encode($data['source']);
        }
        $contactApi = FluentCrmApi('contacts');
        $contact    = $contactApi->createOrUpdate($data);
        if( $contact && 'pending' == $contact->status ) {
            $contact->sendDoubleOptinEmail();
        }
    }


    public function deleteData($id)
    {
        // TODO: Implement deleteData() method.
    }

    /**
     * check if fluent crm is activated or not
     *
     * @return bool
     */
    function is_connected()
    {
        if ( is_plugin_active( 'fluent-crm/fluent-crm.php' ) ) {
            return true;
        }
        return false;
    }

    /**
     * Get Fluent CRM Contact lists
     * 
     * @param null
     * @return array
     */
    public function get_crm_contact_lists(){

        $listApi = FluentCrmApi('lists');
        $allLists = $listApi->all();
        foreach($allLists as $list) {
            $response[$list->id] = [
                'value' => $list->title
            ];
        }
        return $response;
    }

    /**
     * Get Fluent CRM Contact tags
     * 
     * @param null
     * @return array
     */
    public function get_crm_contact_tags( $list = [] ){
        $tagApi = FluentCrmApi('tags');
        $allTags = $tagApi->all();
        foreach($allTags as $tag) {
            $response[$tag->id] = $tag->title;
        }
        return $response;
    }
}