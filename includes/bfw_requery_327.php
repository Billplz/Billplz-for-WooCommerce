<?php

defined( 'ABSPATH' ) || exit;

class BfwRequery327 {
   public static function init(){
      add_action('prepare_bill', array(__CLASS__, 'create_records'));
      add_action('bill_requery', array(__CLASS__, 'update_record'), 10, 3);
      add_action('admin_notices', array(__CLASS__, 'requery_received' ) );
   }

   public static function prepare_records() {
      $current_db_version = get_option( 'bfw_327_version' );

      $store_schema  = new ActionScheduler_StoreSchema();
      $store_schema->register_tables( true );

      if ($current_db_version == '3.27.2'){
         return;
      }

      $ids = array();

      foreach (bfw_get_327_bill_ids() as $bfws) {
         $ids[] = $bfws->id;
      }

      $chunked_ids = array_chunk($ids, 100);

      $flatted_cids = array();

      foreach ($chunked_ids as $each_chunked_ids) {
         $flatted_cids[] = implode("|",$each_chunked_ids);
      }

      foreach ($flatted_cids as $each_flatted_ids ) {
         WC()->queue()->schedule_single(
            time(), 
            'prepare_bill', 
            array(
               'id' => $each_flatted_ids
            ),
            'bfw_327'
         );
      }

      update_option( 'bfw_327_version', '3.27.2' );
   }

   public static function create_records($ids) {
      $current_db_version = get_option( 'bfw_327_version' );
      $loop               = 0;

      $store_schema  = new ActionScheduler_StoreSchema();
      $store_schema->register_tables( true );

      $ids = explode("|", $ids);

      foreach (bfw_get_327_bill($ids) as $bfws ) {
         WC()->queue()->schedule_single(
            time() + $loop, 
            'bill_requery', 
            array(
               'id' => $bfws->id,
               'post_id' => $bfws->post_id, 
               'bill_id' => $bfws->bill_id
            ),
            'bfw_327'
         );

         $loop = $loop + 3;
      }
   }

   public static function update_record($id, $post_id, $bill_id){
      $settings = get_option('woocommerce_billplz_settings');

      if (empty($settings['api_key'])) {
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

      if (!$rbody['paid'] && $order = wc_get_order($post_id)){
         if ($order->has_status( 'processing' )){

            $referer = "<br>Sandbox: " . ($is_sandbox ? 'Yes' : 'No');
            $referer .= "<br>Bill ID: " . $rbody['id'];
            $referer .= "<br>Order ID: " . $order->get_id();
            $referer .= "<br>Type: requery 327";

            $order->update_status( apply_filters( 'woocommerce_default_order_status', 'pending' ));

            $order->add_order_note('Payment Status: FAILED ' . $referer);

            WC_Billplz_Gateway::log('Order #' . $order->get_id() . ' updated in WooCommerce as pending payment for Bill ID: ' . $rbody['id']);
         }

         bfw_update_bill($rbody['id'], $rbody['state']);
      }

   }

   public static function requery_received() {
      if (!isset($_GET['page']) || $_GET['page'] != 'wc-status') {
         return;
      }

      if (!isset($_GET['tab']) || $_GET['tab'] != 'action-scheduler') {
         return;
      }

      if (!isset($_GET['do_requery_bill']) || $_GET['do_requery_bill'] != 'true') {
         return;
      }

      if (get_option( 'bfw_327_version' ) != '3.27.2') {
         global $pagenow;
         if ( $pagenow == 'admin.php') {
            self::prepare_records();
            echo '<div class="notice notice-success is-dismissible">
                     <p>Request for requery for bill successful. The order will be reassessed by stages and will be reverted to pending payment state upon completion. <a href="'.admin_url('edit.php?post_type=shop_order').'">Check your order</a>.</p>
                  </div>';
         }
      }
   }
}

BfwRequery327::init();