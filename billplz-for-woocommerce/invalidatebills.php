<?php

class invalidatebills {

    public function __construct() {
        register_activation_hook(__FILE__, array(&$this, 'bibfw_activate_cron'));
        add_filter('cron_schedules', array(&$this, 'cron_add_bibfw'));
        register_deactivation_hook(__FILE__, array(&$this, 'bibfw_deactivate_cron'));
        add_action('wp', array(&$this, 'bibfw_activate_cron'));
        add_action('billplz_bills_invalidator', array(&$this, 'billplz_delete_bills_function'));
    }

    /*
     * Register WordPress Cron
     */

    public function bibfw_activate_cron() {
        if (!wp_next_scheduled('billplz_bills_invalidator')) {
            wp_schedule_event(time(), 'bfw', 'billplz_bills_invalidator');
        }
    }

    /*
     *  Remove registered WordPress Cron
     */

    public function bibfw_deactivate_cron() {
        // find out when the last event was scheduled
        $timestamp = wp_next_scheduled('billplz_bills_invalidator');
        // unschedule previous event if any
        wp_unschedule_event($timestamp, 'billplz_bills_invalidator');
    }

    /*
     *  Get WooCommerce Hold Stock Time
     *  $param null
     *  $return integer
     */

    function get_woo_hld_stocktime() {
        global $wpdb;
        $sql = "SELECT option_value FROM $wpdb->options WHERE option_name='woocommerce_hold_stock_minutes'";
        $woomm = $wpdb->get_var($sql);
        return $woomm / 2 * 60 / 86400;
    }

    function cron_add_bibfw($schedules) {
        // Get Hold Stock Time
        $masa = $this->get_woo_hld_stocktime();
        // Register the time
        $schedules['bfw'] = array(
            'interval' => $masa * 86400,
            'display' => __('Billplz for WooCommerce')
        );
        return $schedules;
    }

    function billplz_delete_bills_function() {
        global $wpdb;
        //Prepare SQL Query for Bills ID
        $sql = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_wc_billplz_orderid'";
        //Get result from SQL Query
        $id = $wpdb->get_results($sql);
        //Prepare SQL Query for Getting API Key
        $sql = "SELECT option_value FROM $wpdb->options WHERE  option_name = 'woocommerce_billplz_settings'";
        // Get result from SQL Query and Unserialize the Data
        $gatewayParam = unserialize($wpdb->get_var($sql));
        $mode = $gatewayParam['teststaging'];
        $api_key = $gatewayParam['api_key'];
        $obj = new DeleteBill($api_key, $mode);
        //Iterate every ID to delete it
        foreach ($id as $billid) {
            //----------------------------------------------------------------//
            //Dapatkan post meta ID 
            $sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_value='$billid->meta_value'";
            $post_id = $wpdb->get_var($sql);
            //
            /*
             * Semak samada bill tersebut boleh dibuang atau tidak
             * Jika status:
             *      wc-cancelled
             *      wc-completed
             *      wc-processing
             *      wc-on-hold
             *      wc-refunded
             *      wc-failed
             * Boleh buang. Kalau selain dari diatas, tak boleh buang.
             * Completed dan Processing juga dibuang sebab takmau bebankan
             * proses foreach. Tapi, bills takkan dibuang dari Billplz. 
             * 
             */
            $sql = "SELECT post_status FROM $wpdb->posts WHERE ID=$post_id AND (post_status='wc-cancelled' OR post_status='wc-completed' OR post_status='wc-processing' OR post_status='wc-on-hold' OR post_status='wc-refunded' OR post_status='wc-failed')";
            $timeOut = $wpdb->get_var($sql);
            if ($timeOut != '') {
                if ($obj->prepare()->setInfo($billid->meta_value)->process()->checkBill()) {
                    delete_post_meta($post_id, '_wc_billplz_orderid');
                    delete_post_meta($post_id, '_wc_billplz_ordername');
                    delete_post_meta($post_id, '_wc_billplz_ordercollection');
                    delete_post_meta($post_id, '_wc_billplz_orderemail');
                    delete_post_meta($post_id, '_wc_billplz_orderphone');
                    delete_post_meta($post_id, '_wc_billplz_orderurl');
                }
            }
        }
        unset($obj, $sql, $gatewayParam, $mode, $api_key);
    }

}

class DeleteBill {

    var $api_key, $mode, $id, $objdelete, $objcheck;

    public function __construct($api_key, $mode) {
        require_once 'billplz.php';
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
            if ($data['state'] == 'hidden' || $data['state'] == 'paid') {
                return true;
            }
        } else {
            return false;
        }
    }

}
