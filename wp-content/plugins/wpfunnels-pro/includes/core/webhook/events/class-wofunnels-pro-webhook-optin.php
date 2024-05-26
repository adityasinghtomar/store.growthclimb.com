<?php

namespace WPFunnelsProWebHooks\Events;

use WPFunnelsProWebHooks\Functions\Wpfnl_Pro_Webhook_Functions;
use WPFunnelsPro\Wpfnl_Pro_functions;

class Wpfnl_Pro_Webhook_Optin {
    
    protected $settings;

    protected $record;
    
    public function __construct( $settings , $record )
    {
        $this->settings = $settings;
        $this->record = $record;
    }


    

    /**
     * Get event name
     * 
     * @return String
     */
    public function get_event_name(){
        return 'After optin form submit';
    }



    /**
     * Send data to request url through webhook
     */
    public function send_data(){
        $request_url  = Wpfnl_Pro_Webhook_Functions::get_request_url( $this->settings );
        $request_args = $this->prepare_request_args();
        $response = wp_remote_request( $request_url, $request_args );
    }

    
    /**
     * Prepare request arguments
     * 
     */
    public function prepare_request_args(){
        
        $request_body = $this->prepare_request_body( $this->settings, $this->record );
        $content_type = 'application/json';
        return Wpfnl_Pro_functions::prepare_common_request_args( $this->settings, $content_type, $request_body, $this->get_event_name(), home_url( '/' ) );
    } 


    /**
     * Prepare request body
     * 
     * @param $settings
     * @param Array $formatted_body
     */
    private function prepare_request_body( $settings , $record ){
        
        $type = $settings['request']['body']['type'];
        $formatted_body = array();
       
        if( $type === 'all' ){
            $formatted_body = $this->prepare_all_body_fields( $settings , $record  );
        }else{
            $values = $settings['request']['body']['values'];
            $_body = array();
            $_body = $this->prepare_selected_body_fields( $settings , $record );
            $formatted_body['Event Name']   = $this->get_event_name();
            foreach( $values as $value ){
                if( isset( $_body[$value['value']] )){
                    $formatted_body[$value['key']] = $_body[$value['value']];
                }
            }
        }
        return $formatted_body;
    }


    /**
     * Prepare request body field for type 'all'
     * 
     * @param $order, $order_id, $offer_product
     * @return $formatted_body
     */
    public function prepare_all_body_fields( $settings , $record ){

        $formatted_body['Date']         = date("Y-m-d h:i");
        $formatted_body['Event Name']   = $this->get_event_name();
        if( isset($record->form_data['first_name']) ){
            $formatted_body['First Name']   = isset($record->form_data['first_name']) ? $record->form_data['first_name'] : '';
        }

        if( isset($record->form_data['last_name']) ){
            $formatted_body['Last Name']    = isset($record->form_data['last_name']) ? $record->form_data['last_name'] : '';
        }
        
        if( isset($record->form_data['phone']) ){
            $formatted_body['Phone']        = isset($record->form_data['phone']) ? $record->form_data['phone'] : '';
        }
        
        if( isset($record->form_data['email']) ){
            $formatted_body['Email']        = isset($record->form_data['email']) ? $record->form_data['email'] : '';
        }
        
        if( isset($record->form_data['message']) ){
            $formatted_body['Message']        = isset($record->form_data['message']) ? $record->form_data['message'] : '';
        }
        
        if( isset($record->form_data['web-url']) ){
            $formatted_body['Website']        = isset($record->form_data['web-url']) ? $record->form_data['web-url'] : '';
        }

        return $formatted_body;
    }


    /**
     * Prepare request body field for type 'selected'
     * 
     * @param $order, $order_id, $offer_product
     * @return $formatted_body
     */
    public function prepare_selected_body_fields( $settings , $record ){

        $formatted_body['Date']  = date("Y-m-d h:i");
        $formatted_body['first_name']   = isset($record->form_data['first_name']) ? $record->form_data['first_name'] : '';
        $formatted_body['last_name']    = isset($record->form_data['last_name']) ? $record->form_data['last_name'] : '';
        $formatted_body['Phone']        = isset($record->form_data['phone']) ? $record->form_data['phone'] : '';
        $formatted_body['email']        = isset($record->form_data['email']) ? $record->form_data['email'] : '';
        $formatted_body['message']      = isset($record->form_data['message']) ? $record->form_data['message'] : '';
        $formatted_body['website']      = isset($record->form_data['web-url']) ? $record->form_data['web-url'] : '';

        return $formatted_body;
    }

}