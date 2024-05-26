<?php
namespace WPFunnelsPro\Offers;

use WPFunnels\Traits\SingletonTrait;
use WPFunnelsPro\Wpfnl_Pro_functions;
use WOO_SL_functions;
class Offer_Subscription {
    use SingletonTrait;

    public function __construct() {
        
        if( Wpfnl_Pro_functions::is_separate_offer() ) {
            add_action( 'wpfunnels/before_offer_new_child_order_before_completed', array( $this, 'create_separate_subscription' ), 10, 3 );
        }
        else {
            add_action( 'wpfunnels/offer_accepted', array( $this, 'add_to_main_order' ), 10, 2 );
        }
        add_action( 'wpfunnels/before_main_order_cancelled', array( $this, 'may_be_cancel_subscription' ), 10, 1 );
    }


    /**
     * add subscription to main order
     *
     * @param $order
     * @param $offer_product
     * @param $parent_order
     * @throws \WC_Data_Exception
     */
    public function add_to_main_order( $order, $offer_product ) {
        $transaction_id                     = $order->get_meta( '_wpfunnels_offer_txn_resp_' . $offer_product['step_id'] );
        $offer_product['transaction_id']    = $transaction_id;
        $subscription                       = $this->maybe_create_new_subscription( $order, $offer_product );
        if ( ! empty( $subscription ) ) {
            ob_start();
            do_action( 'wpfunnels/subscription_created', $subscription, $offer_product, $order );
            ob_get_clean();
        }
    }


    /**
     * @param $order
     * @param $offer_product
     * @param $parent_order
     * @throws \WC_Data_Exception
     */
    public function create_separate_subscription( $order, $offer_product, $parent_order ) {
        $subscription = $this->maybe_create_new_subscription( $order, $offer_product );
        if ( ! empty( $subscription ) ) {
            ob_start();
            do_action( 'wpfunnels/subscription_created', $subscription, $offer_product, $order );
            ob_get_clean();
        }
    }


    /**
     * @param \WC_Order $order
     * @param $offer_product
     * @return bool|\WC_Order|\WC_Subscription|\WP_Error
     * @throws \WC_Data_Exception
     */
    public function maybe_create_new_subscription( \WC_Order $order, $offer_product ) {
        
        $product_id         = $offer_product['id'];
        $product            = wc_get_product( $product_id );
        $subscription       = false;
        $subscription_order = $order;
        $user_created       = null;
        
        if ( $product && ( 'subscription' === $product->get_type() || 'subscription_variation' === $product->get_type() ) ) {
            if ( is_user_logged_in() ) {
                $user_id = $subscription_order->get_user_id();
                
            } else {
                
                if( $subscription_order ){
                    $user_id      = ( null === $user_created ) ? $this->create_new_customer( $subscription_order->get_billing_email() ) : $user_created;
                    $user_created = $user_id;
                    $subscription_order->set_customer_id( $user_id );
                    $subscription_order->save();
                }
                
            }
            $args = array(
                'product'          => $offer_product,
                'order'            => $subscription_order,
                'user_id'          => $user_id,
            );
           
            $subscription = $this->create_new_subscription( $args, $this->get_subscription_status( $subscription_order ) );
            
            if ( false !== $subscription ) {
                return $subscription;
            }
        }
        return false;
    }


    /**
     * create new WC customer
     *
     * @param $email
     * @return bool|int|\WP_Error
     */
    private function create_new_customer( $email ) {

        if ( empty( $email ) ) {
            return false;
        } 

        
        $maybe_user = get_user_by( 'email', $email );
        if ( $maybe_user instanceof \WP_User ) {
            return $maybe_user->ID;
        }
        $username = sanitize_user( current( explode( '@', $email ) ), true );

        /** user has to be unique */
        $append     = 1;
        $o_username = $username;

        while ( username_exists( $username ) ) {
            $username = $o_username . $append;

            ++ $append;
        }

        $password       = wp_generate_password();
        $customer_id    = wc_create_new_customer( $email, $username, $password );

        if ( ! empty( $customer_id ) ) {
            wp_set_current_user( $customer_id, $username );
            wc_set_customer_auth_cookie( $customer_id );
        }

        return $customer_id;
    }


    /**
     * get status of the subscription order
     *
     * @param $order
     * @return string
     */
    private function get_subscription_status( $order ) {
        $get_payment_method = $order->get_payment_method();
        if ( in_array( $get_payment_method, [ 'bacs', 'cheque' ], true ) ) {
            return 'on-hold';
        }
        return 'completed';
    }


    /**
     * create new subscription
     *
     * @param $args
     * @param $status
     * @return bool|\WC_Order|\WC_Subscription|\WP_Error
     * @throws \WC_Data_Exception
     */
    private function create_new_subscription( $args, $status ) {
        $offer_product  = $args['product'];
        $order          = $args['order'];
        $user_id        = $args['user_id'];
        $product        = wc_get_product($offer_product['id']);
        if( $product ){
            $transaction_id = $offer_product['transaction_id'];
            $start_date     = date( 'Y-m-d H:i:s' );
            $period         = \WC_Subscriptions_Product::get_period( $product );
            $interval       = \WC_Subscriptions_Product::get_interval( $product );
            $trial_period   = \WC_Subscriptions_Product::get_trial_period( $product );
            
            /** create subscription */
            $subscription   = wcs_create_subscription( array(
                'start_date'       => $start_date,
                'order_id'         => $order->get_id(),
                'billing_period'   => $period,
                'billing_interval' => $interval,
                'customer_note'    => $order->get_customer_note(),
                'customer_id'      => $user_id,
            ) );
            
        
            
            if ( is_wp_error( $subscription ) ) {
                return false;
            }
            if ( ! empty( $subscription ) ) {
                $subscription_item_id   = $subscription->add_product( $product, 1 );
                $subscription           = wcs_copy_order_address( $order, $subscription );

                $trial_end_date         = \WC_Subscriptions_Product::get_trial_expiration_date( $product->get_id(), $start_date );
                $next_payment_date      = \WC_Subscriptions_Product::get_first_renewal_payment_date( $product->get_id(), $start_date );
                $end_date               = \WC_Subscriptions_Product::get_expiration_date( $product->get_id(), $start_date );

                $subscription->update_dates(
                    array(
                        'trial_end'    => $trial_end_date,
                        'next_payment' => $next_payment_date,
                        'end'          => $end_date,
                    )
                );

                /** check if the subscription product has trial option */
                if ( \WC_Subscriptions_Product::get_trial_length( $product->get_id() ) > 0 ) {
                    wc_add_order_item_meta( $subscription_item_id, '_has_trial', 'true' );
                }

                if ( ! empty( $trial_period ) ) {
                    update_post_meta( $subscription->get_id(), '_trial_period', $trial_period );
                }

                $subscription->set_payment_method( $order->get_payment_method() );
                $subscription->set_payment_method_title( $order->get_payment_method_title() );

                update_post_meta( $subscription->get_id(), '_customer_user', $user_id );


                if ( 'completed' === $status ) {
                    $subscription->payment_complete( $transaction_id );
                } else {
                    $subscription->update_status( $status );
                }

                $subscription->calculate_totals();
                $subscription->save();
                if( is_plugin_active( 'woocommerce-software-license/software-license.php' ) ){
                    $license_obj = new WOO_SL_functions;
                    $this->order_setup_licensing($order->get_id(), $license_obj);
                    $license_obj->generate_license_keys($order->get_id());
                }

                return $subscription;
            }
        }
        return false;
    }


    /**
     * cancel the main order
     *
     * @param $order
     */
    public function may_be_cancel_subscription( $order ) {
        if ( function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order ) ) {
            $parent_subscription = wcs_get_subscriptions_for_order( $order->get_id() );
            if ( ! empty( $parent_subscription ) ) {
                $parent_subscription = array_pop( $parent_subscription );
                if ( ! empty( $parent_subscription ) ) {
                    $parent_subscription->update_status( 'cancelled', __( 'Subscription replaced by the Offer Product', 'wpfnl-pro' ) );
                }
            }
        }
    }

    /**
    * Add licence data to meta details for the order
    * 
    * @param mixed $order_id
    * @param mixed $license_obj
    */
    public function order_setup_licensing( $order_id, $license_obj)
    {
        
       
        //check if order contain any licensed product
        $order_data     = new \WC_Order($order_id);
        $order_products =   $order_data->get_items();
        
        $found_licensed_product =   FALSE;
        foreach($order_products as  $key    =>  $order_product)
            {                            
                if (WOO_SL_functions::is_product_licensed( $order_product->get_product_id() ) )
                    {
                        $found_licensed_product =   TRUE;
                        break;   
                    }
            }
            
        if(!$found_licensed_product)
            return;
        
        $_woo_sl    =   array();
       
        //get the order items
        foreach ( $order_products as $key   =>  $order_product ) 
            {
                if(! $license_obj->is_product_licensed( $order_product->get_product_id() ))
                    continue;
                
                $is_licence_extend  =   FALSE;
                $_woo_sl_extend     =   wc_get_order_item_meta($key, '_woo_sl_extend', TRUE);

                if(!empty($_woo_sl_extend))
                    $is_licence_extend  =   TRUE;
                
                //no need to process if is an licence extend
                if (    $is_licence_extend  )
                    continue;
                    
                //check against the variation, if assigned a licence group
                if($order_product->get_variation_id()   > 0)
                    {
                        $variation_license_group_id =   get_post_meta($order_product->get_variation_id(), '_sl_license_group_id', TRUE);
                        
                        if( $variation_license_group_id == '')
                            continue;                                                                                                                                        
                    }

                //get product licensing details
                $product_sl_groups     =   WOO_SL_functions::get_product_licensing_groups( $order_product->get_product_id() );
                
                //if variation, filter out the licence groups
                if($order_product->get_variation_id()   >   0)
                    {
                        if(isset($product_sl_groups[$variation_license_group_id]))
                            {
                                $_product_sl_groups  =   $product_sl_groups;
                                $product_sl_groups  =   array();   
                                $product_sl_groups[$variation_license_group_id]  =   $_product_sl_groups[$variation_license_group_id];
                            }
                            else
                            $product_sl_groups  =   array();
                    }    
                
                $_group_title                       =   array();
                $_licence_prefix                    =   array();
                $_max_keys                          =   array();
                $_max_instances_per_key             =   array();
                $_use_predefined_keys               =   array();
                $_product_use_expire                =   array();
                $_product_expire_renew_price        =   array();
                $_product_expire_units              =   array();
                $_product_expire_time               =   array();
                $_product_expire_starts_on_activate =   array();
                $_product_expire_disable_update_link=   array();
                $_product_expire_limit_api_usage    =   array();
                $_product_expire_notice             =   array();
                
                foreach($product_sl_groups  as  $product_sl_group)
                    {
                        $_group_title[]                     =   $product_sl_group['group_title'];
                        $_licence_prefix[]                  =   $product_sl_group['licence_prefix'];
                        //$_max_keys[]                =   $product_sl_group['max_keys']   *   intval($order_product['qty']);
                        $_max_keys[]                        =   $product_sl_group['max_keys'];
                        $_max_instances_per_key[]           =   $product_sl_group['max_instances_per_key'];
                        $_use_predefined_keys[]             =   $product_sl_group['use_predefined_keys'];
                        
                        $_product_use_expire[]                =   $product_sl_group['product_use_expire'];
                        $_product_expire_renew_price[]        =   $product_sl_group['product_expire_renew_price'];
                        $_product_expire_units[]              =   $product_sl_group['product_expire_units'];
                        $_product_expire_time[]               =   $product_sl_group['product_expire_time'];
                        $_product_expire_starts_on_activate[] =   $product_sl_group['product_expire_starts_on_activate'];
                        $_product_expire_disable_update_link[]=   $product_sl_group['product_expire_disable_update_link'];
                        $_product_expire_limit_api_usage[]    =   $product_sl_group['product_expire_limit_api_usage'];
                        $_product_expire_notice[]             =   $product_sl_group['product_expire_notice'];
                    }
                
                $data['group_title']                            =   $_group_title;
                $data['licence_prefix']                         =   $_licence_prefix;
                $data['max_keys']                               =   $_max_keys;
                $data['max_instances_per_key']                  =   $_max_instances_per_key;
                $data['use_predefined_keys']                    =   $_use_predefined_keys;
                $data['product_use_expire']                     =   $_product_use_expire;
                $data['product_expire_renew_price']             =   $_product_expire_renew_price;
                $data['product_expire_units']                   =   $_product_expire_units;
                $data['product_expire_time']                    =   $_product_expire_time;
                $data['product_expire_starts_on_activate']      =   $_product_expire_starts_on_activate;
                $data['product_expire_disable_update_link']     =   $_product_expire_disable_update_link;
                $data['product_expire_limit_api_usage']         =   $_product_expire_limit_api_usage;
                $data['product_expire_notice']                  =   $_product_expire_notice;
                
                
                $data   =   apply_filters('woo_sl/order_processed/product_sl', $data, $order_product, $order_id);
                
                wc_update_order_item_meta($key, '_woo_sl', $data);
                
                //set currently as inactive
                wc_update_order_item_meta($key, '_woo_sl_licensing_status', 'inactive');
                    
                foreach ( $data['product_use_expire']    as  $data_key    =>  $data_block_value )
                    {
                        if ( $data_block_value    !=  'no' )
                            {
                                wc_update_order_item_meta($key, '_woo_sl_licensing_using_expire', $data_block_value );
                        
                                //continue only if expire_starts_on_activate is not set to yes
                                $expire_starts_on_activate  =   $data['product_expire_starts_on_activate'][$data_key];
                                if ( $expire_starts_on_activate ==  'yes' )
                                    {
                                        //set currently as not-activated
                                        wc_update_order_item_meta($key, '_woo_sl_licensing_status', 'not-activated');
                                        continue;
                                    }
                        
                                if ( $data_block_value    ==  'yes' )
                                    {
                                        $today      =   date("Y-m-d", current_time( 'timestamp' ));
                                        $start_at   =   strtotime($today);
                                        wc_update_order_item_meta($key, '_woo_sl_licensing_start', $start_at);
                                                                                    
                                        $_sl_product_expire_units   =   $data['product_expire_units'][$data_key];
                                        $_sl_product_expire_time    =   $data['product_expire_time'][$data_key];
                                        
                                        $expire_at  =   strtotime( " + " . $_sl_product_expire_units . " " . $_sl_product_expire_time,  $start_at);
                                            
                                        wc_update_order_item_meta($key, '_woo_sl_licensing_expire_at', $expire_at);
                                    }
                            }
                    }
                
            }

    }
}