<?php
	require "../../../includes/common.php";

	# Back up all the current tags.
	echo "If the upgrade fails, here's a backup:<br />\n";
	echo '<textarea rows="15" cols="100">';
	echo "CREATE TABLE `".$sql->prefix."tags` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(250) NOT NULL,
  `post_id` int(11) NOT NULL,
  `clean` varchar(250) NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8;\n\n";
	$tags = array();
	foreach ($sql->select("tags", "*", null)->fetchAll() as $tag) {
		if (!isset($tags[$tag["post_id"]]))
			$tags[$tag["post_id"]] = array("normal" => array(), "clean" => array());

		echo "INSERT INTO `".$sql->prefix."tags` VALUES (`post_id`, `name`, `clean`) (".$tag["post_id"].", '".$tag["name"]."', '".$tag["clean"]."')\n";
		$tags[$tag["post_id"]]["normal"][] = "{{".$tag["name"]."}}";
		$tags[$tag["post_id"]]["clean"][] = "{{".$tag["clean"]."}}";
	}
	echo "</textarea>\n<br /><br />\n";

	# Drop the old table.
	echo "Dropping current Tags database table...<br /><br />\n";
	$sql->query("DROP TABLE `__tags`");

	# Create the new table.
	echo "Creating new database table...<br /><br />\n";
	$sql->query("CREATE TABLE IF NOT EXISTS `__tags` (
			      `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
			      `tags` VARCHAR(250) DEFAULT '',
			      `clean` VARCHAR(250) DEFAULT '',
			      `post_id` INTEGER DEFAULT '0'
			     ) DEFAULT CHARSET=utf8");

	# Create the tags in the new format.
	echo "Inserting new tags... Executing the following:<br />\n";
	echo '<textarea rows="15" cols="100">';
	foreach ($tags as $post => $tag) {
		echo "INSERT INTO `".$sql->prefix."tags` VALUES (`tags`, `clean`, `post_id`) ('".implode(" ", $tag["normal"])."', '".implode(" ", $tag["clean"])."', ".$post.")\n";
		$sql->insert("tags",
		             array("tags" => ":tags",
		                   "clean" => ":clean",
		                   "post_id" => ":post_id"),
		            array(":tags" => implode(" ", $tag["normal"]),
		                  ":clean" => implode(" ", $tag["clean"]),
		                  ":post_id" => $post));
	}
	echo "</textarea>\n<br /><br />\n";

	echo "Done!";
?>