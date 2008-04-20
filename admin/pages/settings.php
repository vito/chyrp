<?php
	$sub = (isset($_GET['sub'])) ? $_GET['sub'] : "website" ;
	if (!$user->can("change_settings"))
		error(__("Access Denied"), __("You do not have sufficient privileges to change settings."));
?>
		<ul class="sub-nav">
			<li<?php admin_selected("settings", "website"); ?>><a href="<?php url("settings", "website"); ?>"><?php echo __("Website"); ?></a></li>
			<li<?php admin_selected("settings", "syndication"); ?>><a href="<?php url("settings", "syndication"); ?>"><?php echo __("Syndication"); ?></a></li>
			<li<?php admin_selected("settings", "routes"); ?>><a href="<?php url("settings", "routes"); ?>"><?php echo __("Routes"); ?></a></li>
<?php $trigger->call("admin_settings_nav"); ?>
		</ul>
		<br class="clear" />
		<div class="content">
<?php if (isset($_GET['updated'])): ?>
			<div class="success"><?php echo __("Settings updated."); ?></div>
<?php endif; ?>
<?php
	if (file_exists("pages/settings/".$sub.".php")) {
		require "pages/settings/".$sub.".php";
	} else {
		$page_exists = false;

		foreach ($config->enabled_modules as $module) {
			if (file_exists(MODULES_DIR."/".$module."/pages/admin/settings/".$sub.".php")) {
				require MODULES_DIR."/".$module."/pages/admin/settings/".$sub.".php";
				$page_exists = true;
			}
		}
		
		foreach ($config->enabled_feathers as $feather) {
			if (file_exists(FEATHERS_DIR."/".$feather."/pages/admin/settings/".$sub.".php")) {
				require FEATHERS_DIR."/".$feather."/pages/admin/settings/".$sub.".php";
				$page_exists = true;
			}
		}
		
		if (file_exists(THEME_DIR."/pages/admin/settings/".$sub.".php")) {
			require THEME_DIR."/pages/admin/settings/".$sub.".php";
			$page_exists = true;
		}

		if (!$page_exists) {
?>
			<h2><?php echo __("Not Found"); ?></h2>
			<?php echo __("Requested page does not exist."); ?>
<?php
		}
	}
?>
		</div>