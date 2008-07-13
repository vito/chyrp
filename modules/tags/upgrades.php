<?php
	function update_tags_structure() {
		if (SQL::query("SELECT `tags` FROM `__tags`")) return;

		$tags = array();
		$get_tags = SQL::query("SELECT * FROM `__tags`");
		echo __("Backing up tags...").test($get_tags);
		if (!$get_tags) return;

		while ($tag = SQL::fetch($get_tags)) {
			if (!isset($tags[$tag->post_id]))
				$tags[$tag->post_id] = array("normal" => array(), "clean" => array());

			$tags[$tag->post_id]["normal"][] = "{{".$tag->name."}}";
			$tags[$tag->post_id]["clean"][] = "{{".$tag->clean."}}";
		}

		# Drop the old table.
		$delete_tags = SQL::query("DROP TABLE `__tags`");
		echo __("Dropping old tags table...", "tags").test($delete_tags);
		if (!$delete_tags) return;

		# Create the new table.
		$tags_table = SQL::query("CREATE TABLE IF NOT EXISTS `__tags` (
				                 `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
				                 `tags` VARCHAR(250) DEFAULT '',
				                 `clean` VARCHAR(250) DEFAULT '',
				                 `post_id` INTEGER DEFAULT '0'
				             ) DEFAULT CHARSET=utf8");
		echo __("Creating new tags table...", "tags").test($tags_table);
		if (!$tags_table) return;

		foreach ($tags as $post => $tag)
			echo _f("Inserting tags for post #%s...", array($post), "tags").
			     test(SQL::query("INSERT INTO `__tags` SET
				             `tags` = '".SQL::fix(implode(",", $tag["normal"]))."',
				             `clean` = '".SQL::fix(implode(",", $tag["clean"]))."',
				             `post_id` = '".SQL::fix($post)."'"));
	}

	update_tags_structure();
?>