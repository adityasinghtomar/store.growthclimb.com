<?php

class Wpfnl_Analytics_Factory {

    public static function build($module)
    {
        $class_name = "WPFunnelsPro\\AnalyticsController\\".ucfirst($module);
        if (!class_exists(ucfirst($class_name))) {
            throw new \Exception('Invalid Condition Module.');
        }else {
            return new $class_name();
        }
    }
}