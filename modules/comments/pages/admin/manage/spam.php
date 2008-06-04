					<h1><?php echo __("Manage Spam", "comments"); ?></h1>
<?php if (isset($_GET['purged'])): ?>
					<div class="success"><?php echo __("All spam deleted.", "comments"); ?></div>
<?php elseif (isset($_GET['deleted'])): ?>
					<div class="success"><?php echo __("Selected spam has been deleted.", "comments"); ?></div>
<?php elseif (isset($_GET['despammed'])): ?>
					<div class="success"><?php echo __("Selected comments have been de-spammed and approved.", "comments"); ?></div>
<?php elseif (isset($_GET['noneselected'])): ?>
					<div class="failure"><?php echo __("You didn't select any comments!", "comments"); ?></div>
<?php endif; ?>
					<h2><?php echo __("Need more detail?"); ?></h2>
					<form class="detail" action="index.php" method="get" accept-charset="utf-8">
						<input type="hidden" name="action" value="manage" />
						<input type="hidden" name="sub" value="spam" />
						<a href="<?php echo $config->chyrp_url."/admin/?action=purge_spam"; ?>" class="button negative right">
							<img src="<?php echo $config->chyrp_url."/admin/icons/deny.png"; ?>" alt="" /> <?php echo __("Delete All", "comments"); ?>
						</a>
						<div class="pad">
							<h3><?php echo __("Search&hellip;"); ?></h3>
							<input class="text" type="text" name="query" value="<?php if (!empty($_GET['query'])) echo fix($_GET['query'], "html"); ?>" id="query" />
							<button type="submit" class="inline"><?php echo __("Search &rarr;"); ?></button>
						</div>
					</form>
					<br class="clear" />
					<br />
					<h2><?php echo __("Last 25 Spam", "comments"); ?></h2>
					<form action="<?php url("manage_spam"); ?>" method="post" accept-charset="utf-8" id="spam_form">
						<table border="0" cellspacing="0" cellpadding="0" class="wide manage">
							<tr class="head">
								<th width="1%" id="checkbox_header"></th>
								<th><?php echo __("Author"); ?></th>
								<th><?php echo __("Post"); ?></th>
								<th width="40%"><?php echo __("Excerpt", "comments"); ?></th>
								<?php $trigger->call("admin_manage_comments_column_header"); ?>
								<th colspan="2"></th>
							</tr>
<?php
	if (!empty($_GET['query'])) {
		$get_comments = $sql->select("comments",
		                             "*",
		                             "`body` like :query and
		                              `status` = 'spam'",
		                             "`created_at` desc".
		                             25, "page",
		                             array(
		                                 ":query" => "%".$_GET['query']."%"
		                             ));
	} else {
		$get_comments = $paginate->select("comments",
		                                  "*",
		                                  "`status` = 'spam'",
		                                  "`created_at` desc",
		                                  25);
	}
	$count = 1;
	foreach ($get_comments->fetchAll() as $comment):
		$comment = new Comment($comment["id"], array("read_from" => $comment));
		$trigger->call("manage_comments");
		$class = ($count == $get_comments->rowCount()) ? ' class="last"' : "" ;
		$comment->body = strip_tags($comment->body, "<".join("><", $config->allowed_comment_html).">");
?>
							<tr<?php echo $class; ?>>
								<td><input type="checkbox" name="comments[<?php echo $comment->id; ?>]" id="comments[<?php echo $comment->id; ?>" /></td>
								<td class="center main"><?php echo $comment->author; ?></td>
								<td class="center"><a href="<?php echo $comment->post()->url(); ?>"><?php echo $comment->post()->title(); ?></a></td>
								<td><?php echo truncate($comment->body, 100); ?></td>
								<?php $trigger->call("admin_manage_comments_column", $comment->id); ?>
								<td class="center"><?php echo $comment->edit_link('<img src="icons/edit.png" />'); ?></td>
								<td class="center"><?php echo $comment->delete_link('<img src="icons/delete.png" />'); ?></td>
							</tr>
<?php
		$count++;
	endforeach;
?>
						</table>
						<br />
						<div class="buttons">
							<button type="submit" name="delete" class="negative right">
								<img src="<?php echo $config->chyrp_url."/admin/icons/delete.png"; ?>" alt="" /> <?php echo __("Delete Selected", "comments"); ?>
							</button>
							<button type="submit" name="despam" class="right">
								<img src="<?php echo $config->chyrp_url."/admin/icons/success.png"; ?>" alt="" /> <?php echo __("De-spam Selected", "comments"); ?>
							</button>
						</div>
						<br class="clear" />
						<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
					</form>
<?php if ($paginate->prev_page() or $paginate->next_page()): ?>
					<br  />
<?php endif; ?>
<?php if ($paginate->next_page()): ?>
					<a class="right button" href="<?php echo $paginate->next_page_url("page", false); ?>"><?php echo __("Next &raquo;"); ?></a>
<?php endif; ?>
<?php if ($paginate->prev_page()): ?>
					<a class="button" href="<?php echo $paginate->prev_page_url("page", false); ?>"><?php echo __("&laquo; Previous"); ?></a>
<?php endif; ?>
<?php if ($paginate->prev_page() or $paginate->next_page()): ?>
					<br class="clear" />
<?php endif; ?>
