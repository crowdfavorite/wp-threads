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

function cfth_thread_links($threads) {
	$links = array();
	foreach ($threads as $thread) {
		$post = cftpb_get_post($thread->term_id, $thread->taxonomy);
		$links[] = '<a href="'.get_permalink($post->ID).'">'.$thread->name.'</a>';
	}
	return $links;
}

function cfth_thread_notice($posts, $query) {
	foreach ($posts as $post) {
// check for one or more threads
		$threads = wp_get_post_terms($post->ID, 'threads');
		if (count($threads) > 0) {
			$thread_links = cfth_thread_links($threads);
			$thread_links = implode(', ', $thread_links);
			if (count($threads) == 1) {
				$notice_single = sprintf(__('This post is part of the thread: %s - an ongoing story on this site. View the thread timeline for more context on this post.', 'threads'), $thread_links);
				$notice = apply_filters('cfth_thread_notice_single', $notice_single);
			}
			else {
				$notice_mult = sprintf(__('This post is part of the following threads: %s - ongoing stories on this site. View the thread timelines for more context on this post.', 'threads'), $thread_links);
				$notice = apply_filters('cfth_thread_notice_mult', $notice_mult);
			}
			$post->post_content .= "\n\n".'<p class="threads-post-notice">'.$notice.'</p>';
			$post = apply_filters('cfth_thread_notice', $post, $threads);
		}
	}
	return $posts;
}
add_filter('the_posts', 'cfth_thread_notice', 10, 2);

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
	$posts = cfth_timeline_posts($term_id);
	if (!count($posts)) {
		return array();
	}

	if (count($posts) > 1) {
		$first = $posts[0]->post_date_gmt;
		$last = $posts[count($posts) - 1]->post_date_gmt;
	}

// max reasonable height is 200-250px, need to take max duration, set to 200-250px, check against min height, 
// set all others accordingly

	$prev = null;
	foreach ($posts as $_post) {
		$_post->threads_data = array(
			'time_offset' => 0,
		);
		if ($prev) {
			$prev_timestamp = strtotime($prev->post_date_gmt);
			$this_timestamp = strtotime($_post->post_date_gmt);
			$prev->threads_data['time_offset'] = $this_timestamp - $prev_timestamp;
		}
		$prev = $_post;
	}

	foreach ($posts as $_post) {
		$_post->threads_data['lat'] = false;
		$_post->threads_data['lat_text'] = '';
		$time_offset = $_post->threads_data['time_offset'];
		$margin = ceil($time_offset / 15000);
		if ($time_offset > (DAY_IN_SECONDS * 90)) {
			$_post->threads_data['lat'] = true;
			// calc semi-meaningful duration here
			$margin = 0;
			$_offset = $time_offset;
			$y = $m = $d = 0;
			if ($_offset > (DAY_IN_SECONDS * 365)) {
				$y = floor($_offset / (DAY_IN_SECONDS * 365));
				$_offset -= ($y * DAY_IN_SECONDS * 365);
			}
			if ($_offset > (DAY_IN_SECONDS * 60)) {
				$m = floor($_offset / (DAY_IN_SECONDS * 30));
				$_offset -= ($m * DAY_IN_SECONDS * 30);
			}
			if ($_offset > DAY_IN_SECONDS) {
				$d = floor($_offset / DAY_IN_SECONDS);
				$_offset -= ($d * DAY_IN_SECONDS);
			}

			if ($y > 1 && $m > 0) {
				$lat = sprintf(__('%s Years, %s Months', 'threads'), $y, $m);
			}
			else if ($y > 1) {
				$lat = sprintf(__('%s Years', 'threads'), $y);
			}
			else if ($y == 1 && $m > 0) {
				$lat = sprintf(__('1 Year, %s Months', 'threads'), $m);
			}
			else if ($y == 1) {
				$lat = sprintf(__('1 Year', 'threads'));
			}
			else if ($m >= 6) {
				$lat = sprintf(__('%s Months', 'threads'), $m, $d);
			}
			else if ($m > 0 && $d > 0) {
				$lat = sprintf(__('%s Months, %s Days', 'threads'), $m, $d);
			}
			else if ($m > 0) {
				$lat = sprintf(__('%s Months', 'threads'), $m);
			}
			else {
				$lat = sprintf(__('%s Days', 'threads'), $d);
			}
			$_post->threads_data['lat_text'] = $lat;
		}
		else if ($margin > 200) {
			$margin = 200;
		}
		$_post->threads_data['margin'] = $margin;

		$_post->threads_data['intersects'] = array();
		$threads = wp_get_post_terms($_post->ID, 'threads');
		foreach ($threads as $thread) {
			if ($thread->term_id != $term_id) {
				$_post->threads_data['intersects'][] = $thread;
			}
		}
	}
	
	return $posts;
}

function cfth_update_thread_date($post_id, $post) {
	if ($post->post_status == 'publish') {
// get threads
		$threads = wp_get_post_terms($post->ID, 'threads');
// update each thread date with current date
		foreach ($threads as $thread) {
			$_post = cftpb_get_post($thread->term_id, 'threads');
			$now = current_time('mysql');
			if ($now > $_post->post_date) {
				$data = array(
					'ID' => $_post->ID,
					'post_date' => $now,
					'post_date_gmt' => current_time('mysql', 1),
				);
				wp_update_post($data);
			}
		}
	}
}
add_action('save_post', 'cfth_update_thread_date', 10, 2);

function cfth_asset_url($path) {
	$url = plugins_url($path, __FILE__);
	return apply_filters('cfth_asset_url', $url, $path, __FILE__);
}


