<?php 
	
	/*
		avalaible variables:
		> $oldItemMeta
		> $featured
	*/

	$meta = $oldItemMeta;

	/* Address conversion */
	$address = isset($meta['address']) ? $meta['address'] : "";
	// convert the string from anything to utf8 format
	$address = mb_convert_encoding($address, "UTF-8", "auto");
	// remove new lines from address
	$address = trim(preg_replace('/\s+/', ' ',  $address));
	// remove all quotes from string
	$address = str_replace(array('"',"'"), "", $address);
	/* Address conversion */

	/* Web address fix */
	$web = isset($meta['web']) ? $meta['web'] : "";
	// add http:// to the address
	$web = $web != "" && strpos($web,'http://') !== false ? $web : "http://".$web;
	/* Web address fix */

	return array(
		'subtitle' => "",
		'featuredItem' => isset($featured) ? $featured : 0,
		'headerType' => "map",
		'headerImage' => "",
		'map' => array(
			'address' => $address,
			'latitude' => isset($meta['gpsLatitude']) ? $meta['gpsLatitude'] : 1,
			'longitude' => isset($meta['gpsLongitude']) ? $meta['gpsLongitude'] : 1,
			'streetview' => isset($meta['showStreetview']) ? $meta['showStreetview'] : 0,
			'swheading' => isset($meta['streetViewHeading']) ? $meta['streetViewHeading'] : 0,
			'swpitch' => isset($meta['streetViewPitch']) ? $meta['streetViewPitch'] : 0,
			'swzoom' => isset($meta['streetViewZoom']) ? $meta['streetViewZoom'] : 0,
		),
		'telephone' => isset($meta['telephone']) ? $meta['telephone'] : "",
		'telephoneAdditional' => "",
		'email' => isset($meta['email']) ? $meta['email'] : "",
		'showEmail' => 1,
		'contactOwnerBtn' => 1,
		'web' => $web,
		'webLinkLabel' => "",
		'displayOpeningHours' => 1,
		'openingHoursMonday' => isset($meta['hoursMonday']) ? $meta['hoursMonday'] : "",
		'openingHoursTuesday' => isset($meta['hoursTuesday']) ? $meta['hoursTuesday'] : "",
		'openingHoursWednesday' => isset($meta['hoursWednesday']) ? $meta['hoursWednesday'] : "",
		'openingHoursThursday' => isset($meta['hoursThursday']) ? $meta['hoursThursday'] : "",
		'openingHoursFriday' => isset($meta['hoursFriday']) ? $meta['hoursFriday'] : "",
		'openingHoursSaturday' => isset($meta['hoursSaturday']) ? $meta['hoursSaturday'] : "",
		'openingHoursSunday' => isset($meta['hoursSunday']) ? $meta['hoursSunday'] : "",
		'openingHoursNote' => "",
		'displaySocialIcons' => 0,
		'socialIconsOpenInNewWindow' => 1,
		'socialIcons' => "",
		'displayGallery' => 0,
		'gallery' => "",
		'displayFeatures' => 0,
		'features' => "",
		'customFields' => "",
	);


?>