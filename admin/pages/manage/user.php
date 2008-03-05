					<h1><?php echo __("Manage Users"); ?></h1>
<?php if (isset($_GET['updated'])): ?>
					<div class="success"><?php echo __("User updated."); ?></div>
<?php elseif (isset($_GET['deleted'])): ?>
					<div class="success"><?php echo __("User deleted."); ?></div>
<?php endif; ?>
					<h2><?php echo __("Need more detail?"); ?></h2>
					<form class="detail" action="index.php" method="get" accept-charset="utf-8">
						<input type="hidden" name="action" value="manage" />
						<input type="hidden" name="sub" value="user" />
						<div class="pad">
							<h3><?php echo __("Search&hellip;"); ?></h3>
							<input class="text" type="text" name="query" value="<?php if (!empty($_GET['query'])) echo fix($_GET['query']); ?>" id="query" /> <button type="submit" class="inline"><?php echo __("Search &rarr;"); ?></button>
						</div>
					</form>
					<br class="clear" />
					<br />
					<h2><?php echo __("Users"); ?></h2>
					<table border="0" cellspacing="0" cellpadding="0" class="wide manage">
						<tr class="head">
							<th width="30%"><?php echo __("Login"); ?></th>
							<th width="15%"><?php echo __("Real&nbsp;Name"); ?></th>
							<th width="15%"><?php echo __("E-Mail"); ?></th>
							<th width="15%"><?php echo __("Website"); ?></th>
<?php if ($user->can("edit_user") and $user->can("delete_user")): ?>
							<th colspan="2" width="10%"></th>
<?php else: ?>
							<th width="10%"></th>
<?php endif; ?>
						</tr>
<?php
	if (!empty($_GET['query'])) {
		$get_users = $paginate->select("users",
		                               "*",
		                               "`login` like :query or
		                                `email` like :query or
		                                `website` like :query or
		                                `full_name` like :query",
		                               "`id` desc",
		                               25, "page",
		                               array(
		                               	":query" => "%".$_GET['query']."%"
		                               ));
	} else {
		$get_users = $paginate->select("users",
		                               "*",
		                               null,
		                               "`id` asc",
		                               25);
	}
	while ($temp_user = $get_users->fetch()):
		foreach ($temp_user as $key => $val) $user->$key = $val;
?>
						<tr>
							<td class="main"><?php echo $user->login; ?></td>
							<td><?php echo $user->full_name; ?></td>
							<td class="center"><a href="mailto:<?php echo $user->email; ?>"><?php echo $user->email; ?></a></td>
							<td class="center"><?php if (!empty($user->website)): ?><a href="<?php echo $user->website; ?>"><?php echo $user->website; ?></a><?php endif; ?></td>
<?php if ($user->can("edit_user")): ?>
							<td class="center"><?php echo $user->edit_link($user->id, '<img src="icons/edit.png" alt="edit" />'); ?></td>
<?php endif; ?>
<?php if ($user->can("delete_user")): ?>
							<td class="center"><?php echo $user->delete_link($user->id, '<img src="icons/delete.png" alt="delete" />'); ?></td>
<?php endif; ?>
						</tr>
<?php
	endwhile;
?>
					</table>
<?php if ($paginate->next_page()): ?>
					<a class="right button" href="<?php echo $paginate->next_page_url("page", false); ?>"><?php echo __("Next &raquo;"); ?></a>
<?php endif; ?>
<?php if ($paginate->prev_page()): ?>
					<a class="button" href="<?php echo $paginate->prev_page_url("page", false); ?>"><?php echo __("&laquo; Previous"); ?></a>
<?php endif; ?>
<?php if ($paginate->prev_page() or $paginate->next_page()): ?>
					<br class="clear" />
<?php endif; ?>
