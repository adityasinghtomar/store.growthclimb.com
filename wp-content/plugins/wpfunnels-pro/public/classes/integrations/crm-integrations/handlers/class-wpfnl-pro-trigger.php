<?php

namespace WPFunnelsPro\Integrations\CRM;

use WPFunnels\Traits\SingletonTrait;
use WPFunnelsPro\Integrations\CRM;
use WPFunnelsPro\Integrations\CRM\Data\CookieData;
use WPFunnelsPro\Integrations\CRM\Event\OfferEvent;
use WPFunnelsPro\Integrations\CRM\Event\TriggerCTA;
use WPFunnelsPro\Integrations\CRM\Event\MainOrder;
use WPFunnelsPro\Integrations\CRM\Event\OrderBump;
use WPFunnelsPro\Integrations\CRM\Event\OptinSubmit;

/**
 * Trigger handler for WPF automation
 *
 * Class TriggerHandler
 * @package WPFunnelsPro\Integrations
 */
class TriggerHandler {

    use SingletonTrait;

    /**
     * @var $trigger
     */
    protected $trigger;


    /**
     * @var $data
     */
    protected $data;


    /**
     * @var $event
     */
    protected $event;


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


    /**
     * @var $crm
     */
    protected $crm;

    /**
     * @param TriggerObject $trigger
     * @param CookieData $data
     * @param CRM $crm
     */
    public function init( TriggerObject $trigger, $data, CRM $crm ) {
        $this->trigger      = $trigger;
        $this->data         = $data;
        $this->crm          = $crm;
        $this->event        = $this->get_event();
    }

    /**
     * Automation Event Initialize
     *
     * @param null
     * @return object
     */

    private function get_event() {
        if( strpos($this->trigger->get_event_name(), 'upsell_accepted') !== false ){
            $event_name = 'upsell_accepted';
        }elseif(strpos($this->trigger->get_event_name(), 'upsell_rejected') !== false ){
            $event_name = 'upsell_rejected';
        }elseif(strpos($this->trigger->get_event_name(), 'downsell_accepted') !== false ){
            $event_name = 'downsell_accepted';
        }elseif(strpos($this->trigger->get_event_name(), 'downsell_rejected') !== false ){
            $event_name = 'downsell_rejected';
        }elseif( strpos($this->trigger->get_event_name(), 'any_orderbump_accepted') === false && strpos($this->trigger->get_event_name(), '_orderbump_accepted') !== false  ){
            $event_name = 'orderbump_accepted';
        }elseif(strpos($this->trigger->get_event_name(), '_orderbump_not_accepted') !== false ){
            $event_name = 'orderbump_not_accepted';
        }elseif(strpos($this->trigger->get_event_name(), 'any_orderbump_not_accepted') !== false ){
            $event_name = 'orderbump_not_accepted';
        }elseif(strpos($this->trigger->get_event_name(), 'any_orderbump_accepted') !== false ){
           
            if( isset($this->data['ob_accepetd_products']) && !empty($this->data['ob_accepetd_products']) ){
                $event_name = 'orderbump_accepted';
            }else{
                $event_name = $this->trigger->get_event_name();
            }
            
        }elseif( false !== strpos($this->trigger->get_event_name(), 'cta_clicked') ){
            $event_name = 'cta_clicked';
        }elseif( false !== strpos($this->trigger->get_event_name(), 'after_optin_submit') ){
            $event_name = 'after_optin_submit';
        }else{
            $event_name = $this->trigger->get_event_name();
        }
        switch ( $event_name ) {
            case 'upsell_accepted' :
            case 'downsell_accepted':
            case 'upsell_rejected':
            case 'downsell_rejected':
                return new OfferEvent( $this->trigger, $this->data, $this->crm, $this->trigger->get_event_name() );
            case 'cta_clicked':
                return new TriggerCTA( $this->trigger, $this->data, $this->crm, $this->trigger->get_event_name());
            case 'main_order_accepted':
            case 'main_order_accepted_enrolled':
                return new MainOrder( $this->trigger, $this->data, $this->crm, $this->trigger->get_event_name() );
            case 'orderbump_accepted':
            case 'orderbump_not_accepted':
                return new OrderBump( $this->trigger, $this->data, $this->crm, $this->trigger->get_event_name() );
            case 'after_optin_submit':
                return new OptinSubmit( $this->trigger, $this->data, $this->crm, $this->trigger->get_event_name() );
            default :
                return false;
        }
    }

    /**
     * Automation Event Start
     *
     * @param null
     * @return void
     */
    public function start() {
        
        if($this->event){
            $this->event->run();
        }
       
    }

}