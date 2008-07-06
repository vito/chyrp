<?php
	$title = (!empty($_GET['title'])) ? ": ".html_entity_decode(urldecode($_GET['title'])) : "" ;
	echo "<".'?xml version="1.0" encoding="utf-8"?'.">\r";
?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<title><?php echo htmlspecialchars($config->name.$title, ENT_NOQUOTES, "utf-8"); ?></title>
<?php if (!empty($config->description)): ?>
	<subtitle><?php echo htmlspecialchars($config->description, ENT_NOQUOTES, "utf-8"); ?></subtitle>
<?php endif; ?>
	<id><?php echo self_url() ?></id>
	<updated><?php echo date("c", $latest_timestamp); ?></updated>
	<link href="<?php echo self_url() ?>" rel="self" type="application/atom+xml" />
	<generator uri="http://chyrp.net/" version="<?php echo CHYRP_VERSION; ?>">Chyrp</generator>
<?php
	foreach ($posts->paginated as $post) {
		$title = safe($post->title());
		fallback($title, ucfirst($post->feather)." Post #".$post->id);

		$updated = ($post->updated) ? $post->updated_at : $post->created_at ;

		$tagged = substr(strstr($route->url("id/".$post->id."/"), "//"), 2);
		$tagged = str_replace("#", "/", $tagged);
		$tagged = preg_replace("/(".preg_quote(parse_url($post->url(), PHP_URL_HOST)).")/", "\\1,".when("Y-m-d", $updated).":", $tagged, 1);

		$url = $post->url();
?>
	<entry xml:base="<?php echo fix($post->url(), true); ?>">
		<title type="html"><?php echo $title; ?></title>
		<id>tag:<?php echo $tagged; ?></id>
		<updated><?php echo when("c", $updated); ?></updated>
		<published><?php echo when("c", $post->created_at); ?></published>
		<link href="<?php echo fix($trigger->filter($url, "feed_url", $post), true); ?>" />
		<author>
			<name><?php echo safe(fallback($post->user()->full_name, $post->user()->login, true)); ?></name>
<?php if (!empty($author_uri)): ?>
			<uri><?php echo safe($post->user()->website); ?></uri>
<?php endif; ?>
		</author>
		<content type="html">
			<?php echo safe($post->feed_content()); ?>
		</content>
<?php $trigger->call("feed_item", $post->id); ?>
	</entry>
<?php
	}
?></feed>