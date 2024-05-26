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
use WPFunnelsPro\MailMint\Automation;

/**
 * Class Wpfnl_Mint_Hook
 * @package WPFunnelsPro\Mint
 */
class Wpfnl_Mint_Hook{

    use SingletonTrait;

    /**
     * Initialize all hooks
     */
    public function init()
    {
        add_filter( 'wpfunnels/step_data', [$this, 'update_step_data'], 10, 2 );
        add_action( 'mailmint_before_automation_send_mail', [$this, 'maybe_create_mainmint_contact'], 10, 2 );
        add_action( 'wpfunnels/maybe_automation_exist_for_a_funnel', [$this, 'maybe_automation_exist_for_a_funnel'], 10, 2 );
        add_action( 'wpfunnels/after_step_duplicate', [$this, 'save_automation_after_step_duplicate'], 10, 2 );
    }


    /**
     * Check the automation is exist or not in a funnel 
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
     * update step data if mint automation enable
     * 
     * @param array $step_data
     * @param int $step_id
     * 
     * @return array $step_data
     * @since 2.0.0
     */
    public function update_step_data( $step_data, $step_id ){
        if( !$step_id ){
            return $step_data;
        }
        $trigger = get_post_meta( $step_id, '_wpfnl_automation_trigger', true );
        $step_data['maybe_mint_automation'] = $trigger ? 'yes' : 'no';
        return $step_data;
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


    /**
     * Save automation after step duplicate
     * 
     * @param int $funnel_id
     * @param int $step_id
     * 
     * @since 2.1.2
     */
    public function save_automation_after_step_duplicate( $funnel_id, $step_id ){

        if( $funnel_id && $step_id ){
            $mint_automation = new Automation();
            $automation_data = $mint_automation->prepare_automation_data( $funnel_id, $step_id );
            if( $automation_data ){
                $mint_automation->save_or_update_automation( $automation_data, $funnel_id, $step_id );
            }
        }
    }
}