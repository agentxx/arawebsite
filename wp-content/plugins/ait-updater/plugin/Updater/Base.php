<?php

namespace Ait\Updater;



abstract class Base
{
	protected $updater;

	protected $errors = array();

	private static $instance;



	public function run($updater)
	{
		$this->updater = $updater;
		add_action('plugins_loaded', array($this, 'init'));

		$hook = is_multisite() ? 'network_admin_notices' : 'admin_notices';
		add_action($hook, array($this, 'onAdminNotices'));
	}



	public static function instance()
	{
		$class = get_called_class();
		if(!isset(self::$instance[$class])){
			self::$instance[$class] = new static;
		}

		return self::$instance[$class];
	}



	public function onAdminNotices()
	{
		$this->displayAdminNotices($this->errors);
	}



	protected function displayAdminNotices(&$errors)
	{
		if($errors){
			$msgs = array();
			foreach($errors as $errorMsg){
				$msgs[] = "<p>{$errorMsg}</p>";
			}
			echo '<div class="error">' . implode('', $msgs) . '</div>';
			$errors = array();
		}
	}



	protected function createTempFilePlaceholder($codename)
	{
		$tmpfname = wp_tempnam($codename);

		if(!$tmpfname){
			return new \WP_Error('http_no_file', __('Could not create temporary file.', 'ait-updater'));
		}

		return $tmpfname;
	}



	public function addError($errorMsg)
	{
		$this->errors[] = $errorMsg;
	}



	public function getErrors()
	{
		return $this->errors;
	}



	public function hasErrors()
	{
		return !empty($this->errors);
	}
}