<?php


class AitCsvImportExportImportType
{
	private $type;

	private $neonConfig;

	private $neonFields;

	private $defaultMeta;

	private $slug;

	private $lang;

	private $defaultPostFields;

	private $metaKey;

	private static $instance;

	private $aitOptions;

	public function __construct($type, $slug, $lang){
		$this->type = $type;
		$this->slug = $slug;
		$this->lang = $lang;
		if ($type == 'cpt') {
			$this->defaultPostFields = AitCsvImportExportHelpers::getDefaultPostFields();
			$this->metaKey = AitCsvImportExportHelpers::getMetaKey($this->slug);

			$this->neonConfig = AitCsvImportExportHelpers::getRawConfig($this->slug);
			$this->neonFields = AitCsvImportExportHelpers::getPostMetaFields($this->neonConfig);

			$this->defaultMeta = AitCsvImportExportHelpers::getDefaultMeta($this->slug);
		}
	}



	public static function validateFile($file, $delimiter)
	{
		if (($handle = fopen($file, "r")) !== FALSE) {
			$result = array();

			// switch first row with delimiter
			$data_row = fgets($handle, 10000);

			$headersLineNum = 1;

			// skip empty lines until fields reached
			while ( array(null) == $data_row = fgetcsv($handle, 10000, $delimiter, '"') ) {
				$headersLineNum++;
				continue;
			}

			// get fields
			$result['fields'] = $data_row;
			$result['headersLineNum'] = $headersLineNum;
		}
		fclose($handle);
		return $result;
	}



	public function importFile($options)
	{
		$file           = $options['file'];
		$offset         = $options['offset'];
		$rowsPerRequest = $options['rowsPerRequest'];
		$type           = $options['type'];
		$delimiter      = $options['delimiter'];
		$firstDataRow   = $options['firstDataRow'];

		$response = array();
		$imported = 0;

		$validation = self::validateFile($file, $delimiter);

		$fileFields = $validation['fields'];

		$currentLine = 0;
		// ignore first line with delimiter and number of lines before columns labels
		$startLine = $firstDataRow + $offset;
		$endLine = $startLine + $rowsPerRequest;
		if (($handle = fopen($file, "r")) !== FALSE) {
			while ( ($data_row = fgetcsv($handle, 10000, $delimiter, '"')) !== FALSE ) {

			    if ($currentLine >= $startLine && $currentLine < $endLine) {
					// ignore blank lines
					if (array(null) == $data_row) {
						continue;
					}
					// create associative array from a single csv row
			        foreach ($fileFields as $key => $heading) {
			        	if (!empty($heading)) {
			        		$row[$heading] = (isset($data_row[$key])) ? $data_row[$key] : '';
			        	}
			        }

			        // do import
			        if ($type == "cpt") {
						$result = $this->import($row, $this->slug);
			        } else {
						$result = $this->importTax($row, $this->slug);
			        }

			        if ($result['type'] == 'error') {
			        	$response = $result;

			        	return $response;
			        }

			        $imported ++;
			    }

			    $currentLine++;
			}
		}
		fclose($handle);

		$response['type'] = 'success';
		$response['imported'] = $imported;
		$response['message'] = __('Content was successfully imported, yay!', 'ait-admin');
		return $response;
	}




	public function importTax($row, $taxonomy)
	{
		$defaultTaxFields = AitCsvImportExportHelpers::getTaxonomyFields($taxonomy);
		$metaTaxFields = AitCsvImportExportHelpers::getTaxonomyMetaFields($taxonomy);

		$args = array();

		foreach ($defaultTaxFields as $key => $value) {
			$args[$key] = $row[$key];
		}

		if (empty($args['name'])) {
			$report = array(
				'type' =>  'error',
				'message' => __('Your file is missing column', 'ait-csv-import-export') . ': name',
			);
			return $report;
		}
		$args['slug'] = empty($row['slug']) ? sanitize_title($row['name']) : $row['slug'];

		if (!empty($row['parent'])) {
			$parent =  get_term_by( 'slug', $row['parent'], $taxonomy );
			if ($parent) {
				$args['parent'] = $parent->term_id;
			}
		}

		$foundTerm = get_term_by( 'slug', $args['slug'], $taxonomy );

		if ($foundTerm) {
			$result = wp_update_term( $foundTerm->term_id, $taxonomy, $args );
		} else {
			$result = wp_insert_term(  $row['name'], $taxonomy, $args);
		}

		$termId = $result['term_id'];

		$meta = array();
		foreach ($metaTaxFields as $key => $value) {
			$meta[$key] = $row[$key];
		}

		$optionName = $taxonomy . "_category_" . $termId;
		update_option($optionName, $meta);

		/********** ASSIGN LANGUAGE ****************/
		if (defined('AIT_LANGUAGES_ENABLED') && function_exists('pll_set_term_language')) {
			pll_set_term_language($termId, $this->lang);
		}
		/********** ASSIGN LANGUAGE ****************/
	}



	// private static function import($row)
	public function import($row, $post_type)
	{

		global $wpdb;
		$defaultPostData = array();
		$report = array();

		foreach ($this->defaultPostFields as $key => $value) {
			if (isset($row[$key])) {
				$defaultPostData[$key] = $row[$key];
			} else {
				$report = array(
					'type' =>  'error',
					'message' => __('Your file is missing column', 'ait-csv-import-export') . ': ' . $key,
				);
				return $report;
			}
		}
		$defaultPostData['post_type'] = $post_type;

		if (!empty($defaultPostData['post_date']) && !$this->validateDate($defaultPostData['post_date'])) {
			$report = array(
				'type' =>  'error',
				'message' => __('Invalid date format!', 'ait-csv-import-export') . ' ' . $defaultPostData['post_title'] . ': ' . $defaultPostData['post_date'],
			);
			return $report;
		}




		/********** CREATE/UPDATE POST ****************/
		if (empty($row['post_name'])) {
			$existingPost = NULL;
			$defaultPostData['post_name'] = sanitize_title($defaultPostData['post_title']);
		} else {
			$existingPost = get_page_by_path($row['post_name'], 'OBJECT', $post_type);
		}

		if ($existingPost == NULL) {
			$post_id = wp_insert_post($defaultPostData, true);
			if(is_wp_error($post_id)){
				$report = array(
					'type' =>  'error',
					'message' => $post_id->get_error_message(),
				);
			}
		} else {
			$defaultPostData['ID'] = $existingPost->ID;
			$post_id = wp_update_post($defaultPostData);
		}
		/********** CREATE/UPDATE POST ****************/



		/********** SET FEATURED IMAGE ****************/
		$slug = trim($row['post_image']);
		if ( !empty($slug) ) {
			$found_image_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE guid LIKE '%/$slug.%'");
			if (isset($found_image_id) && $found_image_id) {
				update_post_meta( $post_id, '_thumbnail_id', $found_image_id);
			}
		}
		/********** SET FEATURED IMAGE ****************/



		/********** IMPORT META DATA ****************/
		$replacedFields = $this->neonFields['replacedFields'];
		$neonFields = $this->neonFields['newFields'];

		// save firstly default meta if new post is just inserted
		if ($existingPost == null) {
			$currentMeta = AitCsvImportExportHelpers::getDefaultMeta($post_type);
		} else {
			$currentMeta = get_post_meta( $post_id, $this->metaKey, true );

			// hotfix if meta is empty or doesn't exist for any reason
			if (empty($currentMeta)) {
				$currentMeta = $this->defaultMeta;
			}
		}
		$newMeta = $currentMeta;

		// TODO I assume all CSV fields exists in current meta - there isn't any missing field
		// if some meta is missing for the existing item it will be ignored (skip such csv column)
		foreach ($currentMeta as $key => $value) {
			if (AitCsvImportExportHelpers::isIgnoredField($key)) {
				continue;
			}

			// if input type is translatabe get only default value
			$newMeta[$key] = AitLangs::getDefaultLocaleText($value, $value);

			// ignore meta data which aren't in default config file
			// there might be some additional meta like rating, rating_mean etc.
			if (!isset($this->neonConfig[$key])) {
				continue;
			}

			if (AitCsvImportExportHelpers::isException($this->neonConfig[$key]['type'])) {
				// handle exceptions of input types
				$newMeta[$key] = AitCsvImportExportHelpers::buildMetaFromCSVException($row, $this->neonConfig[$key]['type'], $key, $replacedFields[$key]);
			} elseif (isset($row[$key])) {
				$newMeta[$key] = $row[$key];
			}
		}

		if (!empty($newMeta)) {
			update_post_meta($post_id, $this->metaKey, $newMeta);
		}

		if($post_type == 'ait-item'){
			if(empty($newMeta['featuredItem'])) {
				update_post_meta($post_id, "_ait-item_item-featured", "0");
			} else {
				update_post_meta($post_id, "_ait-item_item-featured", "1");
			}
		}


		/********** IMPORT META DATA ****************/




		/********** ASSIGN TAXONOMIES ****************/
		// TODO
		$taxonomies = array();
		$taxonomies = array_merge($taxonomies, AitCsvImportExportHelpers::getPostTaxonomyFields($post_type));

		foreach ($taxonomies as $taxonomy => $taxInfo) {
			$terms = explode('|', $row[$taxonomy]);
			$result = wp_set_object_terms($post_id, $terms, $taxonomy, true);
			if(!is_wp_error($result)){
				// var_dump($result);
				// TODO
				// report success
			} else {
				// TODO
				// report error
			}
		}
		/********** ASSIGN TAXONOMIES ****************/



		/********** ASSIGN LANGUAGE ****************/
		if (defined('AIT_LANGUAGES_ENABLED') && function_exists('pll_set_post_language')) {
			pll_set_post_language($post_id, $this->lang);
		}
		/********** ASSIGN LANGUAGE ****************/

		do_action('ait_csv_post_imported', $post_type, $post_id);


		$report['type'] = 'success';
		$report['message'] = $row['post_name'];
		return $report;
	}



	public function validateDate($date, $format = 'Y-m-d H:i:s')
	{
	    $d = DateTime::createFromFormat($format, $date);
	    return $d && $d->format($format) == $date;
	}
}
