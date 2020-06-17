<?php

defined('ABSPATH') || exit;

function bfw_add_gateway($methods)
{
  $methods[] = 'WC_Billplz_Gateway';
  return $methods;
}

add_filter('woocommerce_payment_gateways', 'bfw_add_gateway');

class WC_Billplz_Gateway extends WC_Payment_Gateway
{
  public static $log_enabled = false;
  public static $log = false;

  public static $id = 'billplz';

  public function __construct()
  {
    if (!$this->is_valid_for_use()) {
      $this->enabled = 'no';
    }

    $this->id = self::$id;
    $this->method_title = __('Billplz', 'bfw');
    $this->method_description = __('Have your customers pay with Billplz.', 'bfw');
    $this->order_button_text =  __('Pay with Billplz', 'bfw');

    $bfw_icon = plugins_url('assets/billplz-logo.png', BFW_PLUGIN_FILE);
    $this->icon = apply_filters('bfw_icon', $bfw_icon);

    $this->init_form_fields();
    $this->init_settings();

    self::$log_enabled = 'yes' === $this->get_option('debug', 'no');
    $this->has_fields = 'yes' === $this->get_option('has_fields');
    $this->is_sandbox = 'yes' === $this->get_option('is_sandbox');
    $this->do_not_clear_cart = 'yes' === $this->get_option('do_not_clear_cart');
    
    $this->settings = apply_filters('bfw_settings_value', $this->settings);
    
    $this->title = $this->settings['title'];
    $this->description = $this->settings['description'];
    $this->api_key = $this->settings['api_key'];
    $this->x_signature = $this->settings['x_signature'];
    $this->collection_id = $this->settings['collection_id'];
    $this->custom_error = $this->settings['custom_error'];
    $this->reference_1_label = $this->settings['reference_1_label'];
    $this->reference_1 = $this->settings['reference_1'];
    $this->instructions = $this->settings['instructions'];

    $this->woocommerce_add_action();
    $this->initialize_api_helper();
    $this->validate_merchant_account();
  }

  public function init_form_fields()
  {
    $this->form_fields = apply_filters('bfw_form_fields', bfw_get_settings());
  }

  public function is_valid_for_use()
  {
    return in_array(
      get_woocommerce_currency(),
      apply_filters('bfw_supported_currencies', array('MYR')),
      true
    );
  }

  public static function log($message)
  {
    if (self::$log_enabled) {
      if (empty(self::$log)) {
          self::$log = new WC_Logger();
      }
      self::$log->add(self::$id, $message);
    }
  }

  private function woocommerce_add_action()
  {
    add_action('woocommerce_thankyou_billplz', array(&$this, 'thankyou_page'));
    add_action('woocommerce_email_before_order_table', array(&$this, 'email_instructions'), 10, 3);
    add_action('woocommerce_receipt_billplz', array(&$this, 'receipt_page'));
    add_action('woocommerce_update_options_payment_gateways_billplz', array(&$this, 'process_admin_options'));
    add_action('woocommerce_api_wc_billplz_gateway', array(&$this, 'check_response'));
  }

  private function initialize_api_helper(){
    global $bfw_connect, $bfw_api;
    $this->connect = $bfw_connect->set_api_key($this->api_key, $this->is_sandbox);
    $this->billplz = $bfw_api->set_connect($this->connect);
  }

  private function validate_merchant_account()
  {
    global $woocommerce_billplz;

    if (empty($this->api_key))
    {
      $warning = sprintf(__('<strong>Gateway Disabled</strong> You should set your API Key in Billplz. %sClick here to configure!%s', 'bfw'), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=billplz">', '</a>')
      $woocommerce_billplz->add_admin_notice('api_key_missing_message', 'error', $warning);
    } 

    if (empty($this->collection_id))
    {
      $warning = sprintf(__('<strong>Gateway Disabled</strong> You should set your Collection ID in Billplz. %sClick here to configure!%s', 'bfw'), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=billplz">', '</a>')
      $woocommerce_billplz->add_admin_notice('collection_id_missing_message', 'error', $warning);
    }

    if (empty($this->x_signature))
    {
      $warning = sprintf(__('<strong>Gateway Disabled</strong> You should set your XSignature Key in Billplz. %sClick here to configure!%s', 'bfw'), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=billplz">', '</a>')
      $woocommerce_billplz->add_admin_notice('x_signature_missing_message', 'error', $warning);
    }
  }

  private function fetch_billplz_payment_gateways()
  {
    if (false === ($gateways = get_transient('billplz_get_payment_gateways'))) {
      $billplz = $this->billplz;

      list($rheader, $rbody) = $billplz->toArray($billplz->getPaymentGateways());

      $gateways = array();  

      if (isset($rbody['error']) || $rheader != 200) {
        // log this error
      } else {
        $gateways = $rbody;
      }

      set_transient('billplz_get_payment_gateways', $gateways, HOUR_IN_SECONDS * 1);
    }
  }

  private function fetch_billplz_collection_payment_gateways(){
    if (false === ($collection_gateways = get_transient('billplz_get_collection_gateways'))) {
      
      $billplz = $this->billplz;
      list($rheader, $rbody) = $billplz->toArray($billplz->getPaymentMethodIndex($this->collection_id));

      $collection_gateways = array();

      if (isset($rbody['error']) || $rheader != 200) {
        // log this error
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
  }

  public function payment_fields()
  {
    $description = $this->get_description();
    if ( $description ) {
      echo wpautop( wptexturize( $description ) );
    }

    if ($this->has_fields) {
      $this->fetch_billplz_payment_gateways();
      $this->fetch_billplz_collection_payment_gateways();

      $bank_name = BillplzBankName::get();

      if (has_action('bfw_payment_fields')) {
        do_action('bfw_payment_fields', $gateways, $bank_name);
      } else {
        include BFW_PLUGIN_DIR . 'templates/payment.php';
      }
    }
  }

  private function get_order_data($order)
  {
    $order_data = array(
      'id' => $order->get_id(),
      'name'  => $order->get_display_name(),
      'email' => $order->get_email(),
      'phone' => $order->get_billing_phone(),
      'total' => (string) ($order->get_total() * 100)
    );
    
    return apply_filters('bfw_filter_order_data', $data);
  }

  private function validate_and_assign_reference_1(){
    if (isset($_POST['billplz_bank'])){
      $this->reference_1_label = 'Bank Code';
      $this->reference_1 = $_POST['billplz_bank']);
    } else {
      wc_add_notice(__('Please choose the payment method to proceed', 'bfw'), 'error');
    }
  }

  private function delete_previously_assoc_bill($bill_id)
  {
    $billplz = $this->billplz;

    if (!empty(get_post_meta($order_id, $bill_id, true))){
      delete_post_meta($order_id, $bill_id);
      $billplz->deleteBill($bill_id);
    }
  }

  private function get_order_description($order)
  {
    if (sizeof($order->get_items()) > 0) {
      foreach ($order->get_items() as $item) {
        if ($item['qty']) {
          $item_names[] = $item['name'] . ' x ' . $item['qty'];
        }
      }
    }

    $description = sprintf(__('Order %s', 'woocommerce'), $order->get_order_number()) . " - " . implode(', ', $item_names);
    return mb_substr(apply_filters('bfw_description', $description), 0, 200)
  }

  public function process_payment($order_id)
  {
    if ($this->has_fields){
      $this->validate_and_assign_reference_1();
    }

    $order = wc_get_order( $order_id );
    
    if (!empty($bill_id = $order->get_transaction_id())) {
      $this->delete_previously_assoc_bill($bill_id);
    }

    if (!$this->do_not_clear_cart) {
      WC()->cart->empty_cart();
    }

    $order_data = $this->get_order_data($order);

    $parameter = array(
      'collection_id' => $this->collection_id,
      'email' => $order_data['email'],
      'mobile' => trim($order_data['phone']),
      'name' => empty($order_data['name']) ? 'No Name' : $order_data['name'],
      'amount' => $order_data['total'],
      'callback_url' => add_query_arg(array('order' => $order_data['id']), WC()->api_request_url(get_class($this))),
      'description' => $this->get_order_description($order)
    );

    $optional = array(
      'redirect_url' => $parameter['callback_url'],
      'reference_1_label' => mb_substr($this->reference_1_label, 0, 20),
      'reference_1' => mb_substr($this->reference_1, 0, 120),
      'reference_2_label' => 'Order ID',
      'reference_2' => $order_data['id'],
    );

    self::log('Creating bill for order number #' . $order_data['id']);

    $billplz = $this->billplz;

    list($rheader, $rbody) = $billplz->toArray($billplz->createBill($parameter, $optional));

    if ($rheader !== 200) {
      self::log('Error Creating bill for order number #' . $order_data['id'] . print_r($rbody, true));
      wc_add_notice(__('ERROR: ', 'bfw') . print_r($rbody, true), 'error');
      
     return array(
        'result' => 'failed',
        'redirect' => null,
      );
    }

    self::log('Bill ID ' . $rbody['id'] . ' created for order number #' . $order_data['id']);

    update_post_meta($order_id, $rbody['id'], 'due');
    update_post_meta($order_id, '_transaction_id', $rbody['id']);

    if ($this->has_fields){
      $rbody['url'] .= '?auto_submit=true';
    }
    
    return array(
      'result' => 'success',
      'redirect' => $rbody['url'],
    );
  }

  private function complete_payment_process($data)
  {
    $order_id = sanitize_text_field($_GET['order']);
    $post_meta = get_post_meta($order_id, $data['id'], true);

    if (empty($post_meta)) {
      status_header(404);
      self::log('Order not found for Bill ID: ' . $data['id']);
      exit('Order not found');
    }

    self::log('Billplz response ' . print_r($data, true));
    $order = wc_get_order($order_id);

    $order_data = $this->get_order_data($order);

    $referer = "<br>Is Sandbox: " . ($this->is_sandbox ? 'Yes' : 'No');
    $referer .= "<br>Bill ID: " . $data['id'];
    $referer .= "<br>Order ID: " . $order_data['id'];
    $referer .= "<br>Type: " . $data['type'];

    if ($post_meta === 'due') {
      update_post_meta($order_id, $data['id'], 'paid');
      $order->add_order_note('Payment Status: SUCCESSFUL ' . $referer);
      $order->payment_complete($data['id']);
      self::log('Order #' . $order_data['id'] . ' updated in WooCommerce as Paid for Bill ID: ' . $data['id']);
      do_action('bfw_on_payment_success_update', $order);
    }
      
  }

  public function check_response()
  {
      @ob_clean();

      try {
          $data = BillplzWooCommerceWPConnect::getXSignature($this->x_signature);
      } catch (Exception $e) {
          status_header(403);
          self::log('Failed X Signature Validation. Information: '.print_r($_REQUEST, true));
          exit('Failed X Signature Validation');
      }

      if (!$data['paid']){
        if ($data['type'] === 'redirect') {
          wp_redirect($order->get_cancel_order_url());
        }
        exit;
      }

      $this->complete_payment_process();

      if ($data['type'] === 'redirect') {
        wp_redirect($order->get_checkout_order_received_url());
      }
      
      exit;
  }

  public function process_admin_options()
  {
    // Reset payment gateway information
    delete_transient('billplz_get_payment_gateways');
    delete_transient('billplz_get_collection_gateways');

    // Get a collection and check the details
    // Set transient if incorrect
    // Remove transient if correct
    parent::process_admin_options();
  }

  public function thankyou_page()
  {
    if ($this->instructions) {
      echo wpautop(wptexturize($this->instructions));
    }
  }

  public function email_instructions($order, $sent_to_admin, $plain_text = false)
  {
    if ($this->instructions && !$sent_to_admin && 'billplz' === $order->get_payment_method() && $order->has_status('on-hold')) {
        echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
    }
  }
}