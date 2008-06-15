<?php
	if (!count($posts))
		show_404();

	$tag = clean2tag($_GET['name']);

	$theme->title = _f("Posts tagged with \"%s\"", array($tag), "tags");
	$theme->load(array("content/tag", "content/index"), array("posts" => $posts, "tag" => $tag));
?>
