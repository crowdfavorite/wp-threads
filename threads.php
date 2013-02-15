<?php
/*
Plugin Name: Threads 
Plugin URI: http://crowdfavorite.com/wordpress/plugins/ 
Description: (description) 
Version: 1.0dev 
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

define('CFTH_PATH', plugin_dir_path(__FILE__));

// utility library for binding custom taxonomies and post types together
require(CFTH_PATH.'/lib/cf-tax-post-binding/cf-tax-post-binding.php');

// set up custom post types and taxonomies
require(CFTH_PATH.'/architecture.php');

// show views as appropriate
function cfth_template_redirect() {
	global $wp_query;
	if (is_singular('thread')) {
		add_filter('the_content', 'cfth_thread_single', 999999);
		return;
	}
	if (is_post_type_archive('thread')) {
		add_filter('the_content', 'cfth_thread_archive', 999999);
		return;
	}
}
add_action('template_redirect', 'cfth_template_redirect');

function cfth_thread_single($content) {
	ob_start();
	include(CFTH_PATH.'/views/content/type-thread.php');
	return ob_get_Clean();
}

function cfth_thread_archive($content) {
	include(CFTH_PATH.'/views/loop/type-thread.php');
	return $content;
}

function cfth_timeline_posts($term_id) {
	$term = get_term_by('id', $term_id, 'threads');
	$posts = new WP_Query(array(
		'posts_per_page' => -1,
		'taxonomy' => 'threads',
		'term' => $term->slug,
		'order' => 'ASC'
	));
	return $posts->posts;
}

function cfth_timeline_content($term_id) {
// TODO - refactor to handle time offset calculation, etc. here rather than in view
	return cfth_timeline_posts($term_id);
}

function cfth_update_thread_date($post) {
// if published post
// get threads
// update each thread date with current date
}
add_action('update_post', 'cfth_update_thread_date');

function cfth_asset_url($path) {
	return plugins_url($path, __FILE__);
}