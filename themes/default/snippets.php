<?php
	class Snippet {
		function read_more() {
			return __("Read More &raquo;", "theme");
		}
		function not_found() {
?>
		<h1><?php echo __("Not Found", "theme"); ?></h1>
		<div class="post body"><?php echo __("Sorry, but you are looking for something that isn't here.", "theme"); ?></div>
<?php
		}
		function no_posts($action) {
			switch($action) {
				default:
?>
		<h2><?php echo __("No Posts"); ?></h2>
		<div class="post body"><?php echo __("There aren't any posts here yet.", "theme"); ?></div>
<?php
					break;
				case "feather":
?>
		<h2><?php echo __("No Posts"); ?></h2>
		<div class="post body"><?php echo __("There aren't any posts of that Feather.", "theme"); ?></div>
<?php
					break;
				case "search":
?>
		<h2><?php echo __("No Results", "theme"); ?></h2>
		<div class="post body"><?php echo __("Your search did not match any posts.", "theme"); ?></div>
<?php
					break;
				case "drafts":
?>
		<h2><?php echo __("No Drafts", "theme"); ?></h2>
		<div class="post body"><?php echo __("There aren't any drafts yet.", "theme"); ?></div>
<?php
					break;
			}
		}
		function controls() {
			global $user;
			$config = Config::current();
			$route = Route::current();
			if ($user->can('add_post') or $user->can('add_page') or $user->can('view_draft') or $user->can('change_settings')):
?>
		<div class="controls" id="admin_bar"<?php if (isset($_COOKIE['chyrp_hide_admin'])) { echo ' style="display: none"'; } ?>>
			<ul>
				<?php if ($user->can('add_post')): ?><li><a id="add_post" href="<?php echo $config->url; ?>/admin/?action=write"><?php echo __("Write", "theme"); ?></a></li><?php endif; ?>
				<?php if ($user->can('add_page')): ?><li><a id="add_page" href="<?php echo $config->url; ?>/admin/?action=write&amp;sub=page"><?php echo __("Add Page", "theme"); ?></a></li><?php endif; ?>
				<?php if ($user->can('view_draft')): ?><li><a id="your_drafts" href="<?php echo $route->url("drafts/"); ?>"><?php echo __("Drafts", "theme"); ?></a></li><?php endif; ?>
				<?php if ($user->can('change_settings')): ?><li><a id="site_settings" href="<?php echo $config->url; ?>/admin/"><?php echo __("Admin", "theme"); ?></a></li><?php endif; ?>
				<li class="close"><a class="toggle_admin" href="<?php echo $route->url("toggle_admin/"); ?>"><?php echo __("Close", "theme"); ?></a></li>
			</ul>
		</div>
<?php
			endif;
		}
		function drafts_top() {
			echo '<h2>'.__("Your Drafts", "theme").'</h2><br />';
		}
		function feather_top() {
			$feather = $_GET['action'];
			echo '<h2>'.ucfirst($feather).'</h2><br />';
		}
		function tags_top() {
			echo '<h2>'.__("Tags", "theme").'</h2><br />';
		}
		function archive_top($year, $month) {
			echo '<h2>'.sprintf(__("Archive of %s", "theme"), @date("F Y", mktime(0, 0, 0, $month + 1, 0, $year))).'</h2><br />';
		}
		function search_top() {
			global $query;
			echo '<h2>'.sprintf(__("Search results for &#8220;%s&#8221;", "theme"), fix(urldecode($query), "html")).'</h2><br />';
		}
		function tag_view_top() {
			global $tag_name;
			echo '<h2>'.sprintf(__("Posts tagged with &#8220;%s&#8221;", "theme"), fix(urldecode($tag_name), "html")).'</h2><br />';
		}
		function draft_view_top() {
			echo "<div class=\"notice\" onClick=\"$(this).fadeOut('fast')\">".__("This post is a draft.", "theme")."<span class=\"sub\">".__("(click to hide)", "theme")."</span></div>";
		}
		function above_post_added() {
			echo "<div class=\"success\" onClick=\"$(this).fadeOut('fast')\">".__("Post added.", "theme")."<span class=\"sub\">".__("(click to hide)", "theme")."</span></div>";
		}
		function above_post_edited() {
			echo "<div class=\"success\" onClick=\"$(this).fadeOut('fast')\">".__("Post updated.", "theme")."<span class=\"sub\">".__("(click to hide)", "theme")."</span></div>";
		}
		function archive_month($month, $url) {
			echo '<h2><a href="'.$url.'">'.$month.'</a></h2>';
		}
		function archive_list_wrapper() {
			return "<ul>{LIST}</ul><br />";
		}
		function archive_list_item() {
			global $post;
			echo "\t<li>".when("d", $post->created_at).": <a href=\"".$post->url($post->id)."\">".normalize($post->title($post->id))."</a></li>\n";
		}
		function above_post() {
			global $post, $viewing;
			if ($viewing) {
				$post->next_link("(name) &rarr;", "right");
				$post->prev_link();
			}
?>
				<div class="post<?php if ($post->pinned) echo " pinned"; ?>" id="post_<?php echo $post->id; ?>">
					<div class="<?php echo $post->feather; ?> target">
<?php if ($post->pinned) echo '						<span class="pinned_text">'.__("pinned", "theme").'</span>'; ?>
<?php
		}
		function below_post() {
			global $theme, $paginate, $post, $current_user, $viewing, $last, $comment, $comments;
			$config = Config::current();
			$sql = SQL::current();
?>
<?php if (module_enabled("paging")): ?>
						<?php if ($viewing and $post->next_page): ?>
						    <a class="right" href="<?php echo next_page_url(); ?>">Next &rarr;</a>
						<?php endif; ?>
						<?php if ($viewing and $post->prev_page): ?>
						    <a class="left" href="<?php echo prev_page_url(); ?>">&larr; Previous</a>
						<?php endif; ?>
<?php endif; ?>
						<div class="clear"></div>
						<span class="info">
							<strong><a href="<?php echo $post->url(); ?>"><?php echo when('F jS, Y', $post->created_at); ?></a></strong>
<?php if (module_enabled("comments")): ?>
							<em>/</em> <?php post_comments($post->id); ?>
<?php endif; ?>
<?php if (module_enabled("tags")): ?>
							<?php list_post_tags($post->id, __(" <em>/</em> Tags: ", "theme")); ?>
<?php endif; ?>
							<em>/</em> <a href="<?php echo $config->url."/includes/trackback.php?id=".$post->id; ?>"><?php echo __("Trackback", "theme"); ?></a>
							<?php $post->edit_link(null, ' <em>/</em> '); ?>
							<?php $post->delete_link(null, ' <em>/</em> '); ?>
						</span>
						<br class="clear" />
					</div>
<?php if (!$last) echo "					<br />\n"; ?>
				</div>
<?php
			if (!$viewing or !module_enabled("comments")) return;
?>
				<br />
				<h1 id="comments"><?php echo sprintf(__("Comments on &#8220;%s&#8221;", "theme"), $post->title()); ?></h1>
<?php if (strip_tags(post_comments($post->id, "%", false, false)) != "0"): ?>
				<ol class="comments" id="comments_<?php echo $post->id; ?>">
<?php $comments->show($post->id); ?>
				</ol>
<?php endif; ?>
<?php
	$get_last_comment = $sql->query("select `id` from `".$sql->prefix."comments` where `post_id` = ".$sql->quote($post->id)." and (`status` != 'denied' or (`status` = 'denied' and (`author_ip` = '".ip2long($_SERVER['REMOTE_ADDR'])."' or `user_id` = ".$sql->quote($current_user)."))) and `status` != 'spam' order by `created_at` desc limit 1");
	$last_comment = ($get_last_comment->rowCount() > 0) ? $get_last_comment->fetchColumn() : 0 ;
?>
				<input type="hidden" name="last_comment" value="<?php echo $last_comment; ?>" id="last_comment" />
				<?php $paginate->next_link(__("Next &rarr;", "theme"), "right", "comments_page"); ?>
				<?php $paginate->prev_link(__("&larr; Previous", "theme"), "prev_page", "comments_page"); ?>
				<br />
				<br />
<?php
			if ($comment->user_can($post->id))
				$theme->load("forms/comment/new", array("post" => $post));
		}
	}
	
	function change_field_to_search() {
		$config = Config::current();
?>
	if ($.browser.safari) {
		document.getElementById("search").type = "search"
		$("input#search").attr("results", "5").attr("placeholder", "<?php echo __("Search...", "theme"); ?>").attr("autosave", "com.<?php echo sanitize($config->name, true); ?>.search")
	}
<?php
	}
	
	# New module system does not yet have a way for themes to plug in to it.
	#$trigger->add("javascript_domready", "change_field_to_search");
