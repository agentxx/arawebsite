jQuery(document).ready(function(){

	if(typeof ait !== "undefined"){
		// admin tabs init .. must refactor in future
		new ait.admin.Tabs(jQuery('#ait-item-extension' + '-tabs'), jQuery('#ait-item-extension' + '-panels'), 'ait-admin-' + "item-extension" + '-page');
	}

	//action-indicator-save
	jQuery('.ait_item_extension_options').find('.ait-save-item-extension-options').on('click', function(){

		var $saveIndicator = jQuery('.ait_item_extension_options').find("#action-indicator-save");
		$saveIndicator.show();
		$saveIndicator.addClass('action-working');

		var settings = {};

		/* add general settings */
		settings['general_settings'] = {};

		var $generalSettingsContainer = jQuery('.ait_item_extension_options').find('#ait-item-extension-general-settings-panel');
		$generalSettingsContainer.find('.ait-opt-container').each(function(){
			var gKey = jQuery(this).attr('data-db-key');
			var gVal = {};

			if(jQuery(this).hasClass('ait-opt-on-off-main')){
				// plain select get the value from the .chosen conatiner
				gVal = jQuery(this).find('select option:selected').val();
			} else {
				jQuery(this).find('input, textarea').each(function(){
					var lang = jQuery(this).attr('data-lang');
					if(typeof lang !== "undefined"){
						gVal[lang] = jQuery(this).val();
					} else {
						gVal = jQuery(this).val();
					}
				});
			}

			settings['general_settings'][gKey] = gVal;
		});

		/* add general settings */


		jQuery('.ait_item_extension_options').find('.ait-options-panel').each(function(){
			var $cloneContainer = jQuery(this).find('.ait-clone-controls');
			if(typeof $cloneContainer.attr('data-db-key') !== "undefined"){

				settings[$cloneContainer.attr('data-db-key')] = [];
				$cloneContainer.find('.ait-clone-item').each(function(){
					// each clonable input
					var clonableInput = {};

					jQuery(this).find('.ait-opt-container').each(function(){
						// each option
						var inputKey = jQuery(this).attr('data-db-key');
						var inputVal = {};
						if(jQuery(this).hasClass('ait-opt-select-main')){
							// plain select get the value from the .chosen conatiner
							inputVal = jQuery(this).find('.chosen').val();
						} else {
							jQuery(this).find('input').each(function(){
								var lang = jQuery(this).attr('data-lang');
								if(typeof lang !== "undefined"){
									inputVal[lang] = jQuery(this).val();
								} else {
									inputVal = jQuery(this).val();
								}
							});
						}

						clonableInput[inputKey] = inputVal;
					});

					settings[$cloneContainer.attr('data-db-key')].push(clonableInput);

				});

			}
		});

		// here ajax save function
		jQuery.post(ajaxurl, {
			'action': 'aitExtensionSaveGeneralOptions',
			'data': settings
		}).done(function(xhr){
			$saveIndicator.addClass('action-done').fadeIn().delay(2000).fadeOut(100, function(){
				$saveIndicator.removeClass('action-working action-done action-error');
			});
		}).fail(function(xhr){
			// server fail
			console.error('AIT Item Extension: Ajax failed');
		});

	});

});