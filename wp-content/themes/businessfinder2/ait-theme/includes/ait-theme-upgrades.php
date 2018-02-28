<?php

// define('AIT_UPGRADER_PREVIOUS_THEME_VERSION', '1.49');

add_action('ait-theme-upgrade', function($upgrader){
	if(version_compare($upgrader->getParentThemeVersion(), '2.0', '<')){
		$upgradeThemeFn = function(){
			$errors = array();

			// do the logic here
			global $wpdb;

			// if this upgrade failed before there are already some items migrated
			$migratedItems = (int)get_option('ait-migrated-gps-items', 0);

			// count is set to 999999 because wp does not support -1 with offset
			$items = get_posts(array(
				"post_type" => 'ait-item',
				"posts_per_page" => 999999,
				"offset" => $migratedItems,
				"fields" => 'ids'
			));

			// prepare sql value of listed items
			$itemsList = implode( ", ", esc_sql( $items ) );

			// get all metadata of listed items
			$sql = "
				SELECT $wpdb->postmeta.post_id, $wpdb->postmeta.meta_value
					FROM $wpdb->postmeta

					WHERE 1=1
						AND ($wpdb->postmeta.post_id IN ({$itemsList}) )
						AND ($wpdb->postmeta.meta_key = '_ait-item_item-data')"	;
			$data = $wpdb->get_results( $sql, OBJECT_K );

			$meta = array();
			foreach($data as $itemId => $row) {
				$meta = unserialize($row->meta_value);
				if($meta == false){
					continue;
				}
				// save separately meta data for lat and long and update number of migrated items
				update_post_meta($itemId, 'ait-latitude', $meta['map']['latitude']);
				update_post_meta($itemId, 'ait-longitude', $meta['map']['longitude']);
				$migratedItems++;
				update_option('ait-migrated-gps-items', $migratedItems);
			}


			// script finished successfuly and we can remove helper option
			delete_option('ait-migrated-gps-items');
			// do the logic here

			return $errors;
		};
		$upgrader->addErrors($upgradeThemeFn());
	}
});
?>