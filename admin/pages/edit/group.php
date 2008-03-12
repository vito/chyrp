<?php
	$get_group = $sql->select("groups",
	                          "*",
	                          "`id` = :id",
	                          "id",
	                          array(
	                          	":id" => $_GET['id']
	                          ));
	$the_group = $get_group->fetch();
?>
<form class="settings" id="group_edit" action="<?php url("update_group"); ?>" method="post" accept-charset="utf-8">
	<input type="hidden" name="id" value="<?php echo fix($_GET['id'], "html"); ?>" id="id" />
	<h4><?php echo __("Group Settings"); ?></h4>
	<p>
		<label for="name"><?php echo __("Name"); ?></label>
		<input class="text" type="text" name="name" value="<?php echo fix($the_group['name'], "html"); ?>" id="name" />
	</p>
	<h4><?php echo __("Permissions"); ?></h4>
	<p id="toggler">
		
	</p>
<?php
	foreach ($the_group as $column => $permission):
		if (is_int($column) or $column == "id" or $column == "name") continue;
		$checked = ($permission == 1) ? ' checked="checked"' : '' ;
?>
	<p>
		<label for="<?php echo $column; ?>"><?php echo camelize($column, true); ?></label>
		<input type="checkbox" name="permissions[<?php echo $column; ?>]" id="<?php echo $column; ?>"<?php echo $checked; ?> />
		&nbsp;
	</p>
<?php
	endforeach;
?>
	<p style="margin-top: 2em">
		<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
		<button type="submit" accesskey="s" class="right">
			<?php echo __("Update Group &rarr;"); ?>
		</button>
	</p>
	<br class="clear" />
</form>
