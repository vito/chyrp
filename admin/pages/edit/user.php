<?php

$edit_user = $sql->query("select * from `{$sql->prefix}users` where id = :id", array(":id" => $_GET['id']));
$edit_user = $edit_user->fetchObject();

?>
<form class="settings" action="<?php url("update_user"); ?>" method="post" accept-charset="utf-8">
	<input type="hidden" name="id" value="<?php echo fix($_GET['id'], "html"); ?>" id="id" />
	<h4><?php echo __("User Settings"); ?></h4>
	<p>
		<label for="login"><?php echo __("Username"); ?></label>
		<input class="text" type="text" name="login" value="<?php echo fix($edit_user->login, "html"); ?>" id="login" />
	</p>
	<p>
		<label for="new_password1"><?php echo __("New Password?"); ?></label>
		<input class="text" type="password" name="new_password1" value="" id="new_password1" />
	</p>
	<p>
		<label for="new_password2">&nbsp;<span class="sub"><?php echo __("(confirm)"); ?></span></label>
		<input class="text" type="password" name="new_password2" value="" id="new_password2" />
	</p>
	<p>
		<label for="group"><?php echo __("Group"); ?></label>
		<select name="group" id="group">
<?php $get_groups = $sql->query("select * from `".$sql->prefix."groups`
                                 order by `id` asc"); ?>
<?php while ($group = $get_groups->fetchObject()): ?>
			<option value="<?php echo $group->id; ?>"<?php selected($group->id, $edit_user->group_id); ?>><?php echo $group->name; ?></option>
<?php endwhile; ?>
		</select>
	</p>
	<h4><?php echo __("More Information"); ?></h4>
	<p>
		<label for="full_name"><?php echo __("Full Name"); ?></label>
		<input class="text" type="text" name="full_name" value="<?php echo fix($edit_user->full_name, "html"); ?>" id="full_name" tabindex="1" />
	</p>
	<p>
		<label for="email"><?php echo __("E-Mail"); ?></label>
		<input class="text" type="text" name="email" value="<?php echo fix($edit_user->email, "html"); ?>" id="email" tabindex="1" />
	</p>
	<p>
		<label for="website"><?php echo __("Website"); ?></label>
		<input class="text" type="text" name="website" value="<?php echo fix($edit_user->website, "html"); ?>" id="website" tabindex="1" />
	</p>
<?php $trigger->call("admin_edit_user_form", $edit_user); ?>
	<p style="margin-top: 2em">
		<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
		<button type="submit" accesskey="s" class="right">
			<?php echo __("Update User &rarr;"); ?>
		</button>
	</p>
	<br class="clear" />
</form>
