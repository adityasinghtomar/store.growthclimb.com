<?php

namespace WPFunnelsPro\Integrations\CRM;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Trigger object
 *
 * Class TriggerObject
 * @package WPFunnelsPro\Integrations
 * @version 2.0.0
 */
class TriggerObject {

    /**
     * @var $event
     */
    protected $event_name;


    /**
     * @var $tag_id
     */
    protected $tag_id;


    /**
     * @var $list_id
     */
    protected $list_id;


    /**
     * @var $step_id
     */
    protected $step_id;

    public function __construct( $trigger )
    {
        $this->event_name   = isset($trigger['event']) ? $trigger['event'] : '';
        $this->list_id      = isset($trigger['list']) ? $trigger['list'] : '';
        $this->tag_id       = isset($trigger['tag']) ? $trigger['tag'] : '';
        $this->step_id      = isset($trigger['stepID']) ? $trigger['stepID'] : '';
    }


    public function get_step_id() {
        return $this->step_id;
    }


    public function get_event_name() {
        return $this->event_name;
    }


    public function get_tag_id() {
        return $this->tag_id;
    }

    public function get_list_id() {
        return $this->list_id;
    }
}