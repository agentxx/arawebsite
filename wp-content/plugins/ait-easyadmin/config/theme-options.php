<?php

return array(
	'raw' => array(
		'easyAdmin' => array(
			'title' => __('Easy Admin', 'ait-easyadmin'),
			'options' => array(

				'enable' => array(
					'label'		=> __('Enable', 'ait-easyadmin'),
					'type'		=> 'on-off',
					'default'	=> true,
					'less'		=> false,
					'help'		=> __('Enable easy administration', 'ait-easyadmin'),
				),

				array('section' => array('id' => 'easyadmin-general', 'title' => __('General', 'ait-easyadmin'))),

				'maxWidth' => array(
					'label'		=> __('Max Width', 'ait-easyadmin'),
					'type'		=> 'range',
					'unit'		=> 'px',
					'min'		=> '1000',
					'max'		=> '1800',
					'step'		=> '50',
					'default'	=> '1200',
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				'generalBackground' => array(
					'label'		=> __('Background', 'ait-easyadmin'),
					'type'		=> 'background',
					'default'	=> array(
						'color'		=> '#F1F1F1',
						'opacity'	=> '100%',
						'image'		=> '',
						'repeat'	=> 'no-repeat',
						'position'	=> 'top center',
						'scroll'	=> 'scroll',
					),
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				'generalTitlesColor' => array(
					'label'		=> __('Titles Color', 'ait-easyadmin'),
					'type'		=> 'color',
					'default'	=> '#666666',
					'opacity'	=> '100%',
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				'generalTextColor' => array(
					'label'		=> __('Text Color', 'ait-easyadmin'),
					'type'		=> 'color',
					'default'	=> '#888888',
					'opacity'	=> '100%',
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				'generalLinksColor' => array(
					'label'		=> __('Links Color', 'ait-easyadmin'),
					'type'		=> 'color',
					'default'	=> '#3BA5BC',
					'opacity'	=> '100%',
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				'generalButtonColor' => array(
					'label'		=> __('Button Color', 'ait-easyadmin'),
					'type'		=> 'color',
					'default'	=> '#3BA5BC',
					'opacity'	=> '100%',
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				'generalButtonText' => array(
					'label'		=> __('Button Text Color', 'ait-easyadmin'),
					'type'		=> 'color',
					'default'	=> '#ffffff',
					'opacity'	=> '100%',
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				array('section' => array('id' => 'easyadmin-header', 'title' => __('Header', 'ait-easyadmin'))),

				'siteLogo' => array(
					'label'		=> __('Site Logo', 'ait-easyadmin'),
					'type'		=> 'image',
					'default'	=> "",
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				'headerBackground' => array(
					'label'		=> __('Background', 'ait-easyadmin'),
					'type'		=> 'background',
					'default'	=> array(
						'color'		=> '#2d2d2d',
						'opacity'	=> '100%',
						'image'		=> '',
						'repeat'	=> 'no-repeat',
						'position'	=> 'top center',
						'scroll'	=> 'scroll',
					),
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				'headerTextColor' => array(
					'label'		=> __('Text Color', 'ait-easyadmin'),
					'type'		=> 'color',
					'default'	=> '#ffffff',
					'opacity'	=> '100%',
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				array('section' => array('id' => 'easyadmin-header-menu', 'title' => __('Header Menu', 'ait-easyadmin'))),

				'headerMenuBackground' => array(
					'label'		=> __('Background', 'ait-easyadmin'),
					'type'		=> 'background',
					'default'	=> array(
						'color'		=> '#383838',
						'opacity'	=> '100%',
						'image'		=> '',
						'repeat'	=> 'no-repeat',
						'position'	=> 'top center',
						'scroll'	=> 'scroll',
					),
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				'headerMenuTextColor' => array(
					'label'		=> __('Text Color', 'ait-easyadmin'),
					'type'		=> 'color',
					'default'	=> '#ffffff',
					'opacity'	=> '100%',
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				array('section' => array('id' => 'easyadmin-metabox-content', 'title' => __('Metabox Content', 'ait-easyadmin'))),

				'contentMetaboxBackground' => array(
					'label'		=> __('Background', 'ait-easyadmin'),
					'type'		=> 'background',
					'default'	=> array(
						'color'		=> '#ffffff',
						'opacity'	=> '100%',
						'image'		=> '',
						'repeat'	=> 'no-repeat',
						'position'	=> 'top center',
						'scroll'	=> 'scroll',
					),
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				'contentMetaboxLabelColor' => array(
					'label'		=> __('Label Color', 'ait-easyadmin'),
					'type'		=> 'color',
					'default'	=> '#666666',
					'opacity'	=> '100%',
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				'contentMetaboxTextColor' => array(
					'label'		=> __('Text Color', 'ait-easyadmin'),
					'type'		=> 'color',
					'default'	=> '#888888',
					'opacity'	=> '100%',
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

				'contentMetaboxBorderColor' => array(
					'label'		=> __('Border Color', 'ait-easyadmin'),
					'type'		=> 'color',
					'default'	=> '#e8e8e8',
					'opacity'	=> '100%',
					'less'		=> false,
					'help'		=> __('', 'ait-easyadmin'),
				),

			),
		),
	),
	'defaults' => array(
		'easyAdmin' => array(
			'enable'						=> false,

			// general
			'maxWidth'					=> "1200",
			'generalBackground'				=> array(
				'color'		=> '#F7F7F7',
				'opacity'	=> '100%',
				'image'		=> '',
				'repeat'	=> 'no-repeat',
				'position'	=> 'top center',
				'scroll'	=> 'scroll',
			),
			'generalTitlesColor'					=> "#666666",
			'generalTextColor'						=> "#888888",
			'generalLinksColor'					    => "#3BA5BC",
			'generalButtonColor'					=> "#3BA5BC",
			'generalButtonText'						=> "#ffffff",

			// header
			'siteLogo'						=> "",
			'headerBackground'				=> array(
				'color'		=> '#2d2d2d',
				'opacity'	=> '100%',
				'image'		=> '',
				'repeat'	=> 'no-repeat',
				'position'	=> 'top center',
				'scroll'	=> 'scroll',
			),
			'headerTextColor'				=> "#ffffff",

			// header menu
			'headerMenuBackground'			=> array(
				'color'		=> '#383838',
				'opacity'	=> '100%',
				'image'		=> '',
				'repeat'	=> 'no-repeat',
				'position'	=> 'top center',
				'scroll'	=> 'scroll',
			),
			'headerMenuTextColor'			=> "#ffffff",

			// metabox content
			'contentMetaboxBackground'		=> array(
				'color'		=> '#ffffff',
				'opacity'	=> '100%',
				'image'		=> '',
				'repeat'	=> 'no-repeat',
				'position'	=> 'top center',
				'scroll'	=> 'scroll',
			),
			'contentMetaboxLabelColor'		=> "#666666",
			'contentMetaboxTextColor'		=> "#888888",
			'contentMetaboxBorderColor'		=> "#e8e8e8",
		)
	)
);