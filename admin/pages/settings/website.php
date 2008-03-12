			<h1><?php echo __("Site Configuration"); ?></h1>
			<form class="settings" action="index.php?action=settings&amp;sub=website" method="post">
				<p>
					<label for="name"><?php echo __("Site Name"); ?></label>
					<input class="text" type="text" name="name" value="<?php echo fix($config->name, "html"); ?>" id="name" />
				</p>
				<p>
					<label for="description"><?php echo __("Description"); ?></label>
					<textarea name="description" rows="2" cols="40"><?php echo fix($config->description, "html"); ?></textarea>
				</p>
				<p>
					<label for="url"><?php echo __("Website URL"); ?></label>
					<input class="text" type="text" name="url" value="<?php echo fix($config->url, "html"); ?>" id="url" />
				</p>
				<p>
					<label for="email"><?php echo __("E-Mail Address"); ?></label>
					<input class="text" type="text" name="email" value="<?php echo fix($config->email, "html"); ?>" id="email" />
				</p>
				<p>
					<label for="can_register"><?php echo __("Registration"); ?></label>
					<input type="checkbox" name="can_register" id="can_register"<?php if ($config->can_register) echo ' checked="checked"'; ?> /> <?php echo __("Allow people to register"); ?>
				</p>
				<p>
					<label for="default_group"><?php echo __("Default User Group"); ?></label>
					<select name="default_group" id="default_group">
<?php $get_groups = $sql->query("select * from `".$sql->prefix."groups`
                                 order by `id` asc"); ?>
<?php while ($group = $get_groups->fetchObject()): ?>
						<option value="<?php echo $group->id; ?>"<?php selected($group->id, $config->default_group); ?>><?php echo $group->name; ?></option>
<?php endwhile; ?>
					</select>
				</p>
				<p>
					<label for="guest_group"><?php echo __("&#8220;Guest&#8221; Group"); ?></label>
					<select name="guest_group" id="guest_group">
<?php $get_groups = $sql->query("select * from `".$sql->prefix."groups`
                                 order by `id` asc"); ?>
<?php while ($group = $get_groups->fetchObject()): ?>
						<option value="<?php echo $group->id; ?>"<?php selected($group->id, $config->guest_group); ?>><?php echo $group->name; ?></option>
<?php endwhile; ?>
					</select>
				</p>
				<p>
					<label for="time_offset"><?php echo __("Time Offset"); ?></label>
					<input class="text" type="text" name="time_offset" value="<?php echo ($config->time_offset / 3600); ?>" id="time_offset" />
					<small>
						(server time: <?php echo @date("F jS, Y g:i A"); ?>)
					</small>
				</p>
				<p>
					<label for="locale"><?php echo __("Language"); ?></label>
					<select name="locale" id="locale">
<?php
	if ($open = opendir(INCLUDES_DIR."/locale/")) {
	 	while (($folder = readdir($open)) !== false) {
			$split = explode(".", $folder);
			if (end($split) == "mo") {
				$selected = ($config->locale == $split[0]) ? ' selected="selected"' : '' ;
				echo '									<option value="'.$split[0].'"'.$selected.'>'.lang_code($split[0]).'</option>';
			}
		}
		closedir($open);
	}
?>
					</select>
				</p>
				<p>
					<label for="posts_per_page"><?php echo __("Posts Per Page"); ?></label>
					<input class="text nowidth" type="text" name="posts_per_page" value="<?php echo fix($config->posts_per_page, "html"); ?>" size="2" id="posts_per_page" />
				</p>
				<br />
				<button type="submit" accesskey="s" class="right">
					<?php echo __("Update Settings &rarr;"); ?>
				</button>
				<br class="clear" />
				<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
			</form>
