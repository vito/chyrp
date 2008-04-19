			<h1><?php echo __("Route Settings"); ?></h1>
			<form class="settings" action="index.php?action=settings&amp;sub=routes" method="post">
				<p>
					<label for="clean_urls"><?php echo __("Clean URLs?"); ?></label>
					<input type="checkbox" name="clean_urls" id="clean_urls"<?php if ($config->clean_urls) echo ' checked="checked"'; ?> /><span class="sub"> <?php echo __("(recommended)"); ?></span>
					<small>
						<?php echo __("Gives your site prettier urls."); ?><br />
						<?php echo __("Requires .htaccess support (pretty common). If you're unsure, it's safe to test and find out. Just come back and disable it if it breaks your site."); ?>
					</small>
				</p>
				<p>
					<label for="post_url"><?php echo __("Post View URL"); ?><br /><span class="sub"> <?php echo __("(requires clean URLs)"); ?></span></label>
					<input class="text code" type="text" name="post_url" value="<?php echo fix($config->post_url, "html"); ?>" id="post_url" />
				</p>
				<div class="small">
					<strong><?php echo __("Syntax:"); ?></strong>
					<ul>
						<li><code>(year)</code>: <?php echo __("Year submitted"); ?><span class="sub"> <?php echo __("(ex. 2007)"); ?></span></li>
						<li><code>(month)</code>: <?php echo __("Month submitted"); ?><span class="sub"> <?php echo __("(ex. 12)"); ?></span></li>
						<li><code>(day)</code>: <?php echo __("Day submitted"); ?><span class="sub"> <?php echo __("(ex. 25)"); ?></span></li>
						<li><code>(hour)</code>: <?php echo __("Hour submitted"); ?><span class="sub"> <?php echo __("(ex. 03)"); ?></span></li>
						<li><code>(minute)</code>: <?php echo __("Minute submitted"); ?><span class="sub"> <?php echo __("(ex. 59)"); ?></span></li>
						<li><code>(second)</code>: <?php echo __("Second submitted"); ?><span class="sub"> <?php echo __("(ex. 30)"); ?></span></li>
						<li><code>(id)</code>: <?php echo __("Post ID"); ?></li>
						<li><code>(author)</code>: <?php echo __("Post author (username)"); ?><span class="sub"> <?php echo __("(ex. Alex)"); ?></span></li>
						<li><code>(clean)</code>: <?php echo __("The non-unique sanitized name"); ?><span class="sub"> <?php echo __("(ex. this_is_clean)"); ?></span></li>
						<li><code>(url)</code>: <?php echo __("The unique form of (clean)"); ?><span class="sub"> <?php echo __("(ex. this_one_is_taken_2)"); ?></span></li>
						<li><code>(feather)</code>: <?php echo __("The post's feather"); ?><span class="sub"> <?php echo __("(ex. text)"); ?></span></li>
						<li><code>(feathers)</code>: <?php echo __("The plural form of the post's feather"); ?><span class="sub"> <?php echo __("(ex. links)"); ?></span></li>
<?php $trigger->call('admin_settings_post_view_url'); ?>
					</ul>
				</div>
				<br />
				<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
				<button type="submit" accesskey="s" class="right">
					<?php echo __("Update Settings &rarr;"); ?>
				</button>
				<br class="clear" />
			</form>