<?php
	$theme->title = __("Tags", "tags");

	$trigger->call("tags_top");

	if ($sql->count("tags") > 0) {
		$tags = array();
		$clean = array();
		foreach($sql->select("tags")->fetchAll() as $tag) {
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

		$context = array();
		foreach ($tags as $tag => $count)
			$context[] = array("size" => (100 + (($count - $min_qty) * $step)),
			                   "popularity" => $count,
			                   "name" => $tag,
			                   "title" => sprintf(_p("%s post tagged with &quot;%s&quot;", "%s posts tagged with &quot;%s&quot;", $count), $count, $tag),
			                   "clean" => $tag2clean[$tag],
			                   "url" => $route->url("tag/".$tag2clean[$tag]."/"));

		$theme->load("content/tags", array("tag_cloud" => $context));
	}
?>
