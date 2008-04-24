<?php
	echo "<".'?xml version="1.0" encoding="utf-8"?'.">\r";
?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<title><?php echo htmlspecialchars($config->name, ENT_NOQUOTES, "utf-8"); ?></title>
	<subtitle><?php echo htmlspecialchars($config->description, ENT_NOQUOTES, "utf-8"); ?></subtitle>
	<id><?php echo self_url() ?></id>
	<updated><?php echo when("c", $latest_timestamp); ?></updated>
	<link href="<?php echo self_url() ?>" rel="self" />
	<generator uri="http://chyrp.net/" version="<?php echo CHYRP_VERSION; ?>">Chyrp</generator>
<?php
	foreach ($posts as $post) {
		$title = htmlspecialchars($post->title(), ENT_NOQUOTES, "utf-8");
		fallback($title, ucfirst($post->feather)." Post #".$post->id);

		$author_uri = User::info("website", $post->user_id);

		$updated = (substr($post->updated_at, 0, 4) == "0000") ? $post->created_at : $post->updated_at ;

		$tagged = substr(strstr($route->url("id/".$post->id."/"), "//"), 2);
		$tagged = str_replace("#", "/", $tagged);
		$tagged = preg_replace("/(".preg_quote(parse_url($post->url(), PHP_URL_HOST)).")/", "\\1,".when("Y-m-d", $updated).":", $tagged, 1);
		$tagged = "tag:".$tagged;
?>
	<entry xml:base="<?php echo htmlspecialchars($post->url(), ENT_QUOTES, "utf-8"); ?>">
		<title type="html"><?php echo $title; ?></title>
		<id><?php echo $tagged; ?></id>
		<updated><?php echo when("c", $updated); ?></updated>
		<published><?php echo when("c", $post->created_at); ?></published>
		<link href="<?php echo htmlspecialchars($trigger->filter("feed_url", html_entity_decode($post->url())), ENT_NOQUOTES, "utf-8"); ?>" />
		<author>
			<name><?php echo htmlspecialchars(User::info("full_name", $post->user_id, User::info("login", $post->user_id)), ENT_NOQUOTES, "utf-8"); ?></name>
<?php if (!empty($author_uri)): ?>
			<uri><?php echo $author_uri; ?></uri>
<?php endif; ?>
		</author>
		<content type="html">
			<?php echo htmlspecialchars($post->feed_content(), ENT_NOQUOTES, "utf-8"); ?>
		</content>
<?php $trigger->call("feed_item", $post->id); ?>
	</entry>
<?php
	}
?></feed>