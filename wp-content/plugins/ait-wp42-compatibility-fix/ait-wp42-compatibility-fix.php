<?php

/**
 * Plugin Name: AIT WordPress 4.2+ Compatibility Fix
 * Plugin URI: https://ait-themes.club
 * Description: Adds compatibility fix for WordPress 4.2+ (for split shared terms) to <a target="_blank" title="Click here for the list of themes based on Framework 1" href="https://www.ait-themes.club/wordpress-themes/#wpml-ready-theme">AIT themes based on legacy 'Framework 1'</a>
 * Version: 1.1
 * Author: AitThemes.Club
 * Author URI: https://ait-themes.club
 * License: GPLv2 or later
 * Text Domain: ait-wp42-compatibility-fix
 * Domain Path: /languages/
 */


require_once dirname(__FILE__) . '/AitWp42CompatibilityFix.php';


if(is_admin()){
	AitWp42CompatibilityFix::run();
}
