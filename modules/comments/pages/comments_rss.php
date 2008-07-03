<?php
	$config = Config::current();
	$split_locale = explode("_", $config->locale);

	fallback($comments, Comment::find(array("limit" => 20,
	                                        "where" => array("`__comments`.`status` != 'spam'",
			                                                 "`__comments`.`status` != 'denied'"))));

	fallback($title, _f("Comments at &#8220;%s&#8221;", array(htmlspecialchars($config->name)), "comments"));

	$latest_timestamp = 0;
	foreach ($comments as $comment)
		if (strtotime($comment->created_at) > $latest_timestamp)
			$latest_timestamp = strtotime($comment->created_at);

	echo "<".'?xml version="1.0" encoding="utf-8"?'.">\r";
?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title><?php echo $title; ?></title>
		<atom:link href="<?php echo $route->url("comments_rss/"); ?>" rel="self" type="application/rss+xml" />
		<link><?php echo $config->url; ?></link>
		<description><?php echo htmlentities($config->description, ENT_NOQUOTES, "utf-8"); ?></description>
		<generator>http://chyrp.net/</generator>
		<language><?php echo $split_locale[0]; ?></language>
		<pubDate><?php echo when("r", $latest_timestamp); ?></pubDate>
		<docs>http://backend.userland.com/rss2</docs>
<?php
	foreach ($comments as $comment):
		$trigger->call("rss_comment", $comment->id);

		$group = ($comment->user_id) ? $comment->user()->group() : new Group(Config::current()->guest_group) ;
		if (($comment->status != "pingback" and $comment->status != "trackback") and !$group->can("code_in_comments"))
			$comment->body = strip_tags($comment->body, "<".join("><", $config->allowed_comment_html).">");

		$trigger->filter($comment->body, "markup_comment_text");

		$title = htmlspecialchars($comment->post()->title());
?>
		<item>
			<title><?php echo ($title == "") ? "Post #".$comment->post_id : $title ; ?></title>
			<link><?php echo htmlentities($comment->post()->url()."#comment_".$comment->id, ENT_NOQUOTES, "utf-8"); ?></link>
			<description><![CDATA[<?php echo $comment->body; ?>]]></description>
			<pubDate><?php echo when("r", $comment->created_at); ?></pubDate>
			<guid><?php echo htmlentities($comment->post()->url(), ENT_NOQUOTES, "utf-8"); ?></guid>
			<dc:creator><?php echo htmlentities($comment->author, ENT_NOQUOTES, "utf-8"); ?></dc:creator>
<?php $trigger->call("comments_rss_item", $comment->id); ?>
		</item>
<?php
			endforeach;
?>
	</channel>
</rss>
