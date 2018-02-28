<?php


class AitEventsProSettingsAdminPage
{
	protected $params = array();

	public function run($params)
	{
		$this->params = $params;

		add_filter('ait-admin-config', array($this, 'addAdminPage'), 10, 2);

		add_filter('ait-main-config-files', array($this, 'addAdminPageMainConfig'));

		add_filter('ait-config-types', array($this, 'addMainConfigType'));

		// custom actions depending on plugin
		add_action('ait-save-options', array($this, 'saveCustomOptions'), 10, 3);
	}



	public function addAdminPage($adminConfig, $group)
	{
		if ($group && $group == 'pages') {
			foreach ($adminConfig['pages'] as &$page) {
				if (isset($page['slug']) && $page['slug'] == 'theme-options') {
					$page['sub'][] = array(
						'type'           => 'plugin',
						'pluginCodename' => $this->params['pluginCodename'],
						'slug'           => $this->params['pageSlug'],
						'menu-title'     => $this->params['menuTitle'],
					);
					return $adminConfig;
				}
			}
		}
		return $adminConfig;
	}



	public function addAdminPageMainConfig($configFiles)
	{
		$configFiles[$this->params['pluginCodename']] = $this->params['config'];
		return $configFiles;
	}



	public function addMainConfigType($configTypes)
	{
		$configTypes[] = $this->params['pluginCodename'];
		return $configTypes;
	}


	/****************** HELPERS *****************/
	/******* DO NOT COPY TO OTHER PLUGINS *******/

	// save custom options for plugin because some themes already use old format of plugin options
	public function saveCustomOptions($data, $optionsKeys, $oid)
	{
		$optionKey = $this->params['pluginCodename'];
		$optionKey = aitOptions()->getOptionKey($optionKey);
		if(empty($data[$optionKey]))
			return;

		$options = array();
		foreach($data[$optionKey] as $key => $section) {
			$options = array_merge($options, $section);
		}
		update_option('ait_events_pro_options', $options);
	}
}
