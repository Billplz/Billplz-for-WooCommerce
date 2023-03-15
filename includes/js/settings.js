jQuery(document).ready(function($) {
    $('#woocommerce_billplz_is_sandbox').on('change', function() {
        var form_table = $(this).closest('.form-table'),
            tr = form_table.find('tr').not(':first');

        if (this.checked) {
            tr.each(function() {
                $(this).fadeIn(200);
            })
        } else {
            tr.each(function() {
                $(this).fadeOut(200);
            })
        }
    });

    $('#woocommerce_billplz_is_sandbox').trigger('change');
});
