<?php
defined('ABSPATH') || exit;

$billplz_payment_order = bfw_get_refund_payment_order( $refund->get_id() );

if ( !$billplz_payment_order ) {
    return;
}
?>

<div class="bfw-order-refund-info">
    <?php
    printf(
        __( 'Processed via <span class="bfw-brand-text">Billplz</span> Payment Order<br><strong>Payment Order ID:</strong> <span class="help_tip" aria-label="Status: %3$s" data-tip="Status: %3$s"><a href="%2$s" target="_blank">%1$s</a></span>', 'bfw' ),
        $billplz_payment_order['id'],
        $billplz_payment_order['url'],
        strtoupper( $billplz_payment_order['status'] )
    );
    ?>
</div>
