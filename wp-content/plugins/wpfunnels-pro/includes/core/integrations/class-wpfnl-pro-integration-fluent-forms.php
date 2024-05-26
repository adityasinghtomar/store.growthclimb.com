<?php
namespace WPFunnelsPro\Integration;
use FluentCrm\App\Services\Funnel\FunnelProcessor;

class Wpfnl_Pro_Integration_Fluent_Forms {

    public function __construct() {
          add_filter('fluentform_submission_confirmation', array($this, 'fluentform_submission_confirmations'), 10, 4);
          add_filter('fluentcrm_funnel_will_process_fluentform_submission_inserted', array($this, 'fluentcrm_funnel_will_process_fluentform_submission_inserted'), 99, 4);
          add_filter('cron_schedules', array($this, 'wpfnl_10minute_schedules'));
          add_action('wpfnl_10_min_client_check', array($this, 'wpfnl_10_min_client_check'));
          add_action('woocommerce_new_order', array($this, 'wpfnl_woocommerce_new_order'), 10, 1);
    }

    public function fluentform_submission_confirmations($returnData, $form, $confirmation, $formData)
    {

        $trigger = false;
        $redirect_link = '';
        if (isset($formData['__fluent_form_embded_post_id'])) {
            $embedded_post = $formData['__fluent_form_embded_post_id'];
            $next_step = get_post_meta($embedded_post, 'fluent_form_redirect_link', true);
            if ($next_step) {
                $redirect_link = get_post_permalink($next_step);
                $data = array(
                    'redirectUrl' => $redirect_link,
                    'message' => 'Redirecting to next step',
                    'action' => 'hide_form',
                );
                return $data;
            }
        }
        return;
    }

    public function fluentcrm_funnel_will_process_fluentform_submission_inserted($willProcess, $funnel, $subscriberData, $originalArgs)
    {
        $formData = $originalArgs[1];
        $page_id = $formData['__fluent_form_embded_post_id'];
        $trigger = false;
        if (isset($formData['__fluent_form_embded_post_id'])) {
            $embedded_post = $formData['__fluent_form_embded_post_id'];
            $funnel_id = get_post_meta($embedded_post, '_funnel_id', true);
            $steps_order = get_post_meta($funnel_id, '_steps_order', true);

            foreach ($steps_order as $step_key => $step_value) {
                if ($step_value['type'] == 'checkout') {
                    $trigger = true;
                }
            }

            if ($trigger) {
                $insertId = $originalArgs[0];
                $get_crm_data = array();
                $data = array(
                    'funnel' => $funnel,
                    'subscriber' => $subscriberData,
                    'insertID' => $insertId,
                );

                $get_crm_data = get_option('wpfnl_fluent_crm_data');
                $get_crm_data[] = $data;

                update_option('wpfnl_fluent_crm_data', $get_crm_data);
                return false;
            }

            return true;

        }

        return true;
    }

    public function wpfnl_10minute_schedules($schedules)
    {
        if (!isset($schedules["10min"])) {
            $schedules["10min"] = array(
                'interval' => 10 * 60,
                'display' => __('Once every 10 minutes'));
        }
        return $schedules;
    }

    public function wpfnl_10_min_cron()
    {
        wp_schedule_event(time(), '10min', 'wpfnl_10_min_client_check');
    }

    public function wpfnl_10_min_client_check()
    {
        $crm_pending_objects = get_option('wpfnl_fluent_crm_data');
        $contactApi = FluentCrmApi('contacts');
        if ($crm_pending_objects) {
            foreach ($crm_pending_objects as $crm_object) {

                if (isset($crm_object['funnel']) && isset($crm_object['insertID']) && isset($crm_object['subscriber'])) {
                    $funnel = $crm_object['funnel'];
                    $insert_id = $crm_object['insertID'];
                    $subscriberData = $crm_object['subscriber'];
                    $subscriberData['status'] = 'subscriber';
                    $subscriber = $contactApi->createOrUpdateContact($subscriberData);
                    (new FunnelProcessor())->startFunnelSequence($funnel, $subscriberData, [
                        'source_trigger_name' => 'fluentform_submission_inserted',
                        'source_ref_id' => $insert_id,
                    ], $subscriber);
                }
            }
            delete_option('wpfnl_fluent_crm_data');
        }
    }

    public function wpfnl_woocommerce_new_order($order_id)
    {
        $order = wc_get_order($order_id);
        $billing_email = $order->get_billing_email();
        $crm_pending_objects = get_option('wpfnl_fluent_crm_data');
        if ($crm_pending_objects) {
            foreach ($crm_pending_objects as $crm_key => $crm_object) {
                $subscriberData = $crm_object['subscriber'];
                if ($subscriberData['email'] == $billing_email) {
                    unset($crm_pending_objects[$crm_key]);
                }
            }
            update_option('wpfnl_fluent_crm_data', $crm_pending_objects);
        }
    }
}
