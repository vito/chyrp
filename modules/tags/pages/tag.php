<?php
	if (!count($posts))
		show_404();

	$tag = clean2tag($_GET['name']);

	$theme->title = sprintf(__("Posts tagged with \"%s\"", "tags"), $tag);
	$theme->load(array("content/tag", "content/index"), array("posts" => $posts, "tag" => $tag));
?>
