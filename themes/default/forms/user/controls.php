	<h2><?php echo __("Controls", "theme"); ?></h2>
	<br />
	<form action="<?php echo $route->url("update_self/"); ?>" method="post">
		<p>
			<label for="full_name"><?php echo __("Full Name", "theme"); ?></label>
			<input type="text" name="full_name" value="<?php echo fix($user->info("full_name"), "html"); ?>" id="full_name" tabindex="1" />
		</p>
		<p>
			<label for="email"><?php echo __("E-Mail", "theme"); ?></label>
			<input type="text" name="email" value="<?php echo fix($user->info("email"), "html"); ?>" id="email" tabindex="1" />
		</p>
		<p>
			<label for="website"><?php echo __("Website", "theme"); ?></label>
			<input type="text" name="website" value="<?php echo fix($user->info("website"), "html"); ?>" id="website" tabindex="1" />
		</p>
		<p>
			<label for="new_password1"><?php echo __("New Password?", "theme"); ?></label>
			<input type="password" name="new_password1" value="" id="new_password1" />
		</p>
		<p>
			<label for="new_password2"><?php echo __("Confirm", "theme"); ?></label>
			<input type="password" name="new_password2" value="" id="new_password2" />
		</p>

		<p><input name="submit" type="submit" id="submit" value="<?php echo __("Update", "theme"); ?>"></p>
	</form>