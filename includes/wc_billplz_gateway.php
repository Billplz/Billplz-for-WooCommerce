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

    if ( $this->is_sandbox ) {
        $this->is_sandbox_admin = 'yes' === $this->get_option( 'is_sandbox_admin' );
    } else {
        $this->is_sandbox_admin = false;
    }

    // If it is not in WP admin page, check for is_sandbox by checking the user's role
    if (!is_admin() && $this->is_sandbox && $this->is_sandbox_admin) {
      $this->is_sandbox = current_user_can('administrator') ? true : false;
    }

    // Set API credentials based on selected environment
    if ($this->is_sandbox) {
      $this->api_key = $this->get_option('live_api_key');
      $this->x_signature = $this->get_option('live_x_signature');
      $this->collection_id = $this->get_option('live_collection_id');
    } else {
      $this->api_key = $this->get_option('sandbox_api_key');
      $this->x_signature = $this->get_option('sandbox_x_signature');
      $this->collection_id = $this->get_option('sandbox_collection_id');
    }

    $this->reference_1_label = $this->settings['reference_1_label'];
    $this->reference_1 = $this->settings['reference_1'];
    $this->instructions = $this->settings['instructions'];

    $this->twoctwop_boost = 'yes' === $this->get_option('2c2p_boost');
    $this->twoctwop_tng = 'yes' === $this->get_option('2c2p_tng');
    $this->twoctwop_grabpay = 'yes' === $this->get_option('2c2p_grabpay');
    $this->twoctwop_shopeepay = 'yes' === $this->get_option('2c2p_shopeepay');

    $this->is_advanced_checkout = 'yes' === $this->get_option('is_advanced_checkout');

    $this->bfw_settings = new WC_Billplz_Settings();

    if (!$this->bfw_settings->is_valid_for_use()) {
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

  public function enqueue_scripts( $hook_suffix )
  {
    $section = isset( $_GET['section'] ) ? wp_unslash( $_GET['section'] ) : null;

    if ( $hook_suffix == 'woocommerce_page_wc-settings' && $section == $this->id ) {
      wp_enqueue_script( 'bfw-settings', BFW_PLUGIN_URL . 'includes/js/settings.js', array( 'jquery' ), BFW_PLUGIN_VER, true );
    }
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
    add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));

    add_action('before_woocommerce_pay_form', array(&$this, 'before_woocommerce_pay_form'), 10, 3);

    add_action('woocommerce_thankyou_billplz', array(&$this, 'thankyou_page'));
    add_action('woocommerce_email_before_order_table', array(&$this, 'email_instructions'), 10, 3);
    add_action('woocommerce_update_options_payment_gateways_billplz', array(&$this, 'process_admin_options'));
    add_action('woocommerce_api_wc_billplz_gateway', array(&$this, 'check_response'));
  }

  private function initialize_api_helper($sandbox = null){
    global $bfw_connect, $bfw_api;

    if ($sandbox === null) {
      $sandbox = $this->is_sandbox;
    }

    $bfw_connect->set_api_key($this->api_key, $sandbox);
    $this->connect = &$bfw_connect;
    
    $bfw_api->set_connect($this->connect);
    $this->billplz = &$bfw_api;
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
        if (in_array($value['code'], array('BP-2C2PGRB', 'BP-2C2PBST', 'BP-2C2PTNG', 'BP-2C2PSHPE'))){
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

  private function get_order_data($order)
  {
    $customer_full_name = $order->get_formatted_billing_full_name() ?: $order->get_formatted_shipping_full_name();

    $order_data = array(
      'id' => $order->get_id(),
      'name'  => $customer_full_name,
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
    $item_names = array();

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

    if ( $this->is_advanced_checkout && !is_checkout_pay_page() ) {
        set_transient( 'bfw_bill_url_' . $order->get_id(), $rbody['url'], DAY_IN_SECONDS );

        return array(
          'result' => 'success',
          'redirect' => add_query_arg( 'payment_method', $this->id, $order->get_checkout_payment_url() ),
        );
    }

    return array(
      'result' => 'success',
      'redirect' => $rbody['url'],
    );
  }

  // Automatically redirect customer to Billplz payment page when accessing Checkout - Pay for Order page with payment_method=billplz parameter
  public function before_woocommerce_pay_form( $order, $order_button_text, $available_gateways )
  {
    $selected_method = isset( $_GET['payment_method'] ) ? wp_unslash( $_GET['payment_method'] ) : null;

    if ( $selected_method == $this->id ) {
        $bill_url = get_transient( 'bfw_bill_url_' . $order->get_id() );

        if ( $bill_url ) {
            wp_redirect( $bill_url );
            exit;
        }
    }
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
    if ($this->twoctwop_shopeepay){
      $bank_name['BP-2C2PSHPE'] = 'Shopee Pay';
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

    $this->verify_api_key($posted_data);
    $this->verify_sandbox_api_key($posted_data);

    $this->verify_collection_id($posted_data);
    $this->verify_sandbox_collection_id($posted_data);
  }

  private function verify_api_key($posted_data, $recursion = false)
  {
    if ( !isset( $posted_data['woocommerce_billplz_api_key'] ) ) {
      return false;
    }

    $this->api_key = sanitize_text_field( $posted_data['woocommerce_billplz_api_key'] );

    $this->initialize_api_helper(false);

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

  private function verify_sandbox_api_key($posted_data, $recursion = false)
  {
    if ( !isset( $posted_data['woocommerce_billplz_sandbox_api_key'] ) ) {
      return false;
    }

    $this->api_key = sanitize_text_field( $posted_data['woocommerce_billplz_sandbox_api_key'] );

    $this->initialize_api_helper(true);

    $status = $this->billplz->getWebhookRank()[0];

    switch($status) {
      case 200:
        update_option('bfw_sandbox_api_key_state', 'verified');
        return true;
      case 401:
        if (!$recursion && $this->verify_sandbox_api_key($posted_data, true)){
          if ($this->is_sandbox){
            $_POST['woocommerce_billplz_is_sandbox'] = '1';
          } else {
            unset($_POST['woocommerce_billplz_is_sandbox']);
          }
          return true;
        } elseif ($recursion) {
          update_option('bfw_sandbox_api_key_state', 'invalid');
        }
        break;
      default:
        update_option('bfw_sandbox_api_key_state', 'unknown');
    }

    return false;
  }

  private function verify_collection_id($posted_data){
    if (isset($posted_data['woocommerce_billplz_api_key'])){
      $this->api_key = $posted_data['woocommerce_billplz_api_key'];
    }

    if (isset($posted_data['woocommerce_billplz_collection_id'])){
      $this->collection_id = $posted_data['woocommerce_billplz_collection_id'];
    }

    $this->initialize_api_helper(false);

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

  private function verify_sandbox_collection_id($posted_data){
    if (isset($posted_data['woocommerce_billplz_sandbox_api_key'])){
      $this->api_key = $posted_data['woocommerce_billplz_sandbox_api_key'];
    }

    if (isset($posted_data['woocommerce_billplz_sandbox_collection_id'])){
      $this->collection_id = $posted_data['woocommerce_billplz_sandbox_collection_id'];
    }

    $this->initialize_api_helper(true);

    $status = $this->billplz->getCollection($this->collection_id)[0];

    switch($status) {
      case 200:
        update_option('bfw_sandbox_collection_id_state', 'verified');
        return true;
      case 404:
        update_option('bfw_sandbox_collection_id_state', 'invalid');
        break;
      case 401:
        update_option('bfw_sandbox_collection_id_state', 'unauthorized');
        break;
      default:
        update_option('bfw_sandbox_collection_id_state', 'unkown');
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
