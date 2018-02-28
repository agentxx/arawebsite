<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

define( 'WP_ROCKET_ADVANCED_CACHE', true );
$rocket_cache_path = '/Users/elizabeth/Projects/ARA/website/wp-content/cache/wp-rocket/';
$rocket_config_path = '/Users/elizabeth/Projects/ARA/website/wp-content/wp-rocket-config/';

if ( file_exists( '/Users/elizabeth/Projects/ARA/website/wp-content/plugins/wp-rocket/inc/vendors/classes/class-rocket-mobile-detect.php' ) && ! class_exists( 'Rocket_Mobile_Detect' ) ) {
	include_once '/Users/elizabeth/Projects/ARA/website/wp-content/plugins/wp-rocket/inc/vendors/classes/class-rocket-mobile-detect.php';
}
if ( file_exists( '/Users/elizabeth/Projects/ARA/website/wp-content/plugins/wp-rocket/inc/front/process.php' ) ) {
	include '/Users/elizabeth/Projects/ARA/website/wp-content/plugins/wp-rocket/inc/front/process.php';
} else {
	define( 'WP_ROCKET_ADVANCED_CACHE_PROBLEM', true );
}