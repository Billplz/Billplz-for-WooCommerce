<?php

defined('ABSPATH') || exit;

function bfw_delete_order($post_id)
{
  if (defined('BFW_DISABLE_DELETE_ORDER') && BFW_DISABLE_DELETE_ORDER) {
      return;
  }

  $post_type = get_post_type($post_id);
  if ($post_type !== 'shop_order') {
      return;
  }

  $settings = get_option('woocommerce_billplz_settings');
  $api_key = $settings['api_key'];
  $bill_id = get_post_meta($post_id, '_transaction_id', true);
  $bill_paid = bfw_get_bill_state_legacy($post_id, $bill_id);

  if (empty($bill_id) || empty($api_key) || empty($bill_paid)) {
      return;
  }

  if ($bill_paid === 'paid') {
      return;
  }

  global $bfw_connect, $bfw_api;
  $bfw_connect->set_api_key($api_key, $settings['is_sandbox'] == 'yes');
  $connect = &$bfw_connect;
  
  $bfw_api->set_connect($connect);
  $billplz = &$bfw_api;

  list($rheader, $rbody) = $billplz->deleteBill($bill_id);

  if ($rheader !== 200) {
    wp_die('Deleting this order has been prevented. ' . print_r($rbody, true));
  }

  bfw_delete_bill($bill_id);
}
add_action('before_delete_post', 'bfw_delete_order');
