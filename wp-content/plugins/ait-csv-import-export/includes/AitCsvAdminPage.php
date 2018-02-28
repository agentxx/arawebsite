<?php


class AitCsvAdminPage
{
	private static $instance;

	private $optionTitle;

	private $pluginFile;

	private $config;

	private $pageHookname;

	private $codeName = 'ait-csv-import-export';

	private $pageSlug = 'ait-csv-settings';

	public function run($pluginFile)
	{

		$this->optionTitle = __("CSV Import/Export", "ait-csv-import-export");

		$this->pluginFile = $pluginFile;

		$this->config = require_once dirname($this->pluginFile) . '/config/admin-config.php';

		add_action('admin_menu', array($this, 'adminMenu'));
		add_action('admin_init', array($this, 'onAdminInitCallback'));
	}



	public function adminMenu(){
		add_filter( 'ait-enqueue-admin-assets', function($return){
		    if (strpos(get_current_screen()->id,'ait_csv_options') !== false) {
		        return true;
		    }
		    return $return;
		});
		$this->pageHookname = add_submenu_page(
			'ait-theme-options',
			$this->optionTitle,
			$this->optionTitle,
			apply_filters('ait-import-export-menu-permission', 'edit_theme_options'),
			'ait_csv_options',
			array($this, 'adminPage')
		);

	}


	public function onAdminInitCallback()
	{
		// render import page
		add_settings_section(
			'import',
			__('Import from CSV', 'ait-csv-import-export'),
			'__return_false',
			$this->codeName.'_options'
		);

		add_settings_field(
			'import',
			"",
			array($this, 'importAdminPage'),
			$this->codeName.'_options',
			'import'
		);

		// render export page
		add_settings_section(
			'export',
			__('Export to CSV', 'ait-csv-import-export'),
			'__return_false',
			$this->codeName.'_options'
		);

		add_settings_field(
			'export',
			"",
			array($this, 'exportAdminPage'),
			$this->codeName.'_options',
			'export'
		);




	}

	public function adminPage(){
		echo "
		<script type='text/javascript'>
		jQuery(document).ready(function(){

			var context = jQuery('.theme-admin_page_ait_quick_comments_options');

			var select = context.find('.ait-opt-page-select .chosen-wrapper select');
			select.addClass('chosen').chosen();
			context.find('.ait-opt-color .ait-colorpicker').colorpicker();
			ait.admin.options.Ui.onoff(context);

			var currentPage = 'csv-import-export';
			var id = '#ait-' + currentPage;
			new ait.admin.Tabs(jQuery(id + '-tabs'), jQuery(id + '-panels'), 'ait-admin-' + currentPage + '-page');
		});

		</script>";
		echo '<div class="wrap">';
			echo '<div id="'.$this->codeName.'-page" class="ait-admin-page ait-options-layout">';
				echo '<div class="ait-admin-page-wrap">';

					/* Hack for WP notifications, all will be placed right after this h2 */
					echo '<h2 style="display: none;"></h2>';

					echo '<div class="ait-options-page-header">';
						echo '<h3 class="ait-options-header-title">'. $this->optionTitle .'</h3>';
						echo '<div class="ait-options-header-tools">';
							echo '<a class="ait-scroll-to-top"><i class="fa fa-chevron-up"></i></a>';
						echo '</div>';

						echo '<div class="ait-sticky-header">';
							echo '<h4 class="ait-sticky-header-title">'. $this->optionTitle .'<i class="fa fa-circle"></i><span class="subtitle"></span></h4>';
						echo '</div>';
					echo '</div>';

					echo '<div class="ait-options-page">';
						echo '<div class="ait-options-page-content">';

							echo '<div class="ait-options-sidebar">';
								echo '<div class="ait-options-sidebar-content">';
									echo '<ul id="'.$this->codeName.'-tabs" class="ait-options-tabs">';
										foreach($this->config['admin'] as $section => $settings){
											echo '<li id="'.$this->codeName.'-'.$section.'-panel-tab" class=""><a href="#'.$this->codeName.'-'.$section.'-panel">'.$settings['title'].'</a></li>';
										}
									echo '</ul>';
								echo '</div>';
							echo '</div>';

							echo '<div class="ait-options-content">';
								echo '<div class="ait-options-controls-container">';
									echo '<div id="'.$this->codeName.'-panels" class="ait-options-controls ait-options-panels">';
										// echo '<form action="options.php" method="post" name="">';
										settings_fields($this->codeName.'_options');
										foreach($this->config['admin'] as $section => $settings){
											switch($section){
												case 'subscription':

												break;
												default:
													echo '<div id="'.$this->codeName.'-'.$section.'-panel" class="ait-options-group ait-options-panel '.$this->codeName.'-tabs-panel">';
														echo '<div id="ait-options-basic-'.$section.'" class="ait-controls-tabs-panel ait-options-basic">';
															// echo '<div class="ait-options-section ">';
																// echo '<div class="ait-opt-container ait-opt--main">';
																	// echo '<div class="ait-opt-wrap">';
																		do_settings_fields( $this->codeName.'_options', $section );
																	// echo '</div>';
																// echo '</div>';
															// echo '</div>';
														echo '</div>';
													echo '</div>';
												break;
											}
										}
										// echo '</form>';
									echo '</div>';
								echo '</div>';
							echo '</div>';

						echo '</div>';
					echo '</div>';

				echo '</div>';
			echo '</div>';
		echo '</div>';
	}



	public function importAdminPage(){
    	require_once dirname($this->pluginFile) . '/admin/AitCsvImportExportHelpers.php';
    	$docsUrl = "https://www.ait-themes.club/doc/how-to-import-csv-file/";
		$cpts = AitCsvImportExportHelpers::getSupportedCpts();
        $taxonomies = array();
        foreach ($cpts as $cpt => $cptData) {
            $taxonomies = array_merge($taxonomies, AitCsvImportExportHelpers::getPostTaxonomyFields($cpt));

        }

        // i had to create indexed array of all types because of config sections
        // javascript displays sections that contain a selected id in the seciton name
        // if I select "ait-item" also section "ait-items" becomes visible
        $types = array();
        foreach ($cpts as $cpt) {
        	array_push($types, $cpt);
        }
        foreach ($taxonomies as $taxonomy) {
        	array_push($types, $taxonomy);
        }



        $languages = Aitlangs::getLanguagesList();



		?>




		<div class="ait-options-section">

			<div class='ait-opt-container ait-opt-select-main'>
				<div class='ait-opt-wrap'>
					<div class='ait-opt-label'>
						<div class='ait-label-wrapper'>
							<span class='ait-label'><?php _e('Language', 'ait-csv-import-export') ?></span>
						</div>
					</div>
					<div class='ait-opt ait-opt-select'>
						<div class='ait-opt-wrapper chosen-wrapper'>
							<select data-placeholder="<?php _e('Choose&hellip;', 'ait-admin') ?>" class="chosen" name="ait_csv_options[language]">
								<!-- <option selected value="none"><?php _e('Choose&hellip;', 'ait-admin') ?></option> -->
							<?php foreach($languages as $language) : ?>
								<option value="<?php echo $language->slug ?>"><?php echo $language->name ?></option>
							<?php endforeach; ?>


							</select>
						</div>
					</div>
				</div>
			</div>

			<div class='ait-opt-container ait-opt-select-main'>
				<div class='ait-opt-wrap'>
					<div class='ait-opt-label'>
						<div class='ait-label-wrapper'>
							<span class='ait-label'><?php _e('Select type of data', 'ait-csv-import-export') ?></span>
						</div>
					</div>
					<div class='ait-opt ait-opt-select'>
						<div class='ait-opt-wrapper chosen-wrapper'>
							<select data-placeholder="<?php _e('Choose&hellip;', 'ait-admin') ?>" class="chosen" name="ait_csv_options[postType]">
								<option selected value="none"><?php _e('Choose&hellip;', 'ait-admin') ?></option>
							<?php foreach($types as $index => $type) : ?>
								<option value="<?php echo $index ?>"><?php echo $type['label'] ?></option>
							<?php endforeach; ?>


							</select>
						</div>
					</div>
				</div>
			</div>
		</div>


		<?php foreach($types as $index => $type) : ?>
			<div class="ait-options-section  section-postType-<?php echo $index ?> ait-sec-title" id="postType-<?php echo $index ?>-basic">
				<h2 class="ait-options-section-title"><?php echo $type['label'] ?></h2>
				<div class="ait-options-section-help">
				<?php
					printf(__('Description for each type with examples: %s', 'ait-csv-import-export'),
					'<a target="blank" href="'.$docsUrl.'">'.$docsUrl.'</a>');
				?>
				</div>
				<?php
				$importData = array(
					'name'  => $type['slug'],
					'label' => $type['label'],
					'type'  => $type['type']

		        );


				if ($type['type'] == 'cpt') {
					// TODO hladat taxonomy fields a metafields dynamicky
			        $taxonomyFields = AitCsvImportExportHelpers::getPostTaxonomyFields($type['slug']);

			        $itemConfig = AitCsvImportExportHelpers::getRawConfig($type['slug']);
			        $itemFields = AitCsvImportExportHelpers::getPostMetaFields($itemConfig);
			        $itemFields = $itemFields['newFields'];
			        $defaultPostFields = AitCsvImportExportHelpers::getDefaultPostFields();
					$fields = $defaultPostFields + $taxonomyFields + $itemFields;
		        } else {
		        	$defaultTaxonomyFields = AitCsvImportExportHelpers::getTaxonomyFields($type['slug']);
					$fields = array_merge($defaultTaxonomyFields, AitCsvImportExportHelpers::getTaxonomyMetaFields($type['slug']));
		        }

				$this->postTypeTable = new AitCsvImportExportAdminPostTypeTable();
				$this->postTypeTable->createPostTable($fields, $importData);

				?>
			</div>

		<?php endforeach; ?>



		<?php

	}


	public function exportAdminPage()
	{
		require_once dirname($this->pluginFile) . '/admin/AitCsvImportExportHelpers.php';

        $cpts = AitCsvImportExportHelpers::getSupportedCpts();
        $taxonomies = array();
        foreach ($cpts as $cpt => $cptData) {
            $taxonomies = array_merge($taxonomies, AitCsvImportExportHelpers::getPostTaxonomyFields($cpt));
        }
        $types = $cpts + $taxonomies;
        $languages = Aitlangs::getLanguagesList();
        ?>
		<div class="ait-options-section">

        <form id="ait-csv-import" class="ait-opt-container" action="<?php echo plugin_dir_url( $this->pluginFile ) ?>/admin/export.php" method="post" enctype="multipart/form-data">
        	<div class='ait-opt-container ait-opt-select-main'>
				<div class='ait-opt-wrap'>
					<div class='ait-opt-label'>
						<div class='ait-label-wrapper'>
							<span class='ait-label'><?php _e('Select type of data', 'ait-csv-import-export') ?></span>
						</div>
					</div>
					<div class='ait-opt ait-opt-select'>
						<div class='ait-opt-wrapper chosen-wrapper'>
							<select data-placeholder="<?php _e('Choose&hellip;', 'ait-admin') ?>" class="chosen" name="post-type">
							<?php foreach($types as $slug => $type) : ?>
								<option value="<?php echo $slug ?>"><?php echo $type['label'] ?></option>
    						<?php endforeach; ?>


							</select>
						</div>
					</div>
				</div>
			</div>

			<div class='ait-opt-container ait-opt-select-main'>
				<div class='ait-opt-wrap'>
					<div class='ait-opt-label'>
						<div class='ait-label-wrapper'>
							<span class='ait-label'><?php _e('Language', 'ait-csv-import-export') ?></span>
						</div>
					</div>
					<div class='ait-opt ait-opt-select'>
						<div class='ait-opt-wrapper chosen-wrapper'>
							<select data-placeholder="<?php _e('Choose&hellip;', 'ait-admin') ?>" class="chosen" name="post-language">
							<?php foreach($languages as $language) : ?>
								<option value="<?php echo $language->slug ?>"><?php echo($language->name); ?></option>
    						<?php endforeach; ?>


							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='ait-opt-container ait-opt-select-main'>
				<div class='ait-opt-wrap'>
					<div class='ait-opt-label'>
						<div class='ait-label-wrapper'>
						</div>
					</div>
					<div class="ait-backup-action">
		            	<input type="submit" value="<?php _e('Export CSV', 'ait-csv-import-export'); ?>" class="ait-button positive uppercase">
					</div>
				</div>
			</div>


        </form>
		</div>

        <?php
	}



	public static function getInstance()
	{
		if(!self::$instance){
			self::$instance = new self;
		}

		return self::$instance;
	}


}
