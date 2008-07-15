<?php
	# ALTER TABLE is safe to use here, because all of these modifications
	# are only relevant to those ugprading from 1.x, which only used MySQL.
	function add_signature_updated_at() {
		if (!SQL::query("SELECT `signature` FROM `__comments`"))
			echo __("Adding `signature` column to comments table...", "comments").
			     test(SQL::query("ALTER TABLE `__comments` ADD  `signature` VARCHAR(32) DEFAULT '' AFTER `status`"));

		if (!SQL::query("SELECT `updated_at` FROM `__comments`"))
			echo __("Adding `updated_at` column to comments table...", "comments").
			    test(SQL::query("ALTER TABLE `__comments` ADD  `updated_at` DATETIME DEFAULT '0000-00-00 00:00:00' AFTER `created_at`"));
	}

	Config::fallback("auto_reload_comments", 30);
	Config::fallback("enable_reload_comments", false);

	add_signature_updated_at();
?>