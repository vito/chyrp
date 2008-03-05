<?php
	global $user;
	$route = Route::current();
?>
<form id="add_comment" action="<?php echo $route->url("add_comment/"); ?>" method="post" accept-charset="utf-8">
<?php if ($user->logged_in()): ?>
	<?php echo sprintf(__("Logged in as %s."), $user->info("full_name", null, $user->info("login"))); ?> <a href="<?php echo $route->url("logout/"); ?>"><?php echo __("Log Out &rarr;", "theme"); ?></a>
	<input type="hidden" name="author" value="<?php echo $user->info("full_name", null, $user->info("login")); ?>" id="author" />
	<input type="hidden" name="email" value="<?php echo $user->info("email"); ?>" id="email" />
	<input type="hidden" name="url" value="<?php echo $user->info("website"); ?>" id="url" />
<?php else: ?>
	<label for="author"><?php echo __("Your Name", "theme"); ?></label>
	<input type="text" name="author" value="" id="author" /><br />
	<label for="email"><?php echo __("Your E-Mail", "theme"); ?></label>
	<input type="text" name="email" value="" id="email" /><br />
	<label for="url"><?php echo __("Your Website", "theme"); ?></label>
	<input type="text" name="url" value="" id="url" /><br />
<?php endif; ?>
	<textarea name="body" rows="8" cols="40" class="wide"></textarea>

	<input type="hidden" name="post_id" value="<?php echo fix($post->id, "html"); ?>" id="post_id" />
	<p><input type="submit" value="<?php echo __("Speak", "theme"); ?>" /></p>
</form>
