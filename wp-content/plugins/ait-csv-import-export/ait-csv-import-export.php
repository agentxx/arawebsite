<?php

/*
Plugin Name: AIT CSV Import / Export
Version: 2.9
Description: You can import items from CSV or export items to CSV file in your directory-based theme

Author: AitThemes.Club
Author URI: http://ait-themes.club
Text Domain: ait-csv-import-export
Domain Path: /languages
*/

define("AIT_IMPORT_PLUGIN_ENABLED", true);
define("AIT_IMPORT_PLUGIN_URL", plugins_url() . '/ait-toolkit/cpts/');
define("AIT_IMPORTEXPORT_PLUGIN_PATH", dirname(__FILE__));

require_once AIT_IMPORTEXPORT_PLUGIN_PATH.'/admin/AitCsvImportExportAdmin.php';
require_once AIT_IMPORTEXPORT_PLUGIN_PATH.'/admin/AitCsvImportExportAdminPostTypeTable.php';
// require_once AIT_IMPORTEXPORT_PLUGIN_PATH.'/includes/AitCsvAdminPage.php';

AitCsvImportExportAdmin::run(__FILE__);

// $basedir = dirname($pluginFile);
// require_once $basedir . '/includes/AitCsvAdminPage.php';
// AitCsvAdminPage::getInstance()->run(__FILE__);
