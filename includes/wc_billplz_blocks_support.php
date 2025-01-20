<?php
defined('ABSPATH') || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Billplz_Blocks_Support extends AbstractPaymentMethodType {

    private $gateway;

    protected $name = 'billplz';

    // Initializes the payment method type
    public function initialize() {

        $this->settings = get_option( 'woocommerce_billplz_settings', array() );

        $bfw_icon = plugins_url("assets/images/billplz-logo-black.png", BFW_PLUGIN_FILE);
        $this->settings['bfw_icon'] = apply_filters('bfw_checkout_block_icon', $bfw_icon);

        $this->gateway  = new WC_Billplz_Gateway();

    }

    // Checks if the payment method is active and available for use
    public function is_active() {
        return $this->gateway->is_available();
    }

    // Scripts/handles to be registered for this payment method
    public function get_payment_method_script_handles() {

        wp_register_script( 'wc-billplz-blocks', BFW_PLUGIN_URL . '/assets/build/frontend/blocks.js', array(), BFW_PLUGIN_VER, true );

        return array( 'wc-billplz-blocks' );

    }

    // Data for payment method script
    public function get_payment_method_data() {

        return array(
            'name'        => $this->name,
            'title'       => $this->get_setting( 'title' ),
            'description' => $this->get_setting( 'description' ),
            'icon'        => $this->get_setting( 'bfw_icon' ),
            'supports'    => array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) )
        );

    }

}
