<?php
	# TODO: Move this to Twig.
	$get_tags = $sql->query("select `name`, `post_id`, count(`__posts`.`id`) as `count`
	                         from `__tags`, `__posts`
	                         where
	                             `post_id` = `__posts`.`id` and
	                             ".$private.$enabled_feathers."
	                         group by `name`
	                         order by rand() asc");

	$theme->title = __("Tags", "tags");

	$trigger->call("tags_top");

	if ($get_tags->rowCount() > 0) {
		$tags = array();
		$clean = array();
		foreach(SQL::current()->query("select * from `__tags`")->fetchAll() as $tag) {
			$tags[] = $tag["tags"];
			$clean[] = $tag["clean"];
		}

		# array("{{foo}} {{bar}}", "{{foo}}") to "{{foo}} {{bar}} {{foo}}" to array("foo", "bar", "foo") to array("foo" => 2, "bar" => 1)
		$tags = array_count_values(explode(" ", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(" ", $tags))));
		$clean = array_count_values(explode(" ", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(" ", $clean))));
		$tag2clean = array_combine(array_keys($tags), array_keys($clean));

		$max_qty = max(array_values($tags));
		$min_qty = min(array_values($tags));

		$spread = $max_qty - $min_qty;
		if ($spread == 0)
			$spread = 1;

		$step = 75 / $spread;

		foreach ($tags as $tag => $count) {
			$size = 100 + (($count - $min_qty) * $step);
			$title = sprintf(_p("%s post tagged with &quot;%s&quot;", "%s posts tagged with &quot;%s&quot;", $count), $count, $tag);

			echo '<a class="tag" href="'.$route->url("tag/".$tag2clean[$tag]."/").'" style="font-size: '.$size.'%" title="'.$title.'">'.$tag.'</a> ';
		}

	} else {
?>
<h2><?php echo __("No Tags", "tags"); ?></h2>
<?php echo __("There aren't any tags yet. Such a shame.", "tags"); ?>
<?php
	}
?>
