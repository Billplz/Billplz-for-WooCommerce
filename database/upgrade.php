<?php

defined('ABSPATH') || exit;

$bfw_db_version = "1.0.0";

if ( get_option( 'bfw_db_version' ) != $bfw_db_version){
   global $wpdb;
   $charset_collate = $wpdb->get_charset_collate();

   $table_name = $wpdb->prefix . "bfw"; 

   $sql = "CREATE TABLE $table_name (
     id bigint NOT NULL AUTO_INCREMENT,
     created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
     updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
     post_id bigint NOT NULL,
     bill_id varchar(191) NOT NULL,
     state varchar(191) NOT NULL,
     PRIMARY KEY  (id),
     UNIQUE KEY bill_id_slug (bill_id)
   ) $charset_collate;";

   require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
   dbDelta( $sql );

   add_option( 'bfw_db_version', $bfw_db_version );
}
