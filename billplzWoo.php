<?php

/**
 * Plugin Name: Billplz for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/billplz-for-woocommerce/
 * Description: Billplz Payment Gateway | <a href="https://www.billplz.com/enterprise/signup" target="_blank">Sign up Now</a>.
 * Author: Billplz Sdn. Bhd.
 * Author URI: http://github.com/billplz/billplz-for-woocommerce
 * Version: 3.21.6
 * Requires PHP: 5.2.4
 * Requires at least: 4.6
 * License: GPLv3
 * Text Domain: bfw
 * Domain Path: /languages/
 * WC requires at least: 3.0
 * WC tested up to: 3.4.5
 */

/* Load Billplz Class */
if (!class_exists('BillplzWooCommerceAPI') && !class_exists('BillplzWooCommerceWPConnect')) {
    require('includes/Billplz_API.php');
    require('includes/Billplz_WPConnect.php');
}

/* Load Requery Bill Module */
require 'includes/RequeryBill.php';

/* Load Bank Name List */
require 'includes/Billplz_BankName.php';

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
$plugin_action_link = 'plugin_action_links_'.plugin_basename(__FILE__);
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
            //global $woocommerce;

            $this->id = 'billplz';
            $this->icon = apply_filters('bfw_icon', plugins_url('assets/billplz.gif', __FILE__));
            $this->method_title = __('Billplz', 'bfw');
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

            $this->api_key = $this->settings['api_key'];
            $this->x_signature = $this->settings['x_signature'];
            $this->collection_id = $this->settings['collection_id'];
            $this->clearcart = $this->settings['clearcart'];
            $this->notification = $this->settings['notification'];
            $this->custom_error = $this->settings['custom_error'];

            $this->reference_1_label = $this->settings['reference_1_label'];
            $this->reference_1 = $this->settings['reference_1'];

            /* Enable Premium Features */
            $this->has_fields = $this->settings['has_fields'];
            if (isset($this->has_fields) && $this->has_fields === 'yes') {
                $this->notification = '0';
                add_filter('bfw_url', array($this, 'url'));
            }

            // Payment instruction after payment
            $this->instructions = $this->settings['instructions'];

            add_action('woocommerce_thankyou_billplz', array($this, 'thankyou_page'));
            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);

            self::$log_enabled = $this->debug;

            /* Set Receipt Page */
            add_action('woocommerce_receipt_billplz', array(&$this,'receipt_page'));

            // Save setting configuration
            add_action('woocommerce_update_options_payment_gateways_billplz', array($this,'process_admin_options'));

            // Payment listener/API hook
            add_action('woocommerce_api_wc_billplz_gateway', array($this,'check_ipn_response'));

            /* Display error if API Key is not set */
            $this->api_key == '' ? add_action('admin_notices', array(&$this,'api_key_missing_message')) : '';

            /* Display error if X Signature Key is not set */
            $this->x_signature == '' ? add_action('admin_notices', array(&$this,'x_signature_missing_message')) : '';

            /* Display warning if Collection ID is not set */
            $this->collection_id == '' ? add_action('admin_notices', array(&$this,'collection_id_missing_message')) : '';
        }

        public static function settings_value($settings)
        {
            if (isset($settings['has_fields']) && $settings['has_fields'] === 'yes') {
                $settings['reference_1_label'] = 'Bank Code';
                if (isset($_POST['billplz_bank'])) {
                    $bank_name = BillplzBankName::get();
                    if (isset($bank_name[$_POST['billplz_bank']]) && $_POST['billplz_bank'] !== 'OTHERS') {
                        $settings['reference_1'] = $_POST['billplz_bank'];
                    } else {
                        $settings['reference_1'] = '';
                    }
                }
            }
            return $settings;
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
            if (!in_array(get_woocommerce_currency(), array(
                    'MYR'
                ))) {
                return false;
            }
            return true;
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis.
         *
         */
        public function admin_options()
        {
            ?>
            <h3><?php
                _e('Billplz Payment Gateway', 'bfw'); ?></h3>
            <p><?php
                _e('Billplz Payment Gateway works by sending the user to Billplz for payment. ', 'bfw'); ?></p>
            <p><?php
                _e('To immediately reduce stock on add to cart, we strongly recommend you to use this plugin. ', 'bfw'); ?><a href="http://bit.ly/1UDOQKi" target="_blank">
                    WooCommerce Cart Stock Reducer</a></p>
            <p><?php
                _e('You may do a bill requery in-case order is not updated. ', 'bfw'); ?><a href="options-general.php?page=bfw-requery-tool" target="_blank">
                    BFW Tool</a></p>
            <table class="form-table">
                <?php
                    $this->generate_settings_html(); ?>
            </table><!--/.form-table-->
            <?php
        }

        /**
         * Gateway Settings Form Fields.
         *
         */
        public function init_form_fields()
        {
            $this->form_fields = include('includes/settings-billplz.php');
        }

        public function payment_fields()
        {
            if ($description = $this->get_description()) {
                echo wpautop(wptexturize($description));
            }
            if (isset($this->has_fields) && $this->has_fields === 'yes') {
                $rbody = get_option('billplz_fpx_banks');
                $date = get_option('billplz_fpx_banks_last');

                if (!$rbody || ($date !== date('d/m/Y/H'))) {
                    $connect = new BillplzWooCommerceWPConnect($this->api_key);
                    $connect->detectMode();
                    $billplz = new BillplzWooCommerceAPI($connect);
                    list($rheader, $rbody) = $billplz->toArray($billplz->getFpxBanks());

                    if (isset($rbody['error'])) {
                        $rbody = array();
                    }

                    update_option('billplz_fpx_banks', $rbody);
                    update_option('billplz_fpx_banks_last', date('d/m/Y/H'));
                }
            
                $bank_name = apply_filters('bfw_bank_name_list', BillplzBankName::get());
                
                /* Allow theme/plugin to override the way form is represented */
                if (has_action('bfw_payment_fields')) :
                    do_action('bfw_payment_fields', $rbody, $bank_name);
                else :
                    ?>
                <p class="form-row validate-required">
                    <label><?php echo 'Choose Bank'; ?> <span class="required">*</span></label>
                    <select name="billplz_bank">
                        <option value="" disabled selected>Choose your bank</option>
                    <?php
                    foreach ($bank_name as $key => $value) {
                        if (empty($rbody)) {
                            break;
                        }
                        foreach ($rbody['banks'] as $bank) {
                            if ($bank['name'] === $key && $bank['active']) {
                                ?><option value="<?php echo $bank['name']; ?>"><?php echo $bank_name[$bank['name']] ? strtoupper($bank_name[$bank['name']]) : $bank['name']; ?></option><?php
                            }
                        }
                    }
                    ?>
                    <option value="OTHERS">OTHERS</option>
                    </select>
                </p> 
                    <?php
                endif;
            }
        }

        /**
         * This to maintain compatibility with WooCommerce 2.x
         * @return array string
         */
        public static function get_order_data($order)
        {
            global $woocommerce;
            if (version_compare($woocommerce->version, '3.0', "<")) {
                $data = array(
                    'first_name' => !empty($order->billing_first_name) ? $order->billing_first_name : $order->shipping_first_name,
                    'last_name' => !empty($order->billing_last_name) ? $order->billing_last_name : $order->shipping_last_name,
                    'email' => $order->billing_email,
                    'phone' => $order->billing_phone,
                    'total' => $order->order_total,
                    'id' => $order->id,
                );
            } else {
                $data = array(
                    'first_name' => $order->get_billing_first_name(),
                    'last_name' => $order->get_billing_last_name(),
                    'email' => $order->get_billing_email(),
                    'phone' => $order->get_billing_phone(),
                    'total' => $order->get_total(),
                    'id' => $order->get_id(),
                );
                $data['first_name'] = empty($data['first_name']) ? $order->get_shipping_first_name() : $data['first_name'];
                $data['last_name'] = empty($data['last_name']) ? $order->get_shipping_last_name() : $data['last_name'];
            }

            $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);

            /*
             * Compatibility with some themes
             */
            $data['email'] = !empty($data['email']) ? $data['email'] : $order->get_meta('_shipping_email');
            $data['email'] = !empty($data['email']) ? $data['email'] : $order->get_meta('shipping_email');
            $data['phone'] = !empty($data['phone']) ? $data['phone'] : $order->get_meta('_shipping_phone');
            $data['phone'] = !empty($data['phone']) ? $data['phone'] : $order->get_meta('shipping_phone');

            return $data;
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
            global $woocommerce;

            if (!isset($_POST['billplz_bank']) && $this->has_fields === 'yes') {
                wc_add_notice(__('Please choose your bank to proceed', 'bfw'), 'error');
                return;
            }

            /* Redirect to Bill Payment Page if has been created */
            $bill_id = get_post_meta($order_id, '_transaction_id', true);

            /* Check if the Bill is already deleted.
             * Bill can be deleted via Billplz website too.
            **/

            self::log('Connecting to Billplz API for order id #' . $order_id);
            $connect = new BillplzWooCommerceWPConnect($this->api_key);
            $connect->detectMode();
            $billplz = new BillplzWooCommerceAPI($connect);

            $shouldCreateBill = true;

            if (!empty($bill_id)) {
                list($rheader, $rbody) = $billplz->toArray($billplz->getBill($bill_id));
                if ($rbody['state'] !== 'hidden') {
                    $shouldCreateBill = false;
                }
                if (isset($_POST['billplz_bank']) && $rbody['reference_1'] !== $_POST['billplz_bank'] && !empty($rbody['reference_1'])) {
                    $shouldCreateBill = true;
                }
            }

            if (!$shouldCreateBill) {
                return array(
                    'result' => 'success',
                    'redirect' => apply_filters('bfw_url', $rbody['url'])
                );
            }

            if ($this->clearcart === 'yes') {
                /* WC()->cart->empty_cart(); */
                $woocommerce->cart->empty_cart();
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
                'mobile'=> trim($order_data['phone']),
                'name' => empty($order_data['name']) ? $order_data['email']: $order_data['name'],
                'amount' => strval($order_data['total'] * 100),
                'callback_url' => home_url('/?wc-api=WC_Billplz_Gateway'),
                'description' => mb_substr(apply_filters('bfw_description', $description), 0, 199)
            );

            $optional = array(
                'redirect_url' => home_url('/?wc-api=WC_Billplz_Gateway'),
                'reference_1_label' => mb_substr($this->reference_1_label, 0, 19),
                'reference_1' => mb_substr($this->reference_1, 0, 119),
                'reference_2_label' => 'Order ID',
                'reference_2' => $order_data['id']
            );

            self::log('Creating bill for order number #' . $order_data['id']);

            list($rheader, $rbody) = $billplz->toArray($billplz->createBill($parameter, $optional, $this->notification));

            if ($rheader !== 200) {
                self::log('Error Creating bill for order number #' . $order_data['id'] . print_r($rbody, true));
                wc_add_notice(__('ERROR: ', 'woothemes') . print_r($rbody, true), 'error');
                return;
            }

            self::log('Bill ID '.$rbody['id'].' created for order number #' . $order_data['id']);

            if (! add_post_meta($order_id, 'billplz_api_key', $this->api_key, true)) {
                update_post_meta($order_id, 'billplz_api_key', $this->api_key);
            }
            if (! add_post_meta($order_id, 'billplz_paid', 'false', true)) {
                update_post_meta($order_id, 'billplz_paid', 'false');
            }
            if (! add_post_meta($order_id, '_transaction_id', $rbody['id'], true)) {
                update_post_meta($order_id, '_transaction_id', $rbody['id']);
            }

            return array(
                'result' => 'success',
                'redirect' => apply_filters('bfw_url', $rbody['url'])
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
            //global $woocommerce;

            $data = BillplzWooCommerceWPConnect::getXSignature($this->x_signature);

            // Log return type
            self::log('Billplz response '. print_r($data, true));

            $connect = new BillplzWooCommerceWPConnect($this->api_key);
            $connect->detectMode();
            $billplz = new BillplzWooCommerceAPI($connect);
            list($rheader, $rbody) = $billplz->toArray($billplz->getBill($data['id']));

            $order_id = $rbody['reference_2'];
            $order = new WC_Order($order_id);
            $order = apply_filters('bfw_filter_order', $order, $rbody);
            if ($rbody['paid']) {
                $order_data = self::get_order_data($order);

                $referer = "<br>Receipt URL: " . "<a href='" . urldecode($rbody['url']) . "' target=_blank>" . urldecode($rbody['url']) . "</a>";
                $referer .= "<br>Order ID: " . $order_data['id'];
                $referer .= "<br>Collection ID: " . $rbody['collection_id'];
                $referer .= "<br>Type: " . $data['type'];

                $bill_paid = get_post_meta($order_data['id'], 'billplz_paid', true);

                if ($bill_paid === 'false') {
                    update_post_meta($order_id, 'billplz_paid', 'true');
                    $order->add_order_note('Payment Status: SUCCESSFUL' . '<br>Bill ID: ' . $rbody['id'] . ' ' . $referer);
                    $order->payment_complete($rbody['id']);
                    self::log($data['type'] . ', bills ' . $rbody['id'] . '. Order #' . $order_data['id'] . ' updated in WooCommerce as Paid');
                    do_action('bfw_on_payment_success_update', $order);
                } elseif ($bill_paid === 'true') {
                    self::log($data['type'] . ', bills ' . $rbody['id'] . '. Order #' . $order_data['id'] . ' not updated due to Duplicate');
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
    //global $post_type;

    if (defined('BFW_DISABLE_DELETE') && BFW_DISABLE_DELETE) {
        return;
    }

    $post_type = get_post_type($post_id);
    if ($post_type !== 'shop_order') {
        return;
    }

    $bill_id = get_post_meta($post_id, '_transaction_id', true);
    $api_key = get_post_meta($post_id, 'billplz_api_key', true);
    $bill_paid = get_post_meta($post_id, 'billplz_paid', true);

    if (empty($bill_id) || empty($api_key) || empty($bill_paid)) {
        return;
    }

    if ($bill_paid === 'true') {
        return;
    }

    $connect = new BillplzWooCommerceWPConnect($api_key);
    $connect->detectMode();
    $billplz = new BillplzWooCommerceAPI($connect);
    list($rheader, $rbody) = $billplz->deleteBill($bill_id);

    if ($rheader !== 200) {
        wp_die('Deleting this order has been prevented. '.print_r($rbody, true));
    }
}
add_action('before_delete_post', 'bfw_delete_order');
//add_action('wp_trash_post', 'bfw_delete_order');
