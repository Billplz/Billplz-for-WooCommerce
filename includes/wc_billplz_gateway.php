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

  public static $gateway_id = 'billplz';

  public function __construct()
  {
    $this->id = self::$gateway_id;
    $this->method_title = __('Billplz', 'bfw');
    $this->method_description = __('Have your customers pay with Billplz.', 'bfw');
    $this->order_button_text =  __('Pay with Billplz', 'bfw');

    if (is_admin()){
      $this->init_form_fields();
    }

    $this->init_settings();

    self::$log_enabled = 'yes' === $this->get_option('debug', 'no');
    $this->has_fields = 'yes' === $this->get_option('has_fields');
    $this->do_not_clear_cart = 'yes' === $this->get_option('do_not_clear_cart');
    
    $this->settings = apply_filters('bfw_settings_value', $this->settings);
    
    $this->title = $this->settings['title'];
    $this->description = $this->settings['description'];
    $this->is_sandbox = 'yes' === $this->get_option('is_sandbox');
    $this->api_key = $this->get_option('api_key');
    $this->x_signature = $this->get_option('x_signature');
    $this->collection_id = $this->get_option('collection_id');
    $this->reference_1_label = $this->settings['reference_1_label'];
    $this->reference_1 = $this->settings['reference_1'];
    $this->instructions = $this->settings['instructions'];

    $this->twoctwop_boost = 'yes' === $this->get_option('2c2p_boost');
    $this->twoctwop_tng = 'yes' === $this->get_option('2c2p_tng');
    $this->twoctwop_grabpay = 'yes' === $this->get_option('2c2p_grabpay');

    if (!$this->is_valid_for_use()) {
      $this->enabled = 'no';
    }

    $this->load_icon();
    $this->woocommerce_add_action();
    $this->initialize_api_helper();
  }

  public function init_form_fields()
  {
    $this->form_fields = apply_filters('bfw_form_fields', bfw_get_settings());
  }

  public function is_valid_for_use()
  {
    $currencies_presence = $this->validate_currencies_presence();
    if ($keys_presence = $this->validate_keys_presence()){
      $keys_verified = $this->check_keys_verification();
    }

    return $currencies_presence && $keys_presence && $keys_verified;
  }

  public static function log($message)
  {
    if (self::$log_enabled) {
      if (empty(self::$log)) {
          self::$log = new WC_Logger();
      }
      self::$log->add(self::$gateway_id, $message);
    }
  }

  private function load_icon()
  {
    $logo = $this->get_option('display_logo', 'fpx');
    $bfw_icon = plugins_url("assets/billplz-logo-$logo.png", BFW_PLUGIN_FILE);
    $this->icon = apply_filters('bfw_icon', $bfw_icon);
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
    $bfw_connect->set_api_key($this->api_key, $this->is_sandbox);
    $this->connect = &$bfw_connect;
    
    $bfw_api->set_connect($this->connect);
    $this->billplz = &$bfw_api;
  }

  private function validate_currencies_presence()
  {
    if (!in_array(get_woocommerce_currency(),
      apply_filters('bfw_supported_currencies', array('MYR')),
      true)){

      add_action('admin_notices', array(
        &$this, 'unsupported_currency_notice')
      );

      return false;
    }
    return true;
  }

  public function unsupported_currency_notice(){
    $message = '<div class="error">';
    $message .= '<p>' . sprintf("<strong>Billplz Disabled</strong> WooCommerce currency option is not supported by Billplz. %sClick here to configure%s", '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=general">', '</a>') . '</p>';
    $message .= '</div>';  

    echo $message;
  }

  private function validate_keys_presence()
  {
    $valid = true;
    if (empty($this->api_key))
    {
      add_action('admin_notices', array(
        &$this, 
        'api_key_missing_message')
      );
      $valid = false;
    } 

    if (empty($this->collection_id))
    {
      add_action('admin_notices', array(
        &$this, 
        'collection_id_missing_message')
      );
      $valid = false;
    }

    if (empty($this->x_signature))
    {
      add_action('admin_notices', array(
        &$this, 
        'xsignature_key_missing_message')
      );
      $valid = false;
    }

    return $valid;
  }

  private function check_keys_verification()
  {
    $api_key_state = get_option('bfw_api_key_state', 'verified');
    $collection_id_state = get_option('bfw_collection_id_state', 'verified');

    if ($api_key_state !== 'verified'){
      add_action('admin_notices', array(
        &$this, 'api_key_invalid_state_message')
      );

      return false;
    } elseif ($collection_id_state !== 'verified') {
      add_action('admin_notices', array(
        &$this, 'collection_id_invalid_state_message')
      );
      return false;
    }   

    return true;
  }

  public function api_key_invalid_state_message()
  {
    $this->invalid_state_message('API Key');
  }

  public function collection_id_invalid_state_message()
  {
    $this->invalid_state_message('Collection ID');
  }

  public function invalid_state_message($error_type)
  {
    $message = '<div class="error">';
    $message .= '<p>' . sprintf("<strong>Billplz Disabled</strong> $error_type is not valid. %sClick here to configure%s", '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=billplz">', '</a>') . '</p>';
    $message .= '</div>';

    echo $message;
  }

  public function api_key_missing_message()
  {
    $this->key_missing_message('API Key');
  }

  public function collection_id_missing_message()
  {
    $this->key_missing_message('Collection ID');
  }

  public function xsignature_key_missing_message()
  {
    $this->key_missing_message('XSignature Key');
  }

  public function key_missing_message($error_type)
  {
    $message = '<div class="error">';
    $message .= '<p>' . sprintf("<strong>Billplz Disabled</strong> You should set your $error_type in Billplz. %sClick here to configure%s", '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=billplz">', '</a>') . '</p>';
    $message .= '</div>';

    echo $message;
  }

  private function fetch_billplz_payment_gateways()
  {
    if (false === ($gateways = get_transient('bfw_get_payment_gateways'))) {
      $billplz = $this->billplz;

      list($rheader, $rbody) = $billplz->toArray($billplz->getPaymentGateways());

      $gateways = array();  

      if (isset($rbody['error']) || $rheader != 200) {
        self::log('Failed to fetch billplz payment gateways');
      } else {
        $gateways = $rbody;
      }

      foreach($gateways['payment_gateways'] as $key => $value){
        if (in_array($value['code'], array('BP-2C2PGRB', 'BP-2C2PBST', 'BP-2C2PTNG'))){
          $gateways['payment_gateways'][$key]['category'] = 'twoctwopwallet';
        }
      }

      set_transient('bfw_get_payment_gateways', $gateways, HOUR_IN_SECONDS * 1);
    }

    return $gateways;
  }

  private function fetch_billplz_collection_payment_gateways(){
    if (false === ($collection_gateways = get_transient('bfw_get_collection_gateways'))) {
      
      $billplz = $this->billplz;
      list($rheader, $rbody) = $billplz->toArray($billplz->getPaymentMethodIndex($this->collection_id));

      $collection_gateways = array();

      if (isset($rbody['error']) || $rheader != 200) {
        self::log('Failed to fetch billplz collection payment gateways');
      } else {
        foreach ($rbody['payment_methods'] as $payment_method) {
          if ($payment_method['active']) {
            switch ($payment_method['code']){
              case 'isupaypal':
                $payment_method['code'] = 'paypal';
                break;
              case 'twoctwop':
                $payment_method['code'] = '2c2p';
                break;
            }
            $collection_gateways[] = $payment_method['code'];
          }
        }
      }

      set_transient('bfw_get_collection_gateways', $collection_gateways, MINUTE_IN_SECONDS * 30);
    }

    return $collection_gateways;
  }

  public function payment_fields()
  {
    $description = $this->get_description();
    if ( $description ) {
      echo wpautop( wptexturize( $description ) );
    }

    if ($this->has_fields) {
      $gateways = $this->fetch_billplz_payment_gateways();
      $collection_gateways = $this->fetch_billplz_collection_payment_gateways();
      $bank_name = $this->add_custom_gateways(BillplzBankName::get());

      if (has_action('bfw_payment_fields')) {
        do_action('bfw_payment_fields', $gateways, $bank_name);
      } elseif (has_action('bfw_payment_fields_with_collection')) {
        do_action('bfw_payment_fields_with_collection', $gateways, $bank_name, $collection_gateways);
      } else {
        $gateway_option = array();

        if (!empty($gateways)) {
          foreach ($bank_name as $key => $value) {
            foreach ($gateways['payment_gateways'] as $gateway) {
              if ($gateway['code'] === $key && $gateway['active'] && in_array($gateway['category'], $collection_gateways)) {
                $gateway_option[$gateway['code']] = $bank_name[$gateway['code']] ? strtoupper($bank_name[$gateway['code']]) : $gateway['code'];
              }
            }
          }
        }

        woocommerce_form_field('billplz_bank', array(
          'type'        => 'select',
          'required'    => true,
          'label'       => __('Choose Payment Method', 'bfw'),
          'options'       => $gateway_option
        ));
      }
    }
  }

  private function get_customer_first_name($order) {
    if ( $order->get_user_id() ) {
      return get_user_meta( $order->get_user_id(), 'first_name', true );
    }

    if ( '' !== $order->get_billing_first_name( 'edit' ) ) {
      return $order->get_billing_first_name( 'edit' );
    } else {
      return $order->get_shipping_first_name( 'edit' );
    }
  }
  
  private function get_customer_last_name($order) {
    if ( $order->get_user_id() ) {
      return get_user_meta( $order->get_user_id(), 'last_name', true );
    }

    if ( '' !== $order->get_billing_last_name( 'edit' ) ) {
       return $order->get_billing_last_name( 'edit' );
    } else {
       return $order->get_shipping_last_name( 'edit' );
    }
  }

  private function get_order_data($order)
  {
    $firstname = $this->get_customer_first_name($order);
    $lastname = $this->get_customer_last_name($order);

    $order_data = array(
      'id' => $order->get_id(),
      'name'  => "$firstname $lastname",
      'email' => $order->get_billing_email(),
      'phone' => $order->get_billing_phone(),
      'total' => (string) ($order->get_total() * 100)
    );
    
    return apply_filters('bfw_filter_order_data', $order_data);
  }

  private function validate_and_assign_reference_1(){
    if (isset($_POST['billplz_bank'])){
      $this->reference_1_label = 'Bank Code';
      $this->reference_1 = $_POST['billplz_bank'];
    } else {
      wc_add_notice(__('Please choose the payment method to proceed', 'bfw'), 'error');
    }
  }

  private function delete_previously_assoc_bill($order_id, $bill_id)
  {
    $billplz = $this->billplz;

    if (!empty(bfw_get_bill_state_legacy($order_id, $bill_id))){
      bfw_delete_bill($bill_id);
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
    $description = apply_filters('bfw_description_with_order', $description, $order);
    return mb_substr(apply_filters('bfw_description', $description), 0, 200);
  }

  private function validate_bill_payment($order)
  {
    $billplz = $this->billplz;
    if (!empty($bill_id = $order->get_transaction_id())) {
      list($rheader, $rbody) = $billplz->toArray($billplz->getBill($bill_id));
      if ($rheader === 200 && $rbody['paid'] && $rbody['reference_2'] == $order->get_id()){
        self::complete_payment_process($order, ['id' => $bill_id, 'type' => 'requery'], $this->is_sandbox);
        return true;
      }  
      $this->delete_previously_assoc_bill($order->get_id(), $bill_id);
    }
    return false;
  }

  public function process_payment($order_id)
  {
    if ($this->has_fields){
      $this->validate_and_assign_reference_1();
    }

    $order = wc_get_order( $order_id );

    if ($this->do_not_clear_cart) {
      if ($this->validate_bill_payment($order))
      {
        return array(
          'result' => 'success',
          'redirect' => $order->get_checkout_order_received_url(),
        );
      }
    } else {
      WC()->cart->empty_cart();
    }

    $order_data = $this->get_order_data($order);

    $parameter = array(
      'collection_id' => $this->collection_id,
      'email' => $order_data['email'],
      'mobile' => trim($order_data['phone']),
      'name' => empty($order_data['name']) ? 'No Name' : $order_data['name'],
      'amount' => $order_data['total'],
      'callback_url' => add_query_arg(
        array(
          'order' => $order_data['id'],
          'message_type' => 'bill_callback'
        ),
           WC()->api_request_url(get_class($this))
        ),
      'description' => $this->get_order_description($order)
    );

    $optional = array(
      'redirect_url' => add_query_arg(
        array(
          'order' => $order_data['id'],
          'message_type' => 'bill_redirect'
        ),
           WC()->api_request_url(get_class($this))
        ),
      'reference_1_label' => mb_substr($this->reference_1_label, 0, 20),
      'reference_1' => mb_substr($this->reference_1, 0, 120),
      'reference_2_label' => 'Order ID',
      'reference_2' => $order_data['id'],
    );

    self::log('Creating bill for order number #' . $order_data['id']);

    $billplz = $this->billplz;
    list($rheader, $rbody) = $billplz->toArray($billplz->createBill($parameter, $optional));

    if ($rheader !== 200) {
      self::log('Error creating bill for order number #' . $order_data['id'] . print_r($rbody, true));
      wc_add_notice(__('ERROR: ', 'bfw') . print_r($rbody, true), 'error');
      
     return array(
        'result' => 'failed',
        'redirect' => null,
      );
    }

    self::log('Bill ID ' . $rbody['id'] . ' created for order number #' . $order_data['id']);

    bfw_add_bill($order_id, $rbody['id'], 'due');
    update_post_meta($order_id, '_transaction_id', $rbody['id']);

    wp_schedule_single_event( time() + (30 * MINUTE_IN_SECONDS), 'bfw_bill_inquiry', array( $rbody['id'], $order_data['id'] ) );

    if ($this->has_fields){
      $rbody['url'] .= '?auto_submit=true';
    }
    
    return array(
      'result' => 'success',
      'redirect' => $rbody['url'],
    );
  }

  public static function complete_payment_process($order, $data, $is_sandbox)
  {
    $referer = "<br>Sandbox: " . ($is_sandbox ? 'Yes' : 'No');
    $referer .= "<br>Bill ID: " . $data['id'];
    $referer .= "<br>Order ID: " . $order->get_id();
    $referer .= "<br>Type: " . $data['type'];

    $order->add_order_note('Payment Status: SUCCESSFUL ' . $referer);
    $order->payment_complete($data['id']);
    self::log('Order #' . $order->get_id() . ' updated in WooCommerce as Paid for Bill ID: ' . $data['id']);
    do_action('bfw_on_payment_success_update', $order);
  }

  public function check_response()
  {
    @ob_clean();

    if (isset($_GET['message_type'])){
      $type = sanitize_text_field($_GET['message_type']);
    } else if (isset($_GET['billplz']['x_signature'])) {
      // starting 18 august 2021
      // shall be removed in later version
      $type = 'bill_redirect';
    } else {
      $type = 'bill_callback';
    }

    try {
        $data = BillplzWooCommerceWPConnect::getXSignature($this->x_signature, $type);
    } catch (Exception $e) {
        status_header(403);
        self::log('Failed X Signature Validation. Information: '.print_r($_REQUEST, true));
        exit('Failed X Signature Validation');
    } finally {
      self::log('Billplz response ' . print_r($data, true));
    }

    $order_id = sanitize_text_field($_GET['order']);

    if (empty(bfw_get_bill_state_legacy($order_id, $data['id']))) {
      status_header(404);
      self::log('Order not found for Bill ID: ' . $data['id']);
      exit('Order not found');
    }

    $order = wc_get_order($order_id);

    if (!$data['paid'] && $data['type'] === 'bill_redirect'){
      if (isset($data['transaction_status']) && $data['transaction_status'] == 'pending'){
        wp_redirect($order->get_view_order_url());
      } else {
        wp_redirect(esc_url_raw($order->get_cancel_order_url_raw()));
      }
      exit;
    }
    
    if (bfw_get_bill_state_legacy($order_id, $data['id']) == 'due' && $data['paid']) {
      bfw_update_bill($data['id'], 'paid', $order_id);
      self::complete_payment_process($order, $data, $this->is_sandbox);
    }

    if ($data['type'] === 'bill_redirect') {
      wp_redirect($order->get_checkout_order_received_url());
    }
    
    exit;
  }

  public function add_custom_gateways($bank_name){
    if ($this->twoctwop_boost){
      $bank_name['BP-2C2PBST'] = 'Boost';
    }
    if ($this->twoctwop_tng){
      $bank_name['BP-2C2PTNG'] = 'TNG';
    }
    if ($this->twoctwop_grabpay){
      $bank_name['BP-2C2PGRB'] = 'Grab';
    }
    asort($bank_name);
    return apply_filters('billplz_bank_name', $bank_name);
  }

  public function process_admin_options()
  {
    delete_transient('bfw_get_payment_gateways');
    delete_transient('bfw_get_collection_gateways');

    $this->verify_keys_authenticity();

    parent::process_admin_options();
  }

  private function verify_keys_authenticity()
  {
    $posted_data = $this->get_post_data();

    if ($this->verify_api_key($posted_data)){
      $this->verify_collection_id($posted_data);
    }
  }

  private function verify_api_key($posted_data, $recursion = false){
    if ($recursion){
      $this->is_sandbox = !isset($posted_data['woocommerce_billplz_is_sandbox']);
    } else {
      $this->is_sandbox = isset($posted_data['woocommerce_billplz_is_sandbox']);
    }

    if (isset($posted_data['woocommerce_billplz_api_key'])){
      $this->api_key = $posted_data['woocommerce_billplz_api_key'];
    }
    
    $this->initialize_api_helper();

    $status = $this->billplz->getWebhookRank()[0];

    switch($status) {
      case 200:
        update_option('bfw_api_key_state', 'verified');
        return true;
      case 401:
        if (!$recursion && $this->verify_api_key($posted_data, true)){
          if ($this->is_sandbox){
            $_POST['woocommerce_billplz_is_sandbox'] = '1';
          } else {
            unset($_POST['woocommerce_billplz_is_sandbox']);
          }
          return true;
        } elseif ($recursion) {
          update_option('bfw_api_key_state', 'invalid');
        }
        break;
      default:
        update_option('bfw_api_key_state', 'unknown');
    }
    return false;
  }

  private function verify_collection_id($posted_data){
    if (isset($posted_data['woocommerce_billplz_api_key'])){
      $this->api_key = $posted_data['woocommerce_billplz_api_key'];
    }

    $this->initialize_api_helper();

    if (isset($posted_data['woocommerce_billplz_collection_id'])){
      $this->collection_id = $posted_data['woocommerce_billplz_collection_id'];
    }

    $status = $this->billplz->getCollection($this->collection_id)[0];

    switch($status) {
      case 200:
        update_option('bfw_collection_id_state', 'verified');
        return true;
      case 404:
        update_option('bfw_collection_id_state', 'invalid');
        break;
      case 401:
        update_option('bfw_collection_id_state', 'unauthorized');
        break;
      default:
        update_option('bfw_collection_id_state', 'unkown');
    }
    return false;
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
