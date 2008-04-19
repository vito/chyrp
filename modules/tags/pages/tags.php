<?php
	$get_tags = $sql->query("select `name`, `post_id`, count(`".$sql->prefix."posts`.`id`) as `count`
	                         from `".$sql->prefix."tags`, `".$sql->prefix."posts`
	                         where
	                         	`post_id` = `".$sql->prefix."posts`.`id` and
	                         	".$private.$enabled_feathers."
	                         group by `name`
	                         order by rand() asc");
	
	$theme->title = __("Tags", "tags");
	$theme->load("layout/header");
	
	$trigger->call("tags_top");
	
	if ($get_tags->rowCount() > 0) {
		$tags = array();
		while ($tag = mysql_fetch_object($get_tags))
			$tags[$tag->name] = $tag->count;
		
		$max_qty = max(array_values($tags));
		$min_qty = min(array_values($tags));

		$spread = $max_qty - $min_qty;
		if (0 == $spread) {
			$spread = 1;
		}

		$step = 75 / $spread;
	
		foreach ($tags as $key => $value) {
			$size = 100 + (($value - $min_qty) * $step);
			$title = sprintf(_p("%s post tagged with &quot;%s&quot;", "%s posts tagged with &quot;%s&quot;", $value), $value, $key);
			$url = $sql->query("select `clean` from `".$sql->prefix."tags`
			                    where `name` = :name",
			                   array(
			                   	":name" => $key
			                   ))->fetchColumn();
			echo '<a class="tag" href="'.$route->url("tag/".$url."/").'" style="font-size: '.$size.'%" title="'.$title.'">'.$key.'</a> ';
		}
	
	} elseif ($theme->snippet_exists("no_tags")) {
		$trigger->call("no_tags");
	} else {
?>
<h2><?php echo __("No Tags", "tags"); ?></h2>
<?php echo __("There aren't any tags yet. Such a shame.", "tags"); ?>
<?php
	}
	$theme->load("layout/footer");
?>
