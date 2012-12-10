<?php
/*
Plugin Name: Threads 
Plugin URI: http://crowdfavorite.com/wordpress/plugins/threads/ 
Description: (description) 
Version: 1.0dev 
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

// potential future enhancement - abstract to user setting?
function cfthr_enabled_post_types() {
// 	$enabled = array();
// 	$types = get_post_types();
// 	foreach ($types as $type) {
// d($type);
// 	}
// 	return $enabled;
	return array('post');
}

function cfth_register_taxonomy() {
	$types = cfthr_enabled_post_types();
	register_taxonomy(
		'threads',
		$types,
		array(
			'hierarchical' => true,
			'labels' => array(
				'name' => __('Threads', 'threads'),
				'singular_name' => __('Thread', 'threads'),
				'search_items' => __('Search Threads', 'threads'),
				'popular_items' => __('Popular Threads', 'threads'),
				'all_items' => __('All Threads', 'threads'),
				'parent_item' => __('Parent Thread', 'threads'),
				'parent_item_colon' => __('Parent Thread:', 'threads'),
				'edit_item' => __('Edit Thread', 'threads'),
				'update_item' => __('Update Thread', 'threads'),
				'add_new_item' => __('Add New Thread', 'threads'),
				'new_item_name' => __('New Thread Name', 'threads'),
			),
			'sort' => true,
			'args' => array('orderby' => 'term_order'),
			'rewrite' => array(
				'slug' => 'thread',
				'with_front' => false, // TODO - perhaps this should be set true?
			),
		)
	);
}
add_action('init', 'cfth_register_taxonomy', 9999);

//a:23:{s:11:"plugin_name";s:7:"Threads";s:10:"plugin_uri";s:51:"http://crowdfavorite.com/wordpress/plugins/threads/";s:18:"plugin_description";s:13:"(description)";s:14:"plugin_version";s:6:"1.0dev";s:6:"prefix";s:4:"cfth";s:12:"localization";s:7:"threads";s:14:"settings_title";s:7:"Threads";s:13:"settings_link";s:7:"Threads";s:4:"init";s:1:"1";s:7:"install";b:0;s:9:"post_edit";b:0;s:12:"comment_edit";b:0;s:6:"jquery";b:0;s:6:"wp_css";s:1:"1";s:5:"wp_js";s:1:"1";s:9:"admin_css";b:0;s:8:"admin_js";b:0;s:8:"meta_box";b:0;s:15:"request_handler";b:0;s:6:"snoopy";b:0;s:11:"setting_cat";b:0;s:14:"setting_author";b:0;s:11:"custom_urls";b:0;}
