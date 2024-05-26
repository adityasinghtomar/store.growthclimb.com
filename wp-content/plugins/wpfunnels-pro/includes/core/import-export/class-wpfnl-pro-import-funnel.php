<?php

namespace WPFunnelsPro\Import;
use WPFunnels\Wpfnl_functions;
use WPFunnels\Wpfnl;
use WPFunnels\Data_Store\Wpfnl_Steps_Store_Data;
use lementor\TemplateLibrary\Source_Base;
use WPFunnelsPro\AbTesting\Wpfnl_Ab_Testing;
use Elementor\Plugin;
use WPFunnels\Admin\Notices\Notice as Admin_Notice;

/**
 * This class is responsible import funnel 
 * 
 * Import json file 
 * @since 1.7.5
 */
class Wpfnl_Import {

    /**
     * Initialize ajax callback
     * 
     * @since 1.7.5
     */
    public function __construct() {
        add_action( 'wp_ajax_wpfnl_import_funnels', [ $this, 'schedule_import_funnels' ] );
        add_action( 'wp_ajax_nopriv_wpfnl_import_funnels', [ $this, 'schedule_import_funnels' ] );
        add_action( 'wp_ajax_wpfnl_hide_import_funnel_notice', [ $this, 'hide_import_funnel_notice' ] );
        add_action( 'wp_ajax_nopriv_wpfnl_hide_import_funnel_notice', [ $this, 'hide_import_funnel_notice' ] );
        add_action( 'wpfnl_import_funnels', [ $this, 'import_funnels' ] );
        add_filter( 'wpfnl_notices', [ $this, 'add_funnel_notice' ] );
    }

    /**
     * Adds a custom admin notice to the WordPress admin dashboard.
     *
     * This function is a callback used to add a custom notice to the admin dashboard.
     * The notice is added to the existing array of notices.
     *
     * @param array $notices The array of existing admin notices.
     *
     * @return array The updated array of admin notices with the added custom notice.
     * @since 1.9.7
     */
    public function add_funnel_notice( $notices ) {
        $notices[ 'WPFunnelsPro\Import\Wpfnl_Import' ]= 'funnel_import_notice';
        return $notices;
    }

    /**
     * Displays a notice related to funnel import status.
     *
     * This static method is used to display a notice related to the funnel import status in the WordPress admin area.
     * It checks whether the import status option is not empty and whether the current screen context allows displaying
     * the notice. If the conditions are met, a notice is generated using the provided options and displayed in the admin area.
     * The notice can be dismissed by the user.
     *
     * @since 1.9.7
     */
    public static function funnel_import_notice() {
        $import_status = get_option( 'wpfnl_import_notice', '' );
        if( !empty( $import_status ) ) {
            global $current_screen;
            $disabled_notice_page = [ 'wp_funnels' ];
            if( 'plugins' === $current_screen->parent_base || !isset( $_GET[ 'page' ] ) || ( isset( $_GET[ 'page' ] ) && in_array( $_GET[ 'page' ], $disabled_notice_page ) ) ) {
                $options = array(
                    'id'          => 'wpfnl-import-notice',
                    'title'       => 'wpfunnels-basic',
                    'description' => "<span>{$import_status}</span>",
                    'classes'     => [ 'notice', 'notice-info', 'wpfnl-import-notice' ],
                    'type'        => 'update-plugin',
                    'dismissible' => true,
                    'icon'        => '',
                );
                Admin_Notice::print_notice( $options );
            }
        }
    }

    /**
     * Schedule the import of funnels as a background process.
     *
     * This method is responsible for scheduling the import of funnels as a background process. It checks if the 'wpfnl_import_funnels'
     * action has not already been scheduled, and if so, it prepares the necessary file for import and schedules the background process.
     * The process involves uploading the imported file, updating the import notice, and enqueueing the 'wpfnl_import_funnels' action
     * to be executed asynchronously using a background processing mechanism (such as WP Async Task).
     *
     * @since 1.9.7
     */
    public function schedule_import_funnels() {
        if( !as_has_scheduled_action( 'wpfnl_import_funnels' ) ) {
            $file = $this->upload_imported_file();
            if( file_exists( $file ) ) {
                update_option( 'wpfnl_import_notice', 'Funnels import in-progress...' );
                as_enqueue_async_action( 'wpfnl_import_funnels', [ $file ], 'wpfunnels-import' );
            }
        }
    }

    /**
     * Uploads an imported file to the specified directory in the WordPress uploads folder.
     *
     * This private method is used to upload an imported file to a specific directory within the WordPress uploads folder.
     * It ensures that the target directory exists and creates it if not present. The method reads the temporary uploaded
     * file, extracts its content, and saves it to the target file location. The saved file's name is based on the original
     * uploaded filename, and if no filename is provided, a default name is used.
     *
     * @return string|false The full path to the uploaded file on success, or false on failure.
     * @since 1.9.7
     */
    private function upload_imported_file() {
        $path = wp_upload_dir();
        $path = $path[ 'basedir' ] . '/wpfunnels/import/';

        // make directory if not exist
        if( !file_exists( $path ) ) {
            wp_mkdir_p( $path );
        }

        $temp_file = $_FILES[ 'uploaded_file' ][ 'tmp_name' ] ?? '';
        $file_name = $_FILES[ 'uploaded_file' ][ 'name' ] ?? '';
        $content   = !empty( $temp_file ) ? file_get_contents( $temp_file ) : '';
        $file_name = !empty( $file_name ) ? "{$path}$file_name" : "{$path}funnel-import.json";

        return file_put_contents( $file_name, $content ) ? $file_name : '';
    }


    /**
     * Import Funnel data and create
     *
     * @param $file
     *
     * @return true
     * @since  1.7.5
     */
    public function import_funnels( $file ) {
        $file_data = [];
        if( file_exists( $file ) ) {
            $file_data = json_decode( file_get_contents( $file ), true );
            unlink( $file );
        }
        $message = __( 'The funnels import process has been successfully completed.', 'wpfnl-pro' );

        if( is_array( $file_data ) ) {
            $funnel       = Wpfnl::$instance->funnel_store;
            $exclude_meta = array( '_is_imported', '_steps_order', '_steps', '_funnel_data', 'wpfnls_is_newui_migrated' );
            foreach( $file_data as $data ) {
                if( empty( $data[ 'funnel_id' ] ) ) {
                    $message = __( 'Invalid funnel(s) data.', 'wpfnl-pro' );
                    break;
                }
                $funnel_data = [];
                $funnel_name = $data[ 'funnel_name' ] ?? 'New Funnel';
                $funnel_id   = $funnel->create( $funnel_name );
                $funnel->update_meta( $funnel_id, '_is_imported', 'yes' );

                foreach( $data[ 'funnel_meta' ] as $meta_key => $meta ) {
                    if( !in_array( $meta_key, $exclude_meta ) ) {
                        $funnel->update_meta( $funnel_id, $meta_key, maybe_unserialize( $data[ 'funnel_meta' ][ $meta_key ][ 0 ] ) );
                    }
                    else {
                        if( 'funnel_data' === $meta_key ) {
                            $funnel_data = maybe_unserialize( $data[ 'funnel_meta' ][ $meta_key ][ 0 ] );
                        }
                    }
                }
                $_new_step_ids   = [];
                $_new_step_order = [];

                foreach( $data[ 'steps_data' ] as $step_data ) {
                    $response = $this->import_step( $funnel_id, $step_data );
                    if( isset( $response[ 'id' ], $response[ 'title' ], $response[ 'type' ] ) ) {
                        $_new_step_ids[ $step_data[ 'id' ] ] = $response[ 'id' ];
                        $_new_step_order[]                   = [
                            'id'        => $response[ 'id' ],
                            'step_type' => $response[ 'type' ],
                            'name'      => $response[ 'title' ],
                        ];
                    }
                    else {
                        $message = __( 'Invalid funnel(s) data.', 'wpfnl-pro' );
                        break;
                    }
                }

                $this->update_funnel_data( $_new_step_ids, $funnel_id, $funnel_data );
                update_post_meta( $funnel_id, '_steps_order', $_new_step_order );
                update_post_meta( $funnel_id, '_steps', $_new_step_order );

                $instance = new Wpfnl_functions();
                if( method_exists( $instance, 'generate_first_step' ) ) {
                    Wpfnl_functions::generate_first_step( $funnel_id, [] );
                }
            }
        }

        update_option( 'wpfnl_import_notice', $message );
        return true;
    }
    

    /**
     * Import step from json
     * @param Int $funnel_id
     * @param Array $step_data
     * @return
     * @since 1.7.5
     * 
     */
    public function import_step( $funnel_id, $step_data ){
        if( !is_array($step_data) ){
            return false;
        }
        $builder_type = Wpfnl_functions::get_builder_type();
        $previous_step_id        = isset( $step_data['id'] ) ? $step_data['id'] : '';
        $title          = isset( $step_data['title'] ) ? $step_data['title'] : '';
        $page_template  = isset( $step_data['page_template'] ) ? $step_data['page_template'] : '';
        $post_content   = isset( $step_data['post_content'] ) ? $step_data['post_content'] : '';
        $step_type      = isset( $step_data['type'] ) ? $step_data['type'] : '';
        $step = new Wpfnl_Steps_Store_Data();
        $step_id = $step->create_step($funnel_id, $title, $step_type, $post_content, true);
        
        if( $step_id ){
            $step->update_meta($step_id, '_funnel_id', $funnel_id);
            $step->update_meta($step_id, '_wp_page_template', $page_template);
            $exclude_meta = array( '_wpfnl_ab_testing_start_settings','_funnel_id' );
            $sql_query_arr      = [];
            foreach( $step_data['meta'] as $meta_key=>$meta){
                
                if( isset($meta[0]) ){
                    if( !in_array( $meta_key, $exclude_meta ) ){
                        $sql_data = [
                            'post_id'   => $step_id,
                            'meta_key'  => $meta_key,
                            'meta_value'=> $meta[0],
                        ];
                        $sql_query_arr[] = $sql_data;
                    }elseif( '_wpfnl_ab_testing_start_settings' === $meta_key ){
                        $this->update_ab_testing_settings( $funnel_id, $step_id, $meta[0], $step_data['ab_step_data'] );
                    }
                }
            }

            $this->update_meta_by_sql( $sql_query_arr ); // update meta

            if ( ( 'gutenberg' === $builder_type )  && class_exists( '\WPFunnels\Batch\Gutenberg\Wpfnl_Gutenberg_Source' ) ) {
                if ( ini_get( 'allow_url_fopen' ) ) {
                    $gutenberg_source  = new \WPFunnels\Batch\Gutenberg\Wpfnl_Gutenberg_Source;
                    $gutenberg_source->import_single_template( $step_id  );
                }
            }

            // Elementor Data.
            if ( ( 'elementor' === $builder_type ) && class_exists( '\Elementor\Plugin' ) && class_exists( '\WPFunnels\Batch\Elementor\Wpfnl_Elementor_Source' ) ) {
                if ( ini_get( 'allow_url_fopen' ) ) {
                    $elementor_source  = new \WPFunnels\Batch\Elementor\Wpfnl_Elementor_Source;
                    $elementor_source->import_single_template( $step_id  );
                    Plugin::$instance->files_manager->clear_cache(); 
                }
            }

            if ( ( 'divi-builder' === $builder_type ) && class_exists( '\WPFunnels\Batch\Divi\Wpfnl_Divi_Source' ) ) {
                if ( ini_get( 'allow_url_fopen' ) ) {
                    $divi_source  = new \WPFunnels\Batch\Divi\Wpfnl_Divi_Source;
                    $divi_source->import_single_template( $step_id  );
                }
            }

            if ( ( 'oxygen' === $builder_type ) && class_exists( '\WPFunnels\Batch\Oxygen\Wpfnl_Oxygen_Source' ) ) {
                if ( ini_get( 'allow_url_fopen' ) ) {
                    $oxygen_source  = new \WPFunnels\Batch\Oxygen\Wpfnl_Oxygen_Source;
                    $oxygen_source->import_single_template( $step_id  );
                }
            }
            delete_post_meta( $step_id, '_funnel_id' ); 
            update_post_meta( $step_id, '_funnel_id', $funnel_id );
            
            return [
                'id'    => $step_id,
                'type'  => $step_type,
                'title' => $title,
            ];
        }
        
        return false;   
    }


    /**
     * Update post meta by raw SQL.
     * 
     * Inserts post meta data into the WordPress database using raw SQL queries.
     * 
     * @param array $sql_query_arr An array of SQL query values in the format (post_id, meta_key, meta_value).
     * 
     * @since 1.8.10
     * @return boolean 
     */
    public function update_meta_by_sql( $sql_query_arr ){
        if ( ! is_array( $sql_query_arr ) || empty( $sql_query_arr ) ) {
            return false;
        }
        global $wpdb;
        $insert_sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES ";
        $value_placeholders = array();
        $query_args = array();
        foreach( $sql_query_arr as $query ) {
            if( isset($query['post_id'],$query['meta_key'],$query['meta_value']) ){
                $value_placeholders[] = '(%d, %s, %s)';
                $query_args[] = $query['post_id'];
                $query_args[] = $query['meta_key'];
                $query_args[] = $query['meta_value'];
            }
        }
        if( empty( $value_placeholders ) || empty( $query_args )){
            return false;
        }
        $insert_sql_query = rtrim( $insert_sql_query, ',' );
        $insert_sql_query .= ' ' . implode( ',', $value_placeholders );
        $prepared_query = $wpdb->prepare( $insert_sql_query, $query_args );
        $result = $wpdb->query( $prepared_query );
        return false !== $result;
    } 


    /**
     * Update A/B testing settings for a funnel step.
     * This function updates the A/B testing settings for a funnel step with the provided parameters.

     * @param int $funnel_id The ID of the funnel to which the step belongs.
     * @param int $step_id The ID of the step for which the A/B testing settings are being updated.
     * @param mixed $settings The new A/B testing settings for the step. This can be a serialized string or an array.
     * @param mixed $prev_settings The previous A/B testing settings for the step. This can be a serialized string or an array.
     * 
     * @since 1.8.8
     * @return void
    */
    public function update_ab_testing_settings( $funnel_id, $step_id, $settings, $prev_settings ){
        $settings = maybe_unserialize($settings);
        $prev_settings = maybe_unserialize($prev_settings);
        
        if( isset( $settings['auto_winner'], $settings['variations'] ) ){
            $new_settings = [];
            $new_settings['auto_winner'] = ($settings['auto_winner']);
            $new_settings['winner']      = '';
            $new_settings['is_started']  = '';
            $new_settings['start_date']  = isset($settings['start_date']) ?$settings['start_date'] : date( 'Y-m-d H:i:s' );
            $new_settings['variations']  = $this->update_ab_variations( $funnel_id, $step_id, $settings['variations'], $prev_settings );
            update_post_meta( $step_id, '_wpfnl_ab_testing_start_settings' , $new_settings );
        }

    }


    /**
     * Update A/B testing variations for a funnel step.
     * This function updates the A/B testing variations for a funnel step with the provided parameters.
     * 
     * @param int $funnel_id The ID of the funnel to which the step belongs.
     * @param int $step_id The ID of the step for which the A/B testing variations are being updated.
     * @param array $variations The new A/B testing variations for the step.
     * @param mixed $prev_settings The previous A/B testing settings for the step.
     * 
     * @return array The updated A/B testing variations.
     * @since 1.8.8
     */
    public function update_ab_variations( $funnel_id, $step_id, $variations, $prev_settings ){
        if( count( $variations ) >= 2 ){
            $step = new Wpfnl_Steps_Store_Data();
            foreach( $variations as $variation_key=>$variation ){
                
                if( !isset($variation['id'], $variation['variation_type'] ) ){
                    continue;
                }

                $key = array_search($variation['id'], array_column($prev_settings, 'step_id'));
                if( false !== $key ){
                    
                    if ( 'original' !== $variation['variation_type'] && isset($prev_settings[$key]['meta']) ){
                        $metas = $prev_settings[$key]['meta'];
                        $exclude_meta = array( '_wpfnl_ab_testing_start_settings','_wpfnl_automation_steps', '_parent_step_id' );
                        $page_template  = isset( $metas['page_template'] ) ? $metas['page_template'] : '';
                        $title          = isset( $metas['title'] ) ? $metas['title'] : '';
                        $post_content   = isset( $metas['post_content'] ) ? $metas['post_content'] : '';
                        $step_type      = isset( $metas['type'] ) ? $metas['type'] : '';
                        if( $step ){
                            $new_step_id = $step->create_step($funnel_id, $title, $step_type, $post_content, true);
                            if( $new_step_id ){
                                $step->update_meta($new_step_id, '_funnel_id', $funnel_id);
                                $step->update_meta($new_step_id, '_wp_page_template', $page_template);
                                $sql_query_arr      = [];

                                foreach( $metas['meta'] as $meta_key=>$meta ){
                                    if( !in_array( $meta_key, $exclude_meta ) ){
                                        $sql_data = [
                                            'post_id'   => $new_step_id,
                                            'meta_key'  => $meta_key,
                                            'meta_value'=> $meta[0],
                                        ];
                                        $sql_query_arr[] = $sql_data;
                                    }
                                }
                                $this->update_meta_by_sql( $sql_query_arr ); // update meta
                            }
                        }
                        
                    }else{
                        $new_step_id = $step_id;
                    }
                    update_post_meta( $new_step_id, '_parent_step_id', $step_id );
                    $variations[$variation_key]['id'] = $new_step_id;
                    $variations[$variation_key]['step_type'] = get_post_meta( $step_id, '_step_type', true );
                    $step_edit_link =  get_edit_post_link($new_step_id);
                    if( 'elementor' ===  Wpfnl_functions::get_builder_type() ){
                        $step_edit_link = str_replace('&amp;','&',$step_edit_link);
                        $step_edit_link = str_replace('edit','elementor',$step_edit_link);
                    }
                    $variations[$variation_key]['step_edit_link']   		= $step_edit_link;
                    $variations[$variation_key]['step_view_link']   		= get_the_permalink($new_step_id);
                    $variations[$variation_key]['step_title']       		= get_the_title($new_step_id);
                }
            }
        }else{
            $default_data = Wpfnl_Ab_Testing::get_default_data( $step_id );
            $variations = $default_data['variations'];
        }
        return $variations;
    }
    


    /**
     * Update funnel data and steps
     * 
     * @param Int $funnel_id
     * @param Array $step_data
     * 
     * @return void 
     * @since  1.7.5
     */ 
    public function update_funnel_data( $new_step_ids, $funnel_id, $funnel_data ){
        $funnel_identifier = array();
		if ($funnel_data) {
			$node_data = isset($funnel_data['drawflow']['Home']['data']) ? $funnel_data['drawflow']['Home']['data'] : [];
			foreach ($node_data as $node_key => $node_value) {
				if(isset($node_value['data']['step_id'])) {
					if ( isset($new_step_ids[$node_value['data']['step_id']]) ) {
						$post_edit_link = base64_encode(get_edit_post_link($new_step_ids[$node_value['data']['step_id']]));
						$post_view_link = base64_encode(get_post_permalink($new_step_ids[$node_value['data']['step_id']]));
						$funnel_data['drawflow']['Home']['data'][$node_key]['data']['step_id'] = $new_step_ids[$node_value['data']['step_id']];
						$funnel_data['drawflow']['Home']['data'][$node_key]['data']['step_edit_link'] = $post_edit_link;
						$funnel_data['drawflow']['Home']['data'][$node_key]['data']['step_view_link'] = $post_view_link;
						$funnel_data['drawflow']['Home']['data'][$node_key]['html'] = $node_value['data']['step_type'] . $new_step_ids[$node_value['data']['step_id']];
						$funnel_identifier[$node_value['id']] = $new_step_ids[$node_value['data']['step_id']];
					} else {
						if ($node_value['data']['step_type'] != 'conditional') {
							$funnel_identifier[$node_value['id']] = $node_value['data']['step_id'];
						} else {
							$funnel_identifier[$node_value['id']] = $node_value['data']['node_identifier'];
						}
					}
				}
                if ($node_value['data']['step_type'] == 'conditional') {
                    $funnel_identifier[$node_value['id']] = $node_value['data']['node_identifier'];
                }
			}
		}
		update_post_meta($funnel_id, '_funnel_data', $funnel_data);
		if ($funnel_identifier) {
			$funnel_identifier_json = json_encode($funnel_identifier, JSON_UNESCAPED_SLASHES);
			update_post_meta($funnel_id, 'funnel_identifier', $funnel_identifier_json);
		}
    }

    /**
     * Hide the import funnel notice based on nonce verification.
     *
     * This method is responsible for hiding the import funnel notice displayed to users. It receives a nonce value from the
     * submitted form, verifies the nonce using the 'wp_verify_nonce' function, and if the verification succeeds, it deletes the
     * 'wpfnl_import_notice' option to hide the notice. It then sends a JSON success response to the client and terminates the script
     * execution using 'wp_die'.
     *
     * @since 1.9.7
     */
    public function hide_import_funnel_notice() {
        $nonce = !empty( $_POST[ 'security' ] ) ? htmlspecialchars( trim( $_POST[ 'security' ] ) ) : null; // phpcs:ignore

        if( wp_verify_nonce( $nonce, 'wpfnl-admin' ) ) {
            delete_option( 'wpfnl_import_notice' );
        }
        wp_send_json_success();
        wp_die();
    }

    public function get_validation_data()
    {
        return [
            'logged_in' => true,
            'user_can' => 'manage_options',
        ];
    }
}