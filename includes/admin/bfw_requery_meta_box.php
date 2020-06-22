<?php

function bfw_meta_box_actions($actions)
{
  $actions['bfw_requery'] = __( 'Billplz: Requery Status', 'bfw' );
  return $actions;
}

add_action( 'woocommerce_order_actions', 'bfw_meta_box_actions' );

function bfw_process_meta_box_actions($order)
{
  $bill_id = $order->get_transaction_id();
  $order_id = $order->get_id();

  if (empty($bill_state = get_post_meta($order_id, $bill_id, true))) 
  {
    return;
  }

  if ($bill_state === 'paid') 
  {
    return;
  }

  $settings = get_option('woocommerce_billplz_settings');

  if (empty($settings['api_key']))
  {
    return;
  }

  $is_sandbox = $settings['is_sandbox'] === 'yes';

  global $bfw_connect, $bfw_api;

  $bfw_connect->set_api_key($settings['api_key'], $is_sandbox);
  $bfw_api->set_connect($bfw_connect);

  list($rheader, $rbody) = $bfw_api->toArray($bfw_api->getBill($bill_id));

  if ($rheader !== 200){
    WC_Billplz_Gateway::log('Error getting bill id: ' . $bill_id);
    return;
  }

  if (!$rbody['paid']){
    return;
  }

  if (update_post_meta($order_id, $bill_id, 'paid', 'due')){
    WC_Billplz_Gateway::complete_payment_process($order, ['id' => $bill_id, 'type' => 'requery'], $is_sandbox);
  }

}

add_action( 'woocommerce_order_action_bfw_requery', 'bfw_process_meta_box_actions');

