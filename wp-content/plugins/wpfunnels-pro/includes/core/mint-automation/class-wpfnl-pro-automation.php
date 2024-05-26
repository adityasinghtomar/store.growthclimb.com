<?php

namespace WPFunnelsPro\MailMint;

use Mint\MRM\DataBase\Tables\AutomationMetaSchema;

class Automation{

    /**
     * Prepare automation data for a given funnel and step.
     *
     * This method is responsible for preparing automation data based on the configuration of a specific funnel step.
     *
     * @param int $funnel_id The ID of the funnel to which the step belongs.
     * @param int $step_id   The ID of the step for which automation data is being prepared.
     *
     * @return array|false An array containing the prepared automation data or false if not applicable.
     * @since 2.0.0
     */
    public function prepare_automation_data($funnel_id, $step_id)
    {
        if (!$funnel_id || !$step_id) {
            return false;
        }

        // Get the trigger information from the step's post meta.
        $trigger = get_post_meta($step_id, '_wpfnl_automation_trigger', true);

        // If the trigger value is empty, return false as automation data is not applicable.
        if (empty($trigger['value'])) {
            return false;
        }

        // Initialize an array to store automation data.
        $automation_data = [];

        // Check if an automation ID exists and include it in the data.
        $automation_id = get_post_meta($step_id, 'wpfnl_mint_automation_id', true);
        if ($automation_id) {
            $automation_data['id'] = $automation_id;
        }

        // Set default values for automation data.
        $automation_data['name'] = 'automation';
        $automation_data['author'] = 1;
        $automation_data['trigger_name'] = $trigger['value'];
        $automation_data['status'] = 'active';
        $automation_data['steps'] = [];

        // Generate a unique identifier for the next step.
        $next_step = uniqid();

        // Create a trigger step based on the trigger value.
        $trigger_steps = [
            'step_id' => uniqid(),
            'key' => $trigger['value'],
            'type' => 'trigger',
            'next_step_id' => $next_step,
            'settings' => $this->prepare_settings($step_id, $funnel_id),
        ];

        // Add the trigger step to the automation data.
        $automation_data[ 'steps' ][] = $trigger_steps;

        // Retrieve automation steps from post meta.
        $automation_steps = get_post_meta($step_id, '_wpfnl_automation_steps', true);
        $automation_step_id = $next_step;

        // If there are automation steps, process and add them to the automation data.
        if (is_array($automation_steps) && count($automation_steps)) {
            $count = count($automation_steps);

            foreach ($automation_steps as $key => $automation_step) {
                // Format the automation step data.
                $trigger_settings = $this->formatted_automation_data($automation_step_id, $automation_steps, $key, $count);
                $automation_step_id = $trigger_settings[ 'next_step_id' ] ?? '';

                // Add the formatted automation step to the automation data.
                $automation_data[ 'steps' ][] = $trigger_settings;
            }
        }
      
        // Return the prepared automation data.
        return $automation_data;
    }
    


    /**
     * Format automation step data.
     *
     * This method formats automation step data based on the provided parameters.
     *
     * @param string $step_id           The ID of the current automation step.
     * @param array  $automation_steps  An array of automation steps.
     * @param int    $index             The index of the current automation step.
     * @param int    $count             The total count of automation steps.
     *
     * @return array                    Formatted automation step data.
     * @since 2.0.0
     */
    public function formatted_automation_data($step_id, $automation_steps, $index, $count, $is_conditional = 'no', $parent_next_step = '', $logic = '' )
    {
        // Check if the automation step data at the given index exists.
        if (!isset($automation_steps[$index]['key'])) {
            return [];
        }

        // Check if the automation step key is "sendMail."
	    if ( 'sendMail' == $automation_steps[ $index ][ 'key' ] ) {
		    // Check if message data exists in the automation step settings.
		    $automation_steps[ $index ][ 'settings' ][ 'settings' ][ 'message_data' ][ 'body' ] = $automation_steps[ $index ][ 'settings' ][ 'settings' ][ 'message_data' ][ 'body' ] ?? '';
	    }

        // Initialize an array to store the formatted trigger settings.
        $trigger_settings = [];

        // Set the next step ID based on the total count and the current index.
        $trigger_settings['next_step_id'] = $count > ($index + 1) ? uniqid() : '';
        
        if( !$trigger_settings['next_step_id'] && 'yes' === $is_conditional && $parent_next_step ){
            $trigger_settings['next_step_id'] = $parent_next_step;
        }

        if ( 'condition' == $automation_steps[$index]['key'] ) {
            $parent_next_step = $trigger_settings['next_step_id'];
            if( isset($automation_steps[$index]['settings']['settings']['yes']) && is_array($automation_steps[$index]['settings']['settings']['yes']) ){
                $condition_step_id = uniqid();
                $count = count($automation_steps[$index]['settings']['settings']['yes']);
                foreach( $automation_steps[$index]['settings']['settings']['yes'] as $key=> $yes_data ){
                    
                    $_trigger_settings = $this->formatted_automation_data($condition_step_id, $automation_steps[$index]['settings']['settings']['yes'], $key, $count, 'yes', $parent_next_step, 'yes');
                    $condition_step_id = $_trigger_settings[ 'next_step_id' ] ?? '';
                    $automation_steps[$index]['settings']['settings']['yes'][$key] = $_trigger_settings;
                    $automation_steps[$index]['settings']['settings']['yes'][$key]['popover_type'] = 'condition';
                    $automation_steps[$index]['settings']['settings']['yes'][$key]['condition_type'] = 'yes';
                    $automation_steps[$index]['settings']['settings']['yes'][$key]['parent_index'] = $index;
                }
            }

            if( isset($automation_steps[$index]['settings']['settings']['no']) && is_array($automation_steps[$index]['settings']['settings']['no']) ){
                $condition_step_id = uniqid();
                $count = count($automation_steps[$index]['settings']['settings']['no']);
                foreach( $automation_steps[$index]['settings']['settings']['no'] as $key=> $no_data ){
                    
                    $_trigger_settings = $this->formatted_automation_data($condition_step_id, $automation_steps[$index]['settings']['settings']['no'], $key, $count, 'yes', $parent_next_step, 'no');
                    $condition_step_id = $_trigger_settings[ 'next_step_id' ] ?? '';
                    $automation_steps[$index]['settings']['settings']['no'][$key] = $_trigger_settings;
                    $automation_steps[$index]['settings']['settings']['no'][$key]['popover_type'] = 'condition';
                    $automation_steps[$index]['settings']['settings']['no'][$key]['condition_type'] = 'no';
                    $automation_steps[$index]['settings']['settings']['no'][$key]['parent_index'] = $index;
                }
            }
        }
        
        

        // Set the step ID, automation key, type, and settings.
        $trigger_settings['step_id'] = $step_id;
        $trigger_settings['key'] = $automation_steps[ $index ][ 'key' ];
        $trigger_settings['type'] = 'condition' === $automation_steps[$index]['key'] ? 'logical' : 'action';
       

        
        if( 'condition' == $automation_steps[$index]['key'] ){
            $trigger_settings['settings']['rules']['condition'][0] = $automation_steps[ $index ][ 'settings' ][ 'settings' ][ 'rules' ] ?? [];

            $trigger_settings['node_data']['yes'] = $automation_steps[ $index ][ 'settings' ][ 'settings' ][ 'yes' ] ?? [];
            $trigger_settings['node_data']['no'] = $automation_steps[ $index ][ 'settings' ][ 'settings' ][ 'no' ] ?? [];
        
            $trigger_settings['logical_next_step_id']['yes'] = $automation_steps[ $index ][ 'settings' ][ 'settings' ][ 'yes' ][ 0 ][ 'step_id' ] ?? '';
            $trigger_settings['logical_next_step_id']['no'] = $automation_steps[ $index ][ 'settings' ][ 'settings' ][ 'no' ][ 0 ][ 'step_id' ] ?? '';
        }else{
            $trigger_settings['settings'] = $automation_steps[ $index ][ 'settings' ][ 'settings' ] ?? [];

        }
        // Return the formatted automation step data.
        return $trigger_settings;
    }


    /**
     * Prepare settings for a Mint Automation.
     *
     * This method prepares settings for a Mint Automation based on the provided step ID and funnel ID.
     *
     * @param int $step_id    The ID of the current step.
     * @param int $funnel_id  The ID of the funnel associated with the step.
     *
     * @return array          An array of prepared settings for the Mint Automation.
     * @since 2.0.0
     */
    public function prepare_settings($step_id, $funnel_id)
    {
        // Create an array to store the prepared settings.
        $settings = [
            'type'                  => 'selected',               // Indicates that specific steps and funnel IDs are selected.
            'selected_funnel_ids'   => [$funnel_id],            // An array containing the ID of the associated funnel.
            'all_steps'             => 'no',                    // Indicates that not all steps are selected.
            'selectedStep'          => $step_id,                // The ID of the selected step.
            'selectedType'          => get_post_meta($step_id, '_step_type', true) // The type of the selected step.
        ];

        // Return the prepared settings.
        return $settings;
    }
    

    /**
     * Save or update automation data for a step within a funnel.
     *
     * This method takes in automation data, funnel ID, and step ID, and then saves or updates the automation
     * information associated with the step within the funnel.
     *
     * @param mixed $data      The automation data to be saved or updated.
     * @param int   $funnel_id The ID of the funnel to which the step belongs.
     * @param int   $step_id   The ID of the step for which automation data is being saved or updated.
     *
     * @return bool            Returns true if the automation data is successfully saved or updated, otherwise false.
     * @since 2.0.0
     */
    public function save_or_update_automation($data, $funnel_id, $step_id)
    {
        // Check if both data and funnel ID are provided.
        if (!$data || !$funnel_id) {
            return false;
        }

        // Define the class name for the AutomationModel.
        $class_name = "MintMail\\App\\Internal\\Automation\\AutomationModel";

        // Check if the AutomationModel class exists.
        if (!class_exists($class_name)) {
            return false;
        }

        // Use the AutomationModel class to create or update the automation data.
        $automation_id = $class_name::get_instance()->create_or_update($data);

        // Check if the automation ID was obtained.
        if (!$automation_id) {
            return false;
        }

        // Update the post meta for the step with the automation ID.
        update_post_meta($step_id, 'wpfnl_mint_automation_id', $automation_id);

        // Define an array of meta data to associate with the automation.
        $meta_data = [
            [
                'meta_key'   => 'source',
                'meta_value' => 'wpf',
            ],
            [
                'meta_key'   => 'funnel_id',
                'meta_value' => $funnel_id,
            ],
        ];

        

        $this->maybe_automation_exist( $automation_id );

        // Iterate through the meta data array and update automation meta accordingly.
        foreach ($meta_data as $meta) {
            $this->update_automation_meta($automation_id, $meta['meta_key'], $meta['meta_value']);
        }

        // Return true to indicate successful saving or updating of automation data.
        return true;
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
     * Update or insert automation meta data.
     *
     * This method allows you to update or insert meta data associated with automation. It takes in an automation ID,
     * a meta key, and a meta value, and performs the necessary database operations to update or insert the data.
     *
     * @param int    $automation_id The ID of the automation.
     * @param string $meta_key      The key of the meta data to update or insert.
     * @param string $meta_value    The value of the meta data to update or insert.
     *
     * @return bool|int             Returns true if the meta data is successfully updated, the inserted ID if data is added, or false on failure.
     * @since 2.0.0
     */
    public function update_automation_meta($automation_id, $meta_key, $meta_value)
    {
        global $wpdb;

        // Define the table name for automation meta data.
        $automation_meta_table = $wpdb->prefix . AutomationMetaSchema::$table_name;

        // Check if the meta data already exists.
        $select_query = $wpdb->prepare("SELECT * FROM $automation_meta_table WHERE automation_id = %d AND meta_key = %s", array($automation_id, $meta_key));
        $results = $wpdb->get_results($select_query);

        // If meta data already exists.
        if (is_array($results) && !empty($results)) {
            try {
                // Prepare the payload for updating.
                $payload = [
                    'id'            => isset($results[0]->id) ? $results[0]->id : '',
                    'meta_key'      => $meta_key,
                    'meta_value'    => $meta_value,
                ];

                // Update the 'updated_at' timestamp.
                $payload['updated_at'] = current_time('mysql');

                // Perform the update operation.
                $updated = $wpdb->update(
                    $automation_meta_table,
                    $payload,
                    array('ID' => $payload['id'])
                );

                // Check if the update was successful.
                if ($updated) {
                    return true;
                } else {
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        } else {
            // If meta data doesn't exist, insert it.
            try {
                $wpdb->insert(
                    $automation_meta_table,
                    array(
                        'automation_id' => $automation_id,
                        'meta_key'      => $meta_key,
                        'meta_value'    => $meta_value,
                        'created_at'    => current_time('mysql'),
                        'updated_at'    => current_time('mysql'),
                    )
                );

                // Return the inserted ID.
                return $wpdb->insert_id;
            } catch (\Exception $e) {
                return false;
            }
        }
    }


    /**
     * Delete automation by Id
     */
    public function delete_automation( $step_id ){
        if( !$step_id ){
            return false;
        }
        $automation_id = get_post_meta($step_id, 'wpfnl_mint_automation_id', true);
        
        if( !$automation_id ){
            return false;
        }

        $automationModel = "MintMail\\App\\Internal\\Automation\\AutomationModel";
        
        if (!class_exists($automationModel)) {
            return false;
        }
       

        $function_exist = is_callable(array($automationModel, 'destroy'));
        if( !$function_exist ){
            return false;
        }
        
        delete_post_meta( $step_id, 'wpfnl_mint_automation_id' );
        return $automationModel::destroy( $automation_id );
    }

}