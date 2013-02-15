<?php

global $post;

if ( has_post_thumbnail() ) {
?>
	<div class="entry-media"><?php the_post_thumbnail('medium'); ?></div>
<?php
}

echo wpautop(wptexturize($post->post_content));

$term_id = cftpb_get_term_id('threads', $post->ID);
$posts = cfth_timeline_content($term_id);

if (!count($posts)) {
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
	text-align: right;
	width: 87px;
}
.threads-item .title {
	background: transparent url(<?php echo cfth_asset_url('img/bullet.png'); ?>) left center no-repeat;
	margin-left: 4px;
	padding-left: 20px;
}
.threads-item .intersects {
	font-size: 85%;
	margin-left: 116px;
}
.threads-lat {
	background: #fff url(<?php echo cfth_asset_url('img/lat.png'); ?>) no-repeat;;
	color: #999;
	height: 110px;
	line-height: 110px;
	margin: 10px 0;
	padding: 0 75px;
	width: 410px;
}
</style>
<div class="threads-timeline">
<?php

if (count($posts) > 1) {
	$first = $posts[0]->post_date_gmt;
	$last = $posts[count($posts) - 1]->post_date_gmt;
}

// echo $first.'<br />';
// echo strtotime($first, 0).'<br>';
// echo date('Y-m-d H:i:s', strtotime($first, 0));

// check for duration, adjust margin by derrived amount

// max reasonable height is 200-250px, need to take max duration, set to 200-250px, check against min height, 
// set all others accordingly

$time_offsets = array();
$prev = null;
foreach ($posts as $_post) {
	$time_offsets['id_'.$_post->ID] = 0;
	if ($prev) {
		$prev_timestamp = strtotime($prev->post_date_gmt);
		$this_timestamp = strtotime($_post->post_date_gmt);
		$time_offsets['id_'.$prev->ID] = $this_timestamp - $prev_timestamp;
	}
	$prev = $_post;
}

foreach ($posts as $_post) {
	$long_ass_time = false;
	$time_offset = $time_offsets['id_'.$_post->ID];
	$margin = ceil($time_offset / 15000);
	if ($time_offset > (DAY_IN_SECONDS * 90)) {
		$long_ass_time = true;
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
		else if ($y == 1) {
			$lat = sprintf(__('1 Year, %s Months', 'threads'), $m);
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
	}
	else if ($margin > 200) {
		$margin = 200;
	}
	$threads = wp_get_post_terms($_post->ID, 'threads');
	$intersect_with = array();
	foreach ($threads as $thread) {
		if ($thread->term_id != $term_id) {
			$intersect_with[] = $thread;
			if (!isset($intersects['id_'.$thread->term_id])) {
				$intersects['id_'.$thread->term_id] = $thread;
			}
		}
	}
?>
	<div class="threads-item" style="margin-bottom: <?php echo $margin; ?>px">
		<span class="date"><?php echo date('M j, Y', strtotime($_post->post_date)); ?></span>
		<a class="title" href="<?php echo get_permalink($_post->ID); ?>"><?php echo get_the_title($_post->ID); ?></a>
<?php
if (!empty($intersect_with)) {
?>
		<div class="intersects">
<?php
	$links = array();
	foreach ($intersect_with as $thread) {
		$post = cftpb_get_post($thread->term_id, $thread->taxonomy);
		$links[] = '<a href="'.get_permalink($post->ID).'">'.$thread->name.'</a>';
	}
	$links = implode(', ', $links);
	if (count($intersect_with) == 1) {
		printf(__('Also in thread: %s', 'threads'), $links);
	}
	else {
		printf(__('Also in threads: %s', 'threads'), $links);
	}
?>
		</div>
<?php
}
?>
	</div>
<?php
	if ($long_ass_time) {
?>
	<div class="threads-lat"><?php echo $lat; ?></div>
<?php
	}
}

?>
</div>
<?php

if (!empty($intersects)) {
?>
<h2><?php _e('Intersects', 'threads'); ?></h2>
<?php
	p($intersects);
}
