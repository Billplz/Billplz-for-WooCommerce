<?php

/*
 *  This class checks for smsnoti variable availability.
 *  If smsnoti available, it consider the older version is 3.8 and below
 *  and database upgrade are required.
 * 
 *  This should be removed when most user are considered are using
 *  latest version
 */

class updatedb {

    public function __construct() {
        global $wpdb;
        $sql = "SELECT option_value FROM $wpdb->options WHERE  option_name = 'woocommerce_billplz_settings'";
        // Get result from SQL Query and Unserialize the Data
        $gatewayParam = unserialize($wpdb->get_var($sql));
        if (isset($gatewayParam['smsnoti'])) {
            $smsnoti = $gatewayParam['smsnoti'];
            $mainoti = $gatewayParam['emailnoti'];
            if ($smsnoti=='yes' AND $mainnoti=='yes') {
                $gatewayParam['notification'] = 'Both';
            } elseif ($smsnoti=='no' AND $mainnoti=='yes') {
                $gatewayParam['notification'] = 'Email';
            } elseif ($smsnoti=='yes' AND $mainnoti=='no') {
                $gatewayParam['notification'] = 'SMS';
            } else {
                $gatewayParam['notification'] = 'None';
            }
            if (isset($gatewayParam['teststaging'])) {
                if ($gatewayParam['teststaging']=='yes') {
                    $gatewayParam['teststaging'] = 'Staging';
                } else {
                    $gatewayParam['teststaging'] = 'Production';
                }
            }
            unset($gatewayParam['smsnoti'], $gatewayParam['emailnoti'], $gatewayParam['preventduplicatebill']);
            $wpdb->update($wpdb->options, array('option_value' => serialize($gatewayParam)), array(
                'option_name' => 'woocommerce_billplz_settings'));
        }
        unset($sql, $gatewayParam);
    }

}
