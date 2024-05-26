<?php
namespace WPFunnelsPro\Frontend;

use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Frontend\Modules\Analytics\Analytics;
use WPFunnelsPro\Wpfnl_Pro;
use WPFunnelsPro\Wpfnl_Pro_functions;
use WPFunnelsPro\Frontend\SkipOffer;
use Wpfnl_Pro_GBF_Offer_Conditions_Factory;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://rextheme.com
 * @since      1.0.0
 *
 * @package    Wpfnl_Pro
 * @subpackage Wpfnl_Pro/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wpfnl_Pro
 * @subpackage Wpfnl_Pro/public
 * @author     RexTheme <support@rextheme.com>
 */
class Wpfnl_Pro_Public {

    private static $instance;

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;


    /**
     * Get class instance.
     *
     * @return object Instance.
     */
    public static function getInstance() {
        if (!self::$instance) {
            // new self() will refer to the class that uses the trait
            self::$instance = new self();
        }

        return self::$instance;
    }

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct( ) {
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_styles'),9999);
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts'),9999 );
        add_action( 'wp_footer', array($this, 'load_offer_loader') );
        add_action( 'init', array( $this, 'init_funnel' ), 9999 );
        add_action( 'init', array( $this, 'init_skip_offer_class' ), 9999 );

        /** this is the hook where we checked if the funnel has any offer step for payments. */
        add_action( 'wpfunnels/funnel_order_placed', array( $this, 'maybe_setup_offer_funnel' ), 9999, 1 );
        add_action( 'wp', [$this, 'wpfnl_get_ab_testing_variation'], 10 );
        

        add_action( 'wpfunnels/offer_accepted', array( $this, 'add_accepted_offer_details_to_logger' ), 10, 2 );
        add_action( 'wpfunnels/offer_rejected', array( $this, 'add_rejected_offer_details_to_logger' ), 10, 2 );

        wp_ajax_helper()->handle('wpfnl-get-variation-price')
            ->with_callback([ $this, 'wpfnl_get_variation_price' ]);

		add_action( 'wp_ajax_nopriv_wpfnl_set_bounce_rate', [ $this, 'set_bounce_rate' ] );
		add_action( 'wp_ajax_wpfnl_set_bounce_rate', [ $this, 'set_bounce_rate' ] );

        add_action('wp_footer', [ $this, 'add_fraudnet_checkout_script'] );
	}


    /**
     * set plugin name
     *
     * @param $plugin_name
     */
	public function set_name( $plugin_name ) {
        $this->plugin_name = $plugin_name;
    }

    /**
     * set plugin version
     * @param $version
     */
    public function set_version( $version ) {
        $this->version = $version;
    }

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpfnl_Pro_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpfnl_Pro_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/css/wpfnl-pro-public.css', array(), $this->version, 'all' );

        if( Wpfnl_Pro_functions::maybe_unsupported_payment_gateway() ){
            global $post;
            $step_id                    = $post->ID;
            $order_id = ( isset( $_GET['wpfnl-order'] ) ) ? intval( $_GET['wpfnl-order'] ) : 0;
            $offer_product              = Wpfnl_Pro_functions::get_offer_product_data( $step_id, '', '', $order_id );
            $product_id                 = '';


            if ( is_array($offer_product) && !empty($offer_product)) {
                $product_id = $offer_product['id'];
            }
           
            if( $product_id ){
                wp_enqueue_style('woocommerce-layout'); 
                wp_enqueue_style('woocommerce-smallscreen'); 
                wp_enqueue_style('woocommerce-general'); 
            }
        }

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpfnl_Pro_Loader as all the hooks are defined
		 * in that particular class.
		 *
		 * The Wpfnl_Pro_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( Wpfnl_functions::is_funnel_step_page() ) {

            if( Wpfnl_Pro_functions::maybe_unsupported_payment_gateway() ){
                global $post;
                $step_id                    = $post->ID;
                $order_id = ( isset( $_GET['wpfnl-order'] ) ) ? intval( $_GET['wpfnl-order'] ) : 0;
                $offer_product              = Wpfnl_Pro_functions::get_offer_product_data( $step_id, '', '', $order_id );
                $product_id                 = '';


                if ( is_array($offer_product) && !empty($offer_product)) {
                    $product_id = $offer_product['id'];
                }
                if( $product_id ){
                    wp_enqueue_script('woocommerce');
                    wp_enqueue_script('wc-checkout');
                    wp_enqueue_script('wc-cart');
                    wp_enqueue_script('wc-cart-fragments');
                    wp_enqueue_script('wc-add-to-cart');
                    wp_enqueue_script('wc-single-product');
                    wp_enqueue_script('wc-add-to-cart-variation');
                    wp_enqueue_script('wc-credit-card-form');
                    wp_enqueue_script('wc-add-payment-method');
                }
            }

			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/js/wpfnl-pro-public.js', [ 'jquery' ], $this->version, true );
			wp_localize_script( $this->plugin_name, 'wpfnl_pro_obj', [
					'ajaxurl'           => admin_url( 'admin-ajax.php' ),
					'funnel_id'         => get_post_meta( get_the_ID(), '_funnel_id', true ),
					'step_id'           => get_the_ID(),
					'ajaxNonce'        => wp_create_nonce( 'wpfnl_public_pro' ),
					'is_user_logged_in' => is_user_logged_in(),
					'user_id'           => get_current_user_id(),
				]
			);
		}
	}
    
    /**
     * add_fraudnet_checkout_script: enabled to add fraudnet script to send fraud data to paypal
     *
     * @since 2.1.1
     * @return void
     */
    public function add_fraudnet_checkout_script() {

        if ( Wpfnl_functions::is_funnel_step_page() ) {
            if(Wpfnl_functions::check_if_this_is_step_type( 'checkout') || Wpfnl_functions::check_if_this_is_step_type( 'upsell') || Wpfnl_functions::check_if_this_is_step_type( 'downsell')) {
                $paypal_enabled = false;
                $guid = Wpfnl_Pro_functions::generate_guid();
                $paypal_merchant_id = '';
                $gateways = Wpfnl_Pro_functions::get_available_payment_methods();
    
                $paypal_settings = get_option('woocommerce-ppcp-settings');
    
                if ($paypal_settings && isset($paypal_settings['merchant_id'])) {
                    $paypal_merchant_id = $paypal_settings['merchant_id'];
                }
    
                if(!empty($gateways)){
                    foreach ($gateways as $gateway_key => $gateway) {
                        if($gateway == 'PayPal'){
                            $paypal_enabled = true;
                        }
                    }
                }
                
                if($paypal_enabled) {
                    ?>
                    <script type="application/json" fncls="fnparams-dede7cc5-15fd-4c75-a9f4-36c430ee3a99">
                        {
                            "f":"<?php echo $guid; ?>",
                            "s":"<?php echo $paypal_merchant_id . '_' . get_the_id(); ?>",
                            "sandbox":false
                        }
                    </script>
                    <?php  
                }
            }
        }

    }


    /**
     * load offer loader screen
     */
	public function load_offer_loader() {
        if( Wpfnl_Pro_functions::is_upsell_downsell_step() ) {
            ?>
            <div class="wpfunnels-offer-loader">
                <div class="wpfunnels-offer-loader-wrapper">
                    <div class="wpfnl-loader"></div>
                    <h2 class="loader-ittle">
                        Loading
                        <span class="dot-wrapper">
                            <span class="dot-one">.</span> 
                            <span class="dot-two">.</span> 
                            <span class="dot-three">.</span>
                        </span>
                    </h2>
                    <p class="description"><?php echo __('Please wait while processing your order', 'wpfnl-pro'); ?></p>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Init funnel session
     * 
     * @since 1.0.0
     */
	public function init_funnel() {
	    $this->initialize_funnel_session();
    }


    /**
     * Init skip offer class
     * 
     * @since 1.8.4
     */
    public function init_skip_offer_class(){
        $skip_offer_instance =   SkipOffer::getInstance();
        $skip_offer_instance->init();
    }



    /**
     * start session for a funnel
     *
     * @since 1.0.0
     */
    private function initialize_funnel_session() {
	    if(Wpfnl_functions::is_funnel_step_page()) {
	        $funnel_id  =  Wpfnl_functions::get_funnel_id();
            if ( ! $funnel_id ) {
                return;
            }
            if ( Wpfnl_functions::check_if_this_is_step_type('landing') || Wpfnl_functions::check_if_this_is_step_type('checkout') ) {
                $data = array(
                    'funnel_id'     => $funnel_id,
                    'steps'         => get_post_meta( $funnel_id, '_steps_order', true ),
                );
                Wpfnl_Pro::instance()->session->set_session( $funnel_id, $data );
            }
            elseif ( Wpfnl_functions::check_if_this_is_step_type('thankyou') ) {
                Wpfnl_Pro::instance()->session->destroy_session( $funnel_id );
            }
            elseif ( Wpfnl_functions::check_if_this_is_step_type('upsell') || Wpfnl_functions::check_if_this_is_step_type('downsell') ) {
                if ( ! ( is_user_logged_in() && current_user_can( 'manage_options' ) ) ) {
                    if ( ! Wpfnl_Pro::instance()->session->is_active_session( $funnel_id ) ) {
                        wp_die( esc_html__( 'Your session is expired', 'wpfnl-pro' ) );
                    }
                }
            }

        }
    }


    /**
     * may be setup offer funnel
     *
     * @param string $order_id
     *
     * @since 2.2.6
     */
    public function maybe_setup_offer_funnel( $order_id = '' ) {
        if ( empty( $order_id ) ) {
            return;
        }
        $order = wc_get_order( $order_id );
        $this->start_offer_funnel($order);
    }


    /**
     * setup offer funnels
     *
     * @param string $order
     */
    public function start_offer_funnel( $order ) {
        $is_offer_funnel 	= Wpfnl_Pro_functions::is_offer_exists( $order );
        
        if( $is_offer_funnel ) {
            $order_gateway          = $order->get_payment_method();
            $payment_gateway_obj    = Wpfnl_Pro::instance()->payment_gateways->build_gateway($order_gateway);
            if ( $payment_gateway_obj ) {
                ob_start();
                do_action( 'wpfunnels/offer_funnel_started', $order );
                ob_get_clean();
            }
        }
    }


    /**
     * 
     */
    public function wpfnl_get_variation_price($payload){
       
        $response = [
            'success' => false,
            'data'    => 'data not found'
        ];
        if( isset($payload['product_id']) && $payload['product_id'] ){
            if( isset($payload['attr']) && $payload['attr'] ){

                $variation_id = (new \WC_Product_Data_Store_CPT())->find_matching_product_variation(
                    new \WC_Product($payload['product_id']),
                    $payload['attr']
                );

                if( $variation_id ){
                   $product = wc_get_product($variation_id);
                }else{
                    $product = wc_get_product($payload['product_id']);
                }
                $response = [
                    'success' => true,
                    'data'    => $product ? $product->get_price_html() : '',
                ];
            }
        }
        return $response;
    }


    /**
     * Get AB testing variation for view in frontend
     * 
     * @since 1.7.3
     * @return @void
     */
    public function wpfnl_get_ab_testing_variation(){
        $get = Wpfnl_Pro_functions::get_sanitized_get_post();
        if( isset($get['get']['wpfnl-step-id']) ){
            if (class_exists('\WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing')) {
                $instance = new \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;
                $function_exist = is_callable(array($instance, 'get_ab_testing_variation_id'));
                if( $function_exist ){
                    $step_id = $get['get']['wpfnl-step-id'];
                    $funnel_id = Wpfnl_functions::get_funnel_id_from_step( $step_id );
                    $displayable_variation_id = \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing::get_ab_testing_variation_id( $funnel_id, $step_id );
                    if( $displayable_variation_id ){
                        $url = get_the_permalink($displayable_variation_id);
                        $utm_settings 		= Wpfnl_functions::get_funnel_utm_settings( $funnel_id );
                        if($utm_settings['utm_enable'] == 'on') {
                            unset($utm_settings['utm_enable']);
                            $url = add_query_arg($utm_settings,$url);
                            $url   = strtolower($url);
                        }
                        wp_safe_redirect( $url );
                        exit;
                    }
                }
            }
        }
    }


    /**
     * Determine and Redirect to the Appropriate A/B Testing Variation URL.
     *
     * This function manages the process of selecting the appropriate A/B testing variation
     * based on the provided 'wpfnl-step-id' GET parameter and redirects users to the URL
     * of the chosen variation. It integrates with the 'Wpfnl_Ab_Testing' class for variation
     * selection and redirection.
     *
     * @since 1.7.3
     * 
     * @return void
     */
    public function redirect_to_ab_variation_url() {
        // Get sanitized GET and POST data.
        $get = Wpfnl_Pro_functions::get_sanitized_get_post();

        // Check if 'wpfnl-step-id' is set in the request.
        if ( isset( $get['get']['wpfnl-step-id'] ) ) {

            $step_id    = $get['get']['wpfnl-step-id'];
            $funnel_id  = Wpfnl_functions::get_funnel_id_from_step( $step_id );

            // Get the displayable variation ID using the 'get_ab_testing_variation_id' function.
            $displayable_variation_id = \WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing::get_ab_testing_variation_id( $funnel_id, $step_id );

            if ( $displayable_variation_id ) {
                $url = Wpfnl_functions::get_step_url( $funnel_id, $displayable_variation_id );
                if( $url ){
                    // Redirect to the URL of the displayable variation.
                    wp_safe_redirect( $url );
                    exit;
                }
            }
        }
    }


    /**
     * @desc Skip the next offer step(s) for gbf
     * if the assigned product is out of stock.
     * @since 1.6.21
     * @param $next_step
     * @return mixed
     */
    private function skip_next_step_for_gbf( $funnel_id, $step_id ){
        
        $step_type          = get_post_meta($step_id, '_step_type', true);
        $offer_product      = [];
        $order_id           = ( isset( $_POST['order_id'] ) ) ? intval( $_POST['order_id'] ) : (( isset( $_GET['wpfnl-order'] ) ) ? intval( $_GET['wpfnl-order'] ) : 0);
        $gbf_product        = $this->get_gbf_product_from_cookie();
        $gb_funnel_settings = wpfnl()->meta->get_funnel_meta( $funnel_id, 'global_funnel_start_condition' );
        if( $gb_funnel_settings && is_array( $gb_funnel_settings ) ) {
            $offer_mappings  = wpfnl()->meta->get_funnel_meta( $step_id, "global_funnel_{$step_type}_rules" );
            if( isset($offer_mappings['type']) ){
                if( 'specificProduct' == $offer_mappings['type'] ){
                    $product = isset( $offer_mappings['show'] ) && $offer_mappings['show'] ? wc_get_product( $offer_mappings['show'] ) : '';
                    if( $product ){
                        if( $product->get_type() == 'variable' ){
                            return $offer_product;
                        }
                    }
                }
                $param_type = Wpfnl_Pro_GBF_Offer_Conditions_Factory::build($offer_mappings['type']);
                $offer_product   = $param_type->get_offer_product( $offer_mappings, $order_id, $step_id, $gbf_product );
            }
        }
        return $offer_product;
    }

    /**
     * Get GBF product from cookie data
     * 
     * @return Array
     */
    private function get_gbf_product_from_cookie() {
        if( Wpfnl_functions::is_wc_active() ){
            return WC()->session->get('wpfunnels_global_funnel_specific_product') ? WC()->session->get('wpfunnels_global_funnel_specific_product') : [];
        }
        return [];
    }


    /**
     * Add offer details to logger after accepted
     * @param Obj $order
     * @param Array $offer_product
     * 
     * @return void
     * @since 1.6.28
     */
    public function add_accepted_offer_details_to_logger( $order, $offer_product ){
        $this->prepare_offer_data_for_logger( $offer_product, 'Accepted' );
    }
    
    
    /**
     * Add offer details to logger after rejected
     * @param Obj $order
     * @param Array $offer_product
     * 
     * @return void
     * @since 1.6.28
     */
    public function add_rejected_offer_details_to_logger( $order, $offer_product ){
        $this->prepare_offer_data_for_logger( $offer_product, 'Rejected' );
    }


    /**
     * Prepare offer product data for logger
     * 
     */
    private function prepare_offer_data_for_logger( $offer_product, $status ){
        
        $wp_function =  new \WPFunnels\Wpfnl_functions();
        if( is_callable(array($wp_function, 'maybe_logger_enabled')) ){
            if (class_exists("Wpfnl_Logger")) {
                if( Wpfnl_functions::maybe_logger_enabled() && Wpfnl_functions::is_wc_active() ) {
                    $product_array[] = [
                        'name'  => isset($offer_product['name'])    ? $offer_product['name']    : '',
                        'price' => isset($offer_product['price'])   ? $offer_product['price']   : '',
                    ];

                    ob_start();
                    print_r($product_array);
                    $textual_representation = ob_get_contents();
                    ob_end_clean();

                    \Wpfnl_Logger::modify_log_file( 'event',$textual_representation,'Offer Product '.$status );
                }
            }
        } 
    }

	/**
	 * Sets the bounce rate for a specific user within a funnel step.
	 *
	 * This function is designed to handle an AJAX request for updating the bounce rate
	 * of a user in the context of a specific funnel step. Bounce rate typically indicates
	 * whether a user engaged with the content on the step or left the page quickly.
	 *
	 * @since  1.9.6
	 */
	public function set_bounce_rate() {
		// Verify the AJAX request nonce for security.
		check_ajax_referer( 'wpfnl_public_pro', 'security' );

		// Get the funnel ID, step ID, user ID, and user IP from the AJAX request.
		$funnel_id = filter_input( INPUT_POST, 'funnel_id', FILTER_SANITIZE_NUMBER_INT );
		$step_id   = filter_input( INPUT_POST, 'step_id', FILTER_SANITIZE_NUMBER_INT );
		$user_id   = filter_input( INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT );
		$user_ip   = Analytics::getInstance()->get_the_user_ip();

		// Retrieve the analytics ID for the given funnel, step, user, and IP combination.
		$analytics_id = $this->get_analytics_id( $funnel_id, $step_id, $user_id, $user_ip );

		// If an analytics record is found, update the 'bounced' meta key to 'no'.
		if ( $analytics_id ) {
			global $wpdb;
			$analytics_meta = $wpdb->prefix . 'wpfnl_analytics_meta';

			$wpdb->update(
				$analytics_meta,
				[
					'analytics_id' => $analytics_id,
					'meta_key'     => 'bounced',
					'meta_value'   => 'no',
				],
				[
					'analytics_id' => $analytics_id,
					'meta_key'     => 'bounced',
				]
			);
		}

		// Send a JSON success response to the AJAX request.
		wp_send_json_success();

		// Terminate the script execution.
		wp_die();
	}

	/**
	 * Get the analytics ID for a specific user within a funnel step.
	 *
	 * This function queries the database to retrieve the analytics ID that corresponds
	 * to a specific combination of funnel, step, user, and user IP. It is used to uniquely
	 * identify an analytics record for a user's interaction with a funnel step.
	 *
	 * @param int    $funnel_id The ID of the funnel.
	 * @param int    $step_id   The ID of the funnel step.
	 * @param int    $user_id   The ID of the user.
	 * @param string $user_ip   The IP address of the user.
	 *
	 * @return int|false The analytics ID if found, or false if not found.
	 * @since  1.9.6
	 */
	public function get_analytics_id( $funnel_id, $step_id, $user_id, $user_ip ) {
		global $wpdb;
		$analytics_table = $wpdb->prefix . 'wpfnl_analytics';

		// Prepare and construct the SQL query to retrieve the analytics ID.
		$query = $wpdb->prepare( 'SELECT `id` FROM %i ', $analytics_table );
		$query .= $wpdb->prepare( 'WHERE `funnel_id` = %d ', $funnel_id );
		$query .= $wpdb->prepare( 'AND `step_id` = %d ', $step_id );
		$query .= $wpdb->prepare( 'AND `user_id` = %d ', $user_id );
		$query .= $wpdb->prepare( 'AND `user_ip` = %s ', $user_ip );
		$query .= 'ORDER BY `id` DESC';

		// Execute the query and retrieve the analytics ID.
		return $wpdb->get_var( $query );
	}

}
