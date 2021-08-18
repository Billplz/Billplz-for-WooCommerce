<?php


function bfw_bill_inquiry($bill_id, $order_id, $attempts = 0)
{
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

  // if ( false === ( get_transient( 'bfw_bill_inquiry' ) ) ) {
  //   set_transient( 'bfw_bill_inquiry', $bill_id, 2 );
  // } else {
  //   wp_schedule_single_event( time() + 3 , 'bfw_bill_inquiry', array( $rbody['id'], $order_id, $attempts) );
  // }

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
    if ($attempts < 1) {
      $time = time() + DAY_IN_SECONDS;
    } else {
      return;
    }

    wp_schedule_single_event( $time , 'bfw_bill_inquiry', array( $bill_id, $order_id, ++$attempts) );
    return;
  }

  $order = wc_get_order($order_id);

  bfw_update_bill($bill_id, 'paid', $order_id);
  
  WC_Billplz_Gateway::complete_payment_process($order, ['id' => $bill_id, 'type' => 'requery'], $is_sandbox);
}

  
add_action('bfw_bill_inquiry', 'bfw_bill_inquiry', 10, 3);
