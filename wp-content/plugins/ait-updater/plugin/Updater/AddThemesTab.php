<?php

namespace Ait\Updater;



class AddThemesTab
{

	protected static $instance;

	protected $updater;



	public function run($updater)
	{
		$this->updater = $updater;

		add_filter('themes_api', array($this, 'getAitThemes'), 10, 3); // query_themes
		add_filter('themes_api_result', array($this, 'searchWithAitThemes'), 10, 3); // query_themes - search
		add_filter('themes_api', array($this, 'getThemeInfoJustForThemeInstaller'), 10, 3); // theme_information

		add_action('admin_init', array($this, 'makeAitClubTabDefault'));
		add_filter('admin_url', array($this, 'makeAitClubTabDefault'), 10, 3);

		add_action('admin_print_styles-theme-install.php', array($this, 'printStyles'));
		add_action('admin_print_styles-update.php', array($this, 'printStyles'));
		add_action('admin_head-themes.php', array($this, 'printScripts'));
		add_action('admin_print_scripts', array($this, 'addTabViaScript'), 20); // 20 - after jQuery
	}



	public function addTabViaScript()
	{
		global $pagenow;
		if($pagenow !== 'theme-install.php') return;
		?>
		<script>
		jQuery(function(){
			var $filterLinks = jQuery('ul.filter-links');
			$filterLinks.prepend('<li><a href="#" data-sort="ait">AitThemes.club</a></li>');
		});

		</script>
		<?php
	}



	public function makeAitClubTabDefault()
	{
		if(current_filter() === 'admin_init'){ // "Add new" in network admin menu
			global $submenu;
			if(isset($submenu['themes.php'][10][2]) and $submenu['themes.php'][10][2] == 'theme-install.php'){
				$submenu['themes.php'][10][2] = 'theme-install.php?browse=ait';
			}
		}elseif(current_filter() === 'admin_url'){ // "Add new" button on single site
			$args = func_get_args();
			if($args[1] === 'theme-install.php'){
				return $args[0] . '?browse=ait';
			}
			return $args[0];
		}
	}



	public function getAitThemes($false, $action, $args)
	{
		if(!($action === 'query_themes' and isset($args->browse) and $args->browse === 'ait')) return $false;

		$numberOfThemes = 0;
		$themes = array();

		$defaultReturn = array(
			'info' => array(
				'page' => 1,
				'pages' => 1,
				'results' => $numberOfThemes,
			),
			'themes' => $themes,
		);

		if(isset($args->page)) return (object) $defaultReturn; // do not paginate

		$apiResponse = $this->updater->api->themes->all();

		if(!$apiResponse->isSuccessful()){
			return $apiResponse->error(); // wp_error;
		}

		$data = $apiResponse->data();

		$numberOfThemes = count($data);

		$installedThemes = search_theme_directories();

		foreach($data as $theme){
			if(isset($installedThemes[$theme->codename])) continue;

			$themes[] = (object) array(
				'name' => str_replace('&', ' and ', $theme->name),
				'slug' => $theme->codename,
				'version' => $theme->latestVersion->version,
				'preview_url' => $theme->previewUrl,
				'author' => 'AitThemes.club',
				'screenshot_url' => $theme->thumbnailUrl,
				'rating' => 0,
				'num_ratings' => 0,
				'downloaded' => 0,
				'last_updated' => $theme->latestVersion->releasedAt,
				'homepage' => $theme->themeUrl,
				'description' => $theme->description,
			);
		}

		$return = (object) array_merge($defaultReturn, array(
			'info' => array(
				'page' => 1,
				'pages' => 1,
				'results' => $numberOfThemes,
			),
			'themes' => $themes,
		));

		return $return;
	}



	public function searchWithAitThemes($res, $action, $args)
	{
		if(!($action === 'query_themes' and !empty($args->search))) return $res;

		$numberOfThemes = 0;
		$themes = array();

		$apiResponse = $this->updater->api->themes->all();

		if(!$apiResponse->isSuccessful()){
			return $apiResponse->error(); // wp_error;
		}

		$searchTerm = $args->search;

		$data = $apiResponse->data();

		$numberOfThemes = count($data);

		$installedThemes = search_theme_directories();

		foreach($data as $theme){
			if(isset($installedThemes[$theme->codename])) continue;

			if($this->search($theme->name, $searchTerm) or ($this->search($theme->description, $searchTerm))){
				$themes[] = (object) array(
					'name' => str_replace('&', ' and ', $theme->name),
					'slug' => $theme->codename,
					'version' => $theme->latestVersion->version,
					'preview_url' => $theme->previewUrl,
					'author' => 'AitThemes.club',
					'screenshot_url' => $theme->thumbnailUrl,
					'rating' => 0,
					'num_ratings' => 0,
					'downloaded' => 0,
					'last_updated' => $theme->latestVersion->releasedAt,
					'homepage' => $theme->themeUrl,
					'description' => $theme->description,
				);
			}
		}

		$numberOfThemes = count($themes);

		if($numberOfThemes == 0) return $res; // there are no ait themes which match search term

		if(!is_wp_error($res)){
			$res->themes = $themes + $res->themes;

			$res->info['results'] += $numberOfThemes;
			$res->info['pages'] = ceil($res->info['results'] / $args->per_page);
		}

		return $res;
	}



	public function getThemeInfoJustForThemeInstaller($false, $action, $args)
	{
		$apiResponse = $this->updater->api->themes->all(); // it will be from cache mostly

		if(!$apiResponse->isSuccessful()){
			return $apiResponse->error();
		}

		$data = $apiResponse->data();

		if( ! ($action === 'theme_information' and isset($args->slug) and isset($data[$args->slug])) ) return $false;

		foreach($data as $slug => $theme){
			if($slug === $args->slug){
				return (object) array(
					'name' => $theme->name,
					'version' => $theme->latestVersion->version,
					'download_link' => $args->slug, // with prefix
				);
			}
		}

		return new \WP_Error('themes_api_failed', sprintf(__('Can not get information about %s for installation', 'ait-updater'), $codename));
	}



	public function printStyles()
	{
		global $pagenow;
		?>

		<?php if($pagenow === 'theme-install.php'): ?>
			<style>
				a[data-sort="ait"] { font-weight: bold; }
			</style>
		<?php elseif($pagenow === 'update.php' and version_compare($GLOBALS['wp_version'], '4.6', '<' )): ?>
			<style>
				.ait-updater-purchase-message.notice.notice-warning { font-size: 120%; padding: 1em; }
			</style>
		<?php endif ?>

		<?php
	}



	public function printScripts()
	{
		global $pagenow;
		?>

		<?php if($pagenow === 'themes.php'): ?>
			<script>
				jQuery(function(){  // "Add new" button on multi-site
					var $a = jQuery('a[href="theme-install.php"]');
					$a.attr('href', $a.attr('href') + '?browse=ait');
				});
			</script>
		<?php endif ?>

		<?php
	}



	private function search($haystack, $needle)
	{
		return strpos(strtolower($haystack), strtolower($needle)) !== FALSE;
	}



	public static function instance()
	{
		if(!self::$instance){
			self::$instance = new self;
		}

		return self::$instance;
	}
}