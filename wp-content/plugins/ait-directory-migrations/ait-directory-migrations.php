<?php
/*
Plugin Name: AIT Directory Migrations
Plugin URI: http://ait-themes.com
Description: Migrate items, categories, locations from Directory and Business Finder themes to City Guide theme
Version: 2.9
Author: AitThemes.Club
Author URI: http://ait-themes.com
Text Domain: ait-directory-migrations
Domain Path: /languages
License: GPLv2 or later
*/

/* trunk@r133 */

/* DEV NOTES */
// debug off > 14% faster migration (same file debugging on localhost)
/* DEV NOTES */

define('AIT_MIGRATION_PLUGIN', true);
if(!defined('AIT_MIGRATION_PLUGIN_DEBUG')){
	define('AIT_MIGRATION_PLUGIN_DEBUG', true);
}
if(!defined('AIT_MIGRATION_PLUGIN_DEBUG_LEVEL')){
	define('AIT_MIGRATION_PLUGIN_DEBUG_LEVEL', "all");
}

AitMigration::init();

class AitMigration {
	protected static $themeOptionsKey;

	protected static $currentTheme;
	protected static $compatibleThemes;
	protected static $paths;

	protected static $wpml;		// if there is wpml tables ... migrate posts but dont apply translation function
	protected static $poly;		// if polylang is active ... apply translation functions for the migrated posts

	protected static $filename;

	public static function init(){
		$theme = wp_get_theme();
		self::$currentTheme = $theme->parent() != false ? $theme->parent()->stylesheet : $theme->stylesheet;	// this return parent theme on active child theme
		self::$compatibleThemes = array('skeleton', 'cityguide', 'directory2', 'eventguide', 'foodguide', 'businessfinder2');

		// not used at the moment
		self::$themeOptionsKey = sanitize_key(get_stylesheet()); // because theme options are stored _ait_{$theme}_theme_opts and on child theme _ait_{$childTheme}_theme_opts

		self::$paths = array(
			'config' => dirname( __FILE__ ).'/config',
			'design' => dirname( __FILE__ ).'/design',
			'logs'	 => dirname( __FILE__ ).'/logs',
		);

		self::$wpml = AitMigration::hasWMPLTranslations();
		self::$poly = defined('AIT_LANGUAGES_ENABLED');

		self::$filename = 'ait-migration_log.log';

		if(!is_writable(self::$paths['logs'].'/'.self::$filename)){
			$upload_dir = wp_upload_dir();
			self::$paths['logs'] = $upload_dir['basedir'];
		}

		// WP Plugin functions
		register_activation_hook( __FILE__, array(__CLASS__, 'onActivation') );
		register_deactivation_hook(  __FILE__, array(__CLASS__, 'onDeactivation') );
		add_action('after_switch_theme', array(__CLASS__, 'themeSwitched'));

		add_action('plugins_loaded', array(__CLASS__, 'onLoaded'));

		add_action('init', array(__CLASS__, 'onInit'));

		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(__CLASS__, 'pluginActionLinks'));

		// Design functions
		add_filter( 'admin_body_class', array(__CLASS__, 'adminBodyClass'), 10, 1);
		add_action( 'admin_enqueue_scripts', array(__CLASS__, 'enqueueAdminDesign') );

		// Ajax functions
		add_action( 'wp_ajax_aitMigrateOperation', array(__CLASS__, 'ajaxMigrate')); 			// only in admin
		add_action( 'wp_ajax_aitMigrateOperationBulk', array(__CLASS__, 'ajaxMigrateBulk')); 			// only in admin
		add_action( 'wp_ajax_aitMigrationSaveSettings', array(__CLASS__, 'ajaxSaveSettings')); 	// only in admin
		add_action( 'wp_ajax_aitMigrationResetSettings', array(__CLASS__, 'ajaxResetSettings')); 	// only in admin
	}

	/* WP PLUGIN FUNCTIONS */
	public static function onActivation(){
		AitMigration::checkPluginCompatibility(true);

		if(class_exists('AitCache')){
			AitCache::clean();
		}

		AitMigration::updateStatus('users');
		AitMigration::updateStatus('terms');
		AitMigration::updateStatus('items');
		AitMigration::updateStatus('reviews');
		/* Special Offers Support */
		AitMigration::updateStatus('special-offers');
		/* Special Offers Support */

		update_option('_ait_directory_migration_total', count(AitMigration::prepareCommands()));
	}

	public static function onDeactivation(){
		if(class_exists('AitCache')){
			AitCache::clean();
		}
	}

	public static function themeSwitched(){
		AitMigration::checkPluginCompatibility();
	}

	public static function checkPluginCompatibility($die = false){
		if ( !in_array(self::$currentTheme, self::$compatibleThemes) ) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins(plugin_basename( __FILE__ ));
			if($die){
				wp_die('Current theme is not compatible with Directory Migrations plugin :(', '',  array('back_link'=>true));
			} else {
				add_action( 'admin_notices', function(){
					echo "<div class='error'><p>" . _x('Current theme is not compatible with Directory Migrations plugin!', 'ait-directory-migrations') . "</p></div>";
				} );
			}
		}
	}

	public static function onLoaded(){
		add_action('admin_menu', array(__CLASS__, 'adminMenu'));

		load_plugin_textdomain('ait-directory-migrations', false,  dirname(plugin_basename(__FILE__ )) . '/languages');
	}

	public static function onInit(){

	}

	public static function pluginActionLinks($links){
		$link = sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=ait_migration_options'), __('Settings', 'ait-directory-migrations'));
		array_unshift($links, $link);
		return $links;
	}
	/* WP PLUGIN FUNCTIONS */

	/* CONFIGURATION FUNCTIONS */

	/* CONFIGURATION FUNCTIONS */

	/* DESIGN FUNCTIONS */
	public static function adminBodyClass($classes) {
		if(!empty($_REQUEST['page'])){
			if($_REQUEST['page'] == 'ait_migration_options'){
				$classes = explode(" ", $classes);
				array_push($classes, 'ait_migration_options');
				$classes = implode(" ", $classes);
			}
		}
		return $classes;
	}

	public static function enqueueAdminDesign($hook){
		if(!empty($_REQUEST['page'])){
			if($_REQUEST['page'] == 'ait_migration_options'){
				wp_enqueue_script('ait-directory-migrations-admin-script', plugin_dir_url(__FILE__) .'design/js/admin.js' , array('jquery' , 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable'));
				wp_enqueue_style('ait-directory-migrations-admin-style', plugin_dir_url(__FILE__) .'design/css/admin.css' );

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
				wp_enqueue_script('ait.admin.Tabs', "{$assetsUrl}/js/ait.admin.tabs.js", array('ait.admin', 'jquery'), AIT_THEME_VERSION, TRUE);
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
				wp_enqueue_script('ait-migration-aitadmin', plugin_dir_url( __FILE__ )."design/js/ait-admin.js", array("ait.admin.Tabs"), '1.0', true );
			}
		}
	}
	/* DESIGN FUNCTIONS */

	/* AJAX FUNCTIONS */
	public static function ajaxMigrate(){
		/*
			TODO > add function result functions
		*/

		if(!empty($_REQUEST['data']['cmd'])){
			switch ($_REQUEST['data']['cmd']) {
				case 'user':
					if(!empty($_REQUEST['data']['id'])){
						AitMigration::migrateUser(intval($_REQUEST['data']['id']));
						AitMigration::updateStatus('users');
					}
					break;
				case 'term':
					if(!empty($_REQUEST['data']['id'])){
						AitMigration::migrateTerm(intval($_REQUEST['data']['id']));
						AitMigration::updateStatus('terms');
					}
					break;
				case 'item':
					if(!empty($_REQUEST['data']['id'])){
						AitMigration::migrateItem(intval($_REQUEST['data']['id']));
						AitMigration::updateStatus('items');
					}
					break;
				case 'review':
					if(defined('AIT_REVIEWS_ENABLED')){
						if(!empty($_REQUEST['data']['id'])){
							AitMigration::migrateReview(intval($_REQUEST['data']['id']));
							AitMigration::updateStatus('reviews');
						}
					}
					break;
				case 'special-offer':
					if(defined('AIT_SPECIAL_OFFERS_ENABLED')){
						if(!empty($_REQUEST['data']['id'])){
							AitMigration::migrateSpecialOffer(intval($_REQUEST['data']['id']));
							AitMigration::updateStatus('special-offers');
						}
					}
					break;
				default:
					// no cmd
					break;
			}
		}


		$result = array(
			'commands' => AitMigration::prepareCommands(),
		);



		header('Content-Type: application/json');
		echo json_encode($result);
		exit();
	}

	public static function ajaxMigrateBulk(){

		if(!empty($_REQUEST['data'])){
			if(is_array($_REQUEST['data'])){

				foreach($_REQUEST['data'] as $i => $command){
					switch ($command['cmd']) {
						case 'user':
							if(!empty($command['id'])){
								AitMigration::migrateUser(intval($command['id']));
							}
							break;
						case 'term':
							if(!empty($command['id'])){
								AitMigration::migrateTerm(intval($command['id']));
							}
							break;
						case 'item':
							if(!empty($command['id'])){
								AitMigration::migrateItem(intval($command['id']));
							}
							break;
						case 'review':
							if(defined('AIT_REVIEWS_ENABLED')){
								if(!empty($command['id'])){
									AitMigration::migrateReview(intval($command['id']));
								}
							}
							break;
						case 'special-offer':
							if(defined('AIT_SPECIAL_OFFERS_ENABLED')){
								if(!empty($command['id'])){
									AitMigration::migrateSpecialOffer(intval($command['id']));
								}
							}
							break;
						default:
							// no cmd
							break;
					}
				}

			}
		}

		AitMigration::updateStatus('users');
		AitMigration::updateStatus('terms');
		AitMigration::updateStatus('items');
		AitMigration::updateStatus('reviews');
		AitMigration::updateStatus('special-offers');

		$result = array(
			'commands' => AitMigration::prepareCommands(),
		);

		header('Content-Type: application/json');
		echo json_encode($result);
		exit();
	}

	public static function ajaxSaveSettings(){
		$result = array(
			'status' => array(
				'code' => 200,
				'msg' => "OK",
			),
		);

		if(!empty($_REQUEST['data'])){
			$settings = array(
				'migration' => array(
					'bulkMigration' => 0,
					'bulkCount' => 1,
				),
				'roles' => array(),
			);
			$settings = array_merge($settings, $_REQUEST['data']);

			if(update_option('_ait_directory_migration_settings', $settings)){
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
		}

		header('Content-Type: application/json');
		echo json_encode($result);
		exit();
	}

	public static function ajaxResetSettings(){
		$result = array(
			'status' => array(
				'fail' => true,
				'msg' => "Failed to update",
			),
		);

		if(update_option('_ait_directory_migration_settings', "")){
			$result = array(
				'status' => array(
					'fail' => false,
					'msg' => "OK",
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
			__("Migration", "ait-directory-migrations"),
			__("Migration", "ait-directory-migrations"),
			apply_filters('ait-directory-migration-menu-permission', 'edit_theme_options'),
			'ait_migration_options',
			array(__CLASS__, 'adminPage')
		);
	}

	public static function adminPage(){
		echo '<div class="wrap">';
			echo '<div id="ait-migration-page" class="ait-admin-page ait-options-layout">';
				echo '<div class="ait-admin-page-wrap">';
					/* Hack for WP notifications, all will be placed right after this h2 */
					echo '<h2 style="display: none;"></h2>';

					echo '<div class="ait-options-page-header">';
						echo '<h3 class="ait-options-header-title">'. __('Migration', 'ait-directory-migrations') .'</h3>';
						echo '<div class="ait-options-header-tools">';
							echo '<a class="ait-scroll-to-top"><i class="fa fa-chevron-up"></i></a>';
						echo '</div>';

						echo '<div class="ait-sticky-header">';
							echo '<h4 class="ait-sticky-header-title">'. __('Migration', 'ait-directory-migrations') .'<i class="fa fa-circle"></i><span class="subtitle"></span></h4>';
						echo '</div>';
					echo '</div>';

					echo '<div class="ait-options-page">';

						echo '<div class="ait-options-page-content">';
							echo '<div class="ait-options-sidebar">';
								echo '<div class="ait-options-sidebar-content">';
									echo '<ul id="ait-migration-tabs" class="ait-options-tabs">';
										echo '<li id="ait-migration-process-panel-tab" class="tab-active"><a href="#ait-migration-process-panel">'.__('Migration', 'ait-directory-migrations').'</a></li>';
									echo '</ul>';
								echo '</div>';
							echo '</div>';

							echo '<div class="ait-options-content">';
								echo '<div class="ait-options-controls-container">';
									echo '<div id="ait-migration-panels" class="ait-options-controls ait-options-panels">';

											echo '<div id="ait-migration-process-panel" class="ait-options-group ait-options-panel ait-backup-tabs-panel" style="display: block;">';
												echo '<div class="ait-controls-tabs-panel ait-options-basic">';
													$commands = AitMigration::prepareCommands();

													if(count($commands) != 0){
														$settings = get_option('_ait_directory_migration_settings','');

														if(!is_array($settings)){

															echo '<div class="ait-options-section">';
																echo '<div class="alert alert-danger">';
																	echo '<strong>'.__('!!! Important !!!', 'ait-directory-migrations').'</strong> <br><br> '.__('Backup your database before starting the migration process.','ait-directory-migrations').' '.__('Best way is to make an <strong>SQL dump</strong> of complete database.','ait-directory-migrations').' '.__('If you aren´t familiar with database backup, please contact your hosting provider.','ait-directory-migrations');
																echo '</div>';
																echo '<h2 class="ait-options-section-title">'.__('Role Settings','ait-directory-migrations').'</h2>';
																$themePackages = new ThemePackages();
																$orderedPackages = $themePackages->getOrderedPackages();
																for($i = 1; $i <= 5; $i++){
																	echo '<div class="ait-opt-container ait-opt-select-main">';
																		echo '<div class="ait-opt-wrap">';

																			echo '<div class="ait-opt-label">';
																				echo '<div class="ait-label-wrapper">';
																					echo '<span class="ait-label">directory_'.$i.' role</span>';
																				echo '</div>';
																			echo '</div>';

																			echo '<div class="ait-opt ait-opt-select">';
																				echo '<div class="ait-opt-wrapper chosen-wrapper">';
																					echo '<select data-placeholder="Choose…" class="chosen" name="roles][directory_'.$i.']" id="ait_directory_migration_settings_directory_'.$i.'">';
																						foreach($orderedPackages as $index => $slug){
																							$package = $themePackages->getPackageBySlug($slug);
																							$packageOptions = $package->getOptions();

																							echo '<option value="'.$package->getSlug().'">'.$package->getName().'</option>';
																						}
																					echo '</select>';
																				echo '</div>';
																			echo '</div>';

																		echo '</div>';
																	echo '</div>';
																}

																echo '<div class="ait-opt-container ait-backup-action">';
																	echo '<a href="#" id="saveSettings" class="ait-button positive uppercase"><span class="button-title">'.__('Save Settings','ait-directory-migrations').'</span></a>';
																echo '</div>';
														} else {
															echo '<div class="ait-options-section">';

																echo '<div class="ait-opt-container">';
																		echo '<div class="alert alert-danger">';
																			echo '<strong>'.__('!!! Important !!!', 'ait-directory-migrations').'</strong> <br><br> '.__('Backup your database before starting the migration process.','ait-directory-migrations').' '.__('Best way is to make an <strong>SQL dump</strong> of complete database.','ait-directory-migrations').' '.__('If you aren´t familiar with database backup, please contact your hosting provider.','ait-directory-migrations');
																		echo '</div>';

																		echo '<div class="alert alert-warning">';
																			$string = array();
																			$users = get_option('_ait_directory_migration_users', "");
																			if(is_array($users)){
																				if($users['count'] > 0){
																					array_push($string, '<strong>'.$users['count'].'</strong> <u>'.__('user(s)','ait-directory-migrations').'</u>');
																				}
																			}

																			$terms = get_option('_ait_directory_migration_terms', "");
																			if(is_array($terms)){
																				if($terms['count'] > 0){
																					array_push($string, '<strong>'.$terms['count'].'</strong> <u>'.__('term(s)','ait-directory-migrations').'</u>');
																				}
																			}

																			$items = get_option('_ait_directory_migration_items', "");
																			if(is_array($items)){
																				if($items['count'] > 0){
																					array_push($string, '<strong>'.$items['count'].'</strong> <u>'.__('item(s)','ait-directory-migrations').'</u>');
																				}
																			}
																			if(defined('AIT_REVIEWS_ENABLED')){
																				$reviews = get_option('_ait_directory_migration_reviews', "");
																				if(is_array($reviews)){
																					if($reviews['count'] > 0){
																						array_push($string, '<strong>'.$reviews['count'].'</strong> <u>'.__('review(s)','ait-directory-migrations').'</u>');
																					}
																				}
																			}
																			if(defined('AIT_SPECIAL_OFFERS_ENABLED')){
																				$specialOffers = get_option('_ait_directory_migration_special_offers', "");
																				if(is_array($specialOffers)){
																					if($specialOffers['count'] > 0){
																						array_push($string, '<strong>'.$specialOffers['count'].'</strong> <u>'.__('special offer(s)','ait-directory-migrations').'</u>');
																					}
																				}
																			}
																			echo implode(' | ', $string).' to migrate';

																			echo '<span class="ait-migration-timer timer-hidden"><span class="timer-value"></span><span class="timer-unit"></span> '.__('remaining','ait-directory-migrations').'</span>';
																		echo '</div>';

																		echo '<script type="text/javascript">';
																			echo 'AitMigration.commands = '.json_encode($commands).';';
																			$counts = get_option('_ait_directory_migration_total', count($commands));
																			echo 'AitMigration.loaderStep = '.(100/$counts).';';
																			echo 'AitMigration.loaderTotal = '.($counts).';';
																			// automatic get bulkCount
																			$maxExecutionTime = ini_get('max_execution_time');
																			echo 'AitMigration.maxExecutionTime = '.$maxExecutionTime.';';
																			echo 'AitMigration.maxExecutionTimeGuard = '.$maxExecutionTime.' / 6;';
																			//$bulkCount = $maxExecutionTime / 2;	// maximum time for one item to migrate is 2 seconds
																			//echo 'AitMigration.bulkCount = '.$bulkCount.';';
																		echo '</script>';

																		echo '<div class="ait-loader loader-hidden ait-opt-wrapper" data-unit="%" data-value="0"><p class="loader-status"><span class="loader-value">0</span><span class="loader-unit">%</span></p><div class="loader-bar"></div></div>';

																		echo '<div class="ait-opt-container ait-backup-action">';
																			echo '<a href="#" id="migration-start" class="ait-button positive uppercase">'.__('Migrate','ait-directory-migrations').'</a>';
																			//echo '<a href="#" id="migration-stop" class="ait-button button button-primary">Stop</a>';
																		echo '</div>';
																echo '</div>';
															echo '</div>';
														}
													} else {
														echo '<div class="ait-options-section">';
																echo '<div class="alert alert-success">';
																	/* translators: %s - link to log file */
																	$file = plugin_dir_path(__FILE__).'logs/ait-migration_log.log';
																	$src = plugin_dir_url(__FILE__).'logs/ait-migration_log.log';
																	if(!file_exists($file)){
																		$upload_dir = wp_upload_dir();
																		$src = $upload_dir['baseurl'].'/ait-migration_log.log';
																	}
																	$text = sprintf(__('Everything was migrated. For more information, check the %s','ait-directory-migrations'), ' <a href="'.$src.'" target="_blank">.log</a>');
																	echo '<strong>'.__('Congratulations!','ait-directory-migrations').'</strong> ';
																	echo "<p>{$text}</p>";
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
			echo '</div>';
		echo '</div>';

	}
	/* ADMIN FUNCTIONS */

	/* USERS FUNCTIONS */
	public static function getUsers($type = 'all', $args = array(), $filter = "all"){
		// user types => all / migrated / notmigratedposts
		// $meta = array( 'status' => 'updated', 'date' => time() );

		$result = array();
		$users = !empty($args) ? get_users($args) : get_users();
		switch($type){
			case 'migrated':
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'getting migrated users');
				/* MIGRATION LOGGER */

				foreach ($users as $index => $user) {
					$meta = get_user_meta($user->ID, 'ait_migrated', true);
					if(is_array($meta)){
						// we have meta -> this user is migrated
						array_push($result, $user);
					}
				}
			break;
			case 'notmigrated':
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'getting not migrated users');
				/* MIGRATION LOGGER */

				foreach ($users as $index => $user) {
					$meta = get_user_meta($user->ID, 'ait_migrated', true);
					if(!is_array($meta)){
						// we dont have meta -> this user is not migrated
						array_push($result, $user);
					}
				}
			break;
			default:
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'getting all users');
				/* MIGRATION LOGGER */

				$result = $users;
			break;
		}
		/* MIGRATION LOGGER */
		AitMigration::log('info', 'got '.count($result).' users');
		/* MIGRATION LOGGER */

		$result = AitMigration::filterResultsData($result, $filter);
		return $result;
	}

	public static function migrateUser($id, $args = array()){
		$user = new WP_User(intval($id));
		/* MIGRATION LOGGER */
		AitMigration::log('info', 'trying to migrate user <'.$id.'>');
		/* MIGRATION LOGGER */

		$migration_roles = array('directory_1', 'directory_2', 'directory_3', 'directory_4', 'directory_5');
		if($user->exists()){
			$migrateOperation = false;
			$migrateRole = null;
			foreach($user->roles as $role){
				if(in_array($role, $migration_roles)){
					$migrateOperation = true;
					$migrateRole = $role;
					break;
				}
			}
			if($migrateOperation){
				$options = get_option('_ait_directory_migration_settings','');
				if(is_array($options)){
					$newRole = isset($options['roles'][$migrateRole]) ? $options['roles'][$migrateRole] : false;
					if($newRole != false){
						// we can set the new role now
						$user->remove_role($migrateRole);
						$user->add_role($newRole);
						/* MIGRATION LOGGER */
						AitMigration::log('info', 'user <'.$id.'> migrated successfully');
						/* MIGRATION LOGGER */
						update_user_meta($id, 'ait_migrated', array( 'status' => 'updated', 'date' => time() ));
					}
				} else {
					/* MIGRATION LOGGER */
					AitMigration::log('error', 'no migration settings, cannot migrate user');
					/* MIGRATION LOGGER */
				}
			} else {
				/* MIGRATION LOGGER */
				AitMigration::log('notice', 'user <'.$id.'> wont migrate, isnt a directory_x role');
				/* MIGRATION LOGGER */
				update_user_meta($id, 'ait_migrated', array( 'status' => 'updated', 'date' => time() ));
			}
		} else {
			/* MIGRATION LOGGER */
			AitMigration::log('error', 'user <'.$id.'> doesnt exist');
			/* MIGRATION LOGGER */
		}

		//AitMigration::updateStatus('users');
	}
	/* USERS FUNCTIONS */

	/* TERM FUNCTIONS */
	public static function getTerm($id){
		global $wpdb;
		$result = null;
		$dbdata = $wpdb->get_results(
			'SELECT * FROM ' . $wpdb->prefix . 'terms as term JOIN ' . $wpdb->prefix . 'term_taxonomy as tax WHERE term.term_id = tax.term_id AND term.term_id = '.$id
		);

		if(is_array($dbdata) && !empty($dbdata)){
			$result = reset($dbdata);
		}

		return $result;
	}

	public static function getTermParent($term){
		// return root 0 if not found or error
		// term => id / object
		$result = 0;
		// if it is numeric, its the term id, get the term from db
		if(is_numeric($term)){
			$term = AitMigration::getTerm($term);
		}

		if($term != null){
			if($term->parent != 0){
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'trying to find new term parent');
				/* MIGRATION LOGGER */

				$taxonomy = str_replace("-", "_", $term->taxonomy);
				$meta = AitMigration::getTermMeta($term->parent, $taxonomy, 'migrated');
				if(isset($meta['newid'])){
					/* MIGRATION LOGGER */
					AitMigration::log('notice', 'parent <'.$term->parent.'> for <'.$term->term_id.'> found, new parent is <'.$meta['newid'].'>');
					/* MIGRATION LOGGER */

					$result = $meta['newid'];
				} else {
					/* MIGRATION LOGGER */
					AitMigration::log('notice', 'parent for <'.$term->parent.'> not found, fallback parent is 0');
					/* MIGRATION LOGGER */
				}
			} else {
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'term <'.$term->term_id.'> parent is 0, its a root term');
				/* MIGRATION LOGGER */
			}
		} else {
			/* MIGRATION LOGGER */
			AitMigration::log('warning', 'term doesn`t exist or there is an error');
			/* MIGRATION LOGGER */
		}

		return $result;
	}

	public static function getTerms($type = 'all', $taxonomies = array('ait-dir-item-category', 'ait-dir-item-location'), $filter = "all"){
		// term types => all / migrated / notmigratedposts
		// $meta = array( 'status' => 'updated', 'date' => time() );
		global $wpdb;
		$result = array();
		$terms = array();
		if(!empty($taxonomies)){
			foreach($taxonomies as $index => $taxonomy){
				$terms = array_merge($terms, AitMigration::getTermsRecursivelly($taxonomy));
			}
		}

		switch($type){
			case 'migrated':
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'getting migrated terms');
				/* MIGRATION LOGGER */

				foreach ($terms as $index => $term) {
					$taxonomy = str_replace("-", "_", $term->taxonomy);
					$meta = AitMigration::getTermMeta($term->term_id, $taxonomy, 'migrated');
					if(is_array($meta)){
						// we have meta -> this user is migrated
						array_push($result, $term);
					}
				}
			break;
			case 'notmigrated':
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'getting non migrated terms');
				/* MIGRATION LOGGER */

				foreach ($terms as $index => $term) {
					$taxonomy = str_replace("-", "_", $term->taxonomy);
					$meta = AitMigration::getTermMeta($term->term_id, $taxonomy, 'migrated');
					if(!is_array($meta)){
						// we dont have meta -> this user is not migrated
						array_push($result, $term);
					}
				}
			break;
			default:
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'getting all terms');
				/* MIGRATION LOGGER */

				$result = $terms;
			break;
		}
		/* MIGRATION LOGGER */
		AitMigration::log('info', 'got '.count($result).' terms');
		/* MIGRATION LOGGER */

		$result = AitMigration::filterResultsData($result, $filter);
		return $result;
	}

	public static function getTermsRecursivelly($taxonomy, $parent = 0){
		global $wpdb;
		$result = array();
		$dbdata = $wpdb->get_results(
			'SELECT * FROM ' . $wpdb->prefix . 'terms as term JOIN ' . $wpdb->prefix . 'term_taxonomy as tax WHERE term.term_id = tax.term_id AND tax.taxonomy="' . $taxonomy . '" AND tax.parent=' . $parent . ' ORDER BY term.term_id;'
		);

		if(is_array($dbdata) && !empty($dbdata)){
			foreach($dbdata as $index => $value){
				array_push($result, $value);
				$result = array_merge($result, AitMigration::getTermsRecursivelly($taxonomy, $value->term_id));
			}
		}

		return $result;
	}

	public static function migrateTerm($term, $args = array()){
		// if it is numeric, its the term id, get the term from db
		if(is_numeric($term)){
			$term = AitMigration::getTerm($term);
		}

		if($term != null){
			/* MIGRATION LOGGER */
			AitMigration::log('info', 'trying to migrate term <'.$term->term_id.'>: '.$term->name);
			/* MIGRATION LOGGER */

			// check if the term isnt already migrated
			$taxonomy = str_replace("-", "_", $term->taxonomy);
			$meta = AitMigration::getTermMeta($term->term_id, $taxonomy, 'migrated');
			if(!is_array($meta)){
				$map = array(
					'ait-dir-item-category' => 'ait-items',
					'ait-dir-item-location' => 'ait-locations'
				);

				$parent = AitMigration::getTermParent($term); //look for his parent

				$result = wp_insert_term( $term->name, $map[$term->taxonomy], array(
					'slug' => $term->slug,
					'description' => $term->description,
					'parent' => $parent,
				) );

				if(is_wp_error($result)){
					/* MIGRATION LOGGER */
					AitMigration::log('error', 'term <'.$term->term_id.'> failed to migrate: '.$result->get_error_message());
					/* MIGRATION LOGGER */

					// set the already existing term
					$meta = array( 'status' => 'updated', 'date' => time(), 'newid' => $result->error_data['term_exists'] );
				} else {
					/* MIGRATION LOGGER */
					AitMigration::log('notice', 'term <'.$term->term_id.'> migrated successfully, new term_id: <'.$result['term_id'].'> ');
					/* MIGRATION LOGGER */

					// update all existing term meta to new format
					AitMigration::migrateTermMeta($term, $result['term_id']);

					// setup language
					if(AitMigration::willTranslate()){
						/* MIGRATION LOGGER */
						AitMigration::log('info', 'term will translate');
						/* MIGRATION LOGGER */

						// get items old wpml data
						$wpmlData = AitMigration::getTermWPMLData($term->term_id);
						// set the lang for the new item
						// $result contains new id
						if(!empty($wpmlData) && is_object($wpmlData)){
							/* MIGRATION LOGGER */
							AitMigration::log('info', 'got wpml data, current language <'.$wpmlData->language_code.'>');
							/* MIGRATION LOGGER */

							pll_set_term_language($result['term_id'], $wpmlData->language_code);
						} else {
							/* MIGRATION LOGGER */
							AitMigration::log('info', 'wpml data missing, current language <'.AitMigration::getDefaultLang().'>');
							/* MIGRATION LOGGER */

							pll_set_term_language($result['term_id'], AitMigration::getDefaultLang());
						}
					} else {
						/* MIGRATION LOGGER */
						AitMigration::log('info', 'term wont translate');
						/* MIGRATION LOGGER */
					}

					$meta = array( 'status' => 'updated', 'date' => time(), 'newid' => $result['term_id'] );
				}

				AitMigration::setTermMeta($term->term_id, $taxonomy, 'migrated', $meta);
				//AitMigration::updateStatus('terms');
			} else {
				/* MIGRATION LOGGER */
				AitMigration::log('warning', 'term <'.$term->term_id.'> already migrated');
				/* MIGRATION LOGGER */
			}
		} else {
			/* MIGRATION LOGGER */
			AitMigration::log('error', 'term doesn`t exist or there is an error');
			/* MIGRATION LOGGER */
		}
	}

	public static function getTermMeta($id, $taxonomy, $key){
		// term meta stored in wp_options table
		// e.g. ait_dir_item_category_288_excerpt => $taxonomy = ait_dir_item_category / $id = 288 / $key = excerpt
		$option = array();
		array_push($option, $taxonomy);
		array_push($option, $id);
		if(!empty($key)){
			array_push($option, $key);
		}
		$option_string = implode("_", $option);

		return get_option($option_string, "");
	}

	public static function setTermMeta($id, $taxonomy, $key, $value){
		// term meta stored in wp_options table
		// e.g. ait_dir_item_category_288_excerpt => $taxonomy = ait_dir_item_category / $id = 288 / $key = excerpt
		$option = array();
		array_push($option, $taxonomy);
		array_push($option, $id);
		if(!empty($key)){
			array_push($option, $key);
		}
		$option_string = implode("_", $option);
		/* MIGRATION LOGGER */
		AitMigration::log('notice', 'updating term <'.$id.'> options');
		/* MIGRATION LOGGER */

		$result = update_option($option_string, $value);
		if($result){
			/* MIGRATION LOGGER */
			AitMigration::log('info', 'option <'.$option_string.'> updated sucessfully');
			/* MIGRATION LOGGER */
		} else {
			/* MIGRATION LOGGER */
			AitMigration::log('error', 'option <'.$option_string.'> update failed');
			/* MIGRATION LOGGER */
		}
	}

	public static function migrateTermMeta($oldTerm, $newTerm){
		// load term meta map
		if(is_numeric($oldTerm)){
			$oldTerm = AitMigration::getTerm($oldTerm);
		}

		if(is_numeric($newTerm)){
			$newTerm = AitMigration::getTerm($newTerm);
		}

		$theme = self::$currentTheme;
		$taxonomy = str_replace("ait-", "", $newTerm->taxonomy);
		$path = dirname(__FILE__).'/config/'.$theme.'.taxonomy.'.$taxonomy.'.map.php';
		// check if file exists
		$meta = file_exists($path) ? include $path : "";

		AitMigration::setTermMeta($newTerm->term_id, $newTerm->taxonomy.'_category', '', $meta);
	}

	public static function getMigratedTerm($term, $output = "OBJECT"){
		$result = false;
		/* MIGRATION LOGGER */
		AitMigration::log('info', 'getting migrated term');
		/* MIGRATION LOGGER */

		if(is_numeric($term)){
			$term = AitMigration::getTerm($term);
		}

		$taxonomy = str_replace('-', '_', $term->taxonomy);
		$meta = AitMigration::getTermMeta($term->term_id, $taxonomy, 'migrated');

		if(is_array($meta)){
			/* MIGRATION LOGGER */
			AitMigration::log('info', 'found migrated term <'.$meta['newid'].'> for term <'.$term->term_id.'>');
			/* MIGRATION LOGGER */

			if($output == "ID"){
				$result = $meta['newid'];
			} else {
				$result = AitMigration::getTerm($meta['newid']);
			}
		} else {
			/* MIGRATION LOGGER */
			AitMigration::log('info', 'migrated term for <'.$term->term_id.'> wasnt found');
			/* MIGRATION LOGGER */
		}

		return $result;
	}

	public static function getObjectTerms($postID, $term){
        global $wpdb;
		$result = array();

		$dbdata = $wpdb->get_results(
			'SELECT * FROM ' . $wpdb->terms . ' as term INNER JOIN ' . $wpdb->term_taxonomy . ' as tax ON term.term_id=tax.term_id JOIN ' . $wpdb->term_relationships . ' as rel ON rel.term_taxonomy_id=tax.term_taxonomy_id WHERE rel.object_id=' . $postID . ' AND tax.taxonomy="'.$term.'";'
		);

		if(is_array($dbdata) && !empty($dbdata)){
			$result = $dbdata;
		}

		return $result;

	}
	/* TERM FUNCTIONS */

	/* ITEM FUNCTIONS */
	public static function getPosts($type = 'all', $args = array('post_type' => 'ait-dir-item', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC'), $filter = "all"){
		// post types => all / migrated / notmigratedposts
		// $meta = array( 'status' => 'updated', 'date' => time() );

		$result = array();
		//$posts = get_posts($args);

		switch($type){
			case 'migrated':
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'getting migrated posts');
				/* MIGRATION LOGGER */

				/*foreach ($posts as $index => $post) {
					$meta = get_post_meta($post->ID, 'ait_migrated', true);
					if(is_array($meta)){
						// we have meta -> this user is migrated
						array_push($result, $post);
					}
				}*/
				$args['meta_key'] = 'ait_migrated';
				$args['meta_compare'] = 'EXISTS';
			break;
			case 'notmigrated':
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'getting non migrated posts');
				/* MIGRATION LOGGER */

				/*foreach ($posts as $index => $post) {
					$meta = get_post_meta($post->ID, 'ait_migrated', true);
					if(!is_array($meta)){
						// we dont have meta -> this user is not migrated
						array_push($result, $post);
					}
				}*/
				$args['meta_key'] = 'ait_migrated';
				$args['meta_compare'] = 'NOT EXISTS';
			break;
			default:
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'getting all posts');
				/* MIGRATION LOGGER */

				//$result = $posts;
			break;
		}

		/* MIGRATION LOGGER */
		AitMigration::log('info', 'before wordpress query');
		/* MIGRATION LOGGER */

		$query = new WP_Query($args);

		/* MIGRATION LOGGER */
		AitMigration::log('info', 'got '.count($query->posts).' posts');
		/* MIGRATION LOGGER */

		$result = AitMigration::filterResultsData($query->posts, $filter);
		return $result;
	}

	public static function migrateItem($id){
		// migrating
		$meta = get_post_meta($id, 'ait_migrated', true);
		if(is_array($meta)){
			// we have meta -> this post is migrated
			AitMigration::log('warning', 'post <' . $id . '> already migrated');
		} else {
			$post = get_post($id, 'ARRAY_A');

			unset($post['ID']); // we are creating new post exactly the same as old one, not updating existing one
			$post['post_type'] = 'ait-item';

			$result = wp_insert_post($post, true);
			if(is_wp_error( $result )){
				/* MIGRATION LOGGER */
				AitMigration::log('error', 'post failed to migrate: '.$result->get_error_message());
				/* MIGRATION LOGGER */
			} else {
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'post <'.$id.'> successfully migrated, new post id <' . $result . '>');
				/* MIGRATION LOGGER */


				// attach categories
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'getting <ait-dir-item-category> object terms');
				/* MIGRATION LOGGER */

				$oldTerms = AitMigration::getObjectTerms($id, 'ait-dir-item-category');

				/* MIGRATION LOGGER */
				AitMigration::log('info', 'found ('.count($oldTerms).') <ait-dir-item-category> object terms');
				/* MIGRATION LOGGER */

				if(is_array($oldTerms) && !empty($oldTerms)){
					/* MIGRATION LOGGER */
					AitMigration::log('info', 'searching new object terms');
					/* MIGRATION LOGGER */

					$newTerms = array();
					foreach($oldTerms as $index => $term){
						$newTerm = AitMigration::getMigratedTerm($term, 'ID');
						if(is_numeric($newTerm)){
							array_push($newTerms, intval($newTerm));
						}
					}
					/* MIGRATION LOGGER */
					AitMigration::log('info', 'found ('.count($newTerms).') <ait-items> object terms');
					/* MIGRATION LOGGER */

					$tresult = wp_set_object_terms($result, $newTerms, 'ait-items');
					if(is_wp_error($tresult)){
						/* MIGRATION LOGGER */
						AitMigration::log('error', 'failed to set terms: '.$tresult->get_error_message());
						/* MIGRATION LOGGER */
					} else {
						/* MIGRATION LOGGER */
						AitMigration::log('info', 'terms successfully set');
						/* MIGRATION LOGGER */
					}
				}


				// attach locations
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'getting <ait-dir-item-location> object terms');
				/* MIGRATION LOGGER */

				$oldTerms = AitMigration::getObjectTerms($id, 'ait-dir-item-location');

				/* MIGRATION LOGGER */
				AitMigration::log('info', 'found ('.count($oldTerms).') <ait-dir-item-location> object terms');
				/* MIGRATION LOGGER */

				if(is_array($oldTerms) && !empty($oldTerms)){
					/* MIGRATION LOGGER */
					AitMigration::log('info', 'searching new object terms');
					/* MIGRATION LOGGER */

					$newTerms = array();
					foreach($oldTerms as $index => $term){
						$newTerm = AitMigration::getMigratedTerm($term, 'ID');
						if(is_numeric($newTerm)){
							array_push($newTerms, intval($newTerm));
						}
					}
					/* MIGRATION LOGGER */
					AitMigration::log('info', 'found ('.count($newTerms).') <ait-locations> object terms');
					/* MIGRATION LOGGER */

					$tresult = wp_set_object_terms($result, $newTerms, 'ait-locations');
					if(is_wp_error($tresult)){
						/* MIGRATION LOGGER */
						AitMigration::log('error', 'failed to set terms: '.$tresult->get_error_message());
						/* MIGRATION LOGGER */
					} else {
						/* MIGRATION LOGGER */
						AitMigration::log('info', 'terms successfully set');
						/* MIGRATION LOGGER */
					}
				}

				AitMigration::migrateItemMeta($id, $result);

				// setup language
				if(AitMigration::willTranslate()){
					/* MIGRATION LOGGER */
					AitMigration::log('info', 'item will translate');
					/* MIGRATION LOGGER */

					// get items old wpml data
					$wpmlData = AitMigration::getItemWPMLData($id);
					// set the lang for the new item
					// $result contains new id
					if(!empty($wpmlData) && is_object($wpmlData)){
						/* MIGRATION LOGGER */
						AitMigration::log('info', 'got wpml data, current lang <'.$wpmlData->language_code.'>');
						/* MIGRATION LOGGER */

						pll_set_post_language($result, $wpmlData->language_code);
					} else {
						/* MIGRATION LOGGER */
						AitMigration::log('warning', 'wpml data missing, current lang <'.AitMigration::getDefaultLang().'>');
						/* MIGRATION LOGGER */

						pll_set_post_language($result, AitMigration::getDefaultLang());
					}
				} else {
					/* MIGRATION LOGGER */
					AitMigration::log('info', 'item wont translate');
					/* MIGRATION LOGGER */
				}

				update_post_meta($id, 'ait_migrated', array( 'status' => 'updated', 'date' => time(), 'newid' => $result ));

			}

		}
		//AitMigration::updateStatus('items');

	}

	public static function migrateItemMeta($oldPost, $newPost){
		if(is_numeric($oldPost)){
			$oldPost = get_post($oldPost);
		}

		if(is_numeric($newPost)){
			$newPost = get_post($newPost);
		}

		/* map featured items */
		$featured = get_post_meta( $oldPost->ID, 'dir_featured', true );
		$featured = !empty($featured) ? 1 : 0;
		update_post_meta($newPost->ID, '_ait-item_item-featured', $featured);
		/* map featured items */

		/* mapping to new options */
		$oldItemMeta = get_post_meta( $oldPost->ID, '_ait-dir-item', true );

		$theme = self::$currentTheme;
		$type = str_replace("ait-", "", $newPost->post_type);
		$path = dirname(__FILE__).'/config/'.$theme.'.posttype.'.$type.'.map.php';
		// check if file exists
		$newItemMeta = file_exists($path) ? include $path : "";

		update_post_meta($newPost->ID, '_ait-item_item-data', $newItemMeta);
		/* mapping to new options */

		// these can be just cloned to new post
		$cloneMetaKeys = array(
			array('key' => '_thumbnail_id', 'default' => ''),

			array('key' => 'rating_count', 'default' => 0),
			array('key' => 'rating_max', 'default' => 0),
			// translate to new format
			array('key' => 'rating_full', 'default' => 0),
			array('key' => 'rating_rounded', 'default' => 0),

			array('key' => 'rating', 'default' => 0),

			array('key' => 'rating_mean', 'default' => 0),
			array('key' => 'rating_mean_rounded', 'default' => 0)
		);
		foreach($cloneMetaKeys as $index => $metaKey){
			$oldMeta = get_post_meta( $oldPost->ID, $metaKey['key'], true );
			// add new meta if the old one does not exists
			$newMeta = !empty($oldMeta) ? $oldMeta : $metaKey['default'];	// defaults to zero values
			$result = update_post_meta($newPost->ID, $metaKey['key'], $newMeta);
			if($result){
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'postmeta <' . $metaKey['key'] . '> successfully added to post <'.$newPost->ID.'>');
				/* MIGRATION LOGGER */
			} else {
				/* MIGRATION LOGGER */
				AitMigration::log('error', 'postmeta <' . $metaKey['key'] . '> failed to add to post <'.$newPost->ID.'>');
				/* MIGRATION LOGGER */
			}
		}
	}
	/* ITEM FUNCTIONS */

	/* REVIEW FUNCTIONS */
	public static function migrateReview($id){
		// get questions defined in the plugin
		// ait-rating => ait-review
		// migrating
		$meta = get_post_meta($id, 'ait_migrated', true);
		if(is_array($meta)){
			// we have meta -> this post is migrated
			AitMigration::log('warning', 'post <' . $id . '> already migrated');
		} else {
			$post = get_post($id, 'ARRAY_A');

			unset($post['ID']); // we are creating new post exactly the same as old one, not updating existing one
			$post['post_type'] = 'ait-review';

			$result = wp_insert_post($post, true);
			if(is_wp_error( $result )){
				/* MIGRATION LOGGER */
				AitMigration::log('error', 'post failed to migrate: '.$result->get_error_message());
				/* MIGRATION LOGGER */
			} else {
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'post <'.$id.'> successfully migrated, new post id <' . $result . '>');
				/* MIGRATION LOGGER */

				update_post_meta($id, 'ait_migrated', array( 'status' => 'updated', 'date' => time(), 'newid' => $result ));

				AitMigration::migrateReviewMeta($id, $result);

				//AitMigration::updateStatus('reviews');
			}

		}

	}

	public static function migrateReviewMeta($oldReview, $newReview){
		// ait-rating keys
		// post_id
		// rating_1 / rating_2 / rating_3 / rating_4 / rating_5
		// rating_mean
		// rating_mean_rounded

		// ait-review keys
		// post_id
		// ratings => [{"question":"Price","value":"4"},{"question":"Location","value":"3"},{"question":"Staff","value":"5"},{"question":"Services","value":"4"},{"question":"Food","value":"3"}]
		// rating_mean
		// rating_mean_rounded
		if(is_numeric($oldReview)){
			$oldReview = get_post($oldReview);
		}

		if(is_numeric($newReview)){
			$newReview = get_post($newReview);
		}

		// map old post_id to new migrated post_id
		/* map post_id */
		$oldPostId = get_post_meta( $oldReview->ID, 'post_id', true );
		$migrated = get_post_meta( $oldPostId, 'ait_migrated', true );
		$result = update_post_meta($newReview->ID, 'post_id', !empty($migrated['newid']) ? $migrated['newid'] : '' );
		if($result){
			/* MIGRATION LOGGER */
			AitMigration::log('info', 'postmeta <post_id> successfully added to post <'.$newReview->ID.'>');
			/* MIGRATION LOGGER */
		} else {
			/* MIGRATION LOGGER */
			AitMigration::log('error', 'postmeta <post_id> failed to add to post <'.$newReview->ID.'>');
			/* MIGRATION LOGGER */
		}
		/* map post_id */

		/* mapping to new options */
		// get the questions defined in the admin
		$themeOptions = (object)aitOptions()->getOptionsByType('theme');

		$ratings = array();
		for($i = 1; $i <= 5; $i++){
			$question = AitLangs::getCurrentLocaleText($themeOptions->itemReviews['question'.$i]);
			$value = get_post_meta( $oldReview->ID, 'rating_'.$i, true );

			array_push($ratings, array(
				'question' => $question,
				'value' => $value
			));
		}
		//$result = update_post_meta($newReview->ID, 'ratings', json_encode($ratings));
		if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
			$result = update_post_meta($newReview->ID, 'ratings' , json_encode($ratings, JSON_UNESCAPED_UNICODE));
		} else {
			$result = update_post_meta($newReview->ID, 'ratings' , AitItemReviews::raw_json_encode($ratings));
		}

		if($result){
			/* MIGRATION LOGGER */
			AitMigration::log('info', 'postmeta <ratings> successfully added to post <'.$newReview->ID.'>');
			/* MIGRATION LOGGER */
		} else {
			/* MIGRATION LOGGER */
			AitMigration::log('error', 'postmeta <ratings> failed to add to post <'.$newReview->ID.'>');
			/* MIGRATION LOGGER */
		}
		/* mapping to new options */

		// these can be just cloned to new post
		$cloneMetaKeys = array(
			array('key' => 'rating_mean', 'default' => 0),
			array('key' => 'rating_mean_rounded', 'default' => 0)
		);
		foreach($cloneMetaKeys as $index => $metaKey){
			$oldMeta = get_post_meta( $oldReview->ID, $metaKey['key'], true );
			// add new meta if the old one does not exists
			$newMeta = !empty($oldMeta) ? $oldMeta : $metaKey['default'];	// defaults to zero values
			$result = update_post_meta($newReview->ID, $metaKey['key'], $newMeta);
			if($result){
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'postmeta <' . $metaKey['key'] . '> successfully added to post <'.$newReview->ID.'>');
				/* MIGRATION LOGGER */
			} else {
				/* MIGRATION LOGGER */
				AitMigration::log('error', 'postmeta <' . $metaKey['key'] . '> failed to add to post <'.$newReview->ID.'>');
				/* MIGRATION LOGGER */
			}

			// update means in item also
			$postID = get_post_meta( $newReview->ID, 'post_id', true );
			update_post_meta($postID, $metaKey['key'], $newMeta);
		}
	}
	/* REVIEW FUNCTIONS */

	/* SPECIAL OFFER FUNCTION */
	public static function getSpecialOffers($type = 'all', $args = array('post_type' => 'ait-dir-item', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC'), $filter = "all"){
		// post types => all / migrated / notmigratedposts
		// $meta = array( 'status' => 'updated', 'date' => time() );

		$result = array();
		$posts = get_posts($args);

		switch($type){
			case 'migrated':
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'getting migrated special offers');
				/* MIGRATION LOGGER */

				foreach ($posts as $index => $post) {
					$post_meta = get_post_meta($post->ID, '_ait-dir-item', true);
					// need to check if there are any special offers
					if(!empty($post_meta['specialActive'])){
						$meta = get_post_meta($post->ID, 'ait_special_offer_migrated', true);
						if(is_array($meta)){
							// we have meta -> this user is migrated
							array_push($result, $post);
						}
					}
				}
				//$args['meta_key'] = 'ait_migrated';
				//$args['meta_compare'] = 'EXISTS';
			break;
			case 'notmigrated':
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'getting non migrated special offers');
				/* MIGRATION LOGGER */

				foreach ($posts as $index => $post) {
					$post_meta = get_post_meta($post->ID, '_ait-dir-item', true);
					// need to check if there are any special offers
					if(!empty($post_meta['specialActive'])){
						// this item has special offer, next check if we migrated this already
						$meta = get_post_meta($post->ID, 'ait_special_offer_migrated', true);
						if(!is_array($meta)){
							// we dont have meta -> this offer is not migrated
							array_push($result, $post);
						}
					}
				}
			break;
			default:
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'getting all special offers');
				/* MIGRATION LOGGER */

				//$result = $posts;
			break;
		}

		/* MIGRATION LOGGER */
		//AitMigration::log('info', 'before wordpress query');
		/* MIGRATION LOGGER */

		//$query = new WP_Query($args);

		/* MIGRATION LOGGER */
		//AitMigration::log('info', 'got '.count($query->posts).' posts');
		/* MIGRATION LOGGER */

		$result = AitMigration::filterResultsData($result, $filter);
		return $result;
	}

	// $id => post id with special offers enabled
	// refactor this function
	public static function migrateSpecialOffer($id){
		// create new post type from item stored in the meta
		$meta = get_post_meta($id, 'ait_special_offer_migrated', true);
		if(is_array($meta)){
			// we have meta -> this post is migrated
			AitMigration::log('warning', 'special offer <' . $id . '> already migrated');
		} else {
			$migrated_post_meta = get_post_meta($id, 'ait_migrated', true);
			$migrated_post_id = $migrated_post_meta['newid'];

			$old_post = get_post($id);
			$old_post_meta = get_post_meta($id, '_ait-dir-item', true);

			// create new post
			$post = array(
				'post_type'		=> 'ait-special-offer',
				'post_status'	=> 'publish',

				'post_title'	=> $old_post_meta['specialTitle'],
				'post_content'	=> $old_post_meta['specialContent'],
			);

			$postId = wp_insert_post($post, false);
			if($postId !== 0){
				/* MIGRATION LOGGER */
				AitMigration::log('info', 'post <'.$postId.'> successfully created');
				/* MIGRATION LOGGER */

				// add language
				if(self::$poly){ // polylang enabled
					/* MIGRATION LOGGER */
					AitMigration::log('info', 'item will translate');
					/* MIGRATION LOGGER */

					// get items old wpml data
					$lang = pll_get_post_language($migrated_post_id, 'locale');
					if($lang !== false){
						/* MIGRATION LOGGER */
						AitMigration::log('info', 'found language <'.$lang.'> from item <'.$migrated_post_id.'>');
						/* MIGRATION LOGGER */
						pll_set_post_language($postId, $lang);
					} else {
						/* MIGRATION LOGGER */
						AitMigration::log('info', 'no language found from item <'.$migrated_post_id.'>, using default');
						/* MIGRATION LOGGER */
						pll_set_post_language($postId, AitMigration::getDefaultLang());
					}
				} else {
					/* MIGRATION LOGGER */
					AitMigration::log('info', 'item wont translate');
					/* MIGRATION LOGGER */
				}

				// attach featured image
				global $wpdb;
				$thumbId = $wpdb->get_var('SELECT `ID` FROM `'.$wpdb->prefix.'posts` WHERE post_type LIKE "%attachment%" AND guid = "'.$old_post_meta['specialImage'].'"');
				if(!empty($thumbId)){
					$result = set_post_thumbnail( $postId, $thumbId );
					if($result !== false){
						/* MIGRATION LOGGER */
						AitMigration::log('info', 'post thumbnail <'.$thumbId.'> successfully added to post <'.$postId.'>');
						/* MIGRATION LOGGER */
					} else {
						/* MIGRATION LOGGER */
						AitMigration::log('warning', 'post thumbnail <'.$thumbId.'> failed added to post <'.$postId.'>');
						/* MIGRATION LOGGER */
					}
				}

				// attach metadata
				$regexMatches = array();
				preg_match('/\d+\.?\d*/', $old_post_meta['specialPrice'], $regexMatches);
				$price = $regexMatches[0];

				$post_meta = array(
					'price' 	=> is_numeric($price) ? $price : "",
					'currency'	=> "USD",	// default
					'dateFrom'	=> $old_post->post_date,
					'dateTo'	=> date('Y-m-d', strtotime(date("Y-m-d", mktime()) . " + 365 day") ),	// date of the migration + 1 year
					'item'		=> $migrated_post_id,
				);

				$result = update_post_meta($postId, '_ait-special-offer_special-offer-data', $post_meta);
				if($result !== false){
					/* MIGRATION LOGGER */
					AitMigration::log('info', 'postmeta <_ait-special-offer_special-offer-data> successfully added to post <'.$postId.'>');
					/* MIGRATION LOGGER */
				} else {
					/* MIGRATION LOGGER */
					AitMigration::log('warning', 'postmeta <_ait-special-offer_special-offer-data> failed to add to post <'.$postId.'>');
					/* MIGRATION LOGGER */
				}

				$result = update_post_meta($postId, '_ait-special-offer_special-offer_price', $post_meta['price']);
				if($result !== false){
					/* MIGRATION LOGGER */
					AitMigration::log('info', 'postmeta <_ait-special-offer_special-offer_price> successfully added to post <'.$postId.'>');
					/* MIGRATION LOGGER */
				} else {
					/* MIGRATION LOGGER */
					AitMigration::log('warning', 'postmeta <_ait-special-offer_special-offer_price> failed to add to post <'.$postId.'>');
					/* MIGRATION LOGGER */
				}

				$result = update_post_meta($postId, '_ait-special-offer_special-offer_dateFrom', $post_meta['dateFrom']);
				if($result !== false){
					/* MIGRATION LOGGER */
					AitMigration::log('info', 'postmeta <_ait-special-offer_special-offer_dateFrom> successfully added to post <'.$postId.'>');
					/* MIGRATION LOGGER */
				} else {
					/* MIGRATION LOGGER */
					AitMigration::log('warning', 'postmeta <_ait-special-offer_special-offer_dateFrom> failed to add to post <'.$postId.'>');
					/* MIGRATION LOGGER */
				}

				$result = update_post_meta($postId, '_ait-special-offer_special-offer_dateTo', $post_meta['dateTo']);
				if($result !== false){
					/* MIGRATION LOGGER */
					AitMigration::log('info', 'postmeta <_ait-special-offer_special-offer_dateTo> successfully added to post <'.$postId.'>');
					/* MIGRATION LOGGER */
				} else {
					/* MIGRATION LOGGER */
					AitMigration::log('warning', 'postmeta <_ait-special-offer_special-offer_dateTo> failed to add to post <'.$postId.'>');
					/* MIGRATION LOGGER */
				}

				$result = update_post_meta($postId, '_ait-special-offer_special-offer_item', $post_meta['item']);
				if($result !== false){
					/* MIGRATION LOGGER */
					AitMigration::log('info', 'postmeta <_ait-special-offer_special-offer_item> successfully added to post <'.$postId.'>');
					/* MIGRATION LOGGER */
				} else {
					/* MIGRATION LOGGER */
					AitMigration::log('warning', 'postmeta <_ait-special-offer_special-offer_item> failed to add to post <'.$postId.'>');
					/* MIGRATION LOGGER */
				}
			}
			update_post_meta($id, 'ait_special_offer_migrated', array( 'status' => 'updated', 'date' => time(), 'newid' => $postId ));
		}
	}
	/* SPECIAL OFFER FUNCTION */

	/* HELPER FUNCTIONS */
	public static function updateStatus($type){
		switch($type){
			case 'users':
				//$users = AitMigration::getUsers('notmigrated', array('fields' => 'ID'));
				$users = AitMigration::getUsers('notmigrated', array(), 'id');
				update_option('_ait_directory_migration_users', array('count' => count($users), 'ids' => $users));
			break;
			case 'terms':
				$terms = AitMigration::getTerms('notmigrated', array('ait-dir-item-category', 'ait-dir-item-location'), 'id');
				update_option('_ait_directory_migration_terms', array('count' => count($terms), 'ids' => $terms));
			break;
			case 'items':
				$items = AitMigration::getPosts('notmigrated', array('post_type' => 'ait-dir-item', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC'), 'id');
				update_option('_ait_directory_migration_items', array('count' => count($items), 'ids' => $items));
			break;
			case 'reviews':
				if(defined('AIT_REVIEWS_ENABLED')){
					$reviews = AitMigration::getPosts('notmigrated', array('post_type' => 'ait-rating', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC'), 'id');
					update_option('_ait_directory_migration_reviews', array('count' => count($reviews), 'ids' => $reviews));
				}
			break;
			case 'special-offers':
				if(defined('AIT_SPECIAL_OFFERS_ENABLED')){
					$offers = AitMigration::getSpecialOffers('notmigrated', array('post_type' => 'ait-dir-item', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC'), 'id');
					update_option('_ait_directory_migration_special_offers', array('count' => count($offers), 'ids' => $offers));
				}
			break;
			default:
			break;
		}
	}

	public static function prepareCommands(){
		$commands = array();

		// standard option groups
		$optionGroups = array(
			array(
				'key' => '_ait_directory_migration_users',
				'cmd' => 'user',
			),
			array(
				'key' => '_ait_directory_migration_terms',
				'cmd' => 'term',
			),
			array(
				'key' => '_ait_directory_migration_items',
				'cmd' => 'item',
			),
		);

		if(defined('AIT_REVIEWS_ENABLED')){
			array_push($optionGroups, array(
				'key' => '_ait_directory_migration_reviews',
				'cmd' => 'review',
			));
		}

		if(defined('AIT_SPECIAL_OFFERS_ENABLED')){
			array_push($optionGroups, array(
				'key' => '_ait_directory_migration_special_offers',
				'cmd' => 'special-offer',
			));
		}

		foreach($optionGroups as $group){
			$data = get_option($group['key'], array());
			if(!empty($data['ids'])){
				foreach ($data['ids'] as $index => $id) {
					array_push($commands, array($group['cmd'], intval($id)));
				}
			}
		}

		return $commands;
	}

	public static function filterResultsData($data, $fields = 'all'){
		/* MIGRATION LOGGER */
		AitMigration::log('info', 'filtering '.count($data).' results');
		/* MIGRATION LOGGER */

		$result = array();

		// supported > all / id
		if($fields == 'id'){
			/* MIGRATION LOGGER */
			AitMigration::log('info', 'filtering results by id');
			/* MIGRATION LOGGER */

			foreach($data as $index => $value){
				$result[$index] = isset($value->term_id) ? $value->term_id : $value->ID;
			}
		} else {
			$result = $data;
		}
		return $result;
	}

	public static function log($type, $message, $logtime = true, $format = 'U'){

		if(AIT_MIGRATION_PLUGIN_DEBUG){
			$filename = self::$paths['logs'].'/'.self::$filename;
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

			if(AIT_MIGRATION_PLUGIN_DEBUG_LEVEL != "all"){
				if(AIT_MIGRATION_PLUGIN_DEBUG_LEVEL == $type){
					//echo implode("/", $write);
					error_log($write."\r\n", 3, $filename);
				}
			} else {
				//echo implode("/", $write);
				error_log($write."\r\n", 3, $filename);
			}
		}
	}
	/* HELPER FUNCTIONS */

	/* TRANSLATION FUNCTIONS */
	public static function hasSQLTable($tableNameWithoutPrefix){
		global $wpdb;
		$result = false;

		//http://stackoverflow.com/questions/8829102/mysql-check-if-table-exists-without-using-select-from
		$dbdata = $wpdb->get_results(
			"SELECT * FROM information_schema.tables WHERE `table_schema` = '".$wpdb->dbname."' AND `table_name` = '".$wpdb->prefix.$tableNameWithoutPrefix."' LIMIT 1;"
		);

		if(is_array($dbdata) && !empty($dbdata)){
			$result = true;
		}

		return $result;
	}

	public static function hasWMPLTranslations(){
		return AitMigration::hasSQLTable('icl_translations');
	}

	public static function getItemWPMLTranslations($post_id, $includeSelf = false){
		global $wpdb;
		$result = array();

		$sql = "SELECT * FROM `".$wpdb->prefix."icl_translations` WHERE  `trid` = ".$post_id." AND `element_id` != ".$post_id;
		if($includeSelf){
			$sql = "SELECT * FROM `".$wpdb->prefix."icl_translations` WHERE `trid` = ".$post_id;
		}

		$result = $wpdb->get_results($sql);

		return $result;
	}

	public static function getItemWPMLData($post_id){
		global $wpdb;
		$result = null;

		$sql = "SELECT * FROM `".$wpdb->prefix."icl_translations` WHERE `element_id` = ".$post_id." LIMIT 1";

		$dbdata = $wpdb->get_results($sql);
		if(is_array($dbdata) && !empty($dbdata)){
			$result = reset($dbdata);
		}

		return $result;
	}

	public static function getTermWPMLData($term_id){
		return AitMigration::getItemWPMLData($term_id);
	}

	public static function willTranslate(){
		return self::$wpml && self::$poly;
	}

	public static function getDefaultLang(){
		$lang = AitLangs::getDefaultLang();

		return $lang->slug;
	}
	/* TRANSLATION FUNCTIONS */
}