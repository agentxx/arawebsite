<?php

return array(
	'event' => array(
		'title' => __('Event Detail', 'ait-events-pro'),
		'options' => array(
			array('section' => array('title' => __('Images Options', 'ait-events-pro'))),
			'noFeatured' => array(
				'label' 	=> __('Default Featured Image', "ait-events-pro"),
				'type'		=> 'image',
				'default'	=> '/design/img/default_featured_event.jpg',
				'help'		=> __("Default image for items without own featured image", 'ait-events-pro'),
			),
			'noHeader' => array(
				'label'		=> __('Default Header Image', "ait-events-pro"),
				'type'		=> 'image',
				'default'	=> '/design/img/default_featured_event.jpg',
				'help'		=> __("Default image for items without own header image", 'ait-events-pro'),
			),
			array('section' => array('title' => __('Map Options', 'ait-events-pro'))),
			'mapZoom' => array(
				'label' 	=> __('Map Zoom', "ait-events-pro"),
				'type'		=> 'range',
				'min'		=> '1',
				'max'		=> '21',
				'default'	=> '18',
				'help'		=> __("Zoom level of Google Map", 'ait-events-pro'),
			),
			'mapDisplayLandscapeShow' => array(
				'label' 	=> __('Display landscape', "ait-events-pro"),
				'type'		=> 'on-off',
				'default'	=> 'on',
			),
			'mapDisplayAdministrativeShow' => array(
				'label' 	=> __('Display administratives', "ait-events-pro"),
				'type'		=> 'on-off',
				'default'	=> 'on',
			),
			'mapDisplayRoadsShow' => array(
				'label' 	=> __('Display roads', "ait-events-pro"),
				'type'		=> 'on-off',
				'default'	=> 'on',
			),
			'mapDisplayWaterShow' => array(
				'label' 	=> __('Display water', "ait-events-pro"),
				'type'		=> 'on-off',
				'default'	=> 'on',
			),
			'mapDisplayPoiShow' => array(
				'label' 	=> __('Display poi', "ait-events-pro"),
				'type'		=> 'on-off',
				'default'	=> 'on',
			),
			array('section' => array('title' => __('Address Options', 'ait-events-pro'))),
			'addressHideEmptyFields' => array(
				'label' 	=> __('Hide Empty Values', "ait-events-pro"),
				'type'		=> 'on-off',
				'default'	=> 'off',
				'help'		=> __("Hide empty contact information", 'ait-events-pro'),
			),
			'addressHideGpsField' => array(
				'label' 	=> __('Hide GPS', "ait-events-pro"),
				'type'		=> 'on-off',
				'default'	=> 'off',
				'help'		=> __("Hide GPS information", 'ait-events-pro'),
			),
			'addressWebNofollow' => array(
				'label' 	=> __('Nofollow Web Link', "ait-events-pro"),
				'type'		=> 'on-off',
				'default'	=> 'off',
				'help'		=> __("Use nofollow attribute in web link for SEO purposes", 'ait-events-pro'),
			),
		),
	),
	'category' => array(
		'title' => __('Event Category', 'ait-events-pro'),
		'options' => array(
			array('section' => array('title' => __('Images Options', 'ait-events-pro'))),
			'categoryDefaultIcon' => array(
				'label' 	=> __('Category Default Icon', "ait-events-pro"),
				'type'		=> 'image',
				'default'	=> '/design/img/categories/category-event_default.png',
				'help'		=> __("Default icon for categories without own icon", 'ait-events-pro'),
			),
			'categoryDefaultPin' => array(
				'label'		=> __('Category Default Map Marker', "ait-events-pro"),
				'type'		=> 'image',
				'default'	=> '/design/img/pins/default-event_pin.png',
				'help'		=> __("Default marker for categories without own map marker", 'ait-events-pro'),
			),
			'categoryDefaultImage' => array(
				'label' 	=> __('Category Default Image', "ait-events-pro"),
				'type'		=> 'image',
				'default'	=> '/design/img/default_featured_event.jpg',
				'help'		=> __("Default image for categories without own header image", 'ait-events-pro'),
			),
		),
	),
	'sorting' => array(
		'title' => __('Sorting', 'ait-events-pro'),
		'options' => array(
			'sortingDefaultCount' => array(
				'label' 	=> __('Number of Items', "ait-events-pro"),
				'type'		=> 'number',
				'default'	=> '12',
				'help'		=> __("Number of items listed on one page", 'ait-events-pro'),
			),
			'sortingDefaultOrderBy' => array(
				'label' 	=> __('Order By', "ait-events-pro"),
				'type'		=> 'select',
				'selected'	=> 'date',
				'default'	=> array(
					'date' 		=> __('Creation Date', "ait-events-pro"),
					'eventDate'	=> __('Event Date', "ait-events-pro"),
					'title' 	=> __('Title', "ait-events-pro"),
				),
				'help'		=> __("Select order of items listed on page", 'ait-events-pro'),
			),
			'sortingDefaultOrder' => array(
				'label' 	=> __('Order', "ait-events-pro"),
				'type'		=> 'select',
				'selected'	=> 'ASC',
				'default'	=> array(
					'ASC' 		=> 'ASC',
					'DESC' 		=> 'DESC',
				),
				'help'		=> __("Select order of items listed on page", 'ait-events-pro'),
			),
		),
	),

);
