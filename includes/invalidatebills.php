<?php

function wcbillplz_activate_cron() {
    if (!wp_next_scheduled('billplz_bills_invalidator')) {
        wp_schedule_event(time(), 'bfw', 'billplz_bills_invalidator');
    }
}

function wcbillplz_deactivate_cron() {
    // find out when the last event was scheduled
    $timestamp = wp_next_scheduled('billplz_bills_invalidator');
    // unschedule previous event if any
    wp_unschedule_event($timestamp, 'billplz_bills_invalidator');
}

function wcbillplz_stocktime() {
    global $wpdb;
    $sql = "SELECT option_value FROM $wpdb->options WHERE option_name='woocommerce_hold_stock_minutes'";
    $time = $wpdb->get_var($sql);
    if (empty($time)) {
        $time = 10000;
    }
    return $time / 2 * 60 / 86400;
}

function wcbillplz_cron_add($schedules) {
    // Get Hold Stock Time
    $masa = wcbillplz_stocktime();
    // Register the time
    $schedules['bfw'] = array(
        'interval' => $masa * 86400,
        'display' => __('Billplz for WooCommerce')
    );
    return $schedules;
}

function wcbillplz_delete_bills() {

    global $wpdb;
    $sql = "SELECT option_value FROM $wpdb->options WHERE  option_name = 'woocommerce_billplz_settings'";
    $gatewayParam = unserialize($wpdb->get_var($sql));
    $api_key = $gatewayParam['api_key'];

    $sql = "SELECT $wpdb->posts.ID 
         FROM $wpdb->posts
            WHERE (post_status = 'wc-cancelled' OR post_status='wc-completed' OR post_status='wc-processing' OR post_status='wc-on-hold' OR post_status='wc-refunded' OR post_status='wc-failed') 
            AND $wpdb->posts.ID 
            in 
            (
            SELECT DISTINCT $wpdb->postmeta.post_id 
            FROM $wpdb->postmeta, $wpdb->posts 
            WHERE meta_key = '_wc_billplz_id')";

    $post_id = $wpdb->get_results($sql);
    require_once(__DIR__ . '/billplz.php');
    $billplz = new Billplz($api_key);
    if (!empty($post_id)) {
        foreach ($post_id as $value) {
            $sql = get_post_meta($value->ID, '_wc_billplz_id', false);
            foreach ($sql as $bill_id) {
                $deleteProcess = $billplz->deleteBill($bill_id);
                if ($deleteProcess) {
                    delete_post_meta($value->ID, '_wc_billplz_hash');
                    delete_post_meta($value->ID, '_wc_billplz_url');
                    delete_post_meta($value->ID, '_wc_billplz_id');
                    //error_log('Bills ' . $bill_id . ' has successfully removed from DB and/or Billplz');
                } elseif (!$deleteProcess) {
                    $moreData = $billplz->check_bill($bill_id);
                    if (isset($moreData['state'])) {
                        if ($moreData['state'] == 'paid') {
                            delete_post_meta($value->ID, '_wc_billplz_hash');
                            delete_post_meta($value->ID, '_wc_billplz_url');
                            delete_post_meta($value->ID, '_wc_billplz_id');
                        }
                    }
                } else {
                    //error_log('Bills ' . $bill_id . ' has not removed from DB and Billplz');
                    //Maybe because of pending payment by the user
                }
            }
        }
    } else {
        //error_log('WP Cron for Billplz for WooCommerce does not delete anything because no order has been found');
    }
}
