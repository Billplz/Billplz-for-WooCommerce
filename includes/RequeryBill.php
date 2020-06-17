<?php

defined('ABSPATH') || exit;

if (is_admin()) {
    new BfwRequery();
    add_action('wp_ajax_bfw_requery_single', 'bfw_requery_single');
}

function bfw_requery_single()
{
    $bill_id = $_POST['bill_id'];
    $order_id = $_POST['order_id'];

    $bill = get_post_meta($order_id, $bill_id, true);

    if ($bill === 'due') {
        $settings = get_option('woocommerce_billplz_settings');
        $connect = new BillplzWooCommerceWPConnect($settings['api_key']);
        $connect->setStaging($settings['is_sandbox'] == 'yes');
        $billplz = new BillplzWooCommerceAPI($connect);
        list($rheader, $rbody) = $billplz->toArray($billplz->getBill($bill_id));

        if ($rheader !== 200) {
            echo 'Unable to access Billplz due to invalid API Key.';
            wp_die();
        }

        if ($rbody['paid']) {
            $order_note = 'Payment Status: SUCCESSFUL';
            $order_note .= '<br>Is Sandbox: ' . ($settings['is_sandbox'] == 'yes' ? 'Yes' : 'No');
            $order_note .= '<br>Bill ID: ' . $rbody['id'];
            $order_note .= '<br>Type: Requery';
            $order = new WC_Order($order_id);
            $order->add_order_note($order_note);
            $order->payment_complete($bill_id);
            update_post_meta($order_id, $bill_id, 'paid');
            echo 'Successfully Updated status for Order ID #' . $order_id;
        }

    } elseif (empty($bill)) {
        echo 'Order not found';
    } elseif ($bill === 'paid') {
        echo 'Order already paid';
    } else {
        echo 'Unknown error.';
    }

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
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_script'));
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
            array($this, 'create_admin_page')
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
        submit_button('Requery Status', 'primary', 'requery-single', false);?>
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
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            'setting_bill_id', // ID
            'Requery by Bill ID and Order ID', // Title
            array($this, 'print_section_info'), // Callback
            'bfw-requery-tool' // Page
        );

        add_settings_field(
            'bill_id', // ID
            'Bill ID', // Title
            array($this, 'bill_id_callback'), // Callback
            'bfw-requery-tool', // Page
            'setting_bill_id' // Section
        );

        add_settings_field(
            'order_id',
            'Order ID',
            array($this, 'order_id_callback'),
            'bfw-requery-tool',
            'setting_bill_id'
        );

        add_settings_field(
            'status',
            'Status: ',
            array($this, 'status_callback'),
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
        print 'Enter your <b>Bill ID</b> and <b>Order ID</b> to requery.';
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
            array('ajax_url' => admin_url('admin-ajax.php'))
        );
    }
}
