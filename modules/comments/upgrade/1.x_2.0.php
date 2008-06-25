<?php
	require "../../../includes/common.php";

	if (!$visitor->group()->can("toggle_extensions"))
		exit;

	echo __("Adding `signature` column to comments table...", "comments")."<br /><br />";
	$sql->query("ALTER TABLE `__comments` ADD  `signature` VARCHAR(32) NOT NULL AFTER `status`");

	echo __("Adding `auto_reload_comments` setting...", "comments")."<br /><br />";
	$config->set("auto_reload_comments", 0);

	echo __("Done!", "comments");
?>