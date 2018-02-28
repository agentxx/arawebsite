<?php

/*
 * manages plugin upgrades
 *
 */
class AitEventsProUpgrade {
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
		// run upgrade only with current skeleton version which contains functionality for plugin pages
		$skeletonVersionOptionKey = "_ait_" . sanitize_key(get_stylesheet()) . "_skeleton_version";
		$skeletonVersion = get_option($skeletonVersionOptionKey, "");
		if (version_compare($skeletonVersion, '2.20.0', '<')) {
			return false;
		}

		add_action('admin_init', array(&$this, '_upgrade'));
		return true;
	}



	/*
	 * upgrades the plugin depending on the previous version
	 *
	 */
	public function _upgrade() {
		foreach (array('1.9', '1.14') as $version) {
			if (version_compare($this->options['version'], $version, '<')) {
				call_user_func(array(&$this, 'upgrade_' . str_replace('.', '_', $version)));
			}
		}

		$this->options['previous_version'] = $this->options['version']; // remember the previous version of plugin
		$this->options['version'] = AIT_EVENTS_PRO_VERSION;
		update_option('ait-events-pro-plugin', $this->options);
	}



	/*
	 * upgrades if the previous version is < 1.9
	 *
	 */
	protected function upgrade_1_9() {
		// check if database was already updated in previous versions of plugin
		$oldUpgrades = get_option('_ait_events_pro_plugin_upgrades', array());
		if(in_array('1.9', $oldUpgrades)) return;

		$args = array(
			'posts_per_page'   => -1,
			'post_type'        => 'ait-event-pro',
			'post_status'      => 'publish',
		);

		$events = get_posts( $args );
		global $wpdb;
		$table_name = $wpdb->prefix . 'ait_eventspro_dates';

		foreach($events as $event){
			// fix: in case upgrade run multiple times remove duplicate records
			$wpdb->delete( $table_name, array(
					'post_id' => $event->ID
				),
				array ('%s')
			);
			$meta = get_post_meta($event->ID, '_ait-event-pro_event-pro-data', true);
			if(isset($meta) && !empty($meta['dates'])){
				clean_post_cache( $event->ID );

				foreach ($meta['dates'] as $date) {
					$ts1 = strtotime($date['dateFrom']);
					$ts2 = strtotime($date['dateTo']);
					// ignore case when events ends before it starts
					if( $ts2 && $ts2 < $ts1 ) continue;

					$wpdb->insert(
						$table_name,
						array(
							'post_id' => $event->ID,
							'date_from' => $date['dateFrom'],
							'date_to' => empty($date['dateTo']) ? NULL : $date['dateTo'],
						)
					);
				}
			}
		}
		flush_rewrite_rules();
	}



	/*
	 * upgrades if the previous version is < 1.14
	 *
	 */
	protected function upgrade_1_14() {
		$optionKey = aitOptions()->getOptionKey('ait-events-pro');
		$oldOptions = get_option('ait_events_pro_options');
		$newOptions = aitOptions()->getOptionsByType('ait-events-pro');
		foreach ($newOptions as $groupKey => $group) {
			foreach ($group as $key => $value) {
				if (isset($oldOptions[$key])) {
					$newOptions[$groupKey][$key] = $oldOptions[$key];
				}
			}
		}
		update_option($optionKey, $newOptions);
	}
}
