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

function bfw_update_bill($bill_id, $state, $post_id) {
   global $wpdb;

   $table_name = $wpdb->prefix . 'bfw';

   $wpdb->update(
      $table_name,
      array(
         'updated_at' => current_time( 'mysql' ),
         'state' => $state
      ),
      array(
         'bill_id' => $bill_id,
         'post_id' => $post_id
      )
   );
}

function bfw_get_bill_state($post_id, $bill_id) {
   global $wpdb;

   $table_name = $wpdb->prefix . 'bfw';

   $row = $wpdb->get_row(
      $wpdb->prepare( "SELECT state FROM $table_name WHERE bill_id = %s AND post_id = %d", $bill_id, $post_id )
   );

   if (isset($row->state)){
      return $row->state;
   } else {
      return '';
   }
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