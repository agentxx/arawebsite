<?php

namespace Ait\SystemApi;


class Response
{

	protected $isWpError = false;
	protected $wpError;
	protected $headers = array();
	protected $body = '';
	protected $code = 0;

	protected $endpoint = '';
	protected $requestArgs = array();
	protected $isDownloadRequest = false;



	public static function createFromWpResponse($response, $endpoint, $requestArgs = array())
	{
		return new self($response, $endpoint, $requestArgs);
	}



	public static function createFromCache($array)
	{
		return new self($array, 'create_from_cache');
	}



	/**
	 * @param \WP_Error|array $response
	 */
	protected function __construct($response = null, $endpoint = '', $requestArgs = array())
	{
		$this->isWpError = is_wp_error($response);
		$this->wpError = $this->isWpError ? $response : null;

		if(!$this->isWpError){
			if($endpoint === 'create_from_cache'){
				$this->fromCache($response);
			}else{
				$headers = wp_remote_retrieve_headers($response);
				$this->headers = (is_object($headers) and method_exists($headers, 'getAll')) ? $headers->getAll() : $headers;
				$this->body = wp_remote_retrieve_body($response);
				$this->code = wp_remote_retrieve_response_code($response);

				$this->endpoint = $endpoint;
				$this->requestArgs = $requestArgs;
				$this->isDownloadRequest = (isset($requestArgs['stream']) and isset($requestArgs['filename']));
			}
		}
	}



	/**
	 * Flag wether request was successful
	 * @return boolean
	 */
	public function isSuccessful()
	{
		if($this->isWpError){
			return false;
		}elseif(!$this->code(200)){
			return false;
		}
		return true;
	}



	public function data()
	{
		if($this->isSuccessful() and $this->body()){
			$body = @json_decode($this->body());
			if(is_object($body)){
				if($this->requestArgs['_as_array'] === true){
					return (array) $body->data;
				}else{
					return $body->data;
				}
			}else{
				return array();
			}
		}else{
			return array();
		}
	}




	public function error()
	{
		if($this->isWpError){
			return $this->wpError;
		}

		if(!$this->code(200)){
			if($this->isDownloadRequest){
				$msg = base64_decode($this->header('ait-api-download-error'));
				return new \WP_Error('ait_api_download_error', $msg);
			}else{
				$json = @json_decode($this->body());
				if(is_object($json)){
					return new \WP_Error('ait_api_error', $json->error);
				}else{ // server did not return JSON string but some crap, maybe 500 server interal error message
					error_log('Server response is not valid JSON. It is: ' . $this->body());
					return new \WP_Error('ait_api_error', 'Can not fulfil the request. Reaseon: Server response is not valid JSON.');
				}
			}
		}

		return null;
	}



	public function code($codeToCheck = null)
	{
		if($codeToCheck){
			return ($this->code == $codeToCheck);
		}
		return $this->code;
	}



	public function body()
	{
		return $this->body;
	}



	public function headers()
	{
		return $this->headers;
	}



	public function header($header)
	{
		return isset($this->headers[$header]) ? $this->headers[$header] : '';
	}



	public function endpoint()
	{
		return $this->endpoint;
	}



	public function requestArgs()
	{
		return $this->requestArgs;
	}



	public function toCache()
	{
	    return array(
			'isWpError'         => $this->isWpError,
			'wpError'           => $this->wpError,
			'headers'           => $this->headers,
			'body'              => $this->body,
			'code'              => $this->code,
			'endpoint'          => $this->endpoint,
			'requestArgs'       => $this->requestArgs,
			'isDownloadRequest' => $this->isDownloadRequest,
	    );
	}



	protected function fromCache($array)
	{
		foreach($array as $prop => $val){
			if(property_exists($this, $prop)){
				$this->{$prop} = $val;
			}
		}
	}
}
