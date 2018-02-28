<?php
/*
Plugin Name: AIT Permissions Manager
Plugin URI: http://ait-themes.club
Description: User role permissions manager
Version: 1.9
Author: AitThemes.Club
Author URI: http://ait-themes.club
Text Domain: ait-permissions-manager
Domain Path: /languages
License: GPLv2 or later
*/

/* trunk@r69 */

/* FUTURE IDEAS */
// refactor / rethink idea of garbage collector, to cleanup the old stored capabilities
/* FUTURE IDEAS */

define("AIT_PERMISSIONS_MANAGER_ENABLED" , true);
define('AIT_PERMISSIONS_MANAGER_PLUGIN_DEBUG', true);

AitPermissionsManager::init();

class AitPermissionsManager {
	protected static $themeOptionsKey;
	protected static $currentTheme;

	protected static $compatibleThemes;

	protected static $paths;
	protected static $log_filename;

	public static function init(){
		$theme = wp_get_theme();
		self::$currentTheme = $theme->parent() != false ? $theme->parent() : $theme;	// this return parent theme on active child theme
		if(self::$currentTheme->stylesheet == 'skeleton'){
			self::$currentTheme = $theme;
		}

		// not used at the moment
		self::$themeOptionsKey = sanitize_key(get_stylesheet()); // because theme options are stored _ait_{$theme}_theme_opts and on child theme _ait_{$childTheme}_theme_opts

		self::$compatibleThemes = array(
			'businessfinder2'	=> "1.17",
			'cityguide'			=> "2.88",
			'directory2'		=> "1.67",
			'eventguide'		=> "1.46",
			'foodguide'			=> "1.28",
			'blog'				=> "1.72",
		);

		self::$paths = array(
			'config' => dirname( __FILE__ ).'/config',
			'logs'	 => dirname( __FILE__ ).'/logs',
		);

		self::$log_filename = 'ait-permissions-manager.log';
		if(!is_writable(self::$paths['logs'].'/'.self::$log_filename)){
			$upload_dir = wp_upload_dir();
			self::$paths['logs'] = $upload_dir['basedir'];
		}

		// plugin setup
		register_activation_hook( __FILE__, array(__CLASS__, 'onActivation') );
		register_deactivation_hook(  __FILE__, array(__CLASS__, 'onDeactivation') );
		add_action('after_switch_theme', array(__CLASS__, 'onThemeSwitched'));

		add_action('plugins_loaded', array(__CLASS__, 'onLoaded'));
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(__CLASS__, 'pluginActionLinks'));

		// admin setup
		add_filter('ait-admin-pages-permission', array(__CLASS__, 'adminModifyThemeMenuCapabilities'), 10, 3);
		add_action('admin_init', array(__CLASS__, 'onAdminInit'));
		add_filter('admin_body_class', array(__CLASS__, 'adminBodyClass'), 10, 1);
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueueAdminDesign') );

		// plugins permissions
		add_filter('ait-announcements-bar-menu-permission', function($cap_slug){ return 'ait_announcements_bar_options'; }, 10, 1);
		add_filter('ait-item-extension-menu-permission', function($cap_slug){ return 'ait_item_extension_options'; }, 10, 1);
		add_filter('ait-import-export-menu-permission', function($cap_slug){ return 'ait_import_export_options'; }, 10, 1);
		add_filter('ait-directory-migration-menu-permission', function($cap_slug){ return 'ait_directory_migration_options'; }, 10, 1);
		add_filter('ait-quick-comments-menu-permission', function($cap_slug){ return 'ait_quick_comments_options'; }, 10, 1);
		add_filter('ait-updater-menu-permission', function($cap_slug){ return 'ait_updater_options'; }, 10, 1);
		add_filter('ait-languages-menu-permission', function($cap_slug){ return 'ait_languages_options'; }, 10, 1);
		add_filter('ait-sysinfo-menu-permission', function($cap_slug){ return 'ait_sysinfo_options'; }, 10, 1);

		// custom capabilities setup
		add_filter('ait_permisssions_manager_available_capabilities', array(__CLASS__, 'customCapabilitiesManager'), 10, 1);

		// ajax setup
		add_action( 'wp_ajax_aitPermissionManagerSaveOptions', array(__CLASS__, 'ajaxSaveOptions'));
		add_action( 'wp_ajax_aitPermissionManagerResetRole', array(__CLASS__, 'ajaxResetRole'));
	}

	/* PLUGIN SETUP */
	public static function onActivation(){
		AitPermissionsManager::checkPluginCompatibility(true);

		AitPermissionsManager::backupCurrentConfiguration();

		AitPermissionsManager::backupCurrentCapabilities();
		AitPermissionsManager::initAdministratorCapabilities();
		AitPermissionsManager::getAlreadyEnabledCapabilities();

		if(class_exists('AitCache')){
			AitCache::clean();
		}
	}
	public static function onDeactivation(){
		if(class_exists('AitCache')){
			AitCache::clean();
		}
	}
	public static function onLoaded(){
		add_action('admin_menu', array(__CLASS__, 'adminMenu'));

		load_plugin_textdomain('ait-permissions-manager', false,  dirname(plugin_basename(__FILE__ )) . '/languages');
	}
	public static function pluginActionLinks($links){
		$link = sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=ait_permissions_manager_options'), esc_html__('Settings', 'ait-permissions-manager'));
		array_unshift($links, $link);
		return $links;
	}
	/* PLUGIN SETUP */

	/* PLUGIN COMPATIBILITY */
	public static function checkPluginCompatibility($die = false){
		if ( !defined("AIT_SKELETON_VERSION") ) {	// fw2 check
			require_once(ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins(plugin_basename( __FILE__ ));
			if($die){
				wp_die('Current theme is not compatible with Permissions Manager plugin :(', '',  array('back_link'=>true));
			} else {
				add_action( 'admin_notices', function(){
					echo "<div class='error'><p>" . esc_html__('Current theme is not compatible with Permissions Manager plugin!', 'ait-permissions-manager') . "</p></div>";
				} );
			}
		}
	}
	public static function onThemeSwitched(){
		AitPermissionsManager::checkPluginCompatibility();
	}
	/* PLUGIN COMPATIBILITY */

	/* ADMIN SETUP */
	public static function onAdminInit(){
		// not used at this moment
		AitPermissionsManager::getAlreadyEnabledCapabilities(); // ? why enabled ?

		// do the check only for the themes specified in the $compatibleThemes array
		if(!empty(self::$compatibleThemes[self::$currentTheme->stylesheet])){
			// directory version check
			if(version_compare(self::$currentTheme->version, self::$compatibleThemes[self::$currentTheme->stylesheet]) == -1){
				if(!empty($_REQUEST['page'])){
					if($_REQUEST['page'] == 'ait_permissions_manager_options'){
						add_action( 'admin_notices', function(){
							echo "<div class='notice notice-warning'><p>" . sprintf(esc_html__('Permissions manager needs higher version of the theme (%s, %s) to work properly, please update theme to the latest version. Minimum theme version: %s', 'ait-permissions-manager'), self::$currentTheme->name, self::$currentTheme->version, self::$compatibleThemes[self::$currentTheme->stylesheet]) . "</p></div>";
						} );
					}
				}
			}
		}
	}

	public static function adminBodyClass($classes) {
		if(!empty($_REQUEST['page'])){
			if($_REQUEST['page'] == 'ait_permissions_manager_options'){
				$classes = explode(" ", $classes);
				array_push($classes, 'ait_permissions_manager_options');
				$classes = implode(" ", $classes);
			}
		}
		return $classes;
	}

	public static function enqueueAdminDesign($hook){
		$page = "";
		if(!empty($_REQUEST['page'])){
			$page = $_REQUEST['page'];
		}

		if(class_exists('AitTheme') && $page == 'ait_permissions_manager_options'){
			wp_enqueue_script('ait-permissions-manager-admin-script', plugin_dir_url(__FILE__) .'design/js/admin.js' , array('jquery' , 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable'));

			if(!function_exists('aitPaths')) return;

			$assetsUrl = aitPaths()->url->admin . '/assets';
			$min = ((defined('SCRIPT_DEBUG') and SCRIPT_DEBUG) or AIT_DEV) ? '' : '.min';
			$min = "";

			wp_enqueue_style('ait-colorpicker', "{$assetsUrl}/libs/colorpicker/colorpicker.css", array(), '2.2.1');
			wp_enqueue_style('ait-jquery-chosen', "{$assetsUrl}/libs/chosen/chosen.css", array(), '0.9.10');
			wp_enqueue_style('jquery-ui', "{$assetsUrl}/libs/jquery-ui/jquery-ui.css", array('media-views'), AIT_THEME_VERSION);
			wp_enqueue_style('jquery-switch', "{$assetsUrl}/libs/jquery-switch/jquery.switch.css", array(), '0.4.1');
			wp_enqueue_style('ait-admin-style', "{$assetsUrl}/css/style.css", array('media-views'), AIT_THEME_VERSION);
			wp_enqueue_style('ait-admin-options-controls', "{$assetsUrl}/css/options-controls" . "" . ".css", array('ait-admin-style', 'ait-jquery-chosen'), AIT_THEME_VERSION);
			$fontCssFile = aitUrl('css', '/libs/font-awesome.min.css');
			if($fontCssFile){
				wp_enqueue_style('ait-font-awesome-select', $fontCssFile, array(), '4.2.0');
			}

			wp_enqueue_script('ait.admin', "{$assetsUrl}/js/ait.admin.js", array('media-editor'), AIT_THEME_VERSION, TRUE);
			wp_enqueue_script('ait.admin.options', "{$assetsUrl}/js/ait.admin.options.js", array('ait.admin', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable'), AIT_THEME_VERSION, TRUE);
			wp_enqueue_script('ait.admin.options.elements', "{$assetsUrl}/js/ait.admin.options.elements.js", array('ait.admin', 'ait.admin.options', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable'), AIT_THEME_VERSION, TRUE);
			wp_enqueue_script('ait.admin.Tabs', "{$assetsUrl}/js/ait.admin.tabs.js", array('ait.admin', 'jquery'), AIT_THEME_VERSION, TRUE);
			wp_enqueue_script('ait-jquery-filedownload', "{$assetsUrl}/libs/file-download/jquery.fileDownload{$min}.js", array('jquery', 'ait.admin'), '1.3.3', TRUE);

			wp_enqueue_script('ait-colorpicker', "{$assetsUrl}/libs/colorpicker/colorpicker{$min}.js", array('jquery'), '2.2.1', TRUE);
			wp_enqueue_script('ait-jquery-chosen', "{$assetsUrl}/libs/chosen/chosen.jquery{$min}.js", array('jquery'), '1.0.0', TRUE);
			wp_enqueue_script('ait-jquery-sheepit', "{$assetsUrl}/libs/sheepit/jquery.sheepItPlugin{$min}.js", array('jquery', 'ait.admin'), '1.1.1-ait-1', TRUE);
			wp_enqueue_script('ait-jquery-deparam', "{$assetsUrl}/libs/jquery-deparam/jquery-deparam{$min}.js", array('jquery', 'ait.admin'), FALSE, TRUE);
			wp_enqueue_script('ait-jquery-rangeinput', "{$assetsUrl}/libs/rangeinput/rangeinput.min.js", array('jquery', 'ait.admin'), '1.2.7', TRUE);
			wp_enqueue_script('ait-jquery-numberinput', "{$assetsUrl}/libs/numberinput/numberinput{$min}.js", array('jquery', 'ait.admin'), FALSE, TRUE);
			wp_enqueue_script( 'jquery-ui-datepicker' );
			if(class_exists('AitLangs') and AitLangs::getCurrentLanguageCode() !== 'en'){
				wp_enqueue_script('ait-jquery-datepicker-translation', "{$assetsUrl}/libs/datepicker/jquery-ui-i18n{$min}.js", array('jquery', 'ait.admin', 'jquery-ui-datepicker'), FALSE, TRUE);
			}
			wp_enqueue_script('ait-jquery-switch', "{$assetsUrl}/libs/jquery-switch/jquery.switch{$min}.js", array('jquery', 'ait.admin'), FALSE, TRUE);
			wp_enqueue_script('ait-bootstrap-dropdowns', "{$assetsUrl}/libs/bootstrap-dropdowns/bootstrap-dropdowns{$min}.js", array('jquery', 'ait.admin'), FALSE, TRUE);
		}
	}

	public static function adminModifyThemeMenuCapabilities($cap, $page){
		// this is shorter, but we couldnt control the result, in the "if" version its precisely defined
		// $capability = "ait_".str_replace('-', '_', $page['slug']);

		$capability = $cap;
		if($page['slug'] == 'theme-options'){
			$capability = 'ait_theme_options';
		}
		if($page['slug'] == 'default-layout'){
			$capability = 'ait_default_layout';
		}
		if($page['slug'] == 'backup'){
			$capability = 'ait_backup';
		}
		if($page['slug'] == 'pages-options'){
			$capability = 'ait_pages_options';
		}

		/* events pro */
		if($page['slug'] == 'events-pro-options'){
			$capability = 'ait_events_pro_options';
		}
		/* events pro */

		return $capability;
	}

	public static function adminMenu(){
		$hook = add_submenu_page(
			"ait-theme-options",
			esc_html__("Permissions Manager", "ait-permissions-manager"),
			esc_html__("Permissions Manager", "ait-permissions-manager"),
			"ait_permissions_manager_options",
			'ait_permissions_manager_options',
			array(__CLASS__, 'adminPage')
		);
	}

	public static function adminPage(){
		echo '<div class="wrap">';
			echo '<div id="ait-permissions-manager-page" class="ait-admin-page ait-options-layout">';
				echo '<div class="ait-admin-page-wrap">';
					/* Hack for WP notifications, all will be placed right after this h2 */
					echo '<h2 style="display: none;"></h2>';

					echo '<div class="ait-options-page-header">';
						echo '<h3 class="ait-options-header-title">'. esc_html__('Permissions Manager', 'ait-permissions-manager') .'</h3>';
						echo '<div class="ait-options-header-tools">';
							echo '<a class="ait-scroll-to-top"><i class="fa fa-chevron-up"></i></a>';
							echo '<div class="ait-header-save">';
								echo '<button class="ait-save-permissions-manager-options">';
									esc_html_e('Save Options', 'ait-permissions-manager');
								echo '</button>';

								echo '<div id="action-indicator-save" class="action-indicator action-save"></div>';
							echo '</div>';
						echo '</div>';

						echo '<div class="ait-sticky-header">';
							echo '<h4 class="ait-sticky-header-title">'. esc_html__('Permissions Manager', 'ait-permissions-manager') .'<i class="fa fa-circle"></i><span class="subtitle"></span></h4>';
						echo '</div>';
					echo '</div>';

					echo '<div class="ait-options-page">';

						echo '<div class="ait-options-page-content">';

							echo '<div class="ait-options-sidebar">';
								echo '<div class="ait-options-sidebar-content">';
									echo '<ul id="ait-permissions-manager-tabs" class="ait-options-tabs">';
										foreach(AitPermissionsManager::getAvalaibleRoles() as $i => $themePackage){
											echo '<li id="ait-permissions-manager-'.$themePackage->slug.'-panel-tab"><a href="#ait-permissions-manager-'.$themePackage->slug.'-panel">'.$themePackage->name.'</a></li>';
										}
										/* custom capabilities manager */
										echo '<li id="ait-permissions-manager-custom_capabilities_manager-panel-tab"><a href="#ait-permissions-manager-custom_capabilities_manager-panel">'.esc_html__('Custom Capabilities Manager', 'ait-permissions-manager').'</a></li>';
										/* custom capabilities manager */
									echo '</ul>';
								echo '</div>';
							echo '</div>';

							echo '<div class="ait-options-content">';
								echo '<div class="ait-options-controls-container">';
									echo '<div id="ait-permissions-manager-panels" class="ait-options-controls ait-options-panels">';
										$availableCapabilities = AitPermissionsManager::getAvailableCapabilities();
										foreach(AitPermissionsManager::getAvalaibleRoles() as $i => $themePackage){
											echo '<div id="ait-permissions-manager-'.$themePackage->slug.'-panel" class="ait-options-group ait-options-panel" style="display: none;" data-role="'.$themePackage->slug.'">';

												echo '<div class="ait-controls-utils-bar no-tabs">';
													echo '<ul class="ait-element-utils">';
														echo '<li><a href="#" class="ait-reset-role-options" data-role="'.$themePackage->slug.'" data-message="'.esc_html__('Are you sure you want to reset all capabilities to their defaults for this role ?', 'ait-permissions-manager').'"><span class="action-indicator action-reset-group"></span>'.__('Reset to defaults', 'ait-permissions-manager').'</a></li>';
													echo '</ul>';
												echo '</div>';

												echo '<div id="ait-options-basic-packages" class="ait-controls-tabs-panel ait-options-basic">';
													$role = get_role( $themePackage->slug );
													foreach($availableCapabilities as $section => $sectionData){
														echo '<div class="ait-options-section ait-sec-title" data-section="'.$section.'">';
															echo '<h2 class="ait-options-section-title">'.$sectionData['label'].'</h2>';

															if(AitPermissionsManager::countAvailableCapabilities($sectionData['capabilities']) > 0){
																foreach($sectionData['capabilities'] as $capability => $optionData){
																	if($optionData['check']){
																		if(strpos($themePackage->slug, 'cityguide_') === false){
																			// not package role -> render all
																			AitPermissionsManager::renderCapabilityInput($role, $themePackage->slug, $capability, $optionData);
																		} else {
																			// package role
																			if($capability !== 'ait_item'){
																				// render all except 'ait_item'
																				AitPermissionsManager::renderCapabilityInput($role, $themePackage->slug, $capability, $optionData);
																			}
																		}

																	}
																}
															} else {
																echo '<div class="ait-options-section-help">'.esc_html__('There are no settings for the current section', 'ait-permissions-manager').'</div>';
															}

														echo '</div>';
													}
												echo '</div>';

											echo '</div>';
										}

										/* custom capabilities manager */
										echo '<div id="ait-permissions-manager-custom_capabilities_manager-panel" class="ait-options-group ait-options-panel ait-custom-capabilities-manager" style="display: none;">';
											echo '<div id="ait-options-basic-packages" class="ait-controls-tabs-panel ait-options-basic">';

												echo '<div class="ait-options-section ait-sec-title" data-section="custom_capabilities">';
													echo '<div class="ait-options-section-help">'.esc_html__('Refresh page to manage newly added capability', 'ait-permissions-manager').'</div>';

														/* clonable input */
														$inputs = get_option('ait_permissions_manager_custom_capabilities_current', array());
														$inputs = !is_array($inputs) ? array() : $inputs;
														$key = "custom_capabilities_manager";

														echo '<div class="ait-opt-container ait-opt-clone-main">';
															echo '<div class="ait-opt-wrap">';

																echo '<div class="ait-opt ait-opt-clone">';
																	echo '<div id="ait-opt-permissions-manager-'.$key.'" class="ait-clone-controls ui-sortable added_types_'. $key .'" data-db-key="'.$key.'" data-confirm-message="Are you sure?" data-min-forms="0" data-max-forms="0" data-allow-remove-all="true" style="display: block;">';

																		/* stored inputs */
																		foreach ($inputs as $index => $input) {
																			echo '<div id="ait-opt-permissions-manager-'.$key.'-'.$index.'_pregenerated'.$index.'" class="ait-clone-item ait-pregenerated-clone-item" idtemplate="ait-opt-permissions-manager-'.$key.'-'.$index.'-pregenerated">';
																				echo '<div class="form-input-handler ui-sortable-handle clone-sort">';
																					echo '<div class="form-input-title">' .$input['label']. '</div>';
																					echo '<a id="ait-opt-permissions-manager-'.$key.'_remove_current" href="#" class="ait-clone-remove-current" style="display: inline;">×</a>';
																				echo '</div>';

																				echo '<div class="form-input-content">';

																					/* check input */
																					echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="check" style="display: none">';
																						echo '<input type="hidden" value="true">';
																					echo '</div>';
																					/* check input */

																					/* slug input */
																					echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="slug">';
																						echo '<div class="ait-opt-wrap">';

																							echo '<div class="ait-opt-label">';
																								echo '<div class="ait-label-wrapper">';
																									echo '<label class="ait-label" >' . esc_html__('Capability Slug', 'ait-permissions-manager') . '</label>';
																								echo '</div>';
																								echo '<div class="ait-opt-help">';
																									echo '<div class="ait-help">';
																										echo esc_html__('Name of capability e.g. "edit_posts"', 'ait-permissions-manager');
																									echo '</div>';
																								echo '</div>';
																							echo '</div>';

																							echo '<div class="ait-opt ait-opt-string">';
																								echo '<div class="ait-opt-wrapper">';
																									echo '<input type="text" value="'.$input['slug'].'">';
																								echo '</div>';
																							echo '</div>';

																						echo '</div>';
																					echo '</div>';
																					/* slug input */

																					/* label input */
																					echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="label">';
																						echo '<div class="ait-opt-wrap">';

																							echo '<div class="ait-opt-label">';
																								echo '<div class="ait-label-wrapper">';
																									echo '<label class="ait-label" >' . esc_html__('Capability Label', 'ait-permissions-manager') . '</label>';
																								echo '</div>';

																								echo '<div class="ait-opt-help">';
																									echo '<div class="ait-help">';
																										echo esc_html__('Label for capability, used in role capability list', 'ait-permissions-manager');
																									echo '</div>';
																								echo '</div>';

																							echo '</div>';

																							echo '<div class="ait-opt ait-opt-string">';
																								echo '<div class="ait-opt-wrapper">';
																									echo '<input type="text" value="'.$input['label'].'">';
																								echo '</div>';
																							echo '</div>';

																						echo '</div>';
																					echo '</div>';
																					/* label input */

																					/* help input */
																					echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="help">';
																						echo '<div class="ait-opt-wrap">';

																							echo '<div class="ait-opt-label">';
																								echo '<div class="ait-label-wrapper">';
																									echo '<label class="ait-label" >' . esc_html__('Capability Help', 'ait-permissions-manager') . '</label>';
																								echo '</div>';
																								echo '<div class="ait-opt-help">';
																									echo '<div class="ait-help">';
																										echo esc_html__('Help text for capability, used in role capability list', 'ait-permissions-manager');
																									echo '</div>';
																								echo '</div>';
																							echo '</div>';

																							echo '<div class="ait-opt ait-opt-string">';
																								echo '<div class="ait-opt-wrapper">';
																									echo '<input type="text" value="'.$input['help'].'">';
																								echo '</div>';
																							echo '</div>';

																						echo '</div>';
																					echo '</div>';
																					/* help input */

																				echo '</div>';
																			echo '</div>';
																		}
																		/* stored inputs */

																		/* no forms */
																		echo '<div id="ait-opt-permissions-manager-'.$key.'_noforms_template" class="ait-clone-noforms" style="display: none;">';
																			echo __('No Items Defined', 'ait-permissions-manager');
																		echo '</div>';
																		/* no forms */

																		/* template */
																		echo '<div id="ait-opt-permissions-manager-'.$key.'_template" class="ait-clone-item">';
																			echo '<div class="form-input-handler ui-sortable-handle clone-sort">';
																				echo '<div class="form-input-title">' .__('Input', 'ait-permissions-manager'). '</div>';
																				echo '<a id="ait-opt-permissions-manager-'.$key.'_remove_current" href="#" class="ait-clone-remove-current" style="display: inline;">×</a>';
																			echo '</div>';

																			echo '<div class="form-input-content">';
																				/* check input */
																				echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="check" style="display: none">';
																					echo '<input type="hidden" value="true">';
																				echo '</div>';
																				/* check input */

																				/* slug input */
																				echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="slug">';
																					echo '<div class="ait-opt-wrap">';

																						echo '<div class="ait-opt-label">';
																							echo '<div class="ait-label-wrapper">';
																								echo '<label class="ait-label" >' . esc_html__('Capability Slug', 'ait-permissions-manager') . '</label>';
																							echo '</div>';
																							echo '<div class="ait-opt-help">';
																								echo '<div class="ait-help">';
																									echo esc_html__('Name of capability e.g. "edit_posts"', 'ait-permissions-manager');
																								echo '</div>';
																							echo '</div>';
																						echo '</div>';

																						echo '<div class="ait-opt ait-opt-string">';
																							echo '<div class="ait-opt-wrapper">';
																								echo '<input type="text" value="">';
																							echo '</div>';
																						echo '</div>';

																					echo '</div>';
																				echo '</div>';
																				/* slug input */

																				/* label input */
																				echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="label">';
																					echo '<div class="ait-opt-wrap">';

																						echo '<div class="ait-opt-label">';
																							echo '<div class="ait-label-wrapper">';
																								echo '<label class="ait-label" >' . esc_html__('Capability Label', 'ait-permissions-manager') . '</label>';
																							echo '</div>';
																							echo '<div class="ait-opt-help">';
																								echo '<div class="ait-help">';
																									echo esc_html__('Label for capability, used in role capability list', 'ait-permissions-manager');
																								echo '</div>';
																							echo '</div>';
																						echo '</div>';

																						echo '<div class="ait-opt ait-opt-string">';
																							echo '<div class="ait-opt-wrapper">';
																								echo '<input type="text" value="">';
																							echo '</div>';
																						echo '</div>';

																					echo '</div>';
																				echo '</div>';
																				/* label input */

																				/* help input */
																				echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="help">';
																					echo '<div class="ait-opt-wrap">';

																						echo '<div class="ait-opt-label">';
																							echo '<div class="ait-label-wrapper">';
																								echo '<label class="ait-label" >' . esc_html__('Capability Help', 'ait-permissions-manager') . '</label>';
																							echo '</div>';
																							echo '<div class="ait-opt-help">';
																								echo '<div class="ait-help">';
																									echo esc_html__('Help text for capability, used in role capability list', 'ait-permissions-manager');
																								echo '</div>';
																							echo '</div>';
																						echo '</div>';

																						echo '<div class="ait-opt ait-opt-string">';
																							echo '<div class="ait-opt-wrapper">';
																								echo '<input type="text" value="">';
																							echo '</div>';
																						echo '</div>';

																					echo '</div>';
																				echo '</div>';
																				/* help input */

																			echo '</div>';
																		echo '</div>';
																		/* template */

																	echo '</div>';

																	/* tools */
																	echo '<div id="ait-opt-permissions-manager-'.$key.'_controls" class="ait-clone-tools">';
																		echo '<div id="ait-opt-permissions-manager-'.$key.'_add" class="ait-clone-add ait-clone-control-link">';
																			echo '<a href="#">'.esc_html__('+ Add New Item', 'ait-permissions-manager').'</a>';
																		echo '</div>';
																		echo '<div id="ait-opt-permissions-manager-'.$key.'_toggle_all" class="ait-clone-toggle-all ait-clone-control-link">';
																			echo '<a href="#">'.esc_html__('Open/Collapse All Items', 'ait-permissions-manager').'</a>';
																		echo '</div>';
																		echo '<div id="ait-opt-permissions-manager-'.$key.'_remove_last" class="ait-clone-remove-last ait-clone-control-link" style="display: none;">';
																			echo '<a href="#">'.esc_html__('Remove', 'ait-permissions-manager').'</a>';
																		echo '</div>';
																		echo '<div id="ait-opt-permissions-manager-'.$key.'_remove_all" class="ait-clone-remove-all ait-clone-control-link">';
																			echo '<a href="#">'.esc_html__('Remove All Items', 'ait-permissions-manager').'</a>';
																		echo '</div>';
																	echo '</div>';
																	/* tools */

																echo '</div>';

															echo '</div>';
														echo '</div>';
														/* clonable input */

												echo '</div>';

											echo '</div>';
										echo '</div>';
										/* custom capabilities manager */

									echo '</div>';
								echo '</div>';
							echo '</div>';
						echo '</div>';

					echo '</div>';
				echo '</div>';
			echo '</div>';
		echo '</div>';
	}
	/* ADMIN SETUP */

	/* AJAX SETUP */
	public static function ajaxSaveOptions(){
		$result = array(
			'roles' => array(
				'fail' => false,
				'msg' => "OK",
			),
			'custom_capabilities'  => array(
				'fail' => false,
				'msg' => "OK",
			),
		);

		$counter = 0;
		foreach(AitPermissionsManager::getAvalaibleRoles() as $i => $themePackage){
			$role_data = !empty($_POST['data']['roles'][$themePackage->slug]) ? $_POST['data']['roles'][$themePackage->slug] : array();
			$counter += AitPermissionsManager::updateRoleCapabilities($themePackage->slug, $role_data);
		}

		if($counter != count(AitPermissionsManager::getAvalaibleRoles())){
			$result['roles'] = array(
				'fail' => true,
				'msg' => "Failed to update",
			);
		}

		// settings
		if(!empty($_POST['data']['custom_capabilities'])){
			if(update_option('ait_permissions_manager_custom_capabilities_current', $_POST['data']['custom_capabilities']) == false){
				// when the updated data are the same, this will be always false ... it doesnt mean that something went wrong ;)
				$result['custom_capabilities'] = array(
					'fail' => true,
					'msg' => "Failed to update",
				);
			} else {
				AitPermissionsManager::prepareCustomCapabilities();
			}
		}

		// run garbage collector
		//AitPermissionsManager::customCapabilitesGarbageCollector();

		header('Content-Type: application/json');
		echo json_encode($result);
		exit();
	}

	public static function ajaxResetRole(){
		$result = array(
			'status' => array(
				'fail' => false,
				'msg' => "OK",
			),
		);

		if(!empty($_POST['data']['role'])){
			$role = get_role( $_POST['data']['role'] );
			$roleBackupData = get_option('ait_permissions_manager_role_'.$_POST['data']['role'], array());

			if(is_array($roleBackupData) && count($roleBackupData) > 0){
				// remove all capabilities from role
				foreach($role->capabilities as $key => $value){
					$role->remove_cap($key);
				}
				// attach all capabilities from backup
				foreach($roleBackupData as $key => $value){
					$role->add_cap($key);
				}
			} else {
				$result = array(
					'status' => array(
						'fail' => true,
						'msg' => "Failed to reset, no backup data",
					),
				);
			}

		} else {
			$result = array(
				'status' => array(
					'fail' => true,
					'msg' => "Failed to reset, no role defined",
				),
			);
		}

		header('Content-Type: application/json');
		echo json_encode($result);
		exit();
	}
	/* AJAX SETUP */

	/* HELPER FUNCTIONS */
	public static function backupCurrentConfiguration(){
		$config = AitPermissionsManager::getRawConfiguration();
		update_option('ait_permissions_manager_configuration', $config);
	}

	public static function backupCurrentCapabilities(){
		foreach (AitPermissionsManager::getAvalaibleRoles() as $index => $package) {
			$role = get_role($package->slug);
			if(is_object($role)){
				if(get_option('ait_permissions_manager_role_'.$package->slug) == false){
					update_option('ait_permissions_manager_role_'.$package->slug, $role->capabilities);
				}
			}
		}
	}

	public static function initAdministratorCapabilities(){
		$role = get_role('administrator');

		// theme capabilities
		$role->add_cap('ait_theme_options');
		$role->add_cap('ait_default_layout');
		$role->add_cap('ait_backup');
		$role->add_cap('ait_pages_options');

		// plugin capabilities
		$role->add_cap('ait_announcements_bar_options');
		$role->add_cap('ait_import_export_options');
		$role->add_cap('ait_directory_migration_options');
		$role->add_cap('ait_events_pro_options');
		$role->add_cap('ait_item_extension_options');
		$role->add_cap('ait_languages_options');
		$role->add_cap('ait_permissions_manager_options');
		$role->add_cap('ait_updater_options');
		$role->add_cap('ait_quick_comments_options');
		$role->add_cap('ait_sysinfo_options');
	}

	public static function getAlreadyEnabledCapabilities(){
		// use only on activation hook

		// known issue > because all ait cpt capabilites ends like *_post || *_posts .. the regex for pure post cpt is also matched
		// result of this, is when eg. ait-toggles are enabled by default, so the post cpt will be enabled also
		// fix is to change the regex to match only the post cpt capabilites
		// status > fixed (updated regex)

		$capPatterns = array(
			'ait_event_pro'		=> '/(eventspro)/',
			'ait_food_menu'		=> '/(food_menu)/',
			'ait_review'		=> '/(item_reviews)/',
			'ait_special_offer'	=> '/(special_offer)/',
			'post'				=> '/^[a-z]+(_posts)/',	// > /^[a-z]+(_posts)/ || /(post)/
			'page'				=> '/(page)/',
		);
		foreach (AitPermissionsManager::getAvalaibleRoles() as $index => $package) {
			$role = get_role($package->slug);
			if(is_object($role)){
				$keys = array_keys($role->capabilities);
				foreach ($capPatterns as $capability => $pattern) {
					if(count(preg_grep($pattern,$keys)) > 0){
						$role->add_cap($capability);
					}
				}
			}
		}
	}

	public static function updateRoleCapabilities($role_slug, $role_data = array()){
		$result = 1; // 1 => success | 0 => failure

		if(!empty($role_data)){
			$role = get_role($role_slug);
			$data_available = AitPermissionsManager::getAvailableCapabilities();
			$data_enabled = $role_data; // array => section => cap_key = cap_val

			$role_caps = array();

			foreach($data_available as $section => $section_data){
				$section_enabled_data = $data_enabled[$section];
				foreach($section_data['capabilities'] as $cap_slug => $cap_data){
					if($cap_data['check']){
						if(!empty($section_enabled_data[$cap_slug])){ // prevent notices, because ait-item is not enabled for all roles through pm
							if(filter_var($section_enabled_data[$cap_slug], FILTER_VALIDATE_BOOLEAN) == true){
								$role_caps[$cap_slug] = true;

								if(strpos($role->name, 'cityguide_') === false){
									if(!empty($cap_data['caps'])){
										if(!empty($cap_data['caps']['enable'])){
											foreach($cap_data['caps']['enable'] as $index => $cap_name){
												$role_caps[$cap_name] = true;
											}
										}
										if(!empty($cap_data['caps']['disable'])){
											foreach($cap_data['caps']['disable'] as $index => $cap_name){
												$role_caps[$cap_name] = false;
											}
										}
									}
								}else{
									//package role, get limited capabilities for Events Pro posts
									if($cap_slug == 'ait_event_pro'){
										if(!empty($cap_data['limitedCaps'])){
											if(!empty($cap_data['limitedCaps']['enable'])){
												foreach($cap_data['limitedCaps']['enable'] as $index => $cap_name){
													$role_caps[$cap_name] = true;
												}
											}
											if(!empty($cap_data['limitedCaps']['disable'])){
												foreach($cap_data['limitedCaps']['disable'] as $index => $cap_name){
													$role_caps[$cap_name] = false;
												}
											}
										}
									}else{
										if(!empty($cap_data['caps'])){
											if(!empty($cap_data['caps']['enable'])){
												foreach($cap_data['caps']['enable'] as $index => $cap_name){
													$role_caps[$cap_name] = true;
												}
											}
											if(!empty($cap_data['caps']['disable'])){
												foreach($cap_data['caps']['disable'] as $index => $cap_name){
													$role_caps[$cap_name] = false;
												}
											}
										}
									}
								}
							} else {
								$role_caps[$cap_slug] = false;
								if(!empty($cap_data['caps'])){
									if(!empty($cap_data['caps']['enable'])){
										foreach($cap_data['caps']['enable'] as $index => $cap_name){
											$role_caps[$cap_name] = false;
										}
									}
									if(!empty($cap_data['caps']['disable'])){
										foreach($cap_data['caps']['disable'] as $index => $cap_name){
											$role_caps[$cap_name] = false;
										}
									}
								}
							}
						}
					} else {
						$role_caps[$cap_slug] = false;
						if(!empty($cap_data['caps'])){
							if(!empty($cap_data['caps']['enable'])){
								foreach($cap_data['caps']['enable'] as $index => $cap_name){
									$role_caps[$cap_name] = false;
								}
							}
							if(!empty($cap_data['caps']['disable'])){
								foreach($cap_data['caps']['disable'] as $index => $cap_name){
									$role_caps[$cap_name] = false;
								}
							}
						}
					}
				}
			}

			/* log debug data */
			AitPermissionsManager::log('info', 'Role > '.$role->name);
			/* log debug data */

			foreach ($role_caps as $cap_slug => $cap_value) {
				if($cap_value){
					$role->add_cap($cap_slug);
					AitPermissionsManager::log('info', '<'.$role->name.'> added capability ('.$cap_slug.')');
				} else {
					$role->remove_cap($cap_slug);
					AitPermissionsManager::log('info', '<'.$role->name.'> removed capability ('.$cap_slug.')');
				}
			}
		}

		return $result;
	}

	public static function getAvalaibleRoles(){
		$result = array();

		// get all editable user roles, including packages
		$wp_roles = get_editable_roles();

		// unset administrator from the $wp_roles
		unset($wp_roles['administrator']);
		// unset maybe also the subscriber
		unset($wp_roles['subscriber']);

		if(class_exists('ThemePackages')){	// directory type theme .. maybe change for defined('AIT_THEME_TYPE', 'directory') ?
			$themePackages = new ThemePackages();
			$avalaiblePackages = $themePackages->getAvalaible();
			foreach ($avalaiblePackages as $index => $themePackage) {
				$package = (object)array(
					'slug'	=> $themePackage->getSlug(),
					'name'	=> $themePackage->getName(),
				);
				array_push($result, $package);
			}
		}

		// append also default wordpress roles
		foreach($wp_roles as $slug => $roleData){
			if(strpos($slug, 'cityguide_') === false) {	// skip the theme package role, as they are already in the $results field
				array_push($result, (object)array(
					'slug'	=> $slug,
					'name'	=> $roleData['name'],
				));
			}
		}

		return $result;
	}

	public static function getRawConfiguration(){
		$config = include self::$paths['config'].'/available-capabilities.php';
		return $config;
	}

	public static function getAvailableCapabilities(){
		$config = AitPermissionsManager::getRawConfiguration();
		return apply_filters('ait_permisssions_manager_available_capabilities', $config);
	}

	public static function renderCapabilityInput($role, $roleSlug, $capability, $optionData){
		// $role 		=> WP_Role object
		// $capability 	=> string
		// $optionData	=> array [label|help]

		if(!is_object($role)){
			$role = get_role($roleSlug);
		}

		$selectedOn = $role->has_cap($capability) ? 'selected="selected"' : '';
		$selectedOff = $selectedOn == "" ? 'selected="selected"' : "";

		echo '<div class="ait-opt-container ait-opt-on-off-main" data-capability="'.$capability.'">';
			echo '<div class="ait-opt-wrap">';

				if(!empty($optionData['label'])){
				echo '<div class="ait-opt-label">';
					echo '<div class="ait-label-wrapper">';
						echo '<label class="ait-label">'.$optionData['label'].'</label>';
					echo '</div>';
				echo '</div>';
				}

				echo '<div class="ait-opt ait-opt-on-off">';
					echo '<div class="ait-opt-wrapper">';

						echo '<div class="ait-opt-switch">';
							echo '<select>';
								echo '<option value="on" '.$selectedOn.'>On</option>';
								echo '<option value="off" '.$selectedOff.'>Off</option>';
							echo '</select>';
						echo '</div>';

					echo '</div>';
				echo '</div>';

				if(!empty($optionData['help'])){
				echo '<div class="ait-opt-help">';
					echo '<div class="ait-help">';
						echo $optionData['help'];
					echo '</div>';
				echo '</div>';
				}

			echo '</div>';
		echo '</div>';
	}

	public static function countAvailableCapabilities($section = array()){
		// returns the number of capabilities that are going to be displayed
		$count = 0;
		foreach($section as $capability => $optionData){
			if($optionData['check']){
				$count++;
			}
		}
		return $count;
	}

	public static function getThemeConfiguration(){
		$themeConfiguration = array();

		if(function_exists('aitPath')){
			$themeConfiguration = include aitPath('config', '/@theme-configuration.php');
		}

		return $themeConfiguration;
	}

	public static function checkOptionCompatibility($option_key, $check_type, $additional_check = false){
		// check_type > cpt | other, not defined at the moment

		$result = false;

		if($check_type == "cpt"){
			$themeConfiguration = AitPermissionsManager::getThemeConfiguration();
			if(!empty($themeConfiguration)){
				$result = in_array($option_key, $themeConfiguration['ait-theme-support']['cpts']) && $additional_check;
			}
		}

		return $result;
	}

	public static function log($type, $message, $logtime = true, $format = 'U'){

		if(AIT_PERMISSIONS_MANAGER_PLUGIN_DEBUG){
			$filename = self::$paths['logs'].'/'.self::$log_filename;
			$backtrace = debug_backtrace();

			//$template = "{time}{separator}{type}{separator}{initiator}{separator}{message}"; // old template
			$template = "[{type}] {time}{separator}{initiator}{separator}{message}";

			$write = $template;

			$time = $logtime ? date($format) : '';
			$write = str_replace('{time}', $time, $write);

			$write = str_replace('{type}', $type, $write);

			// backtrace[1] holds the function which was this called from ... backtrace[0] is current funtion;
			$initiator = isset($backtrace[1]) ? $backtrace[1]['class'].$backtrace[1]['type'].$backtrace[1]['function'] : __METHOD__;
			$write = str_replace('{initiator}', $initiator, $write);

			$write = str_replace('{message}', $message, $write);

			$separator = "/";
			$write = str_replace('{separator}', $separator, $write);

			error_log($write."\r\n", 3, $filename);
		}
	}
	/* HELPER FUNCTIONS */

	/* CUSTOM CAPABILITIES MANAGER */
	public static function prepareCustomCapabilities(){
		// need to ensure when input is removed from the clonable input, also the role is removed
		// use check -> false
		$capabilities_stored = get_option('ait_permissions_manager_custom_capabilities', false);
		$capabilities_current = get_option('ait_permissions_manager_custom_capabilities_current', false);

		// iterate through new field of options
		// check against stored capabilities and remove when found
		// at the end, check the stored field
		$capabilities_new = array();
		if($capabilities_stored != false){
			// have stored capabilities
			// compare data
			foreach ($capabilities_current as $index => $data) {
				$stored_index = AitPermissionsManager::getCapabilityIndexBySlug($data['slug'], $capabilities_stored);

				if($stored_index != -1){
					// found index ... this capability is in both fields, so it is still present
					// we can remove this index from the stored field .. so we can determine which options were removed
					array_splice($capabilities_stored, $stored_index, 1);

					$data['check'] = true;
					array_push($capabilities_new, $data);
				} else {
					// index not found in the old field ... this capability was added
					$data['check'] = true;
					array_push($capabilities_new, $data);
				}
			}

			foreach ($capabilities_stored as $index => $data){
				// set check values to false, because these settings were removed from the clonable input
				$data['check'] = false;
				array_push($capabilities_new, $data);
			}

			update_option('ait_permissions_manager_custom_capabilities', $capabilities_new);
		} else {
			// doesnt have stored capabilities
			update_option('ait_permissions_manager_custom_capabilities', $capabilities_current);
		}

		// run garbage collector
	}

	public static function getCapabilityIndexBySlug($slug, $capabilities = array()){
		$result = -1;
		if(is_array($capabilities)){
			foreach ($capabilities as $index => $data) {
				if($data['slug'] == $slug){
					$result = $index;
				}
			}
		}
		return $result;
	}

	public static function customCapabilitesGarbageCollector(){
		/* refactor this method > needs better logic */

		// main idea -> clean the stored capabilities array
		// when capability is removed from clonable input, it stays in the database to be removed as a capability from role
		// custom capability removed -> save options -> refresh -> capability not visible in admin, but still present in role -> save options -> refresh -> capability not visible in admin, not present in role || now the garbage collector would take place -> remove the option from the stored field
		// stored field key > ait_permissions_manager_custom_capabilities
		$garbageCollector = get_option('ait_permissions_manager_custom_capabilities_garbage_collector', false);
		if($garbageCollector != false){
			if($garbageCollector['save_count'] == 2){
				$capabilities_stored = get_option('ait_permissions_manager_custom_capabilities', false);
				$capabilities_current = get_option('ait_permissions_manager_custom_capabilities_current', false);

				if($capabilities_stored != false){
					$avalaible_roles = AitPermissionsManager::getAvalaibleRoles();

					foreach ($capabilities_stored as $index => $data) {
						$capability = $data['slug']; 	// capability to check
						$cap_role_count = 0;

						foreach($avalaible_roles as $i => $role_data){
							$role = get_role( $role_data->slug );
							if($role->has_cap($capability) == false){
								// role doesnt have the capability, increment the counter
								$cap_role_count++;
							}
						}

						if($cap_role_count == count($avalaible_roles)){
							// no role have the capability, remove it from the array
							array_splice($capabilities_stored, $index, 1);
						}
					}

					update_option('ait_permissions_manager_custom_capabilities', $capabilities_stored);
					update_option('ait_permissions_manager_custom_capabilities_garbage_collector', array('save_count' => 1));
				}
			} else {
				update_option('ait_permissions_manager_custom_capabilities_garbage_collector', array('save_count' => 2));
			}
		} else {
			update_option('ait_permissions_manager_custom_capabilities_garbage_collector', array('save_count' => 1));
		}
	}

	public static function customCapabilitiesManager($config){
		// stored data = [{slug: "some_slug", label: "some label", "help" : "some help"}, {slug: "some_slug", label: "some label", "help" : "some help"}]
		$custom_capabilities = get_option('ait_permissions_manager_custom_capabilities', false);

		if(is_array($custom_capabilities)){
			$capabilities = array();
			foreach ($custom_capabilities as $index => $data) {
				$capability = array(
					'label'	=> $data['label'],
					'help'	=> $data['help'],
					'check'	=> $data['check'],
				);
				$capabilities[$data['slug']] = $capability;
			}

			$config['custom_capabilities'] = array(
				'label'	=> 'Custom Capabilities',
				'capabilities' => $capabilities,
			);
		}
		return $config;
	}
	/* CUSTOM CAPABILITIES MANAGER */
}
?>