<?php

/**
 * Plugin Name: AIT Updater
 * Version: 5.0.6
 * Description: Updater for themes and plugins from AitThemes.Club
 * Plugin URI: https://www.ait-themes.club/wordpress-plugins/ait-updater/
 *
 * Author: AitThemes.Club
 * Author URI: https://ait-themes.club
 * License: GPLv2 or later
 * Network: true
 * Text Domain: ait-updater
 * Domain Path: /languages/
 */


/* stable@r409 */

define('AIT_UPDATER_VERSION', '5.0.6');


define('AIT_UPDATER_ENABLED', true);


if(is_admin()){
	spl_autoload_register(function($class){
		$file = '';

		$filename = str_replace(array('Ait\\', '\\'), array('', '/'), $class);

		if(substr($filename, 0, 7) === 'Updater'){
			$file = __DIR__ . "/plugin/{$filename}.php";
		}

		if($file and file_exists($file)){
			require_once $file;
		}
	});

	Ait\Updater::run(__FILE__);
}