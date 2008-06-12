<?php
	require "../../includes/common.php";

	# Back up all the current tags.
	$tags = array();
	foreach ($sql->select("tags", "*", null)->fetchAll() as $tag) {
		if (!isset($tags[$tag["post_id"]]))
			$tags[$tag["post_id"]] = array("normal" => array(), "clean" => array());

		$tags[$tag["post_id"]]["normal"][] = "{{".$tag["name"]."}}";
		$tags[$tag["post_id"]]["clean"][] = "{{".$tag["clean"]."}}";
	}

	# Drop the old table.
	$sql->query("DROP TABLE `__tags`");

	# Create the new table.
	$sql->query("CREATE TABLE IF NOT EXISTS `__tags` (
			      `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
			      `tags` VARCHAR(250) DEFAULT '',
			      `clean` VARCHAR(250) DEFAULT '',
			      `post_id` INTEGER DEFAULT '0'
			     ) DEFAULT CHARSET=utf8");

	# Create the tags in the new format.
	foreach ($tags as $post => $tag) {
		$sql->insert("__tags",
		             array("tags" => ":tags",
		                   "clean" => ":clean",
		                   "post_id" => ":post_id"),
		            array(":tags" => implode(" ", $tag["normal"]),
		                  ":clean" => implode(" ", $tag["clean"]),
		                  ":post_id" => $post));
	}

	echo "Done!";
?>