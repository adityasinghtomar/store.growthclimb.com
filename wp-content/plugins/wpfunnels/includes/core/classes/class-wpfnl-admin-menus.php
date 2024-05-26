<?php
/**
 * Admin menus
 *
 * @package
 */
namespace WPFunnels\Menu;

use WPFunnels\Admin\SetupWizard;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

/**
 * Class Wpfnl_Menus
 *
 * @package Wpfnl
 */
class Wpfnl_Menus
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_plugin_menus']);
        add_filter('admin_head', [$this, 'remove_submenu'], 10, 2);
        add_filter('admin_head', [$this, 'remove_notices_from_funnel_window'], 10, 2);
        add_action('admin_init', [$this, 'disallow_all_step_view']);
        add_action('admin_footer', [$this, 'doc_link_with_new_page']);

        if( isset($_GET['page']) && ( 'edit_funnel' === $_GET['page'] || 'wpfunnels_integrations' === $_GET['page'] ) ) {
			add_filter( "admin_body_class", array($this, 'add_folded_menu_class') );
		}
    }


    /**
     * Register plugin menus and submenus
     *
     * @since 1.0.0
     */
    public function register_plugin_menus()
    {
        $role_permission = Wpfnl_functions::get_general_settings();
        add_menu_page(
            'WP Funnels',
            'WP Funnels',
            Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            WPFNL_MAIN_PAGE_SLUG,
            '',
            WPFNL_DIR_URL . 'admin/assets/images/funnel.svg',
            6
        );

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Funnels', 'wpfnl'),
            __('Funnels', 'wpfnl'),
            Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            WPFNL_MAIN_PAGE_SLUG,
            [$this, 'render_funnels_page']
        );

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Settings', 'wpfnl'),
            __('Settings', 'wpfnl'),
            Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            WPFNL_GLOBAL_SETTINGS_SLUG,
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Documentation', 'wpfnl'),
            '<span id="wpfnl-documentation">'. __('Documentation', 'wpfnl').'</span>',
            Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            'https://getwpfunnels.com/resources/'
        );

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Edit Funnel', 'wpfnl'),
            __('Edit Funnel', 'wpfnl'),
            Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            WPFNL_EDIT_FUNNEL_SLUG,
            [$this, 'render_edit_funnel_page']
        );

		add_submenu_page(
			WPFNL_MAIN_PAGE_SLUG,
			__('Request a Feature', 'wpfnl'),
			'<span id="wpfnl-request-feature">'. __('Request a Feature', 'wpfnl').'</span>',
			Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            'https://getwpfunnels.com/ideas/'
		);

        add_submenu_page(
            WPFNL_MAIN_PAGE_SLUG,
            __('Email Builder', 'wpfnl'),
            __('Email Builder', 'wpfnl'),
            Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
            'email-builder',
            [$this, 'render_email_builder_page']
        );

        if ( !Wpfnl_functions::is_wpfnl_pro_activated() ) {
            add_submenu_page(
				WPFNL_MAIN_PAGE_SLUG,
				__('Go Pro', 'wpfnl'),
				'<span class="dashicons dashicons-star-filled" style="font-size: 17px; color:#1fb3fb;"></span> ' . __('Go Pro', 'wpfnl'),
				Wpfnl_functions::role_permission_to_allow_wpfunnel( $role_permission ),
				'https://getwpfunnels.com/pricing/'
			);
        }

		/**
		 * After setup menu of WPFunnels.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wpfunnels/after_setup_menu');
    }


    public function render_email_builder_page() { ?>
        <div id="email-builder"></div>
    <?php }



    /**
     * Render funnel page
     *
     * @since 1.0.0
     */
    public function render_funnels_page()
    {
        Wpfnl::$instance->module_manager->get_admin_modules('funnels')->get_view();
    }


    /**
     * Render edit funnel page.
     *
     * @since 1.0.0
     */
    public function render_edit_funnel_page()
    {
        $funnel_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
        Wpfnl::$instance->module_manager->get_admin_modules('funnel')->init($funnel_id);
        Wpfnl::$instance->module_manager->get_admin_modules('funnel')->get_view();
    }

    /**
     * Render create funnel page
     *
     * @since 1.0.0
     */
    public function render_create_funnel_page()
    {
        Wpfnl::$instance->module_manager->get_admin_modules('create-funnel')->get_view();
    }


    /**
     * Render settings page
     *
     * @since 1.0.0
     */
    public function render_settings_page()
    {
        Wpfnl::$instance->module_manager->get_admin_modules('settings')->get_view();
    }




	/**
	 * Render license page for funnel
	 *
	 * @since 2.0.0
	 */
	public function render_license() {
		require WPFNL_DIR . '/admin/partials/license.php';
	}

    /**
     * Remove submenu from plugin menu
     *
     * @since 1.0.0
     */
    public function remove_submenu()
    {
        remove_submenu_page(WPFNL_MAIN_PAGE_SLUG, 'edit_funnel');
    }


    /**
     * Remove all notices from funnel window
     *
     * @since 2.0.0
     */
    public function remove_notices_from_funnel_window() {
    	if (empty($_GET['page'])) {
    		return;
		}
        if (('edit_funnel' == sanitize_text_field( $_GET['page'] ) )) {
            remove_all_actions( 'admin_notices' );
        }
		if ( 'wp_funnels' == sanitize_text_field( $_GET['page'] ) ) {
			add_action('admin_footer', array( $this, 'remove_admin_notices' ));
		}
    }



    /**
     * Force user to visit all steps page
     *
     * @since 1.0.0
     */
    public function disallow_all_step_view()
    {
        global $pagenow;
        if ('edit.php' === $pagenow && isset($_GET['post_type']) && WPFNL_STEPS_POST_TYPE === sanitize_text_field($_GET['post_type'])) {
            $funnel_link = add_query_arg(
                [
                    'page' => WPFNL_MAIN_PAGE_SLUG,
                ],
                admin_url('admin.php')
            );
            wp_safe_redirect(  wp_sanitize_redirect( esc_url_raw( $funnel_link ) ) );
            exit;
        }
    }

    /**
     * Redirect user to pro url
     */
    public function redirect_to_pro()
    {
        $url = 'https://getwpfunnels.com/pricing/';
        wp_safe_redirect(  wp_sanitize_redirect( esc_url_raw( $url ) ) );
        exit();
    }


    /**
     * Open with new page when documenation is clicked
     */
    public function doc_link_with_new_page(){
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#wpfnl-documentation').parent().attr('target','_blank');
                $('#wpfnl-request-feature').parent().attr('target','_blank');
            });
        </script>
        <?php
    }


    public function add_folded_menu_class($classes) {
		return $classes." folded";
	}


	/**
	 * Remove admin notices
	 */
	public function remove_admin_notices() {
		echo '<style>.update-nag, .updated, .error, .is-dismissible, .notice { display: none; } .wpfunnels-notice {display: block;}</style>';
	}
}
