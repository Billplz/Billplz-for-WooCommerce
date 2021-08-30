<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; 
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
  exit;
}

delete_option('billplz_fpx_banks');
delete_option('billplz_fpx_banks_last');
delete_option('bfw_api_key_state');
delete_option('bfw_collection_id_state');
delete_transient('bfw_3_21_7_fix');
delete_transient('bfw_3_22_0_fix');

/*
 * Only remove ALL product and page data if BFW_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'BFW_REMOVE_ALL_DATA' ) && true === BFW_REMOVE_ALL_DATA ) {
  delete_option("bfw_db_version");

  global $wpdb;
  $table_name = $wpdb->prefix . 'bfw';
  $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
}
