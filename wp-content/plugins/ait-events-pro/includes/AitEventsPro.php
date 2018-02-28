<?php

/*
 * AIT WordPress Plugin
 *
 * Copyright (c) 2015, Affinity Information Technology, s.r.o. (http://ait-themes.com)
 */

class AitEventsPro
{
	/**
	 * @var array
	 */
	protected $paths;



	/**
	 * @var array
	 */
	protected $compatibleThemes = array('eventguide', 'skeleton', 'cityguide', 'directory2', 'businessfinder2');

	/**
	 * @var string
	 */
	protected $currentTheme;

	protected $eventCategoryId = 'ait-events-pro';


	/**
	 * @var AitEventsPro
	 */
	private static $instance;


	/**
	 * Main entry point to EventsPro
	 *
	 * @param  string $pluginFile Full path to plugin file
	 * @return void
	 */
	public function run($pluginFile)
	{
		$theme = wp_get_theme();
		$this->currentTheme = $theme->parent() != false ? $theme->parent()->stylesheet : $theme->stylesheet;	// this return parent theme on active child theme

		$basedir = dirname($pluginFile);
		$baseurl = plugins_url('', $pluginFile);

		$this->paths = (object) array(
			'dir' => (object) array(
				'pluginfile' => $pluginFile,
				'root'       => $basedir,
				'lib'        => $basedir . '/lib',
				'cpts'       => $basedir . '/cpts',
				'elements'   => $basedir . '/elements',
				'includes'   => $basedir . '/includes',
				'templates'  => $basedir . '/templates',
			),
			'url' => (object) array(
				'root'     => $baseurl,
				'lib'      => $baseurl . '/lib',
				'cpts'     => $baseurl . '/cpts',
				'elements' => $baseurl . '/elements',
				'templates'=> $baseurl . '/templates',
				'includes' => $baseurl . '/includes',
			),
		);

		spl_autoload_register(array($this, 'autoload'));

		$adminPageParams = array(
			'pageSlug' 		 => 'events-pro-options',
			'pluginCodename' => 'ait-events-pro',
			'pluginInstance' => self::getInstance(),
			'config'         => $this->paths->dir->includes . "/admin-config.php",
			'menuTitle'      => __('Events Pro', 'ait-events-pro'),
		);

		$adminPage = new AitEventsProSettingsAdminPage();
		$adminPage->run($adminPageParams);

		register_activation_hook($pluginFile, array($this, 'onPluginActivationCallback'));

		register_deactivation_hook($pluginFile, array($this, 'onPluginDeactivationCallback'));

		AitEventsPro::createDbTables();

		add_action( 'plugins_loaded', array($this, 'onPluginsLoadedCallback' ));

		add_action('ait-after-framework-load', array($this, 'onAfterFwLoadCallback'));
		add_action('init', array($this, 'onInitCallback'), 9);
		add_action('init', array($this, 'onInitCallback_11'), 11);

		add_action($this->eventCategoryId."_add_form_fields", array($this, 'addEventCategoryFormFields'), 10, 2);
		add_action($this->eventCategoryId."_edit_form_fields", array($this, 'editEventCategoryFormFields'), 10, 2);
		add_action("edited_".$this->eventCategoryId, array($this, 'saveExtraEventCategoryFormFields'), 10, 2);
		add_action("created_".$this->eventCategoryId, array($this, 'saveExtraEventCategoryFormFields'), 10, 2);

		add_filter('ait-special-custom-pages', array($this, 'addEventProTaxonomiesSpecialPages'));

		// Template functions
		add_filter('wplatte-get-template-part', array($this, 'getTemplate'), 10, 3);



		add_action( 'save_post', array(__CLASS__, 'savePostMeta'), 10, 3);
		add_action('delete_post',  array(__CLASS__, 'deletePost'));

		// add custom tables in demo content
		add_action('ait-create-content-custom-tables', array(__CLASS__, 'createDbTables'), 10, 1);
		add_filter('ait-backup-content-custom-tables', function($tables){
			array_push($tables, 'ait_eventspro_dates');
			return $tables;
		});

		// add new administrator capabilities/features
		add_action('init', array($this, 'initAdminFeatures'), 8, 0);

		add_action('switch_theme', array($this, 'onSwitchTheme'), 10);

	}



	public function checkPluginCompatibility($die = false)
	{
		if ( !in_array($this->currentTheme, $this->compatibleThemes) ) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php' );
			$pluginFile = $this->getPaths('dir')->pluginfile;
			deactivate_plugins(plugin_basename($pluginFile));
			if($die){
				wp_die('Current theme is not compatible with Events Pro plugin :(', '',  array('back_link'=>true));
			} else {
				add_action( 'admin_notices', array($this, 'deactivateMessage') );
			}
		}
	}



	public function onSwitchTheme()
	{
		$this->checkPluginCompatibility();
	}



	public function onPluginActivationCallback($network_wide )
	{
		$this->checkPluginCompatibility(true);
		if(class_exists('AitCache')){
			AitCache::clean();
		}

		if (  $network_wide )
			wp_die('Events Pro Plugin is not allowed for network activation');

		add_option( 'ait_events_pro_flush_rewrite_rules_flag', true );

		$this->updateDirectoryPackages();
		$this->updatePllOptions();
	}



	public function onPluginDeactivationCallback()
	{
		if(class_exists('AitCache')){
			AitCache::clean();
		}

	}



	public function onPluginsLoadedCallback()
	{
		require_once(ABSPATH . 'wp-admin/includes/plugin.php' );
		if(!is_plugin_active('ait-toolkit/plugin.php')){
			$pluginFile = $this->getPaths('dir')->pluginfile;
			deactivate_plugins(plugin_basename($pluginFile));
			// wp_die('Events Pro plugin requires Ait Element Toolkit', '',  array('back_link'=>true));

		}

		load_plugin_textdomain('ait-events-pro', false, basename($this->paths->dir->root) . '/languages');

		// add external element
		add_filter('ait-elements-config', array($this, 'addEventsProElementConfig') , 13);

		$options = get_option('ait-events-pro-plugin');
		if (!$options) {
			// defines default values for options in case this is the first installation
			$options = array(
				'version' => '1.0',
			);
			update_option('ait-events-pro-plugin', $options);
		}

		// plugin upgrade
		if ($options && version_compare($options['version'], AIT_EVENTS_PRO_VERSION, '<')) {
			add_action('ait-theme-run', function(){
				if(class_exists('AitCache')){
					AitCache::clean();
				}
			});
			$upgrade = new AitEventsProUpgrade($options);
			if (!$upgrade->upgrade())
				return;
		}
	}



	/**
	 * Callbak for 'ait-after-framework-load' action hook
	 *
	 * @return void
	 */
	public function onAfterFwLoadCallback()
	{
		// add theme support for Events-Pro element and custom post type
		add_filter( 'ait-theme-configuration', function($configuration){
			// array_push($configuration['ait-theme-support']['elements'], 'events-pro');
			array_push($configuration['ait-theme-support']['cpts'], 'event-pro');
			return $configuration;
		});

		// add maxEvents to the theme config
		add_filter('ait-theme-config', function($config){
			if(isset($config['packages'])){
				$configOption = array(
					"maxEvents" => array(
					"label" => "Maximum Events",
					"type"  => "number",
					"less"  => false,
				));
				$config['packages']['options']['packageTypes']['items'] = AitEventsPro::arrayInsert($config['packages']['options']['packageTypes']['items'], $configOption, 6);
			}
			return $config;
		});
	}



	public function onInitCallback()
	{
		if ( defined('AIT_THEME_CODENAME') && in_array(AIT_THEME_CODENAME, $this->compatibleThemes) ) {
			$this->registerEventProCpt();
			$this->registerTax();

			$this->flushRewriteRules();

			// add new global variable with events pro options
			add_filter('wplatte-layout-params', array($this, 'wplatteLayoutParams'));

			// add maxEvents option for each registered package
			add_filter( 'ait_add_package_option', array($this, 'addPackageOption'), 10, 2 );

			// filter count of events in admin pages for registered users
			add_filter( "views_edit-ait-event-pro" , array($this, 'fixAdminCounts'), 10, 1);

		} else {
			add_action( 'admin_notices', array($this, 'deactivateMessage') );
		}
	}



	public function onInitCallback_11()
	{
		// $this->registerTax();

		// if ait-item cpt exists, ait-locations taxonomy is shared with Event Pro
		if(post_type_exists( 'ait-item' )){
			register_taxonomy_for_object_type('ait-locations', 'ait-event-pro');
		} else {
			// TODO otherwise you should register new Location taxonomy

		}
	}



	public function flushRewriteRules() {
		if ( get_option( 'ait_events_pro_flush_rewrite_rules_flag' ) ) {
			flush_rewrite_rules();
			delete_option( 'ait_events_pro_flush_rewrite_rules_flag' );
		}
	}




	/**
	 * Additional params for layout
	 * @param  array $params
	 * @hookedTo WpLatteLayoutParams
	 * @return array
	 */
	public function wplatteLayoutParams($params)
	{
		$params['eventsProOptions'] = get_option('ait_events_pro_options', array());

		return $params;
	}




	/**
	 * Gets options key
	 *
	 * @return string
	 */
	public function getOptionsKey()
	{
		return '_ait_eventspro_options';
	}


	public function registerEventProCpt()
	{
		add_filter( 'ait-toolkit-cpts-list', array($this,  'registerCpt'));
	}



	public function registerCpt($cpts)
	{
		$newCpt = array(
			'event-pro' => array(
				'package' => array(
					'business' => true,
					'developer' => true,
					'themeforest' => true,
				),
				'paths' => $this->paths,
			),
		);
		$cpts = array_merge($cpts, $newCpt);

		return $cpts;
	}




	public function registerTax()
	{

		$labels = array(
			'name'				=> _x( 'Opportunity/Event Categories', 'taxonomy general name', 'ait-events-pro' ),
			'menu_name'			=> __( 'Categories', 'ait-events-pro' ),
			'singular_name'		=> _x( 'Category', 'taxonomy singular name', 'ait-events-pro' ),
			'search_items'		=> __( 'Search Categories', 'ait-events-pro' ),
			'all_items'			=> __( 'All Categories', 'ait-events-pro' ),
			'parent_item'		=> __( 'Parent Category', 'ait-events-pro' ),
			'parent_item_colon'	=> __( 'Parent Category:', 'ait-events-pro' ),
			'edit_item'			=> __( 'Edit Category', 'ait-events-pro' ),
			'update_item'		=> __( 'Update Category', 'ait-events-pro' ),
			'add_new_item'		=> __( 'Add New Category', 'ait-events-pro' ),
			'new_item_name'		=> __( 'New Category Name', 'ait-events-pro' ),
		);

		$capabilities = array(
			'manage_terms'		=> "ait_toolkit_eventspro_category_manage_events_pro",
			'edit_terms'		=> "ait_toolkit_eventspro_category_edit_events_pro",
			'delete_terms'		=> "ait_toolkit_eventspro_category_delete_events_pro",
			'assign_terms'		=> "ait_toolkit_eventspro_category_assign_events_pro",
		);

		$args = array(
			'hierarchical'         => true,
			'labels'               => $labels,
			'show_ui'              => true,
			'show_admin_column'    => true,
			'query_var'            => true,
			'rewrite'              => array( 'slug' => 'events-pro' ),
			'capabilities'         => $capabilities,
			'ait-translatable-tax' => true,
		);
		register_taxonomy( 'ait-events-pro', 'ait-event-pro', $args );

		// add event cateory option to ait backup/export
		add_filter('ait-backup-wpoptions', function($options, $isDemoContent){
			$options[] = "ait-events-pro\_category\_%";
			$options[] = "ait_events_pro_options";
			return $options;
		}, 10, 2);


	}



	public function initAdminFeatures()
	{
		global $wp_roles;
		$capabilities = array(
			'_ait-event-pro_event-author_author',
		);
		foreach ($capabilities as $capability) {
			if(!empty($wp_roles->role_objects['administrator']) and !isset($wp_roles->role_objects['administrator']->capabilities[$capability])){
				$wp_roles->role_objects['administrator']->add_cap($capability, true);
			}
		}
	}



	public function editEventCategoryFormFields($tag, $taxonomy)
	{
		$termId = $tag->term_id;
		$extraFieldsValues = get_option( $this->eventCategoryId."_category_{$termId}");
		?>

		<tr class="form-field">
			<th scope="row">
				<label for="<?php echo $this->eventCategoryId ?>[keywords]"><?php _e('Keywords', 'ait-toolkit') ?></label>
			</th>
			<td>
				<input type="text" name="<?php echo $this->eventCategoryId ?>[keywords]" id="<?php echo $this->eventCategoryId ?>[keywords]" size="25" style="width:70%;" value="<?php echo isset($extraFieldsValues["keywords"]) ? $extraFieldsValues["keywords"] : ''; ?>">
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row">
				<label for="<?php echo $this->eventCategoryId ?>[icon]"><?php _e('Icon', 'ait-toolkit') ?></label>
			</th>
			<td>
				<input type="text" name="<?php echo $this->eventCategoryId ?>[icon]" id="<?php echo $this->eventCategoryId ?>[icon]" size="25" style="width:70%;" value="<?php echo isset($extraFieldsValues["icon"]) ? $extraFieldsValues["icon"] : ''; ?>">
				<input type="button" class="choose-category-icon-button button button-secondary" <?php echo aitDataAttr('select-image', array('title' => 'Select Image', 'buttonTitle' => __('Insert Image', 'ait-toolkit'))); ?> style="width:25%;" value="<?php _e('Select Icon', 'ait-toolkit') ?>" id="<?php echo $this->eventCategoryId ?>[icon]-media-button">
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row">
				<label for="<?php echo $this->eventCategoryId ?>[icon_color]"><?php _e('Category Color', 'ait-toolkit') ?></label>
			</th>
			<td class="ait-colorpicker ait-control-wrapper">
				<span class="ait-colorpicker-preview"><i style="background-color: rgba(0,0,0,0);"></i></span>
				<input type="text" class="ait-colorpicker-color" data-color-format="hex" id="<?php echo $this->eventCategoryId ?>[icon_color]" value="<?php echo isset($extraFieldsValues["icon_color"]) ? $extraFieldsValues["icon_color"] : "";?>">
				<input type="hidden" class="ait-colorpicker-storage" name="<?php echo $this->eventCategoryId ?>[icon_color]" value="<?php echo isset($extraFieldsValues["icon_color"]) ? $extraFieldsValues["icon_color"] : "";?>">
			</td>
			<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery('.ait-colorpicker').colorpicker();
			});
			</script>
		</tr>

		<tr class="form-field">
			<th scope="row">
				<label for="<?php echo $this->eventCategoryId ?>[map_icon]"><?php _e('Icon in Map', 'ait-toolkit') ?></label>
			</th>
			<td>
				<input type="text" name="<?php echo $this->eventCategoryId ?>[map_icon]" id="<?php echo $this->eventCategoryId ?>[map_icon]" size="25" style="width:70%;" value="<?php echo isset($extraFieldsValues["map_icon"]) ? $extraFieldsValues["map_icon"] : ''; ?>">
				<input type="button" class="choose-category-icon-button button button-secondary" <?php echo aitDataAttr('select-image', array('title' => 'Select Image', 'buttonTitle' => __('Insert Image', 'ait-toolkit'))); ?> style="width:25%;" value="<?php _e('Select Icon', 'ait-toolkit') ?>" id="<?php echo $this->eventCategoryId ?>[map_icon]-media-button">
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row">
				<label for="<?php echo $this->eventCategoryId ?>[header_type]"><?php _e('Header Type', 'ait-toolkit') ?></label>
			</th>
			<td>
				<select name="<?php echo $this->eventCategoryId ?>[header_type]" id="<?php echo $this->eventCategoryId ?>[header_type]" style="width:70%;">
					<option value="map" <?php echo isset($extraFieldsValues["header_type"]) && $extraFieldsValues["header_type"] == 'map' ? 'selected' : ''; ?>><?php _e('Map', 'ait-toolkit') ?></option>
					<option value="image" <?php echo isset($extraFieldsValues["header_type"]) && $extraFieldsValues["header_type"] == 'image' ? 'selected' : ''; ?>><?php _e('Image', 'ait-toolkit') ?></option>
					<option value="none" <?php echo isset($extraFieldsValues["header_type"]) && $extraFieldsValues["header_type"] == 'none' ? 'selected' : ''; ?>><?php _e('None', 'ait-toolkit') ?></option>
				</select>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row">
				<label for="<?php echo $this->eventCategoryId ?>[header_image]"><?php _e('Header Image', 'ait-toolkit') ?></label>
			</th>
			<td>
				<input type="text" name="<?php echo $this->eventCategoryId ?>[header_image]" id="<?php echo $this->eventCategoryId ?>[header_image]" size="25" style="width:70%;" value="<?php echo isset($extraFieldsValues["header_image"]) ? $extraFieldsValues["header_image"] : ''; ?>">
				<input type="button" class="choose-category-icon-button button button-secondary" <?php echo aitDataAttr('select-image', array('title' => 'Select Image', 'buttonTitle' => __('Insert Image', 'ait-toolkit'))); ?> style="width:25%;" value="<?php _e('Select Image', 'ait-toolkit') ?>" id="<?php echo $this->eventCategoryId ?>[header_image]-media-button">
			</td>
		</tr>

		<?php
	}



	public function addEventCategoryFormFields($taxonomy)
	{
		?>

		<div class="form-field">
			<label for="<?php echo $this->eventCategoryId ?>[keywords]"><?php _e('Keywords', 'ait-toolkit') ?></label>
			<input type="text" name="<?php echo $this->eventCategoryId ?>[keywords]" id="<?php echo $this->eventCategoryId ?>[keywords]" size="25" style="width:70%;" value="<?php echo isset($extraFieldsValues["keywords"]) ? $extraFieldsValues["keywords"] : ''; ?>">
		</div>

		<div class="form-field">
			<label for="<?php echo $this->eventCategoryId ?>[icon]"><?php _e('Icon', 'ait-toolkit') ?></label>
			<input type="text" name="<?php echo $this->eventCategoryId ?>[icon]" id="<?php echo $this->eventCategoryId ?>[icon]" size="25" style="width:70%;" value="<?php echo isset($extraFieldsValues["icon"]) ? $extraFieldsValues["icon"] : ''; ?>">
			<input type="button" class="choose-category-icon-button button button-secondary" <?php echo aitDataAttr('select-image', array('title' => 'Select Image', 'buttonTitle' => __('Insert Image', 'ait-toolkit'))); ?> style="width:25%;" value="<?php _e('Select Icon', 'ait-toolkit') ?>" id="<?php echo $this->eventCategoryId ?>[icon]-media-button">
		</div>

		<div class="form-field">
			<label for="<?php echo $this->eventCategoryId ?>[icon_color]"><?php _e('Category Color', 'ait-toolkit') ?></label>
			<div class="ait-colorpicker ait-control-wrapper">
				<span class="ait-colorpicker-preview"><i style="background-color: rgba(0,0,0,0);"></i></span>
				<input type="text" class="ait-colorpicker-color" data-color-format="hex" id="<?php echo $this->eventCategoryId ?>[icon_color]" value="">
				<input type="hidden" class="ait-colorpicker-storage" name="<?php echo $this->eventCategoryId ?>[icon_color]" value="">
			</div>
			<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery('.ait-colorpicker').colorpicker();
			});
			</script>
		</div>

		<div class="form-field">
			<label for="<?php echo $this->eventCategoryId ?>[map_icon]"><?php _e('Icon in Map', 'ait-toolkit') ?></label>
			<input type="text" name="<?php echo $this->eventCategoryId ?>[map_icon]" id="<?php echo $this->eventCategoryId ?>[map_icon]" size="25" style="width:70%;" value="<?php echo isset($extraFieldsValues["map_icon"]) ? $extraFieldsValues["map_icon"] : ''; ?>">
			<input type="button" class="choose-category-icon-button button button-secondary" <?php echo aitDataAttr('select-image', array('title' => 'Select Image', 'buttonTitle' => __('Insert Image', 'ait-toolkit'))); ?> style="width:25%;" value="<?php _e('Select Icon', 'ait-toolkit') ?>" id="<?php echo $this->eventCategoryId ?>[map_icon]-media-button">
		</div>

		<div class="form-field">
			<label for="<?php echo $this->eventCategoryId ?>[header_type]"><?php _e('Header Type', 'ait-toolkit') ?></label>
			<select name="<?php echo $this->eventCategoryId ?>[header_type]" id="<?php echo $this->eventCategoryId ?>[header_type]" style="width:70%;">
				<option value="map" selected><?php _e('Map', 'ait-toolkit') ?></option>
				<option value="image"><?php _e('Image', 'ait-toolkit') ?></option>
				<option value="none"><?php _e('None', 'ait-toolkit') ?></option>
			</select>
		</div>

		<div class="form-field">
			<label for="<?php echo $this->eventCategoryId ?>[header_image]"><?php _e('Header Image', 'ait-toolkit') ?></label>
			<input type="text" name="<?php echo $this->eventCategoryId ?>[header_image]" id="<?php echo $this->eventCategoryId ?>[header_image]" size="25" style="width:70%;" value="<?php echo isset($extraFieldsValues["header_image"]) ? $extraFieldsValues["header_image"] : ''; ?>">
			<input type="button" class="choose-category-icon-button button button-secondary" <?php echo aitDataAttr('select-image', array('title' => 'Select Image', 'buttonTitle' => __('Insert Image', 'ait-toolkit'))); ?> style="width:25%;" value="<?php _e('Select Image', 'ait-toolkit') ?>" id="<?php echo $this->eventCategoryId ?>[header_image]-media-button">
		</div>
		<?php
	}



	public function saveExtraEventCategoryFormFields($term_id)
	{
		if(isset( $_POST[$this->eventCategoryId])){
			$extraFields = get_option( $this->eventCategoryId."_category_{$term_id}");
			$keys = array_keys($_POST[$this->eventCategoryId]);
			foreach ($keys as $key){
				$extraFields[$key] = $_POST[$this->eventCategoryId][$key];
			}
			update_option($this->eventCategoryId . "_category_{$term_id}", $extraFields);
		}
	}



	public function addEventsProElementConfig($localConfig)
	{
		$elementConfig = include $this->paths->dir->elements . '/events-pro/events-pro.php';
		$localConfig['events-pro'] = $elementConfig;
		return $localConfig;
	}



	/* ************ helper methods ********** */
	public static function getInstance()
	{
		if(!self::$instance){
			self::$instance = new self;
		}

		return self::$instance;
	}



	public function autoload($class)
	{
		$file = '';

		if(substr($class, 0, 12) === 'AitEventsPro'){
			$file = $this->paths->dir->includes . "/{$class}.php";
		}

		if($file and file_exists($file)){
			require_once $file;
		}
	}



	public function deactivateMessage() {
			echo "<div class='error'><p>" . _x('Current theme is not compatible with Events Pro plugin!', 'ait-event-pro') . "</p></div>";
	}



	public function addEventProTaxonomiesSpecialPages($specialPages)
	{

		$pages = array();
		$obj = aitManager('cpts')->getByInternalId('ait-event-pro');

		if($obj === false) return $specialPages;

		foreach ($obj->getRawPublicTaxonomies() as $taxonomy) {
			$pages["_taxonomy_{$taxonomy->name}"] = array(
				'label'   => __($taxonomy->label, 'ait-toolkit'),
				'with-id' => false,
				'if' => "is_archive() && is_tax('{$taxonomy->name}')",
			);
		}

		$pages['_archive-event-pro'] = array(
			'label'   => __('Event Pro Archive', 'ait-events-pro'),
			'with-id' => false,
			'if' => "is_post_type_archive('ait-event-pro')",
		);

		return array_merge($pages, $specialPages);
	}



	public function contains($haystack, $needle){
		return strpos($haystack, $needle) !== FALSE;
	}


	public function getTemplate($templates, $slug, $name){
		$ok = true;
		foreach(glob($this->paths->dir->templates . '/*.php') as $file){
			$filename = basename($file, '.php');
			if(!$this->contains($slug, $filename)){
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


		$pluginDir = $this->paths->dir->templates;

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


	public static function prepareICSRecurringEvent($event)
	{
		$events = array();
		foreach (self::getActualRecurringDates($event) as $date) {
			// if (empty($date['dateTo'])) {
			// 	d($date['dateFrom']);
			// 	$date = new DateTime($date['dateFrom']);
			// 	$date->setTime(23,59,0);
			// 	$date['dateTo'] = $date->format('Y-m-d H:i:s');
			// 	d($date['dateTo']);
			// }
			array_push($events, array(
				'start' => $date['dateFrom'],
				'end' => $date['dateTo'],
				'name' => $event->rawTitle,
				'description' => '',
			));
		}

		return $events;
	}



	public static function getActualRecurringDates($event)
	{
		$result = array();
		$now = new DateTime();
		$now = $now->getTimeStamp();

		$meta = $event->meta('event-pro-data');
		if (empty($meta->dates)) {
			return array();
		}
		$dates = $meta->dates;
		foreach ($dates as $date) {
			if ( (empty($date['dateTo']) && strtotime($date['dateFrom']) >= $now) || (strtotime($date['dateTo']) >= $now) ) {
				array_push($result, $date);
			}
		}

		return $result;
	}



	public function getPaths($type = 'dir')
	{
		if ($type == 'dir') {
			return $this->paths->dir;
		} else {
			return $this->paths->url;
		}
	}



	public static function arrayInsert($array, $var, $position)
	{
		$array = array_slice($array, 0, $position, true) +
		$var +
		array_slice($array, $position, count($array) - count($var), true) ;
		return $array;
	}



	// this function updates existing account packages and adds maxEvents option for each package if doesn't exist yet
	public function updateDirectoryPackages()
	{
		$themeOptions = get_option(aitOptions()->getOptionKey('theme'), array());
		if (!isset($themeOptions['packages'])) {
			return;
		}
		foreach ($themeOptions['packages']['packageTypes'] as $key => $package) {
			if (!isset($package['maxEvents'])) {
				$themeOptions['packages']['packageTypes'][$key] = self::arrayInsert($themeOptions['packages']['packageTypes'][$key], array('maxEvents' => 0), 5);
			}
		}
		update_option(aitOptions()->getOptionKey('theme'), $themeOptions);
	}



	public function addPackageOption($options, $package)
	{
		if (!isset($options['maxEvents'])) {
			$options['maxEvents'] = isset($package['maxEvents']) ? $package['maxEvents'] : 0;
		}
		return $options;
	}



	public function fixAdminCounts($views)
	{
		if (function_exists('fixItemsCount')) {
			$views = fixItemsCount( 'ait-event-pro', $views );
		}
		return $views;
	}



	public function updatePllOptions()
	{
		$pllOptions = get_option('polylang', '');
		if (!empty($pllOptions)) {

			if (!in_array('ait-event-pro', $pllOptions['post_types'])) {
				$pllOptions['post_types'][] = 'ait-event-pro';
			}

			if (!in_array('ait-events-pro', $pllOptions['taxonomies'])) {
				$pllOptions['taxonomies'][] = 'ait-events-pro';
			}

			update_option( 'polylang', $pllOptions );
		}
	}



	public static function createDbTables() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'ait_eventspro_dates';
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				post_id mediumint(9) NOT NULL,
				date_from datetime,
				date_to datetime,
				UNIQUE KEY id (id)
			) $charset_collate;";

			/*require_once(ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );*/
			$wpdb->query($sql);
		}
	}



	public static function savePostMeta( $post_id, $post, $update )
	{
	    $slug = 'ait-event-pro';

	    if ( $slug != $post->post_type ) {
	        return;
	    }

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// Prevent quick edit from clearing custom fields
		if (defined('DOING_AJAX') && DOING_AJAX) {
			return;
		}

		// Prevent bulk edit case
		if (empty($_POST)) {
			return;
		}

		// save related item metadata
		if ( isset( $_POST['_ait-event-pro_event-pro-data']['item'] ) ) {
			$relatedItem = $_POST['_ait-event-pro_event-pro-data']['item'];
			update_post_meta( $post_id, 'ait-event-pro-related-item', $relatedItem );
		}


		// save gps separately
		$useItemLocation = filter_var($_POST['_ait-event-pro_event-pro-data']['useItemLocation'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		update_post_meta($post_id, 'ait-use-item-location', $useItemLocation);
		if ( $useItemLocation ) {
			// event uses item's gps
			delete_post_meta($post_id, 'ait-latitude');
			delete_post_meta($post_id, 'ait-longitude');
		} else {
			// event has own gps
			$address = $_POST['_ait-event-pro_event-pro-data']['map'];
			update_post_meta($post_id, 'ait-latitude', $address['latitude']);
			update_post_meta($post_id, 'ait-longitude', $address['longitude']);
		}

	    // save recurring dates
	    if ( !empty( $_POST['_ait-event-pro_event-pro-data']['dates'] ) ) {
	    	global $wpdb;
			$table_name = $wpdb->prefix . 'ait_eventspro_dates';

			// remove old dates for current post
			$wpdb->delete( $table_name, array(
					'post_id' => $post_id
				),
				array ('%s')
			);
			foreach ($_POST['_ait-event-pro_event-pro-data']['dates'] as $key => $date) {
				$ts1 = strtotime($date['dateFrom']);
				$ts2 = strtotime($date['dateTo']);
				// ignore case when events ends before it starts
				if( $ts2 && $ts2 < $ts1 ) continue;

				$wpdb->insert(
					$table_name,
					array(
						'post_id' => $post_id,
						'date_from' => $date['dateFrom'],
						'date_to' => empty($date['dateTo']) ? NULL : $date['dateTo'],
					)
				);
			}
	    }
	}



	public static function saveAuthorMetabox($postId, $post, $metabox, $data)
	{
		if($post->post_type == 'ait-event-pro'){
			if(empty($data)){
				$data = get_post_meta($postId, '_ait-event-pro_event-author', true);
			}

			if(is_array($data) && !empty($data)){
				$update_post = array(
					'ID' => $post->ID,
					'post_author' => intval($data['author']),
				);


				if(!isset($GLOBALS['ait_saveEventAuthorMetabox_runned_once'])){
					$GLOBALS['ait_saveEventAuthorMetabox_runned_once'] = true;
					wp_update_post($update_post);

					update_post_meta($postId, '_ait-event-pro_event-author', $data);
				}
			}
		}
	}



	public static function fillAuthorMetabox()
	{
		$wp_users = get_users(array('orderby' => 'ID'));
		$result = array();

		// push current option (in this case the post author) to the first position
		global $post;
		$unset = null;
		if(isset($post)){
			$user = new WP_User($post->post_author);
			$result[$user->ID] = $user->data->user_nicename;
			$unset = $user->ID;
		}

		//id => label
		foreach($wp_users as $index => $user){
			if($user->ID != $unset){
				$result[$user->ID] = $user->data->user_nicename;
			}
		}
		return $result;
	}


	public static function deletePost($post_id)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'ait_eventspro_dates';

		// remove old dates for current post
		$wpdb->delete( $table_name, array(
				'post_id' => $post_id
			),
			array ('%s')
		);
	}



	public static function getEventsFromDate($date)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'ait_eventspro_dates';
		$postids = $wpdb->get_col( $wpdb->prepare(
				"
			SELECT DISTINCT post_id, date_from
			FROM $table_name
			WHERE
				( date_from <= %s AND date_to >= %s )
				OR
				( date_from >= %s )
			ORDER BY date_from ASC
			",
			$date,
			$date,
			$date
		) );
		if (empty($postids)) {
			return array(0);
		}

		return array_unique($postids);
	}


	public static function getEvents()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'ait_eventspro_dates';
		$postids = $wpdb->get_col(
			"
			SELECT DISTINCT post_id
			FROM $table_name
			ORDER BY date_from, id ASC
			"
		);
		if (empty($postids)) {
			return array(0);
		}
		return $postids;
	}



	public static function getAllActualDates()
	{
		$date = date_i18n('Y-m-d H:i:s');
		global $wpdb;
		$table_name = $wpdb->prefix . 'ait_eventspro_dates';
		$dates = $wpdb->get_col( $wpdb->prepare(
			"
			SELECT DATE(date_from)
			FROM $table_name
			WHERE
				( date_from <= %s AND date_to >= %s )
				OR
				( date_from >= %s )
			ORDER BY date_from ASC
			",
			$date,
			$date,
			$date
		) );
		return $dates;
	}


	public static function getEventClosestDate($post_id, $date = '')
	{
		$date = empty($date) ? date_i18n('Y-m-d') : $date;
		global $wpdb;
		$table_name = $wpdb->prefix . 'ait_eventspro_dates';
		$row = $wpdb->get_row( $wpdb->prepare(
			"
			SELECT *
			FROM $table_name
			WHERE
				post_id = %s
				AND
				(
					( date_from <= %s AND date_to >= %s )
					OR
					( date_from <= %s AND date_to IS NULL )
					OR
					( date_from >= %s )
				)
			ORDER BY date_from ASC
			",
			$post_id,
			$date,
			$date,
			$date,
			$date
		) );
		if ($row !== null) {
			return array(
				'dateFrom' => $row->date_from,
				'dateTo' => $row->date_to,
			);
		}

		//there isn't any future date - return first date ever
		$row = $wpdb->get_row( $wpdb->prepare(
			"
			SELECT *
			FROM $table_name
			WHERE
				post_id = %s

			ORDER BY date_from DESC
			",
			$post_id
		) );
		if ($row !== null) {
			return array(
				'dateFrom' => $row->date_from,
				'dateTo' => $row->date_to,
			);
		}

		return array();
	}



	public static function getEventRecurringDates($post_id)
	{
		$date = date_i18n('Y-m-d');
		$dates = array();
		global $wpdb;
		$table_name = $wpdb->prefix . 'ait_eventspro_dates';
		$result = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT *
			FROM $table_name
			WHERE
				post_id = %s
				AND
				(
					( date_from <= %s AND date_to >= %s )
					OR
					( date_from <= %s AND date_to IS NULL )
					OR
					( date_from >= %s )
				)
			ORDER BY date_from ASC
			",
			$post_id,
			$date,
			$date,
			$date,
			$date
		) );
		foreach ($result as $row) {
			array_push($dates, array(
				'dateFrom' => $row->date_from,
				'dateTo' => $row->date_to,
			));
		}
		return $dates;
	}


	public static function getEventsByItem($itemID, $args = array())
	{
		$defaults = array(
			'post_type'      => 'ait-event-pro',
			'posts_per_page' => -1,
			'meta_key'       => 'ait-event-pro-related-item',
			'meta_value'     => $itemID,
			'post__in'       => self::getEventsFromDate(date_i18n('Y-m-d')),
			'orderby'        => 'post__in',
			'order'          => 'ASC',
		);
		$args = array_merge($defaults, $args);
		if ($args['orderby'] == 'post__in' && $args['order'] == 'DESC') {
			$args['post__in'] = array_reverse($args['post__in']);
		}
		return new WpLatteWpQuery($args);
	}


	public static function getEventsByDate($date)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'ait_eventspro_dates';
		$parts = explode(" ", $date);
		if(sizeof($parts) == 2) {
			// if $date is a datetime consider the time in comparison
			// usually the case when we try to retrieve events from today but only those wich are still running
			// do not use mysql UNIX_TIMESTAMP functions because timezones might differ
			// if date_to is null but date_from is today's date it means the event is happening only today no matter what time
			$postids = $wpdb->get_col( $wpdb->prepare(
				"
				SELECT DISTINCT post_id
				FROM $table_name
				WHERE
					( date_from <= %s AND date_to >= %s )
					OR
					( DATE(date_from) = %s AND date_to IS NULL)
				ORDER BY date_from ASC
				",
				$date,
				$date,
				$parts[0]
			) );

		} else {
			$postids = $wpdb->get_col( $wpdb->prepare(
				"
				SELECT DISTINCT post_id
				FROM $table_name
				WHERE
					( DATE(date_from) <= %s AND DATE(date_to) >= %s )
					OR
					( DATE(date_from) = %s AND date_to IS NULL)
				ORDER BY date_from ASC
				",
				$date,
				$date,
				$date
			) );

		}
		if (empty($postids)) {
			return array(0);
		}
		return $postids;
	}


	public static function getEventsByRadius($radius, $lat, $lon)
	{
		$query = new Wp_Query( array(
			'status' => 'publish',
			'post_type' => 'ait-event-pro',
			'posts_per_page' => -1,
		));

		$filtered = array();
		foreach (new WpLatteLoopIterator($query) as $item) {
			$address = self::getEventAddress($item, true);
			$eventLat = $address['latitude'];
			$eventLng = $address['longitude'];

			if($eventLat !== false && $eventLng !== false){
				if (self::isPointInRadius($radius, $lat, $lon, $eventLat, $eventLng)){
					array_push($filtered, $item->id);
				}
			}
		}
		return $filtered;
	}



	public static function getEventAddress($event, $all = false)
	{
		$meta = $event->meta('event-pro-data');
		$useItemLocation = filter_var($meta->useItemLocation, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		if ($useItemLocation and !empty($meta->item)) {
			$itemMeta = get_post_meta($meta->item, '_ait-item_item-data', true);
			if ($all) {
				return array(
					'address'    => $itemMeta['map']['address'],
					'latitude'   => $itemMeta['map']['latitude'],
					'longitude'  => $itemMeta['map']['longitude'],
					'swheading'  => $itemMeta['map']['swheading'],
					'swpitch'    => $itemMeta['map']['swpitch'],
					'swzoom'     => $itemMeta['map']['swzoom'],
					'streetview' => $itemMeta['map']['streetview'],
				);
			}
			return $itemMeta['map']['address'];
		} else {
			if ($all) {
				return array(
					'address'   => $meta->map['address'],
					'latitude'  => $meta->map['latitude'],
					'longitude' => $meta->map['longitude'],
					'swheading' => $meta->map['swheading'],
					'swpitch' => $meta->map['swpitch'],
					'swzoom' => $meta->map['swzoom'],
					'streetview' => $meta->map['streetview'],
				);
			}
			return $meta->map['address'];
		}
	}



	public static function isPointInRadius($radiusInMeters, $cenLat, $cenLng, $lat, $lng)
	{
		$radiusInMeters = floatval($radiusInMeters);
		$cenLat = floatval($cenLat);
		$cenLng = floatval($cenLng);
		$lat = floatval($lat);
		$lng = floatval($lng);
		$distance = ( 6371 * acos( cos( deg2rad($cenLat) ) * cos( deg2rad( $lat ) ) * cos( deg2rad( $lng ) - deg2rad($cenLng) ) + sin( deg2rad($cenLat) ) * sin( deg2rad( $lat ) ) ) );
		if(floatval($distance*1000) <= $radiusInMeters){
			return true;
		} else {
			return false;
		}
	}



	public static function getNextDate($dates, $from)
	{
		if (empty($dates) || empty($from)) return array();

		$now = new DateTime($from);
		$nowTimestamp = ($now->getTimeStamp());

		foreach ($dates as $date) {
			$newDate = new DateTime($date);

			if ($newDate > $now) {
				return $date;
			}
		}
		return array();
	}

}
