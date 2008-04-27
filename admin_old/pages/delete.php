<?php
	$sub = (isset($_GET['sub'])) ? $_GET['sub'] : "post" ;
?>
		<ul class="sub-nav">
			<li<?php admin_selected('delete', 'post'); ?>><a href="<?php url("manage", "post"); ?>"><?php echo __("Posts"); ?></a></li>
			<li<?php admin_selected('delete', 'page'); ?>><a href="<?php url("manage", "page"); ?>"><?php echo __("Pages"); ?></a></li>
			<li<?php admin_selected('delete', 'user'); ?>><a href="<?php url("manage", "user"); ?>"><?php echo __("Users"); ?></a></li>
			<li<?php admin_selected('delete', 'group'); ?>><a href="<?php url("manage", "group"); ?>"><?php echo __("Groups"); ?></a></li>
			<?php $trigger->call("admin_manage_nav"); ?>
		</ul>
		<br class="clear" />
		<div class="content">
			<h2><?php echo sprintf(__("Are you sure you want to delete this %s?"), $sub); ?></h2>
<?php
	if (file_exists("pages/delete/".$sub.".php")) {
		require "pages/delete/".$sub.".php";
	} else {
		$page_exists = false;

		foreach ($config->enabled_modules as $module) {
			if (file_exists(MODULES_DIR."/".$module."/pages/admin/delete/".$sub.".php")) {
				require MODULES_DIR."/".$module."/pages/admin/delete/".$sub.".php";
				$page_exists = true;
			}
		}

		foreach ($config->enabled_feathers as $feather) {
			if (file_exists(FEATHERS_DIR."/".$feather."/pages/admin/delete/".$sub.".php")) {
				require FEATHERS_DIR."/".$feather."/pages/admin/delete/".$sub.".php";
				$page_exists = true;
			}
		}

		if (file_exists(THEME_DIR."/pages/admin/delete/".$sub.".php")) {
			require THEME_DIR."/pages/admin/delete/".$sub.".php";
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