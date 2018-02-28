<?php 
	
	$id = $oldTerm->term_id;
	$tax = str_replace("-", "_", $oldTerm->taxonomy);
	
	return array(
		'keywords' => '',
		'icon' => AitMigration::getTermMeta($id , $tax, 'icon'),
		'map_icon' => AitMigration::getTermMeta($id , $tax, 'marker'),
		'header_type' => 'map',
		'header_image' => '',		
		'header_image_align' => 'image-left',
		'category_featured' => false,
		'header_height' => ''
	);

?>