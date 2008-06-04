<?php
	if (!empty($_POST)) {
		$rss_feeds = $config->rss_feeds;
		$rss_feeds[$_POST['name']] = array("url" => $_POST['url'], "last_updated" => 0, "feather" => $_POST['feather'], "data" => array());
		$rss_feeds[$_POST['name']]["data"]["link"] = $_POST['link'];
		$rss_feeds[$_POST['name']]["data"]["title"] = $_POST['title'];
		$rss_feeds[$_POST['name']]["data"]["description"] = $_POST['content'];
		$config->set("rss_feeds", $rss_feeds);
	}
?>
		<ul class="sub-nav">
<?php if ($visitor->group()->can("edit_post") or $visitor->group()->can("delete_post")): ?>
			<li<?php selected("manage", "post"); ?>><a href="<?php url("manage", "post"); ?>"><?php echo __("Posts"); ?></a></li>
<?php endif; ?>
<?php if ($visitor->group()->can("edit_page") or $visitor->group()->can("delete_page")): ?>
			<li<?php selected("manage", "page"); ?>><a href="<?php url("manage", "page"); ?>"><?php echo __("Pages"); ?></a></li>
<?php endif; ?>
<?php if ($visitor->group()->can("edit_user") or $visitor->group()->can("delete_user")): ?>
			<li<?php selected("manage", "user"); ?>><a href="<?php url("manage", "user"); ?>"><?php echo __("Users"); ?></a></li>
<?php endif; ?>
<?php if ($visitor->group()->can("edit_group") or $visitor->group()->can("delete_group")): ?>
			<li<?php selected("manage", "group"); ?>><a href="<?php url("manage", "group"); ?>"><?php echo __("Groups"); ?></a></li>
<?php endif; ?>
			<?php $trigger->call("admin_manage_nav"); ?>
		</ul>
		<br class="clear" />
		<div class="content">
			<h1><?php echo __("New Aggregator", "rss"); ?></h1>
			<form action="index.php?action=new_aggregator" method="post">
				<p>
					<label for="name">Name</label>
					<input class="text" type="text" name="name" value="" id="name" />
				</p>
				<p>
					<label for="url">URL</label>
					<input class="text" type="text" name="url" value="" id="url" size="50" />
				</p>
				<p>
					<label for="feather">Feather</label>
					<select name="feather" id="feather">
<?php foreach ($config->enabled_feathers as $feather): ?>
						<option value="<?php echo $feather; ?>"><?php echo $feather; ?></option>
<?php endforeach; ?>
					</select>
				</p>
				<br />
				<h2>Import RSS values as...</h2>
				What fields should the RSS item attributes import as? (e.g. title, body for the "Text" feather)
				<br />
				<p>
					<label for="link">Link</label>
					<input class="text" type="text" name="link" value="" id="link" />
				</p>
				<p>
					<label for="title">Title</label>
					<input class="text" type="text" name="title" value="" id="title" />
				</p>
				<p>
					<label for="content">Content</label>
					<input class="text" type="text" name="content" value="" id="content" />
				</p>
				<br />
				<button type="submit" class="positive right" accesskey="s">
					<img src="<?php echo $config->chyrp_url."/admin/icons/success.png"; ?>" alt="" /> <?php echo __("Aggregate!", "rss"); ?>
				</button>
				<br class="clear" />
				<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
			</form>
		</div>