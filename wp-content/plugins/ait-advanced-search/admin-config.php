<?php

return array(
	'general' => array(
		'title' => __('General Settings', 'ait-advanced-search'),
		'options' => array(
			'useDefaults' => array(
				'label' 	=> __('Use Default Values', "ait-advanced-search"),
				'type'		=> 'on-off',
				'default'	=> false,
			),
			'defaultLocation' => array(
				'label' => __('Default Location', 'ait-advanced-search'),
				'type' => 'map',
				'default' => array(
					'address'    => '',
					'latitude'   => '1',
					'longitude'  => '1',
					'streetview' => false,
				),
			),
			'defaultRadius' => array(
				'label' 	=> __('Default Radius', "ait-advanced-search"),
				'type'		=> 'range',
				'min'		=> '0.1',
				'max'		=> '100',
				'step'		=> '0.1',
				'default'	=> '5',
				'help'		=> __("Default radius is used unless user choose differently", 'ait-advanced-search'),
			),
		),
	),


);
