<?php

namespace WPFunnelsPro\Session;

use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

class Wpfnl_Pro_Session {

    use SingletonTrait;

    public function __construct() {
        if ( ! defined( "WPFUNNELS_SESSION_EXPIRE_TIME" ) ) {
            define( 'WPFUNNELS_SESSION_EXPIRE_TIME', 30 );
        }

        if ( ! defined( "WPFUNNELS_SESSION_COOKIE" ) ) {
            define( 'WPFUNNELS_SESSION_COOKIE', 'wpfunnels_session_' );
        }
    }

    /**
     * get wp cookie path
     *
     * @return string|string[]|null
     */
    public function get_cookiepath() {

        return COOKIEPATH ? COOKIEPATH : '/';
    }


    /**
     * set transient and cookie data
     *
     * @param $key
     * @param $transient
     * @param $funnel_id
     * @since 1.0.0
     */
    public function set_transient( $key, $transient, $funnel_id ) {
        $expiration_time = WPFUNNELS_SESSION_EXPIRE_TIME;
        $cookiepath      = $this->get_cookiepath();
        setcookie(
            WPFUNNELS_SESSION_COOKIE . $funnel_id,
            $key,
            time() + $expiration_time * MINUTE_IN_SECONDS,
            $cookiepath,
            COOKIE_DOMAIN
        );
        set_transient( 'wpfunnels_data_' . $key, $transient, $expiration_time * MINUTE_IN_SECONDS );
        wp_cache_set( 'wpfunnels_data_' . $key, $transient );
    }


    /**
     * Set session data
     *
     * @param $funnel_id
     * @param array $data
     * @since 1.0.0
     */
    public function set_session( $funnel_id, $data = array() ) {

        if ( isset( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) ) {
            $key = sanitize_text_field( wp_unslash( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) );
        } else {
            $key = $funnel_id . '_' . md5( time() . wp_rand() );
        }

        $transient = $data;

        $this->set_transient( $key, $transient, $funnel_id );
    }


    /**
     * update session data
     *
     * @param $funnel_id
     * @param array $data
     * @since 1.0.0
     */
    public function update_session( $funnel_id, $data = array() ) {

        if ( ! isset( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) ) {
            $this->set_session( $funnel_id, $data );
        }
        $key = sanitize_text_field( wp_unslash( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) );

        $transient = get_transient( 'wpfunnels_data_' . $key );
        $this->set_transient( $key, $transient, $funnel_id );

    }


    /**
     * Destroy session data
     *
     * @param $funnel_id
     */
    public function destroy_session( $funnel_id ) {

        if ( isset( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) ) {
            $key        = sanitize_text_field( wp_unslash( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) );
            $cookiepath = $this->get_cookiepath();
            delete_transient( 'wpfunnels_data_' . $key );
            wp_cache_delete( 'wpfunnels_data_' . $key );
            unset( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] );
            setcookie( WPFUNNELS_SESSION_COOKIE . $funnel_id, $key, time() - 3600, $cookiepath, COOKIE_DOMAIN );
        }
    }


    /**
     * get session
     *
     * @param $funnel_id
     */
    public function get_session($funnel_id) {
        if ( isset( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) ) {
            $key = sanitize_text_field( wp_unslash( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) );
            $data = get_transient( 'wpfunnels_data_' . $key );
            return $data;
        }
    }


    /**
     * update transient data for funnel
     *
     * @param $funnel_id
     * @param array $data
     */
    public function update_data( $funnel_id, $data = array() ) {

        if ( isset( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) ) {
            $key = sanitize_text_field( wp_unslash( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) );
            $transient = get_transient( 'wpfunnels_data_' . $key );
            if ( ! is_array( $transient ) ) {
                $transient = array();
            }
            $transient          = array_merge( $transient, $data );
            $expiration_time    = WPFUNNELS_SESSION_EXPIRE_TIME;
            set_transient( 'wpfunnels_data_' . $key, $transient, $expiration_time * MINUTE_IN_SECONDS );
            wp_cache_set( 'wpfunnels_data_' . $key, $transient );
        }
    }


    /**
     * get transient data
     *
     * @param $funnel_id
     * @return array|false
     *
     * @since 1.0.0
     */
    public function get_data( $funnel_id ) {

        if ( isset( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) ) {
            $key = sanitize_text_field( wp_unslash( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) );
            // Try to grab the transient from the database, if it exists.
            $transient = get_transient( 'wpfunnels_data_' . $key );
            if ( is_array( $transient ) ) {
                return $transient;
            }
        }

        return false;
    }


    /**
     * check if session is active
     *
     * @param $funnel_id
     * @return mixed|void
     */
    public function is_session_active( $funnel_id ) {
        $is_active = false;
        if ( isset( $_GET['wcf-sk'] ) && isset( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) ) {
            $session_key  = sanitize_text_field( wp_unslash( $_GET['wpfnl-sk'] ) );
            $key = sanitize_text_field( wp_unslash( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) );
            if ( $session_key === $key ) {
                if ( isset( $_GET['wpfnl-order'] ) && isset( $_GET['wpfnl-key'] ) ) {
                    $order_id  = empty( $_GET['wpfnl-order'] ) ? 0 : absint( $_GET['wpfnl-order'] );
                    $order_key = empty( $_GET['wpfnl-key'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['wpfnl-key'] ) );

                    if ( $order_id > 0 ) {
                        $order = wc_get_order( $order_id );
                        if ( $order && $order->get_order_key() === $order_key ) {
                            $is_active = true;
                        }
                    }
                }
            }
        }
        return $is_active;
    }


    /**
     * check if funnel session is active
     *
     * @param $funnel_id
     * @return mixed|void
     *
     * @since 1.0.0
     */
    public function is_active_session( $funnel_id ) {

        $is_active = false;

        if ( isset( $_GET['wpfnl-sk'] ) && isset( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) ) {

            $sk  = sanitize_text_field( wp_unslash( $_GET['wpfnl-sk'] ) );
            $key = sanitize_text_field( wp_unslash( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) );

            if ( $sk === $key ) {
                if ( isset( $_GET['wpfnl-order'] ) && isset( $_GET['wpfnl-key'] ) ) {
                    // Get the order.
                    $order_id  = empty( $_GET['wpfnl-order'] ) ? 0 : absint( $_GET['wpfnl-order'] );
                    $order_key = empty( $_GET['wpfnl-key'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['wpfnl-key'] ) );

                    if ( $order_id > 0 ) {

                        $order = wc_get_order( $order_id );
                        if ( $order && $order->get_order_key() === $order_key ) {
                            $is_active = true;
                        }
                    }
                }
            }
        }
        return $is_active;
    }


    /**
     * Get session key for funnel
     *
     * @param $funnel_id
     * @return false|string
     */
    public function get_session_key( $funnel_id ) {
        if ( isset( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) ) {
            $key = sanitize_text_field( wp_unslash( $_COOKIE[ WPFUNNELS_SESSION_COOKIE . $funnel_id ] ) );
            return $key;
        }
        return false;
    }
}
