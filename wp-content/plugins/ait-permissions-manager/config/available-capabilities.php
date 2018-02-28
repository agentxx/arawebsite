<?php
// this can be overriden using a filter 'ait_permisssions_manager_available_capabilities'

/*
example:
	add_filter('ait_permisssions_manager_available_capabilities', function($config){
		// 'filter_added_section' can be also one of the already defined sections => wordpress_section | theme_section | plugins_section | cpt_section
		$config['filter_added_section'] = array(
			'label'		=> esc_html__('Filter added capabilities', 'ait-permissions-manager'),
			'capabilities' => array(
				// 'my_custom_capability' can be also any capability from this config so it can be overrided
				'my_custom_capability' => array(
					'label'		=> esc_html__('Custom capability', 'ait-permissions-manager'),
					'help'		=> esc_html__('Enables the user to modify custom things', 'ait-permissions-manager'),
					'check'		=> true,
					// 'caps' array defines the core capabilities which should be enabled when the main capability is enabled
					// this must be done this way because custom post types need more than one capability to be enabled for the user/role
					// if this field isnt set, only the main capability is enabled
					// 'caps' array is used only in the ajax updateCapabilities function
					'caps'		=> array(
						'enable'	=> array(
							// when main capability is enabled, also capabilities in this array are enabled
						),
						'disable'	=> array(
							// when main capability is enabled, capabilities in this array are disabled for the role
						),
					),
				),

			),
		);
		return $config;
	}, 10, 1);
*/

return array(
	'wordpress_section' => array(
		'label'	=> esc_html__('Wordpress Capabilities', 'ait-permissions-manager'),
		'capabilities' => array(

			'manage_options' => array(
				'label'						=> esc_html__('Settings', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of wordpress settings', 'ait-permissions-manager'),
				'check'						=> true,
			),

			'upload_files' => array(
				'label'						=> esc_html__('Media', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of media', 'ait-permissions-manager'),
				'check'						=> true,
			),

		),
	),

	'theme_section' => array(
		'label'	=> esc_html__('Theme Capabilities', 'ait-permissions-manager'),
		'capabilities' => array(

			'ait_theme_options'			=> array(
				'label'						=> esc_html__('Theme Options', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of theme options', 'ait-permissions-manager'),
				'check'						=> false,
			),

			'ait_default_layout'		=> array(
				'label'						=> esc_html__('Default Layout', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of default layout', 'ait-permissions-manager'),
				'check'						=> defined('AIT_THEME_CODENAME'),
			),

			'ait_backup'				=> array(
				'label'						=> esc_html__('Import / Export', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of import / export', 'ait-permissions-manager'),
				'check'						=> defined('AIT_THEME_CODENAME'),
			),

			'ait_pages_options'			=> array(
				'label'						=> esc_html__('Page Builder', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of page builder', 'ait-permissions-manager'),
				'check'						=> defined('AIT_THEME_CODENAME'),
			),

		),
	),

	'plugins_section' => array(
		'label'	=> esc_html__('Plugin Capabilities', 'ait-permissions-manager'),
		'capabilities' => array(

			'ait_announcements_bar_options'		=> array(
				'label'						=> 'AIT Announcements Bar',
				'help'						=> esc_html__('Enables the management of ait announcements bar settings', 'ait-permissions-manager'),
				'check'						=> defined('AIT_ANNOUNCEMENTS_BAR_ENABLED'),
			),

			'ait_events_pro_options'			=> array(
				'label'						=> 'AIT Events Pro',
				'help'						=> esc_html__('Enables the management of ait events pro settings', 'ait-permissions-manager'),
				'check'						=> defined('AIT_EVENTS_PRO_ENABLED'),
			),

			'ait_item_extension_options'		=> array(
				'label'						=> 'AIT Item Extension',
				'help'						=> esc_html__('Enables the management of ait item extension settings', 'ait-permissions-manager'),
				'check'						=> defined('AIT_EXTENSION_ENABLED'),
			),

			// need to verify this
			'ait_languages_options'				=> array(
				'label'						=> 'AIT Languages',
				'help'						=> esc_html__('Enables the management of ait languages settings', 'ait-permissions-manager'),
				'check'						=> defined('AIT_LANGUAGES_ENABLED'),
			),

			'ait_directory_migration_options'	=> array(
				'label'						=> 'AIT Directory Migrations',
				'help'						=> esc_html__('Enables the management of ait directory migrations settings', 'ait-permissions-manager'),
				'check'						=> defined('AIT_MIGRATION_PLUGIN'),
			),

			// need to verify this
			'ait_updater_options'				=> array(
				'label'						=> 'AIT Updater',
				'help'						=> esc_html__('Enables the management of ait updater settings', 'ait-permissions-manager'),
				'check'						=> defined('AIT_UPDATER_ENABLED'),
			),

			'ait_quick_comments_options'		=> array(
				'label'						=> 'AIT Quick Comments',
				'help'						=> esc_html__('Enables the management of ait quick comments settings', 'ait-permissions-manager'),
				'check'						=> defined('AIT_QUICK_COMMENTS_ENABLED'),
			),

			'ait_sysinfo_options'		=> array(
				'label'						=> 'AIT SysInfo',
				'help'						=> esc_html__('Enables the management of ait sysinfo settings', 'ait-permissions-manager'),
				'check'						=> defined('AIT_SYSINFO_VERSION'),
			),

		),
	),

	'cpt_section' => array(
		'label'	=> esc_html__('Custom Post Type Capabilities ', 'ait-permissions-manager'),
		'capabilities' => array(

			'post'				=> array(
				'label'						=> esc_html__('Posts', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of posts custom post type', 'ait-permissions-manager'),
				'check'						=> true,
				'caps'						=> array(
					'enable'	=> array(
						'edit_post',
						'edit_posts',
						'read_post',
						'publish_posts',
						'delete_posts',
						'edit_published_posts',
						'delete_published_posts',
						'edit_others_posts',
						'delete_others_posts',
						'read_private_posts',
						'edit_private_posts',
						'delete_private_posts',
						'manage_categories',
					),
					'disable'	=> array(),
				),
			),

			'page'				=> array(
				'label'						=> esc_html__('Pages', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of pages custom post type', 'ait-permissions-manager'),
				'check'						=> true,
				'caps'						=> array(
					'enable'	=> array(
						'edit_page',
						'edit_pages',
						'read_page',
						'publish_pages',
						'delete_pages',
						'delete_published_pages',
						'edit_published_pages',
						'edit_others_pages',
						'delete_others_pages',
						'read_private_pages',
						'edit_private_pages',
						'delete_private_pages',
					),
					'disable'	=> array(),
				),
			),

			'ait_ad_space'				=> array(
				'label'						=> esc_html__('Advertisments', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of advertisments custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'ad-space', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_ad-space_edit_post',
						'ait_toolkit_ad-space_edit_posts',
						'ait_toolkit_ad-space_read_post',
						'ait_toolkit_ad-space_publish_posts',
						'ait_toolkit_ad-space_delete_posts',
						'ait_toolkit_ad-space_delete_published_posts',
						'ait_toolkit_ad-space_edit_published_posts',
						'ait_toolkit_ad-space_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_event'					=> array(
				'label'						=> esc_html__('Events', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of events custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'event', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_event_edit_post',
						'ait_toolkit_event_edit_posts',
						'ait_toolkit_event_read_post',
						'ait_toolkit_event_publish_posts',
						'ait_toolkit_event_delete_posts',
						'ait_toolkit_event_delete_published_posts',
						'ait_toolkit_event_edit_published_posts',
						'ait_toolkit_event_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_event_pro'				=> array(
				'label'						=> esc_html__('Events Pro', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of events pro custom post type', 'ait-permissions-manager'),
				'check'						=> defined('AIT_EVENTS_PRO_ENABLED'),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_eventspro_edit_event',
						'ait_toolkit_eventspro_edit_events',
						'ait_toolkit_eventspro_read_event',
						'ait_toolkit_eventspro_read_events',
						'ait_toolkit_eventspro_publish_events',
						'ait_toolkit_eventspro_delete_events',
						'ait_toolkit_eventspro_delete_published_events',
						'ait_toolkit_eventspro_edit_published_events',
						'ait_toolkit_eventspro_edit_others_events',
						'ait_toolkit_eventspro_delete_others_events',
						'ait_toolkit_eventspro_read_private_events',
						'ait_toolkit_eventspro_edit_private_events',
						'ait_toolkit_eventspro_delete_private_events',
						'ait_toolkit_eventspro_category_assign_events_pro',
						'ait_toolkit_eventspro_category_manage_events_pro',
						'ait_toolkit_eventspro_category_edit_events_pro',
						'ait_toolkit_eventspro_category_delete_events_pro',
					),
					'disable'	=> array(),
				),
				'limitedCaps'				=> array(
					'enable'	=> array(
						'ait_toolkit_eventspro_edit_event',
						'ait_toolkit_eventspro_edit_events',
						'ait_toolkit_eventspro_read_event',
						'ait_toolkit_eventspro_read_events',
						'ait_toolkit_eventspro_publish_events',
						'ait_toolkit_eventspro_delete_events',
						'ait_toolkit_eventspro_delete_published_events',
						'ait_toolkit_eventspro_edit_published_events',
						'ait_toolkit_eventspro_category_assign_events_pro',
					),
					'disable'	=> array(),
				),
			),

			'ait_facility'				=> array(
				'label'						=> esc_html__('Facilities', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of facility custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'facility', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_facility_edit_post',
						'ait_toolkit_facility_edit_posts',
						'ait_toolkit_facility_read_post',
						'ait_toolkit_facility_publish_posts',
						'ait_toolkit_facility_delete_posts',
						'ait_toolkit_facility_delete_published_posts',
						'ait_toolkit_facility_edit_published_posts',
						'ait_toolkit_facility_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_faq'					=> array(
				'label'						=> esc_html__('FAQ', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of faq custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'faq', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_faq_edit_post',
						'ait_toolkit_faq_edit_posts',
						'ait_toolkit_faq_read_post',
						'ait_toolkit_faq_publish_posts',
						'ait_toolkit_faq_delete_posts',
						'ait_toolkit_faq_delete_published_posts',
						'ait_toolkit_faq_edit_published_posts',
						'ait_toolkit_faq_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_food_menu'				=> array(
				'label'						=> esc_html__('Food Menu', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of food menu custom post type', 'ait-permissions-manager'),
				'check'						=> defined('AIT_FOOD_MENU_ENABLED'),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_food_menu_edit_post',
						'ait_toolkit_food_menu_edit_posts',
						'ait_toolkit_food_menu_read_post',
						'ait_toolkit_food_menu_publish_posts',
						'ait_toolkit_food_menu_delete_posts',
						'ait_toolkit_food_menu_delete_published_posts',
						'ait_toolkit_food_menu_edit_published_posts',
						'ait_food_menu_category_manage_terms',
						'ait_food_menu_category_edit_terms',
						'ait_food_menu_category_delete_terms',
						'ait_food_menu_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_infopanel'				=> array(
				'label'						=> esc_html__('Infopanels', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of infopanels custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'infopanel', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_infopanel_edit_post',
						'ait_toolkit_infopanel_edit_posts',
						'ait_toolkit_infopanel_read_post',
						'ait_toolkit_infopanel_publish_posts',
						'ait_toolkit_infopanel_delete_posts',
						'ait_toolkit_infopanel_delete_published_posts',
						'ait_toolkit_infopanel_edit_published_posts',
						'ait_toolkit_infopanel_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_item'					=> array(
				'label'						=> esc_html__('Items', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of items custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'item', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_items_edit_item',
						'ait_toolkit_items_edit_items',
						'ait_toolkit_items_read_item',
						'ait_toolkit_items_read_items',
						'ait_toolkit_items_publish_items',
						'ait_toolkit_items_delete_items',
						'ait_toolkit_items_delete_published_items',
						'ait_toolkit_items_edit_published_items',
						'ait_toolkit_items_edit_others_items',
						'ait_toolkit_items_delete_others_items',
						'ait_toolkit_items_read_private_items',
						'ait_toolkit_items_edit_private_items',
						'ait_toolkit_items_delete_private_items',
						'ait_toolkit_items_category_manage_items',
						'ait_toolkit_items_category_edit_items',
						'ait_toolkit_items_category_delete_items',
						'ait_toolkit_items_category_assign_items',
						'ait_toolkit_items_category_manage_locations',
						'ait_toolkit_items_category_edit_locations',
						'ait_toolkit_items_category_delete_locations',
						'ait_toolkit_items_category_assign_locations',
						'_ait-item_item-data_featuredItem',
						'_ait-item_item-data_headerType',
						'_ait-item_item-data_headerType-image',
						'_ait-item_item-data_map',
						'_ait-item_item-data_telephone',
						'_ait-item_item-data_telephoneAdditional',
						'_ait-item_item-data_email',
						'_ait-item_item-data_showEmail',
						'_ait-item_item-data_contactOwnerBtn',
						'_ait-item_item-data_web',
						'_ait-item_item-data_webLinkLabel',
						'_ait-item_item-data_itemOpeningHours',
						'_ait-item_item-data_itemSocialIcons',
						'_ait-item_item-data_itemGallery',
						'_ait-item_item-data_itemFeatures',
						'_ait-item_item-author_author',
					),
					'disable'	=> array(),
				),
			),

			'ait_job_offer'				=> array(
				'label'						=> esc_html__('Job Offers', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of job offers custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'job-offer', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_job-offer_edit_post',
						'ait_toolkit_job-offer_edit_posts',
						'ait_toolkit_job-offer_read_post',
						'ait_toolkit_job-offer_publish_posts',
						'ait_toolkit_job-offer_delete_posts',
						'ait_toolkit_job-offer_delete_published_posts',
						'ait_toolkit_job-offer_edit_published_posts',
						'ait_toolkit_job-offer_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_match'					=> array(
				'label'						=> esc_html__('Matches', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of matches custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'match', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_match_edit_post',
						'ait_toolkit_match_edit_posts',
						'ait_toolkit_match_read_post',
						'ait_toolkit_match_publish_posts',
						'ait_toolkit_match_delete_posts',
						'ait_toolkit_match_delete_published_posts',
						'ait_toolkit_match_edit_published_posts',
						'ait_toolkit_match_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_member'				=> array(
				'label'						=> esc_html__('Members', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of members custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'member', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_member_edit_post',
						'ait_toolkit_member_edit_posts',
						'ait_toolkit_member_read_post',
						'ait_toolkit_member_publish_posts',
						'ait_toolkit_member_delete_posts',
						'ait_toolkit_member_delete_published_posts',
						'ait_toolkit_member_edit_published_posts',
						'ait_toolkit_member_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_obituary'				=> array(
				'label'						=> esc_html__('Obituaries', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of obituaries custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'obituary', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_obituary_edit_post',
						'ait_toolkit_obituary_edit_posts',
						'ait_toolkit_obituary_read_post',
						'ait_toolkit_obituary_publish_posts',
						'ait_toolkit_obituary_delete_posts',
						'ait_toolkit_obituary_delete_published_posts',
						'ait_toolkit_obituary_edit_published_posts',
					),
					'disable'	=> array(),
				),
			),

			'ait_partner'				=> array(
				'label'						=> esc_html__('Partners', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of partners custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'partner', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_partner_edit_post',
						'ait_toolkit_partner_edit_posts',
						'ait_toolkit_partner_read_post',
						'ait_toolkit_partner_publish_posts',
						'ait_toolkit_partner_delete_posts',
						'ait_toolkit_partner_delete_published_posts',
						'ait_toolkit_partner_edit_published_posts',
						'ait_toolkit_partner_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_portfolio_item'		=> array(
				'label'						=> esc_html__('Portfolio', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of portfolio custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'portfolio-item', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_portfolio-item_edit_post',
						'ait_toolkit_portfolio-item_edit_posts',
						'ait_toolkit_portfolio-item_read_post',
						'ait_toolkit_portfolio-item_publish_posts',
						'ait_toolkit_portfolio-item_delete_posts',
						'ait_toolkit_portfolio-item_delete_published_posts',
						'ait_toolkit_portfolio-item_edit_published_posts',
						'ait_toolkit_portfolio-item_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_price_table'			=> array(
				'label'						=> esc_html__('Price Tables', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of price tables custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'price-table', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_price-table_edit_post',
						'ait_toolkit_price-table_edit_posts',
						'ait_toolkit_price-table_read_post',
						'ait_toolkit_price-table_publish_posts',
						'ait_toolkit_price-table_delete_posts',
						'ait_toolkit_price-table_delete_published_posts',
						'ait_toolkit_price-table_edit_published_posts',
						'ait_toolkit_price-table_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_product_item'			=> array(
				'label'						=> esc_html__('Products', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of products custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'product-item', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_product-item_edit_post',
						'ait_toolkit_product-item_edit_posts',
						'ait_toolkit_product-item_read_post',
						'ait_toolkit_product-item_publish_posts',
						'ait_toolkit_product-item_delete_posts',
						'ait_toolkit_product-item_delete_published_posts',
						'ait_toolkit_product-item_edit_published_posts',
						'ait_toolkit_product-item_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_review'				=> array(
				'label'						=> esc_html__('Item Reviews', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of reviews custom post type', 'ait-permissions-manager'),
				'check'						=> defined('AIT_REVIEWS_ENABLED'),
				'caps'						=> array(
					'enable'	=> array(
						'ait_item_reviews_edit_posts',
						'ait_item_reviews_delete_posts',
						'ait_item_reviews_delete_published_posts',
					),
					'disable'	=> array(
						'ait_item_reviews_read_post',
						'ait_item_reviews_edit_post',
						'ait_item_reviews_publish_posts',
						'ait_item_reviews_edit_published_posts',
					),
				),
			),

			'ait_service_box'			=> array(
				'label'						=> esc_html__('Services', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of services custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'service-box', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_service-box_edit_post',
						'ait_toolkit_service-box_edit_posts',
						'ait_toolkit_service-box_read_post',
						'ait_toolkit_service-box_publish_posts',
						'ait_toolkit_service-box_delete_posts',
						'ait_toolkit_service-box_delete_published_posts',
						'ait_toolkit_service-box_edit_published_posts',
						'ait_toolkit_service-box_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_special_offer'			=> array(
				'label'						=> esc_html__('Special Offers', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of special offers custom post type', 'ait-permissions-manager'),
				'check'						=> defined('AIT_SPECIAL_OFFERS_ENABLED'),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_special_offer_edit_post',
						'ait_toolkit_special_offer_edit_posts',
						'ait_toolkit_special_offer_read_post',
						'ait_toolkit_special_offer_publish_posts',
						'ait_toolkit_special_offer_delete_posts',
						'ait_toolkit_special_offer_delete_published_posts',
						'ait_toolkit_special_offer_edit_published_posts',
					),
					'disable'	=> array(),
				),
			),

			'ait_testimonial'			=> array(
				'label'						=> esc_html__('Testimonials', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of testimonials custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'testimonial', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_testimonial_edit_post',
						'ait_toolkit_testimonial_edit_posts',
						'ait_toolkit_testimonial_read_post',
						'ait_toolkit_testimonial_publish_posts',
						'ait_toolkit_testimonial_delete_posts',
						'ait_toolkit_testimonial_delete_published_posts',
						'ait_toolkit_testimonial_edit_published_posts',
						'ait_toolkit_testimonial_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_toggle'				=> array(
				'label'						=> esc_html__('Toggles', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of toggles custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'toggle', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_toggle_edit_post',
						'ait_toolkit_toggle_edit_posts',
						'ait_toolkit_toggle_read_post',
						'ait_toolkit_toggle_publish_posts',
						'ait_toolkit_toggle_delete_posts',
						'ait_toolkit_toggle_delete_published_posts',
						'ait_toolkit_toggle_edit_published_posts',
						'ait_toolkit_toggle_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

			'ait_tour'					=> array(
				'label'						=> esc_html__('Tours', 'ait-permissions-manager'),
				'help'						=> esc_html__('Enables the management of tours custom post type', 'ait-permissions-manager'),
				'check'						=> AitPermissionsManager::checkOptionCompatibility( 'tour', 'cpt', defined('AIT_TOOLKIT_ENABLED') ),
				'caps'						=> array(
					'enable'	=> array(
						'ait_toolkit_tour_edit_post',
						'ait_toolkit_tour_edit_posts',
						'ait_toolkit_tour_read_post',
						'ait_toolkit_tour_publish_posts',
						'ait_toolkit_tour_delete_posts',
						'ait_toolkit_tour_delete_published_posts',
						'ait_toolkit_tour_edit_published_posts',
						'ait_toolkit_tour_category_assign_terms',
					),
					'disable'	=> array(),
				),
			),

		),
	),
);