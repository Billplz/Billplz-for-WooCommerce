<?php

defined('ABSPATH') || exit;

function bfw_get_settings() {
  $saved_settings = get_option('woocommerce_billplz_settings');

  $settings = array();

  $settings['enabled'] = array(
    'title' => __('Enable/Disable', 'bfw'),
    'type' => 'checkbox',
    'label' => __('Enable Billplz', 'bfw'),
    'default' => 'no',
  );

  $settings['title'] = array(
    'title' => __('Title', 'bfw'),
    'type' => 'text',
    'description' => __('Payment method description that the customer will see on your checkout.', 'bfw'),
    'default' => 'Billplz',
    'desc_tip' => true,
  );
    
  $settings['description'] = array(
    'title' => __('Description', 'bfw'),
    'type' => 'textarea',
    'description' => __('This controls the description which the user sees during checkout.', 'bfw'),
    'default' => __('Pay with Billplz. ', 'bfw'),
    'desc_tip' => true,
  );

  $settings['display_logo'] = array(
    'title' => __('Billplz Logo','bfw'),
    'description' => sprintf(__('This controls which logo appeared on checkout page. <a target="_blank" href="%s">Fpx</a>. <a target="_blank" href="%s">Old</a>. <a target="_blank" href="%s">All</a>.', 'bfw' ), BFW_PLUGIN_URL.'assets/images/billplz-logo-fpx.png', BFW_PLUGIN_URL.'assets/images/billplz-logo-old.png', BFW_PLUGIN_URL.'assets/images/billplz-logo-all.png'),
    'default' => 'fpx',
    'class' => 'wc-enhanced-select',
    'type' => 'select',
    'desc_tip' => false,
    'options' => array(
      'fpx' => 'Fpx',
      'old' => 'Old',
      'all' => 'All'
    ),
  );

  $settings['api_credentials'] = array(
    'title' => __('API Credentials', 'bfw'),
    'type' => 'title',
    'description' => '',
  );

  $settings['is_sandbox'] = array(
    'title' => __('Enable Sandbox', 'bfw'),
    'type' => 'checkbox',
    'label' => __('Enable Billplz sandbox', 'bfw'),
    'default' => 'no',
    'description' => sprintf(__('Billplz sandbox can be used to test payments. Sign up for a <a href="%s" target="_blank">sandbox account</a>.', 'bfw'), 'https://www.billplz-sandbox.com/'),
  );

  if (defined('BFW_API_KEY')){
    $settings['api_key_information'] = array(
      'title' => __('API Secret Key', 'bfw'),
      'type' => 'title',
      'description' => 'API Secret Key is not configurable.'
    );
  } else {
    $settings['api_key'] = array(
      'title' => __('API Secret Key', 'bfw'),
      'type' => 'text',
      'placeholder' => 'Example : ed586547-00b7-459a-a02e-7e876a744590',
      'description' => __('Billplz API Secret Key. Can be obtained from Billplz Account Setttings.', 'bfw'),
      'default' => '',
      'desc_tip' => true,
      'disabled' => defined('BFW_API_KEY'),
    );
  }

  if (defined('BFW_X_SIGNATURE')){
    $settings['x_signature_information'] = array(
      'title' => __('X Signature Key', 'bfw'),
      'type' => 'title',
      'description' => 'X Signature Key is not configurable.'
    );
  } else {
    $settings['x_signature'] = array(
      'title' => __('X Signature Key', 'bfw'),
      'type' => 'text',
      'placeholder' => 'Example : S-0Sq67GFD9Y5iXmi5iXMKsA',
      'description' => __('Billplz X Signature Key. Can be obtained from Billplz Account Setttings.', 'bfw'),
      'default' => '',
      'desc_tip' => true,
      'disabled' => defined('BFW_X_SIGNATURE'),
    );
  }

  if (defined('BFW_COLLECTION_ID')){
    $settings['collection_id_information'] = array(
      'title' => __('Collection ID', 'bfw'),
      'type' => 'title',
      'description' => 'Collection ID is not configurable.'
    );
  } else {
    $settings['collection_id'] = array(
      'title' => __('Collection ID', 'bfw'),
      'type' => 'text',
      'placeholder' => 'Example : ugo_7dit',
      'description' => __('Billplz Collection ID. Can be obtained from Billplz Billing page.', 'bfw'),
      'default' => '',
      'desc_tip' => true,
      'disabled' => defined('BFW_COLLECTION_ID'),
    );
  }

  $settings['refund'] = array(
    'title' => __('Refund', 'bfw'),
    'type' => 'title',
    'description' => __('To enable refund order via Billplz feature, enter your payment order credentials.', 'bfw'),
  );

  if (defined('BFW_PAYMENT_ORDER_COLLECTION_ID')){
    $settings['payment_order_collection_id_information'] = array(
      'title' => __('Payment Order Collection ID', 'bfw'),
      'type' => 'title',
      'description' => 'Payment Order Collection ID is not configurable.',
    );
  } elseif ( isset( $saved_settings['payment_order_collection_id'] ) && !empty( $saved_settings['payment_order_collection_id'] ) ) {
    $settings['payment_order_collection_id_information'] = array(
      'title' => __('Payment Order Collection ID', 'bfw'),
      'type' => 'billplz_payment_order_collection_id',
    );
  } else {
    $settings['payment_order_collection_id'] = array(
      'title' => __('Payment Order Collection ID', 'bfw'),
      'type' => 'text',
      'placeholder' => 'Example : abcd1234-1234-ab12-de34-abcdef123456',
      'description' => __('Billplz Payment Order Collection ID. Can be obtained from Billplz Payment Order page.', 'bfw'),
      'default' => '',
      'desc_tip' => true,
      'disabled' => defined('BFW_PAYMENT_ORDER_COLLECTION_ID'),
    );
  }

  $settings['checkout_settings'] = array(
    'title' => __('Checkout Settings', 'bfw'),
    'type' => 'title',
    'description' => '',
  );

  $settings['instructions'] = array(
    'title' => __('Instructions', 'bfw'),
    'type' => 'textarea',
    'description' => __('Instructions that will be added to the thank you page and emails.', 'bfw'),
    'default' => '',
    'desc_tip' => true,
  );
    
  $settings['do_not_clear_cart'] = array(
    'title' => __('Do not clear Cart', 'bfw'),
    'type' => 'checkbox',
    'label' => __('Do not clear cart upon checkout', 'bfw'),
    'default' => 'no',
  );

  $settings['has_fields'] = array(
    'title' => __('Skip Bill Page', 'bfw'),
    'type' => 'checkbox',
    'label' => __('Bypass Billplz Bill Page', 'bfw'),
    'default' => 'no',
  );

  $settings['is_advanced_checkout'] = array(
    'title' => __('Advanced Checkout', 'bfw'),
    'type' => 'checkbox',
    'label' => __('Tick this checkbox if you are using any advanced checkout plugin', 'bfw'),
    'default' => 'no',
    'description' => __('If checked, plugin will redirect customer to Checkout - Pay for Order page to avoid redirect issue with advanced checkout plugin installed on your site. eg: WooCommerce Fast Cart', 'bfw'),
  );

  $settings['debugging'] = array(
    'title' => __('Debugging', 'bfw'),
    'type' => 'title',
    'description' => '',
  );

  $settings['debug'] = array(
    'title' => __('Debug Log', 'bfw'),
    'type' => 'checkbox',
    'label' => __('Enable logging', 'bfw'),
    'default' => 'no',
    'description' => sprintf(__('Log Billplz events, such as IPN requests, inside <code>%s</code>', 'bfw'), WC_Log_Handler_File::get_log_file_path('billplz')),
  );

  $settings['bill_option'] = array(
    'title' => __('Bill Option', 'bfw'),
    'type' => 'title',
    'description' => '',
  );

  $settings['reference_1_label'] = array(
    'title' => __('Reference 1 Label', 'bfw'),
    'type' => 'text',
    'default' => '',
    'description' => __('Bill Reference 1 Label.', 'bfw'),
    'desc_tip' => true,
  );
    
  $settings['reference_1'] = array(
    'title' => __('Reference 1', 'bfw'),
    'type' => 'text',
    'default' => '',
    'description' => __('Bill Reference 1 Value.', 'bfw'),
    'desc_tip' => true,
  );

  $settings['auto_delete_bill_order_cancelled'] = array(
    'title' => __('Auto Delete Bill', 'bfw'),
    'type' => 'checkbox',
    'label' => __('Automatically delete a bill upon order marked as "Cancelled"', 'bfw'),
    'default' => 'no',
  );

  return $settings;
}


function bfw_get_settings_defaults() {
    $settings = bfw_get_settings();
    $defaults = array();

    foreach ($settings as $key => $value) {
        if (isset($value['default'])) {
            $defaults[$key] = $value['default'];
        } else {
            $defaults[$key] = null;
        }
    }

    return $defaults;
}
