<?php

defined('ABSPATH') || exit;

function bfw_get_settings() {
  return array(
    'enabled' => array(
      'title' => __('Enable/Disable', 'bfw'),
      'type' => 'checkbox',
      'label' => __('Enable Billplz', 'bfw'),
      'default' => 'no',
    ),
    'title' => array(
      'title' => __('Title', 'bfw'),
      'type' => 'text',
      'description' => __('Payment method description that the customer will see on your checkout.', 'bfw'),
      'default' => 'Billplz',
      'desc_tip' => true,
    ),
    'description' => array(
      'title' => __('Description', 'bfw'),
      'type' => 'textarea',
      'description' => __('This controls the description which the user sees during checkout.', 'bfw'),
      'default' => __('Pay with Billplz. ', 'bfw'),
      'desc_tip' => true,
    ),
    'display_logo' => array(
      'title' => __('Billplz Logo','bfw'),
      'description' => sprintf(__('This controls which logo appeared on checkout page. <a target="_blank" href="%s">Fpx</a>. <a target="_blank" href="%s">Old</a>. <a target="_blank" href="%s">All</a>.', 'bfw' ), BFW_PLUGIN_URL.'assets/billplz-logo-fpx.png', BFW_PLUGIN_URL.'assets/billplz-logo-old.png', BFW_PLUGIN_URL.'assets/billplz-logo-all.png'),
      'default' => 'fpx',
      'class' => 'wc-enhanced-select',
      'type' => 'select',
      'desc_tip' => false,
      'options' => array(
        'fpx' => 'Fpx',
        'old' => 'Old',
        'all' => 'All'
      ),
    ),
    'api_credentials' => array(
      'title' => __('API Credentials', 'bfw'),
      'type' => 'title',
      'description' => '',
    ),
    'is_sandbox' => array(
      'title' => __('Billplz sandbox', 'bfw'),
      'type' => 'checkbox',
      'label' => __('Enable Billplz sandbox', 'bfw'),
      'default' => 'no',
      'description' => sprintf(__('Billplz sandbox can be used to test payments. Sign up for a <a href="%s">sandbox account</a>.', 'bfw'), 'https://www.billplz-sandbox.com/'),
    ),
    'api_key' => array(
      'title' => __('API Secret Key', 'bfw'),
      'type' => 'text',
      'placeholder' => 'Example : ed586547-00b7-459a-a02e-7e876a744590',
      'description' => __('Billplz API Secret Key. Can be obtained from Billplz Account Setttings.', 'bfw'),
      'default' => '',
      'desc_tip' => true,
    ),
    'x_signature' => array(
      'title' => __('X Signature Key', 'bfw'),
      'type' => 'text',
      'placeholder' => 'Example : S-0Sq67GFD9Y5iXmi5iXMKsA',
      'description' => __('Billplz X Signature Key. Can be obtained from Billplz Account Setttings.', 'bfw'),
      'default' => '',
      'desc_tip' => true,
    ),
    'collection_id' => array(
      'title' => __('Collection ID', 'bfw'),
      'type' => 'text',
      'placeholder' => 'Example : ugo_7dit',
      'description' => __('Billplz Collection ID. Can be obtained from Billplz Billing pages.', 'bfw'),
      'default' => '',
      'desc_tip' => true,
    ),
    'checkout_settings' => array(
      'title' => __('Checkout Settings', 'bfw'),
      'type' => 'title',
      'description' => '',
    ),
    'instructions' => array(
      'title' => __('Instructions', 'bfw'),
      'type' => 'textarea',
      'description' => __('Instructions that will be added to the thank you page and emails.', 'bfw'),
      'default' => '',
      'desc_tip' => true,
    ),
    'custom_error' => array(
      'title' => __('Error Message', 'bfw'),
      'type' => 'text',
      'placeholder' => 'Example : You have cancelled the payment. Please make a payment!',
      'description' => __('Error message that will appear when customer cancel the payment.', 'bfw'),
      'default' => 'You have cancelled the payment. Please make a payment!',
    ),
    'do_not_clear_cart' => array(
        'title' => __('Do not clear Cart', 'bfw'),
        'type' => 'checkbox',
        'label' => __('Do not clear cart upon checkout', 'bfw'),
        'default' => 'no',
    ),
    'has_fields' => array(
        'title' => __('Skip Bill Page', 'bfw'),
        'type' => 'checkbox',
        'label' => __('Bypass Billplz Bill Page', 'bfw'),
        'default' => 'no',
    ),
    'debugging' => array(
        'title' => __('Debugging', 'bfw'),
        'type' => 'title',
        'description' => '',
    ),
    'debug' => array(
        'title' => __('Debug Log', 'bfw'),
        'type' => 'checkbox',
        'label' => __('Enable logging', 'bfw'),
        'default' => 'no',
        'description' => sprintf(__('Log Billplz events, such as IPN requests, inside <code>%s</code>', 'bfw'), wc_get_log_file_path('billplz')),
    ),
    'bill_option' => array(
        'title' => __('Bill Option', 'bfw'),
        'type' => 'title',
        'description' => '',
    ),
    'reference_1_label' => array(
        'title' => __('Reference 1 Label', 'bfw'),
        'type' => 'text',
        'default' => '',
        'description' => __('Bill Reference 1 Label.', 'bfw'),
        'desc_tip' => true,
    ),
    'reference_1' => array(
        'title' => __('Reference 1', 'bfw'),
        'type' => 'text',
        'default' => '',
        'description' => __('Bill Reference 1 Value.', 'bfw'),
        'desc_tip' => true,
    ),
  );
}
