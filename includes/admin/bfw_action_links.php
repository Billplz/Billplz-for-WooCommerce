<?php

defined('ABSPATH') || exit;

function bfw_settings_url() {
  return add_query_arg(
    array(
      'page' => 'wc-settings',
      'tab' => 'checkout',
      'section' => 'billplz'
    ),
    admin_url('admin.php')
  );
}

function bfw_as_327_bill_requery_url() {
  return add_query_arg(
    array(
      'page' => 'wc-status',
      'tab' => 'action-scheduler',
      'status' => 'pending'
    ),
    admin_url('admin.php')
  );
}

function bfw_action_links($actions) {
    $new_actions = array(
      'settings' => sprintf(
        '<a href="%1$s">%2$s</a>',
        bfw_settings_url(),
        esc_html__('Settings', 'bfw')
      )
    );

    if (get_option( 'bfw_327_version' ) != '3.27.2'){

      $url = add_query_arg(
        array(
          'do_requery_bill' => 'true',
        ),
        bfw_as_327_bill_requery_url()
      );

      $new_actions['requery_327_bill'] = sprintf(
        '<a data-method="POST" href="%1$s">%2$s</a>',
        $url,
        esc_html__('Requery', 'bfw')
      );
    }
    
    return array_merge($new_actions, $actions);
}
add_filter('plugin_action_links_' . BFW_BASENAME, 'bfw_action_links');