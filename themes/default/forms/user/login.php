	<h2><?php echo __("Log In", "theme"); ?></h2>
	<br />
	<form action="<?php echo $route->url("process_login/"); ?>" method="post">
		<p>
			<label for="login"><?php echo __("Username", "theme"); ?></label>
			<input type="text" name="login" value="" id="login" />
		</p>
		<p>
			<label for="password"><?php echo __("Password", "theme"); ?></label>
			<input type="password" name="password" value="" id="password" />
		</p>

		<p><input name="submit" type="submit" id="submit" value="<?php echo __("Log In", "theme"); ?>"></p>
	</form>