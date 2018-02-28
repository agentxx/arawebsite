<?php 
	
	$id = $oldTerm->term_id;
	$tax = str_replace("-", "_", $oldTerm->taxonomy);
	
	return array(
		'keywords' => '',
		'icon' => AitMigration::getTermMeta($id , $tax, 'icon'),
		'icon_color' => '',
		'map_icon' => AitMigration::getTermMeta($id , $tax, 'marker'),
		'header_type' => 'map',
		'header_image' => '',		
	);

?>