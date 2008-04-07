<?php if (!isset($_GET['new'])): ?>
					<h1><?php echo __("Manage Groups"); ?></h1>
<?php if (isset($_GET['updated'])): ?>
					<div class="success"><?php echo __("Group updated."); ?></div>
<?php elseif (isset($_GET['deleted'])): ?>
					<div class="success"><?php echo __("Group deleted."); ?></div>
<?php elseif (isset($_GET['added'])): ?>
					<div class="success"><?php echo __("Group created."); ?></div>
<?php endif; ?>
					<h2><?php echo __("Need more detail?"); ?></h2>
					<form class="detail" action="index.php" method="get" accept-charset="utf-8">
						<input type="hidden" name="action" value="manage" />
						<input type="hidden" name="sub" value="group" />
<?php if ($user->can("add_group")): ?>
						<a href="<?php echo $config->url."/admin/?action=manage&amp;sub=group&amp;new"; ?>" class="button positive right">
							<img src="<?php echo $config->url."/admin/icons/add.png"; ?>" alt="add" /> <?php echo __("New Group"); ?>
						</a>
<?php endif; ?>
						<div class="pad">
							<h3><?php echo __("Search Groups for User&hellip;"); ?></h3>
							<input class="text" type="text" name="query" value="<?php if (!empty($_GET['query'])) echo fix($_GET['query'], "html"); ?>" id="query" /> <button type="submit" class="inline"><?php echo __("Search &rarr;"); ?></button>
						</div>
					</form>
					<br class="clear" />
					<br />
					<h2>Groups</h2>
<?php
	if (!empty($_GET['query'])) {
		$id = $sql->select("users",
		                   "group_id",
		                   "`login` like :login",
		                   "id",
		                   array(
		                   	":login" => "%".$_GET['query']."%"
		                   ))->fetchColumn();
		$get_groups = $paginate->select("groups",
		                                "*",
		                                "`id` = :id",
		                                "id",
		                                25, "page",
		                                array(
		                                	":id" => $id
		                                ));
	} else {
		$get_groups = $paginate->select("groups",
		                                "*",
		                                null,
		                                "`id` asc",
		                                25);
	}
	while ($the_group = $get_groups->fetch()) {
		$members = ($the_group["id"] == $config->guest_group) ?
		           sprintf(__("&#8220;%s&#8221; is the default group for guests"), $the_group["name"]) :
		           sprintf(_p("&#8220;%s&#8221; has %s member", "&#8220;%s&#8221; has %s members", $group->user_count($the_group["id"])), $the_group["name"], $group->user_count($the_group["id"])) ;
?>
					<div class="box">
						<h1>
							<span class="right">
								<?php if ($user->can("edit_group")) echo $group->edit_link($the_group["id"], '<img src="icons/edit.png" alt="edit" /> '.__("edit")); ?>
								<?php if ($user->can("delete_group")) echo $group->delete_link($the_group["id"], '<img src="icons/delete.png" alt="delete" /> '.__("delete")); ?>
							</span>
							<?php echo $members; ?>
						</h1>
					</div>
<?php
	}
	if (!$get_groups->rowCount() and !empty($_GET['query'])):
?>
					<h1><?php echo __("No Results"); ?></h1>
					<?php echo __("I can't find any groups with that user in them."); ?>
<?php
	elseif (!$get_groups->rowCount()):
?>
					<h1><?php echo __("No Groups"); ?></h1>
					<?php echo __("Strangely enough, there don't seem to be any groups. Way to go."); ?>
<?php
	endif;
?>
<?php if ($paginate->next_page()): ?>
					<a class="right button" href="<?php echo $paginate->next_page_url("page", false); ?>"><?php echo __("Next &raquo;"); ?></a>
<?php endif; ?>
<?php if ($paginate->prev_page()): ?>
					<a class="button" href="<?php echo $paginate->prev_page_url("page", false); ?>"><?php echo __("&laquo; Previous"); ?></a>
<?php endif; ?>
<?php if ($paginate->prev_page() or $paginate->next_page()): ?>
					<br class="clear" />
<?php endif; ?>
<?php else: # (isset($_GET['add'])) ?>
				<h1><?php echo __("New Group"); ?></h1>
				<form class="settings" id="new_group" action="<?php url("add_group"); ?>" method="post" accept-charset="utf-8">
					<h4><?php echo __("Group Settings"); ?></h4>
					<p>
						<label for="name"><?php echo __("Name"); ?></label>
						<input class="text" type="text" name="name" value="" id="name" />
					</p>
					<h4><?php echo __("Permissions"); ?></h4>
					<p id="toggler">
		
					</p>
<?php
	$get_columns = $sql->query("show columns from `".$sql->prefix."groups`");
	while ($column = $get_columns->fetch()):
		if (is_int($column) or $column["Field"] == "id" or $column["Field"] == "name") continue;
?>
					<p>
						<label for="<?php echo $column["Field"]; ?>"><?php echo camelize($column["Field"], true); ?></label>
						<input type="checkbox" name="permissions[<?php echo $column["Field"]; ?>]" id="<?php echo $column["Field"]; ?>" />
						&nbsp;
					</p>
<?php
	endwhile;
?>
					<p style="margin-top: 2em">
						<label>&nbsp;</label>
						<input type="submit" value="<?php echo __("Create &rarr;"); ?>" />
					</p>
					<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
				</form>
<?php endif; # (isset($_GET['add'])) ?>
