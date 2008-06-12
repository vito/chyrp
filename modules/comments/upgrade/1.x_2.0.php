<?php
	require "../../../includes/common.php";

	if (!$user->can("toggle_extensions"))
		exit;

	echo "Adding `signature column to `".$sql->prefix."comments`...<br />";
	$sql->query("ALTER TABLE `__comments` ADD  `signature` VARCHAR(32) NOT NULL AFTER `status`");
	echo "Done!";
?>