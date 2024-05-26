<?php
namespace WPFunnelsPro\Widgets\Gutenberg\BlockTypes;

use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;
use WPFunnelsPro\Wpfnl_Pro_functions;
use WPFunnels\Widgets\Gutenberg\BlockTypes\AbstractBlock as FreeAbstractBlocks;

/**
 * AbstractBlock class.
 */
class AbstractBlock extends FreeAbstractBlocks{

	use SingletonTrait;

    /**
     * Block namespace.
     *
     * @var string
     */
    protected $namespace = 'wpfunnelspro';

    /**
     * Constructor.
     *
     * @param string              $block_name Optionally set block name during construct.
     */
    public function __construct( $block_name = '' ) {
        parent::__construct( $block_name );
    }



    /**
     * Get the editor script data for this block type.
     *
     * @see $this->register_block_type()
     * @param string $key Data to get, or default to everything.
     * @return array|string
     */
    protected function get_block_type_editor_script( $key = null ) {
        $script = [
            'handle'       => 'wpfnl-' . $this->block_name,
            'path'         => $this->get_block_asset_build_path( $this->block_name ),
            'dependencies' => [ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-api-fetch' ],
        ];
        return $key ? $script[ $key ] : $script;
    }


    /**
     * Register script and style assets for the block type before it is registered.
     *
     * This registers the scripts; it does not enqueue them.
     */
    protected function register_block_type_assets() {
        if ( null !== $this->get_block_type_editor_script() ) {
            $post_id   = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0; //phpcs:ignore
            $handle = $this->get_block_type_editor_script( 'handle' );

            $funnel_id = get_post_meta($post_id, '_funnel_id', true);
            $funnel_type = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );


            if(
                ( Wpfnl_functions::check_if_this_is_step_type_by_id( $post_id, 'upsell' ) || Wpfnl_functions::check_if_this_is_step_type_by_id( $post_id, 'downsell' ) )
                && $handle === 'wpfnl-offer-button'
            ) {
				$this->register_script(
					$handle,
					$this->get_block_type_editor_script( 'path' ),
					$this->get_block_type_editor_script( 'dependencies' )
				);
			}
            if(
                ( Wpfnl_functions::check_if_this_is_step_type_by_id( $post_id, 'upsell' ) || Wpfnl_functions::check_if_this_is_step_type_by_id( $post_id, 'downsell' ) )
                && $handle === 'wpfnl-lms-offer-button' && 'lms' === $funnel_type && Wpfnl_functions::is_lms_addon_active()
            ) {
				$this->register_script(
					$handle,
					$this->get_block_type_editor_script( 'path' ),
					$this->get_block_type_editor_script( 'dependencies' )
				);
			}

            if(
                ( Wpfnl_functions::check_if_this_is_step_type_by_id( $post_id, 'upsell' ) || Wpfnl_functions::check_if_this_is_step_type_by_id( $post_id, 'downsell' ) )
                && $handle === 'wpfnl-offer-title'
            ) {
                $this->register_script(
                    $handle,
                    $this->get_block_type_editor_script( 'path' ),
                    $this->get_block_type_editor_script( 'dependencies' )
                );
            }

            if(
                ( Wpfnl_functions::check_if_this_is_step_type_by_id( $post_id, 'upsell' ) || Wpfnl_functions::check_if_this_is_step_type_by_id( $post_id, 'downsell' ) )
                && $handle === 'wpfnl-offer-price'
            ) {
                $this->register_script(
                    $handle,
                    $this->get_block_type_editor_script( 'path' ),
                    $this->get_block_type_editor_script( 'dependencies' )
                );
            }
            if(
                ( Wpfnl_functions::check_if_this_is_step_type_by_id( $post_id, 'upsell' ) || Wpfnl_functions::check_if_this_is_step_type_by_id( $post_id, 'downsell' ) )
                && $handle === 'wpfnl-offer-product'
            ) {
                $this->register_script(
                    $handle,
                    $this->get_block_type_editor_script( 'path' ),
                    $this->get_block_type_editor_script( 'dependencies' )
                );
            }

        }

    }


    public function register_script( $handle, $relative_src, $dependencies = [], $has_i18n = true ) {
        $src     = '';
        $version = '1';

        if ( $relative_src ) {
            $src        = $this->get_asset_url( $relative_src );
            $version    = $this->get_file_version( $relative_src );
        }


        wp_register_script( $handle, $src, apply_filters( 'wpfunnels/gutenberg_blocks_register_script_dependencies', $dependencies, $handle ), $version, true );
        $step_id = 0;
        if ( is_admin() && isset( $_REQUEST['action'] ) ) {
            if ('edit' === $_REQUEST['action'] && isset($_GET['post'])) {
                $step_id = isset($_GET['post']) ? $_GET['post'] : -1;
            } elseif (isset($_REQUEST['wpfunnels_gb']) && isset($_POST['post_id'])) { //phpcs:ignore
                $step_id = intval($_POST['post_id']); //phpcs:ignore
            }

            wp_localize_script(
                $handle,
                'wpfnl_pro_block_object',
                array(
                    'plugin'  			=> WPFNL_DIR_URL,
                    'siteUrl'           => get_site_url(),
                    'ajaxUrl'           => admin_url('admin-ajax.php'),
                    'orderBumpImg'      => WPFNL_URL . 'admin/assets/images/placeholder.jpg',
                    'data_post_id'      => $step_id,
                    'variableRender'      => $this->render_variable_shortcode( $step_id ),
                    'wpfnl_ob_data'     => get_post_meta( $step_id, 'order-bump-settings', true ),
                    'nonce'             => wp_create_nonce('wp_rest'),
                    'wpfnl_ajax_nonce'  => wp_create_nonce('wpfnl_gb_ajax_nonce'),
                    'variable_shortcode'=> do_shortcode( '[wpf_variable_offer]' ),
                    'is_variable_product'=> $this->check_is_variable_product($step_id),
                    'lmsOfferButtonRender'=> $this->render_lms_pay_button_shortcode($step_id),
                    'isGbf'	            => $this->maybe_global_funnel( $step_id ),
                    'productInfo'	    => $this->get_dynamic_product_info( $step_id ),
                )
            );
        }
    }


    /**
     * Check funnel is global funnel or not.
	 * 
	 * @param String
	 * @return String
	 * 
	 * @since 2.4.14
	 * 
	 */
	public function maybe_global_funnel( $step_id = '' ){
		if( $step_id ){
			$funnel_id = get_post_meta($step_id, '_funnel_id', true);
			$is_gbf = get_post_meta( $funnel_id, 'is_global_funnel', true);
			if( $is_gbf ){
				return $is_gbf;
			}
		}
		return 'no';
	}


    /**
     * Get dynamic product information.
	 * 
	 * @param String
	 * @return String
	 * 
	 * @since 2.4.14
	 * 
	 */
    public function get_dynamic_product_info( $step_id ){

        if( $step_id ){
            $response = Wpfnl_Pro_functions::get_product_data_for_widget( $step_id );
            $offer_product       = isset($response['offer_product']) && $response['offer_product'] ? $response['offer_product'] : '';

            if( $offer_product ){
                $product = [
                    'img' => get_the_post_thumbnail_url($offer_product->get_id()),
                    'title' => $offer_product->get_name(),
                    'description' => $offer_product->get_short_description(),
                    'price' => $offer_product->get_price_html(),
                ];

                return $product;
            }
        }
        
        return [
            'img' => '',
            'title' => 'What is Lorem Ipsum?',
            'description' => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.",
            'price' => "$120",
        ];
    }


    /**
     * Get asset url.
	 * 
	 * @param String
	 * @return String
	 * 
	 * @since 2.4.14
	 * 
	*/
    public function get_asset_url( $relative_url ) {
        return plugin_dir_url( WPFNL_PRO_DIR . '/includes/core/widgets/block/index.php' ) . $relative_url;
    }


    /**
     * Check product type is variable or not.
	 * 
	 * @param String
	 * @return String
	 * 
	 * @since 2.4.14
	 * 
	*/
    public function check_is_variable_product( $step_id ) {
       return Wpfnl_Pro_functions::check_is_variable_product($step_id);
    }

    /**
     * Render variable product's shortcode.
	 * 
	 * @param String
	 * @return String
	 * 
	 * @since 2.4.14
	 * 
	*/
    public function render_variable_shortcode( $step_id ) {
        $data = '';
        ob_start();
        echo do_shortcode('[wpf_variable_offer post_id="'.$step_id.'" ]');
        $data = ob_get_clean();
        return $data;
    }

    /**
     * Render LMS pay button shortcode.
	 * 
	 * @param String
	 * @return String
	 * 
	 * @since 2.4.14
	 * 
	*/
    public function render_lms_pay_button_shortcode($step_id){
        ob_start();
        $funnel_id = get_post_meta($step_id,'_funnel_id',true);
        $get_learn_dash_setting = get_post_meta($funnel_id,'_wpfnl_funnel_type',true);
        $lms_button_text = get_option( 'learndash_settings_custom_labels' );

        if ($get_learn_dash_setting == 'lms'){
            $step_type = get_post_meta($step_id, '_step_type', true);

            if ($step_type != 'upsell' && $step_type != 'downsell'){
                echo __('Sorry, Please place the element in WPFunnels Offer page');

            }else{
                $button_text = !empty($lms_button_text['button_take_this_course']) ? $lms_button_text['button_take_this_course'] : "Take this course";

                echo $button_text;
            }
        }else{
            echo __('Sorry, Please place the element in WPFunnels when learnDash is active ');
        }
        return ob_get_clean();
    }

    /**
     * Returns the path to the plugin directory.
     *
     * @param string $relative_path  If provided, the relative path will be
     *                               appended to the plugin path.
     *
     * @return string
     */
    public function get_path( $relative_path = '' ) {
        return trailingslashit( WPFNL_PRO_DIR_URL . '/includes/core/Blocks' ) . $relative_path;
    }

    /**
     * Get the file modified time as a cache buster if we're in dev mode.
     *
     * @param string $file Local path to the file (relative to the plugin
     *                     directory).
     * @return string The cache buster value to use for the given file.
     */
    protected function get_file_version( $file ) {
        return '1.0.0';
    }


    /**
     * Get the editor style handle for this block type.
     *
     * @see $this->register_block_type()
     * @return string|null
     */
    protected function get_block_type_editor_style() {
        return 'wpfnl-pro-blocks-editor-style';
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
            'handle'       => 'wpfnl-pro-' . $this->block_name . '-frontend',
            'path'         => $this->get_block_asset_build_path( $this->block_name . '-frontend' ),
            'dependencies' => [],
        ];
        return $key ? $script[ $key ] : $script;
    }



    /**
     * Get the frontend style handle for this block type.
     *
     * @see $this->register_block_type()
     * @return string|null
     */
    protected function get_block_type_style() {
        return 'wpfnl-pro-blocks-style';
    }

    /**
     * Get the supports array for this block type.
     *
     * @see $this->register_block_type()
     * @return string;
     */
    protected function get_block_type_supports() {
        return [];
    }

    /**
     * Get block attributes.
     *
     * @return array|null;
     */
    protected function get_block_type_attributes() {
        return null;
    }

    /**
     * Parses block attributes from the render_callback.
     *
     * @param array|WP_Block $attributes Block attributes, or an instance of a WP_Block. Defaults to an empty array.
     * @return array
     */
    protected function parse_render_callback_attributes( $attributes ) {
        return is_a( $attributes, 'WP_Block' ) ? $attributes->attributes : $attributes;
    }

    /**
     * Render the block. Extended by children.
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Block content.
     * @return string Rendered block type output.
     */
    protected function render( $attributes, $content ) {
        return $content;
    }

    /**
     * Enqueue frontend assets for this block, just in time for rendering.
     *
     * @internal This prevents the block script being enqueued on all pages. It is only enqueued as needed. Note that
     * we intentionally do not pass 'script' to register_block_type.
     *
     * @param array $attributes  Any attributes that currently are available from the block.
     */
    protected function enqueue_assets( array $attributes ) {
        if ( $this->enqueued_assets ) {
            return;
        }
        $this->enqueue_scripts( $attributes );
        $this->enqueued_assets = true;
    }

    /**
     * Injects block attributes into the block.
     *
     * @param string $content HTML content to inject into.
     * @param array  $attributes Key value pairs of attributes.
     * @return string Rendered block with data attributes.
     */
    protected function inject_html_data_attributes( $content, array $attributes ) {
        return preg_replace( '/<div /', '<div ' . $this->get_html_data_attributes( $attributes ) . ' ', $content, 1 );
    }

    /**
     * Converts block attributes to HTML data attributes.
     *
     * @param array $attributes Key value pairs of attributes.
     * @return string Rendered HTML attributes.
     */
    protected function get_html_data_attributes( array $attributes ) {
        $data = [];

        foreach ( $attributes as $key => $value ) {
            if ( is_bool( $value ) ) {
                $value = $value ? 'true' : 'false';
            }
            if ( ! is_scalar( $value ) ) {
                $value = wp_json_encode( $value );
            }
            $data[] = 'data-' . esc_attr( strtolower( preg_replace( '/(?<!\ )[A-Z]/', '-$0', $key ) ) ) . '="' . esc_attr( $value ) . '"';
        }

        return implode( ' ', $data );
    }


    /**
     * Register/enqueue scripts used for this block on the frontend, during render.
     *
     * @param array $attributes Any attributes that currently are available from the block.
     */
    protected function enqueue_scripts( array $attributes = [] ) {
        if ( null !== $this->get_block_type_script() ) {
            wp_enqueue_script( $this->get_block_type_script( 'handle' ) );
        }
    }
}
