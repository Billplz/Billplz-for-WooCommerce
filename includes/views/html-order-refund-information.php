<?php
defined('ABSPATH') || exit;

$billplz_payment_order = bfw_get_refund_payment_order( $refund->get_id() );

if ( !$billplz_payment_order ) {
    return;
}
?>

<a href="<?php echo esc_url( $billplz_payment_order['url'] ); ?>" target="_blank" id="bfw-order-refund-information">
    <span class="bfw-order-refund-label"><?php esc_html_e( 'Billplz Refund ID :', 'bfw' ); ?></span>
    <span class="bfw-order-refund-id"><?php echo esc_html( $billplz_payment_order['id'] ); ?></span>
</a>
