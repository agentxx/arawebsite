<?php 
	
	$id = $oldTerm->term_id;
	$tax = str_replace("-", "_", $oldTerm->taxonomy);
	
	return array(
		'keywords' => '',
		'icon' => AitMigration::getTermMeta($id , $tax, 'icon'),		
		'header_image' => '',		
		'header_image_align' => 'image-left',
		'header_type' => 'map',
		'category_featured' => false,
		'header_height' => '',
		'taxonomy_image' => ''
	);

?>