<?php
namespace WPFunnelsPro\Frontend\Modules\Webhook;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Frontend\Module\Wpfnl_Frontend_Module;
use WPFunnelsPro\Frontend\Modules\Webhook\Wpfnl_Pro_Webhook_Mapping;

use WPFunnels\Wpfnl_functions;

class Wpfnl_Pro_Webhook
{
    use SingletonTrait;
    public function __construct()
    {
        new Wpfnl_Pro_Webhook_Mapping();
    }

}