<?php
namespace WPFunnelsPro\Widgets;

use WPFunnels\Base_Manager;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;

class Wpfnl_Pro_Widgets_Manager extends Base_Manager {

    use SingletonTrait;

    private $widgets = [];

    public function init() {
        $modules_namespace_prefix = $this->get_namespace_prefix();
        $builders = $this->get_builders();
        global $post;
        foreach ( $builders as $builder ) {
            $class_name = str_replace( '-', ' ', $builder );
            $class_name = str_replace( ' ', '', ucwords( $class_name ) );
            $class_name = $modules_namespace_prefix . '\\Widgets\\' . $class_name . '\Manager';
            $this->widgets[ $builder ] = $class_name::instance();
        }

    }

    public function get_builders() {

        $builder = array(
            'elementor',
            'gutenberg',
            'diviModules',
            'oxygen'
        );
        if (method_exists(Wpfnl_functions::class,'oxygen_builder_version_capability')){
            $oxygen_capability = Wpfnl_functions::oxygen_builder_version_capability();
            if ($oxygen_capability){
                array_push($builder,'oxygen');
            }
        }

        return $builder;
    }


    public function get_namespace_prefix() {
        return 'WPFunnelsPro';
    }
}
