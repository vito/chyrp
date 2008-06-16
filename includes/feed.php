<?php
	$title = (!empty($_GET['title'])) ? ": ".html_entity_decode(urldecode($_GET['title'])) : "" ;
	echo "<".'?xml version="1.0" encoding="utf-8"?'.">\r";
?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<title><?php echo htmlspecialchars($config->name.$title, ENT_NOQUOTES, "utf-8"); ?></title>
	<subtitle><?php echo htmlspecialchars($config->description, ENT_NOQUOTES, "utf-8"); ?></subtitle>
	<id><?php echo self_url() ?></id>
	<updated><?php echo @date("c", $latest_timestamp); ?></updated>
	<link href="<?php echo self_url() ?>" rel="self" type="application/atom+xml" />
	<generator uri="http://chyrp.net/" version="<?php echo CHYRP_VERSION; ?>">Chyrp</generator>
<?php
	foreach ($posts->paginated as $post) {
		$title = htmlspecialchars($post->title(), ENT_NOQUOTES, "utf-8");
		fallback($title, ucfirst($post->feather)." Post #".$post->id);

		$updated = (substr($post->updated_at, 0, 4) == "0000") ? $post->created_at : $post->updated_at ;

		$tagged = substr(strstr($route->url("id/".$post->id."/"), "//"), 2);
		$tagged = str_replace("#", "/", $tagged);
		$tagged = preg_replace("/(".preg_quote(parse_url($post->url(), PHP_URL_HOST)).")/", "\\1,".when("Y-m-d", $updated).":", $tagged, 1);
?>
	<entry xml:base="<?php echo htmlspecialchars($post->url(), ENT_QUOTES, "utf-8"); ?>">
		<title type="html"><?php echo $title; ?></title>
		<id>tag:<?php echo $tagged; ?></id>
		<updated><?php echo when("c", $updated); ?></updated>
		<published><?php echo when("c", $post->created_at); ?></published>
		<link href="<?php echo htmlspecialchars($trigger->filter("feed_url", html_entity_decode($post->url()), $post), ENT_NOQUOTES, "utf-8"); ?>" />
		<author>
			<name><?php echo htmlspecialchars(fallback($post->user()->full_name, $post->user()->login, true), ENT_NOQUOTES, "utf-8"); ?></name>
<?php if (!empty($author_uri)): ?>
			<uri><?php echo $post->user()->website; ?></uri>
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