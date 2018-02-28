<?php

$wp_root = dirname(__FILE__) .'/../../../../';
if(file_exists($wp_root . 'wp-load.php')) {
	require_once($wp_root . "wp-load.php");
} else if(file_exists($wp_root . 'wp-config.php')) {
	require_once($wp_root . "wp-config.php");
} else {
	exit;
}

require_once dirname(__FILE__) . '/AitCsvImportExportHelpers.php';

$type = isset($_POST['post-type']) ? $_POST['post-type'] : '';

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=".$type.".csv");
header("Pragma: no-cache");
header("Expires: 0");

// TODO
// decide whether post type or taxonomy is exported
$supportedCpts = array_keys(AitCsvImportExportHelpers::getSupportedCpts());
if ( in_array($type, $supportedCpts) ) {
	exportPostType($type);
} elseif (AitCsvImportExportHelpers::isSupportedTax($type)) {
	exportTaxonomy($type);
}


function exportPostType($type)
{
	$lang = isset($_POST['post-language']) ? $_POST['post-language'] : AitLangs::getDefaultLang()->locale;

	$defaultPostFields = AitCsvImportExportHelpers::getDefaultPostFields();
	$metaConfig = AitCsvImportExportHelpers::getRawConfig($type);
	$metaFields = AitCsvImportExportHelpers::getPostMetaFields($metaConfig);
	$parents = $metaFields['parents'];
	$allNewMetaFields = $metaFields['newFields'];
	$taxonomyFields = AitCsvImportExportHelpers::getPostTaxonomyFields($type);
	$fields = array_merge($defaultPostFields, $taxonomyFields, $allNewMetaFields);
	$metaKey = AitCsvImportExportHelpers::getMetaKey($type);


	$data = array();

	// fix for microsoft office
	$data[0] = array('sep=;');
	$data[1] = array_keys($fields);


	$outstream = fopen("php://output", 'w');
	array_walk($data, '__outputCSV', $outstream);

	$args = array(
		'post_type' => $type,
		'posts_per_page' => -1,
		'lang' => $lang,
		'post_status' => array('publish', 'draft'),
	);
	$posts = get_posts($args);
	// dd($posts);

	$sortedKeys = array_keys($fields);
	foreach ($posts as $post) {
		$row = array();
		// add default post data
		foreach ($defaultPostFields as $key => $field) {
			$row[$key] = $post->$key;
		}

		foreach ($taxonomyFields as $key => $field) {
			$row[$key] = implode('|', wp_get_post_terms( $post->ID, $key, array('fields' => 'slugs') ));
		}

		// add featured image
		$file = get_attached_file( get_post_thumbnail_id($post->ID) );
		if(!empty($file)){
			$path_parts = pathinfo($file);
		}

		$row['post_image'] = empty($file) ? '' : $path_parts['filename'];

		// add metadata
		$postMeta = get_post_meta($post->ID, $metaKey, true);
		foreach ($allNewMetaFields as $newKey => $fieldInfo) {
			// if current post meta contain the key from available csv fields uste the default value
			// otherwise handle exception
			// if exception doesn't exist maybe meta wasn't saved correctly so save only empty string
			if (empty($postMeta)) {
				$row[$newKey] = "";
			} elseif (isset($postMeta[$newKey])) {
				$row[$newKey] = $postMeta[$newKey];
			} elseif (isset($parents[$newKey])) {
				$originalMetakey = $parents[$newKey]['metakey'];
				$metaEntry = $postMeta[$originalMetakey];
				$row[$newKey] = AitCsvImportExportHelpers::getValueFromMeta($metaEntry, $parents[$newKey], $newKey);
			} else {
				$row[$newKey] = "";
			}
		}


		// sort the result array so it matches with
		$sortedRow = array();
		foreach ($sortedKeys as $key) {
			$sortedRow[$key] = $row[$key];
		}

		fputcsv($outstream, $sortedRow, ';', '"');
	}


	fclose($outstream);
	// }

}


function exportTaxonomy($taxonomy)
{
	$defaultTaxFields = AitCsvImportExportHelpers::getTaxonomyFields($taxonomy);
	$metaTaxFields = AitCsvImportExportHelpers::getTaxonomyMetaFields($taxonomy);
	$sortedKeys = array_keys(array_merge($defaultTaxFields, $metaTaxFields));

	$data = array();

	// fix for microsoft office
	$data[0] = array('sep=;');
	$data[1] = $sortedKeys;


	$outstream = fopen("php://output", 'w');
	array_walk($data, '__outputCSV', $outstream);

	$lang = isset($_POST['post-language']) ? $_POST['post-language'] : AitLangs::getDefaultLang()->slug;

	$args = array(
		'lang'         => $lang,
		'hide_empty'   => false,
		'parent'       => 0,
		'hierarchical' => true,
	);
	$terms = AitCsvImportExportHelpers::getTermsRecursivelly($taxonomy, $args);
	foreach ($terms as $term) {
		$row = array();
		foreach ($defaultTaxFields as $key => $keyInfo) {

			// match only existing data (for ex. language and parent will be matched later)
			if (isset($term->$key)) {
				$row[$key] = $term->$key;
			}

			// replace parent id by it's slug
			if (!empty($term->parent)) {
				$parent = get_term_by('id', $term->parent , $taxonomy);
				$row['parent'] = $parent->slug;
			}

			// add language info
			$row['lang'] = $lang;
		}

		$meta = get_option($taxonomy . "_category_" . $term->term_id, array());

		foreach ($metaTaxFields as $key => $keyInfo) {
				$row[$key] = "";
			if (isset($meta[$key])) {
				if ($keyInfo['type'] == 'on-off') {
					$row[$key] = empty($meta[$key]) ? '0' : '1';
				} else {
					$row[$key] = $meta[$key];
				}
			} else {
				$row[$key] = "";
			}
		}


		$sortedRow = array();
		foreach ($sortedKeys as $key) {
			$sortedRow[$key] = $row[$key];
		}

		fputcsv($outstream, $sortedRow, ';', '"');
	}
}


function __outputCSV(&$vals, $key, $filehandler) {
	fputcsv($filehandler, $vals, ';', '"');
}

?>