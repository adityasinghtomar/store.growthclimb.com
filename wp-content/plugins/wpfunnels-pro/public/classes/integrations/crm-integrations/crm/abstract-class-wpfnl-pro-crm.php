<?php


namespace WPFunnelsPro\Integrations;

use WPFunnels\Wpfnl_functions;

/**
 * Abstract CRM class for CRM integrations
 *
 * Class CRM
 * @package WPFunnelsPro\Integrations
 */
abstract class CRM {

    abstract function get_name();

    abstract function is_connected();

    abstract function get_user_info_from_order($order_id);

    abstract function send_or_update_data( $data );

    abstract function deleteData( $id );

    abstract function get_crm_contact_lists();

    abstract function get_crm_contact_tags( $list = [] );
}