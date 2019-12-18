jQuery(document).ready(function($) {
    $('#requery-single').click(function(){
        $('#status_callback').text('Scanning...');
	    var data = {
		    'action': 'bfw_requery_single',
		    'bill_id': $('#bill_id').val(),
        'order_id': $('#order_id').val()
	   };
	   // We can also pass the url value separately from ajaxurl for front end AJAX implementations
	   jQuery.post(ajax_object.ajax_url, data, function(response) {
		    $('#status_callback').html(response);
	   });
       return false;
   });
});
