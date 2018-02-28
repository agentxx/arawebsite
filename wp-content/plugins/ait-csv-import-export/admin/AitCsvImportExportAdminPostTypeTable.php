<?php

class AitCsvImportExportAdminPostTypeTable
{

	private static $instance;


	// public function __construct() {

	// }





	public static function getInstance(){
		if(self::$instance == null){
			self::$instance = new self;
			return self::$instance;
		}
		return self::$instance;
	}



	public function createPostTable($fields, $postTypeData){
		$containerId = 'ait-csv-container-' . $postTypeData['name'];

		?>
		<div id="<?php echo $containerId ?>" data-slug="<?php echo $postTypeData['name'] ?>" class="ait-csv-container">
			<div class="ait-csv-block ait-csv-part1">
				<table>
					<tr>
						<th><?php _e('Column name in CSV file', 'ait-csv-import-export'); ?></th>
						<th><?php _e('Type', 'ait-csv-import-export'); ?></th>
					</tr>

					<?php
					foreach ($fields as $field => $values) {?>
					<tr>
						<td><?php echo $field; ?></td>
						<td><?php echo $values['type']; ?></td>
					</tr>
					<?php } ?>
				</table>
				<form class="ait-csv-sample-form" action="<?php echo plugin_dir_url( __FILE__ ) ?>sample.php" method="post" enctype="multipart/form-data">
					<input type="hidden" name="ait-sample-type" value="<?php echo $postTypeData['name'] ?>">
					<div class="ait-csv-button">
						<input type="submit" value="<?php _e('Download sample CSV', 'ait-csv-import-export'); ?>" class="ait-button download">
					</div>
				</form>
			</div>

			<div class="ait-opt-container ait-opt-file-upload-main ait-csv-block ait-csv-part2">
				<h4><?php _e('Import from file', 'ait-csv-import-export'); ?></h4>
				<form class="ait-csv-import-form" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post" onsubmit="javascript: importCSV(event, '<?php echo $containerId ?>', '<?php echo $postTypeData["type"] ?>');" enctype="multipart/form-data">

					<div class="ait-opt ait-opt-file-upload ait-csv-file">
						<input type="hidden" name="slug" value="<?php echo $postTypeData['name']; ?>">
						<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'form-nonce' );?>" />
						<input type="hidden" name="upload-handler" value="<?php echo plugin_dir_url( __FILE__ ) ?>upload-handler.php" class="upload-handler" />
						<div class="ait-opt-wrapper">
							<label class="ait-opt-file-wrapper">
								<span class="ait-opt-file-input"><?php _ex('Choose your file', 'import', 'ait-admin') ?></span>
								<input name="posts_csv" type="file" accept=".csv" class="file-select">
								<span class="ait-opt-btn"><?php _ex('Browse', 'browse file from disk button label', 'ait-admin') ?></span>
							</label>
						</div>
					</div>
					<div class="ait-backup-action">
						<input type="submit" value="<?php _e('Import from CSV', 'ait-csv-import-export'); ?>" class="ait-button upload positive uppercase">
					</div>
				</form>

				<div class="progress ait-loader" data-max="0" data-current="0">
					<p class="loader-status"><span class="loader-value">0</span>/<span class="loader-max">0</span></p>
					<div class="loader-bar"></div>
				</div>
			</div>

			<div class="ait-csv-report alert alert-danger"></div>

		</div>

	<?php
	}

}

