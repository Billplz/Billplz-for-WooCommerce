jQuery(document).ready(function($) {
    var wc_order_metaboxes = {
        init: function() {
            $('#woocommerce-order-items')
                .on('change keyup', '.wc-order-refund-items #refund_amount', this.refunds.amount_changed)
                .on('click', 'button.refund-items', this.refunds.change_button_class)
                .on('click', 'button.do-bfw-api-refund', this.refunds.toggle_refund_form);
        },
        refunds: {
            amount_changed: function() {
                $('#bfw-order-refund-metabox #refund-amount').val($(this).val());
            },
            change_button_class: function() {
                $('#woocommerce-order-items button.do-api-refund')
                    .removeClass('do-api-refund')
                    .addClass('do-bfw-api-refund');
            },
            toggle_refund_form: function() {
                $('#bfw-order-refund-metabox').slideToggle();
            }
        },
        block: function() {
            $('#woocommerce-order-items').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },
        unblock: function() {
            $('#woocommerce-order-items').unblock();
        }
    };

    var bfw_order_metaboxes = {
        init: function() {
            $('#bfw-order-refund-metabox').insertAfter('#woocommerce-order-items');

            $('#bfw-order-refund-metabox')
                .hide()
                .on('change keyup', '#refund-amount', this.refunds.amount_changed)
                .on('click', 'button.do-bfw-order-refund', this.refunds.submit);
        },
        refunds: {
            amount_changed: function() {
                $('#woocommerce-order-items .wc-order-refund-items #refund_amount').val($(this).val()).trigger('change');
            },
            submit: function() {
                bfw_order_metaboxes.block();
                wc_order_metaboxes.block();

                var order_refund_form   = $('#bfw-order-refund-form');

                ///////////////////////////////////////////////////////////////////////////////////

                var refund_amount   = $( 'input#refund_amount' ).val();
                var refund_reason   = $( 'input#refund_reason' ).val();
                var refunded_amount = $( 'input#refunded_amount' ).val();

                // Get line item refunds
                var line_item_qtys       = {};
                var line_item_totals     = {};
                var line_item_tax_totals = {};

                $( '.refund input.refund_order_item_qty' ).each(function( index, item ) {
                    if ( $( item ).closest( 'tr' ).data( 'order_item_id' ) ) {
                        if ( item.value ) {
                            line_item_qtys[ $( item ).closest( 'tr' ).data( 'order_item_id' ) ] = item.value;
                        }
                    }
                });

                $( '.refund input.refund_line_total' ).each(function( index, item ) {
                    if ( $( item ).closest( 'tr' ).data( 'order_item_id' ) ) {
                        line_item_totals[ $( item ).closest( 'tr' ).data( 'order_item_id' ) ] = item.value;
                    }
                });

                $( '.refund input.refund_line_tax' ).each(function( index, item ) {
                    if ( $( item ).closest( 'tr' ).data( 'order_item_id' ) ) {
                        var tax_id = $( item ).data( 'tax_id' );

                        if ( !line_item_tax_totals[ $( item ).closest( 'tr' ).data( 'order_item_id' ) ] ) {
                            line_item_tax_totals[ $( item ).closest( 'tr' ).data( 'order_item_id' ) ] = {};
                        }

                        line_item_tax_totals[ $( item ).closest( 'tr' ).data( 'order_item_id' ) ][ tax_id ] = item.value;
                    }
                });

                ///////////////////////////////////////////////////////////////////////////////////

                var bank                = order_refund_form.find('#refund-bank').val();
                var bank_account_number = order_refund_form.find('#refund-bank-account-number').val();
                var bank_account_name   = order_refund_form.find('#refund-bank-account-name').val();
                var description         = order_refund_form.find('#refund-description').val();

                var data = {
                    action                : 'bfw_create_refund',
                    order_id              : woocommerce_admin_meta_boxes.post_id,
                    refund_amount         : refund_amount,
                    refunded_amount       : refunded_amount,
                    refund_reason         : refund_reason,
                    line_item_qtys        : JSON.stringify( line_item_qtys, null, '' ),
                    line_item_totals      : JSON.stringify( line_item_totals, null, '' ),
                    line_item_tax_totals  : JSON.stringify( line_item_tax_totals, null, '' ),
                    restock_refunded_items: $( '#restock_refunded_items:checked' ).length ? 'true': 'false',

                    bank                  : bank,
                    bank_account_number   : bank_account_number,
                    bank_account_name     : bank_account_name,
                    description           : description,

                    security              : bfw_admin_order_metaboxes.create_refund_nonce
                };

                $.ajax({
                    url:      bfw_admin_order_metaboxes.ajax_url,
                    data:     data,
                    type:     'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success === true) {
                            window.alert(bfw_admin_order_metaboxes.refund_success_message);

                            // Redirect to same page for show the refunded status
                            window.location.reload();
                        } else {
                            window.alert(response.data.error);
                            bfw_order_metaboxes.unblock();
                            wc_order_metaboxes.unblock();
                        }
                    },
                    error: function() {
                        bfw_order_metaboxes.unblock();
                        wc_order_metaboxes.unblock();
                    }
                });
            }
        },
        block: function() {
            $('#bfw-order-refund-metabox').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },
        unblock: function() {
            $('#bfw-order-refund-metabox').unblock();
        }
    }

    wc_order_metaboxes.init();
    bfw_order_metaboxes.init();
});
