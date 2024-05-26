<?php

namespace WPFunnelsPro\Integrations\CRM\Data;
use WPFunnels\Traits\SingletonTrait;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Cookie data for automation
 *
 * Class CookieData
 * @package WPFunnelsPro\Integrations
 * @version 2.0.0
 */
class CookieData {

    /**
     * @var $data
     */
    protected $data;

    public function __construct($cookie_data) {
        $this->data = $cookie_data;
    }



}