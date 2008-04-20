					<h1><?php echo __("Manage Posts"); ?></h1>
<?php if (isset($_GET['updated'])): ?>
					<div class="success"><?php echo __("Post updated."); ?> <a href="<?php $post = new Post($_GET['updated']); echo $post->url(); ?>"><?php echo __("View &raquo;"); ?></a></div>
<?php elseif (isset($_GET['deleted'])): ?>
					<div class="success"><?php echo __("Post deleted."); ?></div>
<?php endif; ?>
					<h2><?php echo __("Need more detail?"); ?></h2>
					<form class="detail" action="index.php" method="get" accept-charset="utf-8">
						<input type="hidden" name="action" value="manage" />
						<input type="hidden" name="sub" value="post" />
						<div class="left pad margin-right">
							<h3><?php echo __("Search&hellip;"); ?></h3>
							<input class="text" type="text" name="query" value="<?php if (!empty($_GET['query'])) echo fix($_GET['query'], "html"); ?>" id="query" /> <button type="submit" class="inline"><?php echo __("Search &rarr;"); ?></button>
						</div>
						<div class="left pad">
							<h3><?php echo __("Browse by month:"); ?></h3>
							<select name="month">
								<option value="">----------</option>
<?php foreach ($theme->list_archives() as $archive): ?>
								<option value="<?php echo $archive["year"]."-".month_to_number($archive["month"]); ?>"<?php if (!empty($_GET['month']) and $_GET['month'] == $archive["url"]) echo ' selected="selected"'; ?>><?php echo $archive["month"]." ".$archive["year"]; ?></option>
<?php endforeach; ?>
							</select>
							<button type="submit" class="inline"><?php echo __("Show &rarr;"); ?></button>
						</div>
						<br class="clear" />
					</form>
					<br class="clear" />
					<br />
					<h2><?php echo __("Last 25 Posts"); ?></h2>
					<table border="0" cellspacing="0" cellpadding="0" class="wide manage">
						<tr class="head">
							<th><?php echo __("Title"); ?></th>
							<th><?php echo __("Posted"); ?></th>
							<th><?php echo __("Author"); ?></th>
							<?php $trigger->call("admin_manage_posts_column_header"); ?>
<?php if ($user->can("edit_post") and $user->can("delete_post")): ?>
							<th colspan="2"></th>
<?php else: ?>
							<th></th>
<?php endif; ?>
						</tr>
<?php
	if (!empty($_GET['query']) and !empty($_GET['month'])) {
		$get_posts = $sql->select("posts",
		                          "*",
		                          "`xml` like :query and
		                           `created_at` like :month",
		                          "`created_at` desc",
		                          array(
		                          	":query" => "%".$_GET['query']."%",
		                          	":month" => $_GET['month']."%"
		                          ));
	} elseif (!empty($_GET['query'])) {
		$get_posts = $sql->select("posts",
		                          "*",
		                          "`xml` like :query",
		                          "`created_at` desc",
		                          array(
		                          	":query" => "%".$_GET['query']."%"
		                          ));
	} elseif (!empty($_GET['month'])) {
		$get_posts = $paginate->select("posts",
		                               "*",
		                               "`created_at` like :month",
		                               "`created_at` desc",
		                               25, "page",
		                               array(
		                               	":month" => $_GET['month']."%"
		                               ));
	} else {
		$get_posts = $paginate->select("posts",
		                               "*",
		                               null,
		                               "`created_at` desc",
		                               25);
	}
	$count = 1;

	foreach ($get_posts->fetchAll() as $post) {
		$post = new Post(null, array("read_from" => $post));
		$class = ($count == $get_posts->rowCount()) ? ' class="last '.$post->status.'"' : ' class="'.$post->status.'"' ;
?>
						<tr id="post_<?php echo $post->id; ?>"<?php echo $class; ?>>
							<td class="main"><a href="<?php echo $post->url($post->id); ?>"><?php echo $post->title($post->id); ?></a></td>
							<td><?php echo when("F jS, Y", $post->created_at); ?></td>
							<td class="center"><?php echo $user->info('full_name', $post->user_id, $user->info('login', $post->user_id)); ?></td>
							<?php $trigger->call("admin_manage_posts_column", $post->id); ?>
<?php if ($user->can("edit_post")): ?>
							<td class="center"><?php echo $post->edit_link('<img src="icons/edit.png" alt="edit" />'); ?></td>
<?php endif; ?>
<?php if ($user->can("delete_post")): ?>
							<td class="center"><?php echo $post->delete_link('<img src="icons/delete.png" alt="edit" />'); ?></td>
<?php endif; ?>
						</tr>
<?php
		$count++;
	}
?>
					</table>
<?php if ($paginate->prev_page() or $paginate->next_page()): ?>
					<br />
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
