<?php
	require "../../../includes/common.php";

	if (!$user->can("toggle_extensions"))
		exit;

	echo __("Adding `signature` column to comments table...", "comments")."<br />";
	$sql->query("ALTER TABLE `__comments` ADD  `signature` VARCHAR(32) NOT NULL AFTER `status`");
	echo __("Done!", "comments");
?>