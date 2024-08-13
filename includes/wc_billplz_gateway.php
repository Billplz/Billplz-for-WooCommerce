<?php

defined('ABSPATH') || exit;

function bfw_add_gateway($methods)
{
  $methods[] = 'WC_Billplz_Gateway';
  return $methods;
}

add_filter('woocommerce_payment_gateways', 'bfw_add_gateway');

// Action hooks that needs to register outside of the payment gateway class
add_action('wp_ajax_bfw_create_refund', array('WC_Billplz_Gateway', 'create_refund'));

// "admin_notices" hook must be registered outside gateway class to avoid duplicate notices in admin screen
add_action('admin_notices', function() {
  $wc_billplz_gateway = new WC_Billplz_Gateway();
  $wc_billplz_gateway->display_errors();
});

// Display the saved payment order data for specified refund
add_action('woocommerce_after_order_refund_item_name', function($refund) {
  include BFW_PLUGIN_DIR . '/includes/views/html-order-refund-information.php';
});

use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class WC_Billplz_Gateway extends WC_Payment_Gateway
{
  public static $log_enabled = false;
  public static $log = false;

  public static $gateway_id = 'billplz';

  private $error_messages = array();

  private $do_not_clear_cart = false;

  private $is_sandbox = false;
  private $api_key;
  private $x_signature;
  private $collection_id;
  private $payment_order_collection_id;

  private $reference_1_label;
  private $reference_1;
  private $instructions;

  private $twoctwop_boost;
  private $twoctwop_tng;
  private $twoctwop_grabpay;
  private $twoctwop_shopeepay;
  private $is_advanced_checkout;

  private $connect;
  private $billplz;

  public function __construct()
  {
    $this->id = self::$gateway_id;
    $this->method_title = __('Billplz', 'bfw');
    $this->method_description = __('Have your customers pay with Billplz.', 'bfw');
    $this->order_button_text =  __('Pay with Billplz', 'bfw');

    $this->supports = array('products', 'refunds');

    if (is_admin()){
      $this->init_form_fields();
    }

    $this->init_settings();

    self::$log_enabled = 'yes' === $this->get_option('debug', 'no');
    $this->has_fields = 'yes' === $this->get_option('has_fields');
    $this->do_not_clear_cart = 'yes' === $this->get_option('do_not_clear_cart');
    
    $this->settings = apply_filters('bfw_settings_value', $this->settings);
    
    $this->title = $this->get_option('title');
    $this->description = $this->get_option('description');

    $this->is_sandbox = 'yes' === $this->get_option('is_sandbox');

    $this->api_key = $this->get_option('api_key');
    $this->x_signature = $this->get_option('x_signature');
    $this->collection_id = $this->get_option('collection_id');
    $this->payment_order_collection_id = $this->get_option('payment_order_collection_id');

    $this->reference_1_label = $this->get_option('reference_1_label');
    $this->reference_1 = $this->get_option('reference_1');
    $this->instructions = $this->get_option('instructions');

    $this->twoctwop_boost = 'yes' === $this->get_option('2c2p_boost');
    $this->twoctwop_tng = 'yes' === $this->get_option('2c2p_tng');
    $this->twoctwop_grabpay = 'yes' === $this->get_option('2c2p_grabpay');
    $this->twoctwop_shopeepay = 'yes' === $this->get_option('2c2p_shopeepay');

    $this->is_advanced_checkout = 'yes' === $this->get_option('is_advanced_checkout');

    if ( !$this->is_valid_for_use() ) {
      $this->enabled = 'no';
    }

    $this->load_icon();
    $this->woocommerce_add_action();
    $this->initialize_api_helper();
  }

  public function init_form_fields()
  {
    $this->form_fields = apply_filters('bfw_form_fields', bfw_get_settings());

    // Custom field in the plugin settings to hide the payment order collection ID
    add_action( 'woocommerce_generate_billplz_payment_order_collection_id_html', array( $this, 'generate_payment_order_collection_id_html'), 10, 3 );

    add_action( 'admin_init', array( $this, 'reset_payment_order_collection_id' ) );
  }

  public function generate_payment_order_collection_id_html( $field_key, $field_value, $data )
  {
    if ( !$this->payment_order_collection_id ) {
      return;
    }

    $defaults  = array(
      'title'             => '',
      'disabled'          => false,
      'class'             => '',
      'css'               => '',
      'placeholder'       => '',
      'type'              => 'text',
      'desc_tip'          => false,
      'description'       => '',
      'custom_attributes' => array(),
    );

    $data = wp_parse_args( $data, $defaults );

    // Display only the first 6 characters of the payment order collection ID and conceal the remainder with "*"
    $hidden_payment_order_collection_id = substr( $this->payment_order_collection_id, 0, 6 );
    $hidden_payment_order_collection_id .= str_repeat( '*', strlen( $this->payment_order_collection_id ) - 6 );

    ob_start();
    ?>
    <tr valign="top">
      <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
      </th>
      <td class="forminp">
        <input type="text" value="<?php echo esc_attr( $hidden_payment_order_collection_id ); ?>" readonly>
        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . self::$gateway_id . '&reset_payment_order_collection_id=1' ), 'bfw_reset_payment_order_collection_id' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Reset Payment Order Collection ID', 'bfw' ); ?></a>
      </td>
    </tr>
    <?php
    return ob_get_clean();
  }

  public function reset_payment_order_collection_id()
  {
    if ( !isset( $_GET['reset_payment_order_collection_id'] ) || $_GET['reset_payment_order_collection_id'] !== '1' ) {
      return false;
    }

    $nonce = isset( $_GET['_wpnonce'] ) ? wp_unslash( $_GET['_wpnonce'] ) : '';

    if ( !wp_verify_nonce( $nonce, 'bfw_reset_payment_order_collection_id' ) ) {
      wp_die( __( 'Invalid nonce.', 'bfw' ) );
    }

    $this->update_option( 'payment_order_collection_id', '' );

    wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . self::$gateway_id ) );
    exit;
  }

  public function is_valid_for_use()
  {
    $currencies_presence = $this->validate_currencies();

    if ($keys_presence = $this->validate_keys_presence()){
      $keys_verified = $this->check_keys_verification();
    }

    if ($this->errors) {
      return false;
    }

    return true;
  }

  private function validate_currencies() {

    $wc_currency = get_woocommerce_currency();
    $supported_currencies = apply_filters( 'bfw_supported_currencies', array( 'MYR' ) );

    if ( !in_array( $wc_currency, $supported_currencies ) ) {
      $this->add_error( 'unsupported_currency' );
    }

    return empty( $this->errors ) ? true : false;

  }

  private function validate_keys_presence() {

    if ( !$this->api_key ) {
      $this->add_error( 'api_key_missing' );
    }

    if ( !$this->collection_id ) {
      $this->add_error( 'collection_id_missing' );
    }

    if ( !$this->x_signature ) {
      $this->add_error( 'x_signature_missing' );
    }

    return empty( $this->errors ) ? true : false;

  }

  private function check_keys_verification() {

    $api_key_state = get_option( 'bfw_api_key_state', 'verified' );
    $collection_id_state = get_option( 'bfw_collection_id_state', 'verified' );
    $payment_order_collection_id_state = get_option( 'bfw_payment_order_collection_id_state', 'verified' );

    if ( $api_key_state !== 'verified' ) {
      $this->add_error( 'api_key_invalid_state' );
    }

    if ( $collection_id_state !== 'verified' ) {
      $this->add_error( 'collection_id_invalid_state');
    }

    if ( $this->payment_order_collection_id && $payment_order_collection_id_state !== 'verified' ) {
      $this->add_error( 'payment_order_collection_id_invalid_state');
    }

    return empty( $this->errors ) ? true : false;

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
    $bfw_icon = plugins_url("assets/images/billplz-logo-$logo.png", BFW_PLUGIN_FILE);
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

    add_action('add_meta_boxes', array(&$this, 'register_metaboxes'), 10, 2);
  }

  public function enqueue_scripts()
  {
    $screen    = get_current_screen();
    $screen_id = $screen ? $screen->id : '';

    if ( $this->is_order_meta_box_screen( $screen_id ) ) {
      wp_enqueue_style('bfw-admin-order', BFW_PLUGIN_URL . 'assets/css/admin-order.css', array('woocommerce_admin_styles'), BFW_PLUGIN_VER, 'all');
      wp_enqueue_script('bfw-admin-order', BFW_PLUGIN_URL . 'assets/js/admin-order.js', array('jquery', 'wc-admin-order-meta-boxes'), BFW_PLUGIN_VER, true);

      wp_localize_script('bfw-admin-order', 'bfw_admin_order_metaboxes', array(
        'ajax_url'               => admin_url('admin-ajax.php'),
        'create_refund_nonce'    => wp_create_nonce('bfw-create-refund'),
        'refund_success_message' => __( 'Refund created. The refund payment will be processed via Billplz.', 'bfw' ),
      ));
    }
  }

  // Helper function from WooCommerce plugin to determine whether the current screen is an order edit screen
  private function is_order_meta_box_screen( $screen_id ) {

    $screen_id = str_replace( 'edit-', '', $screen_id );

    $types_with_metaboxes_screen_ids = array_filter(
      array_map(
        'wc_get_page_screen_id',
        wc_get_order_types( 'order-meta-boxes' )
      )
    );

    return in_array( $screen_id, $types_with_metaboxes_screen_ids, true );

  }

  // Display admin error messages
  public function display_errors() {

    $errors = (array) $this->get_errors();

    foreach ( $errors as $error ) {
      ?>
      <div id="woocommerce_errors" class="error notice"><p><?php echo wp_kses_post( $error ); ?></p></div>
      <?php
    }

  }

  // Add an error message for display in admin on save
  public function add_error( $error ) {

    if ( empty( $this->error_messages ) ) {
      $this->init_error_messages();
    }

    if ( isset( $this->error_messages[ $error ] ) ) {
      $this->errors[] = $this->error_messages[ $error ];
    } else {
      $this->errors[] = $error;
    }

  }

  private function init_error_messages() {

    $this->error_messages = array(
      'unsupported_currency'                      => sprintf( __( '<strong>Billplz Disabled</strong>: WooCommerce currency option is not supported by Billplz. <a href="%s">Click here to configure</a>', 'bfw' ), admin_url( 'admin.php?page=wc-settings&tab=general' ) ),

      'api_key_missing'                           => $this->key_missing_message( __( 'API Key', 'bfw' ) ),
      'collection_id_missing'                     => $this->key_missing_message( __( 'Collection ID', 'bfw' ) ),
      'x_signature_missing'                       => $this->key_missing_message( __( 'XSignature Key', 'bfw' ) ),
      'api_key_invalid_state'                     => $this->invalid_state_message( __( 'API Key', 'bfw' ) ),
      'collection_id_invalid_state'               => $this->invalid_state_message( __( 'Collection ID', 'bfw' ) ),
      'payment_order_collection_id_invalid_state' => $this->invalid_state_message( __( 'Payment Order Collection ID', 'bfw' ) ),
    );

  }

  private function key_missing_message( $error_type ) {
    return sprintf( __( '<strong>Billplz Disabled</strong>: You should set your %1$s in the plugin settings. <a href="%2$s">Click here to configure</a>', 'bfw' ), $error_type, admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . self::$gateway_id ) );
  }

  private function invalid_state_message( $error_type ) {
    return sprintf( __( '<strong>Billplz Disabled</strong>: %1$s is not valid. <a href="%2$s">Click here to configure</a>', 'bfw' ), $error_type, admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . self::$gateway_id ) );
  }

  private function initialize_api_helper(){
    global $bfw_connect, $bfw_api;

    $bfw_connect->set_api_key($this->api_key, $this->is_sandbox);
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

    $description = sprintf(__('Order %s', 'bfw'), $order->get_order_number()) . " - " . implode(', ', $item_names);
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

    $order->update_meta_data('_transaction_id', $rbody['id']);
    $order->save();

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

    if (!$order) {
        wp_die(__('Order not found.'));
    }

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

    // Reload page after settings saved.
    wp_safe_redirect( wp_unslash( $_SERVER['REQUEST_URI'] ) );
    exit();
  }

  private function verify_keys_authenticity()
  {
    $posted_data = $this->get_post_data();

    if ( $this->verify_api_key( $posted_data ) ) {
      $this->verify_collection_id( $posted_data );
      $this->verify_payment_order_collection_id( $posted_data );
    }
  }

  private function verify_api_key( $posted_data )
  {
    $is_sandbox = isset($posted_data['woocommerce_billplz_is_sandbox']) ? $posted_data['woocommerce_billplz_is_sandbox'] === '1' : false;
    $api_key    = isset($posted_data['woocommerce_billplz_api_key']) ? $posted_data['woocommerce_billplz_api_key'] : false;

    if ( !$api_key ) {
      return false;
    }

    global $bfw_connect, $bfw_api;

    $bfw_connect->set_api_key( $api_key, $is_sandbox );
    $bfw_api->set_connect( $bfw_connect );
    $billplz = $bfw_api;

    list( $rheader, $rbody ) = $billplz->getWebhookRank();

    switch ( $rheader ) {
      case 200:
        update_option( 'bfw_api_key_state', 'verified' );
        return true;
        break;

      case 401:
        update_option( 'bfw_api_key_state', 'invalid' );
        break;

      default:
        update_option( 'bfw_api_key_state', 'unknown' );
        break;
    }

    return false;
  }

  private function verify_collection_id( $posted_data )
  {
    $is_sandbox    = isset($posted_data['woocommerce_billplz_is_sandbox']) ? $posted_data['woocommerce_billplz_is_sandbox'] === '1' : false;
    $api_key       = isset($posted_data['woocommerce_billplz_api_key']) ? $posted_data['woocommerce_billplz_api_key'] : false;
    $collection_id = isset($posted_data['woocommerce_billplz_collection_id']) ? $posted_data['woocommerce_billplz_collection_id'] : false;

    if ( !$api_key || !$collection_id ) {
      return false;
    }

    global $bfw_connect, $bfw_api;

    $bfw_connect->set_api_key( $api_key, $is_sandbox );
    $bfw_api->set_connect( $bfw_connect );
    $billplz = $bfw_api;

    list( $rheader, $rbody ) = $billplz->getCollection( $collection_id );

    switch ( $rheader ) {
      case 200:
        update_option( 'bfw_collection_id_state', 'verified' );
        return true;
        break;

      case 404:
        update_option( 'bfw_collection_id_state', 'invalid' );
        break;

      case 401:
        update_option( 'bfw_collection_id_state', 'unauthorized' );
        break;

      default:
        update_option( 'bfw_collection_id_state', 'unknown' );
        break;
    }

    return false;
  }

  private function verify_payment_order_collection_id( $posted_data )
  {
    $is_sandbox                  = isset($posted_data['woocommerce_billplz_is_sandbox']) ? $posted_data['woocommerce_billplz_is_sandbox'] === '1' : false;
    $api_key                     = isset($posted_data['woocommerce_billplz_api_key']) ? $posted_data['woocommerce_billplz_api_key'] : false;
    $x_signature                 = isset($posted_data['woocommerce_billplz_x_signature']) ? $posted_data['woocommerce_billplz_x_signature'] : false;
    $payment_order_collection_id = isset($posted_data['woocommerce_billplz_payment_order_collection_id']) ? $posted_data['woocommerce_billplz_payment_order_collection_id'] : $this->payment_order_collection_id;

    if ( !$api_key || !$x_signature || !$payment_order_collection_id ) {
      return false;
    }

    global $bfw_connect, $bfw_api;

    $bfw_connect->set_api_key( $api_key, $is_sandbox );
    $bfw_api->set_connect( $bfw_connect );
    $billplz = $bfw_api;

    $parameter = array(
      'epoch' => time(),
    );

    $checksum_data = array(
      $payment_order_collection_id,
      $parameter['epoch'],
    );

    $parameter['checksum'] = hash_hmac( 'sha512', implode( '', $checksum_data ), $x_signature );

    list( $rheader, $rbody ) = $billplz->getPaymentOrderCollection( $payment_order_collection_id, $parameter );

    switch ( $rheader ) {
      case 200:
        update_option( 'bfw_payment_order_collection_id_state', 'verified' );
        return true;
        break;

      case 404:
        update_option( 'bfw_payment_order_collection_id_state', 'invalid' );
        break;

      case 401:
        update_option( 'bfw_payment_order_collection_id_state', 'unauthorized' );
        break;

      default:
        update_option( 'bfw_payment_order_collection_id_state', 'unknown' );
        break;
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

  public function can_refund_order( $order ) {
    return $order
        && $this->supports( 'refunds' )
        && $order->is_paid()
        && $order->get_total() > 0
        && $order->get_remaining_refund_amount() > 0;
  }

  public function register_metaboxes( $post_type, $post )
  {
    if ( OrderUtil::get_order_type($post) === 'shop_order' ) {
      $order = wc_get_order($post);

      if ( $order && $order->is_paid() && $order->get_payment_method() === $this->id ) {
        $screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
          ? wc_get_page_screen_id( 'shop-order' )
          : 'shop_order';

        add_meta_box('bfw-order-refund-metabox', __('Billplz Refund', 'bfw'), array(&$this, 'refund_metabox'), $screen, 'normal', 'core');
      }
    }
  }

  public function refund_metabox($post)
  {
    if ( OrderUtil::get_order_type( $post ) === 'shop_order' ) {
      $order = wc_get_order($post);

      if ( $order && $order->is_paid() && $order->get_payment_method() === $this->id ) {
        $banks = BillplzBankName::getSwift( $this->is_sandbox );

        include BFW_PLUGIN_DIR . '/includes/views/html-order-refund-metabox.php';
      }
    }
  }

  public static function create_refund()
  {
    ob_start();

    check_ajax_referer( 'bfw-create-refund', 'security' );

    if ( !current_user_can( 'edit_shop_orders' ) ) {
      wp_die( -1 );
    }

    $order_id               = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
    $refund_amount          = isset( $_POST['refund_amount'] ) ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['refund_amount'] ) ), wc_get_price_decimals() ) : 0;
    $refunded_amount        = isset( $_POST['refunded_amount'] ) ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['refunded_amount'] ) ), wc_get_price_decimals() ) : 0;
    $refund_reason          = isset( $_POST['refund_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['refund_reason'] ) ) : '';
    $line_item_qtys         = isset( $_POST['line_item_qtys'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['line_item_qtys'] ) ), true ) : array();
    $line_item_totals       = isset( $_POST['line_item_totals'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['line_item_totals'] ) ), true ) : array();
    $line_item_tax_totals   = isset( $_POST['line_item_tax_totals'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['line_item_tax_totals'] ) ), true ) : array();
    $api_refund             = isset( $_POST['api_refund'] ) && 'true' === $_POST['api_refund'];
    $restock_refunded_items = isset( $_POST['restock_refunded_items'] ) && 'true' === $_POST['restock_refunded_items'];

    $bank                   = isset( $_POST['bank'] ) ? sanitize_text_field( wp_unslash( $_POST['bank'] ) ) : '';
    $bank_account_number    = isset( $_POST['bank_account_number'] ) ? sanitize_text_field( wp_unslash( $_POST['bank_account_number'] ) ) : '';
    $bank_account_name      = isset( $_POST['bank_account_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bank_account_name'] ) ) : '';
    $identity_number        = isset( $_POST['identity_number'] ) ? sanitize_text_field( wp_unslash( $_POST['identity_number'] ) ) : '';
    $refund_description     = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';

    try {
      $order = wc_get_order( $order_id );

      if ( !$order ) {
        throw new Exception( __( 'Invalid order.', 'bfw' ) );
      }

      $payment_order_collection_id_state = get_option( 'bfw_payment_order_collection_id_state', 'verified' );

      if ( $payment_order_collection_id_state !== 'verified' ) {
        throw new Exception( __( 'Invalid payment order collection ID. Please set a valid Billplz payment order collection ID in the plugin settings.', 'bfw' ) );
      }

      if ( !$refund_amount ) {
        throw new Exception( __( 'Please enter the refund amount.', 'bfw' ) );
      }

      if ( !$bank ) {
        throw new Exception( __( 'Please select a bank.', 'bfw' ) );
      }

      if ( !$bank_account_number ) {
        throw new Exception( __( 'Please enter the bank account number.', 'bfw' ) );
      }

      if ( !$bank_account_name ) {
        throw new Exception( __( 'Please enter the bank account name.', 'bfw' ) );
      }

      if ( !$identity_number ) {
        throw new Exception( __( 'Please enter the identification number (IC/SSM number) for bank account verification purposes.', 'bfw' ) );
      }

      if ( !$refund_description ) {
        throw new Exception( __( 'Please enter the refund description.', 'bfw' ) );
      }

      $refund_data = array(
        'amount'              => $refund_amount,
        'bank'                => $bank,
        'bank_account_number' => $bank_account_number,
        'bank_account_name'   => $bank_account_name,
        'identity_number'     => $identity_number,
        'description'         => $refund_description,
      );

      // Save refund data into WordPress database for 1 hour, so that we can retrieved it in the process_refund method
      set_transient( "bfw_order_{$order_id}_refund_data", $refund_data, HOUR_IN_SECONDS );

      // Refer WC_AJAX::refund_line_items /////////////////////////////////////////////////////////

      $max_refund = wc_format_decimal( $order->get_total() - $order->get_total_refunded(), wc_get_price_decimals() );
      $total_refund = wc_format_decimal( $order->get_total_refunded(), wc_get_price_decimals() );

      if ( $max_refund < $refund_amount ) {
        throw new Exception( __( 'Invalid refund amount.', 'bfw' ) );
      }

      if ( $total_refund !== $refunded_amount ) {
        throw new Exception( __( 'Error processing refund. Please try again.', 'bfw' ) );
      }

      // Prepare line items which we are refunding
      $line_items = array();
      $item_ids   = array_unique( array_merge( array_keys( $line_item_qtys ), array_keys( $line_item_totals ) ) );

      foreach ( $item_ids as $item_id ) {
        $line_items[ $item_id ] = array(
          'qty'          => 0,
          'refund_total' => 0,
          'refund_tax'   => array(),
        );
      }
      foreach ( $line_item_qtys as $item_id => $qty ) {
        $line_items[ $item_id ]['qty'] = max( $qty, 0 );
      }
      foreach ( $line_item_totals as $item_id => $total ) {
        $line_items[ $item_id ]['refund_total'] = wc_format_decimal( $total );
      }
      foreach ( $line_item_tax_totals as $item_id => $tax_totals ) {
        $line_items[ $item_id ]['refund_tax'] = array_filter( array_map( 'wc_format_decimal', $tax_totals ) );
      }

      $args = array(
        'amount'         => $refund_amount,
        'reason'         => $refund_reason,
        'order_id'       => $order_id,
        'line_items'     => $line_items,
        'refund_payment' => $api_refund,
        'restock_items'  => $restock_refunded_items,
      );

      // Create the refund object
      $refund = wc_create_refund(
        array(
          'amount'         => $refund_amount,
          'reason'         => $refund_reason,
          'order_id'       => $order_id,
          'line_items'     => $line_items,
          'refund_payment' => true, // set to true to call process_refund method
          'restock_items'  => $restock_refunded_items,
        )
      );

      /////////////////////////////////////////////////////////////////////////////////////////////

      if ( is_wp_error( $refund ) ) {
        throw new Exception( $refund->get_error_message() );
      }
    } catch ( Exception $e ) {
      wp_send_json_error( array( 'error' => $e->getMessage() ) );
    }

    wp_send_json_success();
  }

  public function process_refund( $order_id, $amount = null, $reason = '' )
  {
    try {
      $order = wc_get_order( $order_id );

      if ( !$order ) {
        throw new Exception( __( 'Invalid order.', 'bfw' ) );
      }

      $refund = $this->get_order_latest_refund( $order_id );

      if ( !$refund ) {
        throw new Exception( __( 'Invalid refund.', 'bfw' ) );
      }

      if ( !$this->payment_order_collection_id ) {
        throw new Exception( __( 'To refund the order via Billplz, enter your payment order credentials in the plugin settings.', 'bfw' ) );
      }

      $payment_order_collection_id_state = get_option( 'bfw_payment_order_collection_id_state', 'verified' );

      if ( $payment_order_collection_id_state !== 'verified' ) {
        throw new Exception( __( 'Invalid payment order collection ID. Please set a valid Billplz payment order collection ID in the plugin settings.', 'bfw' ) );
      }

      // Refund information
      $refund_data = get_transient( "bfw_order_{$order_id}_refund_data" );
      $refund_data = wp_parse_args( $refund_data, array(
        'amount'              => 0,
        'bank'                => '',
        'bank_account_number' => '',
        'bank_account_name'   => '',
        'identity_number'     => '',
        'description'         => '',
      ) );

      if ( !$refund_data['amount'] ) {
        throw new Exception( __( 'Please enter the refund amount.', 'bfw' ) );
      }

      if ( !$refund_data['bank'] ) {
        throw new Exception( __( 'Please select a bank.', 'bfw' ) );
      }

      if ( !$refund_data['bank_account_number'] ) {
        throw new Exception( __( 'Please enter the bank account number.', 'bfw' ) );
      }

      if ( !$refund_data['bank_account_name'] ) {
        throw new Exception( __( 'Please enter the bank account name.', 'bfw' ) );
      }

      if ( !$refund_data['identity_number'] ) {
        throw new Exception( __( 'Please enter the identification number (IC/SSM number) for bank account verification purposes.', 'bfw' ) );
      }

      if ( !$refund_data['description'] ) {
        throw new Exception( __( 'Please enter the refund description.', 'bfw' ) );
      }

      $banks = BillplzBankName::getSwift( $this->is_sandbox );

      if ( !in_array( $refund_data['bank'], array_keys( $banks ) ) ) {
        throw new Exception( __( 'Invalid bank selected.' ) );
      }

      $args = array(
        'payment_order_collection_id' => $this->payment_order_collection_id,
        'bank_code'                   => $refund_data['bank'],
        'bank_account_number'         => $refund_data['bank_account_number'],
        'identity_number'             => $refund_data['identity_number'],
        'name'                        => $refund_data['bank_account_name'],
        'description'                 => $refund_data['description'],
        'total'                       => absint( $refund_data['amount'] * 100 ),
        'email'                       => $order->get_billing_email(),
        'reference_id'                => $refund->get_id(),
        'epoch'                       => time(),
      );

      $checksum_data = array(
        $args['payment_order_collection_id'],
        $args['bank_account_number'],
        $args['total'],
        $args['epoch'],
      );

      $args['checksum'] = hash_hmac( 'sha512', implode( '', $checksum_data ), $this->x_signature );

      $billplz = $this->billplz;

      self::log( 'Creating a payment order for refund #' . $refund->get_id() );

      list( $rheader, $rbody ) = $billplz->toArray( $billplz->createPaymentOrder( $args ) );

      if ( isset( $rbody['error']['message'] ) ) {
        if ( is_array( $rbody['error']['message'] ) ) {
          $error_message = implode( ', ', array_map( 'sanitize_text_field', $rbody['error']['message'] ) );
        } else {
          $error_message = sanitize_text_field( $rbody['error']['message'] );
        }

        self::log( 'Error creating a payment order for refund #' . $refund->get_id() . ': ' . $error_message );
        throw new Exception( sprintf( esc_html__( 'Error processing refund: %s.', 'bfw' ), $error_message ) );
      }

      if ( $rheader != 200 ) {
        self::log( 'Error creating a payment order for refund #' . $refund->get_id() );
        throw new Exception( esc_html__( 'Error processing refund. Please try again.', 'bfw' ) );
      }

      $payment_order_data = array(
        'id'            => isset( $rbody['id'] ) ? sanitize_text_field( $rbody['id'] ) : '',
        'collection_id' => isset( $rbody['payment_order_collection_id'] ) ? sanitize_text_field( $rbody['payment_order_collection_id'] ) : '',
        'status'        => isset( $rbody['status'] ) ? sanitize_text_field( $rbody['status'] ) : '',
        'sandbox'       => $this->is_sandbox,
      );

      $payment_order_defaults = bfw_get_refund_payment_order_defaults();
      $payment_order_data = wp_parse_args( $payment_order_data, $payment_order_defaults );

      if ( $payment_order_data['id'] && $payment_order_data['collection_id'] && $payment_order_data['status'] ) {
        self::log( 'Successfully created a payment order for refund #' . $refund->get_id() . '. Payment order ID: ' . $payment_order_data['id'] );
        self::complete_refund_process( $order, $refund, $payment_order_data );

        return true;
      } else {
        self::log( 'Error creating a payment order for refund #' . $refund->get_id() );
        $refund->delete();
      }
    } catch ( Exception $e ) {
      return new WP_Error( 'error', $e->getMessage() );
    }

    return false;
  }

  private function get_order_latest_refund( $order_id )
  {
    $refunds = wc_get_orders(
      array(
        'type'   => 'shop_order_refund',
        'parent' => absint( $order_id ),
        'limit'  => 1,
      )
    );

    if ( isset( $refunds[0] ) ) {
      return $refunds[0];
    }

    return false;
  }

  public static function complete_refund_process( $order, $refund, $payment_order_data )
  {
    $payment_order_data = wp_parse_args( $payment_order_data, array(
      'id'            => '',
      'collection_id' => '',
      'status'        => '',
      'sandbox'       => false,
    ) );

    $payment_order_defaults = bfw_get_refund_payment_order_defaults();
    $payment_order_data = wp_parse_args( $payment_order_data, $payment_order_defaults );

    if ( !$payment_order_data['id'] || !$payment_order_data['collection_id'] || !$payment_order_data['status'] ) {
      return false;
    }

    $order_id = absint( $order->get_id() );
    $refund_id = absint( $refund->get_id() );

    ///////////////////////////////////////////////////////////////////////////////////////////////

    switch ( $payment_order_data['status'] ) {
      case 'enquiring':
      case 'executing':
      case 'reviewing':
        // Check the payment order status after 1 minute
        wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'bfw_check_refund_payment_order', array( $order_id, $refund_id ) );
        break;

      case 'refunded':
        $refund->delete();
        break;
    }

    if ( $payment_order_data['status'] !== 'refunded' ) {
      bfw_update_refund_payment_order( $refund_id, $payment_order_data );
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////

    switch ( $payment_order_data['status'] ) {
      case 'completed':
        $refund_note = sprintf( __( 'Refund #%d successfully paid via Billplz.', 'bfw' ), $refund_id );
        break;

      case 'refunded':
        $refund_note = sprintf( __( 'Refund #%d was deleted because the refund payment failed to be processed via Billplz.', 'bfw' ), $refund_id );
        break;

      default:
        $refund_note = sprintf( __( 'Refund #%d successfully submitted to Billplz.', 'bfw' ), $refund_id );
        break;
    }

    $reference = sprintf( __( 'ID: %s', 'bfw' ), $payment_order_data['id'] );
    $reference .= '<br>' . sprintf( __( 'Collection ID: %s', 'bfw' ), $payment_order_data['collection_id'] );
    $reference .= '<br>' . sprintf( __( 'Status: %s', 'bfw' ), strtoupper( $payment_order_data['status'] ) );
    $reference .= '<br>' . sprintf( __( 'Sandbox: %s', 'bfw' ), ( $payment_order_data['status'] ? __( 'Yes', 'bfw' ) : __( 'No', 'bfw' ) ) );

    $refund_note .= '<br>.<br>';
    $refund_note .= __( 'Payment order data:', 'bfw' );
    $refund_note .= '<br>**********************<br>';
    $refund_note .= $reference;

    $order->add_order_note( $refund_note );
  }
}
