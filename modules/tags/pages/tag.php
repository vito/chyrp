<?php
	# This has to be separate or else it'll mess with the $get_posts results
	$tag = $sql->query("select * from `".$sql->prefix."tags`
	                    where `clean` = :clean",
	                    array(
                            ":clean" => $tag
                        ))->fetchObject();

	if (!$get_posts->rowCount())
		show_404();

	$theme->title = sprintf(__("Posts tagged with \"%s\"", "tags"), $tag->name);

	$shown_dates = array();
	$posts = array();
	foreach ($get_posts->fetchAll() as $post) {
		$post = new Post(null, array("read_from" => $post));
		if (!$post->theme_exists()) continue;

		$post->date_shown = in_array(when("m-d-Y", $post->created_at), $shown_dates);
		if (!in_array(when("m-d-Y", $post->created_at), $shown_dates))
			$shown_dates[] = when("m-d-Y", $post->created_at);

		$posts[] = $post;
	}

	$file = ($theme->file_exists("content/tag")) ?
	        "content/tag" :
	        "content/index" ;
	$theme->load($file, array("posts" => $posts, "tag" => $tag));
?>
