/* Admin Script */
jQuery(document).ready(function(){

	if(typeof ait !== "undefined"){
		var $context = jQuery('.ait_announcements_bar_options');
		ait.admin.options.Ui.datepicker($context);

		new ait.admin.Tabs(jQuery('#ait-announcements-bar' + '-tabs'), jQuery('#ait-announcements-bar' + '-panels'), 'ait-admin-' + "announcements-bar" + '-page');
	}
});