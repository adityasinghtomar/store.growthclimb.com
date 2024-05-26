<?php
namespace WPFunnelsPro\Integration;
use WPFunnelsPro\Integration\Affiliate\Wpfnl_Pro_Integration_WPAffiliate;

class Wpfnl_Pro_Integrations_Manager
{
    public function __construct()
    {
        if (in_array('fluentform/fluentform.php', WPFNL_ACTIVE_PLUGINS) && in_array('fluent-crm/fluent-crm.php', WPFNL_ACTIVE_PLUGINS)) {
            $fluent_forms_integration = new Wpfnl_Pro_Integration_Fluent_Forms();
        } else {
          wp_clear_scheduled_hook('wpfnl_10_min_client_check');
        }

    }
}
