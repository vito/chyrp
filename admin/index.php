<?php
	define('ADMIN', true);
	require_once "../includes/common.php";

	# Should the Manage tab be shown?
	$show_manage_tab = false;
	$manage_pages = array("post", "page", "user", "group");
	$manage_pages = $trigger->filter("show_admin_manage_tab", $manage_pages);
	foreach($manage_pages as $type)
		if ($user->can("edit_".$type) or $user->can("delete_".$type)) {
			if (!isset($can_manage))
				$can_manage = $type;
			$show_manage_tab = true;
		}

	$allowed = ($user->can("add_post") or $user->can("add_page") or $user->can("change_settings") or $show_manage_tab);

	if (!$allowed)
		error(__("Access Denied"), __("You are not allowed to view this area."));

	$action = (isset($_GET['action'])) ? strip_tags($_GET['action']) : "write" ;
	$sub = (isset($_GET['sub'])) ? strip_tags($_GET['sub']) : null ;

	# I know I could just do __(ucfirst($whatever)) with these, but
	# doing it this way lets my gettext scanner live a little easier.
	$actions = array("write" => __("Write"),
	                 "settings" => __("Settings"),
	                 "manage" => __("Manage"),
	                 "extend" => __("Extend"),
	                 "edit" => __("Edit"),
	                 "delete" => __("Delete"));
	$subs = array("website" => __("Website"),
	              "syndication" => __("Syndication"),
	              "routes" => __("Routes"),
	              "post" => __("Posts"),
	              "page" => __("Pages"),
	              "user" => __("Users"),
	              "group" => __("Groups"),
	              "modules" => __("Modules"),
	              "feathers" => __("Feathers"),
	              "themes" => __("Themes"));
	$actions = $trigger->filter("admin_page_titles", $actions);
	$subs = $trigger->filter("admin_sub_page_titles", $subs);

	if (!isset($_GET['action']) and !isset($_GET['sub']))
		if (!$user->can("add_post") and !$user->can("add_page"))
			if ($user->can("change_settings"))
				$route->redirect(url("settings", null, true));
			elseif ($show_manage_tab)
				if ($user->can("edit_post") or $user->can("delete_post"))
					$route->redirect("/admin/?action=manage");
				else
					$route->redirect("/admin/?action=manage&sub=".$can_manage);
		elseif ($user->can("add_page"))
			$route->redirect(url("write", "page", true));

	if ($action == "manage" and !isset($_GET['sub']) and $show_manage_tab)
		if (!$user->can("edit_post") and !$user->can("delete_post"))
			$route->redirect("/admin/?action=manage&sub=".$can_manage);

	function url($action, $sub = null, $return = false) {
		$config = Config::current();
		$url = $config->url."/admin/?action=".$action;
		if (!is_null($sub)) $url.= "&amp;sub=".$sub;
		if ($return) return $url;
		echo $url;
	}
	function admin_selected($the_action, $the_sub = null, $return = false) {
		global $action, $sub;
		if ((!is_null($the_sub) and ($sub == $the_sub)) or (is_null($the_sub)) and ($action == $the_action))
			if ($return)
				return ' class="selected"';
			else
				echo ' class="selected"';
	}
	$action_up = str_replace(array_keys($actions), array_values($actions), $action);
	$sub_title = (isset($sub)) ? ": ".ucfirst(str_replace(array_keys($subs), array_values($subs), $sub)) : "" ;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title><?php echo $config->name.": ".$action_up.$sub_title; ?></title>
		<link rel="stylesheet" href="<?php echo (file_exists(THEME_DIR.'/stylesheets/admin.css')) ? $config->url.'/themes/'.$config->theme.'/stylesheets/admin.css' : 'style.css'; ?>" type="text/css" media="screen" charset="utf-8" />
		<script src="<?php echo $config->url; ?>/includes/lib/jquery.js" type="text/javascript" charset="utf-8"></script>
		<script src="<?php echo $config->url; ?>/admin/js/ifixpng.js" type="text/javascript" charset="utf-8"></script>
		<script src="<?php echo $config->url; ?>/admin/js/interface.js" type="text/javascript" charset="utf-8"></script>
		<script src="<?php echo $config->url; ?>/admin/js/javascript.php?action=<?php echo $action; ?>&amp;sub=<?php echo $sub; ?>" type="text/javascript" charset="utf-8"></script>
<?php $trigger->call("admin_head"); ?>
	</head>
	<body>
		<div class="header">
			<a class="view" href="<?php echo $config->url; ?>"><?php echo __("View Site &raquo;"); ?></a>
			<h1><?php echo $config->name; ?></h1>
		</div>
		<div class="main-nav">
			<ul>
<?php if ($user->can("add_post") or $user->can("add_page")): ?>
				<li<?php admin_selected('write'); ?>><a href="<?php url('write'); ?>"><?php echo __("Write"); ?></a></li>
<?php endif; ?>
<?php if ($show_manage_tab): ?>
				<li<?php admin_selected('manage'); ?><?php admin_selected('edit'); ?><?php admin_selected('delete'); ?>><a href="<?php url('manage'); ?>"><?php echo __("Manage"); ?></a></li>
<?php endif; ?>
<?php if ($user->can("change_settings")): ?>
				<li<?php admin_selected('settings'); ?>><a href="<?php url('settings'); ?>"><?php echo __("Settings"); ?></a></li>
<?php endif; ?>
<?php if ($user->can("change_settings")): ?>
				<li<?php admin_selected('extend'); ?>><a href="<?php url('extend'); ?>"><?php echo __("Extend"); ?></a></li>
<?php endif; ?>
<?php $trigger->call("admin_nav"); ?>
			</ul>
		</div>
<?php
	if (file_exists("pages/".$action.".php")) {
		require "pages/".$action.".php";
	} else {
		$page_exists = false;

		foreach ($config->enabled_modules as $module) {
			if (file_exists(MODULES_DIR."/".$module."/pages/admin/".$action.".php")) {
				require MODULES_DIR."/".$module."/pages/admin/".$action.".php";
				$page_exists = true;
			}
		}

		foreach ($config->enabled_feathers as $feather) {
			if (file_exists(FEATHERS_DIR."/".$feather."/pages/admin/".$action.".php")) {
				require FEATHERS_DIR."/".$feather."/pages/admin/".$action.".php";
				$page_exists = true;
			}
		}

		if (file_exists(THEME_DIR."/pages/admin/".$action.".php")) {
			require THEME_DIR."/pages/admin/".$action.".php";
			$page_exists = true;
		}

		if (!$page_exists) {
?>
		<br />
		<div class="content">
			<h1><?php echo __("Not Found"); ?></h1>
			<?php echo __("Requested page does not exist."); ?>
		</div>
<?php
		}
	}
?>
		<div class="footer">
			Chyrp v<?php echo CHYRP_VERSION; ?> &copy; Alex Suraci, 2007
		</div>
	</body>
</html>
