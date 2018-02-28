<?php

/*
 * manages plugin upgrades
 *
 */
class AitAdvancedSearchUpgrade {
	public $options;

	/*
	 * constructor
	 *
	 */
	public function __construct(&$options) {
		$this->options = &$options;
	}

	/*
	 * upgrades if possible otherwise die to avoid activation
	 *
	 */
	public function upgrade_at_activation() {
		if (!$this->can_upgrade()) {
			ob_start();
			die(ob_get_contents());
		}
	}

	/*
	 * upgrades if possible otherwise returns false to stop plugin loading
	 *
	 * @return bool true if upgrade is possible, false otherwise
	 */
	public function upgrade() {
		if (!$this->can_upgrade()) {
			return false;
		}
		return true;
	}


	/*
	 * check if we the previous version is not too old
	 * /!\ never start any upgrade before admin_init as it is likely to conflict with some other plugins
	 *
	 * @return bool true if upgrade is possible, false otherwise
	 */
	public function can_upgrade() {
		// add here custom conditions whether upgrade should run or not
		// if upgrade isn't allowed return false

		add_action('admin_init', array(&$this, '_upgrade'));
		return true;
	}



	/*
	 * upgrades the plugin depending on the previous version
	 *
	 */
	public function _upgrade() {
		foreach (array() as $version) {
			if (version_compare($this->options['version'], $version, '<')) {
				call_user_func(array(&$this, 'upgrade_' . str_replace('.', '_', $version)));
			}
		}

		$this->options['previous_version'] = $this->options['version']; // remember the previous version of plugin
		$this->options['version'] = AIT_ADVANCED_SEARCH_VERSION;
		update_option('ait-advanced-search-plugin', $this->options);
	}
}
