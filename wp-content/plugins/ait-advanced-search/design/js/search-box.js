aitAdvancedSearchInit();
watchLocationInput();

// auto resize input size if searchform is set to sentence type
if (jQuery('body').hasClass('search-form-type-2')) {
	aitAdvancedSearchDynamicWidth();
}

function aitAdvancedSearchInit() {
	var input = document.getElementById('location-address');
	if (input == null) return;
	var searchBox = new google.maps.places.SearchBox(input, {});
	searchBox.addListener('places_changed', function () {
		console.log('google place changed');
		var places = searchBox.getPlaces();

		if (places.length == 0) {
			return;
		}

		var place = places.pop();
		if (!place.geometry) {
			console.log("Returned place contains no geometry");
			return;
		}

		var location = place.geometry.location;

		// trigger click on radius toggle button to initialize hidden inputs
		// advanced-search parameter sent to inform script that geo data is sent from this plugin
		var $container = jQuery('.radius');
		$container.find('input').each(function () {
			jQuery(this).attr('disabled', false);
		});
		$container.addClass('radius-set');
		jQuery(".radius-toggle").trigger('click', [['advanced-search']]);

		jQuery("#latitude-search").attr('value', location.lat());
		jQuery("#longitude-search").attr('value', location.lng());
	});

	// prevent form from being sent on enter
	google.maps.event.addDomListener(input, 'keydown', function (e) {
		if (e.keyCode == 13) {
			e.preventDefault();
		}
	});

	// clear input
	jQuery('.radius-clear').on('click', function () {
		jQuery(input).attr('value', "");
		jQuery(input).text("");
	});

	// button input
	if (jQuery('body').hasClass('search-form-type-3')) {
		var $locationButton = jQuery('.location-search-wrap .location-icon');

		$locationButton.on('click', function () {
			jQuery(this).parent().toggleClass('active');
		});

		$locationButton.parent().focusout(function () {
			jQuery(this).removeClass('active');
		});
	}
}



function aitAdvancedSearchDynamicWidth() {
	var $container = jQuery('.location-search-wrap');
	var $locationInput = jQuery('#location-address');
	var $hiddenDiv = jQuery('<div />').addClass('searchinput').css({ position: 'fixed', height: '1px', visibility: 'hidden', pointerEvents: 'none' }).html($locationInput.attr('placeholder'));
	$container.append($hiddenDiv);

	if ($locationInput.val() != "") {
		$hiddenDiv.html($locationInput.val());
	} else {
		$hiddenDiv.html($locationInput.attr('placeholder'));
	}
	if ($hiddenDiv.width() > 0) {
		$locationInput.width($hiddenDiv.width());
	}

	$locationInput.on('keyup', function () {
		if (jQuery(this).val() != "") {
			$hiddenDiv.html(jQuery(this).val());
		} else {
			$hiddenDiv.html(jQuery(this).attr('placeholder'));
		}

		if ($hiddenDiv.width() <= 150) {
			if (jQuery(this).val() != "") {
				jQuery(this).width($hiddenDiv.outerWidth(true));
			} else {
				jQuery(this).width($hiddenDiv.width());
			}
		}
	});

	$locationInput.on('places_changed', function () {
		$hiddenDiv.html(jQuery(this).val());
		if ($hiddenDiv.width() <= 150) {
			jQuery(this).width($hiddenDiv.width());
		} else {
			jQuery(this).width(150);
		}
	});
}



function watchLocationInput() {
	var $locationInput = jQuery('#location-address');
	$locationInput.keyup(function (event) {
		if (!this.value) {
			jQuery("#latitude-search").attr('value', "");
			jQuery("#longitude-search").attr('value', "");
			// hotfix for 4th search type in businessfinder theme
			if (jQuery('body').hasClass('search-form-type-4')) {
				setGeoData();
			} else {
				jQuery(".radius-toggle").trigger('click', [['radius-already-selected', 'reset-geodata']]);
			}
		}
	});
}