<?php


class AitSysInfoReporter
{

	protected $columnWidth = 30;



	protected function getInfo()
	{
		global $wp_version, $wp_db_version, $blog_id, $table_prefix;

		return array(
			array(
				'AIT SysInfo'  => 'v' . AIT_SYSINFO_VERSION,
				'Generated At' => $this->generatedAt(),
			),
			array(
				'WordPress Version'    => $wp_version,
				'WordPress DB Version' => $wp_db_version,
				'PHP Version'          => PHP_VERSION,
				'MySQL Version'        => $this->getMySqlVersion(),
				'Web Server'           => $_SERVER['SERVER_SOFTWARE'],
				'Web Server Protocol'  => wp_get_server_protocol(),
				'Web Server HTTPS'     => $this->yesNo(is_ssl()),
			),
			array(
				'WordPress URL' => site_url(),
				'Home URL'      => home_url(),
			),
			array(
				'WP Locale'       => get_locale(),
				'WP Table Prefix' => $table_prefix,
				'WP Date Format'  => get_option('date_format'),
				'WP Time Format'  => get_option('time_format'),
				'WP Time Zone'    => get_option('timezone_string') ? get_option('timezone_string') : 'UTC',
				'WP Time'         => date_i18n('Y-m-d H:i:s'),
			),
			array(
				'Active Theme' => $this->getActiveTheme(),
			),
			array(
				'Active Plugins' => $this->getActivePlugins(),
			),
			array(
				'Active Network Plugins' => $this->getActiveNetworkPlugins(),
			),
			array(
				'Active MU Plugins' => $this->getActiveMuPlugins(),
			),
			array(
				'Active Dropins' => $this->getActiveDropins(),
			),
			array(
				'Updates' => $this->getUpdates(),
			),
			array(
				'Content Dir'          => WP_CONTENT_DIR,
				'Content URL'          => WP_CONTENT_URL,
			),
			array(
				'Plugins Dir'          => WP_PLUGIN_DIR,
				'Plugins URL'          => WP_PLUGIN_URL,
			),
			array(
				'Languages Dir'        => WP_LANG_DIR,
			),
			array(
				'WPMU Plugin Dir'      => WPMU_PLUGIN_DIR,
				'WPMU Plugin URL'      => WPMU_PLUGIN_URL,
			),
			array(
				'Uploads Dir'          => $this->getUploadsDir(),
				'Uploads Dir Writable' => $this->yesNo(is_writable($this->getUploadsDir())),
			),
			array(
				'Temp dir'             => get_temp_dir(),
			),
			array(
				'Current User Roles'    => implode(', ', wp_get_current_user()->roles),
			),
			array(
				'Current Site Name'    => get_option('blogname'),
				'Current Site ID'      => get_current_blog_id(),
				'Multi Site Active'    => $this->yesNo(is_multisite()),
				'SUBDOMAIN_INSTALL'    => $this->isDefinedAsBool('SUBDOMAIN_INSTALL'),
				'DOMAIN_CURRENT_SITE'  => $this->isDefinedAsValue('DOMAIN_CURRENT_SITE'),
				'PATH_CURRENT_SITE'    => $this->isDefinedAsValue('PATH_CURRENT_SITE'),
				'SITE_ID_CURRENT_SITE' => $this->isDefinedAsValue('SITE_ID_CURRENT_SITE'),
				'BLOG_ID_CURRENT_SITE' => $this->isDefinedAsValue('BLOG_ID_CURRENT_SITE'),
			),
			array(
				'WP_MEMORY_LIMIT'     => $this->isDefinedAsValue('WP_MEMORY_LIMIT'),
				'WP_MAX_MEMORY_LIMIT' => $this->isDefinedAsValue('WP_MAX_MEMORY_LIMIT'),
			),
			array(
				'WP_DEBUG'          => $this->isDefinedAsBool('WP_DEBUG'),
				'WP_DEBUG_LOG'      => $this->isDefinedAsBool('WP_DEBUG_LOG'),
				'WP_DEBUG_DISPLAY'  => $this->isDefinedAsBool('WP_DEBUG_DISPLAY'),
				'MEDIA_TRASH'       => $this->isDefinedAsBool('MEDIA_TRASH'),
				'SCRIPT_DEBUG'      => $this->isDefinedAsBool('SCRIPT_DEBUG'),
				'SAVEQUERIES'       => $this->isDefinedAsBool('SAVEQUERIES'),
				'FORCE_SSL_ADMIN'   => $this->isDefinedAsBool('FORCE_SSL_ADMIN'),
				'FORCE_SSL_LOGIN'   => $this->isDefinedAsBool('FORCE_SSL_LOGIN'),
				'AUTOSAVE_INTERVAL' => $this->isDefinedAsValue('AUTOSAVE_INTERVAL'),
				'WP_POST_REVISIONS' => $this->isDefinedAsValue('WP_POST_REVISIONS'),
				'COOKIE_DOMAIN'     => $this->isDefinedAsValue('COOKIE_DOMAIN'),
				'WP_DEFAULT_THEME'  => $this->isDefinedAsValue('WP_DEFAULT_THEME'),
			),
			array(
				'DB_CHARSET' => DB_CHARSET,
				'DB_COLLATE' => DB_COLLATE,
			),
			array(
				'Operating System' => $this->getBrowser('platform'),
				'Browser'          => $this->getBrowser('name', 'version'),
				'User Agent'       => $this->getBrowser('user_agent'),
			),
			array(
				'PHP cURL Support'    => $this->yesNo(function_exists('curl_init')),
				'PHP GD Support'      => $this->yesNo(function_exists('gd_info')),
				'PHP Memory Limit'    => ini_get('memory_limit'),
				'PHP Memory Usage'    => $this->getMemoryUsage(),
				'PHP Max Input Vars'  => ini_get('max_input_vars'),
				'PHP Post Max Size'   => ini_get('post_max_size'),
				'PHP Upload Max Size' => ini_get('upload_max_filesize'),
				'PHP Display Errors'  => $this->yesNo(ini_get('display_errors')),
				'PHP Error Reporting' => $this->errorCode2string(ini_get('error_reporting')),
			),
			array(
				"Debug Log" => $this->getDebugLog(),
			),
		);
	}



	public static function report()
	{
		$hasSkeleton = defined('AIT_SKELETON_VERSION');
		$class = $hasSkeleton ? 'ait-button positive uppercase' : 'button button-default';
		$reporter = new self;
		?>
			<div class="ait-backup-action">
				<a href="#" class="<?php echo $class ?>" id="ait-sysinfo-copy"><?php _e('Copy to clipboard', 'ait-sysinfo') ?></a>
			</div>
			<br>

			<div class='ait-opt-container ait-opt-multiline-code-main'>
				<div class='ait-opt-wrap'>
					<div class='ait-opt ait-opt-multiline-code'>
						<div class='ait-opt-wrapper'>
							<div id="ait-sys-info-report">
								<textarea readonly="readonly" wrap="off" id="ait-sysinfo-report"><?php echo $reporter->getReport(); ?></textarea>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php
	}



	public function getReport()
	{
		$report = '';

		foreach($this->getInfo() as $group){
			foreach($group as $label => $value){
				$report .= $this->formatInfo($group, $label, $value);
			}
			$report .= "\n";
		}

		return $report;
	}



	protected function formatInfo($group, $label, $value)
	{
		$return = '';

		$label = str_pad($label . ':', $this->columnWidth);

		if(!is_array($value)){
			$return .= "$label$value\n";
		}else{
			$return .= $label;

			if(!empty($value)){
				$i = 0;
				foreach($value as $lbl => $vl){
					if(is_array($vl)){
						$padding = '';
						if($i !== 0) $padding = str_repeat(' ', $this->columnWidth);
						$i++;

						$return .= $padding . $lbl . "\n";
						foreach($vl as $v){
							if($v){
								$return .= str_repeat(' ', $this->columnWidth) . " - $v\n";
							}
						}
					}else{
						$return .= "$v\n";
					}
				}
			}else{
				$return .= "None\n";
			}
		}

		return $return;
	}



	protected function getActiveTheme()
	{
		$t = wp_get_theme();
		$parent = $t->parent();

		if($parent){
			return array(
				"{$t->Name} v{$t->version} (child theme)" => array(
					"Author: {$t->get('Author')}",
					"Dirname: " . basename($t->stylesheet_dir),
				),
				"{$parent->Name} v{$parent->version} (parent theme)" => array(
					"Author: {$parent->get('Author')}",
					"Dirname: " . basename($parent->template_dir),
				),
			);
		}else{
			return array(
				"{$t->Name} v{$t->version}" => array(
					"Author: {$t->get('Author')}",
					"Dirname: " . basename($t->template_dir),
				),
			);
		}
	}



	protected function getActivePlugins()
	{
		$return = array();
		$plugins = get_plugins();
		$active = get_option('active_plugins', array());

		foreach($plugins as $slug => $plugin){
			if(in_array($slug, $active)){
				$return["{$plugin['Name']} v{$plugin['Version']}"] = array();
			}
		}

		return $return;
	}



	protected function getActiveNetworkPlugins()
	{
		$return = array();
		$plugins = get_plugins();
		$network = array_keys(get_site_option('active_sitewide_plugins', array()));

		foreach($plugins as $slug => $plugin){
			if(in_array($slug, $network)){
				$return["{$plugin['Name']} v{$plugin['Version']}"] = array();
			}
		}

		return $return;
	}



	protected function getActiveMuPlugins()
	{
		$return = array();
		$mu = get_mu_plugins();

		foreach($mu as $slug => $plugin){
			$v = $plugin['Version'] ? ' v' . $plugin['Version'] : '';
			$return["{$plugin['Name']}$v"] = array(
				"Filename: $slug",
				$plugin['Author'] ? "Author: {$plugin['Author']}" : null,
				$plugin['PluginURI'] ? "Plugin URL: {$plugin['PluginURI']}" : null,
			);
		}

		return $return;
	}



	protected function getActiveDropins()
	{
		$return = array();
		$dropins = get_dropins();

		foreach($dropins as $slug => $plugin){
			$v = $plugin['Version'] ? ' v' . $plugin['Version'] : '';
			$return["{$plugin['Name']}$v"] = array(
				"Filename: $slug",
				$plugin['Author'] ? "Author: {$plugin['Author']}" : null,
				$plugin['PluginURI'] ? "Plugin URL: {$plugin['PluginURI']}" : null,
			);
		}

		return $return;
	}



	protected function getBrowser()
	{
		// http://www.php.net/manual/en/function.get-browser.php#101125.
		// Cleaned up a bit, but overall it's the same.

		$args = func_get_args();

		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		$browserName = 'Unknown';
		$platform = 'Unknown';
		$version= "";

		// First get the platform
		if(preg_match('/linux/i', $userAgent)){
			$platform = 'Linux';
		}elseif(preg_match('/macintosh|mac os x/i', $userAgent)){
			$platform = 'Mac';
		}elseif(preg_match('/windows|win32/i', $userAgent)){
			$platform = 'Windows';
		}

		// Next get the name of the user agent yes seperately and for good reason
		if(preg_match('/MSIE/i', $userAgent) && !preg_match('/Opera/i', $userAgent)){
			$browserName = 'Internet Explorer';
			$browserNameShort = "MSIE";
		}elseif(preg_match('/Firefox/i', $userAgent)){
			$browserName = 'Mozilla Firefox';
			$browserNameShort = "Firefox";
		}elseif(preg_match('/Chrome/i', $userAgent)){
			$browserName = 'Google Chrome';
			$browserNameShort = "Chrome";
		}elseif(preg_match('/Safari/i', $userAgent)){
			$browserName = 'Apple Safari';
			$browserNameShort = "Safari";
		}elseif(preg_match('/Opera/i', $userAgent)){
			$browserName = 'Opera';
			$browserNameShort = "Opera";
		}elseif(preg_match('/Netscape/i', $userAgent)){
			$browserName = 'Netscape';
			$browserNameShort = "Netscape";
		}

		// Finally get the correct version number
		$known = array('Version', $browserNameShort, 'other');
		$pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
		if(!preg_match_all($pattern, $userAgent, $matches)){
			// We have no matching number just continue
		}

		// See how many we have
		$i = count($matches['browser']);
		if($i != 1){
			// We will have two since we are not using 'other' argument yet
			// See if version is before or after the name
			if(strripos($userAgent, "Version") < strripos($userAgent, $browserNameShort)){
				$version= $matches['version'][0];
			}else{
				$version= $matches['version'][1];
			}
		}else{
			$version= $matches['version'][0];
		}

		// Check if we have a number
		if($version == null || $version == ""){ $version = "?"; }

		$result = array(
			'user_agent' => $userAgent,
			'name'       => $browserName,
			'version'    => $version,
			'platform'   => $platform,
			'pattern'    => $pattern,
		);

		if(empty($args)){
			return $result;
		}else{
			$return = '';
			foreach($args as $arg){
				$return .= $result[$arg] . ' ';
			}

			return trim($return);
		}
	}



	protected function getMySqlVersion()
	{
		global $wpdb;
		return $wpdb->use_mysqli ? mysqli_get_server_info($wpdb->dbh) : mysql_get_server_info($wpdb->dbh);
	}



	protected function getUploadsDir()
	{
		$uploadDir = wp_upload_dir(null, false, false);
		return $uploadDir['basedir'];
	}



	protected function getUpdates()
	{
		$return = array();

		$themes = get_site_transient('update_themes');
		$plugins = get_site_transient('update_plugins');
		$core = get_site_transient('update_core');

		if($themes and !empty($themes->response)){
			foreach($themes->response as $slug => $theme){
				$return["{$theme['theme']} v{$theme['new_version']}"] = array();
			}
		}

		if($plugins and !empty($plugins->response)){
			foreach($plugins->response as $slug => $plugin){
				$return[dirname($plugin->plugin) . " v{$plugin->new_version}"] = array();
			}
		}

		if($core and !empty($core->response)){
			foreach($core->updates as $update){
				if($update->response !== 'latest'){
					$return["WordPress v{$update->version} {$update->response} {$update->locale}"] = array();
				}
			}
		}

		return $return;
	}



	protected function getMemoryUsage()
	{
		$value = round(memory_get_usage() / 1024 / 1024, 2);
		$percentage = round($value / ini_get('memory_limit') * 100, 0);
		return sprintf("%dM (%d%%)", $value, $percentage);
	}



	protected function getDebugLog()
	{
		$log = WP_CONTENT_DIR . '/debug.log';

		if(file_exists($log)){
			$r = WP_CONTENT_URL . '/debug.log' . "\n";
			$r .= "\n" . $this->tail($log, 50);
			return $r;
		}

		return 'None';
	}



	protected function errorCode2string($value)
	{
		$levelNames = array(
			E_ERROR           => 'E_ERROR',
			E_WARNING         => 'E_WARNING',
			E_PARSE           => 'E_PARSE',
			E_NOTICE          => 'E_NOTICE',
			E_CORE_ERROR      => 'E_CORE_ERROR',
			E_CORE_WARNING    => 'E_CORE_WARNING',
			E_COMPILE_ERROR   => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING => 'E_COMPILE_WARNING',
			E_USER_ERROR      => 'E_USER_ERROR',
			E_USER_WARNING    => 'E_USER_WARNING',
			E_USER_NOTICE     => 'E_USER_NOTICE'
		);

		if(defined('E_STRICT')) $levelNames[E_STRICT] = 'E_STRICT';
		$levels=array();

		if(($value & E_ALL) == E_ALL){
			$levels[] = 'E_ALL';
			$value &= ~E_ALL;
		}

		foreach($levelNames as $level => $name){
			if(($value & $level) == $level) $levels[] = $name;
		}

		return implode(' | ', $levels);
	}



	protected function isDefinedAsValue($constToTest)
	{
		return defined($constToTest) ? (constant($constToTest) ? constant($constToTest) : 'Disabled') : 'Not set';
	}



	protected function isDefinedAsBool($constToTest)
	{
		return defined($constToTest) ? (constant($constToTest) ? 'Enabled' : 'Disabled') : 'Not set';
	}



	protected function yesNo($test)
	{
		return $test ? 'Yes' : 'No';
	}



	protected function generatedAt()
	{
		$s = '';

		$old = date_default_timezone_get();
		date_default_timezone_set('Europe/Prague');
		$savingtime = date('I');
		date_default_timezone_set($old);
		$aitTime = time() + (($savingtime ? 2 : 1) * HOUR_IN_SECONDS);

		$s .= gmdate('Y-m-d H:i:s', current_time('timestamp')) . " (website time)";

		$s .= "\n" . str_repeat(' ', $this->columnWidth) . gmdate('Y-m-d H:i:s', $aitTime) . " (AIT time)";
		return $s;
	}



	protected function tail($filepath, $lines = 1, $adaptive = true)
	{
		// Open file
		$f = @fopen($filepath, "rb");
		if ($f === false) return false;

		// Sets buffer size
		if (!$adaptive) $buffer = 4096;
		else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

		// Jump to last character
		fseek($f, -1, SEEK_END);

		// Read it and adjust line number if necessary
		// (Otherwise the result would be wrong if file doesn't end with a blank line)
		if (fread($f, 1) != "\n") $lines -= 1;

		// Start reading
		$output = '';
		$chunk = '';

		// While we would like more
		while(ftell($f) > 0 && $lines >= 0){

			// Figure out how far back we should jump
			$seek = min(ftell($f), $buffer);

			// Do the jump (backwards, relative to where we are)
			fseek($f, -$seek, SEEK_CUR);

			// Read a chunk and prepend it to our output
			$output = ($chunk = fread($f, $seek)) . $output;

			// Jump back to where we started reading
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

			// Decrease our line counter
			$lines -= substr_count($chunk, "\n");

		}

		// While we have too many lines
		// (Because of buffer size we might have read too many)
		while ($lines++ < 0) {
			// Find first newline and remove all text before that
			$output = substr($output, strpos($output, "\n") + 1);
		}

		// Close file and return
		fclose($f);
		return trim($output);
	}
}