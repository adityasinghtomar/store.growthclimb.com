<?php
namespace WPFunnelsPro\Widgets\Gutenberg\BlockTypes;


/**
 * OrderDetails class.
 */
class OfferTitle extends AbstractDynamicBlock {

    protected $defaults = array(
    );

    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'offer-title';


    /**
     * OfferButton constructor.
     * @param string $block_name
     */
    public function __construct( $block_name = '' )
    {
        parent::__construct($block_name);
        add_action('wp_ajax_wpfnl_offer_product_title_shortcode', [$this, 'render_offer_product_shortcode']);
    }


    /**
     * render offer product shortcode markup
     * based on type
     *
     */
    public function render_offer_product_shortcode() {
        check_ajax_referer( 'wpfnl_gb_ajax_nonce', 'nonce' );
        $data['html'] = do_shortcode( '[wpfunnels_offer_product_title]' );
        wp_send_json_success( $data );
    }

    /**
     * Render the Featured Product block.
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Block content.
     * @return string Rendered block type output.
     */
    protected function render( $attributes, $content ) {
        $output  = sprintf( '<div class="%1$s" style="%2$s">', esc_attr( $this->get_classes( $attributes ) ), esc_attr( $this->get_styles( $attributes ) ) );
        $output .= '<div class="wpfnl-pro-elementor-offer-product-title">';
        $output .= do_shortcode('[wpfunnels_offer_product_title]');
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }


    /**
     * Get the styles for the wrapper element (background image, color).
     *
     * @param array       $attributes Block attributes. Default empty array.
     * @return string
     */
    public function get_styles( $attributes ) {
        $style      = '';
        return $style;
    }


    /**
     * Get class names for the block container.
     *
     * @param array $attributes Block attributes. Default empty array.
     * @return string
     */
    public function get_classes( $attributes ) {
        $classes = array( 'wpfnl-block-' . $this->block_name );
        return implode( ' ', $classes );
    }


    /**
     * Extra data passed through from server to client for block.
     *
     * @param array $attributes  Any attributes that currently are available from the block.
     *                           Note, this will be empty in the editor context when the block is
     *                           not in the post content on editor load.
     */
    protected function enqueue_data( array $attributes = [] ) {
        parent::enqueue_data( $attributes );
    }


    /**
     * Get the frontend script handle for this block type.
     *
     * @see $this->register_block_type()
     * @param string $key Data to get, or default to everything.
     * @return array|string
     */
    protected function get_block_type_script( $key = null ) {
        $script = [
            'handle'       => 'wpfnl-offer-title-frontend',
            'path'         => $this->get_block_asset_build_path( 'offer-title-frontend' ),
            'dependencies' => [],
        ];
        return $key ? $script[ $key ] : $script;
    }
}
