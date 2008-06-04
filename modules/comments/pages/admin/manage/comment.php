<?php $status = (isset($_GET['status'])) ? $_GET['status'] : "" ; ?>
					<h1><?php echo __("Manage Comments", "comments"); ?></h1>
<?php if (isset($_GET['updated'])): ?>
					<div class="success"><?php echo __("Comment updated.", "comments"); ?></div>
<?php elseif (isset($_GET['deleted'])): ?>
					<div class="success"><?php echo __("Comment deleted.", "comments"); ?></div>
<?php elseif (isset($_GET['approved'])): ?>
					<div class="success"><?php echo __("Comment approved.", "comments"); ?></div>
<?php elseif (isset($_GET['denied'])): ?>
					<div class="success"><?php echo __("Comment denied.", "comments"); ?></div>
<?php elseif (isset($_GET['spammed'])): ?>
					<div class="success"><?php echo __("Comment marked as spam.", "comments"); ?></div>
<?php endif; ?>
					<h2><?php echo __("Need more detail?"); ?></h2>
					<form class="detail" action="index.php" method="get" accept-charset="utf-8">
						<input type="hidden" name="action" value="manage" />
						<input type="hidden" name="sub" value="comment" />
						<div class="pad left margin-right">
							<h3><?php echo __("Search&hellip;"); ?></h3>
							<input class="text" type="text" name="query" value="<?php if (!empty($_GET['query'])) echo fix($_GET['query'], "html"); ?>" id="query" />
							<button type="submit" class="inline"><?php echo __("Search &rarr;"); ?></button>
						</div>
						<div class="pad left">
							<h3><?php echo __("Show:", "comments"); ?></h3>
							<select name="status">
								<option value="all"<?php selected("all", $status); ?>><?php echo __("All", "comments"); ?></option>
								<option value="approved"<?php selected("approved", $status); ?>><?php echo __("Approved", "comments"); ?></option>
								<option value="denied"<?php selected("denied", $status); ?>><?php echo __("Denied", "comments"); ?></option>
								<option value="trackback"<?php selected("trackback", $status); ?>><?php echo __("Trackback", "comments"); ?></option>
								<option value="pingback"<?php selected("pingback", $status); ?>><?php echo __("Pingback", "comments"); ?></option>
							</select>
							<button type="submit" class="inline"><?php echo __("Show &rarr;"); ?></button>
						</div>
						<br class="clear" />
					</form>
					<br class="clear" />
					<br />
					<h2><?php echo __("Last 25 Comments", "comments"); ?></h2>
<?php
	if (!empty($_GET['status']) and !empty($_GET['query'])) {
		$status =  ($_GET['status'] == "all") ? "`status` != 'spam'" : "`status` = :status" ;
		$get_comments = $paginate->select("comments",
		                                  "*",
		                                  "`body` like :query and ".$status,
		                                  "`created_at` desc",
		                                  25, "page",
		                                  array(
		                                      ":query" => "%".$_GET['query']."%",
		                                      ":status" => $_GET['status']
		                                  ));
	} elseif (!empty($_GET['status'])) {
		$status =  ($_GET['status'] == "all") ? "`status` != 'spam'" : "`status` = :status" ;
		$get_comments = $paginate->select("comments",
		                                  "*",
		                                  $status,
		                                  "`created_at` desc",
		                                  25);
	} elseif (!empty($_GET['query'])) {
		$get_comments = $paginate->select("comments",
		                                  "*",
		                                  "`body` like :query and
		                                   `status` != 'spam'",
		                                  "`created_at` desc",
		                                  25, "page",
		                                  array(
		                                      ":query" => "%".$_GET['query']."%",
		                                      ":status" => $_GET['status']
		                                  ));
	} else {
		$get_comments = $paginate->select("comments",
		                                  "*",
		                                  "`status` != 'spam'",
		                                  "`created_at` desc",
		                                  25);
	}
	foreach ($get_comments->fetchAll() as $comment):
		$comment = new Comment($comment["id"], array("read_from" => $comment));
		$trigger->call("manage_comments");

		$comment->body = strip_tags($comment->body, "<".join("><", $config->allowed_comment_html).">");
?>
					<div class="box">
						<h1>
							<span class="right">
								<?php if ($visitor->group()->can("edit_comment")) echo $comment->edit_link('<img src="icons/edit.png" /> '.__("edit")); ?>
								<?php if ($visitor->group()->can("delete_comment")) echo $comment->delete_link('<img src="icons/delete.png" /> '.__("delete")); ?>
<?php if ($comment->status == "approved"): ?>
								<a href="<?php echo $config->chyrp_url."/admin/?action=deny_comment&amp;id=".$comment->id; ?>"><img src="icons/deny.png" /> <?php echo __("deny", "comments"); ?></a>
<?php elseif ($comment->status == "denied"): ?>
								<a href="<?php echo $config->chyrp_url."/admin/?action=approve_comment&amp;id=".$comment->id; ?>"><img src="icons/success.png" /> <?php echo __("approve", "comments"); ?></a>
<?php endif; ?>
								<a href="<?php echo $config->chyrp_url."/admin/?action=mark_spam&amp;id=".$comment->id; ?>"><img src="<?php echo $config->chyrp_url."/modules/comments/spam.png"; ?>" /> <?php echo __("spam", "comments"); ?></a>
							</span>
							<?php echo sprintf(__("<a href=\"mailto:%s\">%s</a> (%s)", "comments"), $comment->author_email, $comment->author, (($comment->author_ip == -1) ? "Local" : long2ip($comment->author_ip))); ?>
						</h1>
						<div class="excerpt">
							<?php echo truncate($comment->body, 250); ?>
							<br />
							<span class="sub">(<a href="<?php echo $comment->post()->url(); ?>"><?php echo $comment->post()->title() ?></a>)</span>
						</div>
					</div>
<?php
	endforeach;
?>
<?php if ($paginate->next_page()): ?>
					<a class="right button" href="<?php echo $paginate->next_page_url("page", false); ?>"><?php echo __("Next &raquo;"); ?></a>
<?php endif; ?>
<?php if ($paginate->prev_page()): ?>
					<a class="button" href="<?php echo $paginate->prev_page_url("page", false); ?>"><?php echo __("&laquo; Previous"); ?></a>
<?php endif; ?>
<?php if ($paginate->prev_page() or $paginate->next_page()): ?>
					<br class="clear" />
<?php endif; ?>
