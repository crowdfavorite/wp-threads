<?php

if (!count($posts)) {
	return;
}

?>
<div class="threads-timeline">
<?php

$interescts = array();

foreach ($posts as $_post) {
?>
	<div class="threads-item" style="margin-bottom: <?php echo $_post->threads_data['margin']; ?>px">
		<span class="date"><?php echo date('M j, Y', strtotime($_post->post_date)); ?></span><a class="title" href="<?php echo get_permalink($_post->ID); ?>"><?php echo get_the_title($_post->ID); ?></a>
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
