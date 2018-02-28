jQuery(document).ready(function(){

	if(typeof ait !== "undefined"){
		// admin tabs init .. must refactor in future
		new ait.admin.Tabs(jQuery('#ait-permissions-manager' + '-tabs'), jQuery('#ait-permissions-manager' + '-panels'), 'ait-admin-' + "permissions-manager" + '-page');
	}

	//action-indicator-save
	jQuery('.ait_permissions_manager_options').find('.ait-save-permissions-manager-options').on('click', function(e){
		e.preventDefault();

		var $saveIndicator = jQuery('.ait_permissions_manager_options').find("#action-indicator-save");
		$saveIndicator.show();
		$saveIndicator.addClass('action-working');

		var settings = {};
		settings['roles'] = {};

		jQuery('.ait_permissions_manager_options').find('.ait-options-panel:not(.ait-custom-capabilities-manager)').each(function(){
			var role = jQuery(this).attr('data-role');
			settings['roles'][role] = {};

			jQuery(this).find('.ait-options-section').each(function(){
				var section = jQuery(this).attr('data-section');
				settings['roles'][role][section] = {};

				jQuery(this).find('.ait-opt-container').each(function(){
					var cap_key = jQuery(this).attr('data-capability');
					if(jQuery(this).hasClass('ait-opt-on-off-main')){
						var cap_val = jQuery(this).find('select').val() == "on" ? true : false;
					} else {
						var cap_val = jQuery(this).find('textarea').val();
					}

					settings['roles'][role][section][cap_key] = cap_val;
				});

			});

		});

		settings['custom_capabilities'] = [];

		jQuery('.ait_permissions_manager_options').find('.ait-options-section[data-section=custom_capabilities] .ait-clone-item').each(function(){
			
			var capability = {};
			jQuery(this).find('.ait-opt-container').each(function(){
				var key = jQuery(this).attr('data-db-key');
				// ensure slug is in lowercase format with spaces replaced with underscores
				var value = key === "slug" ? jQuery(this).find('input').val().trim().replace(new RegExp(' ', 'g'), '_').toLowerCase() : jQuery(this).find('input').val();

				capability[key] = value;
			});

			settings['custom_capabilities'].push(capability);
		});

		// here ajax save function
		jQuery.post(ajaxurl, {
			'action': 'aitPermissionManagerSaveOptions',
			'data': settings
		}).done(function(xhr){
			$saveIndicator.addClass('action-done').fadeIn().delay(2000).fadeOut(100, function(){
				$saveIndicator.removeClass('action-working action-done action-error');
			});
		}).fail(function(xhr){
			// server fail
			console.error('AIT Permissions Manager: Ajax failed');
		});

	});

	// reset role capabilities
	jQuery('.ait_permissions_manager_options').find('.ait-reset-role-options').on('click', function(e){
		e.preventDefault();

		var settings = {};
		settings['role'] = jQuery(this).attr('data-role');

		if(window.confirm( jQuery(this).attr('data-message') )){
			var $saveIndicator = jQuery(this).find('.action-indicator');
			$saveIndicator.addClass('action-working');

			jQuery.post(ajaxurl, {
				'action': 'aitPermissionManagerResetRole',
				'data': settings
			}).done(function(xhr){

				// check xhr for errors
				if(xhr.status.fail){
					console.error('AIT Permissions Manager: '+xhr.status.msg);
				} else {
					$saveIndicator.removeClass('action-working');
					location.reload();
				}

			}).fail(function(xhr){
				// server fail
				console.error('AIT Permissions Manager: Ajax failed');
			});
		}
	});

});