<?php

/*
Plugin Name: AIT Advanced Search
Plugin URI: http://ait-themes.club
Description: Adds ability to search content around any location
Version: 1.1
Author: AitThemes.Club
Author URI: http://ait-themes.club
Text Domain: ait-advanced-search
Domain Path: /languages
License: GPLv2 or later
*/

/* trunk@r36 */

define('AIT_ADVANCED_SEARCH_ENABLED', true);
define('AIT_ADVANCED_SEARCH_VERSION', '1.1');

AitAdvancedSearch::init();

class AitAdvancedSearch {
	protected static $pluginCodename = 'ait-advanced-search';
	protected static $currentTheme;
	protected static $compatibleThemes = array( 'skeleton', 'businessfinder2', 'directory2', 'cityguide', 'eventguide', 'foodguide');
	protected static $paths;

	public static function init()
	{
		// this return parent theme on active child theme
		$theme = wp_get_theme();
		self::$currentTheme = $theme->parent() != false ? $theme->parent()->stylesheet : $theme->stylesheet;

		self::$paths = (object) array(
			'dir' => (object) array(
				'pluginfile' => __FILE__,
				'root'       => dirname( __FILE__ ),
			),
			'url' => (object) array(
				'root'     => plugins_url('', __FILE__),
			),
		);

		self::addAdminPage();

		register_activation_hook( __FILE__, array(__CLASS__, 'onActivation') );
		register_deactivation_hook(  __FILE__, array(__CLASS__, 'onDeactivation') );

		add_action( 'plugins_loaded', array(__CLASS__, 'onPluginsLoadedCallback' ));

		add_action('switch_theme', array(__CLASS__, 'onSwitchTheme'));

		// following hooks will fire AFTER compatibility validation

		add_action('ait-after-framework-load', array(__CLASS__, 'onAfterFwLoad'));

		add_action('init', array(__CLASS__, 'onInit'));

		add_action('wp_enqueue_scripts', array(__CLASS__, 'includeGoogleLibraries'), 11);
		add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueueScripts'), 11);


	}



	/************************************************/
	/****************** MAIN HOOKS ******************/
	public static function onActivation($network_wide)
	{
		self::checkPluginCompatibility(true);

		if ( $network_wide ) {
			wp_die('Advanced Search Plugin is not allowed for network activation :(');
		}

		flush_rewrite_rules();
		if(class_exists('AitCache')){
			AitCache::clean();
		}
	}



	public static function onDeactivation()
	{
		flush_rewrite_rules();
		if(class_exists('AitCache')){
			AitCache::clean();
		}
	}



	public static function onSwitchTheme()
	{
		self::checkPluginCompatibility();
	}



	public static function onPluginsLoadedCallback()
	{
		load_plugin_textdomain('ait-advanced-search', false, basename(self::$paths->dir->root) . '/languages');
		$options = get_option('ait-advanced-search-plugin');
		if (!$options) {
			// defines default values for options in case this is the first installation
			$options = array(
				'version' => '1.0',
			);
			update_option('ait-advanced-search-plugin', $options);
		}

		// plugin upgrade
		if ($options && version_compare($options['version'], AIT_ADVANCED_SEARCH_VERSION, '<')) {
			add_action('ait-theme-run', function(){
				if(class_exists('AitCache')){
					AitCache::clean();
				}
			});
			require_once dirname(__FILE__) . '/AitAdvancedSearchUpgrade.php';
			$upgrade = new AitAdvancedSearchUpgrade($options);
			if (!$upgrade->upgrade())
				return;
		}
	}



	public static function onAfterFwLoad()
	{

	}



	public static function onInit()
	{

	}


	public static function includeGoogleLibraries()
	{
		global $wp_scripts;
		$registered = $wp_scripts->registered;
		$googlemapsApi = $registered['googlemaps-api'];
		$handle = $registered['googlemaps-api']->handle;
		$url = $registered['googlemaps-api']->src;
		$parsedUrl = parse_url($url);
		$url = "";
		if(isset($parsedUrl['scheme'])) {
			$url .= $parsedUrl['scheme'] . "://";
		}
		if(isset($parsedUrl['host'])) {
			$url .= $parsedUrl['host'];
		}
		if(isset($parsedUrl['path'])) {
			$url .= $parsedUrl['path'];
		}
		// $url = "".$parsedUrl['host'].$parsedUrl['path'];
		$query = isset($parsedUrl['query']) ? $parsedUrl['query'] : false;

		// add libraries parameter to url or extend already existing libraries parameter
		// something&libraries=places
		if (isset($parsedUrl['query'])) {
			if (strpos($query, "libraries=") !== false) {
				// libraries parameter already exists and we want to modify it
				$pos = strpos($query, "libraries=") + strlen('libraries=');
				$query = substr_replace($query, 'places,', $pos, 0);
			} else {
				// libraries parameter doesn't exist yet
				$query .= '&libraries=places';
			}
		} else {
			// there are not any parameters yet in the query'
			$query = '?libraries=places';
		}

		$url .= "?".$query;

		// if (strpos($url, "http://") === false and strpos($url, "https://") === false) {
		// 	$url = "//$url";
		// }
		$googlemapsApi->src = $url;
	}


	public static function enqueueScripts()
	{
		$src = plugins_url( 'design' , __FILE__ );
		$src = "{$src}/js/search-box.js";
		$inFooter = true;
		wp_enqueue_script( 'ait-advanced-search', $src, array(), '', $inFooter );

		$src = plugins_url( 'design' , __FILE__ );
		$src = "{$src}/css/ait-advanced-search.css";
		wp_enqueue_style( 'ait-advanced-search', $src );
	}
	/****************** MAIN HOOKS - END ************/
	/************************************************/



	/************************************************/
	/****************** HELPERS *********************/
	public static function checkPluginCompatibility($die = false)
	{
		if ( !in_array(self::$currentTheme, self::$compatibleThemes) ) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php' );
			$pluginFile = self::getPaths('dir')->pluginfile;
			deactivate_plugins(plugin_basename($pluginFile));
			if($die){
				wp_die('Current theme is not compatible with Advanced Search plugin :(', '',  array('back_link'=>true));
			} else {
				add_action( 'admin_notices', array($this, 'deactivateMessage') );
			}
		}
	}


	public static function addAdminPage()
	{
		$adminPageParams = array(
			'pageSlug' 		 => 'advanced-search-options',
			'pluginCodename' => self::$pluginCodename,
			'config'         => self::$paths->dir->root . "/admin-config.php",
			'menuTitle'      => __('Advanced Search', 'ait-advanced-search'),
		);

		require_once dirname(__FILE__) . '/AitAdvancedSearchSettingsAdminPage.php';

		$adminPage = new AitAdvancedSearchSettingsAdminPage();
		$adminPage->run($adminPageParams);
	}


	public static function getPaths($type)
	{
		if ($type == 'dir') {
			return self::$paths->dir;
		} else {
			return self::$paths->url;
		}
	}
 }
