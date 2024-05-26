<?php
/**
 * Woostify pro functions.
 *
 * @package Woostify Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'woostify_post_type_support' ) ) {
	/**
	 * Post type supports
	 */
	function woostify_post_type_support() {
		$post_types       = get_post_types();
		$post_types_unset = array(
			'attachment'          => __( 'attachment', 'woostify-pro' ),
			'revision'            => __( 'revision', 'woostify-pro' ),
			'nav_menu_item'       => __( 'nav_menu_item', 'woostify-pro' ),
			'custom_css'          => __( 'custom_css', 'woostify-pro' ),
			'customize_changeset' => __( 'customize_changeset', 'woostify-pro' ),
			'oembed_cache'        => __( 'oembed_cache', 'woostify-pro' ),
			'user_request'        => __( 'user_request', 'woostify-pro' ),
			'wp_block'            => __( 'wp_block', 'woostify-pro' ),
			'elementor_library'   => __( 'elementor_library', 'woostify-pro' ),
			'btf_builder'         => __( 'btf_builder', 'woostify-pro' ),
			'elementor-hf'        => __( 'elementor-hf', 'woostify-pro' ),
			'elementor_font'      => __( 'elementor_font', 'woostify-pro' ),
			'elementor_icons'     => __( 'elementor_icons', 'woostify-pro' ),
			'wpforms'             => __( 'wpforms', 'woostify-pro' ),
			'wpforms_log'         => __( 'wpforms_log', 'woostify-pro' ),
			'acf-field-group'     => __( 'acf-field-group', 'woostify-pro' ),
			'acf-field'           => __( 'acf-field', 'woostify-pro' ),
			'booked_appointments' => __( 'booked_appointments', 'woostify-pro' ),
			'wpcf7_contact_form'  => __( 'wpcf7_contact_form', 'woostify-pro' ),
			'scheduled-action'    => __( 'scheduled-action', 'woostify-pro' ),
			'shop_order'          => __( 'shop_order', 'woostify-pro' ),
			'shop_order_refund'   => __( 'shop_order_refund', 'woostify-pro' ),
			'shop_coupon'         => __( 'shop_coupon', 'woostify-pro' ),
		);
		$diff             = array_diff( $post_types, $post_types_unset );
		$default          = array(
			'all'       => __( 'All', 'woostify-pro' ),
			'blog'      => __( 'Blog Page', 'woostify-pro' ),
			'archive'   => __( 'Archive Page', 'woostify-pro' ),
			'search'    => __( 'Search Page', 'woostify-pro' ),
			'not_found' => __( '404 Page', 'woostify-pro' ),
		);
		$options          = array_merge( $default, $diff );

		return $options;
	}
}

if ( ! function_exists( 'woostify_fetch_svg_icon' ) ) {
	/**
	 * Fetch SVG icon
	 *
	 * @param string $icon Icon name.
	 */
	function woostify_fetch_svg_icon( $icon ) {
		if ( ! class_exists( 'Woostify_Icon' ) ) {
			return '';
		}

		return Woostify_Icon::fetch_svg_icon( $icon, false );
	}
}

if ( ! function_exists( 'woostify_fetch_all_svg_icon' ) ) {
	/**
	 * Fetch All SVG icon
	 */
	function woostify_fetch_all_svg_icon() {
		if ( ! class_exists( 'Woostify_Icon' ) ) {
			return '';
		}

		return Woostify_Icon::fetch_all_svg_icon();
	}
}

if( ! function_exists( 'woostify_display_all_thumbnail_sizes' ) ){
	/**
	 * Display all thumbnail size
	 */
	function woostify_display_all_thumbnail_sizes() {
		global $_wp_additional_image_sizes;

		$sizes = array();
		foreach ( get_intermediate_image_sizes() as $_size ) {
			if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
				$sizes[ $_size ]['name'] =  str_replace("_"," ", $_size);
				$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
				$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
				$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) && in_array( $_size, array( 'woocommerce_thumbnail', 'woocommerce_single', 'woocommerce_gallery_thumbnail' ) ) ) {
				$sizes[ $_size ] = array( 
					'name' => str_replace("_"," ", $_size),
					'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
					'height' => $_wp_additional_image_sizes[ $_size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $_size ]['crop']
				);
			}
		}

		return $sizes;
	}
}
