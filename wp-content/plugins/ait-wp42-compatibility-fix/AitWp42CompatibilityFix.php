<?php


class AitWp42CompatibilityFix
{

	private static $instance;

	protected $splitTerms = array();
	protected $alreadyHandled = 'no';



	public static function getInstance()
	{
		if(!self::$instance){
			self::$instance = new self;
		}

		return self::$instance;
	}



	public static function run()
	{
		$instance = self::getInstance();
		add_action('after_setup_theme', array($instance, 'onAfterSetupTheme'), 15);
	}



	public function onAfterSetupTheme()
	{
		if(!defined('AIT_DEFAULT_OPTIONS_KEY')) return; // bail early if it's not AIT theme with Framework1

		$this->splitTerms = get_option('_split_terms', array());
		$this->alreadyHandled = get_option('_ait_wp42_compatibility_fix_split_shared_terms_handled', 'no');

		if(isset($_GET['ait-action']) and $_GET['ait-action'] === 'handle-split-shared-terms' and wp_verify_nonce($_GET['_wpnonce'], 'ait-handle-split-shared-terms')){
			$this->handleSplitSharedTerms();
			wp_redirect(admin_url());
		}

		if(function_exists('wp_get_split_terms')){ // only in wp 4.2+
			add_action('admin_notices', array($this, 'onAdminNotices'));
		}

		add_action('split_shared_term', array($this, 'onSplitSharedTerm'), 10, 4);
	}



	public function handleSplitSharedTerms()
	{
		foreach($this->splitTerms as $oldTermId => $taxonomies){
			foreach($taxonomies as $taxonomy => $newTermId){
				$this->onSplitSharedTerm($oldTermId, $newTermId, null, $taxonomy);
			}
		}

		update_option('_ait_wp42_compatibility_fix_split_shared_terms_handled', 'yes');
	}



	public function onSplitSharedTerm($oldTermId, $newTermId, $termTaxonomyId, $taxonomy)
	{
		$results = get_option('_ait_wp42_compatibility_fix_split_shared_terms_results', array());

		$r = array();
		$r['option_keys']   = $this->updateThemeOptions($oldTermId, $newTermId, $termTaxonomyId, $taxonomy);
		$r['posts_ids']     = $this->updatePostMetaOptions($oldTermId, $newTermId, $termTaxonomyId, $taxonomy);
		$r['new_terms_ids'] = $this->updateTermMetaKey($oldTermId, $newTermId, $termTaxonomyId, $taxonomy);

		$results = array_merge_recursive($results, $r);

		update_option('_ait_wp42_compatibility_fix_split_shared_terms_results', $results); // for displaying results for user what was updated, maybe future use
	}



	public function onAdminNotices()
	{
		if($this->alreadyHandled === 'yes'){
			$msg = __('<strong>Successfully updated!</strong>');
			echo '<div class="notice notice-success"><p>' . $msg . '</p></div>';
			update_option('_ait_wp42_compatibility_fix_split_shared_terms_handled', ''); // reset
		}elseif($this->alreadyHandled === 'no'){
			$link = sprintf("<a href='%s'>%s</a>", wp_nonce_url(admin_url('?ait-action=handle-split-shared-terms'), 'ait-handle-split-shared-terms'), __('Update it now'));
			$msg = sprintf(__('<strong>WordPress 4.2 compatibility issue</strong>: There are some split shared terms, so theme options and other settings in this theme from AitThemes.com needs to be updated. %s'), $link);
			echo '<div class="notice notice-warning"><p>' . $msg . '</p></div>';
		}
	}



	public function updateThemeOptions($oldTermId, $newTermId, $termTaxonomyId, $taxonomy)
	{
		global $aitThemeConfig, $wpdb;

		$key = 'ait_' . THEME_CODE_NAME . '_options_';
		$preparedQuery = $wpdb->prepare("SELECT `option_name` FROM `{$wpdb->options}` WHERE `option_name` LIKE %s", $wpdb->esc_like($key) . '%');
		$results = $wpdb->get_results($preparedQuery);


		$justCategories = $this->getThemeOptionsOfTypes(array('dropdown-categories', 'dropdown-categories-posts', 'multiple-category-select'), $aitThemeConfig);

		$return = array();

		foreach($results as $row){
			$newThemeOptions = $previousOptions = get_option($row->option_name);

			foreach($previousOptions as $section => $options){
				foreach($options as $optKey => $optValue){
					if(isset($justCategories[$section][$optKey])){

						$fullTaxonomyName = "ait-" . $justCategories[$section][$optKey]['default'] . "-category";
						if($justCategories[$section][$optKey]['default'] === 'category'){
							$fullTaxonomyName = 'category';
						}elseif($justCategories[$section][$optKey]['type'] === 'multiple-category-select' and is_array($justCategories[$section][$optKey]['default'])){
							$fullTaxonomyName = 'category';
						}

						if($fullTaxonomyName !== $taxonomy) continue;

						if(!is_array($optValue) and $optValue == $oldTermId){
							$newThemeOptions[$section][$optKey] = $newTermId;
						}elseif(is_array($optValue)){
							foreach($optValue as $i => $id){
								if($id == $oldTermId){
									$newThemeOptions[$section][$optKey][$i] = $newTermId;
								}
							}
						}

					}
				}
			}

			if($previousOptions !== $newThemeOptions){
				$return[] = $row->option_name;
				update_option($row->option_name, $newThemeOptions);
			}

			return $return;
		}
	}



	public function updatePostMetaOptions($oldTermId, $newTermId, $termTaxonomyId, $taxonomy)
	{
		global $wpdb, $pageOptions;

		$return = array();

		foreach($pageOptions as $_ => $metabox){

			$justCategories = $this->getPostMetaOptionsOfTypes(array('dropdown-categories', 'dropdown-categories-posts', 'multiple-category-select'), $metabox->configData);

			$preparedQuery = $wpdb->prepare("SELECT * FROM `{$wpdb->postmeta}` WHERE `meta_key` = %s", $metabox->id);
			$results = $wpdb->get_results($preparedQuery);

			foreach($results as $row){
				$newOptions = $previousOptions = maybe_unserialize($row->meta_value);
				if(!is_array($newOptions)){
					$newOptions = $previousOptions = array();
				}

				foreach($previousOptions as $optKey => $optValue){
					if(isset($justCategories[$optKey])){

						$fullTaxonomyName = "ait-" . $justCategories[$optKey]['default'] . "-category";
						if($justCategories[$optKey]['default'] === 'category'){
							$fullTaxonomyName = 'category';
						}elseif($justCategories[$optKey]['type'] === 'multiple-category-select' and is_array($justCategories[$optKey]['default'])){
							$fullTaxonomyName = 'category';
						}

						if($fullTaxonomyName !== $taxonomy) continue;

						if(!is_array($optValue) and $optValue == $oldTermId){
							$newOptions[$optKey] = $newTermId;
						}elseif(is_array($optValue)){
							foreach($optValue as $i => $id){
								if($id == $oldTermId){
									$newOptions[$optKey][$i] = $newTermId;
								}
							}
						}
					}
				}

				if($newOptions !== $previousOptions){
					$return[] = $row->post_id;
					update_post_meta($row->post_id, $row->meta_key, $newOptions);
				}
			}

		}

		return $return;
	}



	public function updateTermMetaKey($oldTermId, $newTermId, $termTaxonomyId, $taxonomy)
	{
		global $wpdb;

		if(!in_array($taxonomy, array('ait-dir-item-category', 'ait-dir-item-location'))) return;

		$key = 'ait_dir_item_';
		$preparedQuery = $wpdb->prepare("SELECT `option_name`, `option_value` FROM `{$wpdb->options}` WHERE `option_name` LIKE %s", $wpdb->esc_like($key) . '%');
		$results = $wpdb->get_results($preparedQuery);

		$return = array();

		foreach($results as $row){
			$oldOptionName = $row->option_name;
			if(@preg_match('/\d+/i', $oldOptionName, $result)){
				$termIdFromKey = $result[0];
				if($termIdFromKey == $oldTermId){
					$newOptionName = str_replace($oldTermId, $newTermId, $oldOptionName);
					update_option($newOptionName, maybe_unserialize($row->option_value));
					$return[] = $newTermId;
				}
			}
		}

		return $return;
	}



	public function getThemeOptionsOfTypes($types, $config)
	{
		$settings = array();
		$return = array();

		// simplify array
		foreach($config as $menuKey => $page){
			if(isset($page['tabs'])){
				foreach($page['tabs'] as $tabKey => $tabPage){
					unset($settings[$menuKey]);
					$settings[$tabKey] = $tabPage['options'];
				}
			}
		}

		foreach($settings as $section => $options){
			foreach($options as $key => $value){
				if(is_string($value) and startsWith('section', $value)){
					continue;
				}

				if(in_array($value['type'], $types)){
					$return[$section][$key] = $value;
				}
			}
		}

		return $return;
	}



	public function getPostMetaOptionsOfTypes($types, $config)
	{
		$return = array();

		foreach($config as $key => $value){
			if(is_string($value) and startsWith('section', $value)){
				continue;
			}
			if(in_array($value['type'], $types)){
				$return[$key] = $value;
			}
		}

		return $return;
	}
}
