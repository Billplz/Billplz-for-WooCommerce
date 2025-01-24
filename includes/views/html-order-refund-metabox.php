<?php defined('ABSPATH') || exit; ?>

<div id="bfw-order-refund-form">
    <div class="bfw-metabox-field-container bank-field-container">
        <label for="bank" class="bfw-metabox-label"><?php _e('Bank', 'bfw'); ?><span class="bfw-required">*</span></label>
        <select id="refund-bank" class="bfw-metabox-field">
            <?php
            foreach ( $swift_banks as $bank_code => $bank_name ) {
                echo '<option value="' . esc_attr( $bank_code ) . '">' . esc_html( $bank_name ) . '</option>';
            }
            ?>
        </select>
    </div>
    <div class="bfw-metabox-field-container bank-account-number-field-container">
        <label for="bank-account-number" class="bfw-metabox-label"><?php _e( 'Account Number', 'bfw' ); ?><span class="bfw-required">*</span></label>
        <input id="refund-bank-account-number" type="number" class="bfw-metabox-field" />
    </div>
    <div class="bfw-metabox-field-container name-field-container">
        <label for="bank-account-name" class="bfw-metabox-label"><?php _e( 'Account Name', 'bfw' ); ?><span class="bfw-required">*</span></label>
        <input id="refund-bank-account-name" type="text" class="bfw-metabox-field" />
    </div>
    <div class="bfw-metabox-field-container identity-number-field-container">
        <label for="identity-number" class="bfw-metabox-label"><?php _e( 'IC Number', 'bfw' ); ?><span class="bfw-required">*</span></label>
        <input id="refund-identity-number" type="number" class="bfw-metabox-field" />
    </div>
    <div class="bfw-metabox-field-container amount-field-container">
        <label for="amount" class="bfw-metabox-label"><?php _e( 'Amount', 'bfw'); ?><span class="bfw-required">*</span></label>
        <input id="refund-amount" type="number" class="bfw-metabox-field" step="0.01"/>
    </div>
    <div class="bfw-metabox-field-container description-field-container">
        <label for="description" class="bfw-metabox-label"><?php _e( 'Description', 'bfw' ); ?><span class="bfw-required">*</span></label>
        <input id="refund-description" type="text" class="bfw-metabox-field" value="<?php echo esc_attr( sprintf( __( 'Refund for Order %d', 'bfw' ), $order->get_id() ) ); ?>"/>
    </div>
    <div class="bfw-metabox-submit-container">
        <?php
        $refund_amount = '<span class="wc-order-refund-amount">' . wc_price( 0, array( 'currency' => $order->get_currency() ) ) . '</span>';
        $gateway_name  = false !== $this ? ( ! empty( $this->method_title ) ? $this->method_title : $this->get_title() ) : __( 'Payment gateway', 'woocommerce' );

        echo '<button type="button" class="button button-primary do-bfw-order-refund">' . sprintf( esc_html__( 'Refund %1$s via %2$s', 'woocommerce' ), wp_kses_post( $refund_amount ), esc_html( $gateway_name ) ) . '</button>';
        ?>
    </div>
</div>
