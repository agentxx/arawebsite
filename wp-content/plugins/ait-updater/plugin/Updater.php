<?php

namespace Ait;

use Ait\SystemApi;
use Ait\Updater\Themes;
use Ait\Updater\Plugins;
use Ait\Updater\Detector;
use Ait\Updater\Settings;
use Ait\Updater\AddThemesTab;
use Ait\Updater\AddPluginsTab;



class Updater
{

	protected $paths;



	public static function run($pluginFile)
	{
		new self($pluginFile);
	}



	public function __construct($pluginFile)
	{
		$rootPath = dirname($pluginFile);
		$rootUrl = plugins_url('', $pluginFile);

		$uploadDir = wp_upload_dir();

		$this->paths = array(
			'path.pluginFile' => $pluginFile,
			'path.root'       => $rootPath,
			'path.src'        => "$rootPath/src",
			'path.libs'       => "$rootPath/libs",
			'path.backups'    => $uploadDir['basedir'] . '/backups',

			'url.root'        => $rootUrl,
			'url.backups'     => $uploadDir['baseurl'] . '/backups',
			'url.assets'      => "$rootUrl/assets",
		);


		add_action('plugins_loaded', array($this, 'onPluginsLoaded'));

		Plugins::instance()->run($this);
		Themes::instance()->run($this);

		Settings::run($this);

		AddPluginsTab::instance()->run($this);
		AddThemesTab::instance()->run($this);
	}



	public function onPluginsLoaded()
	{
		load_plugin_textdomain('ait-updater', false, basename($this->path('root')) . '/languages');

		include_once $this->path('libs') . '/ait-system-api-client/load.php';

		$this->maybeRunHttpApiDebug();
	}



	/**
	 * Gets credentials from username and api key
	 * @return string
	 */
	public function credentials()
	{
		if($u = Settings::get('username') and $k = Settings::get('api_key')){
			return array(
				'username' => $u,
				'api_key' => $k,
			);
		}

		return array();
	}



	public function credentialsForRequest()
	{
		return implode(':', $this->credentials());
	}



	protected function api()
	{
		$client = SystemApi\Client::instance();
		$client->updaterVersion(AIT_UPDATER_VERSION);
		$client->debugMode((defined('AIT_SYSTEM_API_DEBUG') and AIT_SYSTEM_API_DEBUG));

		if(defined('AIT_SYSTEM_API_TESTING_URL') and AIT_SYSTEM_API_TESTING_URL){
			$client->baseUrl(AIT_SYSTEM_API_TESTING_URL);
		}

		return $client;
	}



	public function doBackup($packageType, $codename)
	{
		$backupsDir = $this->path('backups');

		if(file_exists(!$backupsDir)){
			wp_mkdir_p($backupsDir);
			@file_put_contents("$backupsDir/index.php", "<?php\n// Silence is golden.");
		}

		$prefix = ($packageType == 'theme') ? 'ait-' : '';

		$zipfile = "$backupsDir/{$prefix}$codename-" . date('Y-m-d_H-i-s') . '.zip';

		$srcRoot = ($packageType == 'theme') ? get_theme_root($codename) : WP_PLUGIN_DIR;
		$src = "$srcRoot/$codename";

		if(file_exists($src)){
			$result = $this->makeBackupZip($src, $zipfile);
		}
	}



	protected function makeBackupZip($src, $zipfile)
	{
		if(!class_exists('\ZipArchive')) return false;

		$src = realpath($src);

		@ini_set('memory_limit', apply_filters('admin_memory_limit', WP_MAX_MEMORY_LIMIT));

		$zip = new \ZipArchive();
		$zip->open($zipfile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);


		// Create recursive directory iterator
		/** @var SplFileInfo[] $files */
		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src), \RecursiveIteratorIterator::LEAVES_ONLY);

		foreach ($files as $name => $file){
			// Skip directories (they would be added automatically)
			if (!$file->isDir()){
				// Get real and relative path for current file
				$filePath = $file->getRealPath();
				$relativePath = substr($filePath, strlen(dirname($src)) + 1);
				// Add current file to archive
				$zip->addFile($filePath, $relativePath);
			}
		}

		// Zip archive will be created only after closing object
		return $zip->close();
	}



	/* ************ helper methods ********** */


	public function __get($name)
	{
		if($name === 'api'){
			return $this->api();
		}
	}


	public function paths()
	{
		return apply_filters('ait-updater-get-paths', $this->paths);
	}



	public function path($location)
	{
		$paths = $this->paths();

		if(isset($paths["path.{$location}"])){
			return apply_filters('ait-updater-path', $paths["path.{$location}"], $location);
		}

		return '';
	}



	public function url($location)
	{
		$paths = $this->paths();

		if(isset($paths["url.{$location}"])){
			return apply_filters('ait-updater-url', $paths["url.{$location}"], $location);
		}

		return '';
	}



	protected function maybeRunHttpApiDebug()
	{
		if(defined('AIT_UPDATER_HTTP_API_DEBUG') and AIT_UPDATER_HTTP_API_DEBUG){
			if(defined('AIT_UPDATER_HTTP_API_DEBUG_LOG_FILE') and AIT_UPDATER_HTTP_API_DEBUG_LOG_FILE){
				if(file_exists(AIT_UPDATER_HTTP_API_DEBUG_LOG_FILE)){
					@unlink(AIT_UPDATER_HTTP_API_DEBUG_LOG_FILE);
				}
			}
			add_action('http_api_debug', array($this, 'onHttpApiDebugCallback'), 10, 5);
		}
	}



	public function onHttpApiDebugCallback($response, $context, $class, $args, $url)
	{
		ob_start();

		print_r($url);
		echo "\n------------------------------\n";
		print_r($args);
		echo "\n------------------------------\n";
		print_r($response);
		echo "\n------------------------------\n";
		print_r($class);
		echo "\n\n------------------------------------------------------------------------------------------\n\n";

		$dump = ob_get_clean();

		if(defined('AIT_UPDATER_HTTP_API_DEBUG_LOG_FILE') and AIT_UPDATER_HTTP_API_DEBUG_LOG_FILE){
			file_put_contents(AIT_UPDATER_HTTP_API_DEBUG_LOG_FILE, $dump, FILE_APPEND);
		}else{
			echo "<xmp>$dump</xmp>";
		}
	}

}
