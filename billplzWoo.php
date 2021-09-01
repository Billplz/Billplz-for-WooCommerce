<?php

/**
 * Plugin Name: Billplz for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/billplz-for-woocommerce/
 * Description: Billplz. Fair payment platform.
 * Author: Billplz Sdn Bhd
 * Author URI: http://github.com/billplz/billplz-for-woocommerce
 * Version: 3.27.2
 * Requires PHP: 7.0
 * Requires at least: 4.6
 * License: GPLv3
 * Text Domain: bfw
 * Domain Path: /languages/
 * WC requires at least: 3.0
 * WC tested up to: 5.6
 */

defined('ABSPATH') || exit;

class Woocommerce_Billplz {

  private static $instance;
  public $notices = array();

  public static function get_instance() {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function __clone() {}

  protected function __construct() {
    $this->define_constants();

    add_action('admin_init', array(&$this, 'check_environment'));
    add_action('admin_notices', array(&$this, 'admin_notices'), 15);
    add_action('plugins_loaded', array(&$this, 'init'));
    add_filter('option_woocommerce_billplz_settings', array(&$this, 'patch_keys_constant'), 10, 2);
  }

  private function define_constants() {
    $this->define( 'BFW_MIN_WOOCOMMERCE_VER', '3.0' );
    $this->define( 'BFW_MIN_PHP_VER',  '7.0' );
    $this->define( 'BFW_PLUGIN_FILE',  __FILE__ );
    $this->define( 'BFW_PLUGIN_URL', plugin_dir_url(BFW_PLUGIN_FILE));
    $this->define( 'BFW_PLUGIN_DIR',  dirname(BFW_PLUGIN_FILE) );
    $this->define( 'BFW_BASENAME',  plugin_basename(BFW_PLUGIN_FILE) );
  }

  public function check_environment() {
    $environment_warning = self::get_environment_warning();
    if ($environment_warning && is_plugin_active(BFW_BASENAME)) {
      deactivate_plugins(BFW_BASENAME);
      $this->add_admin_notice('bad_environment', 'error', $environment_warning);
      if (isset($_GET['activate'])) {
        unset($_GET['activate']);
      }
    }
    $is_woocommerce_active = class_exists('WooCommerce');
    if (is_admin() && current_user_can('activate_plugins') && !$is_woocommerce_active) {
      $this->add_admin_notice('prompt_bfw_activate', 'error', sprintf(__('<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">WooCommerce</a> plugin installed and activated for Billplz to activate.', 'bfw'), 'https://woocommerce.com'));
      deactivate_plugins(BFW_BASENAME);
      if (isset($_GET['activate'])) {
        unset($_GET['activate']);
      }
      return false;
    }
    if (defined('WC_VERSION') && version_compare(WC_VERSION, BFW_MIN_WOOCOMMERCE_VER, '<')) {
      $this->add_admin_notice('prompt_woocommerce_version_update', 'error', sprintf(__('<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">WooCommerce</a> core version %s+ for the Billplz for WooCommerce add-on to activate.', 'bfw'), 'https://woocommerce.com', BFW_MIN_WOOCOMMERCE_VER));
      deactivate_plugins(BFW_BASENAME);
      if (isset($_GET['activate'])) {
        unset($_GET['activate']);
      }
      return false;
    }
  }

  public function init() {
    if (self::get_environment_warning()) {
      return;
    }

    $this->load_plugin_textdomain();

    $this->includes();
  }

  public function patch_keys_constant($value, $option_name){
    if ($option_name != 'woocommerce_billplz_settings'){
      return $value;
    }

    if (defined('BFW_API_KEY')) {
      $value['api_key'] = BFW_API_KEY;
    }

    if (defined('BFW_COLLECTION_ID')) {
      $value['collection_id'] = BFW_COLLECTION_ID;
    }

    if (defined('BFW_X_SIGNATURE')) {
      $value['x_signature'] = BFW_X_SIGNATURE;
    }

    return $value;
  }

  public function load_plugin_textdomain()
  {
    load_plugin_textdomain('bfw', false, BFW_PLUGIN_DIR . '/languages/');
  }

  public function add_admin_notice($slug, $class, $message)
  {
      $this->notices[$slug] = array(
          'class' => $class,
          'message' => $message,
      );
  }

  public function admin_notices()
  {
    $allowed_tags = array(
      'a' => array(
        'href' => array(),
        'title' => array(),
        'class' => array(),
        'id' => array()
      ),
      'br' => array(),
      'em' => array(),
      'span' => array(
        'class' => array(),
      ),
      'strong' => array(),
    );
    foreach ((array) $this->notices as $notice_key => $notice) {
      echo "<div class='" . esc_attr($notice['class']) . "'><p>";
      echo wp_kses($notice['message'], $allowed_tags);
      echo '</p></div>';
    }
  }

  private function includes() {
    if (!class_exists('WooCommerce')) {
      return false;
    }

    // database model must load first to be used by others
    include BFW_PLUGIN_DIR . '/database/model.php';
    include BFW_PLUGIN_DIR . '/database/upgrade.php';

    if (is_admin()) {
      include BFW_PLUGIN_DIR . '/includes/admin/bfw_action_links.php';
      include BFW_PLUGIN_DIR . '/includes/admin/bfw_delete_order.php';
      include BFW_PLUGIN_DIR . '/includes/admin/bfw_requery_meta_box.php';
      include BFW_PLUGIN_DIR . '/includes/admin/bfw_requery_button.php';
      include BFW_PLUGIN_DIR . '/includes/admin/bfw_requery_button_in_order_page.php';
    }

    // ensure are able to be loaded regardless of admin status as it may loaded with or without admin flag
    include BFW_PLUGIN_DIR . '/includes/admin/bfw_settings.php';

    include BFW_PLUGIN_DIR . '/includes/helpers/billplz_api.php';
    include BFW_PLUGIN_DIR . '/includes/helpers/billplz_wpconnect.php';
    include BFW_PLUGIN_DIR . '/includes/helpers/billplz_bank_name.php';

    include BFW_PLUGIN_DIR . '/includes/wc_billplz_gateway.php';
    include BFW_PLUGIN_DIR . '/includes/wc_bill_inquiry.php';
  }

  private function define( $name, $value ) {
    if ( ! defined( $name ) ) {
      define( $name, $value );
    }
  }

  public static function activation_check() {
    $environment_warning = self::get_environment_warning(true);
    if ($environment_warning) {
      deactivate_plugins(BFW_BASENAME);
      wp_die($environment_warning);
    }
  }

  public static function get_environment_warning($during_activation = false) {
    if (version_compare(phpversion(), BFW_MIN_PHP_VER, '<')) {
      if ($during_activation) {
        $message = __('The plugin could not be activated. The minimum PHP version required for this plugin is %1$s. You are running %2$s. Please contact your web host to upgrade your server\'s PHP version.', 'bfw');
      } else {
        $message = __('The plugin has been deactivated. The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'bfw');
      }
      return sprintf($message, BFW_MIN_PHP_VER, phpversion());
    }

    if (!function_exists('curl_init')) {
      if ($during_activation) {
        return __('The plugin could not be activated. cURL is not installed. Please contact your web host to install cURL.', 'bfw');
      }
      return __('The plugin has been deactivated. cURL is not installed. Please contact your web host to install cURL.', 'bfw');
    }

    if (!class_exists('WC_Payment_Gateway')) {
      if ($during_activation) {
        return __('The plugin could not be activated. Billplz for WooCommerce depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work.', 'bfw');
      }
      return __('The plugin has been deactivated. Billplz for WooCommerce depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work.', 'bfw');
    }

    return false;
  }
}
$GLOBALS['woocommerce_billplz'] = Woocommerce_Billplz::get_instance();
register_activation_hook(__FILE__, array('Woocommerce_Billplz', 'activation_check'));