<?php
/*
Plugin Name: AIT SysInfo
Description: Generates useful system information report about your WordPress website for AIT team
Version: 2.0.2
Author: AitThemes.Club
Author URI: https://www.ait-themes.club/
Text Domain: ait-sysinfo
*/

/* stable@r69 */

define('AIT_SYSINFO_VERSION', '2.0.2');

require_once dirname(__FILE__) . '/AitRequirementsInfoReporter.php';
require_once dirname(__FILE__) . '/AitPhpInfoReporter.php';
require_once dirname(__FILE__) . '/AitSysInfoReporter.php';
require_once dirname(__FILE__) . '/AitSysInfo.php';


AitSysInfo::getInstance()->run();
