<?php

namespace WPFunnelsPro\Integrations\CRM\Event;

use WPFunnelsPro\Integrations\CRM;
use WPFunnelsPro\Integrations\CRM\Data\CookieData;
use WPFunnelsPro\Integrations\CRM\TriggerObject;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Event class for automation
 *
 * Class Event
 * @package WPFunnelsPro\Integrations
 * @version 2.0.0
 */

class Event {

    /**
     * @var $triggerObject
     */
    protected $triggerObject;

    /**
     * @var $crm
     */
    protected $crm;

    /**
     * @var $data
     */
    protected $data;

    /**
     * @var $data_index
     */
    protected $event;

    public function __construct( TriggerObject $triggerObject, $data, CRM $crm, $event ) {
        $this->triggerObject    = $triggerObject;
        $this->data             = $data;
        $this->crm              = $crm;
        $this->event            = $event;
    }
}