	<h2><?php echo __("Register", "theme"); ?></h2>
	<form action="<?php echo $route->url("process_registration/"); ?>" method="post">
		<p>
			<label for="login"><?php echo __("Username", "theme"); ?></label>
			<input type="text" name="login" value="" id="login" />
		</p>
		<p>
			<label for="password1"><?php echo __("Password", "theme"); ?></label>
			<input type="password" name="password1" value="" id="password1" />
		</p>
		<p>
			<label for="password2"><?php echo __("Password (again)", "theme"); ?></label>
			<input type="password" name="password2" value="" id="password2" />
		</p>
		<p>
			<label for="email"><?php echo __("Valid E-Mail Address", "theme"); ?></label>
			<input type="text" name="email" value="" id="email" />
		</p>

		<p><input type="submit" value="<?php echo __("Register", "theme"); ?>"></p>
	</form>