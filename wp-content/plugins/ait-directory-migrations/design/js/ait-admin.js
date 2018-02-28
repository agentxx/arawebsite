/* Admin Script */
jQuery(document).ready(function(){

	if(typeof ait !== "undefined"){
		new ait.admin.Tabs(jQuery('#ait-migration' + '-tabs'), jQuery('#ait-migration' + '-panels'), 'ait-admin-' + "migration" + '-page');
	}
});