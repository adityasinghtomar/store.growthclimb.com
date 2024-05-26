<?php
namespace WPFunnelsPro\Admin\Modules\Steps\Checkout;

use WPFunnels\Metas\Wpfnl_Step_Meta_keys;
use WPFunnels\Admin\Modules\Steps\Module as Steps;
use WPFunnels\Wpfnl;

class Module extends Steps
{
    
    /**
     * init ajax hooks for
     * saving metas
     *
     * @since 1.0.0
     */
    public function init_ajax()
    {
        $this->validations = [
            'logged_in' => true,
            'user_can' => 'manage_options',
        ];
        wp_ajax_helper()->handle('wpfn-show-billing-fields')
            ->with_callback([ $this, 'wpfn_show_checkout_billing_fields' ])
            ->with_validation($this->validations);
    }

    /**
     * wpfn_show_checkout_billing_fields
     * Show the billing fields
     * 
     * @param array $payload  
     * 
     */
    public function wpfn_show_checkout_billing_fields($payload){
        
        return [
            'status' => 'success',
        ];
    }
}