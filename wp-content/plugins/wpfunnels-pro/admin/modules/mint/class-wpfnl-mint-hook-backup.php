<?php

namespace WPFunnelsPro\Mint;

use Error;

use WPFunnelsPro\Wpfnl_Pro_functions;
use WPFunnels\Wpfnl;
use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;
use MintMail\App\Internal\Automation\HelperFunctions;
use function WPFunnels\Rest\Controllers\wpfnl_pro_analytics_get_param_type;
use Mint\MRM\DataStores\ContactData;
use Mint\MRM\DataBase\Models\ContactModel;
use MRM\Common\MrmCommon;
use Mint\MRM\Admin\API\Controllers\MessageController;

/**
 * Class Wpfnl_Mint_Hook
 * @package WPFunnelsPro\Mint
 */
class Backup_Mint_Hook{

    use SingletonTrait;

    /**
     * Initialize all hooks
     */
    public function init()
    {
        add_filter( 'wpfunnels/update_funnel_data_response', [$this, 'update_funnel_data_response'], 10 );
        add_action( 'wpfunnels/save_mint_automation', [$this, 'save_mint_automation'], 10, 2 );
        add_action( 'wpfunnels/before_delete_funnel', [$this, 'delete_funnel_automation'], 10, 1 );
        add_action( 'wpfunnels/maybe_automation_exist_for_a_funnel', [$this, 'maybe_automation_exist_for_a_funnel'], 10, 2 );
        add_action( 'mailmint_before_automation_send_mail', [$this, 'maybe_create_mainmint_contact'], 10, 2 );
    }


    /**
     * update funnel data response
     * 
     * @param Array $response
     * 
     * @since 1.7.8
     * @return Array $response
     */
    public function update_funnel_data_response( $response ){
        
        $automation_steps = [];
        if( is_array($response) && !empty($response['funnel_data']['drawflow']['Home']['data']) ){
            $steps_order = $response['funnel_data']['drawflow']['Home']['data'];
            if( is_array($steps_order) ){
                foreach( $steps_order as $key=>$step ){
                    if( isset( $step['data']['step_id'] ) ){
                        $step_id = $step['data']['step_id'];
                        if( Wpfnl_functions::is_mint_mrm_active() ){
                            $automation = get_post_meta( $step_id, '_wpfnl_automation_steps', true );
                            if( is_array($automation) ){
                                $key = array_search('sendMail', array_column($automation, 'key'));
                                if( false !== $key ){
                                    if( isset($automation[$key]['settings']['settings']['message_data']['body'])  ){
                                        unset($automation[$key]['settings']['settings']['message_data']['body']);
                                    }
                                } 
                            }
                            
                            $automation_steps['step_'.$step_id]['data'] = $automation ? $automation : [];
                        }
                    }
                }
            }
        }
        $response['automationSteps'] = $automation_steps;
        return $response;
    }


    /**
     * Save mint automation settings
     * 
     * @param int $funnel_id
     * @param array $automation_steps
     */
    public function save_mint_automation( $funnel_id, $automation_steps ){
        if( $automation_steps && is_array($automation_steps) ){
            foreach( $automation_steps as $key=>$step ){
                if( !empty($step['stepID']) ){
                    $data = isset($step['value']) ? $step['value'] : [];
                    $step_data = get_post_meta( $step['stepID'], '_wpfnl_automation_steps', true );
                    $key = array_search('sendMail', array_column($data, 'key'));
                    if( false !== $key && isset($data[$key]['settings']['settings']['message_data']['body']) && isset($step_data[$key]['settings']['settings']['message_data']['body'])  ){
                        $data[$key]['settings']['settings']['message_data']['body'] = $step_data[$key]['settings']['settings']['message_data']['body'];
                    }
                    
                    update_post_meta( $step['stepID'], '_wpfnl_automation_steps', $data );
                }
            }
        }
    }
    
    
    /**
     * Delete funnel automation 
     * 
     * @param int $funnel_id
     */
    public function delete_funnel_automation( $funnel_id ){
        
        if( Wpfnl_functions::is_mint_mrm_active() ){
            Wpfnl_Pro_functions::delete_automation_by_funnel_id( $funnel_id );
        }
    }
    
    
    /**
     * Delete funnel automation 
     * 
     * @param int $funnel_id
     */
    public function maybe_automation_exist_for_a_funnel( $response, $funnel_id ){
        
        if(  Wpfnl_functions::is_mint_mrm_active() && class_exists("Mint\\MRM\\DataBase\\Tables\\AutomationMetaSchema") && class_exists("MintMail\\App\\Internal\\Automation\\AutomationModel") && class_exists("Mint\\MRM\\DataBase\\Tables\\AutomationSchema")  ) {
            $automationSchema = "Mint\\MRM\\DataBase\\Tables\\AutomationSchema";
            $automationMetaSchema = "Mint\\MRM\\DataBase\\Tables\\AutomationMetaSchema";
            global $wpdb;
            $automation_table = $wpdb->prefix . $automationSchema::$table_name;
            $automation_meta_table = $wpdb->prefix . $automationMetaSchema::$table_name;
            $automations = $wpdb->get_results( $wpdb->prepare( "SELECT automation.id as id FROM $automation_table as automation INNER JOIN $automation_meta_table as automation_meta ON automation.id = automation_meta.automation_id WHERE automation_meta.meta_key = %s AND automation_meta.meta_value = %s", array( 'funnel_id', $funnel_id ) ), ARRAY_A ); // db call ok. ; no-cache ok.   
            if( is_array($automations) && count($automations) ){
                $response = true;
            }
        }
        return $response;
    }


    /**
     * Create a MainMint contact if conditions are met.
     *
     * This function checks if certain conditions are met and if the required classes
     * exist. If the conditions are satisfied, it creates a new contact in the MainMint
     * system with the provided email address and automation ID. This process may involve
     * sending a double-opt-in email if configured.
     *
     * @since 1.9.6
     *
     * @param int    $automation_id The ID of the automation.
     * @param string $email         The email address of the contact to be created.
     *
     * @return void
     */
    public function maybe_create_mainmint_contact( $automation_id, $email ) {
        if ( $automation_id && $email &&
            class_exists( 'Mint\\MRM\\DataBase\\Tables\\AutomationMetaSchema' ) &&
            class_exists( 'MintMail\\App\\Internal\\Automation\\AutomationModel' ) &&
            class_exists( 'Mint\\MRM\\DataBase\\Tables\\AutomationSchema' )
        ) {
            $is_exist = $this->maybe_automation_exist( $automation_id );
            if ( $is_exist ) {
                if ( !HelperFunctions::maybe_user( $email ) ) {
                    $is_double_optin = MRMCommon::is_double_optin_enable();
                    $parms           = array(
                        'status' => $is_double_optin ? 'pending' : 'subscribed',
                    );
                    $contact         = new ContactData( $email, $parms );
                    $contact_id      = ContactModel::insert( $contact );

                    if ( $contact_id && $is_double_optin ) {
                        MessageController::get_instance()->send_double_opt_in( $contact_id );
                    }
                }
            }
        }
    }


    /**
     * Check if a specific automation exists.
     *
     * This private function checks whether a given automation ID exists in the MainMint
     * system by querying the appropriate tables. It uses the provided automation ID to
     * look for matching automations based on predefined conditions.
     *
     * @since 1.6.0
     *
     * @param int $automation_id The ID of the automation to check.
     *
     * @return bool True if the automation exists, false otherwise.
     */
    private function maybe_automation_exist( $automation_id ) {
        if ( ! $automation_id ) {
            return false;
        }

        global $wpdb;

        $automation_table     = $wpdb->prefix . 'mint_automations';
        $automation_meta_table = $wpdb->prefix . 'mint_automation_meta';

        // Query to retrieve matching automations.
        $automations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT automation.id as id 
                FROM $automation_table as automation 
                INNER JOIN $automation_meta_table as automation_meta 
                ON automation.id = automation_meta.automation_id 
                WHERE automation_meta.automation_id = %d 
                AND automation_meta.meta_key = %s 
                AND automation_meta.meta_value = %s",
                array( $automation_id, 'source', 'wpf' )
            ),
            ARRAY_A
        );

        if ( ! is_array( $automations ) || ! count( $automations ) ) {
            return false;
        }

        return true;
    }
    
}