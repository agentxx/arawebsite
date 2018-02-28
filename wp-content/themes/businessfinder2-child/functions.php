<?php
    function pu_scripts_admin(){
        wp_enqueue_script('pu-scripts',get_stylesheet_directory_uri().'/pu-scripts.js',array('jquery'),filemtime(get_stylesheet_directory().'/pu-scripts.js'));
    }
    add_action('admin_enqueue_scripts','pu_scripts_admin');

    function pu_scripts(){
         wp_enqueue_style( 'child-style', get_stylesheet_directory_uri().'/style.css', array(), filemtime(get_stylesheet_directory().'/style.css'), 'all' );
    }
    add_action('wp_enqueue_scripts','pu_scripts',100);

   

/*
CHANGE SLUGS OF CUSTOM POST TYPES
*/
function change_post_types_slug( $args, $post_type ) {

	/*item post type slug*/
	if ( 'ait-item' === $post_type ) {
		$args['rewrite']['slug'] = 'Initiatives-Map';
	}

	/*event pro post type slug, available with Events Pro plugin*/
	if ( 'ait-event-pro' === $post_type ) {
		$args['rewrite']['slug'] = 'opportunity-event';
	}

	return $args;
}
add_filter( 'register_post_type_args', 'change_post_types_slug', 10, 2 );


/*
CHANGE SLUGS OF TAXONOMIES, slugs used for archive pages
*/
function change_taxonomies_slug( $args, $taxonomy ) {

	/*item category*/
	if ( 'ait-items' === $taxonomy ) {
		$args['rewrite']['slug'] = 'Initiatives-Map';
	}
	/*event pro category, available with Events Pro plugin*/
	if ( 'ait-events-pro' === $taxonomy ) {
		$args['rewrite']['slug'] = 'opportunities-events';
	}

	return $args;
}
add_filter( 'register_taxonomy_args', 'change_taxonomies_slug', 10, 2 );




function debug($obj)
{
    echo "<pre>";
    var_dump($obj);
    echo "</pre>";
}

?>