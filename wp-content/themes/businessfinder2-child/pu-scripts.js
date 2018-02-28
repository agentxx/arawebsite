jQuery(function ($) {
    $(document).ready(function(){
        /*if (($('#post-status-display').html())=='Published'){
            $('#ait-opt-metabox-_ait-event-pro_event-pro-data-item').next().find('.chosen-drop').css('display','none')
        }*/
        if (($('#post-status-display').html())!='Published'){
            $('#ait-opt-metabox-_ait-event-pro_event-pro-data-item option:first-child').remove();
        }
        if ($('body').hasClass('ait-easy-admin-enabled')) {
        $('.ait-opt-container.ait-opt-posts-main').css('display','none');
        }
        function GetURLParameter(sParam){
            var sPageURL = window.location.search.substring(1);
            var sURLVariables = sPageURL.split('&');
            for (var i = 0; i < sURLVariables.length; i++){
                var sParameterName = sURLVariables[i].split('=');
                if (sParameterName[0] == sParam){
                    return sParameterName[1];
                }
            }
        }
        var getsource = GetURLParameter('post_type');
        //console.log(getsource);
             $('#post').submit(function(event) {
                if (getsource == 'ait-event-pro') {
                    if (!$('#ait-opt-metabox-_ait-event-pro_event-pro-data-item').val()) {
                        alert('No initiative was created');
                        return false;
                    } else {
                    if (!$('#ait-opt-metabox-_ait-event-pro_event-pro-data-dates-0-datefrom-standard-format').val()) {
                        alert('Dates for Events and Opportunities are mandatory');
                        return false;
                    }
                    if (!$('#ait-opt-metabox-_ait-event-pro_event-pro-data-dates-0-dateto-standard-format').val()) {
                        alert('Dates for Events and Opportunities are mandatory');
                        return false;
                    }
                    }
                }
});
             
    })
})


