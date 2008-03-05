			<h1><?php echo __("Syndication Settings"); ?></h1>
			<form class="settings" action="index.php?action=settings&amp;sub=syndication" method="post">
				<p>
					<label for="feed_items"><?php echo __("Feed Posts Limit"); ?></label>
					<input class="text nowidth" type="text" name="feed_items" value="<?php echo fix($config->feed_items, "html"); ?>" size="2" id="feed_items" />
				</p>
				<p>
					<label for="enable_trackbacking"><?php echo __("Enable Trackbacking"); ?></label>
					<input type="checkbox" name="enable_trackbacking" id="enable_trackbacking"<?php if ($config->enable_trackbacking) echo ' checked="checked"'; ?> />&nbsp;
					<small>
						<?php echo __("Trackbacking allows sites to notify you when they write a new entry, usually because they link to or reference yours."); ?>
					</small>
				</p>
				<p>
					<label for="send_pingbacks"><?php echo __("Send Pingbacks"); ?></label>
					<input type="checkbox" name="send_pingbacks" id="send_pingbacks"<?php if ($config->send_pingbacks) echo ' checked="checked"'; ?> />&nbsp;
					<small>
						<?php echo __("Attempts to notify sites linked to from your posts. It'll slow down things a bit when you submit them, depending on how many links you've got in it."); ?>
					</small>
				</p>
				<br />
				<button type="submit" accesskey="s" class="right">
					<?php echo __("Update Settings &rarr;"); ?>
				</button>
				<br class="clear" />
				<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
			</form>