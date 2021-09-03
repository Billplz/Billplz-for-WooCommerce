<?php

function bfw_add_bill($post_id, $bill_id, $state = 'due') {
   global $wpdb;
   
   $table_name = $wpdb->prefix . 'bfw';
   
   $wpdb->insert( 
      $table_name, 
      array( 
         'created_at' => current_time( 'mysql' ), 
         'updated_at' => current_time( 'mysql' ), 
         'post_id' => $post_id, 
         'bill_id' => $bill_id,
         'state' => $state 
      ) 
   );
}

function bfw_update_bill($bill_id, $state, $post_id = '0') {
   global $wpdb;

   $table_name = $wpdb->prefix . 'bfw';
   
   $update_state = $wpdb->update( 
      $table_name, 
      array( 
         'updated_at' => current_time( 'mysql' ), 
         'bill_id' => $bill_id,
         'state' => $state 
      ),
      array(
         'bill_id' => $bill_id
      )
   );

   // starting 18 august 2021
   // shall be removed in later version
   // possibility of using previously metadata type
   if($update_state < 1 && $post_id !== '0' && empty(bfw_get_bill_state($bill_id))) {
      bfw_add_bill($post_id, $bill_id, $state);
      delete_post_meta($post_id, $bill_id);
   }
}

function bfw_get_bill_state($bill_id) {
   global $wpdb;

   $table_name = $wpdb->prefix . 'bfw';
   
   $row = $wpdb->get_row(
      $wpdb->prepare( "SELECT state FROM $table_name WHERE bill_id = %s", $bill_id )
   );

   if (isset($row->state)){
      return $row->state;
   } else {
      return '';
   }
}

// starting 18 august 2021
// shall be removed in later version
function bfw_get_bill_state_legacy($order_id, $bill_id) {
   $bill_state = get_post_meta($order_id, $bill_id, true);

   if (empty($bill_state)){
      $bill_state = bfw_get_bill_state($bill_id);
   }

   return $bill_state;
}

function bfw_delete_bill($bill_id) {
   global $wpdb;

   $table_name = $wpdb->prefix . 'bfw';
   
   $wpdb->delete(
      $table_name,
      array(
         'bill_id' => $bill_id,
      )
   );
}

function bfw_get_327_bill_ids(){
   global $wpdb;

   $table_name = $wpdb->prefix . 'bfw';

   // 30 August 2021, 10:52 AM
   $start_dt = new DateTime('2021-08-30 02:52:00');
   $s = $start_dt->format('Y-m-d H:i:s');

   $end_dt = new DateTime('2021-09-30 23:59:59');
   $now_dt = new DateTime("now");

   if ($now_dt < $end_dt){
      $e = $now_dt->format('Y-m-d H:i:s');
   } else {
      $e = $end_dt->format('Y-m-d H:i:s');
   }

   $row = $wpdb->get_results(
      $wpdb->prepare("SELECT id FROM $table_name WHERE state = %s AND updated_at BETWEEN %s AND %s", 'paid', $s, $e)
   );

   return $row;
}

function bfw_get_327_bill($ids = array()){
   global $wpdb;

   $table_name = $wpdb->prefix . 'bfw';

   // 30 August 2021, 10:52 AM
   $start_dt = new DateTime('2021-08-30 02:52:00');
   $s = $start_dt->format('Y-m-d H:i:s');

   $end_dt = new DateTime('2021-09-30 23:59:59');
   $now_dt = new DateTime("now");

   if ($now_dt < $end_dt){
      $e = $now_dt->format('Y-m-d H:i:s');
   } else {
      $e = $end_dt->format('Y-m-d H:i:s');
   }

   // $status_string: a comma separated string with quoted post stati
   // $status_string = "'publish', 'draft'";
   $id_string = "'" . implode( "', '", $ids ) . "'";

   $row = $wpdb->get_results(
      $wpdb->prepare("SELECT id, post_id, bill_id FROM $table_name WHERE id in ( $id_string ) AND state = %s AND updated_at BETWEEN %s AND %s", 'paid', $s, $e)
   );

   return $row;
}