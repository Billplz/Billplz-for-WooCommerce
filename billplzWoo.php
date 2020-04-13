<?php

/**
 * Plugin Name: Billplz for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/billplz-for-woocommerce/
 * Description: Billplz. Fair payment platform. | <a href="https://www.billplz.com/enterprise/signup" target="_blank">Sign up Now</a>.
 * Author: Billplz Sdn. Bhd.
 * Author URI: http://github.com/billplz/billplz-for-woocommerce
 * Version: 3.24.0
 * Requires PHP: 7.0
 * Requires at least: 4.6
 * License: GPLv3
 * Text Domain: bfw
 * Domain Path: /languages/
 * WC requires at least: 3.0
 * WC tested up to: 4.0
 */

/* Load Billplz Class */
if (!class_exists('BillplzWooCommerceAPI') && !class_exists('BillplzWooCommerceWPConnect')) {
    require 'includes/Billplz_API.php';
    require 'includes/Billplz_WPConnect.php';
}

/* Load Requery Bill Module */
require 'includes/RequeryBill.php';

/* Load Bank Name List */
require 'includes/Billplz_BankName.php';

/* Load upgrade script to prevent error */
require 'includes/Upgrade.php';

function bfw_plugin_uninstall()
{
    global $wpdb;

    /* Remove rows that created from previous version */
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'billplz_fwoo_%'");

    delete_option('billplz_fpx_banks');
    delete_option('billplz_fpx_banks_last');
}
register_uninstall_hook(__FILE__, 'bfw_plugin_uninstall');

/*
 *  Add settings link on plugin page
 */

function bfw_plugin_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=billplz">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin_action_link = 'plugin_action_links_' . plugin_basename(__FILE__);
add_filter($plugin_action_link, 'bfw_plugin_settings_link');

function bfw_fallback_notice()
{
    $message = '<div class="error">';
    $message .= '<p>' . __('Billplz for WooCommerce depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work!', 'bfw') . '</p>';
    $message .= '</div>';
    echo $message;
}

/**
 * Billplz plugin function
 *
 * @return mixed
 */
function bfw_load()
{
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'bfw_fallback_notice');
        return;
    }
    // Load language
    load_plugin_textdomain('bfw', false, dirname(plugin_basename(__FILE__)) . '/languages/');

    /**
     * Add Billplz gateway to ensure WooCommerce can load it
     *
     * @param array $methods
     * @return array
     */
    function bfw_add_gateway($methods)
    {
        /* Retained for compatibility with previous version */
        $methods[] = 'WC_Billplz_Gateway';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'bfw_add_gateway');
    add_filter('bfw_settings_value', array('WC_Billplz_Gateway', 'settings_value'));

    /**
     * Define the Billplz gateway
     *
     */
    class WC_Billplz_Gateway extends WC_Payment_Gateway
    {

        /** @var bool Whether or not logging is enabled */
        public static $log_enabled = false;

        /** @var WC_Logger Logger instance */
        public static $log = false;

        /**
         * Construct the Billplz for WooCommerce class
         *
         * @global mixed $woocommerce
         */
        public function __construct()
        {
            $this->id = 'billplz';
            $this->icon = apply_filters('bfw_icon', plugins_url('assets/billplz.gif', __FILE__));
            $this->method_title = __('Billplz', 'bfw');
            $this->method_description = __('Have your customers pay with Billplz.', 'bfw');

            $this->debug = 'yes' === $this->get_option('debug', 'no');

            // Load the form fields.
            $this->init_form_fields();
            // Load the settings.
            $this->init_settings();

            /* Enable settings value alteration through plugins/themes function */
            $this->settings = apply_filters('bfw_settings_value', $this->settings);

            /* Customize checkout button label */
            $this->order_button_text = __($this->settings['checkout_label'], 'bfw');

            // Define user setting variables.
            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];

            $this->is_sandbox = $this->get_option('is_sandbox') == 'yes';
            $this->api_key = $this->settings['api_key'];
            $this->x_signature = $this->settings['x_signature'];
            $this->collection_id = $this->settings['collection_id'];
            $this->custom_error = $this->settings['custom_error'];

            $this->reference_1_label = $this->settings['reference_1_label'];
            $this->reference_1 = $this->settings['reference_1'];

            $this->has_fields = $this->settings['has_fields'];
            if (isset($this->has_fields) && $this->has_fields === 'yes') {
                add_filter('bfw_url', array($this, 'url'));
            }

            if (!$this->is_valid_for_use()) {
                $this->enabled = 'no';
            }

            // Payment instruction after payment
            $this->instructions = $this->settings['instructions'];

            add_action('woocommerce_thankyou_billplz', array($this, 'thankyou_page'));
            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);

            self::$log_enabled = $this->debug;

            /* Set Receipt Page */
            add_action('woocommerce_receipt_billplz', array(&$this, 'receipt_page'));

            // Save setting configuration
            add_action('woocommerce_update_options_payment_gateways_billplz', array($this, 'process_admin_options'));

            // Payment listener/API hook
            add_action('woocommerce_api_wc_billplz_gateway', array($this, 'check_ipn_response'));

            /* Display error if API Key is not set */
            $this->api_key == '' ? add_action('admin_notices', array(&$this, 'api_key_missing_message')) : '';

            /* Display error if X Signature Key is not set */
            $this->x_signature == '' ? add_action('admin_notices', array(&$this, 'x_signature_missing_message')) : '';

            /* Display warning if Collection ID is not set */
            $this->collection_id == '' ? add_action('admin_notices', array(&$this, 'collection_id_missing_message')) : '';
        }

        public static function settings_value($settings)
        {
            if (isset($settings['has_fields']) && $settings['has_fields'] === 'yes') {
                $settings['reference_1_label'] = 'Bank Code';
                if (isset($_POST['billplz_bank'])) {
                    $bank_name = BillplzBankName::get();
                    if (isset($bank_name[$_POST['billplz_bank']])) {
                        $settings['reference_1'] = $_POST['billplz_bank'];
                    } else {
                        $settings['reference_1'] = '';
                    }
                }
            }
            return $settings;
        }

        public function process_admin_options()
        {
            delete_transient('billplz_get_payment_gateways');
            delete_transient('billplz_get_collection_gateways');
            parent::process_admin_options();
        }

        public function url($url)
        {
            if (isset($this->has_fields) && $this->has_fields === 'yes') {
                return $url . '?auto_submit=true';
            }
            return $url;
        }

        /**
         * Checking if this gateway is enabled and available in the user's country.
         *
         * @return bool
         */
        public function is_valid_for_use()
        {
            return in_array(
                get_woocommerce_currency(),
                apply_filters(
                    'bfw_supported_currencies',
                    array('MYR')
                ),
                true
            );
        }

        /**
         * Gateway Settings Form Fields.
         *
         */
        public function init_form_fields()
        {
            $form_fields = include 'includes/settings-billplz.php';
            $this->form_fields = apply_filters('bfw_form_fields', $form_fields);
        }

        public function payment_fields()
        {
            if ($description = $this->get_description()) {
                echo wpautop(wptexturize($description));
            }

            if (isset($this->has_fields) && $this->has_fields === 'yes') {

                if (false === ($gateways = get_transient('billplz_get_payment_gateways'))) {
                    $connect = new BillplzWooCommerceWPConnect($this->api_key);
                    $connect->setStaging($this->is_sandbox);

                    $billplz = new BillplzWooCommerceAPI($connect);
                    list($rheader, $rbody) = $billplz->toArray($billplz->getPaymentGateways());

                    $gateways = array();

                    if (isset($rbody['error']) || $rheader != 200) {
                        /* Do nothing */
                    } else {
                        $gateways = $rbody;
                    }

                    set_transient('billplz_get_payment_gateways', $gateways, HOUR_IN_SECONDS * 1);
                }

                if (false === ($collection_gateways = get_transient('billplz_get_collection_gateways'))) {
                    $connect = new BillplzWooCommerceWPConnect($this->api_key);
                    $connect->setStaging($this->is_sandbox);

                    $billplz = new BillplzWooCommerceAPI($connect);
                    list($rheader, $rbody) = $billplz->toArray($billplz->getPaymentMethodIndex($this->collection_id));

                    $collection_gateways = array();

                    if (isset($rbody['error']) || $rheader != 200) {
                        /* Do nothing */
                    } else {
                        foreach ($rbody['payment_methods'] as $payment_method) {
                            if ($payment_method['active']) {
                                if ($payment_method['code'] === 'isupaypal') {
                                    $payment_method['code'] = 'paypal';
                                }
                                $collection_gateways[] = $payment_method['code'];
                            }
                        }
                    }

                    set_transient('billplz_get_collection_gateways', $collection_gateways, MINUTE_IN_SECONDS * 30);
                }

                $bank_name = apply_filters('bfw_bank_name_list', BillplzBankName::get());

                /* Allow theme/plugin to override the way form is represented */
                if (has_action('bfw_payment_fields')):
                    do_action('bfw_payment_fields', $gateways, $bank_name);
                else:
                ?>
                <p class="form-row validate-required">
                    <label><?php echo 'Choose Payment Method'; ?> <span class="required">*</span></label>
                    <select name="billplz_bank">
                        <option value="" disabled selected>Choose your payment method</option>
                    <?php

                foreach ($bank_name as $key => $value) {
                    if (empty($gateways)) {
                        break;
                    }
                    foreach ($gateways['payment_gateways'] as $gateway) {
                        if ($gateway['code'] === $key && $gateway['active'] && in_array($gateway['category'], $collection_gateways)) {
                            ?><option value="<?php echo $gateway['code']; ?>"><?php echo $bank_name[$gateway['code']] ? strtoupper($bank_name[$gateway['code']]) : $gateway['code']; ?></option><?php

                        }
                    }
                }
                ?>
                    </select>
                </p>
                    <?php

                endif;
            }
        }

        /**
         * @return array string
         */
        public static function get_order_data($order)
        {
            $data = array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'total' => (string) ($order->get_total() * 100),
                'id' => $order->get_id(),
            );
            $data['first_name'] = empty($data['first_name']) ? $order->get_shipping_first_name() : $data['first_name'];
            $data['last_name'] = empty($data['last_name']) ? $order->get_shipping_last_name() : $data['last_name'];

            $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);

            /*
             * Compatibility with some themes
             */
            $data['email'] = !empty($data['email']) ? $data['email'] : $order->get_meta('_shipping_email');
            $data['email'] = !empty($data['email']) ? $data['email'] : $order->get_meta('shipping_email');
            $data['phone'] = !empty($data['phone']) ? $data['phone'] : $order->get_meta('_shipping_phone');
            $data['phone'] = !empty($data['phone']) ? $data['phone'] : $order->get_meta('shipping_phone');

            return apply_filters('bfw_filter_order_data', $data);
        }

        /**
         * Logging method.
         * @param string $message
         */
        public static function log($message)
        {
            if (self::$log_enabled) {
                if (empty(self::$log)) {
                    self::$log = new WC_Logger();
                }
                self::$log->add('billplz', $message);
            }
        }

        /**
         * Order error button.
         *
         * @param  object $order Order data.
         * @return string Error message and cancel button.
         */
        protected function billplz_order_error($order)
        {
            $html = '<p>' . __('An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'bfw') . '</p>';
            $html .= '<a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Click to try again', 'bfw') . '</a>';
            return $html;
        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment($order_id)
        {

            if (!isset($_POST['billplz_bank']) && $this->has_fields === 'yes') {
                wc_add_notice(__('Please choose your bank to proceed', 'bfw'), 'error');
                return;
            }

            self::log('Connecting to Billplz API for order id #' . $order_id);
            $connect = new BillplzWooCommerceWPConnect($this->api_key);
            $connect->setStaging($this->is_sandbox);
            $billplz = new BillplzWooCommerceAPI($connect);

            $bill_id = get_post_meta($order_id, '_transaction_id', true);
            if (!empty($bill_id)) {
                if (!empty(get_post_meta($order_id, $bill_id, true))){
                    delete_post_meta($order_id, $bill_id);
                    $billplz->deleteBill($bill_id);
                }
            }

            if ($this->get_option('do_not_clear_cart') !== 'yes') {
                WC()->cart->empty_cart();
            }

            $order = new WC_Order($order_id);
            $order = apply_filters('bfw_filter_order', $order, $rbody = array());
            $order_data = self::get_order_data($order);

            if (sizeof($order->get_items()) > 0) {
                foreach ($order->get_items() as $item) {
                    if ($item['qty']) {
                        $item_names[] = $item['name'] . ' x ' . $item['qty'];
                    }
                }
            }
            $description = sprintf(__('Order %s', 'woocommerce'), $order->get_order_number()) . " - " . implode(', ', $item_names);

            $parameter = array(
                'collection_id' => $this->collection_id,
                'email' => $order_data['email'],
                'mobile' => trim($order_data['phone']),
                'name' => empty($order_data['name']) ? $order_data['email'] : $order_data['name'],
                'amount' => $order_data['total'],
                'callback_url' => add_query_arg(array('wc-api' => 'WC_Billplz_Gateway', 'order' => $order_data['id']), home_url('/')),
                'description' => mb_substr(apply_filters('bfw_description', $description), 0, 200),
            );

            $optional = array(
                'redirect_url' => $parameter['callback_url'],
                'reference_1_label' => mb_substr($this->reference_1_label, 0, 20),
                'reference_1' => mb_substr($this->reference_1, 0, 120),
                'reference_2_label' => 'Order ID',
                'reference_2' => $order_data['id'],
            );

            $parameter['name'] = preg_replace("/[^a-zA-Z0-9\s]+/", "", $parameter['name']);

            self::log('Creating bill for order number #' . $order_data['id']);

            list($rheader, $rbody) = $billplz->toArray($billplz->createBill($parameter, $optional));

            if ($rheader !== 200) {
                self::log('Error Creating bill for order number #' . $order_data['id'] . print_r($rbody, true));
                wc_add_notice(__('ERROR: ', 'bfw') . print_r($rbody, true), 'error');
                return;
            }

            self::log('Bill ID ' . $rbody['id'] . ' created for order number #' . $order_data['id']);

            if (!add_post_meta($order_id, $rbody['id'], 'due', true)) {
                update_post_meta($order_id, $rbody['id'], 'due');
            }

            if (!add_post_meta($order_id, '_transaction_id', $rbody['id'], true)) {
                update_post_meta($order_id, '_transaction_id', $rbody['id']);
            }

            return array(
                'result' => 'success',
                'redirect' => apply_filters('bfw_url', $rbody['url']),
            );
        }

        /**
         * Check for Billplz Response
         *
         * @access public
         * @return void
         */
        public function check_ipn_response()
        {
            @ob_clean();

            try {
                $data = BillplzWooCommerceWPConnect::getXSignature($this->x_signature);
            } catch (Exception $e) {
                status_header(403);
                exit('Failed X Signature Validation');
            }

            $order_id = sanitize_text_field($_GET['order']);

            $post_meta = get_post_meta($order_id, $data['id'], true);

            if (empty($post_meta)) {
                status_header(404);
                exit('Order not found');
            }

            // Log return type
            self::log('Billplz response ' . print_r($data, true));

            $order = new WC_Order($order_id);
            $order = apply_filters('bfw_filter_order', $order, array());
            if ($data['paid']) {
                $order_data = self::get_order_data($order);
                $referer = "<br>Is Sandbox: " . ($this->is_sandbox ? 'Yes' : 'No');
                $referer .= "<br>Bill ID: " . $data['id'];
                $referer .= "<br>Order ID: " . $order_data['id'];
                $referer .= "<br>Type: " . $data['type'];

                if ($post_meta === 'due') {
                    update_post_meta($order_id, $data['id'], 'paid');
                    $order->add_order_note('Payment Status: SUCCESSFUL ' . $referer);
                    $order->payment_complete($data['id']);
                    self::log($data['type'] . ', bills ' . $data['id'] . '. Order #' . $order_data['id'] . ' updated in WooCommerce as Paid');
                    do_action('bfw_on_payment_success_update', $order);
                } elseif ($post_meta === 'paid') {
                    self::log($data['type'] . ', bills ' . $data['id'] . '. Order #' . $order_data['id'] . ' not updated due to Duplicate');
                }
                $redirectpath = $order->get_checkout_order_received_url();
            } else {
                $order->add_order_note('Payment Status: CANCELLED BY USER' . '<br>Bill ID: ' . $data['id']);
                wc_add_notice(__('ERROR: ', 'woothemes') . $this->custom_error, 'error');
                $redirectpath = $order->get_cancel_order_url();
            }

            if ($data['type'] === 'redirect') {
                wp_redirect($redirectpath);
            } else {
                echo 'RECEIVEOK';
                exit;
            }
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page()
        {
            if ($this->instructions) {
                echo wpautop(wptexturize($this->instructions));
            }
        }

        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions($order, $sent_to_admin, $plain_text = false)
        {
            if ($this->instructions && !$sent_to_admin && 'offline' === $order->get_payment_method() && $order->has_status('on-hold')) {
                echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
            }
        }

        /**
         * Adds error message when not configured the API Secret Key.
         *
         */
        public function api_key_missing_message()
        {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf(__('<strong>Gateway Disabled</strong> You should inform your API Key in Billplz. %sClick here to configure!%s', 'bfw'), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=billplz">', '</a>') . '</p>';
            $message .= '</div>';
            echo $message;
        }

        /**
         * Adds error message when not configured the X Signature Key.
         *
         */
        public function x_signature_missing_message()
        {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf(__('<strong>Gateway Disabled</strong> You should inform your X Signature Key in Billplz. %sClick here to configure!%s', 'bfw'), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=billplz">', '</a>') . '</p>';
            $message .= '</div>';
            echo $message;
        }

        /**
         * Adds error message when not configured the Collection ID.
         *
         */
        public function collection_id_missing_message()
        {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf(__('<strong>Gateway Notice!</strong> You should inform your Collection ID in Billplz. %sClick here to configure!%s', 'bfw'), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=billplz">', '</a>') . '</p>';
            $message .= '</div>';
            echo $message;
        }
    }
}
add_action('plugins_loaded', 'bfw_load', 0);

function bfw_clear_cron()
{
    /* Removed hook that registered from previous version */
    wp_clear_scheduled_hook('billplz_bills_invalidator');
}
register_deactivation_hook(__FILE__, 'bfw_clear_cron');
add_action('upgrader_process_complete', 'bfw_clear_cron', 10, 2);

/*
 * Display Billplz Bills URL on the Order Admin Page
 */
function bfw_add_bill_id($order)
{
    $order_data = WC_Billplz_Gateway::get_order_data($order);
    $bill_id = get_post_meta($order_data['id'], '_transaction_id', true);?>
    <span class="description"><?php echo wc_help_tip(__('You may refer to Custom Fields to get more information', 'bfw')); ?> <?php echo 'Bill ID: ' . $bill_id; ?></span><?php
}
add_action('woocommerce_order_item_add_action_buttons', 'bfw_add_bill_id');

/* Delete Bills before deleting order */
function bfw_delete_order($post_id)
{

    if (defined('BFW_DISABLE_DELETE') && BFW_DISABLE_DELETE) {
        return;
    }

    $post_type = get_post_type($post_id);
    if ($post_type !== 'shop_order') {
        return;
    }

    $settings = get_option('woocommerce_billplz_settings');
    $api_key = $settings['api_key'];
    $bill_id = get_post_meta($post_id, '_transaction_id', true);
    $bill_paid = get_post_meta($post_id, $bill_id, true);

    if (empty($bill_id) || empty($api_key) || empty($bill_paid)) {
        return;
    }

    if ($bill_paid === 'paid') {
        return;
    }

    $connect = new BillplzWooCommerceWPConnect($api_key);
    $connect->setStaging($settings['is_sandbox'] == 'yes');
    $billplz = new BillplzWooCommerceAPI($connect);
    list($rheader, $rbody) = $billplz->deleteBill($bill_id);

    if ($rheader !== 200) {
        wp_die('Deleting this order has been prevented. ' . print_r($rbody, true));
    }
}
add_action('before_delete_post', 'bfw_delete_order');
