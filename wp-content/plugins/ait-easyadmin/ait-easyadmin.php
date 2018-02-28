<?php

/*
Plugin Name: AIT Easy Admin
Plugin URI: http://www.ait-themes.club
Description: Simplified Wordpress administration
Version: 2.7
Author: AitThemes.Club
Author URI: http://ait-themes.club
Text Domain: ait-easyadmin
Domain Path: /languages
License: GPLv2 or later
*/

/* trunk@r473 */

define("AIT_EASY_ADMIN_ENABLED" , true);

AitEasyAdmin::init();

class AitEasyAdmin {
	protected static $currentTheme;
	protected static $paths;

	public static function init(){
		self::$currentTheme = sanitize_key(get_stylesheet());

		self::$paths = array(
			'config' => dirname( __FILE__ ).'/config',
			'design' => dirname( __FILE__ ).'/design',
		);

		// WP Plugin functions
		register_activation_hook( __FILE__, array(__CLASS__, 'onActivation'));
		register_deactivation_hook(  __FILE__, array(__CLASS__, 'onDeactivation'));
		add_action('after_switch_theme', array(__CLASS__, 'themeSwitched'));

		add_action('plugins_loaded', array(__CLASS__, 'onLoaded'));
		add_action('init', array(__CLASS__, 'onInit'));

		add_action('ait-save-options', array(__CLASS__, 'refreshLessStylesheet'));
	}

	/* WP PLUGIN FUNCTIONS */
	public static function onActivation(){
		AitEasyAdmin::checkPluginCompatibility(true);

		AitEasyAdmin::updateThemeOptions();

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
		AitEasyAdmin::checkPluginCompatibility();
	}

	public static function checkPluginCompatibility($die = false){
		if(!defined('AIT_SKELETON_VERSION')){
			require_once(ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins(plugin_basename( __FILE__ ));
			if($die){
				wp_die('Current theme is not compatible with Easy Admin plugin :(', '',  array('back_link'=>true));
			} else {
				add_action( 'admin_notices', function(){
					echo "<div class='error'><p>" . __('Current theme is not compatible with Easy Admin plugin!', 'ait-easyadmin') . "</p></div>";
				} );
			}
		}
	}

	public static function onLoaded(){
		load_plugin_textdomain('ait-easyadmin', false,  dirname(plugin_basename(__FILE__ )) . '/languages');

		// run actions accordingly
		add_filter('ait-theme-config',array(__CLASS__, 'prepareThemeConfig'));
	}

	public static function onInit(){
		if(defined('AIT_SKELETON_VERSION')){	// compatibility check

			add_filter('admin_body_class', array(__CLASS__, 'adminBodyClass'), 10, 1);
			add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueueAdminDesign'));

			// check if in admin admin_init is too late for some filters to run
			if(!current_user_can('manage_options')){	// use role => administrator
				$settings = AitEasyAdmin::getPluginThemeOptions();
				if($settings['enable'] == true){
					// disable functionality for easy admin user
					add_filter('show_admin_bar', '__return_false');
					// screen options tab
					add_filter('screen_options_show_screen', '__return_false');
					// help tab
					add_filter('contextual_help', function(){
						$screen = get_current_screen();
						$screen->remove_help_tabs();
					}, 999, 3 );

					add_action('current_screen', function($current_screen){
						if($current_screen->id == "dashboard"){
							wp_redirect(admin_url('profile.php'));
						}
					});

					// menu => consult this
					add_action('admin_menu', function(){
						remove_menu_page('index.php');		// dashboard
						//remove_menu_page('separator1');		// separator
						//remove_menu_page('separator2');		// separetor
					});

					// custom header
					add_action('in_admin_header', function(){
						global $menu, $submenu;

						echo '<div class="easyadmin-header-container">';
							echo '<div class="content">';

								echo '<div class="easyadmin-top-container">';
									echo '<div class="content narrow">';

										echo '<div class="easyadmin-logo-container">';
											echo '<div class="content">';
												$themeOptions = AitEasyAdmin::getThemeOptions();
												$pluginOptions = AitEasyAdmin::getPluginThemeOptions();
												$imageUrl = !empty($pluginOptions['siteLogo']) ? $pluginOptions['siteLogo'] : $themeOptions->header['logo'];
												echo '<img src="'.$imageUrl.'" alt="'.get_bloginfo( 'title' ).'" />';
											echo '</div>';
										echo '</div>';

										echo '<div class="easyadmin-menu-container">';
											echo '<div class="content">';
												echo '<ul id="easyadmin-user-menu">';
													echo '<li><a href="'.get_site_url().'" class="view-site">'.__("View Site", 'ait-easyadmin').'</a></li>';
													echo '<li><a href="'.wp_logout_url(home_url()).'" class="user-logout">'. __("Log Out", 'ait-easyadmin').'</a></li>';
												echo '</ul>';
											echo '</div>';
										echo '</div>';

									echo '</div>';
								echo '</div>';

								echo '<div class="easyadmin-bottom-container">';
									echo '<div class="content narrow">';

										echo '<div class="easyadmin-menu-container">';
											echo '<div class="content">';
												echo '<ul id="easyadmin-main-menu">';
													_wp_menu_output($menu, $submenu);
													do_action( 'adminmenu' );
												echo '</ul>';
											echo '</div>';
										echo '</div>';

									echo '</div>';
								echo '</div>';

							echo '</div>';
						echo '</div>';
					},1);

					// profile
					remove_action('admin_color_scheme_picker', 'admin_color_scheme_picker');

					// generate css
					if(!file_exists(aitPaths()->dir->cache.'/ait-easyadmin-admin-colors.css')){
						AitEasyAdmin::compileLessStylesheet();
					}
				}
			}
		}
	}
	/* WP PLUGIN FUNCTIONS */

	/* CONFIGURATION FUNCTIONS */
	public static function loadThemeConfig($type = 'raw'){
		$config = include self::$paths['config'].'/theme-options.php';
		return $config[$type];
	}

	public static function prepareThemeConfig($config = array()){
		$plugin = AitEasyAdmin::loadThemeConfig();

		if(count($config) == 0){
			$theme = self::$currentTheme;
			$config = get_option("_ait_{$theme}_theme_opts", array());
			$plugin = AitEasyAdmin::loadThemeConfig('defaults');

			/* here load the colors from theme options and set them as defaults */
			$plugin['easyAdmin']['maxWidth']							= !empty($config['general']['websiteWidth']) ? $config['general']['websiteWidth'] : $plugin['easyAdmin']['maxWidth'];
			$plugin['easyAdmin']['generalTitlesColor']					= !empty($config['general']['titColor']) ? $config['general']['titColor'] : $plugin['easyAdmin']['generalTitlesColor'];
			$plugin['easyAdmin']['generalTextColor']					= !empty($config['general']['txtColor']) ? $config['general']['txtColor'] : $plugin['easyAdmin']['generalTextColor'];
			$plugin['easyAdmin']['generalLinksColor']					= !empty($config['general']['lnkColor']) ? $config['general']['lnkColor'] : $plugin['easyAdmin']['generalLinksColor'];

			if(!empty($config['general']['secdecColor'])){
				$plugin['easyAdmin']['generalButtonColor'] = $config['general']['secdecColor'];
			} else {
				if(!empty($config['general']['decColor'])){
					$plugin['easyAdmin']['generalButtonColor'] = $config['general']['decColor'];
				}
			}

			$plugin['easyAdmin']['siteLogo']							= !empty($config['header']['logo']) ? $config['header']['logo'] : $plugin['easyAdmin']['siteLogo'];
			$plugin['easyAdmin']['headerBackground']['color']			= !empty($config['header']['headbg']['color']) ? $config['header']['headbg']['color'] : $plugin['easyAdmin']['headerBackground']['color'];
			$plugin['easyAdmin']['headerTextColor']						= !empty($config['header']['menuColor']) ? $config['header']['menuColor'] : $plugin['easyAdmin']['headerTextColor'];

			$plugin['easyAdmin']['headerMenuBackground']['color']		= !empty($config['general']['decColor']) ? $config['general']['decColor'] : $plugin['easyAdmin']['headerMenuBackground']['color'];

			$plugin['easyAdmin']['contentMetaboxLabelColor']			= !empty($config['general']['titColor']) ? $config['general']['titColor'] : $plugin['easyAdmin']['contentMetaboxLabelColor'];
			$plugin['easyAdmin']['contentMetaboxTextColor']				= !empty($config['general']['txtColor']) ? $config['general']['txtColor'] : $plugin['easyAdmin']['contentMetaboxTextColor'];
			/* here load the colors from theme options and set them as defaults */
		}

		return array_merge($config, $plugin);
	}

	public static function updateThemeOptions(){
		// check if the settings already exists
		$theme = self::$currentTheme;
		$themeOptions = get_option("_ait_{$theme}_theme_opts");

		if(!isset($themeOptions['easyAdmin'])){
			// check for old settings instance
			$updatedConfig = AitEasyAdmin::prepareThemeConfig();

			// update function from old data format to new
			$oldConfig_settings = get_option('ait_easy_admin_settings', array());
			$oldConfig_appearance = get_option('ait_easy_admin_appearance', array());
			$oldConfig = array_merge($oldConfig_settings, $oldConfig_appearance);
			$updatedConfig = AitEasyAdmin::mergeOldConfiguration($oldConfig, $updatedConfig);

			update_option("_ait_{$theme}_theme_opts", $updatedConfig);
		}
	}

	public static function mergeOldConfiguration($oldConfig, $currentConfig){
		if(is_array($oldConfig) && !empty($oldConfig)){
			$currentConfig['easyAdmin']['enable']								= $oldConfig['easy_admin'];

			$currentConfig['easyAdmin']['maxWidth']								= $oldConfig['maxWidth'];
			$currentConfig['easyAdmin']['generalBackground']['color']			= $oldConfig['pageBackground'];
			$currentConfig['easyAdmin']['generalTitlesColor']					= $oldConfig['fontColor'];
			$currentConfig['easyAdmin']['generalTextColor']						= $oldConfig['mainFont'];
			$currentConfig['easyAdmin']['generalButtonColor']					= $oldConfig['buttonColor'];
			$currentConfig['easyAdmin']['generalButtonText']					= $oldConfig['buttonTextColor'];

			$currentConfig['easyAdmin']['siteLogo']								= $oldConfig['easyAdminLogo'];
			$currentConfig['easyAdmin']['headerBackground']['color']			= $oldConfig['headerColor1'];
			$currentConfig['easyAdmin']['headerTextColor']						= $oldConfig['headerh2'];

			$currentConfig['easyAdmin']['headerMenuBackground']['color']		= $oldConfig['menuBackgroundColor'];
			$currentConfig['easyAdmin']['headerMenuTextColor']					= $oldConfig['menuFontColor'];

			$currentConfig['easyAdmin']['contentMetaboxBackground']['color']	= $oldConfig['postboxBackground'];
			$currentConfig['easyAdmin']['contentMetaboxLabelColor']				= $oldConfig['postboxLabelColor'];
			$currentConfig['easyAdmin']['contentMetaboxTextColor']				= $oldConfig['postboxfontColor'];
			$currentConfig['easyAdmin']['contentMetaboxBorderColor']			= $oldConfig['postboxBorderColor'];
		}
		return $currentConfig;
	}

	public static function getThemeOptions(){
		$options = array();
		if(class_exists("AitOptions")){
			$options = (object)aitOptions()->getOptionsByType('theme');
		}
		return $options;
	}

	public static function getPluginThemeOptions(){
		$themeOptions = AitEasyAdmin::getThemeOptions();
		$defaults = AitEasyAdmin::loadThemeConfig('defaults');
		return !empty($themeOptions->easyAdmin) ? $themeOptions->easyAdmin : $defaults['easyAdmin'];
	}

	public static function getPluginUrl($path){
		$url = plugins_url( $path , __FILE__ );
		return $url;
	}
	/* CONFIGURATION FUNCTIONS */

	/* DESIGN FUNCTIONS */
	public static function adminBodyClass($classes) {
		$classes = explode(" ", $classes);

		// check current user // if not admin // admin doesnt have easy admin
		// check if easy admin is enabled
		if(!current_user_can('manage_options')){
			$settings = AitEasyAdmin::getPluginThemeOptions();
			if($settings['enable'] == true){
				array_push($classes, 'ait-easy-admin-enabled');
			}
		}

		$classes = implode(" ", $classes);
		return $classes;
	}

	public static function enqueueAdminDesign(){
		wp_enqueue_style( 'ait-easyadmin-admin-layout', plugins_url( '/design/css/admin-layout.css' , __FILE__ ), false, false, 'screen' );
		wp_enqueue_style( 'ait-easyadmin-admin-colors', aitPaths()->url->cache.'/ait-easyadmin-admin-colors.css', false, false, 'screen' );
		$fontCssFile = aitUrl('css', '/libs/font-awesome.min.css');
		if($fontCssFile){
			wp_enqueue_style('ait-font-awesome-select', $fontCssFile, array(), '4.2.0');
		}

		if(!current_user_can('manage_options')){
			wp_enqueue_script( 'ait-easyadmin-admin', plugins_url( '/design/js/admin.js' , __FILE__ ), array('jquery'), false, true );
		}
	}

	public static function compileLessStylesheet(){
		if(!class_exists('lessc')){
			require(plugin_dir_path( __FILE__ ).'libs/lessc.inc.php');
		}
		$parser = new lessc;

		/* prepare options */
		$cssOptions = array();
		$pluginOptions = AitEasyAdmin::getPluginThemeOptions();
		$ignoredOpitons = array('enable', 'siteLogo');
		foreach($pluginOptions as $slug => $value){
			if(!in_array($slug, $ignoredOpitons)){
				if(is_array($value)){
					foreach($value as $optionSlug => $optionValue){
						$cssOptionSlug = $slug.ucfirst($optionSlug);
						$cssOptions[$cssOptionSlug] = $optionSlug == "image" ? '"'.$optionValue.'"' : $optionValue;
					}
				} else {
					$cssOptions[$slug] = $slug == "maxWidth" ? $value."px" : $value;
				}
			}
		}
		/* prepare options */

		$parser->setVariables($cssOptions);
		@$parser->compileFile(self::$paths['design'].'/css/admin-colors.less', aitPaths()->dir->cache.'/ait-easyadmin-admin-colors.css');
	}

	public static function refreshLessStylesheet(){
		if(file_exists(aitPaths()->dir->cache.'/ait-easyadmin-admin-colors.css')){
			unlink(aitPaths()->dir->cache.'/ait-easyadmin-admin-colors.css');
			AitEasyAdmin::compileLessStylesheet();
		}
	}
	/* DESIGN FUNCTIONS */

	/* HELPER FUNCTIONS */

	/* HELPER FUNCTIONS */
}