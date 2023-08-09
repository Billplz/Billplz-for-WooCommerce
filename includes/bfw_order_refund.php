<?php

defined('ABSPATH') || exit;

function bfw_get_refund_payment_order_defaults() {

    return array(
        'id'            => '',
        'collection_id' => '',
        'status'        => '',
        'sandbox'       => false,
    );

}

function bfw_get_refund_payment_order( $refund_id ) {

    $refund = wc_get_order( $refund_id );

    if ( !$refund ) {
        return false;
    }

    $payment_order_defaults = bfw_get_refund_payment_order_defaults();

    $payment_order_data = $refund->get_meta( 'bfw_order_refund_payment_order' );
    $payment_order_data = wp_parse_args( $payment_order_data, $payment_order_defaults );

    if ( !$payment_order_data['id'] ) {
        return false;
    }

    if ( $payment_order_data['sandbox'] ) {
        $payment_order_data['url'] = 'https://dashboard.billplz-sandbox.com/';
    } else {
        $payment_order_data['url'] = 'https://dashboard.billplz.com/';
    }

    $payment_order_data['url'] .= "enterprise/payment_order_collections/{$payment_order_data['collection_id']}/payment_orders/{$payment_order_data['id']}";

    return $payment_order_data;

}

function bfw_get_refund_payment_order_meta( $refund_id, $key = 'id' ) {

    $refund = wc_get_order( $refund_id );

    if ( !$refund ) {
        return false;
    }

    $payment_order_data = $refund->get_meta( 'bfw_order_refund_payment_order' );

    return isset( $payment_order_data[ $key ] ) ? $payment_order_data[ $key ] : null;

}

function bfw_update_refund_payment_order( $refund_id, array $payment_order_data ) {

    $refund = wc_get_order( $refund_id );

    if ( !$refund ) {
        return false;
    }

    $payment_order_defaults = bfw_get_refund_payment_order_defaults();
    $payment_order_data = wp_parse_args( $payment_order_data, $payment_order_defaults );

    if ( !$payment_order_data['id'] ) {
        return false;
    }

    $order->update_meta_data( 'bfw_order_refund_payment_order', $payment_order_data );

    return $order->save();

}

add_action( 'bfw_check_refund_payment_order', 'bfw_check_refund_payment_order', 10, 2 );
function bfw_check_refund_payment_order( $order_id, $refund_id ) {

    global $bfw_connect, $bfw_api;

    $order = wc_get_order( $order_id );

    if ( !$order ) {
        return false;
    }

    $refund = wc_get_order( $refund_id );

    if ( !$refund ) {
        return false;
    }

    $payment_order_id = bfw_get_refund_payment_order_meta( $refund_id, 'id' );

    if ( !$payment_order_id ) {
        return false;
    }

    $settings    = get_option( 'woocommerce_billplz_settings' );
    $api_key     = isset( $settings['api_key'] ) ? $settings['api_key'] : null;
    $x_signature = isset( $settings['x_signature'] ) ? $settings['x_signature'] : null;
    $is_sandbox  = isset( $settings['is_sandbox'] ) ? $settings['is_sandbox'] === 'yes' : false;

    if ( !$api_key || !$x_signature ) {
        return false;
    }

    try {
        $bfw_connect->set_api_key( $api_key, $is_sandbox );
        $bfw_api->set_connect( $bfw_connect );

        $args = array( 'epoch' => time() );

        $checksum_data = array(
            $payment_order_id,
            $args['epoch'],
        );

        $args['checksum'] = hash_hmac( 'sha512', implode( '', $checksum_data ), $x_signature );

        list( $rheader, $rbody ) = $bfw_api->toArray( $bfw_api->getPaymentOrder( $payment_order_id, $args ) );

        if ( isset( $rbody['error']['message'] ) ) {
            if ( is_array( $rbody['error']['message'] ) ) {
                $error_message = implode( ', ', array_map( 'sanitize_text_field', $rbody['error']['message'] ) );
            } else {
                $error_message = sanitize_text_field( $rbody['error']['message'] );
            }

            throw new Exception( sprintf( esc_html__( 'Error retrieving refund payment data: %s.', 'bfw' ), $error_message ) );
        }

        if ( $rheader != 200 ) {
            throw new Exception( esc_html__( 'Error retrieving refund payment data. Please try again.', 'bfw' ) );
        }

        $payment_order_data = array(
            'id'            => isset( $rbody['id'] ) ? sanitize_text_field( $rbody['id'] ) : '',
            'collection_id' => isset( $rbody['payment_order_collection_id'] ) ? sanitize_text_field( $rbody['payment_order_collection_id'] ) : '',
            'status'        => isset( $rbody['status'] ) ? sanitize_text_field( $rbody['status'] ) : '',
            'sandbox'       => $is_sandbox,
        );

        $payment_order_defaults = bfw_get_refund_payment_order_defaults();
        $payment_order_data = wp_parse_args( $payment_order_data, $payment_order_defaults );

        if ( $payment_order_data['id'] && $payment_order_data['collection_id'] && $payment_order_data['status'] ) {
            if ( method_exists( 'WC_Billplz_Gateway', 'complete_refund_process' ) ) {
                WC_Billplz_Gateway::complete_refund_process( $order, $refund, $payment_order_data );
                return true;
            }
        } else {
            throw new Exception( esc_html__( 'Error retrieving refund payment data: Invalid response', 'bfw' ) );
        }
    } catch ( Exception $e ) {
    }

    return false;

}
