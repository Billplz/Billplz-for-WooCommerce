<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings for Billplz for WooCommerce.
 */
return array(
    'enabled' => array(
        'title' => __('Enable/Disable', 'bfw'),
        'type' => 'checkbox',
        'label' => __('Enable Billplz', 'bfw'),
        'default' => 'yes'
    ),
    'title' => array(
        'title' => __('Title', 'bfw'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'bfw'),
        'default' => __('Billplz Payment Gateway', 'bfw')
    ),
    'description' => array(
        'title' => __('Description', 'bfw'),
        'type' => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', 'bfw'),
        'default' => __('Pay with <strong>Maybank2u, CIMB Clicks, Bank Islam, RHB, Hong Leong Bank, Bank Muamalat, Public Bank, Alliance Bank, Affin Bank, AmBank, Bank Rakyat, UOB, Standard Chartered </strong>. ', 'bfw')
    ),
    'api_key' => array(
        'title' => __('API Secret Key', 'bfw'),
        'type' => 'text',
        'placeholder' => 'Example : ed586547-00b7-459a-a02e-7e876a744590',
        'description' => __('Please enter your Billplz Api Key.', 'bfw') . ' ' . sprintf(__('Get Your API Key: %sBillplz%s.', 'bfw'), '<a href="https://www.billplz.com/enterprise/setting" target="_blank">', '</a>'),
        'default' => ''
    ),
    'collection_id' => array(
        'title' => __('Collection ID', 'bfw'),
        'type' => 'text',
        'placeholder' => 'Example : ugo_7dit',
        'description' => __('Please enter your Billplz Collection ID. ', 'bfw') . ' ' . sprintf(__('Login to Billplz >> Billing >> Create Collection. %sLink%s.', 'bfw'), '<a href="https://www.billplz.com/enterprise/billing" target="_blank">', '</a>'),
        'default' => ''
    ),
    'x_signature' => array(
        'title' => __('X Signature Key', 'bfw'),
        'type' => 'text',
        'placeholder' => 'Example : S-0Sq67GFD9Y5iXmi5iXMKsA',
        'description' => __('Please enter your Billplz X Signature Key. ', 'bfw') . ' ' . sprintf(__('Login to Billplz >> Settings >> Enable X Signature. %sLink%s.', 'bfw'), '<a href="https://www.billplz.com/enterprise/billing" target="_blank">', '</a>'),
        'default' => ''
    ),
    'notification' => array(
        'title' => __('Bill Notification', 'bfw'),
        'type' => 'select',
        'class' => 'wc-enhanced-select',
        'description' => __('No Notification - Customer will NOT receive any notification. Email Only - Customer will receive Email Notification for payment. SMS Only - Customer will receive SMS Notification for payment. Both - Customer will receive Email & SMS Notification for payment.', 'bfw'),
        'default' => 'None',
        'desc_tip' => true,
        'options' => array(
            '0' => __('No Notification (Recommended)', 'bfw'),
            '1' => __('Email Only (FREE)', 'bfw'),
            '2' => __('SMS Only (RM0.15)', 'bfw'),
            '3' => __('Both (RM0.15)', 'bfw')
        )
    ),
    'clearcart' => array(
        'title' => __('Clear Cart Session', 'bfw'),
        'type' => 'checkbox',
        'label' => __('Tick to clear cart session on checkout', 'bfw'),
        'default' => 'no'
    ),
    'debug' => array(
        'title' => __('Debug Log', 'bfw'),
        'type' => 'checkbox',
        'label' => __('Enable logging', 'bfw'),
        'default' => 'no',
        'description' => sprintf(__('Log Billplz events, such as IPN requests, inside <code>%s</code>', 'bfw'), wc_get_log_file_path('billplz'))
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
        'default' => 'You have cancelled the payment. Please make a payment!'
    ),
    'reference_1_label' => array(
        'title' => __('Reference 1 Label', 'bfw'),
        'type' => 'text',
        'default' => ''
    ),
    'reference_1' => array(
        'title' => __('Reference 1', 'bfw'),
        'type' => 'text',
        'default' => ''
    ),
    'checkout_label' => array(
        'title' => __('Checkout Label', 'bfw'),
        'type' => 'text',
        'placeholder' => 'Example: Pay with Billplz',
        'description' => __('Button label on checkout.', 'bfw'),
        'default' => 'Pay with Billplz'
    )
);
