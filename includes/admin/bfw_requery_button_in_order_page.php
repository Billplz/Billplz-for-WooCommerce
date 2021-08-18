<?php

function bfw_order_item_add_action_buttons( $order )
{
  if ( $order->has_status( array( 'pending', 'on-hold', 'cancelled' ) ) && $order->get_payment_method() === 'billplz') {

    if (empty($bill_id = $order->get_transaction_id())){
        return;
    }

    if (empty($bill_state = bfw_get_bill_state_legacy($order->get_id(), $bill_id)))
    {
        return;
    }

    if ($bill_state === 'paid') 
    {
      return;
    }  
    
    echo '<button type="button" onclick="document.post.submit();" class="button bfw-requery">' . __( 'Requery', 'bfw' ) . '</button>';
    // indicate its taopix order generator button
    echo '<input type="hidden" value="1" name="bfw_requery_button_in_order_page" />';
  }
};

add_action( 'woocommerce_order_item_add_action_buttons', 'bfw_order_item_add_action_buttons', 10, 1);

function bfw_process_requery_action_button($order_id, $post, $update){
  $slug = 'shop_order';
  if(is_admin()){
    if ( $slug != $post->post_type ) {
      return;
    }
    
    if(isset($_POST['bfw_requery_button_in_order_page']) && $_POST['bfw_requery_button_in_order_page']){

      $order = wc_get_order( $order_id );
      if (empty($bill_id = $order->get_transaction_id())){
        return;
      }

      if (empty($bill_state = bfw_get_bill_state_legacy($order->get_id(), $bill_id)))
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
  }
}

add_action('save_post', 'bfw_process_requery_action_button', 10, 3);