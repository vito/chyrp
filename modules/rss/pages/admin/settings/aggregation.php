			<h1><?php echo __("Aggregation Settings", "comments"); ?></h1>
			<form class="settings" action="index.php?action=settings&amp;sub=aggregation" method="post">
				<a href="javascript:void(0)" class="button positive" onclick="newFeed(); return false;"><img src="<?php echo $config->chyrp_url; ?>/admin/icons/add.png" alt="add" /> Add RSS Feed</a>
				<br />
				<button type="submit" accesskey="s" class="right">
					<?php echo __("Update Settings &rarr;"); ?>
				</button>
				<br class="clear" />
				<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
			</form>