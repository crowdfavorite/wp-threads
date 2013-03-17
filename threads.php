<?php
/*
Plugin Name: Threads 
Plugin URI: http://crowdfavorite.com/wordpress/plugins/ 
Description: Provide context for an ongoing story by showing a timeline of related posts (with a link to that timeline from each post).
Version: 1.0b1 
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

define('CFTH_PATH', trailingslashit(plugin_dir_path(__FILE__)));

// utility library for binding custom taxonomies and post types together
require(CFTH_PATH.'lib/cf-tax-post-binding/cf-tax-post-binding.php');

// set up custom post types and taxonomies
require(CFTH_PATH.'architecture.php');

// sidebar widget
require(CFTH_PATH.'recent-threads-widget.php');

// check for /thread support in permalink patterns
function cfth_permalink_check() {
	$rewrite_rules = get_option('rewrite_rules');
	if ($rewrite_rules == '') {
		return;
	}
	global $wp_rewrite;
	$pattern = $wp_rewrite->front.'thread/';
	if (substr($pattern, 0, 1) == '/') {
		$pattern = substr($pattern, 1);
	}
	// check for 'thread' in rewrite rules
	foreach ($rewrite_rules as $rule => $params) {
		if (substr($rule, 0, strlen($pattern)) == $pattern) {
			return;
		}
	}
	// flush rules if not found above
	flush_rewrite_rules();
}
add_action('admin_init', 'cfth_permalink_check');

// show views as appropriate
function cfth_template_redirect() {
	if (is_singular('thread')) {
		add_filter('the_content', 'cfth_thread_single', 999999);
		add_action('wp_head', 'cfth_timeline_css');
		return;
	}
// TODO
// 	if (is_post_type_archive('thread')) {
// 		add_filter('the_content', 'cfth_thread_archive', 999999);
// 		return;
// 	}
}
add_action('template_redirect', 'cfth_template_redirect');

function cfth_thread_single($content) {
	global $post;
	if ($post->post_type == 'thread') {
		$term_id = cftpb_get_term_id('threads', $post->ID);
		$view = apply_filters('threads_single_view', CFTH_PATH.'views/content/type-thread.php');
		ob_start();
		include($view);
		$content = ob_get_clean();
	}
	return $content;
}

// TODO
function cfth_thread_archive($content) {
	$view = apply_filters('threads_archive_view', CFTH_PATH.'views/loop/type-thread.php');
	ob_start();
	include($view);
	return ob_get_clean();
}

function cfth_thread_timeline($term_id) {
	$posts = cfth_timeline_content($term_id);
	$view = apply_filters('threads_timeline_view', CFTH_PATH.'views/content/timeline.php');
	ob_start();
	include($view);
	return ob_get_clean();
}

function cfth_timeline_shortcode($atts) {
	extract(shortcode_atts(array(
		'term' => null,
	), $atts));
	$_term = get_term_by('slug', $term, 'threads');
	if (empty($_term) || is_wp_error($_term)) {
		return '<p>'.sprintf(__('Sorry, could not find a thread: <i>%s</i>', 'threads'), esc_html($term)).'</p>';
	}
	ob_start();
	cfth_timeline_css();
	$css = ob_get_clean();
	return $css.cfth_thread_timeline($_term->term_id);
}
add_shortcode('thread', 'cfth_timeline_shortcode');

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
	if ($term) {
		$query = new WP_Query(array(
			'posts_per_page' => -1,
			'taxonomy' => 'threads',
			'term' => $term->slug,
			'order' => 'ASC',
		));
		return $query->posts;
	}
	return array();
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
			else if ($y == 1 || ($y == 0 && $m == 12)) {
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
	if ($post->post_type == 'thread') {
// don't infinite loop
		remove_action('save_post', 'cfth_update_thread_date', 10, 2);
// get term
		$term_id = cftpb_get_term_id('threads', $post_id);
		$term = get_term($term_id, 'threads');
// get most recent post
		$query = new WP_Query(array(
			'posts_per_page' => 1,
			'taxonomy' => 'threads',
			'term' => $term->slug,
			'post_status' => 'publish',
			'order' => 'DESC'
		));
		if (count($query->posts == 1)) {
			$thread_post = $query->posts[0];
// get term post, update with date
			wp_update_post(array(
				'ID' => $post_id,
				'post_date' => $thread_post->post_date,
				'post_date_gmt' => $thread_post->post_date_gmt,
			));
		}
	}
	else if ($post->post_status == 'publish') {
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

function cfth_timeline_css() {
	$css = apply_filters('threads_timeline_css', '');
	if (!empty($css)) {
		echo $css;
		return;
	}
?>
<style>
.threads-timeline {
	background: transparent url(<?php echo cfth_asset_url('img/timeline.png'); ?>) repeat-y;
	padding: 20px 0;
}
.threads-item .date {
	color: #666;
	display: inline-block;
	font-size: 85%;
	margin-right: 3px;
	text-align: right;
	width: 88px;
}
.threads-item .title {
	background: transparent url(<?php echo cfth_asset_url('img/bullet-hollow.png'); ?>) left center no-repeat;
	margin-left: 4px;
	padding-left: 20px;
}
.threads-item .intersects {
	font-size: 85%;
	margin-left: 116px;
}
.threads-lat {
	background: #fff url(<?php echo cfth_asset_url('img/lat.png'); ?>) no-repeat;
	color: #999;
	height: 110px;
	line-height: 110px;
	margin: 10px 0;
	padding: 0 75px;
	width: 410px;
}
@media screen and (max-width: 768px) {
	.threads-timeline {
		background-position: -90px 0;
	}
	.threads-item .date {
		display: block;
		line-height: 14px;
		padding-left: 20px;
		text-align: left;
	}
	.threads-item .title {
		background-position: 5px 2px;
		display: block;
		margin-left: 0;
	}
	.threads-item .intersects {
		line-height: 16px;
		margin-left: 20px;
	}
}
@media 	(min--moz-device-pixel-ratio: 1.5),
		(-o-min-device-pixel-ratio: 3/2),
		(-webkit-min-device-pixel-ratio: 1.5),
		(min-device-pixel-ratio: 1.5),
		(min-resolution: 144dpi),
		(min-resolution: 1.5dppx) {
	.threads-timeline {
		background-image: url(<?php echo cfth_asset_url('img/timeline@2x.png'); ?>);
		background-size: 105px 20px;
	}
	.threads-item .title {
		background-image: url(<?php echo cfth_asset_url('img/bullet-hollow@2x.png'); ?>);
		background-size: 11px 11px;
	}
	.threads-lat {
		background-image: url(<?php echo cfth_asset_url('img/lat@2x.png'); ?>);
		background-size: 410px 110px;
	} 
}
</style>
<?php
}

