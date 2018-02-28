jQuery(function () {
//date-picker
    jQuery(".datepicker").datepicker({dateFormat: 'yy-mm-dd'});
    jQuery("#date_to").datepicker({dateFormat: 'yy-mm-dd'});
    jQuery("#date_from").datepicker({dateFormat: 'yy-mm-dd'}).bind("change", function () {
        var minValue = jQuery(this).val();
        minValue = jQuery.datepicker.parseDate("yy-mm-dd", minValue);
        minValue.setDate(minValue.getDate() + 0);
        jQuery("#date_to").datepicker("option", "minDate", minValue);
    });
//ajax-timezone
    jQuery("#select_timezone").change(function () {
        jQuery.ajax({
            url: ulh_custom_object.ajax_url,
            method: 'post',
            data: {
                timezone: jQuery(this).val(), 
                secure_nonce:ulh_custom_object.ajax_nonce , 
                action: 'ulh_public_select_timezone'
            },
            success: function () {
                window.location.reload();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert(ulh_custom_object.internal_error_message);
            }
        });
    });
});