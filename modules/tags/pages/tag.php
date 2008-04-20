<?php
	# This has to be separate or else it'll mess with the $get_posts results
	$tag_name = $sql->query("select `name` from `".$sql->prefix."tags`
	                         where `clean` = :clean",
	                        array(
	                        	":clean" => $tag
	                        ))->fetchColumn();

	if ($get_posts->rowCount()) {
		$theme->title = sprintf(__("Posts tagged with \"%s\"", "tags"), $tag_name);
		$theme->load("layout/header");

		$trigger->call("tag_view_top");

		$count = 1;
		$shown_dates = array();

		foreach ($get_posts->fetchAll() as $post) {
			$post = new Post($post['id']);

			$last = ($count == $get_posts->rowCount());
			$date_shown = in_array(when("m-d-Y", $post->created_at), $shown_dates);
			if (!in_array(when("m-d-Y", $post->created_at), $shown_dates))
				$shown_dates[] = when("m-d-Y", $post->created_at);

			$trigger->call("above_post");
			$theme->load("content/feathers/".$post->feather);
			$trigger->call("below_post");
			$count++;
		}
		$theme->load("layout/footer");
	} else
		show_404();
?>
