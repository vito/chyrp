<?php
	function update_tags_structure() {
		$check = query("SELECT * FROM `__tags`");
		while ($row = fetch_object($check))
			if (isset($row->tags))
				return;

		$tags = array();
		$get_tags = query("SELECT * FROM `__tags`");
		echo __("Backing up tags...").test($get_tags);
		if (!$get_tags) return;

		while ($tag = fetch_object($get_tags)) {
			if (!isset($tags[$tag->post_id]))
				$tags[$tag->post_id] = array("normal" => array(), "clean" => array());

			$tags[$tag->post_id]["normal"][] = "{{".$tag->name."}}";
			$tags[$tag->post_id]["clean"][] = "{{".$tag->clean."}}";
		}

		# Drop the old table.
		$delete_tags = query("DROP TABLE `__tags`");
		echo __("Deleting old tags table...", "tags").test($delete_tags);
		if (!$delete_tags) return;

		# Create the new table.
		$tags_table = query("CREATE TABLE IF NOT EXISTS `__tags` (
				                 `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
				                 `tags` VARCHAR(250) DEFAULT '',
				                 `clean` VARCHAR(250) DEFAULT '',
				                 `post_id` INTEGER DEFAULT '0'
				             ) DEFAULT CHARSET=utf8");
		echo __("Creating new tags table...", "tags").test($tags_table);
		if (!$tags_table) return;

		foreach ($tags as $post => $tag)
			echo _f("Inserting tags for post #%s...", array($post), "tags").
			     test(query("INSERT INTO `__tags` SET
				             `tags` = '".fix(implode(",", $tag["normal"]))."',
				             `clean` = '".fix(implode(",", $tag["clean"]))."',
				             `post_id` = '".fix($post)."'"));
	}

	update_tags_structure();
?>