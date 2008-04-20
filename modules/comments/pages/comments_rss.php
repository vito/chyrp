<?php
	$config = Config::current();
	$split_locale = explode("_", $config->locale);
	
	fallback($get_comments, $sql->query("select * from `".$sql->prefix."comments` where (`status` != 'denied' or (`status` = 'denied' and (`author_ip` = '".ip2long($_SERVER['REMOTE_ADDR'])."' or (`user_id` != '' and `user_id` = ".fix($current_user).")))) and `status` != 'spam' order by `created_at` desc"));
	
	fallback($latest_timestamp, $sql->query("select `created_at` from `".$sql->prefix."comments` where (`status` != 'denied' or (`status` = 'denied' and (`author_ip` = '".ip2long($_SERVER['REMOTE_ADDR'])."' or (`user_id` != '' and `user_id` = ".fix($current_user).")))) and `status` != 'spam' order by `created_at` desc limit 1")->fetchColumn());
	
	fallback($title, $config->name);
	
	fallback($url, $route->url("comments_rss/"));
	
	echo "<".'?xml version="1.0" encoding="utf-8"?'.">\r";
?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title>Comments at &#8220;<?php echo htmlspecialchars($title); ?>&#8221;</title>
		<atom:link href="<?php echo $url; ?>" rel="self" type="application/rss+xml" />
		<link><?php echo $config->url; ?></link>
		<description><?php echo htmlentities($config->description, ENT_NOQUOTES, "utf-8"); ?></description>
		<generator>http://chyrp.net/</generator>
		<language><?php echo $split_locale[0]; ?></language>
		<pubDate><?php echo when("r", $latest_timestamp); ?></pubDate>
		<docs>http://backend.userland.com/rss2</docs>
<?php
			while ($temp_comment = $get_comments->fetch()) {
				foreach ($temp_comment as $key => $val)
					if (!is_int($key))
						$comment->$key = $val;
				
				$trigger->call("rss_comment", $comment->id);
				
				if (($comment->status != "pingback" and !$comment->status != "trackback") and !$user->can("code_in_comments", $comment->user_id))
					$comment->body = strip_tags($comment->body, "<".join("><", $config->allowed_comment_html).">");
				
				$comment->body = $trigger->filter("markup_comment_text", $comment->body);
				
				$title = htmlspecialchars($post->title($comment->post_id));
?>
		<item>
			<title><?php echo ($title == "") ? "Post #".$comment->post_id : $title ; ?></title>
			<link><?php echo htmlentities($post->url($comment->post_id)."#comment_".$comment->id, ENT_NOQUOTES, "utf-8"); ?></link>
			<description><![CDATA[<?php echo $comment->body; ?>]]></description>
			<pubDate><?php echo when("r", $comment->created_at); ?></pubDate>
			<guid><?php echo htmlentities($post->url($comment->post_id), ENT_NOQUOTES, "utf-8"); ?></guid>
			<dc:creator><?php echo htmlentities($comment->author, ENT_NOQUOTES, "utf-8"); ?></dc:creator>
<?php $trigger->call("comments_rss_item", $comment->id); ?>
		</item>
<?php
			}
?>
	</channel>
</rss>
