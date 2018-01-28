<?php

/**
 * Plugin Name: Billplz for WooCommerce
 * Plugin URI: https://wordpress.org/plugins-wp/billplz-for-woocommerce/
 * Description: Billplz Payment Gateway | Accept Payment using all participating FPX Banking Channels. <a href="https://www.billplz.com/join/8ant7x743awpuaqcxtqufg" target="_blank">Sign up Now</a>.
 * Author: Wan @ Billplz
 * Author URI: http://fb.com/billplzplugin
 * Version: 3.18
 * Requires PHP: 5.6
 * License: GPLv3
 * Text Domain: wcbillplz
 * Domain Path: /languages/
 * WC requires at least: 3.0
 * WC tested up to: 3.2.6
 */
/*
 * Remove databaser record on uninstallation
 */

function billplz_for_wooocommerce_plugin_uninstall()
{
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'billplz_fwoo_%'");
}
register_uninstall_hook(__FILE__, 'billplz_for_wooocommerce_plugin_uninstall');

/*
 *  Add settings link on plugin page
 */

function billplz_for_woocommerce_plugin_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=billplz">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'billplz_for_woocommerce_plugin_settings_link');

function wcbillplz_woocommerce_fallback_notice()
{
    $message = '<div class="error">';
    $message .= '<p>' . __('WooCommerce Billplz Gateway depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work!', 'wcbillplz') . '</p>';
    $message .= '</div>';
    echo $message;
}
// Load the function
add_action('plugins_loaded', 'wcbillplz_gateway_load', 0);

/**
 * Load Billplz gateway plugin function
 * 
 * @return mixed
 */
function wcbillplz_gateway_load()
{
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'wcbillplz_woocommerce_fallback_notice');
        return;
    }
    // Load language
    load_plugin_textdomain('wcbillplz', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    add_filter('woocommerce_payment_gateways', 'wcbillplz_add_gateway');

    /**
     * Add Billplz gateway to ensure WooCommerce can load it
     * 
     * @param array $methods
     * @return array
     */
    function wcbillplz_add_gateway($methods)
    {
        $methods[] = 'WC_Billplz_Gateway';
        return $methods;
    }

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
        private $notification;
        private $api_key;
        private $collection_id;
        private $clearcart;
        public $custom_error;
        public $title;
        public $description;
        private $x_signature;

        /**
         * Construct the Billplz gateway class
         * 
         * @global mixed $woocommerce
         */
        public function __construct()
        {
            //global $woocommerce;

            $this->id = 'billplz';
            $this->icon = plugins_url('assets/billplz.gif', __FILE__);
            $this->has_fields = false;
            $this->method_title = __('Billplz', 'wcbillplz');
            $this->debug = 'yes' === $this->get_option('debug', 'no');
            $this->order_button_text = __('Proceed to Billplz', 'woocommerce');

            // Load the form fields.
            $this->init_form_fields();
            // Load the settings.
            $this->init_settings();

            // Define user setting variables.
            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];
            $this->api_key = $this->settings['api_key'];
            $this->collection_id = $this->settings['collection_id'];
            $this->clearcart = $this->settings['clearcart'];
            $this->notification = $this->settings['notification'];
            $this->x_signature = $this->settings['x_signature'];
            $this->custom_error = $this->settings['custom_error'];

            // Payment instruction after payment
            $this->instructions = isset($this->settings['instructions']) ? $this->settings['instructions'] : '';

            add_action('woocommerce_thankyou_billplz', array($this, 'thankyou_page'));
            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);

            self::$log_enabled = $this->debug;

            add_action('woocommerce_receipt_billplz', array(
                &$this,
                'receipt_page'
            ));
            // Save setting configuration
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'
            ));
            // Payment listener/API hook
            add_action('woocommerce_api_wc_billplz_gateway', array(
                $this,
                'check_ipn_response'
            ));
            // Checking if api_key is not empty.
            $this->api_key == '' ? add_action('admin_notices', array(
                        &$this,
                        'api_key_missing_message'
                    )) : '';
            // Checking if x_signature is not empty.
            $this->x_signature == '' ? add_action('admin_notices', array(
                        &$this,
                        'x_signature_missing_message'
                    )) : '';
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
                _e('Billplz Payment Gateway', 'wcbillplz');

                ?></h3>
            <p><?php
                _e('Billplz Payment Gateway works by sending the user to Billplz for payment. ', 'wcbillplz');

                ?></p>
            <p><?php
                _e('To immediately reduce stock on add to cart, we strongly recommend you to use this plugin. ', 'wcbillplz');

                ?><a href="http://bit.ly/1UDOQKi" target="_blank"><?php
                    _e('WooCommerce Cart Stock Reducer', 'wcbillplz');

                    ?></a></p>
            <p><?php
                _e('WP Cron are implemented by this plugin to manage Bills. ', 'wcbillplz');

                ?><a href="https://wordpress.org/plugins-wp/wp-crontrol/" target="_blank"><?php
                    _e('Check what inside WP-Cron', 'wcbillplz');

                    ?></a></p>
            <table class="form-table">
                <?php
                $this->generate_settings_html();

                ?>
            </table><!--/.form-table-->
            <?php
        }

        /**
         * Gateway Settings Form Fields.
         * 
         */
        public function init_form_fields()
        {
            $this->form_fields = include(__DIR__ . '/includes/settings-billplz.php');
        }

        /**
         * 
         * @return array string
         */
        private static function get_order_data($order)
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
                    'first_name' => !empty($order->get_billing_first_name()) ? $order->get_billing_first_name() : $order->get_shipping_first_name(),
                    'last_name' => !empty($order->get_billing_last_name()) ? $order->get_billing_last_name() : $order->get_shipping_last_name(),
                    'email' => $order->get_billing_email(),
                    'phone' => $order->get_billing_phone(),
                    'total' => $order->get_total(),
                    'id' => $order->get_id(),
                );
            }

            $data['name'] = $data['first_name'] . ' ' . $data['last_name'];

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
         * Create bills function
         * Save to database
         * 
         * @return string Return URL
         */
        protected function create_bill($order, $order_data)
        {
            require_once(__DIR__ . '/includes/billplz.php');
            $deliver = $this->notification;

            /**
             * Generate Description for Bills
             */
            if (sizeof($order->get_items()) > 0)
                foreach ($order->get_items() as $item)
                    if ($item['qty'])
                        $item_names[] = $item['name'] . ' x ' . $item['qty'];
            $desc = sprintf(__('Order %s', 'woocommerce'), $order->get_order_number()) . " - " . implode(', ', $item_names);

            $obj = new Billplz($this->api_key);

            $obj->setCollection($this->collection_id)
                ->setName($order_data['name'])
                ->setAmount($order_data['total'])
                ->setDeliver($deliver)
                ->setMobile($order_data['phone'])
                ->setEmail($order_data['email'])
                ->setDescription($desc)
                ->setReference_1($order_data['id'])
                ->setReference_1_Label('Order ID')
                ->setPassbackURL(home_url('/?wc-api=WC_Billplz_Gateway'), home_url('/?wc-api=WC_Billplz_Gateway'))
                ->create_bill(true);

            // Log the bills creation
            self::log('Creating bills ' . $obj->getID() . ' for order number #' . $order_data['id']);
            return array(
                'url' => $obj->getURL(),
                'id' => $obj->getID(),
            );
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
            $html = '<p>' . __('An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'wcbillplz') . '</p>';
            $html .= '<a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Click to try again', 'wcbillplz') . '</a>';
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
            if ($this->clearcart === 'yes')
                $woocommerce->cart->empty_cart();
            /*
             * If don't want to use global:
             * WC()->cart->empty_cart();
             */

            $order = new WC_Order($order_id);
            $order_data = self::get_order_data($order);

            $md5 = substr(md5(strtolower($order_data['name'] . $order_data['email'] . $order_data['phone'])), 0, 6);

            /*
             * If previously the order has created Bills, use it if details are same
             * Otherwise, set it to clutter and create new bills
             * If previously the order has not created any Bills, create new bills
             */
            $bills_before = get_option('billplz_fwoo_order_id_' . $order_id, false);
            $data_before = get_option('billplz_fwoo_order_id_data_' . $order_id, false);
            if ($bills_before) {
                if ($data_before === $md5) {
                    $bills['id'] = $bills_before;
                    $bills['url'] = get_option('billplz_fwoo_order_id_url_' . $order_id, false);
                } else {
                    $bills = $this->create_bill($order, $order_data);
                    update_option('billplz_fwoo_clutter_' . $bills_before, $order_id, false);
                }
            }else {
                $bills = $this->create_bill($order, $order_data);
            }
            
            /*
             * Save to Database for cleaning later
             */
            update_option('billplz_fwoo_order_id_' . $order_id, $bills['id'], false);
            update_option('billplz_fwoo_order_id_url_' . $order_id, $bills['url'], false);
            update_option('billplz_fwoo_order_id_data_' . $order_id, $md5, false);

            return array(
                'result' => 'success',
                'redirect' => $bills['url']
            );
        }

        /**
         * Check for Billplz Response
         *
         * @access public
         * @return void
         */
        function check_ipn_response()
        {
            @ob_clean();
            //global $woocommerce;

            require_once(__DIR__ . '/includes/billplz.php');
            if (isset($_POST['id'])) {
                $signal = 'Callback';
                $data = Billplz::getCallbackData($this->x_signature);
                sleep(10);
            } elseif (isset($_GET['billplz']['id'])) {
                $signal = 'Return';
                $data = Billplz::getRedirectData($this->x_signature);
            } else {
                exit('Nothing to do');
            }

            // Log return type
            self::log('Response is: ' . $signal . ' ' . print_r($_REQUEST, true));

            $bill_id = $data['id'];

            $billplz = new Billplz($this->api_key);
            $moreData = $billplz->check_bill($bill_id);
            self::log('Bills Re-Query: ' . print_r($data, true));

            $order_id = $moreData['reference_1'];

            $order = new WC_Order($order_id);

            if ($moreData['paid']) {
                $this->save_payment($order, $moreData, $signal);
                $redirectpath = $order->get_checkout_order_received_url();
            } else {
                $order->add_order_note('Payment Status: CANCELLED BY USER' . '<br>Bill ID: ' . $data['id']);
                wc_add_notice(__('ERROR: ', 'woothemes') . $this->custom_error, 'error');
                $redirectpath = $order->get_cancel_order_url();
            }

            if ($signal === 'Return')
                wp_redirect($redirectpath);
            else
                echo 'RECEIVEOK';
        }

        /**
         * Save payment status to DB for successful return/callback
         */
        private function save_payment($order, $bill, $type)
        {
            $order_data = self::get_order_data($order);

            $referer = "<br>Receipt URL: " . "<a href='" . urldecode($bill['url']) . "' target=_blank>" . urldecode($bill['url']) . "</a>";
            $referer .= "<br>Order ID: " . $order_data['id'];
            $referer .= "<br>Collection ID: " . $bill['collection_id'];
            $referer .= "<br>Type: " . $type;

            $bill_id = get_post_meta($order_data['id'], '_transaction_id', true);

            if (empty($bill_id)) {
                $order->add_order_note('Payment Status: SUCCESSFUL' . '<br>Bill ID: ' . $bill['id'] . ' ' . $referer);
                $order->payment_complete($bill['id']);
                self::log($type . ', bills ' . $bill['id'] . '. Order #' . $order_data['id'] . ' updated in WooCommerce as Paid');
            } elseif ($bill_id === $bill['id']) {
                self::log($type . ', bills ' . $bill['id'] . '. Order #' . $order_data['id'] . ' not updated due to Duplicate');
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
         * Adds error message when not configured the app_key.
         * 
         */
        public function api_key_missing_message()
        {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf(__('<strong>Gateway Disabled</strong> You should inform your API Key in Billplz. %sClick here to configure!%s', 'wcbillplz'), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=billplz">', '</a>') . '</p>';
            $message .= '</div>';
            echo $message;
        }

        /**
         * Adds error message when not configured the app_secret.
         * 
         */
        public function x_signature_missing_message()
        {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf(__('<strong>Gateway Disabled</strong> You should inform your X Signature Key in Billplz. %sClick here to configure!%s', 'wcbillplz'), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=billplz">', '</a>') . '</p>';
            $message .= '</div>';
            echo $message;
        }

        public static function order_maintainance($order_id, $status_from, $status_to, $classobject)
        {
            self::log('Hook for changing order status running for order id ' . $order_id);
            $statuses = array('cancelled', 'refunded', 'failed');

            foreach ($statuses as $status) {
                if ($status_to === $status) {
                    $bill_id = get_option('billplz_fwoo_order_id_' . $order_id, false);
                    if ($bill_id) {

                        require_once(__DIR__ . '/includes/billplz.php');
                        $gateway = maybe_unserialize(get_option('woocommerce_billplz_settings', false));
                        $billplz = new Billplz($gateway['api_key']);
                        self::log('Bills removed from billplz fwoo order id');
                        if ($billplz->deleteBill($bill_id)) {
                            delete_option('billplz_fwoo_order_id_' . $order_id);
                        }
                    }
                    break;
                }
            }
        }
        /*
         * Add bills ID in order admin page
         */

        public static function add_bills_id($order)
        {
            $order_data = self::get_order_data($order);
            $bill_id = get_option('billplz_fwoo_order_id_' . $order_data['id'], false);
            if ($bill_id) {
                require_once(__DIR__ . '/includes/billplz.php');
                $gatewayParam = maybe_unserialize(get_option('woocommerce_billplz_settings', false));
                $api_key = $gatewayParam['api_key'];
                $billplz = new Billplz($api_key);
                $data = $billplz->check_bill($bill_id);

                ?>
                <span class="description"><?php echo wc_help_tip(__('This order has a Billplz Bills created by the order on user checkout', 'woocommerce')); ?> <?php _e('Bill URL: ' . $data['url'], 'woocommerce'); ?></span><?php
            }
        }

        public static function delete_order($order_id)
        {
            global $post_type;

            if ($post_type !== 'shop_order') {
                return;
            }

            $bill_id = get_option('billplz_fwoo_order_id_' . $order_id, false);

            $order = new WC_Order($order_id);
            $gatewayParam = maybe_unserialize(get_option('woocommerce_billplz_settings', false));
            $api_key = $gatewayParam['api_key'];
            require_once(__DIR__ . '/includes/billplz.php');
            $billplz = new Billplz($api_key);
            delete_option('billplz_fwoo_order_id_' . $order_id);
            if (!$billplz->deleteBill($bill_id)) {
                update_option('billplz_fwoo_clutter_' . $bill_id, $order_id, false);
            }
        }
    }

}
/*
 * If the order status is changed, try to delet Bills
 */
add_action('woocommerce_order_status_changed', array('WC_Billplz_Gateway', 'order_maintainance'), 10, 4);

/*
 * Display Billplz Bills URL on the Order Admin Page
 */
add_action('woocommerce_order_item_add_action_buttons', array('WC_Billplz_Gateway', 'add_bills_id'));

/*
 * Delete Bills before deleting order
 */
add_action('before_delete_post', array('WC_Billplz_Gateway', 'delete_order'), 10, 1);
add_action('wp_trash_post', array('WC_Billplz_Gateway', 'delete_order'));

require __DIR__ . '/includes/invalidatebills.php';

add_action('billplz_bills_invalidator', 'wcbillplz_delete_bills');
register_activation_hook(__FILE__, 'wcbillplz_activate_cron');
register_deactivation_hook(__FILE__, 'wcbillplz_deactivate_cron');
