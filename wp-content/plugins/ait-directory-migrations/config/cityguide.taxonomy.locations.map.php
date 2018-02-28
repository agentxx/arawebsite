<?php 
	
	$id = $oldTerm->term_id;
	$tax = str_replace("-", "_", $oldTerm->taxonomy);

	return array(
		'icon' => AitMigration::getTermMeta($id , $tax, 'icon'),
		'map_icon' => AitMigration::getTermMeta($id , $tax, 'marker'),
		'header_type' => 'map',
		'header_image' => '',
		'keywords' => '',
		'header_image_align' => 'image-left'
	);
	
?>