<?php
/**
 * WooCommerce Plugin Framework
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the plugin to newer
 * versions in the future. If you wish to customize the plugin for your
 * needs please refer to http://www.skyverge.com
 *
 * @package   SkyVerge/WooCommerce/API
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace WPFunnelsPro\Frontend\Gateways\API;

defined( 'ABSPATH' ) or exit;

class Wpfnl_Pro_API_Base {


    public $parameters;
    /** @var string request method, defaults to POST */
    protected $request_method = 'POST';
    /** @var string URI used for the request */
    protected $request_uri;
    /** @var array request headers */
    protected $request_headers = array();
    /** @var string request user-agent */
    protected $request_user_agent;
    /** @var string request HTTP version, defaults to 1.0 */
    protected $request_http_version = '1.0';
    /** @var string request duration */
    protected $request_duration;
    /** @var object request */
    protected $request;
    /** @var string response code */
    protected $response_code;
    /** @var string response message */
    protected $response_message;
    /** @var array response headers */
    protected $response_headers;
    /** @var string raw response body */
    protected $raw_response_body;
    /** @var string response handler class name */
    protected $response_handler;
    /** @var object response */
    protected $response;

    /**
     * Add multiple parameters
     *
     * @param array $params
     *
     * @since 2.0
     */
    public function add_parameters( array $params ) {
        foreach ( $params as $key => $value ) {
            $this->add_parameter( $key, $value );
        }
    }

    /**
     * Add a parameter
     *
     * @param string $key
     * @param string|int $value
     *
     * @since 2.0
     */
    public function add_parameter( $key, $value ) {
        $this->parameters[ $key ] = $value;
    }

    public function clean_params() {
        $this->parameters = array();
    }

    public function get_parameters() {

        $this->parameters = apply_filters( 'wpfnl_api_args', $this->parameters, $this );

        // validate parameters
        foreach ( $this->parameters as $key => $value ) {

            // remove unused params
            if ( '' === $value || is_null( $value ) ) {
                unset( $this->parameters[ $key ] );
            }
        }

        return $this->parameters;
    }

    /**
     * Perform the request and return the parsed response
     *
     * @param object $request class instance which implements \SV_WC_API_Request
     *
     * @return object class instance which implements \SV_WC_API_Response
     * @throws Exception
     * @since 2.2.0
     *
     */
    protected function perform_request( $request ) {

        // ensure API is in its default state
        $this->reset_response();

        // save the request object
        $this->request = $request;

        $start_time = microtime( true );
        // perform the request
        $response = $this->do_remote_request( $this->get_request_uri(), $this->get_request_args() );
        // calculate request duration
        $this->request_duration = round( microtime( true ) - $start_time, 5 );

        try {

            // parse & validate response
            $response = $this->handle_response( $response );


        } catch ( \Exception $e ) {

            // alert other actors that a request has been made
            $this->broadcast_request();
            throw $e;
        }

        return $response;
    }

    /**
     * Reset the API response members to their
     *
     * @since 1.0.0
     */
    protected function reset_response() {

        $this->response_code     = null;
        $this->response_message  = null;
        $this->response_headers  = null;
        $this->raw_response_body = null;
        $this->response          = null;
        $this->request_duration  = null;
    }

    /**
     * Simple wrapper for wp_remote_request() so child classes can override this
     * and provide their own transport mechanism if needed, e.g. a custom
     * cURL implementation
     *
     * @param string $request_uri
     * @param string $request_args
     *
     * @return array|WP_Error
     * @since 2.2.0
     *
     */
    protected function do_remote_request( $request_uri, $request_args ) {
        return wp_safe_remote_request( $request_uri, $request_args );
    }

    /**
     * Get the request URI
     *
     * @return string
     * @since 2.2.0
     */
    protected function get_request_uri() {

        // API base request URI + any request-specific path
        $uri = $this->request_uri . ( $this->get_request() ? $this->get_request()->path : '' );

        /**
         * Request URI Filter.
         *
         * Allow actors to filter the request URI. Note that child classes can override
         * this method, which means this filter may be invoked prior to the overridden
         * method.
         *
         * @param string $uri current request URI
         * @param \Wpfnl_Pro_API_Base class instance
         *
         * @since 4.1.0
         *
         */
        return apply_filters( 'wc_' . $this->get_api_id() . '_api_request_uri', $uri, $this );
    }

    /**
     * Returns the most recent request object
     *
     * @return object the most recent request object
     * @see \SV_WC_API_Request
     * @since 2.2.0
     */
    public function get_request() {
        return $this->request;
    }

    /**
     * Get the ID for the API, used primarily to namespace the action name
     * for broadcasting requests
     *
     * @return string
     * @since 2.2.0
     */
    protected function get_api_id() {

        return 'wpfunnels-pro';
    }

    /**
     * Get the request arguments in the format required by wp_remote_request()
     *
     * @return mixed|void
     * @since 2.2.0
     */
    protected function get_request_args() {

        $args = array(
            'method'      => $this->get_request_method(),
            'timeout'     => MINUTE_IN_SECONDS,
            'redirection' => 0,
            'httpversion' => $this->get_request_http_version(),
            'sslverify'   => true,
            'blocking'    => true,
            'user-agent'  => $this->get_request_user_agent(),
            'headers'     => $this->get_request_headers(),
            'body'        => $this->get_request()->body,
            'cookies'     => array(),
        );



        /**
         * Request arguments.
         *
         * Allow other actors to filter the request arguments. Note that
         * child classes can override this method, which means this filter may
         * not be invoked, or may be invoked prior to the overridden method
         *
         * @param array $args request arguments
         * @param \Wpfnl_Pro_API_Base class instance
         *
         * @since 2.2.0
         *
         */
        return apply_filters( 'wc_' . $this->get_api_id() . '_http_request_args', $args, $this );
    }

    /**
     * Get the request method, POST by default
     *
     * @return string
     * @since 2.2.0
     */
    protected function get_request_method() {
        // if the request object specifies the method to use, use that, otherwise use the API default
        return $this->get_request() && $this->get_request()->method ? $this->get_request()->method : $this->request_method;
    }

    /** Request Getters *******************************************************/

    /**
     * Get the request HTTP version, 1.1 by default
     *
     * @return string
     * @since 2.2.0
     */
    protected function get_request_http_version() {

        return $this->request_http_version;
    }

    /**
     * Get the request user agent, defaults to:
     *
     * Dasherized-Plugin-Name/Plugin-Version (WooCommerce/WC-Version; WordPress/WP-Version)
     *
     * @return string
     * @since 2.2.0
     */
    protected function get_request_user_agent() {
        return '';

        return sprintf( '%s/%s (WooCommerce/%s; WordPress/%s)', str_replace( ' ', '-', $this->get_plugin()->get_plugin_name() ), $this->get_plugin()->get_version(), WC_VERSION, $GLOBALS['wp_version'] );
    }

    /**
     * Get the request headers
     *
     * @return array
     * @since 2.2.0
     */
    protected function get_request_headers() {
        return $this->request_headers;
    }

    /**
     * Handle and parse the response
     *
     * @param array|WP_Error $response response data
     *
     * @return object request class instance that implements SV_WC_API_Request
     * @throws Exception network issues, timeouts, API errors, etc
     * @since 2.2.0
     *
     */
    protected function handle_response( $response ) {

        // check for WP HTTP API specific errors (network timeout, etc)
        if ( is_wp_error( $response ) ) {
            throw new \Exception( $response->get_error_message(), (int) $response->get_error_code() );
        }

        // set response data
        $this->response_code     = wp_remote_retrieve_response_code( $response );
        $this->response_message  = wp_remote_retrieve_response_message( $response );
        $this->response_headers  = wp_remote_retrieve_headers( $response );
        $this->raw_response_body = wp_remote_retrieve_body( $response );



        // allow child classes to validate response prior to parsing -- this is useful
        // for checking HTTP status codes, etc.
        $this->do_pre_parse_response_validation();

        // parse the response body and tie it to the request
        $this->response = $this->get_parsed_response( $this->raw_response_body );

        // allow child classes to validate response after parsing -- this is useful
        // for checking error codes/messages included in a parsed response
        $this->do_post_parse_response_validation();

        // fire do_action() so other actors can act on request/response data,
        // primarily used for logging
        $this->broadcast_request();
        
        return $this->response;
    }

    /**
     * Allow child classes to validate a response prior to instantiating the
     * response object. Useful for checking response codes or messages, e.g.
     * throw an exception if the response code is not 200.
     *
     * A child class implementing this method should simply return true if the response
     * processing should continue, or throw a \SV_WC_API_Exception with a
     * relevant error message & code to stop processing.
     *
     * Note: Child classes *must* sanitize the raw response body before throwing
     * an exception, as it will be included in the broadcast_request() method
     * which is typically used to log requests.
     *
     * @since 2.2.0
     */
    protected function do_pre_parse_response_validation() {
        // stub method
    }

    /**
     * Return the parsed response object for the request
     *
     * @param string $raw_response_body
     *
     * @return object response class instance which implements SV_WC_API_Request
     * @since 2.2.0
     *
     */
    protected function get_parsed_response( $raw_response_body ) {

        /**
         * do parsing if necessary
         */

        return $raw_response_body;
    }

    /**
     * Allow child classes to validate a response after it has been parsed
     * and instantiated. This is useful for check error codes or messages that
     * exist in the parsed response.
     *
     * A child class implementing this method should simply return true if the response
     * processing should continue, or throw an Exception with a
     * relevant error message & code to stop processing.
     *
     * Note: Response body sanitization is handled automatically
     *
     * @since 2.2.0
     */
    protected function do_post_parse_response_validation() {
        // stub method
    }

    /**
     * Alert other actors that a request has been performed. This is primarily used
     * for request logging.
     *
     * @since 2.2.0
     */
    protected function broadcast_request() {

        $request_data = array(
            'method'     => $this->get_request_method(),
            'uri'        => $this->get_request_uri(),
            'user-agent' => $this->get_request_user_agent(),
            'headers'    => $this->get_sanitized_request_headers(),
            'body'       => $this->request->body,
            'duration'   => $this->get_request_duration() . 's', // seconds
        );

        $response_data = array(
            'code'    => $this->get_response_code(),
            'message' => $this->get_response_message(),
            'headers' => $this->get_response_headers(),
            'body'    => $this->get_sanitized_response_body() ? $this->get_sanitized_response_body() : $this->get_raw_response_body(),
        );

        do_action( 'wc_' . $this->get_api_id() . '_api_request_performed', $request_data, $response_data, $this );
    }


    /** Response Getters ******************************************************/

    /**
     * Get sanitized request headers suitable for logging, stripped of any
     * confidential information
     *
     * The `Authorization` header is sanitized automatically.
     *
     * Child classes that implement any custom authorization headers should
     * override this method to perform sanitization.
     *
     * @return array
     * @since 2.2.0
     */
    protected function get_sanitized_request_headers() {

        $headers = $this->get_request_headers();

        if ( ! empty( $headers['Authorization'] ) ) {
            $headers['Authorization'] = str_repeat( '*', strlen( $headers['Authorization'] ) );
        }

        return $headers;
    }

    /**
     * Get the request duration in seconds, rounded to the 5th decimal place
     *
     * @return string
     * @since 2.2.0
     */
    protected function get_request_duration() {
        return $this->request_duration;
    }

    /**
     * Get the response code
     *
     * @return string
     * @since 2.2.0
     */
    protected function get_response_code() {
        return $this->response_code;
    }

    /**
     * Get the response message
     *
     * @return string
     * @since 2.2.0
     */
    protected function get_response_message() {
        return $this->response_message;
    }

    /**
     * Get the response headers
     *
     * @return array
     * @since 2.2.0
     */
    protected function get_response_headers() {
        return $this->response_headers;
    }

    /**
     * Get the sanitized response body, provided by the response class
     * to_string_safe() method
     *
     * @return string|null
     * @since 2.2.0
     */
    protected function get_sanitized_response_body() {
        return is_callable( array( $this->get_response(), 'to_string_safe' ) ) ? $this->get_response()->to_string_safe() : null;
    }


    /** Misc Getters ******************************************************/

    /**
     * Returns the most recent response object
     *
     * @return object the most recent response object
     * @see \SV_WC_API_Response
     * @since 2.2.0
     */
    public function get_response() {
        return $this->response;
    }

    /**
     * Get the raw response body, prior to any parsing or sanitization
     *
     * @return string
     * @since 2.2.0
     */
    protected function get_raw_response_body() {
        return $this->raw_response_body;
    }

    /**
     * Get the response handler class name
     *
     * @return string
     * @since 2.2.0
     */
    protected function get_response_handler() {
        return $this->response_handler;
    }






    /** Setters ***************************************************************/

    /**
     * Set the response handler class name. This class will be instantiated
     * to parse the response for the request.
     *
     * Note the class should implement SV_WC_API
     *
     * @param string $handler handle class name
     *
     * @return array
     * @since 2.2.0
     *
     */
    protected function set_response_handler( $handler ) {
        $this->response_handler = $handler;
    }

    /**
     * Set a header request
     *
     * @param string $name header name
     * @param string $value header value
     *
     * @return string
     * @since 2.2.0
     *
     */
    protected function set_request_header( $name, $value ) {

        $this->request_headers[ $name ] = $value;
    }

    /**
     * Set HTTP basic auth for the request
     *
     * Since 2.2.0
     *
     * @param string $username
     * @param string $password
     */
    protected function set_http_basic_auth( $username, $password ) {

        $this->request_headers['Authorization'] = sprintf( 'Basic %s', base64_encode( "{$username}:{$password}" ) );
    }

    /**
     * Set the Content-Type request header
     *
     * @param string $content_type
     *
     * @since 2.2.0
     *
     */
    protected function set_request_content_type_header( $content_type ) {
        $this->request_headers['content-type'] = $content_type;
    }

    /**
     * Set the Accept request header
     *
     * @param string $type the request accept type
     *
     * @since 2.2.0
     *
     */
    protected function set_request_accept_header( $type ) {
        $this->request_headers['accept'] = $type;
    }
}