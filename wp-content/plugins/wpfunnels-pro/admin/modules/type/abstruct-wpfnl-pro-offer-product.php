<?php
namespace WPFunnelsPro\Admin\OfferProduct;

abstract class Wpfnl_Pro_OfferProduct
{

    


    /**
     * add upsell item to post meta 
     * 
     * @param String $id
     * @return Array
     * @since 2.4.6
     */
    abstract public function add_upsell_items( $id, $data, $step_id );


    /**
     * get upsell item from post meta 
     * @return Array
     * @since 2.4.6
     */
    abstract public function get_upsell_items( $products, $step_id );


    /**
     * add downsell item to post meta 
     * 
     * @param String $id
     * @param Array $data
     * @return Array
     * @since 2.4.6
     */
    abstract public function add_downsell_items( $id, $data, $step_id );

    /**
     * get downsell item from post meta 
     * @return Array
     * @since 2.4.6
     */
    abstract public function get_downsell_items( $products, $step_id );


}