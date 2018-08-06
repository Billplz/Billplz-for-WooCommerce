<?php

if (is_admin()) {
    new BfwRequery();
    add_action('wp_ajax_bfw_requery_single', 'bfw_requery_single');
    add_action('wp_ajax_bfw_requery_all', 'bfw_requery_all');
}

function bfw_requery_single()
{
    global $wpdb;
    $bill_id_array = explode(',', preg_replace('/\s+/', '', $_POST['bill_id']));
    $order_id_array = explode(',', preg_replace('/\s+/', '', $_POST['order_id']));
    $output = array();

    foreach ($bill_id_array as $bill_id) {
        if (!empty($bill_id)) {
            $results = $wpdb->get_results("select post_id, meta_key from $wpdb->postmeta where meta_value = '$bill_id'", ARRAY_A);
            if (empty($results)) {
                $output[]= 'Order for Bill ID '.$bill_id.' not found';
            } else {
                $bill_order_id = $results[0]['post_id'];
                $bill_order_api_key = get_post_meta($bill_order_id, 'billplz_api_key', true);
                $bill_order_paid = get_post_meta($bill_order_id, 'billplz_paid', true);

                $connnect = (new \Billplz\WooCommerce\WPConnect($bill_order_api_key))->detectMode();
                $billplz = new \Billplz\WooCommerce\API($connnect);
                list($rheader, $rbody) = $billplz->toArray($billplz->getBill($bill_id));

                if ($rbody['paid']) {
                    update_post_meta($bill_order_id, 'billplz_paid', 'true');
                    $order = new WC_Order($bill_order_id);
                    $order->add_order_note('Payment Status: SUCCESSFUL' . '<br>Bill ID: ' . $rbody['id']);
                    $order->payment_complete($bill_id);
                    $output[]= 'Successfully Updated status for Order ID #'.$bill_order_id;
                } else {
                    $output[]= 'Status for Bill ID '.$bill_id.' not updated due to unpaid status';
                }
            }
        }
    }

    foreach ($order_id_array as $order_id) {
        if (!empty($order_id)) {
            $order_bill_id = get_post_meta($order_id, '_transaction_id', true);
            $order_bill_api_key = get_post_meta($order_id, 'billplz_api_key', true);
            $order_bill_paid = get_post_meta($order_id, 'billplz_paid', true);

            if (empty($order_bill_id) || empty($order_bill_api_key) || empty($order_bill_paid)) {
                $output[]= 'Order ID #'.$order_id.' not found';
            } else {
                $connnect = (new \Billplz\WooCommerce\WPConnect($order_bill_api_key))->detectMode();
                $billplz = new \Billplz\WooCommerce\API($connnect);
                list($rheader, $rbody) = $billplz->toArray($billplz->getBill($order_bill_id));

                if ($rbody['paid']) {
                    update_post_meta($order_id, 'billplz_paid', 'true');
                    $order = new WC_Order($order_id);
                    $order->add_order_note('Payment Status: SUCCESSFUL' . '<br>Bill ID: ' . $rbody['id']);
                    $order->payment_complete($order_bill_id);
                    $output[]= 'Successfully updated status for Order ID #'.$order_id;
                } else {
                    $output[]= 'Status for Order ID #'.$order_id.' not updated due to unpaid status';
                }
            }
        }
    }
    echo implode("<br>", $output);

    wp_die();
}

function bfw_requery_all()
{
    global $wpdb;

    set_time_limit(1800);
    ignore_user_abort(true);

    $sql = "select postmeta.post_id from $wpdb->postmeta postmeta, $wpdb->posts posts where postmeta.meta_value = 'false' AND postmeta.meta_key = 'billplz_paid'  AND posts.post_status<>'trash' AND posts.id=postmeta.post_id";
    $results = $wpdb->get_results($sql, ARRAY_A);
    $output= array();

    foreach ($results as $result) {
        $order_id = $result['post_id'];
        $bill_id = get_post_meta($order_id, '_transaction_id', true);
        $bill_api_key = get_post_meta($order_id, 'billplz_api_key', true);
        $bill_paid = get_post_meta($order_id, 'billplz_paid', true);

        if (empty($bill_id) || empty($bill_api_key) || empty($bill_paid)) {
            continue;
        }

        $connnect = (new \Billplz\WooCommerce\WPConnect($bill_api_key))->detectMode();
        $billplz = new \Billplz\WooCommerce\API($connnect);
        list($rheader, $rbody) = $billplz->toArray($billplz->getBill($bill_id));
        if ($rbody['paid']) {
            update_post_meta($order_id, 'billplz_paid', 'true');
            $order = new WC_Order($order_id);
            $order->add_order_note('Payment Status: SUCCESSFUL' . '<br>Bill ID: ' . $rbody['id']);
            $order->payment_complete($bill_id);
            $output[]= 'Successfully updated status for Order ID #'.$order_id;
        } else {
            $output[]= 'Status for Order ID #'.$order_id.' not updated due to unpaid status';
        }
    }
    echo implode("<br>", $output);
    wp_die();
}

class BfwRequery
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action('admin_menu', array( $this, 'add_plugin_page' ));
        add_action('admin_init', array( $this, 'page_init' ));
        add_action('admin_enqueue_scripts', array( $this, 'enqueue_script' ));
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Billplz Requery Tool (Experimental)',
            'BFW Tool',
            'manage_options',
            'bfw-requery-tool',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        // $this->options = get_option('bfw_option_name');?>
        <div class="wrap">
        <h1>Billplz Bill Requery Tool (Experimental)</h1>
        <form method="post" action="options.php">
        <?php
        // This prints out all hidden setting fields
        settings_fields('bfw_option_group');
        do_settings_sections('bfw-requery-tool');
        submit_button('Requery Status', 'primary', 'requery-single', false);
        submit_button('Requery ALL', 'delete', 'requery-all', true); ?>
        </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'bfw_option_group', // Option group
            'bfw_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_bill_id', // ID
            'Requery by Bill ID', // Title
            array( $this, 'print_section_info' ), // Callback
            'bfw-requery-tool' // Page
        );

        add_settings_field(
            'bill_id', // ID
            'Bill ID', // Title
            array( $this, 'bill_id_callback' ), // Callback
            'bfw-requery-tool', // Page
            'setting_bill_id' // Section
        );

        add_settings_field(
            'order_id',
            'Order ID',
            array( $this, 'order_id_callback' ),
            'bfw-requery-tool',
            'setting_bill_id'
        );

        add_settings_field(
            'status',
            'Status: ',
            array( $this, 'status_callback' ),
            'bfw-requery-tool',
            'setting_bill_id'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize($input)
    {
        $new_input = array();
        if (isset($input['bill_id'])) {
            $new_input['bill_id'] = sanitize_text_field($input['bill_id']);
        }

        if (isset($input['order_id'])) {
            $new_input['order_id'] = absint($input['order_id']);
        }

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your <b>Bill ID</b> and/or <b>Order ID</b> to requery.';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function bill_id_callback()
    {
        printf(
            '<input type="text" id="bill_id" name="bfw_option_name[bill_id]" value="%s" />',
            isset($this->options['bill_id']) ? esc_attr($this->options['bill_id']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function order_id_callback()
    {
        printf(
            '<input type="text" id="order_id" name="bfw_option_name[order_id]" value="%s" />',
            isset($this->options['order_id']) ? esc_attr($this->options['order_id']) : ''
        );
    }

    public function status_callback()
    {
        printf(
            '<p id="status_callback"></p>'
        );
    }

    public function enqueue_script($hook)
    {
        if ('settings_page_bfw-requery-tool' != $hook) {
            // Only applies to dashboard panel
            return;
        }

        wp_enqueue_script('ajax-script', plugins_url('/js/requery-tool.js', __FILE__), array('jquery'));

        // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
        wp_localize_script(
            'ajax-script',
            'ajax_object',
            array( 'ajax_url' => admin_url('admin-ajax.php'))
        );
    }
}
