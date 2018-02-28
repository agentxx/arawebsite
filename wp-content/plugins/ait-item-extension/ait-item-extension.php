<?php
/*
Plugin Name: AIT Item Extension
Plugin URI: http://ait-themes.club
Description: Custom fields for Item Custom Post Type
Version: 1.23
Author: AitThemes.Club
Author URI: http://ait-themes.club
Text Domain: ait-item-extension
Domain Path: /languages
License: GPLv2 or later
*/

/* trunk@r256 */

define("AIT_EXTENSION_ENABLED" , true);

AitItemExtension::init();

class AitItemExtension {
	protected static $themeOptionsKey;

	protected static $currentTheme;
	protected static $compatibleThemes;
	protected static $paths;

	public static function init(){
		$theme = wp_get_theme();
		self::$currentTheme = $theme->parent() != false ? $theme->parent()->stylesheet : $theme->stylesheet;	// this return parent theme on active child theme
		self::$compatibleThemes = array('skeleton', 'cityguide', 'directory2', 'eventguide', 'foodguide', 'businessfinder2');

		// not used at the moment
		self::$themeOptionsKey = sanitize_key(get_stylesheet()); // because theme options are stored _ait_{$theme}_theme_opts and on child theme _ait_{$childTheme}_theme_opts

		self::$paths = array(
			'config' => dirname( __FILE__ ).'/config',
			'design' => dirname( __FILE__ ).'/design',
			'templates'	 => dirname( __FILE__ ).'/templates',
		);

		// WP Plugin functions
		register_activation_hook( __FILE__, array(__CLASS__, 'onActivation') );
		register_deactivation_hook(  __FILE__, array(__CLASS__, 'onDeactivation') );
		add_action('after_switch_theme', array(__CLASS__, 'themeSwitched'));

		add_action('plugins_loaded', array(__CLASS__, 'onLoaded'));
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(__CLASS__, 'pluginActionLinks'));
		add_action('init', array(__CLASS__, 'onInit'));

		// Metabox functions
		add_action('add_meta_boxes', array(__CLASS__, 'addMetabox'), 12, 0);
		add_action('save_post', array(__CLASS__, 'saveMetabox'), 10, 3);

		// Template functions
		add_filter('wplatte-get-template-part', array(__CLASS__, 'getTemplate'), 10, 3);

		// Design functions
		add_filter('admin_body_class', array(__CLASS__, 'adminBodyClass'), 10, 1);
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueueAdminDesign') );
		add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueueDesign') );

		// Ajax functions
		add_action( 'wp_ajax_aitExtensionSaveGeneralOptions', array(__CLASS__, 'ajaxSaveGeneralOptions'));
	}

	/* WP PLUGIN FUNCTIONS */
	public static function onActivation(){
		AitItemExtension::checkPluginCompatibility(true);

		/* update from old verion to new */
		$pluginData = get_option('ait-plugin-item-extension', array()); // $pluginData = array('version' => 1.12); // any other data could be stored here
		if(is_array($pluginData) && empty($pluginData)){
			// there is nothing stored in the database for this plugin
			// so we can run the data update

			// note > all previous plugin data remains untouched so we can roll back to older version od the plugin if necessary
			AitItemExtension::updateThemeSettings();	// updates the database settings format for theme options
			AitItemExtension::updateItemSettings();		// updates the database settings format for item metabox

			// store the current version in the database for future updates
			update_option('ait-plugin-item-extension', array('version' => "1.12")); // do not change 1.12 is the version where the option was created, use the else branch to modify this number
		} else {
			// (future updates) there is plugin data ... now we need to check the current version with the db version
			// if($pluginData['version'] < *get the current version of the plugin*){ *run some updates* }
			require_once(ABSPATH . 'wp-admin/includes/plugin.php' );
			$wpPluginData = get_plugin_data( __FILE__ );
			$updateData = array();

			if(version_compare($pluginData['version'],  "1.13", '<')){
				AitItemExtension::updateThemeSettings();
				AitItemExtension::updateItemSettings();
				$updateData['status'] = "updated";
			}

			// always update the version in the database
			$updateData['version'] = $wpPluginData['Version'];
			update_option('ait-plugin-item-extension', $updateData);
		}
		/* update from old verion to new */

		if(class_exists('AitCache')){
			AitCache::clean();
		}
	}

	public static function onDeactivation(){
		if(class_exists('AitCache')){
			AitCache::clean();
		}
	}

	public static function themeSwitched(){
		AitItemExtension::checkPluginCompatibility();
	}

	public static function checkPluginCompatibility($die = false){
		if ( !in_array(self::$currentTheme, self::$compatibleThemes) ) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins(plugin_basename( __FILE__ ));
			if($die){
				wp_die('Current theme is not compatible with Item Extension plugin :(', '',  array('back_link'=>true));
			} else {
				add_action( 'admin_notices', function(){
					echo "<div class='error'><p>" . _x('Current theme is not compatible with Item Extension plugin!', 'ait-item-extension') . "</p></div>";
				} );
			}
		}
	}

	public static function onLoaded(){
		add_action('admin_menu', array(__CLASS__, 'adminMenu'));

		load_plugin_textdomain('ait-item-extension', false,  dirname(plugin_basename(__FILE__ )) . '/languages');
	}

	public static function onInit(){

	}

	public static function pluginActionLinks($links){
		$link = sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=ait_item_extension_options'), __('Settings', 'ait-item-extension'));
		array_unshift($links, $link);
		return $links;
	}
	/* WP PLUGIN FUNCTIONS */

	/* CONFIGURATION FUNCTIONS */
	public static function updateThemeSettings(){
		global $wpdb;
		$results = $wpdb->get_results( 'SELECT `option_name`, `option_value` FROM `'.$wpdb->prefix.'options` WHERE `option_name` LIKE "%ait-item-extension-field-%"', ARRAY_A );
		if(is_array($results) && !empty($results)){
			$package_slugs = array();

			foreach($results as $result){
				$package_slug = str_replace("ait-item-extension-field-", "", $result['option_name']);
				array_push($package_slugs, $package_slug);

				$package_inputs = unserialize($result['option_value']);
				$new_inputs = array();

				$inputs_corelation = array();

				if(is_array($package_inputs) && !empty($package_inputs)){
					foreach ($package_inputs as $index => $input_data) {
						$uniqid = uniqid(mt_rand(), true);

						$new_input_structure = array(
							'uid'	=> $uniqid,
							'type'	=> $input_data['type'] === 'checkbox' ? 'onoff' : $input_data['type'],
							'label' => $input_data['name'],
							'help'	=> "",
							'value'	=> "",
							'min'	=> "",
							'max'	=> "",
							'step'	=> "",
						);

						array_push($new_inputs, $new_input_structure);
						array_push($inputs_corelation, array( $input_data['slug'] => $uniqid ));
					}
				}
				update_option('ait_item_extension_'.$package_slug.'_options', $new_inputs);
				update_option('ait_item_extension_'.$package_slug.'_corelation', $inputs_corelation);
			}
			update_option('ait_item_extension_package_slugs', $package_slugs);
		}
	}

	public static function updateItemSettings(){
		global $wpdb;
		$results = $wpdb->get_results( 'SELECT * FROM `'.$wpdb->prefix.'postmeta` WHERE `meta_key` LIKE "%ait-item-extension-custom-field%"', ARRAY_A );
		if(is_array($results) && !empty($results)){
			foreach ($results as $result) {
				$postId = $result['post_id'];
				$input_data = unserialize($result['meta_value']);
				if(is_array($input_data) && !empty($input_data)){

					$package_slugs = get_option('ait_item_extension_package_slugs', array());
					$package_slug = false;
					foreach ($package_slugs as $index => $slug) {
						if(strpos(key($input_data), $slug) !== false){
							$package_slug = $slug;
						}
					}

					// get the defined options from theme options
					$package_options = get_option('ait_item_extension_'.$package_slug.'_options', array());
					if(is_array($package_options) && !empty($package_options)){

						/* prepare new item options array */
						$new_inputs = array();
						foreach ($package_options as $index => $option_data) {
							$new_inputs[$option_data['type']] = array();
						}
						/* prepare new item options array */

						$inputs_corelation = get_option('ait_item_extension_'.$package_slug.'_corelation', array());

						foreach ($input_data as $input_slug => $input_value) {

							// find uid for input
							$input_uid = "";
							foreach ($inputs_corelation as $index => $corelation) {
								if(isset($corelation[$input_slug])){
									$input_uid = $corelation[$input_slug];
									break;
								}
							}
							// find uid for input

							// find type for input
							$input_type = "";
							foreach ($package_options as $index => $option_data) {
								if($option_data['uid'] == $input_uid){
									$input_type = $option_data['type'];
									break;
								}
							}
							// find type for input

							if($input_type === "onoff"){
								$new_inputs[$input_type][$input_uid] = $input_value === "true" ? "on" : "off";
							} else {
								$new_inputs[$input_type][$input_uid] = $input_value;
							}
						}

						update_post_meta($postId, '_ait-item_item-extension', $new_inputs);
					}
				}
			}
		}
	}
	/* CONFIGURATION FUNCTIONS */

	/* TEMPLATE FUNCTIONS */
	public static function getTemplate($templates, $slug, $name){
		$ok = true;
		foreach(glob(self::$paths['templates'] . '/*.php') as $file){
			$filename = basename($file, '.php');
			if(!self::contains($slug, $filename)){
				$ok = false;
			}else{
				$ok = true;
				break;
			}
		}

		if(!$ok){
			return $templates;
		}

		// create name of file
		// e.g. 'parts/entry-date-format-<NAME>-loop.php'
		// e.g. 'parts/entry-date-format-<NAME>.php'
		if($name){
			//if(!is_singular()) $templates[] = "{$slug}-{$name}-loop.php";
			$templates[] = "{$slug}-{$name}.php";
		}

		// e.g. 'parts/entry-date-format-loop.php'
		// e.g. 'parts/entry-date-format.php'
		//if(!is_singular()) $templates[] = "{$slug}-loop.php";
			$templates[] = "{$slug}.php";

		$locatedInTheme = locate_template($templates, false, false);


		$pluginDir = self::$paths['templates'];

		if(!$locatedInTheme){ // in theme file does not exist, load it from plugin
			$newTemplate = '';
			foreach($templates as $tmpl){
				$tmpl = basename($tmpl);

				if(file_exists("$pluginDir/$tmpl")){
					$newTemplate = "$pluginDir/$tmpl";
				}else{
					continue;
				}
			}
			if(!$newTemplate){
				trigger_error("Template '$tmpl' does not exist in plugin dir nor theme dir");
			}
			return $newTemplate;
		} else {
			return $locatedInTheme; // exist in theme
		}
	}
	/* TEMPLATE FUNCTIONS */

	/* DESIGN FUNCTIONS */
	public static function adminBodyClass($classes) {
		if(!empty($_REQUEST['page'])){
			if($_REQUEST['page'] == 'ait_item_extension_options'){
				$classes = explode(" ", $classes);
				array_push($classes, 'ait_item_extension_options');
				$classes = implode(" ", $classes);
			}
		}
		return $classes;
	}

	public static function enqueueAdminDesign($hook){
		if(!empty($_REQUEST['page'])){
			if($_REQUEST['page'] == 'ait_item_extension_options'){
				wp_enqueue_script('ait-item-extension-admin-script', plugin_dir_url(__FILE__) .'design/js/admin.js' , array('jquery' , 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable'));
				//wp_enqueue_style('ait-item-extension-admin-style', plugin_dir_url(__FILE__) .'design/css/admin.css' );

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
		} else {
			//wp_enqueue_style('ait-item-extension-admin-style', plugin_dir_url(__FILE__) .'design/css/admin.css' );
		}
	}

	public static function enqueueDesign(){
		// styles
		$assetPath = file_exists(aitPath('css').'/ait-item-extension.css') ? aitUrl('css').'/ait-item-extension.css' : plugins_url( '/design/css/frontend.css' , __FILE__ );
		wp_enqueue_style( 'ait-item-extension-frontend', $assetPath, false, false, 'screen' );
	}
	/* DESIGN FUNCTIONS */

	/* METABOX OPTIONS */
	public static function addMetabox(){
		if(!class_exists('AitToolkit')) return;

		// custom metabox title
		$storedInputs = AitItemExtension::getGeneralOptions("general_settings");
		$metaboxTitle = __('Item Extension', 'ait-item-extension');

		if(is_array($storedInputs) && !empty($storedInputs)){
			$newMetaboxTitle = AitLangs::getCurrentLocaleText($storedInputs['section_title'], __('Item Extension', 'ait-item-extension'));
			if($newMetaboxTitle !== ""){
				$metaboxTitle = $newMetaboxTitle;
			}
		}

		add_meta_box('item-extension', $metaboxTitle, array(__CLASS__, 'renderMetabox'), 'ait-item');
	}

	public static function renderMetabox(){
		/* METABOX WRAPPER */
		echo '<div data-ait-metabox="_ait-item_item-extension">';
			echo '<div id="ait-ait-item-item-extension-_ait-item_item-extension-panel" class="ait-options-group ait-options-panel ait-ait-item-item-extension-tabs-panel">';
				echo '<div id="ait-options-basic-_ait-item_item-extension" class="ait-controls-tabs-panel ait-options-basic">';
					echo '<div class="ait-options-section">';
		/* METABOX WRAPPER */

						/* METABOX CONTENTS */
						$role = AitItemExtension::getUserPackage();	// this should be dependent on item author
						if($role != false){
							$generalOptions = AitItemExtension::getGeneralOptions($role);
							if(is_array($generalOptions) && !empty($generalOptions)){
								$post_meta = get_post_meta(get_the_ID(), '_ait-item_item-extension', true);
								foreach(AitItemExtension::getGeneralOptions($role) as $index => $options){
									// prepare name and id values
									$options['name'] = !empty($options['name']) ? $options['name'] : '_ait-item_item-extension['.$options['type'].']['.$options['uid'].']';
									$options['id'] = !empty($options['id']) ? $options['id'] : 'item-extension-option-'.$index;

									if(is_array($post_meta) && !empty($post_meta)){
										// use the stored value instead of default value from general options
										$storedValue = !empty($post_meta[ $options['type'] ][ $options['uid'] ]) ? $post_meta[ $options['type'] ][ $options['uid'] ] : $options['value'];
										$options['value']  = $storedValue;
									}
									AitItemExtension::renderSingleInput($options);
								}
							} else {
								echo "there are no extension fields defined for the current package ... <a href='".admin_url('admin.php?page=ait_item_extension_options#ait-item-extension-'.$role.'-panel')."'>Change Settings</a>";
							}
						} else {
							echo __("No package defined for current user", 'ait-item-extension');
						}
						/* METABOX CONTENTS */

		/* METABOX WRAPPER */
					echo '</div>';
				echo '</div>';
			echo '</div>';
		echo '</div>';
		/* METABOX WRAPPER */
	}

	public static function saveMetabox($post_id, $post, $update){
		if ( 'ait-item' != $post->post_type ) {
			return;
		}

		if(!empty($_POST['_ait-item_item-extension'])){
			// maybe store them with package_dependency // possible override // need to validate
			update_post_meta($post_id, '_ait-item_item-extension', $_POST['_ait-item_item-extension']);
		}
	}
	/* METABOX OPTIONS */

	/* AJAX FUNCTIONS */
	public static function ajaxSaveGeneralOptions(){
		$result = array(
			'status' => array(
				'code' => 200,
				'msg' => "OK",
			),
		);

		/* save general settings separatelly */
		$gValues = !empty($_POST['data']["general_settings"]) ? $_POST['data']["general_settings"] : array();
		update_option('ait_item_extension_general_settings_options', $gValues);
		/* save general settings separatelly */

		$counter = 0;
		foreach(AitItemExtension::getAvalaibleRoles() as $i => $themePackage){
			$values = !empty($_POST['data'][$themePackage->slug]) ? $_POST['data'][$themePackage->slug] : array();
			// generate unique id for each input to store the order .. validate this uid for further use
			foreach ($values as $id => $value) {
				if(!isset($value['uid'])){
					$values[$id]['uid'] = uniqid(mt_rand(), true);
				}
			}

			if(update_option('ait_item_extension_'.$themePackage->slug.'_options', $values)){
				$counter++;
			}
		}

		if($counter == count(AitItemExtension::getAvalaibleRoles())){
			$result = array(
				'status' => array(
					'fail' => false,
					'msg' => "OK",
				),
			);
		} else {
			$result = array(
				'status' => array(
					'fail' => true,
					'msg' => "Failed to update",
				),
			);
		}

		header('Content-Type: application/json');
		echo json_encode($result);
		exit();
	}
	/* AJAX FUNCTIONS */

	/* ADMIN FUNCTIONS */
	public static function adminMenu(){
		$hook = add_submenu_page(
			"ait-theme-options",
			__("Item Extension", "ait-item-extension"),
			__("Item Extension", "ait-item-extension"),
			apply_filters('ait-item-extension-menu-permission', 'edit_theme_options'),
			'ait_item_extension_options',
			array(__CLASS__, 'adminPage')
		);
	}

	public static function adminPage(){
		echo '<div class="wrap">';
			echo '<div id="ait-item-extension-page" class="ait-admin-page ait-options-layout">';
				echo '<div class="ait-admin-page-wrap">';
					/* Hack for WP notifications, all will be placed right after this h2 */
					echo '<h2 style="display: none;"></h2>';

					echo '<div class="ait-options-page-header">';
						echo '<h3 class="ait-options-header-title">'. __('Item Extension', 'ait-item-extension') .'</h3>';
						echo '<div class="ait-options-header-tools">';
							echo '<a class="ait-scroll-to-top"><i class="fa fa-chevron-up"></i></a>';
							echo '<div class="ait-header-save">';
								echo '<button class="ait-save-item-extension-options">';
									esc_html_e('Save Options', 'ait-admin');
								echo '</button>';

								echo '<div id="action-indicator-save" class="action-indicator action-save"></div>';
							echo '</div>';
						echo '</div>';

						echo '<div class="ait-sticky-header">';
							echo '<h4 class="ait-sticky-header-title">'. __('Item Extension', 'ait-item-extension') .'<i class="fa fa-circle"></i><span class="subtitle"></span></h4>';
						echo '</div>';
					echo '</div>';

					echo '<div class="ait-options-page">';

						echo '<div class="ait-options-page-content">';
							echo '<div class="ait-options-sidebar">';
								echo '<div class="ait-options-sidebar-content">';
									echo '<ul id="ait-item-extension-tabs" class="ait-options-tabs">';
											echo '<li id="ait-item-extension-general-settings-panel-tab"><a href="#ait-item-extension-general-settings-panel">'.__('General Settings', 'ait-item-extension').'</a></li>';
										foreach(AitItemExtension::getAvalaibleRoles() as $i => $themePackage){
											echo '<li id="ait-item-extension-'.$themePackage->slug.'-panel-tab"><a href="#ait-item-extension-'.$themePackage->slug.'-panel">'.$themePackage->name.'</a></li>';
										}
									echo '</ul>';
								echo '</div>';
							echo '</div>';

							echo '<div class="ait-options-content">';
								echo '<div class="ait-options-controls-container">';
									echo '<div id="ait-item-extension-panels" class="ait-options-controls ait-options-panels">';
										/* General Seetings */
										echo '<div id="ait-item-extension-general-settings-panel" class="ait-options-group ait-options-panel ait-backup-tabs-panel" style="display: none;">';
											echo '<div class="ait-controls-tabs-panel ait-options-basic">';
												echo '<div id="ait-options-basic-packages">';
													echo '<div class="ait-options-section">';
														$generalSettings = AitItemExtension::getGeneralSettings();
														/* section settings */
														// >> display > on-off
														$selectedOn = $generalSettings['section_display'] == "on" ? 'selected="selected"' : '';
														$selectedOff = $selectedOn == "" ? 'selected="selected"' : "";

														echo '<div class="ait-opt-container ait-opt-on-off-main" data-db-key="section_display">';
															echo '<div class="ait-opt-wrap">';

																echo '<div class="ait-opt-label">';
																	echo '<div class="ait-label-wrapper">';
																		echo '<label class="ait-label" for="test">'.__('Display section', 'ait-item-extension').'</label>';
																	echo '</div>';
																echo '</div>';


																echo '<div class="ait-opt ait-opt-on-off">';
																	echo '<div class="ait-opt-wrapper">';
																		echo '<div class="ait-opt-switch">';
																			echo '<select id="test" name="test">';
																				echo '<option value="on" '.$selectedOn.'>On</option>';
																				echo '<option value="off" '.$selectedOff.'>Off</option>';
																			echo '</select>';
																		echo '</div>';
																	echo '</div>';
																echo '</div>';


																echo '<div class="ait-opt-help">';
																	echo '<div class="ait-help">';
																		echo __('Display or Hide section on item detail', 'ait-item-extension');
																	echo '</div>';
																echo '</div>';


															echo '</div>';
														echo '</div>';

														// >> section heading
														echo '<div class="ait-opt-container ait-opt-text-main" data-db-key="section_title">';
															echo '<div class="ait-opt-wrap">';

																echo '<div class="ait-opt-label">';
																	echo '<div class="ait-label-wrapper">';
																		echo '<label class="ait-label" >' . __('Section Title', 'ait-item-extension') . '</label>';
																	echo '</div>';
																echo '</div>';

																echo '<div class="ait-opt ait-opt-text">';
																	foreach(AitLangs::getLanguagesList() as $lang){

																		if(!AitLangs::isFilteredOut($lang)){
																			echo '<div class="ait-opt-wrapper '.AitLangs::htmlClass($lang->locale).'">';
																				if(AitLangs::isEnabled()){
																					echo '<div class="flag">'.$lang->flag.'</div>';
																				}
																				$value = "";
																				if(is_array($generalSettings['section_title'])){
																					if(!empty($generalSettings['section_title'][$lang->locale])){
																						$value = $generalSettings['section_title'][$lang->locale];
																					}
																				} else {
																					$value = $generalSettings['section_title'];
																				}
																				echo '<input type="text" value="'.$value.'" data-lang="'.$lang->locale.'">';
																			echo '</div>';
																		} else {
																			$value = "";
																			if(is_array($generalSettings['section_title'])){
																				if(!empty($generalSettings['section_title'][$lang->locale])){
																					$value = $generalSettings['section_title'][$lang->locale];
																				}
																			} else {
																				$value = $generalSettings['section_title'];
																			}
																			echo '<input type="hidden" value="'.$value.'" data-lang="'.$lang->locale.'">';
																		}

																	}
																echo '</div>';

															echo '</div>';
														echo '</div>';

														// >> section description
														echo '<div class="ait-opt-container ait-opt-textarea-main" data-db-key="section_description">';
															echo '<div class="ait-opt-wrap">';

																echo '<div class="ait-opt-label">';
																	echo '<div class="ait-label-wrapper">';
																		echo '<label class="ait-label" >' . __('Section Description', 'ait-item-extension') . '</label>';
																	echo '</div>';
																echo '</div>';

																echo '<div class="ait-opt ait-opt-textarea">';
																	foreach(AitLangs::getLanguagesList() as $lang){

																		if(!AitLangs::isFilteredOut($lang)){
																			echo '<div class="ait-opt-wrapper '.AitLangs::htmlClass($lang->locale).'">';
																				if(AitLangs::isEnabled()){
																					echo '<div class="flag">'.$lang->flag.'</div>';
																				}
																				$value = "";
																				if(is_array($generalSettings['section_description'])){
																					if(!empty($generalSettings['section_description'][$lang->locale])){
																						$value = $generalSettings['section_description'][$lang->locale];
																					}
																				} else {
																					$value = $generalSettings['section_description'];
																				}
																				echo '<textarea data-lang="'.$lang->locale.'">'.$value.'</textarea>';
																			echo '</div>';
																		} else {
																			$value = "";
																			if(is_array($generalSettings['section_description'])){
																				if(!empty($generalSettings['section_description'][$lang->locale])){
																					$value = $generalSettings['section_description'][$lang->locale];
																				}
																			} else {
																				$value = $generalSettings['section_description'];
																			}
																			echo '<input type="hidden" value="'.$value.'" data-lang="'.$lang->locale.'">';
																		}

																	}
																echo '</div>';

															echo '</div>';
														echo '</div>';

														/* section settings */

													echo '</div>';
												echo '</div>';
											echo '</div>';
										echo '</div>';
										/* General Seetings */

										foreach(AitItemExtension::getAvalaibleRoles() as $i => $themePackage){
											echo '<div id="ait-item-extension-'.$themePackage->slug.'-panel" class="ait-options-group ait-options-panel ait-backup-tabs-panel" style="display: none;">';
												echo '<div class="ait-controls-tabs-panel ait-options-basic">';
													echo '<div id="ait-options-basic-packages">';
														echo '<div class="ait-options-section">';
															$storedInputs = AitItemExtension::getGeneralOptions($themePackage->slug);
															$key = $themePackage->slug;

															/* CLONABLE INPUT */
															echo '<div class="ait-opt-container ait-opt-clone-main">';
																echo '<div class="ait-opt-wrap">';

																	echo '<div class="ait-opt ait-opt-clone">';
																		echo '<div id="ait-opt-item-extension-'.$key.'" class="ait-clone-controls ui-sortable added_types_'. $key .'" data-db-key="'.$key.'" data-confirm-message="Are you sure?" data-min-forms="0" data-max-forms="0" data-allow-remove-all="true" style="display: block;">';

																			/* STORED CLONABLE INPUTS */
																			$i = 0;
																			foreach($storedInputs as $index => $input){
																				echo '<div id="ait-opt-item-extension-'.$key.'-'.$index.'_pregenerated'.$index.'" class="ait-clone-item ait-pregenerated-clone-item" idtemplate="ait-opt-item-extension-'.$key.'-'.$index.'-pregenerated">';
																					echo '<div class="form-input-handler ui-sortable-handle clone-sort">';

																						echo '<div class="form-input-title">' .AitLangs::getCurrentLocaleText($input['label']). '</div>';
																							echo '<a id="ait-opt-item-extension-'.$key.'_remove_current" href="#" class="ait-clone-remove-current" style="display: inline;">×</a>';
																						echo '</div>';

																						echo '<div class="form-input-content"'. ($i != 0 ? 'style="display: none;"' : '') .'>';
																							echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="uid" style="display: none">';
																								echo '<input type="hidden" value="'.$input['uid'].'">';
																							echo '</div>';


																							echo '<div class="ait-opt-container ait-opt-select-main" data-db-key="type">';
																								echo '<div class="ait-opt-wrap">';
																									echo '<div class="ait-opt-label">';
																										echo '<div class="ait-label-wrapper">';
																											echo '<label class="ait-label" for="ait-opt-item-extension-'.$key.'">' . __('Type', 'ait-item-extension') . '</label>';
																										echo '</div>';
																									echo '</div>';

																									echo '<div class="ait-opt ait-opt-select">';
																										echo '<div class="ait-opt-wrapper chosen-wrapper">';
																											echo '<select class="chosen" data-placeholder="'.__('Choose...', 'ait').'">';
																											foreach (AitItemExtension::getSupportedInputs() as $type => $label) {
																												$selected = $input['type'] == $type ? 'selected="selected"' : '';
																												echo '<option value="'.$type.'" '.$selected.'>'.$label.'</option>';
																											}
																										echo '</select>';
																									echo '</div>';
																								echo '</div>';
																							echo '</div>';
																						echo '</div>';

																						echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="label">';
																							echo '<div class="ait-opt-wrap">';

																								echo '<div class="ait-opt-label">';
																									echo '<div class="ait-label-wrapper">';
																										echo '<label class="ait-label" >' . __('Label', 'ait-item-extension') . '</label>';
																									echo '</div>';
																								echo '</div>';

																								echo '<div class="ait-opt ait-opt-string">';
																									foreach(AitLangs::getLanguagesList() as $lang){

																										if(!AitLangs::isFilteredOut($lang)){
																											echo '<div class="ait-opt-wrapper '.AitLangs::htmlClass($lang->locale).'">';
																												if(AitLangs::isEnabled()){
																													echo '<div class="flag">'.$lang->flag.'</div>';
																												}
																												$value = is_array($input['label']) ? $input['label'][$lang->locale] : $input['label'];
																												echo '<input type="text" value="'.$value.'" data-lang="'.$lang->locale.'">';
																											echo '</div>';
																										} else {
																											$value = is_array($input['label']) ? $input['label'][$lang->locale] : "";
																											echo '<input type="hidden" value="'.$value.'" data-lang="'.$lang->locale.'">';
																										}

																									}
																								echo '</div>';

																							echo '</div>';
																						echo '</div>';

																						echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="help">';
																							echo '<div class="ait-opt-wrap">';

																								echo '<div class="ait-opt-label">';
																									echo '<div class="ait-label-wrapper">';
																										echo '<label class="ait-label" >' . __('Help Text', 'ait-item-extension') . '</label>';
																									echo '</div>';
																								echo '</div>';

																								echo '<div class="ait-opt ait-opt-string">';
																									foreach(AitLangs::getLanguagesList() as $lang){

																										if(!AitLangs::isFilteredOut($lang)){
																											echo '<div class="ait-opt-wrapper '.AitLangs::htmlClass($lang->locale).'">';
																												if(AitLangs::isEnabled()){
																													echo '<div class="flag">'.$lang->flag.'</div>';
																												}
																												$value = is_array($input['help']) ? $input['help'][$lang->locale] : $input['help'];
																												echo '<input type="text" value="'.$value.'" data-lang="'.$lang->locale.'">';
																											echo '</div>';
																										} else {
																											$value = is_array($input['help']) ? $input['help'][$lang->locale] : "";
																											echo '<input type="hidden" value="'.$value.'" data-lang="'.$lang->locale.'">';
																										}

																									}
																								echo '</div>';

																							echo '</div>';
																						echo '</div>';

																						echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="value">';
																							echo '<div class="ait-opt-wrap">';

																								echo '<div class="ait-opt-label">';
																									echo '<div class="ait-label-wrapper">';
																										echo '<label class="ait-label" >' . __('Default Value', 'ait-item-extension') . '</label>';
																									echo '</div>';
																								echo '</div>';

																								echo '<div class="ait-opt ait-opt-string">';
																									echo '<div class="ait-opt-wrapper">';
																										echo '<input type="text" value="'.$input['value'].'">';
																									echo '</div>';
																								echo '</div>';

																							echo '</div>';
																						echo '</div>';

																						echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="min">';
																							echo '<div class="ait-opt-wrap">';

																								echo '<div class="ait-opt-label">';
																									echo '<div class="ait-label-wrapper">';
																										echo '<label class="ait-label" >' . __('Min Value', 'ait-item-extension') . '</label>';
																									echo '</div>';
																									echo '<div class="ait-opt-help">';
																										echo '<div class="ait-help">';
																											echo __('Applicable for "Number" and "Range" input types', 'ait-item-extension');
																										echo '</div>';
																									echo '</div>';
																								echo '</div>';

																								echo '<div class="ait-opt ait-opt-string">';
																									echo '<div class="ait-opt-wrapper">';
																										echo '<input type="text" value="'.$input['min'].'">';
																									echo '</div>';
																								echo '</div>';

																							echo '</div>';
																						echo '</div>';

																						echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="max">';
																							echo '<div class="ait-opt-wrap">';

																								echo '<div class="ait-opt-label">';
																									echo '<div class="ait-label-wrapper">';
																										echo '<label class="ait-label" >' . __('Max Value', 'ait-item-extension') . '</label>';
																									echo '</div>';
																									echo '<div class="ait-opt-help">';
																										echo '<div class="ait-help">';
																											echo __('Applicable for "Number" and "Range" input types', 'ait-item-extension');
																										echo '</div>';
																									echo '</div>';
																								echo '</div>';

																								echo '<div class="ait-opt ait-opt-string">';
																									echo '<div class="ait-opt-wrapper">';
																										echo '<input type="text" value="'.$input['max'].'">';
																									echo '</div>';
																								echo '</div>';

																							echo '</div>';
																						echo '</div>';

																						echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="step">';
																							echo '<div class="ait-opt-wrap">';

																								echo '<div class="ait-opt-label">';
																									echo '<div class="ait-label-wrapper">';
																										echo '<label class="ait-label" >' . __('Step', 'ait-item-extension') . '</label>';
																									echo '</div>';
																									echo '<div class="ait-opt-help">';
																										echo '<div class="ait-help">';
																											echo __('Applicable for "Range" input type', 'ait-item-extension');
																										echo '</div>';
																									echo '</div>';
																								echo '</div>';

																								echo '<div class="ait-opt ait-opt-string">';
																									echo '<div class="ait-opt-wrapper">';
																										echo '<input type="text" value="'.$input['step'].'">';
																									echo '</div>';
																								echo '</div>';

																							echo '</div>';
																						echo '</div>';

																					echo '</div>';
																				echo '</div>';
																				$i++;
																			}
																			/* STORED CLONABLE INPUTS */

																			echo '<div id="ait-opt-item-extension-'.$key.'_noforms_template" class="ait-clone-noforms" style="display: none;">';
																				echo __('No Items Defined', 'ait-item-extension');
																			echo '</div>';

																			echo '<div id="ait-opt-item-extension-'.$key.'_template" class="ait-clone-item">';
																				echo '<div class="form-input-handler ui-sortable-handle clone-sort">';

																					echo '<div class="form-input-title">' .__('Input', 'ait-item-extension'). '</div>';
																						echo '<a id="ait-opt-item-extension-'.$key.'_remove_current" href="#" class="ait-clone-remove-current" style="display: inline;">×</a>';
																					echo '</div>';

																					echo '<div class="form-input-content">';
																						echo '<div class="ait-opt-container ait-opt-select-main" data-db-key="type">';
																							echo '<div class="ait-opt-wrap">';
																								echo '<div class="ait-opt-label">';
																									echo '<div class="ait-label-wrapper">';
																										echo '<label class="ait-label" for="ait-opt-item-extension-'.$key.'">' . __('Type', 'ait-item-extension') . '</label>';
																									echo '</div>';
																								echo '</div>';

																								echo '<div class="ait-opt ait-opt-select">';
																									echo '<div class="ait-opt-wrapper chosen-wrapper">';
																										echo '<select class="chosen" data-placeholder="'.__('Choose...', 'ait').'">';
																										foreach (AitItemExtension::getSupportedInputs() as $type => $label) {
																											echo '<option value="'.$type.'">'.$label.'</option>';
																										}
																									echo '</select>';
																								echo '</div>';
																							echo '</div>';
																						echo '</div>';
																					echo '</div>';

																					echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="label">';
																						echo '<div class="ait-opt-wrap">';

																							echo '<div class="ait-opt-label">';
																								echo '<div class="ait-label-wrapper">';
																									echo '<label class="ait-label" >' . __('Label', 'ait-item-extension') . '</label>';
																								echo '</div>';
																							echo '</div>';

																							echo '<div class="ait-opt ait-opt-string">';
																								/*echo '<div class="ait-opt-wrapper">';
																									echo '<input type="text" value="">';
																								echo '</div>';*/

																								foreach(AitLangs::getLanguagesList() as $lang){

																									if(!AitLangs::isFilteredOut($lang)){
																										echo '<div class="ait-opt-wrapper '.AitLangs::htmlClass($lang->locale).'">';
																											if(AitLangs::isEnabled()){
																												echo '<div class="flag">'.$lang->flag.'</div>';
																											}
																											echo '<input type="text" value="" data-lang="'.$lang->locale.'">';
																										echo '</div>';
																									} else {
																										echo '<input type="hidden" value="" data-lang="'.$lang->locale.'">';
																									}

																								}

																							echo '</div>';

																						echo '</div>';
																					echo '</div>';

																					echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="help">';
																						echo '<div class="ait-opt-wrap">';

																							echo '<div class="ait-opt-label">';
																								echo '<div class="ait-label-wrapper">';
																									echo '<label class="ait-label" >' . __('Help Text', 'ait-item-extension') . '</label>';
																								echo '</div>';
																							echo '</div>';

																							echo '<div class="ait-opt ait-opt-string">';
																								/*echo '<div class="ait-opt-wrapper">';
																									echo '<input type="text" value="">';
																								echo '</div>';*/

																								foreach(AitLangs::getLanguagesList() as $lang){

																									if(!AitLangs::isFilteredOut($lang)){
																										echo '<div class="ait-opt-wrapper '.AitLangs::htmlClass($lang->locale).'">';
																											if(AitLangs::isEnabled()){
																												echo '<div class="flag">'.$lang->flag.'</div>';
																											}
																											echo '<input type="text" value="" data-lang="'.$lang->locale.'">';
																										echo '</div>';
																									} else {
																										echo '<input type="hidden" value="" data-lang="'.$lang->locale.'">';
																									}

																								}

																							echo '</div>';

																						echo '</div>';
																					echo '</div>';

																					echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="value">';
																						echo '<div class="ait-opt-wrap">';

																							echo '<div class="ait-opt-label">';
																								echo '<div class="ait-label-wrapper">';
																									echo '<label class="ait-label" >' . __('Default Value', 'ait-item-extension') . '</label>';
																								echo '</div>';
																							echo '</div>';

																							echo '<div class="ait-opt ait-opt-string">';
																								echo '<div class="ait-opt-wrapper">';
																									echo '<input type="text" value="">';
																								echo '</div>';
																							echo '</div>';

																						echo '</div>';
																					echo '</div>';

																					echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="min">';
																						echo '<div class="ait-opt-wrap">';

																							echo '<div class="ait-opt-label">';
																								echo '<div class="ait-label-wrapper">';
																									echo '<label class="ait-label" >' . __('Min Value', 'ait-item-extension') . '</label>';
																								echo '</div>';
																								echo '<div class="ait-opt-help">';
																									echo '<div class="ait-help">';
																										echo __('Applicable for "Number" and "Range" input types', 'ait-item-extension');
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

																					echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="max">';
																						echo '<div class="ait-opt-wrap">';

																							echo '<div class="ait-opt-label">';
																								echo '<div class="ait-label-wrapper">';
																									echo '<label class="ait-label" >' . __('Max Value', 'ait-item-extension') . '</label>';
																								echo '</div>';
																								echo '<div class="ait-opt-help">';
																									echo '<div class="ait-help">';
																										echo __('Applicable for "Number" and "Range" input types', 'ait-item-extension');
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

																					echo '<div class="ait-opt-container ait-opt-string-main" data-db-key="step">';
																						echo '<div class="ait-opt-wrap">';

																							echo '<div class="ait-opt-label">';
																								echo '<div class="ait-label-wrapper">';
																									echo '<label class="ait-label" >' . __('Step', 'ait-item-extension') . '</label>';
																								echo '</div>';
																								echo '<div class="ait-opt-help">';
																									echo '<div class="ait-help">';
																										echo __('Applicable for "Range" input type', 'ait-item-extension');
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

																				echo '</div>';
																			echo '</div>';

																		echo '</div>';
																		/* HERE THE MAIN CLONE FUNCTION */

																		echo '<div id="ait-opt-item-extension-'.$key.'_controls" class="ait-clone-tools">';
																			echo '<div id="ait-opt-item-extension-'.$key.'_add" class="ait-clone-add ait-clone-control-link">';
																				echo '<a href="#">'.__('+ Add New Item', 'ait-item-extension').'</a>';
																			echo '</div>';
																			echo '<div id="ait-opt-item-extension-'.$key.'_toggle_all" class="ait-clone-toggle-all ait-clone-control-link">';
																				echo '<a href="#">'.__('Open/Collapse All Items', 'ait-item-extension').'</a>';
																			echo '</div>';
																			echo '<div id="ait-opt-item-extension-'.$key.'_remove_last" class="ait-clone-remove-last ait-clone-control-link" style="display: none;">';
																				echo '<a href="#">'.__('Remove', 'ait-item-extension').'</a>';
																			echo '</div>';
																			echo '<div id="ait-opt-item-extension-'.$key.'_remove_all" class="ait-clone-remove-all ait-clone-control-link">';
																				echo '<a href="#">'.__('Remove All Items', 'ait-item-extension').'</a>';
																			echo '</div>';
																		echo '</div>';
																	echo '</div>';

																echo '</div>';
															echo '</div>';
															/* CLONABLE INPUT */

														echo '</div>';
													echo '</div>';
												echo '</div>';
											echo '</div>';
										}
									echo '</div>';
								echo '</div>';
							echo '</div>';
						echo '</div>';
					echo '</div>';
				echo '</div>';
			echo '</div>';
		echo '</div>';
	}

	public static function renderSingleInput($options){
		if(is_array($options) && !empty($options)){
			switch($options['type']){
				case 'text':
					// simple input
					echo '<div class="ait-opt-container ait-opt-string-main">';
						echo '<div class="ait-opt-wrap">';

							if(AitLangs::getCurrentLocaleText($options['label']) != "" || AitLangs::getCurrentLocaleText($options['help']) != ""){
							echo '<div class="ait-opt-label">';
								if(AitLangs::getCurrentLocaleText($options['label']) != ""){
								echo '<div class="ait-label-wrapper">';
									echo '<label class="ait-label" for="'.$options['id'].'">'.AitLangs::getCurrentLocaleText($options['label']).'</label>';
								echo '</div>';
								}

								if(AitLangs::getCurrentLocaleText($options['help']) != ""){
								echo '<div class="ait-opt-help">';
									echo '<div class="ait-help">';
										echo AitLangs::getCurrentLocaleText($options['help']);
									echo '</div>';
								echo '</div>';
								}
							echo '</div>';
							}

							echo '<div class="ait-opt ait-opt-string">';
								echo '<div class="ait-opt-wrapper">';
									echo '<input type="text" id="'.$options['id'].'" name="'.$options['name'].'" value="'.esc_html($options['value']).'">';
								echo '</div>';
							echo '</div>';

						echo '</div>';
					echo '</div>';
				break;
				case 'url':
					echo '<div class="ait-opt-container ait-opt-url-main">';
						echo '<div class="ait-opt-wrap">';

							if(AitLangs::getCurrentLocaleText($options['label']) != "" || AitLangs::getCurrentLocaleText($options['help']) != ""){
							echo '<div class="ait-opt-label">';
								if(AitLangs::getCurrentLocaleText($options['label']) != ""){
								echo '<div class="ait-label-wrapper">';
									echo '<label class="ait-label" for="'.$options['id'].'">'.AitLangs::getCurrentLocaleText($options['label']).'</label>';
								echo '</div>';
								}

								if(AitLangs::getCurrentLocaleText($options['help']) != ""){
								echo '<div class="ait-opt-help">';
									echo '<div class="ait-help">';
										echo AitLangs::getCurrentLocaleText($options['help']);
									echo '</div>';
								echo '</div>';
								}
							echo '</div>';
							}

							echo '<div class="ait-opt ait-opt-url">';
								echo '<div class="ait-opt-wrapper">';
									echo '<input type="url" autocomplete="off" id="'.$options['id'].'" name="'.$options['name'].'" value="'.$options['value'].'">';
								echo '</div>';
							echo '</div>';

						echo '</div>';
					echo '</div>';
				break;
				case 'tel':
					// type="tel" is supported only in Safari 8 (http://www.w3schools.com/html/tryit.asp?filename=tryhtml_input_tel)
					echo '<div class="ait-opt-container ait-opt-string-main">';
						echo '<div class="ait-opt-wrap">';

							if(AitLangs::getCurrentLocaleText($options['label']) != "" || AitLangs::getCurrentLocaleText($options['help']) != ""){
							echo '<div class="ait-opt-label">';
								if(AitLangs::getCurrentLocaleText($options['label']) != ""){
								echo '<div class="ait-label-wrapper">';
									echo '<label class="ait-label" for="'.$options['id'].'">'.AitLangs::getCurrentLocaleText($options['label']).'</label>';
								echo '</div>';
								}

								if(AitLangs::getCurrentLocaleText($options['help']) != ""){
								echo '<div class="ait-opt-help">';
									echo '<div class="ait-help">';
										echo AitLangs::getCurrentLocaleText($options['help']);
									echo '</div>';
								echo '</div>';
								}
							echo '</div>';
							}

							echo '<div class="ait-opt ait-opt-string">';
								echo '<div class="ait-opt-wrapper">';
									echo '<input type="tel" id="'.$options['id'].'" name="'.$options['name'].'" value="'.$options['value'].'">';
								echo '</div>';
							echo '</div>';

						echo '</div>';
					echo '</div>';
				break;
				case 'email':
					// type="email" is not supported in IE9 and earlier. (http://www.w3schools.com/html/tryit.asp?filename=tryhtml_input_email)
					echo '<div class="ait-opt-container ait-opt-string-main">';
						echo '<div class="ait-opt-wrap">';

							if(AitLangs::getCurrentLocaleText($options['label']) != "" || AitLangs::getCurrentLocaleText($options['help']) != ""){
							echo '<div class="ait-opt-label">';
								if(AitLangs::getCurrentLocaleText($options['label']) != ""){
								echo '<div class="ait-label-wrapper">';
									echo '<label class="ait-label" for="'.$options['id'].'">'.AitLangs::getCurrentLocaleText($options['label']).'</label>';
								echo '</div>';
								}

								if(AitLangs::getCurrentLocaleText($options['help']) != ""){
								echo '<div class="ait-opt-help">';
									echo '<div class="ait-help">';
										echo AitLangs::getCurrentLocaleText($options['help']);
									echo '</div>';
								echo '</div>';
								}
							echo '</div>';
							}

							echo '<div class="ait-opt ait-opt-string">';
								echo '<div class="ait-opt-wrapper">';
									echo '<input type="email" id="'.$options['id'].'" name="'.$options['name'].'" value="'.$options['value'].'">';
								echo '</div>';
							echo '</div>';

						echo '</div>';
					echo '</div>';
				break;
				case 'number':
					//  type="number" is not supported in IE9 and earlier (http://www.w3schools.com/html/tryit.asp?filename=tryhtml_input_number)
					$min = !empty($options['min']) ? 'min="'.$options['min'].'"' : '';
					$max = !empty($options['max']) ? 'max="'.$options['max'].'"' : '';

					echo '<div class="ait-opt-container ait-opt-number-main">';
						echo '<div class="ait-opt-wrap">';

							if(AitLangs::getCurrentLocaleText($options['label']) != "" || AitLangs::getCurrentLocaleText($options['help']) != ""){
							echo '<div class="ait-opt-label">';
								if(AitLangs::getCurrentLocaleText($options['label']) != ""){
								echo '<div class="ait-label-wrapper">';
									echo '<label class="ait-label" for="'.$options['id'].'">'.AitLangs::getCurrentLocaleText($options['label']).'</label>';
								echo '</div>';
								}

								if(AitLangs::getCurrentLocaleText($options['help']) != ""){
								echo '<div class="ait-opt-help">';
									echo '<div class="ait-help">';
										echo AitLangs::getCurrentLocaleText($options['help']);
									echo '</div>';
								echo '</div>';
								}
							echo '</div>';
							}

							echo '<div class="ait-opt ait-opt-number">';
								echo '<div class="ait-opt-wrapper">';
									echo '<input type="number" id="'.$options['id'].'" name="'.$options['name'].'" value="'.$options['value'].'" '.$min.' '.$max.'>';
								echo '</div>';
							echo '</div>';

						echo '</div>';
					echo '</div>';
				break;
				case 'range':
					// type="range" is not supported in Internet Explorer 9 and earlier versions. (http://www.w3schools.com/html/tryit.asp?filename=tryhtml_input_range)
					$min = !empty($options['min']) ? 'min="'.$options['min'].'"' : '';
					$max = !empty($options['max']) ? 'max="'.$options['max'].'"' : '';
					$step = !empty($options['step']) ? 'step="'.$options['step'].'"' : '';

					echo '<div class="ait-opt-container ait-opt-range-main">';
						echo '<div class="ait-opt-wrap">';

						if(AitLangs::getCurrentLocaleText($options['label']) != "" || AitLangs::getCurrentLocaleText($options['help']) != ""){
						echo '<div class="ait-opt-label">';
							if(AitLangs::getCurrentLocaleText($options['label']) != ""){
							echo '<div class="ait-label-wrapper">';
								echo '<label class="ait-label" for="'.$options['id'].'">'.AitLangs::getCurrentLocaleText($options['label']).'</label>';
							echo '</div>';
							}

							if(AitLangs::getCurrentLocaleText($options['help']) != ""){
							echo '<div class="ait-opt-help">';
								echo '<div class="ait-help">';
									echo AitLangs::getCurrentLocaleText($options['help']);
								echo '</div>';
							echo '</div>';
							}
						echo '</div>';
						}

						echo '<div class="ait-opt ait-opt-range">';
							echo '<div class="ait-opt-wrapper">';
								echo '<input type="range" id="'.$options['id'].'" name="'.$options['name'].'" value="'.$options['value'].'" data-initval="'.$options['value'].'" '.$min.' '.$max.' '.$step.'>';
							echo '</div>';
						echo '</div>';

						echo '</div>';
					echo '</div>';
				break;
				case 'date':
					echo '<div class="ait-opt-container ait-opt-date-main">';
						echo '<div class="ait-opt-wrap">';

							if(AitLangs::getCurrentLocaleText($options['label']) != "" || AitLangs::getCurrentLocaleText($options['help']) != ""){
							echo '<div class="ait-opt-label">';
								if(AitLangs::getCurrentLocaleText($options['label']) != ""){
								echo '<div class="ait-label-wrapper">';
									echo '<label class="ait-label" for="'.$options['id'].'">'.AitLangs::getCurrentLocaleText($options['label']).'</label>';
								echo '</div>';
								}

								if(AitLangs::getCurrentLocaleText($options['help']) != ""){
								echo '<div class="ait-opt-help">';
									echo '<div class="ait-help">';
										echo AitLangs::getCurrentLocaleText($options['help']);
									echo '</div>';
								echo '</div>';
								}
							echo '</div>';
							}

							echo '<div class="ait-opt ait-opt-date">';
								echo '<div class="ait-opt-wrapper">';
									echo '<div class="ait-datepicker">';
										echo '<input type="text" autocomplete="off" id="'.$options['id'].'" name="'.$options['name'].'" value="'.$options['value'].'" data-ait-datepicker=\''.json_encode(array('format' => 'yy-mm-dd', 'pickerType' => 'date')).'\'>';
										//echo '<input type="hidden" id="'.$options['id'].'-standard-format" name="'.$options['name'].'" value="'.$options['value'].'">';
										?>
										<a href="#" class="datepicker-reset" style="position: absolute; top: 3px; right: 43px" onclick="javascript: event.preventDefault(); jQuery(this).parent().find('input[type=text], input[type=hidden]').attr('value', ''); return false;"><i class="fa fa-times"></i></a>
										<?php
									echo '</div>';
								echo '</div>';
							echo '</div>';

						echo '</div>';
					echo '</div>';
				break;
				case 'onoff':
					$selectedOn = $options['value'] == "on" ? 'selected="selected"' : '';
					$selectedOff = $selectedOn == "" ? 'selected="selected"' : "";

					echo '<div class="ait-opt-container ait-opt-on-off-main">';
						echo '<div class="ait-opt-wrap">';

							if(AitLangs::getCurrentLocaleText($options['label']) != ""){
							echo '<div class="ait-opt-label">';
								echo '<div class="ait-label-wrapper">';
									echo '<label class="ait-label" for="'.$options['id'].'">'.AitLangs::getCurrentLocaleText($options['label']).'</label>';
								echo '</div>';
							echo '</div>';
							}

							echo '<div class="ait-opt ait-opt-on-off">';
								echo '<div class="ait-opt-wrapper">';

									echo '<div class="ait-opt-switch">';
										echo '<select id="'.$options['id'].'" name="'.$options['name'].'">';
											echo '<option value="on" '.$selectedOn.'>On</option>';
											echo '<option value="off" '.$selectedOff.'>Off</option>';
										echo '</select>';
									echo '</div>';

								echo '</div>';
							echo '</div>';

							if(AitLangs::getCurrentLocaleText($options['help']) != ""){
							echo '<div class="ait-opt-help">';
								echo '<div class="ait-help">';
									echo AitLangs::getCurrentLocaleText($options['help']);
								echo '</div>';
							echo '</div>';
							}

						echo '</div>';
					echo '</div>';
				break;
				default:
					echo 'no type specified';
				break;
			}
		} else {
			echo 'input options are empty';
		}
	}
	/* ADMIN FUNCTIONS */

	/* HELPER FUNCTIONS */
	public static function contains($haystack, $needle){
		return strpos($haystack, $needle) !== FALSE;
	}

	public static function getAvalaibleRoles(){
		$result = array();
		if(class_exists('ThemePackages')){
			$themePackages = new ThemePackages();
			//$result = $themePackages->getAvalaible();
			$avalaiblePackages = $themePackages->getAvalaible();
		}

		/* append administrator role */
		array_push($result, (object)array(
			'slug'	=> 'administrator',
			'name'	=> __('Admin','ait-item-extension'),
		));
		/* append administrator role */
		foreach ($avalaiblePackages as $index => $themePackage) {
			$package = (object)array(
				'slug'	=> $themePackage->getSlug(),
				'name'	=> $themePackage->getName().' '.__('Package', 'ait-item-extension'),
			);
			array_push($result, $package);
		}

		return $result;
	}

	public static function getGeneralOptions($packageSlug){
		// key: ait_item_extension_<packageSlug>_options
		return get_option('ait_item_extension_'.$packageSlug.'_options', array());
	}

	public static function getSupportedInputs(){
		return array(
			'text' 			=> __('Text', 'ait-item-extension'),
			'email' 		=> __('Email', 'ait-item-extension'),
			'url'			=> __('Url', 'ait-item-extension'),
			'tel'			=> __('Telephone', 'ait-item-extension'),
			'number'		=> __('Number', 'ait-item-extension'),
			'range'			=> __('Range', 'ait-item-extension'),
			'date'			=> __('Date', 'ait-item-extension'),
			'onoff'			=> __('On-Off', 'ait-item-extension'),
		);
	}

	public static function getGeneralSettings(){
		$storedSettings = AitItemExtension::getGeneralOptions("general_settings");
		$defaultSettings = array(
			'section_display'		=> "on",
			'section_title'			=> __('Item Extension', 'ait-item-extension'),
			'section_description'	=> "",
		);
		return array_merge($defaultSettings, $storedSettings);
	}

	public static function getUserPackage($user = ""){
		global $post;
		if(!empty($user)){
			if(is_numeric($user)){
				$user = get_user_by('id', $user);
			}
		} else {
			$user = get_user_by('id', $post->post_author);
		}

		$result = false;
		foreach($user->roles as $index => $role){
			if(strpos($role, 'cityguide_') !== false || $role == "administrator"){	// administrator enabled
				$result = $role;
			}
		}
		return $result;
	}

	public static function escapeHtml($string){
		return wp_kses_data($string);	// use wp built-in html escaping
	}
	/* HELPER FUNCTIONS */
}
