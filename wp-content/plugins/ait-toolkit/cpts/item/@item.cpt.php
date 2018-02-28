<?php

return array(
	'public' => true,
	'class' => 'AitItemCpt',

	'cpt' => array(
		'labels' => array(
			'name'               => _x('Initiatives', 'post type general name', 'ait-toolkit'),
			'singular_name'      => _x('Initiative', 'post type singular name', 'ait-toolkit'),
			'menu_name'          => _x('Initiatives', 'post type menu name', 'ait-toolkit'),
			'add_new'            => _x('Add New', 'Initiative', 'ait-toolkit'),
			'add_new_item'       => __('Add New Initiative', 'ait-toolkit'),
			'edit_item'          => __('Edit Initiative', 'ait-toolkit'),
			'new_item'           => __('New Initiative', 'ait-toolkit'),
			'view_item'          => __('View Initiative', 'ait-toolkit'),
			'search_items'       => __('Search Initiatives', 'ait-toolkit'),
			'not_found'          => __('No Initiatives found', 'ait-toolkit'),
			'not_found_in_trash' => __('No Initiatives found in Trash', 'ait-toolkit'),
			'all_items'          => __('All Initiatives', 'ait-toolkit'),
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
			'capability_type' => array('ait-item', 'ait-items'),
			'map_meta_cap' => true,
			'capabilities' => array(
				'edit_post'              => 'ait_toolkit_items_edit_item',
				'read_post'              => 'ait_toolkit_items_read_item',
				'delete_post'            => 'ait_toolkit_items_delete_item',
				'edit_posts'             => 'ait_toolkit_items_edit_items',
				'edit_others_posts'      => 'ait_toolkit_items_edit_others_items',
				'publish_posts'          => 'ait_toolkit_items_publish_items',
				'read_private_posts'     => 'ait_toolkit_items_read_private_items',
				'read'                   => 'ait_toolkit_items_read_items',
				'delete_posts'           => 'ait_toolkit_items_delete_items',
				'delete_private_posts'   => 'ait_toolkit_items_delete_private_items',
				'delete_published_posts' => 'ait_toolkit_items_delete_published_items',
				'delete_others_posts'    => 'ait_toolkit_items_delete_others_items',
				'edit_private_posts'     => 'ait_toolkit_items_edit_private_items',
				'edit_published_posts'   => 'ait_toolkit_items_edit_published_items',
			),
		),
	),

	'taxonomies' => array(
		'items' => array(
			'labels' => array(
				'name'              => _x('Initiative Categories', 'taxonomy general name', 'ait-toolkit'),
				'menu_name'         => _x('Initiative Categories', 'taxonomy menu name', 'ait-toolkit'),
				'singular_name'     => _x('Initiative Category', 'taxonomy singular name', 'ait-toolkit'),
				'search_items'      => __('Search Categories', 'ait-toolkit'),
				'all_items'         => __('All Categories', 'ait-toolkit'),
				'parent_item'       => __('Parent Category', 'ait-toolkit'),
				'parent_item_colon' => __('Parent Category:', 'ait-toolkit'),
				'edit_item'         => __('Edit Category', 'ait-toolkit'),
				'view_item'         => __('View Category', 'ait-toolkit'),
				'update_item'       => __('Update Category', 'ait-toolkit'),
				'add_new_item'      => __('Add New Category', 'ait-toolkit'),
				'new_item_name'     => __('New Category Name', 'ait-toolkit'),
			),
			'args' => array(
				'rewrite' => array(
					'slug' => 'cat',
				),
				'capabilities' => array(
					'manage_terms' => 'ait_toolkit_items_category_manage_items',
					'edit_terms'   => 'ait_toolkit_items_category_edit_items',
					'delete_terms' => 'ait_toolkit_items_category_delete_items',
					'assign_terms' => 'ait_toolkit_items_category_assign_items',
				),
			),
		),

		'locations' => array(
			'labels' => array(
				'name'              => _x('Locations', 'taxonomy general name', 'ait-toolkit'),
				'menu_name'         => _x('Locations', 'taxonomy menu name', 'ait-toolkit'),
				'singular_name'     => _x('Location', 'taxonomy singular name', 'ait-toolkit'),
				'search_items'      => __('Search Categories', 'ait-toolkit'),
				'all_items'         => __('All Categories', 'ait-toolkit'),
				'parent_item'       => __('Parent Category', 'ait-toolkit'),
				'parent_item_colon' => __('Parent Cateogry:', 'ait-toolkit'),
				'edit_item'         => __('Edit Category', 'ait-toolkit'),
				'view_item'         => __('View Category', 'ait-toolkit'),
				'update_item'       => __('Update Category', 'ait-toolkit'),
				'add_new_item'      => __('Add New Category', 'ait-toolkit'),
				'new_item_name'     => __('New Category Name', 'ait-toolkit'),
			),
			'args' => array(
				'rewrite' => array(
					'slug' => 'loc',
				),
				'capabilities' => array(
					'manage_terms' => 'ait_toolkit_items_category_manage_locations',
					'edit_terms'   => 'ait_toolkit_items_category_edit_locations',
					'delete_terms' => 'ait_toolkit_items_category_delete_locations',
					'assign_terms' => 'ait_toolkit_items_category_assign_locations',
				),
			),
		),
	),

	'metaboxes' => array(
		'item-data' => array(
			'title'        => _x('Initiative Options', 'custom metabox title', 'ait-toolkit'),
			'config'       => 'item-data',
			'saveCallback' => array('AitItemCpt', 'saveItemMeta'),
		),
		'item-author' => array(
			'title'        => _x('Author Options', 'custom metabox title', 'ait-toolkit'),
			'config'       => 'item-author',
			'saveCallback' => array('AitItemCpt', 'saveAuthorMetabox'),
		),
	),

	'featuredImageMetabox' => array(
		'labels' => array(
			'title'           => _x('Initiative Image', 'featured image metabox', 'ait-toolkit'),
			'linkSetTitle'    => _x('Set Initiative Image', 'featured image metabox', 'ait-toolkit'),
			'linkRemoveTitle' => _x('Remove Initiative Image', 'featured image metabox', 'ait-toolkit'),
		),
		'context' => 'normal',
		'priority' => 'default',
	),
);
