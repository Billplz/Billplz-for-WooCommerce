<?php

function bfw_meta_box_actions($actions)
{
  if (!isset($_GET['post'])){
    return $actions;
  }

  $order_id = absint( wp_unslash( $_GET['post'] ) );
  $order  = wc_get_order( $order_id );

  if ( $order->has_status( array( 'pending', 'on-hold', 'cancelled' ) ) && $order->get_payment_method() === 'billplz') {

    if (empty($bill_id = $order->get_transaction_id())){
        return $actions;
    }

    if (empty($bill_state = bfw_get_bill_state_legacy($order->get_id(), $bill_id)))
    {
        return $actions;
    }

    if ($bill_state === 'paid') 
    {
      return $actions;
    }

    $actions['bfw_requery'] = __( 'Billplz: Requery Status', 'bfw' );
  }

  return $actions;
}

add_action( 'woocommerce_order_actions', 'bfw_meta_box_actions' );

function bfw_process_meta_box_actions($order)
{
  $bill_id = $order->get_transaction_id();
  $order_id = $order->get_id();

  if (empty($bill_state = bfw_get_bill_state_legacy($order_id, $bill_id))) 
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

  bfw_update_bill($bill_id, 'paid', $order_id);
  WC_Billplz_Gateway::complete_payment_process($order, ['id' => $bill_id, 'type' => 'requery'], $is_sandbox);
}

add_action( 'woocommerce_order_action_bfw_requery', 'bfw_process_meta_box_actions');

