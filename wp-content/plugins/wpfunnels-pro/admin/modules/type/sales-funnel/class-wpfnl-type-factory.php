<?php

/**
 *
 * Create class object of funnel type and return object
 * @since             2.4.5
 * Author:            A S M Nasim
 */
class Wpfnl_Pro_OfferProduct_Factory {

    public static function build($module)
    {
        $class_name = "WPFunnelsPro\\Admin\\OfferProduct\\Wpfunnels_Pro_".ucfirst($module)."_OfferProduct";
        if (!class_exists(ucfirst($class_name))) {
            throw new \Exception('Invalid Condition Module.');
        }else {
            return new $class_name();
        }
    }
}