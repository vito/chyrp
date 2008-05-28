<?php if (isset($_GET['invalid_defensio'])): ?>
			<div class="failure"><?php echo __("Invalid Defensio API key."); ?></div>
<?php endif; ?>
			<h1><?php echo __("Comment Settings", "comments"); ?></h1>
			<form class="settings" action="index.php?action=settings&amp;sub=comments" method="post">
				<p>
					<label for="defensio_api_key"><?php echo __("Defensio API Key", "comments"); ?><br /> <span class="sub"><?php echo __("(recommended)"); ?></span></label>
					<input class="text" type="text" name="defensio_api_key" value="<?php echo fix($config->defensio_api_key, "html"); ?>" id="defensio_api_key" /><br />
					<small><?php echo __("A very good spam blocker. Get an API key by signing up at <a href=\"http://wordpress.com/\">WordPress.com</a> &ndash; they'll send it to your email.", "comments"); ?></small>
				</p>
				<p>
					<label for="allowed_comment_html"><?php echo __("Allowed HTML Tags", "comments"); ?></label>
					<input class="text code" type="text" name="allowed_comment_html" value="<?php echo fix(join(", ", $config->allowed_comment_html), "html"); ?>" id="allowed_comment_html" /><br />
					<small><?php echo __("Format: <code>strong, blockquote, em</code>", "comments"); ?></small>
				</p>
				<p>
					<label for="default_comment_status"><?php echo __("Default Comment Status", "comments"); ?></label>
					<select name="default_comment_status">
						<option value="approved"<?php selected("approved", $config->default_comment_status); ?>><?php echo __("Approved", "comments"); ?></option>
						<option value="denied"<?php selected("denied", $config->default_comment_status); ?>><?php echo __("Denied", "comments"); ?></option>
					</select>
				</p>
				<p>
					<label for="comments_per_page"><?php echo __("Comments Per Page", "comments"); ?></label>
					<input class="text nowidth" type="text" name="comments_per_page" value="<?php echo fix($config->comments_per_page, "html"); ?>" size="2" id="comments_per_page" />
				</p>
				<br />
				<button type="submit" accesskey="s" class="right">
					<?php echo __("Update Settings &rarr;"); ?>
				</button>
				<br class="clear" />
				<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
			</form>
