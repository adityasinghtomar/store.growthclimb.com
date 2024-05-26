<?php

namespace WPFunnelsPro;

use WPFunnels\Traits\SingletonTrait;

/**
 * Class Addons
 * @package WPFunnelsPro
 */
class Addons {

    use SingletonTrait;

    /**
     * check if plugin is installed
     *
     * @param $plugin_slug
     * @return bool
     */
    public function check_plugin_installed( $plugin_slug ) {
        if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $installed_plugins = get_plugins();
        return array_key_exists( $plugin_slug, $installed_plugins ) || in_array( $plugin_slug, $installed_plugins, true );
    }


    /**
     * get button text
     *
     * @param $slug
     * @return string|void
     *
     * @since 1.3.1
     */
    public function get_button_text( $slug ) {
        if ( !$this->check_plugin_installed( $slug ) ) {
            return __('Get the add-on', 'wpfnl-pro');
        }

        if ( is_plugin_active( $slug ) ) {
            return __('Activated', 'wpfnl-pro');
        }
        return __('Please enable', 'wpfnl-pro');
    }

    /**
     * get wpf pro addons lists
     *
     * @return array
     *
     * @since 1.3.0
     */
    public function get_addons() {
        if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $default_addons = [];
        return apply_filters( 'wpfunnels/addon-lists', $default_addons );
    }
}