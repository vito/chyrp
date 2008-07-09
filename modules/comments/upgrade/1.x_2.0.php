<?php
	require "../../../includes/common.php";

	if (!$visitor->group()->can("toggle_extensions"))
		exit;

	echo __("Adding `signature` column to comments table...", "comments")."<br /><br />";
	$sql->query("ALTER TABLE `__comments` ADD  `signature` VARCHAR(32) DEFAULT '' AFTER `status`");

	echo __("Adding `updated_at` column to comments table...", "comments")."<br /><br />";
	$sql->query("ALTER TABLE `__comments` ADD  `updated_at` DATETIME DEFAULT '0000-00-00 00:00:00' AFTER `created_at`");

	echo __("Adding `auto_reload_comments` and `enable_reload_comments` settings...", "comments")."<br /><br />";
	$config->set("auto_reload_comments", 30);
	$config->set("enable_reload_comments", false);

	echo __("Done!", "comments");
?>