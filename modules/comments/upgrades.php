<?php
	function add_signature_updated_at() {
		$check = query("SELECT * FROM `__comments`");
		while ($row = fetch_object($check))
			if (isset($row->signature))
				return;

		echo __("Adding `signature` column to comments table...", "comments").
		     test(query("ALTER TABLE `__comments` ADD  `signature` VARCHAR(32) DEFAULT '' AFTER `status`"));

		echo __("Adding `updated_at` column to comments table...", "comments").
		     test(query("ALTER TABLE `__comments` ADD  `updated_at` DATETIME DEFAULT '0000-00-00 00:00:00' AFTER `created_at`"));
	}

	add_config_if_not_exists("auto_reload_comments", 30);
	add_config_if_not_exists("enable_reload_comments", false);

	add_signature_updated_at();
?>