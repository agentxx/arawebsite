<?php

namespace Ait\SystemApi;


class Client
{
	const VERSION = '5.1.1';

	protected $apiBaseUrl = 'https://system.ait-themes.club/api/5.0';

	protected static $instance;

	protected $resources = array();

	protected $isDebugMode = false;

	protected $updaterVersion = '';



	public static function instance()
	{
		if(is_null(self::$instance)){
			self::$instance = new self;
		}

		return self::$instance;
	}



	public function baseUrl($url = '')
	{
		if(!$url){
			return $this->apiBaseUrl;
		}else{
			$this->apiBaseUrl = $url;
		}
	}



	public function updaterVersion($version = '')
	{
		if(!$version){
			return $this->updaterVersion;
		}else{
			$this->updaterVersion = $version;
		}
	}



	public function debugMode($isDebugMode)
	{
		$this->isDebugMode = $isDebugMode;
	}



	public function request($endpointOrUrl, $args = array())
	{
		$transientKey = str_replace('.', '', sprintf('ait_api_%s_%s_%s', $this->updaterVersion(), self::VERSION, substr(md5($endpointOrUrl), 0, 15)));

		include ABSPATH . WPINC . '/version.php'; // include an unmodified $wp_version

		$updaterVersion = $this->updaterVersion() ? sprintf("AitUpdater/%s;", $this->updaterVersion()) : '';

		$defaultArgs = array(
			'method' => 'GET',
			'_use_cache' => true,
			'_as_array' => false,
			'cache_expiration' => HOUR_IN_SECONDS,
			'timeout' => (defined('DOING_CRON') && DOING_CRON) ? 40 : 8,
			'user-agent' => sprintf('WordPress/%s; SystemApiClient/%s; %s %s', $wp_version,  self::VERSION, $updaterVersion, get_bloginfo('url')),
		);

		$args = wp_parse_args($args, $defaultArgs);

		if(!$this->isDebugMode and $args['_use_cache']){
			$cached = get_site_transient($transientKey);
			if($cached !== false){
				return Response::createFromCache($cached);
			}
		}

		if(strncmp($endpointOrUrl, 'http', 4) === 0){ // starts with
			$url = $endpointOrUrl;
		}else{
			$url = $this->apiBaseUrl . $endpointOrUrl;
		}

		$method = strtolower($args['method']);
		$remoteFn = "wp_remote_{$args['method']}";
		$wpResponse = $remoteFn($url, $args);

		$apiResponse = Response::createFromWpResponse($wpResponse, $endpointOrUrl, $args);

		if($apiResponse->isSuccessful() and !$this->isDebugMode and $args['_use_cache']){
			set_site_transient($transientKey, $apiResponse->toCache(), $args['cache_expiration']);
		}

		return $apiResponse;
	}



	public function get($endpoint, $args = array())
	{
		$args['method'] = 'GET';
		return $this->request($endpoint, $args);
	}



	public function post($endpoint, $args = array())
	{
		$args['method'] = 'POST';
		return $this->request($endpoint, $args);
	}



	protected function resource($resource)
	{
		$resource = ucfirst(strtolower($resource));
		if(!isset($this->resources[$resource])){
			$className = '\Ait\SystemApi\Resources\\' . $resource;
			$r = new $className();
			$r->api = $this;
			$this->resources[$resource] = $r;
		}

		return $this->resources[$resource];
	}



	public function __get($resource)
	{
		return $this->resource($resource);
	}
}
