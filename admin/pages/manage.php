<?php
	$sub = (isset($_GET['sub'])) ? $_GET['sub'] : "post" ;

	$permission = $trigger->filter("manage_permission", $sub);

	$show_page = ($visitor->group->can("edit_".$permission) or $visitor->group->can("delete_".$permission));
	$show_page = ($trigger->exists("show_admin_manage_page")) ?
	             $trigger->filter("show_admin_manage_page", array($show_page, $sub), true) :
	             $show_page ;
?>
		<ul class="sub-nav">
<?php if ($visitor->group->can("edit_post") or $visitor->group->can("delete_post")): ?>
			<li<?php admin_selected("manage", "post"); ?>><a href="<?php url("manage", "post"); ?>"><?php echo __("Posts"); ?></a></li>
<?php endif; ?>
<?php if ($visitor->group->can("edit_page") or $visitor->group->can("delete_page")): ?>
			<li<?php admin_selected("manage", "page"); ?>><a href="<?php url("manage", "page"); ?>"><?php echo __("Pages"); ?></a></li>
<?php endif; ?>
<?php if ($visitor->group->can("edit_user") or $visitor->group->can("delete_user")): ?>
			<li<?php admin_selected("manage", "user"); ?>><a href="<?php url("manage", "user"); ?>"><?php echo __("Users"); ?></a></li>
<?php endif; ?>
<?php if ($visitor->group->can("edit_group") or $visitor->group->can("delete_group")): ?>
			<li<?php admin_selected("manage", "group"); ?>><a href="<?php url("manage", "group"); ?>"><?php echo __("Groups"); ?></a></li>
<?php endif; ?>
			<?php $trigger->call("admin_manage_nav"); ?>
		</ul>
		<br class="clear" />
		<div class="content">
<?php
	if (!$show_page) {
?>
			<h1><?php echo __("Access Denied"); ?></h1>
			<?php echo sprintf(__("You do not have sufficient privileges to manage %s."), $sub); ?>
<?php
	} else {
		if (file_exists("pages/manage/".$sub.".php")) {
			require "pages/manage/".$sub.".php";
		} else {
			$page_exists = false;

			foreach ($config->enabled_modules as $module) {
				if (file_exists(MODULES_DIR."/".$module."/pages/admin/manage/".$sub.".php")) {
					require MODULES_DIR."/".$module."/pages/admin/manage/".$sub.".php";
					$page_exists = true;
				}
			}

			foreach ($config->enabled_feathers as $feather) {
				if (file_exists(FEATHERS_DIR."/".$feather."/pages/admin/manage/".$sub.".php")) {
					require FEATHERS_DIR."/".$feather."/pages/admin/manage/".$sub.".php";
					$page_exists = true;
				}
			}

			if (file_exists(THEME_DIR."/pages/admin/manage/".$sub.".php")) {
				require THEME_DIR."/pages/admin/manage/".$sub.".php";
				$page_exists = true;
			}

			if (!$page_exists) {
?>
			<h2><?php echo __("Not Found"); ?></h2>
			<?php echo __("Requested page does not exist."); ?>
<?php
			}
		}
	}
?>
		</div>