<?php

global $post;

if (has_post_thumbnail()) {
	$img_size = apply_filters('threads_featured_image_size', 'medium');
?>
	<div class="threads-featured-img"><?php the_post_thumbnail($img_size); ?></div>
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


@media (min--moz-device-pixel-ratio: 1.5),
       (-o-min-device-pixel-ratio: 3/2),
       (-webkit-min-device-pixel-ratio: 1.5),
       (min-device-pixel-ratio: 1.5),
       (min-resolution: 144dpi),
       (min-resolution: 1.5dppx) {
 
	.threads-timeline {
		background: transparent url(<?php echo cfth_asset_url('img/timeline@2x.png'); ?>) repeat-y;
		background-size: 105px 20px;
	}
	.threads-item .title {
		background: transparent url(<?php echo cfth_asset_url('img/bullet@2x.png'); ?>) left center no-repeat;
		background-size: 11px 11px;
	}
	.threads-lat {
		background: #fff url(<?php echo cfth_asset_url('img/lat@2x.png'); ?>) no-repeat;;
		background-size: 410px 110px;
	} 
}


</style>
<div class="threads-timeline">
<?php

$interescts = array();

foreach ($posts as $_post) {
?>
	<div class="threads-item" style="margin-bottom: <?php echo $_post->threads_data['margin']; ?>px">
		<span class="date"><?php echo date('M j, Y', strtotime($_post->post_date)); ?></span>
		<a class="title" href="<?php echo get_permalink($_post->ID); ?>"><?php echo get_the_title($_post->ID); ?></a>
<?php
	if (!empty($_post->threads_data['intersects'])) {
?>
		<div class="intersects">
<?php
		$links = cfth_thread_links($_post->threads_data['intersects']);
		$links = implode(', ', $links);
		if (count($_post->threads_data['intersects']) == 1) {
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
	if ($_post->threads_data['lat']) {
?>
	<div class="threads-lat"><?php echo $_post->threads_data['lat_text']; ?></div>
<?php
	}
}

?>
</div>
