<?php if (!isset($_GET['new'])): ?>
					<h1><?php echo __("Manage Users"); ?></h1>
<?php if (isset($_GET['updated'])): ?>
					<div class="success"><?php echo __("User updated."); ?></div>
<?php elseif (isset($_GET['deleted'])): ?>
					<div class="success"><?php echo __("User deleted."); ?></div>
<?php elseif (isset($_GET['added'])): ?>
					<div class="success"><?php echo __("User created."); ?></div>
<?php endif; ?>
					<h2><?php echo __("Need more detail?"); ?></h2>
					<form class="detail" action="index.php" method="get" accept-charset="utf-8">
						<input type="hidden" name="action" value="manage" />
						<input type="hidden" name="sub" value="user" />
<?php if ($visitor->group()->can("edit_user")): ?>
						<a href="<?php echo $config->url."/admin/?action=manage&amp;sub=user&amp;new"; ?>" class="button positive right">
							<img src="<?php echo $config->url."/admin/icons/add.png"; ?>" alt="add" /> <?php echo __("New User"); ?>
						</a>
<?php endif; ?>
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
<?php $trigger->call("admin_manage_users_column_header"); ?>
<?php if ($visitor->group()->can("edit_user") and $visitor->group()->can("delete_user")): ?>
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
	foreach ($get_users->fetchAll() as $user):
		$user = new User(null, array("read_from" => $user));
?>
						<tr>
							<td class="main"><?php echo $user->login; ?></td>
							<td><?php echo $user->full_name; ?></td>
							<td class="center"><a href="mailto:<?php echo $user->email; ?>"><?php echo $user->email; ?></a></td>
							<td class="center"><?php if (!empty($user->website)): ?><a href="<?php echo $user->website; ?>"><?php echo $user->website; ?></a><?php endif; ?></td>
<?php $trigger->call("admin_manage_users_column", $user); ?>
<?php if ($visitor->group()->can("edit_user")): ?>
							<td class="center"><?php echo $user->edit_link('<img src="icons/edit.png" alt="edit" />'); ?></td>
<?php endif; ?>
<?php if ($visitor->group()->can("delete_user")): ?>
							<td class="center"><?php echo $user->delete_link('<img src="icons/delete.png" alt="delete" />'); ?></td>
<?php endif; ?>
						</tr>
<?php
	endforeach;
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
<?php else: # (isset($_GET['new'])) ?>
				<h1><?php echo __("New User"); ?></h1>
				<form class="settings" action="<?php url("add_user"); ?>" method="post" accept-charset="utf-8">
					<h4><?php echo __("User Settings"); ?></h4>
					<p>
						<label for="login"><?php echo __("Username"); ?></label>
						<input class="text" type="text" name="login" value="" id="login" />
					</p>
					<p>
						<label for="password1"><?php echo __("New Password?"); ?></label>
						<input class="text" type="password" name="password1" value="" id="password1" />
					</p>
					<p>
						<label for="password2">&nbsp;<span class="sub"><?php echo __("(confirm)"); ?></span></label>
						<input class="text" type="password" name="password2" value="" id="password2" />
					</p>
					<p>
						<label for="group"><?php echo __("Group"); ?></label>
						<select name="group" id="group">
<?php $get_groups = $sql->query("select * from `{$sql->prefix}groups` order by `id` asc"); ?>
<?php while ($group = $get_groups->fetchObject()): ?>
							<option value="<?php echo $group->id; ?>"<?php selected($group->id, $config->default_group); ?>><?php echo $group->name; ?></option>
<?php endwhile; ?>
						</select>
					</p>

					<h4><?php echo __("More Information"); ?></h4>
					<p>
						<label for="full_name"><?php echo __("Full Name"); ?></label>
						<input class="text" type="text" name="full_name" value="" id="full_name" />
					</p>
					<p>
						<label for="email"><?php echo __("E-Mail"); ?></label>
						<input class="text" type="text" name="email" value="" id="email" />
					</p>
					<p>
						<label for="website"><?php echo __("Website"); ?></label>
						<input class="text" type="text" name="website" value="" id="website" />
					</p>
<?php $trigger->call("admin_new_user_form"); ?>

					<p style="margin-top: 2em">
						<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
						<button type="submit" accesskey="s" class="right">
							<?php echo __("Add User &rarr;"); ?>
						</button>
					</p>
					<br class="clear" />
				</form>
<?php endif; # (isset($_GET['new'])) ?>
