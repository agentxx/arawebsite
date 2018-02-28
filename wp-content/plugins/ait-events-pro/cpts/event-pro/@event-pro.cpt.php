<?php

return array(
	'public' => 'true',

	'cpt' => array(
		'labels' => array(
			'name'               => _x('Opportunities & Events', 'post type general name', 'ait-events-pro'),
			'singular_name'      => _x('Opportunities & Events', 'post type singular name', 'ait-events-pro'),
			'menu_name'          => _x('Opportunities & Events', 'post type menu name', 'ait-events-pro'),
			'add_new'            => _x('Add New', 'Event', 'ait-events-pro'),
			'add_new_item'       => __('Add New Opportunity/Event', 'ait-events-pro'),
			'edit_item'          => __('Edit Opportunity/Event', 'ait-events-pro'),
			'new_item'           => __('New Opportunity/Event', 'ait-events-pro'),
			'view_item'          => __('View Opportunity/Event', 'ait-events-pro'),
			'search_items'       => __('Search Opportunities/Events', 'ait-events-pro'),
			'not_found'          => __('No Opportunities/Events found', 'ait-events-pro'),
			'not_found_in_trash' => __('No Opportunities/Events found in Trash', 'ait-events-pro'),
			'all_items'          => __('All Opportunities & Events', 'ait-events-pro'),
		),

		'args' => array(

			'supports' => array(
				'title',
				'thumbnail',
				'editor',
				'page-attributes',
				'excerpt',
				'comments',
			),

			'capabilities' => array(
				'edit_post'              => 'ait_toolkit_eventspro_edit_event',
				'read_post'              => 'ait_toolkit_eventspro_read_event',
				'delete_post'            => 'ait_toolkit_eventspro_delete_events',
				'edit_posts'             => 'ait_toolkit_eventspro_edit_events',
				'edit_others_posts'      => 'ait_toolkit_eventspro_edit_others_events',
				'publish_posts'          => 'ait_toolkit_eventspro_publish_events',
				'read_private_posts'     => 'ait_toolkit_eventspro_read_private_events',
				'read'                   => 'ait_toolkit_eventspro_read_events',
				'delete_posts'           => 'ait_toolkit_eventspro_delete_events',
				'delete_private_posts'   => 'ait_toolkit_eventspro_delete_private_events',
				'delete_published_posts' => 'ait_toolkit_eventspro_delete_published_events',
				'delete_others_posts'    => 'ait_toolkit_eventspro_delete_others_events',
				'edit_private_posts'     => 'ait_toolkit_eventspro_edit_private_events',
				'edit_published_posts'   => 'ait_toolkit_eventspro_edit_published_events',
			),
		),
		'icon' => 'event-pro.png',
	),



	'metaboxes' => array(
		'event-pro-data' => array(
			'title' => _x('Opportunity/Event Options', 'custom metabox title', 'ait-events-pro'),
			'config' => 'event-pro-data',
		),
		'event-author' => array(
			'title'        => _x('Author Options', 'custom metabox title', 'ait-toolkit'),
			'config'       => 'event-author',
			'saveCallback' => array('AitEventsPro', 'saveAuthorMetabox'),
		),
	),
		// event-pro-relations-'data' => array(
		// 	'title' => '_x(Relations, custom metabox title)',
		// 	'config' => 'event-pro-relations-data',

	'featuredImageMetabox' => array(
		'labels' => array(
			'title'           => _x('Opportunity/Event Image', 'featured image metabox', 'ait-events-pro'),
			'linkSetTitle'    => _x('Set Opportunity/Event Image', 'featured image metabox', 'ait-events-pro'),
			'linkRemoveTitle' => _x('Remove Opportunity/Event Image', 'featured image metabox', 'ait-events-pro'),
		),
		'context' => 'normal',
		'priority' => 'default',
	),
);
