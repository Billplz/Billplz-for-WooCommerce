<?php

defined('ABSPATH') || exit;

/**
 * Since previous Billplz for WooCommerce version doesn't have some settings,
 * it may return undefined index if the error reporting is on.
 *
 * To immediately fix this issue without having two step update, we need to
 * use transient based and update the existing value immediately.
 *
 * This function should be removed on a later version.
 *
 * @return void
 */
// function bfw_3_21_7_fix()
// {
//     if (!get_transient('bfw_3_21_7_fix')) {
//         if ($settings = get_option('woocommerce_billplz_settings')) {
//             $settings['checkout_label'] = isset($settings['checkout_label']) ? $settings['checkout_label'] : '';
//             $settings['reference_1_label'] = isset($settings['reference_1_label']) ? $settings['reference_1_label'] : '';
//             $settings['reference_1'] = isset($settings['reference_1']) ? $settings['reference_1'] : '';
//             $settings['has_fields'] = isset($settings['has_fields']) ? $settings['has_fields'] : 'no';
//         }
//         update_option('woocommerce_billplz_settings', $settings);
//         set_transient('bfw_3_21_7_fix', 1);
//     }

//     //delete_transient('bfw_3_21_7_fix');
// }

// add_action('init', 'bfw_3_21_7_fix');

/**
 * Remove unused option since we are using transient
 */

// function bfw_3_22_0_fix()
// {
//     if (!get_transient('bfw_3_22_0_fix')) {
//         delete_option('billplz_fpx_banks');
//         delete_option('billplz_fpx_banks_last');
//         set_transient('bfw_3_22_0_fix', 1);
//     }
// }

// add_action('init', 'bfw_3_22_0_fix');
