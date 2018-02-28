<?php

return array(

	'headerType' => array(
		'label' => __('Event Header', 'ait-events-pro'),
		'type' => 'select',
		'selected' => 'image',
		'default' => array(
			'none'  => __('No header', 'ait-events-pro'),
			'map'   => __('Map', 'ait-events-pro'),
			'image' => __('Image', 'ait-events-pro'),
		),
		'help' => __('Select type of header on page', 'ait-events-pro'),
	),

	array('section' => array('id' => 'headerType-image', 'title' => __('Image Options', 'ait-events-pro'))),

	'headerImage' => array(
		'label'   => __('Header Image', 'ait-events-pro'),
		'type'    => 'image',
		'default' => '',
		'help'    => __('Image displayed in header', 'ait-events-pro'),
	),

	array('section' => array('title' => _x('General', 'general options', 'ait-events-pro'))),

	'dates' => array(
		'label' => __('Dates', 'ait-events-pro'),
		'type' => 'clone',
		'items' => array(
			'dateFrom' => array(
				'label'   => __('Date From', 'ait-events-pro'),
				'type'    => 'date',
				'format'  => 'D, d M yy',
				'default' => 'none',
				'picker'  => 'datetime',
				'help'    => __('Starting date of event', 'ait-events-pro'),
			),
			'dateTo' => array(
				'label'   => __('Date To', 'ait-events-pro'),
				'type'    => 'date',
				'format'  => 'D, d M yy',
				'default' => 'none',
				'picker'  => 'datetime',
				'help'    => __('Ending date of event', 'ait-events-pro'),
			),
		),
		'default' => array(),
		'help' => __('Create more inputs if event is recurring', 'ait-events-pro'),
	),

	'fee' => array(
		'label' => __('Fee', 'ait-events-pro'),
		'type' => 'clone',
		'items' => array(
			'name' => array(
				'label'   => __('Label', 'ait-events-pro'),
				'type'    => 'text',
				'default' => '',
				'help'    => __('Optional', 'ait-events-pro'),
			),
			'price' => array(
				'label'   => __('Price', 'ait-events-pro'),
				'type'    => 'number',
				'step'    => 'any',
				'default' => 0,
				'help'    => __('Set 0 or leave empty for free', 'ait-events-pro'),
			),
			'url' => array(
				'label'   => 'Ticket Url',
				'type'    => 'url',
				'default' => '',
				'help'    => __("Optional external link for ticket's shop. Use valid url with http://.", 'ait-events-pro'),
			),
			'desc' => array(
				'label' => __('Description', 'ait-events-pro'),
				'type' => 'text',
				'default' => '',
				'help' => __('Optional', 'ait-events-pro'),
			),
		),
		'default' => array(),
		'help' => __('Leave empty for free', 'ait-events-pro'),
	),

	'currency' => array(
		'label' => __('Currency', 'ait-events-pro'),
		'type' => 'select',
		'selected' => 'USD',
		'default' => array(
			'AUD' => 'Australian Dollar (AUD)',
			'BRL' => 'Brazilian Real (BRL)',
			'CAD' => 'Canadian Dollar (CAD)',
			'CZK' => 'Czech Koruna (CZK)',
			'DKK' => 'Danish Krone (DKK)',
			'EUR' => 'Euro (EUR)',
			'HKD' => 'Hong Kong Dollar (HKD)',
			'HUF' => 'Hungarian Forint (HUF)',
			'ILS' => 'Israeli New Sheqel (ILS)',
			'JPY' => 'Japanese Yen (JPY)',
			'MYR' => 'Malaysian Ringgit (MYR)',
			'MXN' => 'Mexican Peso (MXN)',
			'NOK' => 'Norwegian Krone (NOK)',
			'NZD' => 'New Zealand Dollar (NZD)',
			'PHP' => 'Philippine Peso (PHP)',
			'PLN' => 'Polish Zloty (PLN)',
			'GBP' => 'Pound Sterling (GBP)',
			'RUB' => 'Russian Ruble (RUB)',
			'SGD' => 'Singapore Dollar (SGD)',
			'SEK' => 'Swedish Krona (SEK)',
			'CHF' => 'Swiss Franc (CHF)',
			'TWD' => 'Taiwan New Dollar (TWD)',
			'THB' => 'Thai Baht (THB)',
			'TRY' => 'Turkish Lira (TRY)',
			'USD' => 'U.S. Dollar (USD)',
		),
	),

	'item' => array(
		'label'        => __('Initiative', 'ait-events-pro'),
		'type'         => 'posts',
		'cpt'          => 'ait-item',
		'translatable' => true,
		'default'      => '',
		'help'         => __('Related Initiative', 'ait-events-pro'),
	),

	'useItemLocation' => array(
		'label' => __("Use Initiative's Location", 'ait-events-pro'),
		'type' => 'select',
		'selected' => 'no',
		'default' => array(
			'yes' => __('yes', 'ait-events-pro'),
			'no' => __('no', 'ait-events-pro'),
		),
		'help' => __('Event and related item will have the same address', 'ait-events-pro'),
	),

	array('section' => array('id' => 'useItemLocation-no')),

	'map' => array(
		'label' => __('Address', 'ait-events-pro'),
		'type' => 'map',
		'default' => array(
			'address'    => '',
			'latitude'   => '0',
			'longitude'  => '0',
			'streetview' => false,
		),
	),
);
