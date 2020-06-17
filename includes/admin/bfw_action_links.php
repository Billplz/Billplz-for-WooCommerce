<?php

defined('ABSPATH') || exit;

function bfw_action_links($actions)
{
    $new_actions = array(
      'settings' => sprintf(
        '<a href="%1$s">%2$s</a>', admin_url('admin.php?page=wc-settings&tab=checkout&section=billplz'), esc_html__('Settings', 'bfw')
      )
    );
    return array_merge($new_actions, $actions);
}
add_filter('plugin_action_links_' . BFW_BASENAME, 'bfw_action_links');