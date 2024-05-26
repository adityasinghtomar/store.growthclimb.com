<?php

namespace WPFunnelsPro\Orders;


use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

class Orders {

    use SingletonTrait;

    public function __construct() {
        add_filter( 'woocommerce_register_shop_order_post_statuses', array( $this, 'register_new_order_status' ), 99 );
        add_filter( 'wc_order_statuses', array( $this, 'update_to_new_stauses' ), 99 );
        add_action( 'wpfnl_cron_update_order_status', array( $this, 'schedule_update_order_status' ), 99, 3 );

        $offer_settings = Wpfnl_functions::get_offer_settings();
        if( isset($offer_settings['offer_orders']) && 'main-order' === $offer_settings['offer_orders'] ) {
           
            add_action( 'wpfunnels/offer_funnel_started', array( $this, 'assign_wpfnl_status_to_main_order' ), 10 );
        }
        add_action('wpfunnels/update_order_status_processing',array($this,'update_status_funnels_processing'),10,3);

        add_filter( 'woocommerce_email_actions', array($this,'set_woocommerce_email_hook_funnel_order') );
        add_action('woocommerce_order_status_changed', array($this,'send_email_funner_order_to_processing'), 20, 4 );


        add_action('wpfunnels/page_is_thankyou',array($this,'page_is_thankyou'),10,2);
        add_action('wp',array($this,'maybe_thankyou_page'),10);
    }


    /**
     * Maybe thankyou page
     * If thankyou page then update the order status
     * 
     * @since 2.0.0
     */
    public function maybe_thankyou_page(){
        if (Wpfnl_functions::check_if_this_is_step_type('thankyou')) {
          	
			if( isset($_GET['wpfnl-order']) ){
				$order_id = $_GET['wpfnl-order'];
				$order = wc_get_order($order_id);
                
                if ( false === is_a( $order, 'WC_Order' ) ) {
                    return; // Order not found
                }
                
				$order_status = $order->get_status();

				$status = [
					'wc-wpfnl-main-order',
					'wpfnl-main-order',
				];
				if ( !in_array($order_status, $status) ) {
					return;
				}
				$this->chanage_normalize_status( $order, $order_status, $normal_status='processing');
			}
        }
    }

    /**
     * Register new order status.
     * @param string $order_status order status.
     * @return array
     */
    public function register_new_order_status( $order_status ) {

        $order_status_title = _x( 'Funnel Order Accepted', 'Order status', 'wpfnl-pro' );
        $partial_order_status_title = _x( 'Funnel Partially Refunded', 'Order status', 'wpfnl-pro' );

        $order_status[ 'wc-wpfnl-main-order' ] = array(
            'label'                     => $order_status_title,
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Funnel Order Accepted <span class="count">(%s)</span>', 'Funnel Order Accepted <span class="count">(%s)</span>', 'wpfnl-pro' ),
        );
        return $order_status;
    }

    /**
     * Update native statuses.
     * @param string $order_status Order status.
     * @return array
     */
    public function update_to_new_stauses( $order_status ) {

        $order_status[ 'wc-wpfnl-main-order' ] = 'Funnel Order Accepted';

        return $order_status;
    }

    /**
     * Schedule normalize order status.
     * @param int    $order_id order id.
     * @param string $before_normal before status.
     * @param string $normal_status normal status.
     * @return void
     */
    public function schedule_update_order_status( $order_id, $before_normal, $normal_status ) {

        $order = wc_get_order( $order_id );
        $this->chanage_normalize_status( $order, $before_normal, $normal_status );
    }


    /**
     * Normalize order status.
     * @param array  $order order.
     * @param string $before_normal before status.
     * @param string $normal_status normal status.
     * @return void
     */
    public function chanage_normalize_status( $order, $before_normal = 'pending', $normal_status = 'processing' )
    {

        if (false === is_a($order, 'WC_Order')) {
            return;
        }

        $current_status = $order->get_status();

        $status = [
            'wc-wpfnl-main-order',
            'wpfnl-main-order',
        ];
     
        if ( !in_array($current_status, $status) ) {
            return;
        }

        if( $order ){
            $is_course = false;
            $is_downloadable_product = true;
            foreach( $order->get_items() as $item_id => $item ){
               
                $product = wc_get_product( $item->get_product_id() );
                if( $product && ( !$product->is_downloadable('yes') || !$product->get_virtual()) ){
                    $is_downloadable_product = false;
                }

                if( $product->get_type() == 'course' ){
                    $is_course = true;
                }
            }
        }

        $payment_method = $order->get_payment_method();
        if( apply_filters( 'wpfunnels/maybe_update_order_status', true ) ){
            if( $is_course ){
                if( 'cod' === $payment_method || 'bacs' === $payment_method ){
                    $order->update_status($before_normal);
                    $order->update_status($normal_status);
                }else{
                    $order->update_status('completed');
                }
            }else{
                if( !$is_downloadable_product || 'cod' === $payment_method || 'bacs' === $payment_method ){
                    $order->update_status($before_normal);
                    $order->update_status($normal_status);
                }else{
                    $order->update_status('completed');
                }
            }
        }
        
        do_action( 'wpfunnels/after_update_order_status', $order );
    }

    /**
     * register new wpfunnel order status to main order
     * @param $order
     */

    public function assign_wpfnl_status_to_main_order( $order ) {
        
        if ( ! is_a( $order, 'WC_Order' ) ) {
            return;
        }
        $payment_method = $order->get_payment_method();
        add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'assign_new_order_status' ), 999, 3 );
        if ( 'cod' === $payment_method || 'bacs' === $payment_method ) {
            return;
        }

    }

    /**
     * Assign new order status 'wc-wpfnl-main-order'
     * @param $order_status
     * @param $id
     * @param $order
     * @return string
     */

    public function assign_new_order_status( $order_status, $id, $order )
    {
        $new_status = 'wc-wpfnl-main-order';
        
        ob_start();
        do_action( 'wpfunnels/update_order_status_processing', $new_status, $order_status, $order );
        ob_get_clean();
        
        return 'wc-wpfnl-main-order';
    }

    /**
     * Run cron every 30 minutes
     * Update Funnels order status
     * Funnel order accpected to Processing
     * @param $new_status
     * @param $normal_status
     * @param $order
     */

    public function update_status_funnels_processing($new_status, $normal_status, $order){
       
        if ( false === is_a( $order, 'WC_Order' ) ) {
            return;
        }

        $status = [
            'wc-wpfnl-main-order',
            'wpfnl-main-order',
        ];
        
        if ( !in_array($new_status, $status) ) {
            return;
        }
        
        $args = array(
            'order_id'      => $order->get_id(),
            'before_normal' => $order->get_status(),
            'normal_status' => $normal_status,
        );
        
        if ( false === wp_next_scheduled( 'wpfnl_cron_update_order_status', $args ) ) {
           
            $cron_time = apply_filters( 'wpfnl_order_status_cron_time', 30 );
            wp_schedule_single_event( time() + ( $cron_time * MINUTE_IN_SECONDS ), 'wpfnl_cron_update_order_status', $args );

        }
    }

    /**
     * Check thank you page ad order status processing
     * @param $order_id
     */

    public function page_is_thankyou( $order_id ){

        $order = wc_get_order($order_id);
        if ( false === is_a( $order, 'WC_Order' ) ) {
            return false;
        }
        $order_status = $order->get_status();
        
        $status = [
            'wc-wpfnl-main-order',
            'wpfnl-main-order',
        ];
        if ( !in_array($order_status, $status) ) {
            return;
        }
        $this->chanage_normalize_status( $order, $order_status, $normal_status='processing');
    }

    /**
     * Set WP Funner order status in new email hook
     * @param $actions
     * @return mixed
     */

    public function set_woocommerce_email_hook_funnel_order($actions)
    {
        $actions[] = 'woocommerce_order_status_wpfnl-main-order';
        return $actions;
    }

    /**
     * Send Processing email when Order status change
     * @param $order_id
     * @param $old_status
     * @param $new_status
     * @param $order
     */

    function send_email_funner_order_to_processing( $order_id, $old_status, $new_status, $order ){
        $status = [
            'wc-wpfnl-main-order',
            'wpfnl-main-order',
        ];
        
        if ( in_array($old_status, $status) && $new_status == 'processing' ) {
            $wc_emails = WC()->mailer()->get_emails();
            $wc_emails['WC_Email_Customer_Processing_Order']->trigger( $order_id );
            $wc_emails['WC_Email_New_Order']->trigger( $order_id );
        }
        if(  in_array($old_status, $status) && $new_status == 'completed' ){
            $wc_emails = WC()->mailer()->get_emails();
            $wc_emails['WC_Email_Customer_Completed_Order']->trigger( $order_id );
        }
    }
}