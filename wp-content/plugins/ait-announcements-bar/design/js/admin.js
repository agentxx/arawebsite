/* AIT Plugin Product Questions */
/* Admin Script */
jQuery(document).ready(function(){

	jQuery('#announcements-bar-save-options').on('click', function(e){
		e.preventDefault();
		$('#action-indicator-save').addClass('action-working').show();
		jQuery('#ait-announcements-bar-page form').trigger('submit');
	});

});