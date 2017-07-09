<?php

function wcbillplz_activate_cron()
{
    if (!wp_next_scheduled('billplz_bills_invalidator')) {
        wp_schedule_event(time(), 'daily', 'billplz_bills_invalidator');
    }
}

function wcbillplz_deactivate_cron()
{
    // find out when the last event was scheduled
    $timestamp = wp_next_scheduled('billplz_bills_invalidator');
    // unschedule previous event if any
    wp_unschedule_event($timestamp, 'billplz_bills_invalidator');
}

function wcbillplz_delete_bills()
{

    global $wpdb;
    $gatewayParam = maybe_unserialize(get_option('woocommerce_billplz_settings', false));
    $api_key = $gatewayParam['api_key'];

    $sql = "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'billplz_fwoo_clutter_%'";
    $option_name_val = $wpdb->get_results($sql);
    
    if (empty($option_name_val)){
        return;
    }

    require_once(__DIR__ . '/billplz.php');
    $billplz = new Billplz($api_key);
    foreach ($option_name_val as $nameval) {
        $bill_id = substr($nameval->option_name, 21);
        
        /*
         * If the bills successfully deleted, remove from DB
         */
        if ($billplz->deleteBill($bill_id)){
            delete_option('billplz_fwoo_clutter_' . $bill_id);
        }
    }
}
