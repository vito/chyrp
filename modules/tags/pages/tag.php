<?php
	$tag = $sql->select("tags", "*", "`clean` = :clean", "id", array(":clean" => $_GET['name']), 1)->fetchObject();

	if (!count($posts))
		show_404();

	$theme->title = sprintf(__("Posts tagged with \"%s\"", "tags"), $tag->name);

	$file = ($theme->file_exists("content/tag")) ? "content/tag" : "content/index" ;
	$theme->load($file, array("posts" => $posts, "tag" => $tag));
?>
