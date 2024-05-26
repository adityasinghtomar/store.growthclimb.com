<?php

namespace WPFunnelsPro\Admin\Modules\Steps\Downsell;

use WPFunnels\Metas\Wpfnl_Step_Meta_keys;
use WPFunnels\Admin\Modules\Steps\Module as Steps;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;
use \WC_Subscriptions_Product;
use WPFunnelsPro\ReplaceOrder\OrderReplacement;
use Wpfnl_Pro_OfferProduct_Factory;
class Module extends Steps
{
    protected $validations;

    protected $_internal_keys = [];

    protected $type = 'downsell';

    protected $prefix = '_wpfnl_downsell_';

    /**
     * init ajax hooks for
     * saving metas
     *
     * @since 1.0.0
     */
    public function init_ajax()
    {
        $this->validations = [
            'logged_in' => true,
            'user_can' => 'manage_options',
        ];
        wp_ajax_helper()->handle('add-downsell-product')
            ->with_callback([ $this, 'add_product' ])
            ->with_validation($this->validations);

        wp_ajax_helper()->handle('update-offer-settings')
            ->with_callback([ $this, 'update_settings' ])
            ->with_validation($this->validations);
    }


    /**
     * get view of the downsell
     *
     * @since 1.0.0
     */
    public function get_view()
    {
        // TODO: Implement get_view() method.
        $show_settings = filter_input(INPUT_GET, 'show_settings', FILTER_SANITIZE_STRING);
        if ($show_settings == 1) {
            $this->_internal_keys = Wpfnl_Step_Meta_keys::get_meta_keys($this->type);
            $this->set_internal_meta_value();
            $prev_next_options =  $this->get_prev_next_link_options();
            require_once WPFNL_PRO_DIR . '/admin/modules/steps/downsell/views/settings.php';
        } else {
            require_once WPFNL_PRO_DIR . '/admin/modules/steps/downsell/views/view.php';
        }
    }


    /**
     * save upsell product tab
     * data
     *
     * @param $payload
     * @return array
     */
    public function add_product( $payload ) {
        $step_id    = sanitize_text_field($payload['step_id']);
        $id         = sanitize_text_field( $payload['product_id'] );
        if(!$step_id) {
            return [
                'success' => false,
            ];
        }
        $data = array(
            array(
                'id'        =>  sanitize_text_field($payload['product_id']),
                'quantity'  =>  sanitize_text_field($payload['quantity'])
            )
        );

        $type = '';
        if( $payload['isLms'] == 'true' ){
            $type = 'lms';
        }else{
            $type = 'wc';
        }

        $class_object = Wpfnl_Pro_OfferProduct_Factory::build( $type );
        if( $class_object ){
            $response = $class_object->add_downsell_items( $id, $data, $step_id );
            if( $response ){
                return $response;
            }
        }
        return array(
            'success'   => false,
            'message'   => __('Product Not Found', 'wpfnl')
        );
    }


    /**
     * save upsell product tab
     * data
     *
     * @param $payload
     * @return array
     */
    public function update_settings( $payload ) {
        $step_id        = sanitize_text_field($payload['step_id']);
        $products       = isset($payload['products']) ? $payload['products'] : '';
        $type           = $payload['type'];
        $discount       = $payload['discount'];
        if($products){
            update_post_meta( $step_id, '_wpfnl_'.$type.'_products', $products );
        }
      
        update_post_meta( $step_id, '_wpfnl_'.$type.'_discount', $discount );
        
        $step_info = array(
            'step_id'   => $step_id,
            'step_type' => $type,
        );

        if( isset($payload['replaceSettings']) && $payload['replaceSettings'] == 'true' ){
            update_post_meta( $step_info['step_id'], '_wpfnl_'.$step_info['step_type'].'_replacement_settings', 'true' );
            $offerReplacement = $payload['offerReplacement'];
            $replacement_type = isset($offerReplacement['replacement_type']) && $offerReplacement['replacement_type'] ? $offerReplacement['replacement_type'] : '';
            $value = isset($offerReplacement['value']) && $offerReplacement['value'] ? $offerReplacement['value'] : '';
            OrderReplacement::save_replace_order_condition( $step_info, $replacement_type, $value );
        }else{
            update_post_meta( $step_info['step_id'], '_wpfnl_'.$step_info['step_type'].'_replacement_settings', 'false' );
            delete_post_meta( $step_info['step_id'], '_wpfnl_'.$step_info['step_type'].'_replacement' );
        }

        if( isset($payload['timeBoundDiscount'])){
            update_post_meta($step_id, '_wpfnl_time_bound_discount_settings', $payload['timeBoundDiscount']);
        }
        
        // update_post_meta( $step_id, '_wpfnl_'.$type.'_replacement', $isOfferReplace );
        return array(
            'success'   => true,
            'step_id'   => $step_id,
            'message'   => __('Product Saved Successfully', 'wpfnl')
        );
    }


    /**
     * get prev/next links
     *
     * @return array
     * @since 1.0.0
     */
    public function get_prev_next_link_options()
    {
        $associate_funnel_id = get_post_meta($this->get_id(), '_funnel_id', true);
        $steps_array = [
            'upsell' => 'Upsell',
            'downsell' => 'Downsell',
            'thankyou' => 'Thankyou'
        ];
        $option_group = [];
        foreach ($steps_array as $key=>$value) {
            $args = [
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'post_type'      => WPFNL_STEPS_POST_TYPE,
                'post_status'    => 'publish',
                'post__not_in'   => [ $this->get_id() ],
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key'     => '_step_type',
                        'value'   => $key,
                        'compare' => '=',
                    ],
                    [
                        'key'     => '_funnel_id',
                        'value'   => $associate_funnel_id,
                        'compare' => '=',
                    ],
                ],
            ];
            $query = new \WP_Query($args);
            $steps = $query->posts;
            if ($steps) {
                foreach ($steps as $s) {
                    $option_group[$key][] = [
                        'id'    => $s->ID,
                        'title' => $s->post_title,
                    ];
                }
            }
        }
        return $option_group;
    }
}
