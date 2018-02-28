<?php

/*
Plugin Name: AIT Announcements Bar
Plugin URI: http://ait-themes.club
Description: Adds simple announcements bar at the top of page on the frontend. You have full control over HTML and CSS. This plugin works only on themes from AIT for now.
Version: 2.0
Author: AitThemes.Club
Author URI: http://ait-themes.club
Domain Path: /languages
License: GPLv2 or later
*/

/* trunk@r61 */


define('AIT_ANNOUNCEMENTS_BAR_ENABLED', true);
define('AIT_ANNOUNCEMENTS_BAR_PACKAGE', 'developer');


require_once dirname(__FILE__) . '/AitAnnouncementsBar.php';


AitAnnouncementsBar::run(__FILE__, dirname(__FILE__), plugins_url('', __FILE__));

