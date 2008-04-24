<?php
	$sub = (isset($_GET['sub'])) ? $_GET['sub'] : "post" ;
	if (!$visitor->group()->can("edit_".$sub))
		error(__("Access Denied"), sprintf(__("You do not have sufficient privileges to edit %ss."), $sub));
?>
		<ul class="sub-nav">
<?php if ($visitor->group()->can("edit_post") or $visitor->group()->can("delete_post")): ?>
			<li<?php admin_selected('manage', 'post'); ?>><a href="<?php url("manage", "post"); ?>"><?php echo __("Posts"); ?></a></li>
<?php endif; ?>
<?php if ($visitor->group()->can("edit_page") or $visitor->group()->can("delete_page")): ?>
			<li<?php admin_selected('manage', 'page'); ?>><a href="<?php url("manage", "page"); ?>"><?php echo __("Pages"); ?></a></li>
<?php endif; ?>
<?php if ($visitor->group()->can("edit_user") or $visitor->group()->can("delete_user")): ?>
			<li<?php admin_selected('manage', 'user'); ?>><a href="<?php url("manage", "user"); ?>"><?php echo __("Users"); ?></a></li>
<?php endif; ?>
<?php if ($visitor->group()->can("edit_group") or $visitor->group()->can("delete_group")): ?>
			<li<?php admin_selected('manage', 'group'); ?>><a href="<?php url("manage", "group"); ?>"><?php echo __("Groups"); ?></a></li>
<?php endif; ?>
			<?php $trigger->call("admin_manage_nav"); ?>
		</ul>
		<br class="clear" />
		<div class="content">
<?php
	$action = ($sub == "page") ? "update_page" : "update_post" ;

	if ($sub == "post")
		$post = new Post($_GET['id'], array("filter" => false));

	if ($sub == "post" or $sub == "page"):
?>
			<form action="<?php url($action, $sub); ?>" id="edit_form" method="post" accept-charset="utf-8" enctype="multipart/form-data">
<?php
	endif;
?>
				<h1><?php echo sprintf(__("Edit %s"), ucfirst($sub)); ?></h1>
<?php
	if (file_exists("pages/edit/".$sub.".php")) {
		require "pages/edit/".$sub.".php";
	} else {
		$page_exists = false;

		foreach ($config->enabled_modules as $module) {
			if (file_exists(MODULES_DIR."/".$module."/pages/admin/edit/".$sub.".php")) {
				require MODULES_DIR."/".$module."/pages/admin/edit/".$sub.".php";
				$page_exists = true;
			}
		}

		foreach ($config->enabled_feathers as $feather) {
			if (file_exists(FEATHERS_DIR."/".$feather."/pages/admin/edit/".$sub.".php")) {
				require FEATHERS_DIR."/".$feather."/pages/admin/edit/".$sub.".php";
				$page_exists = true;
			}
		}

		if (file_exists(THEME_DIR."/pages/admin/edit/".$sub.".php")) {
			require THEME_DIR."/pages/admin/edit/".$sub.".php";
			$page_exists = true;
		}

		if (!$page_exists) {
?>
				<h2><?php echo __("Not Found"); ?></h2>
				<?php echo __("Requested page does not exist."); ?>
<?php
		}
	}
	if ($sub == "post" or $sub == "page"):
?>
				<br id="after_options" />
				<button type="submit" id="save" class="positive right" accesskey="s">
					<img src="<?php echo $config->url."/admin/icons/success.png"; ?>" alt="" /> <?php echo __("Save"); ?>
				</button>
				<br class="clear" />
				<br class="js_disabled" />
				<div id="more_options" class="more_options js_disabled">
<?php
		if ($sub != "page"):
		     edit_post_options($_GET['id']);
		else:
?>
					<p>
						<label for="pinned"><?php echo __("Show in pages list?"); ?></label>
						<input type="checkbox" name="show_in_list" id="show_in_list" tabindex="3"<?php checked($page->show_in_list); ?> />&nbsp;
					</p>
					<p>
						<label for="slug"><?php echo __("Slug"); ?></label>
						<input class="text" type="text" name="slug" value="<?php echo fix($page->url, "html"); ?>" id="slug" />
					</p>
					<p>
						<label for="parent_id"><?php echo __("Parent"); ?></label>
						<select name="parent_id" id="parent_id">
							<option value="0">(none)</option>
<?php
	$get_pages = $sql->query("select `id`, `title` from `".$sql->prefix."pages`
	                          where `id` != :id
	                          order by `id` asc",
	                         array(
	                             ":id" => $page->id
	                         ));
	while ($the_page = $get_pages->fetchObject()):
?>
							<option value="<?php echo $the_page->id; ?>"<?php selected($the_page->id, $page->parent_id); ?>><?php echo fix($the_page->title, "html"); ?></option>
<?php
	endwhile;
?>
						</select>
					</p>
					<?php $trigger->call("edit_page_options", $_GET['id']); ?>
<?php
		endif;
?>
					<div class="clear"></div>
				</div>
				<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
				<input type="hidden" name="id" value="<?php echo $_GET['id']; ?>" id="id" />
			</form>
<?php
	endif;
?>
		</div>