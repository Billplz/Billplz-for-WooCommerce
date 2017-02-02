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
    $mode = $gatewayParam['teststaging'];
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
    $billplz = new DeleteBill($api_key, $mode);
    if (!empty($post_id)) {
        foreach ($post_id as $value) {
            $sql = get_post_meta($value->ID, '_wc_billplz_id', false);
            foreach ($sql as $bill_id) {
                if ($billplz->prepare()->setInfo($bill_id)->process()->checkBill()) {
                    delete_post_meta($value->ID, '_wc_billplz_hash');
                    delete_post_meta($value->ID, '_wc_billplz_url');
                    delete_post_meta($value->ID, '_wc_billplz_id');
                    //error_log('Bills ' . $bill_id . ' has successfully removed from DB and/or Billplz');
                } else {
                    //error_log('Bills ' . $bill_id . ' has not removed from DB and Billplz');
                }
            }
        }
    } else {
        //error_log('WP Cron for Billplz for WooCommerce does not delete anything because no order has been found');
    }
}

class DeleteBill {

    var $api_key, $mode, $id, $objdelete, $objcheck;

    public function __construct($api_key, $mode) {
        require_once(__DIR__ . '/billplz.php');
        $this->api_key = $api_key;
        $this->mode = $mode;
        $this->objdelete = new curlaction;
        $this->objcheck = new billplz;
    }

    public function prepare() {
        $this->objdelete->setAPI($this->api_key)->setAction('DELETE');
        return $this;
    }

    public function setInfo($id) {
        //this->id is saved for checkBill() function
        $this->id = $id;
        $this->objdelete->setURL($this->mode, $id);
        return $this;
    }

    public function process() {
        $this->objdelete->curl_action('');
        return $this;
    }

    public function checkBill() {
        $data = $this->objcheck->check_bill($this->api_key, $this->id, $this->mode);
        if (isset($data['state'])) {
            // Hidden dah buang. Paid tak boleh buang
            if ($data['state'] == 'hidden' || $data['state'] == 'paid') {
                // True maksudnya dah buang
                return true;
            }
            // False maknya tak buang
            return false;
        } else {
            return false;
        }
    }

}
