<?php
return array(
	'title' => _x('Events Pro', 'name of element', 'ait-events-pro'),
	'package' => array(
		'business'    => true,
		'developer'   => true,
		'themeforest' => true,
	),
	'configuration' => array(
		'columnable' => true,
		'sortable'   => true,
		'cloneable'  => true,
		'class'      => 'AitEventsProElement',
		'assets' => array(
			'js' => array(
				'ait-jquery-carousel' => true,
			),
		),
	),
	'icon' => 'fa-calendar-plus-o',
	'color' => '#64A6A3',
);