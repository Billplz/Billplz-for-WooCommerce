<?php

function bfw_requery_button( $actions, $order ) {

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

    $action_slug = 'bfw_requery';
    
    $actions[$action_slug] = array(
      'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=bfw_requery&bill_id='.$bill_id.'&order_id=' . $order->get_id() ), 'bfw_requery' ),
      'name'      => __( 'Billplz Requery', 'bfw' ),
      'action'    => $action_slug,
    );
  }
  return $actions;
}

add_filter( 'woocommerce_admin_order_actions', 'bfw_requery_button', 100, 2 );

function bfw_requery_actions_button_css() {
  $action_slug = "bfw_requery";

  echo '<style>.wc-action-button-'.$action_slug.'::after { font-family: woocommerce !important; content: "\e031" !important; }</style>';
}

add_action( 'admin_head', 'bfw_requery_actions_button_css' );

function bfw_requery_status()
{
  if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'bfw_requery' ) && isset($_GET['order_id'], $_GET['bill_id'] )) {
  
    $order  = wc_get_order( absint( wp_unslash( $_GET['order_id'] ) ) );

    $bill_id = wp_unslash($_GET['bill_id']);

    $settings = get_option('woocommerce_billplz_settings');

    if (empty($settings['api_key']))
    {
      wp_die( __('API Key is not set.', 'bfw'), __('Requery Bill', 'bfw'));
    }

    $is_sandbox = $settings['is_sandbox'] === 'yes';

    global $bfw_connect, $bfw_api;

    $bfw_connect->set_api_key($settings['api_key'], $is_sandbox);
    $bfw_api->set_connect($bfw_connect);

    list($rheader, $rbody) = $bfw_api->toArray($bfw_api->getBill($bill_id));

    if ($rheader !== 200){
      WC_Billplz_Gateway::log('Error getting bill id: ' . $bill_id);
      wp_die( 'Error getting bill id: ' . $bill_id , __('Requery Bill', 'bfw'));
    }

    if ($rbody['paid']){
      bfw_update_bill($bill_id, 'paid', $order->get_id());
      
      WC_Billplz_Gateway::complete_payment_process($order, ['id' => $bill_id, 'type' => 'requery'], $is_sandbox); 
    }
  }

  wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) );
  exit;
}

add_action( 'wp_ajax_bfw_requery', 'bfw_requery_status' );