<?php
/**
 * Elementor Product Rating Widget
 *
 * @package Woostify Pro
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class widget.
 */
class Woostify_Product_FBT_Widget extends Widget_Base {
	/**
	 * Category
	 */
	public function get_categories() {
		return array( 'woostify-product' );
	}

	/**
	 * Name
	 */
	public function get_name() {
		return 'woostify-product-fbt';
	}

	/**
	 * Gets the title.
	 */
	public function get_title() {
		return __( 'Woostify - Product Frequently Bought Together', 'woostify-pro' );
	}

	/**
	 * Gets the icon.
	 */
	public function get_icon() {
		return 'eicon-gallery-group';
	}

	/**
	 * Gets the keywords.
	 */
	public function get_keywords() {
		return array( 'woostify', 'woocommerce', 'shop', 'product', 'rating', 'store', 'fbt', 'frequently-bought-together' );
	}

	/**
	 * Controls
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'start',
			array(
				'label' => __( 'General', 'woostify-pro' ),
			)
		);

		$this->add_responsive_control(
			'fbt_size',
			array(
				'label'     => __( 'Display size', 'woostify-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'wide'   => array(
						'title' => __( 'Wide', 'woostify-pro' ),
						'icon'  => 'eicon-posts-grid',
					),
					'small' => array(
						'title' => __( 'Small', 'woostify-pro' ),
						'icon'  => 'eicon-post-list',
					),
				),
				'default'   => 'wide',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render
	 */
	protected function render() {
		global $product;
		if ( woostify_is_elementor_editor() ) {
			$product_id         = \Woostify_Woo_Builder::init()->get_product_id();
			$product            = wc_get_product( $product_id );
			$GLOBALS['product'] = $product;
		}
		if ( empty( $product ) ) {
			return;
		}

        $settings = $this->get_settings_for_display();
		\Woostify_FBT_Frontend::products_list($settings); // Load Woostify_FBT_Frontend outside of 
	}
}

Plugin::instance()->widgets_manager->register( new Woostify_Product_FBT_Widget() );