<?php
defined('ABSPATH') || exit;

class WC_Billplz_Settings extends WC_Settings_API {

    private $error_messages = array();

    public function __construct() {

        $this->id = 'billplz';

        $this->live_api_key          = $this->get_option( 'api_key' );
        $this->live_x_signature      = $this->get_option( 'x_signature' );
        $this->live_collection_id    = $this->get_option( 'collection_id' );

        $this->sandbox_api_key       = $this->get_option( 'sandbox_api_key' );
        $this->sandbox_x_signature   = $this->get_option( 'sandbox_x_signature' );
        $this->sandbox_collection_id = $this->get_option( 'sandbox_collection_id' );

        $this->is_sandbox            = 'yes' === $this->get_option( 'is_sandbox' );
        $this->is_sandbox_admin      = 'yes' === $this->get_option( 'is_sandbox_admin' );

        if ( !$this->is_sandbox ) {
            $this->is_sandbox_admin = false;
        }

        $this->init_messages();
        $this->init_notices();

    }

    public function init() {
        add_action( 'admin_notices', array( $this, 'display_errors' ) );
    }

    private function init_messages() {

        $this->error_messages = array(
            'unsupported_currency'                => sprintf( __( '<strong>Billplz Disabled</strong>: WooCommerce currency option is not supported by Billplz. <a href="%s">Click here to configure</a>', 'bfw' ), get_admin_url( 'admin.php?page=wc-settings&tab=general' ) ),

            'live_api_key_missing'                => $this->key_missing_message( __( 'Live API Key', 'bfw' ) ),
            'live_collection_id_missing'          => $this->key_missing_message( __( 'Live Collection ID', 'bfw' ) ),
            'live_x_signature_missing'            => $this->key_missing_message( __( 'Live XSignature Key', 'bfw' ) ),

            'sandbox_api_key_missing'             => $this->key_missing_message( __( 'Sandbox API Key', 'bfw' ) ),
            'sandbox_collection_id_missing'       => $this->key_missing_message( __( 'Sandbox Collection ID', 'bfw' ) ),
            'sandbox_x_signature_missing'         => $this->key_missing_message( __( 'Sandbox XSignature Key', 'bfw' ) ),

            'live_api_key_invalid_state'          => $this->invalid_state_message( __( 'Live API Key', 'bfw' ) ),
            'live_collection_id_invalid_state'    => $this->invalid_state_message( __( 'Live Collection ID', 'bfw' ) ),

            'sandbox_api_key_invalid_state'        => $this->invalid_state_message( __( 'Sandbox API Key', 'bfw' ) ),
            'sandbox_collection_id_invalid_state'  => $this->invalid_state_message( __( 'Sandbox Collection ID', 'bfw' ) ),
        );

    }

    private function key_missing_message( $error_type ) {
        return sprintf( __( '<strong>Billplz Disabled</strong>: You should set your %1$s in Billplz. <a href="%2$s">Click here to configure</a>', 'bfw' ), $error_type, get_admin_url( 'admin.php?page=wc-settings&tab=checkout&section=billplz' ) );
    }

    private function invalid_state_message( $error_type ) {
        return sprintf( __( '<strong>Billplz Disabled</strong>: %1$s is not valid. <a href="%2$s">Click here to configure</a>', 'bfw' ), $error_type, get_admin_url( 'admin.php?page=wc-settings&tab=checkout&section=billplz' ) );
    }

    private function init_notices() {

        $this->validate_currencies_presence();
        $this->validate_keys_presence();

        // Continue to validate API credentials if no error from previous validation
        if (!$this->get_errors()) {
            $this->check_keys_verification();
        }

    }

    // Add an error message for display in admin on save
    public function add_error( $error ) {

        if ( isset( $this->error_messages[ $error ] ) ) {
            $this->errors[] = $this->error_messages[ $error ];
        } else {
            $this->errors[] = $error;
        }

    }

    // Display admin error messages
    public function display_errors() {

        if ( $this->get_errors() ) {
            foreach ( $this->get_errors() as $error ) {
                echo '<div id="woocommerce_errors" class="error notice"><p>' . wp_kses_post( $error ) . '</p></div>';
            }
        }

    }

    public function is_valid_for_use() {

        if ($this->get_errors()) {
            return false;
        }

        return true;

    }

    private function validate_currencies_presence() {

        $wc_currency = get_woocommerce_currency();
        $supported_currencies = apply_filters( 'bfw_supported_currencies', array( 'MYR' ) );

        if ( !in_array( $wc_currency, $supported_currencies ) ) {
            $this->add_error( 'unsupported_currency' );
        }

    }

    private function validate_keys_presence() {

        if ( !$this->is_sandbox || $this->is_sandbox_admin ) {
            if ( !$this->live_api_key ) {
                $this->add_error( 'live_api_key_missing' );
            }

            if ( !$this->live_collection_id ) {
                $this->add_error( 'live_collection_id_missing' );
            }

            if ( !$this->live_x_signature ) {
                $this->add_error( 'live_x_signature_missing' );
            }
        }

        if ($this->is_sandbox) {
            if ( !$this->sandbox_api_key ) {
                $this->add_error( 'sandbox_api_key_missing' );
            }

            if ( !$this->sandbox_collection_id ) {
                $this->add_error( 'sandbox_collection_id_missing' );
            }

            if ( !$this->sandbox_x_signature ) {
                $this->add_error( 'sandbox_x_signature_missing' );
            }
        }

    }

    private function check_keys_verification() {

        if ( !$this->is_sandbox || $this->is_sandbox_admin ) {
            $live_api_key_state = get_option( 'bfw_api_key_state', 'verified' );
            $live_collection_id_state = get_option( 'bfw_collection_id_state', 'verified' );

            if ( $live_api_key_state !== 'verified' ) {
                $this->add_error( 'live_api_key_invalid_state' );
            }

            if ( $live_collection_id_state !== 'verified' ) {
                $this->add_error( 'live_collection_id_invalid_state');
            }
        }

        if ( $this->is_sandbox ) {
            $sandbox_api_key_state = get_option( 'bfw_sandbox_api_key_state', 'verified' );
            $sandbox_collection_id_state = get_option( 'bfw_sandbox_collection_id_state', 'verified' );

            if ( $sandbox_api_key_state !== 'verified' ) {
                $this->add_error( 'sandbox_api_key_invalid_state' );
            }

            if ( $sandbox_collection_id_state !== 'verified' ) {
                $this->add_error( 'sandbox_collection_id_invalid_state');
            }
        }

    }
}

add_action( 'woocommerce_settings_page_init', function() {
    $settings = new WC_Billplz_Settings();
    $settings->init();
} );
