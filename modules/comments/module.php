<?php
	require_once "model.Comment.php";

	class Comments extends Module {
		function __construct() {
			$this->addAlias('metaWeblog_newPost_preQuery', 'metaWeblog_editPost_preQuery');
		}

		static function __install() {
			$visitor = Visitor::current();
			$sql = SQL::current();
			$sql->query("create table if not exists `".$sql->prefix."comments` (
			                 `id` int(11) not null auto_increment,
			                 `body` longtext not null,
			                 `author` varchar(250) not null default '',
			                 `author_url` varchar(128) not null default '',
			                 `author_email` varchar(128) not null default '',
			                 `author_ip` int(10) not null,
			                 `author_agent` varchar(255) not null default '',
			                 `status` enum('denied','approved','spam','trackback','pingback') not null default 'denied',
			                 `created_at` datetime not null,
			                 `post_id` int(11) not null,
			                 `user_id` int(11) not null,
			                 primary key	 (`id`)
			             ) default charset=utf8");
			$config = Config::current();
			$config->set("default_comment_status", "denied");
			$config->set("allowed_comment_html", array("strong", "em", "blockquote", "code", "pre", "a"));
			$config->set("comments_per_page", 25);
			$config->set("akismet_api_key", null);
			Group::add_permission("add_comment");
			Group::add_permission("add_comment_private");
			Group::add_permission("edit_comment");
			Group::add_permission("delete_comment");
			Group::add_permission("code_in_comments");
		}

		static function __uninstall($confirm) {
			$visitor = Visitor::current();

			if ($confirm) {
				$sql = SQL::current();
				$sql->query("drop table `".$sql->prefix."comments`");
			}

			$config = Config::current();
			$config->remove("default_comment_status");
			$config->remove("allowed_comment_html");
			$config->remove("comments_per_page");
			$config->remove("akismet_api_key");
			Group::remove_permission("add_comment");
			Group::remove_permission("add_comment_private");
			Group::remove_permission("edit_comment");
			Group::remove_permission("delete_comment");
			Group::remove_permission("code_in_comments");
		}

		static function show($id) {
			global $theme, $paginate, $comment;

			$config = Config::current();
			$sql = SQL::current();
			$visitor = Visitor::current();
			$get_comments = $paginate->select("comments", # table
			                                  "*", # fields
			                                  "`post_id` = :post_id and (
			                                       `status` != 'denied' or (
			                                           `status` = 'denied' and (
			                                               `author_ip` = :current_ip or (
			                                                   `user_id` != '' and
			                                                   `user_id` = :current_user
			                                               )
			                                           )
			                                       )
			                                   ) and
			                                   `status` != 'spam'", #where
			                                  "`created_at` asc", # order
			                                  $config->comments_per_page, "comments_page",
			                                  array(
			                                      ":post_id" => $id,
			                                      ":current_ip" => ip2long($_SERVER['REMOTE_ADDR']),
			                                      ":current_user" => $visitor->id
			                                  ));

			$count = 1;
			$shown_dates = array();
			$trigger = Trigger::current();
			while ($temp_comment = $get_comments->fetchObject()) {
				foreach ($temp_comment as $key => $val)
					if (!is_int($key))
						$comment->$key = $val;
				$trigger->call("show_comment", $comment->id);

				$last = ($count == $get_comments->rowCount());
				$date_shown = in_array(when("m-d-Y", $comment->created_at), $shown_dates);
				if (!in_array(when("m-d-Y", $comment->created_at), $shown_dates))
					$shown_dates[] = when("m-d-Y", $comment->created_at);

				if (($comment->status != "pingback" and !$comment->status != "trackback") and !$visitor->group->can("code_in_comments", $comment->user_id))
					$comment->body = strip_tags($comment->body, "<".join("><", $config->allowed_comment_html).">");

				$comment->body = $trigger->filter("markup_comment_text", $comment->body);
				$comment->is_author = (Post::info("user_id", $comment->post_id) == $comment->user_id);

				$theme->load("content/comment", array("comment" => $comment));

				$count++;
			}
		}

		static function route_add_comment() {
			global $comment;
			$visitor = Visitor::current();
			# If the user can't add a comment, and if they can add a private comment
			# but the post isn't set to private, or if $_POST is empty.
			if ((!$visitor->group->can("add_comment") and ($visitor->group->can("add_comment_private") and Post::info("status", $_POST['post_id']) != "private")) or empty($_POST))
				return;

			if ($_POST['author'] == "") error(__("Error"), __("Author can't be blank.", "comments"));
			if ($_POST['email'] == "") error(__("Error"), __("E-Mail address can't be blank.", "comments"));
			if ($_POST['body'] == "") error(__("Error"), __("Message can't be blank.", "comments"));
			$comment->create($_POST['author'],
			                 $_POST['email'],
			                 $_POST['url'],
			                 $_POST['body'],
			                 $_POST['post_id']);
		}

		static function admin_update_comment($action) {
			global $comment;
			$visitor = Visitor::current();
			if (!$visitor->group->can("edit_comment") or empty($_POST)) return;
			$timestamp = when("Y-m-d H:i:s", $_POST['created_at']);
			$comment->update($_POST['id'],
			                 $_POST['author'],
			                 $_POST['author_email'],
			                 $_POST['author_url'],
			                 $_POST['body'],
			                 $_POST['status'],
			                 $timestamp);
			if (isset($_POST['ajax']))
				exit("{ comment_id: ".$_POST['id']." }");
			$config = Config::current();
			$route = Route::current();
			$route->redirect("/admin/?action=manage&sub=comment&updated");
		}

		static function admin_delete_comment_real($action) {
			global $comment;
			$visitor = Visitor::current();
			if (!$visitor->group->can("delete_comment") or empty($_POST)) return;
			$comment->delete($_POST['id']);
			$config = Config::current();
			$route = Route::current();
			$route->redirect("/admin/?action=manage&sub=comment&deleted");
		}

		static function admin_mark_spam($action) {
			$visitor = Visitor::current();
			if (!$visitor->group->can("edit_comment")) return;

			$sql = SQL::current();
			$sql->query("update `".$sql->prefix."comments`
			             set `status` = 'spam'
			             where `id` = :id",
			            array(
			                ":id" => $_GET['id']
			            ));
			$config = Config::current();
			$route = Route::current();
			$route->redirect("/admin/?action=manage&sub=comment&spammed");
		}

		static function admin_approve_comment($action) {
			$visitor = Visitor::current();
			if (!$visitor->group->can("edit_comment")) return;

			$sql = SQL::current();
			$sql->query("update `".$sql->prefix."comments`
			             set `status` = 'approved'
			             where `id` = :id",
			            array(
			                ":id" => $_GET['id']
			            ));
			$config = Config::current();
			$route = Route::current();
			$route->redirect("/admin/?action=manage&sub=comment&approved");
		}

		static function admin_deny_comment($action) {
			$visitor = Visitor::current();
			if (!$visitor->group->can("edit_comment")) return;

			$sql = SQL::current();
			$sql->query("update `".$sql->prefix."comments`
			             set `status` = 'denied'
			             where `id` = :id",
			            array(
			                ":id" => $_GET['id']
			            ));

			$config = Config::current();
			$route = Route::current();
			$route->redirect("/admin/?action=manage&sub=comment&denied");
		}

		static function admin_manage_spam($action) {
			global $comment;
			$visitor = Visitor::current();
			$config = Config::current();
			$route = Route::current();
			if (empty($_POST['comments'])) $route->redirect("/admin/?action=manage&sub=spam&noneselected");
			if (isset($_POST['delete'])) {
				if (!$visitor->group->can("delete_comment")) return;
				foreach ($_POST['comments'] as $id => $value)
					$comment->delete($id);
				$route->redirect("/admin/?action=manage&sub=spam&deleted");
			}
			if (isset($_POST['despam'])) {
				if (!$visitor->group->can("edit_comment")) return;

				$sql = SQL::current();
				foreach ($_POST['comments'] as $id => $value)
					$sql->query("update `".$sql->prefix."comments`
					             set `status` = 'approved'
					             where `id` = :id",
					            array(
					                ":id" => $id
					            ));
				$route->redirect("/admin/?action=manage&sub=spam&despammed");
			}
			$route->redirect("/admin/?action=manage&sub=spam");
		}

		static function admin_purge_spam($action) {
			global $comment;
			$visitor = Visitor::current();
			if (!$visitor->group->can("delete_comment")) return;

			$sql = SQL::current();
			$sql->query("delete from `".$sql->prefix."comments`
			             where `status` = 'spam'");
			$config = Config::current();
			$route = Route::current();
			$route->redirect("/admin/?action=manage&sub=spam&purged");
		}

		static function new_post_options() {
?>
					<p>
						<label for="comment_status"><?php echo __("Comment Status", "comments"); ?></label>
						<select name="option[comment_status]" id="comment_status">
							<option value="open"><?php echo __("Open", "comments"); ?></option>
							<option value="closed"><?php echo __("Closed", "comments"); ?></option>
							<option value="private"><?php echo __("Private"); ?></option>
							<option value="registered_only"><?php echo __("Registered Only"); ?></option>
						</select>
					</p>
<?php
		}

		static function edit_post_options() {
			global $post;
			fallback($post->comment_status, "open");
?>
					<p>
						<label for="comment_status"><?php echo __("Comment Status", "comments"); ?></label>
						<select name="option[comment_status]" id="comment_status">
							<option value="open"<?php selected($post->comment_status, "open"); ?>><?php echo __("Open", "comments"); ?></option>
							<option value="closed"<?php selected($post->comment_status, "closed"); ?>><?php echo __("Closed", "comments"); ?></option>
							<option value="private"<?php selected($post->comment_status, "private"); ?>><?php echo __("Private"); ?></option>
							<option value="registered_only"<?php selected($post->comment_status, "registered_only"); ?>><?php echo __("Registered Only", "comments"); ?></option>
						</select>
					</p>
<?php
		}

		static function trackback_receive() {
			global $comment, $url, $title, $excerpt, $blog_name;

			$sql = SQL::current();
			$dupe = $sql->query("select `id` from `".$sql->prefix."comments`
			                     where
			                         `post_id` = :post_id and
			                         `author_url` = :url",
			                    array(
			                        ":post_id" => $_GET['id'],
			                        ":url" => $_POST['url']
			                    ));
			if ($dupe->rowCount() == 1)
				trackback_respond(true, __("A ping from that URL is already registered.", "comments"));

			$url = fix($url, "html");
			$title = fix($title, "html");
			$comment->create($blog_name,
			                 "",
			                 $_POST["url"],
			                 "<strong><a href=\"$url\">$title</a></strong> $excerpt",
			                 $_GET["id"],
			                 "trackback");
		}

		static function pingback($id, $to, $from, $title, $excerpt) {
			global $comment;

			$sql = SQL::current();
			$dupe = $sql->query("select `id` from `".$sql->prefix."comments`
			                     where
			                         `post_id` = :id and
			                         `author_url` = :url",
			                    array(
			                        ":id" => $id,
			                        ":url" => $from
			                    ));
			if ($dupe->rowCount() == 1)
				return new IXR_Error(48, __("A ping from that URL is already registered.", "comments"));

			$comment->create($title,
			                 "",
			                 $from,
			                 $excerpt,
			                 $id,
			                 "pingback");
		}

		static function delete_post($post) {
			$sql = SQL::current();
			$sql->query("delete from `".$sql->prefix."comments`
			             where `post_id` = :id",
			            array(
			                ":id" => $post->id
			            ));
		}

		static function change_settings($sub) {
			# Don't do anything if they're not submitting to the "comments" page
			if ($sub != "comments") return;

			$config = Config::current();
			$config->set("akismet_api_key", $_POST['akismet_api_key']);
			$config->set("allowed_comment_html", explode(", ", $_POST['allowed_comment_html']));
			$config->set("default_comment_status", $_POST['default_comment_status']);
			$config->set("comments_per_page", $_POST['comments_per_page']);
		}

		static function admin_settings_nav() {
?>
					<li<?php selected("settings", "comments"); ?>><a href="<?php url("settings", "comments"); ?>"><?php echo __("Comments", "comments"); ?></a></li>
<?php
		}

		static function admin_manage_nav() {
			$visitor = Visitor::current();
			if (!$visitor->group->can("edit_comment") and !$visitor->group->can("delete_comment")) return;
?>
					<li<?php selected("manage", "comment").selected("edit", "comment"); ?>><a href="<?php url("manage", "comment"); ?>"><?php echo __("Comments", "comments"); ?></a></li>
					<li<?php selected("manage", "spam").selected("edit", "spam"); ?>><a href="<?php url("manage", "spam"); ?>"><?php echo __("Spam", "comments"); ?></a></li>
<?php
		}

		static function admin_manage_posts_column_header() {
			echo '<th>'.__("Comments", "comments").'</th>';
		}

		static function admin_manage_posts_column($id) {
			global $comment;
			$post = new Post($id);
			echo '<td align="center"><a href="'.$post->url().'#comments">'.$comment->post_count($id).'</a></td>';
		}

		static function javascript_domready() {
			$config = Config::current();
?>
//<script>
	if ($(".comments").size()) {
		var updater = setInterval("Comment.reload()", 30000);
		$("#add_comment").append($(document.createElement("input")).attr({ type: "hidden", name: "ajax", value: "true", id: "ajax" }));
		$("#add_comment").ajaxForm({ dataType: "json", resetForm: true, beforeSubmit: function(){
			$("#add_comment").loader();
		}, success: function(json){
			$.post("<?php echo $config->url; ?>/includes/ajax.php", { action: "show_comment", comment_id: json.comment_id, reason: "added" }, function(data) {
				if ($(".comment_count").size() && $(".comment_plural").size()) {
					var count = parseInt($(".comment_count").text())
					count++
					$(".comment_count").text(count)
					var plural = (count == 1) ? "" : "s"
					$(".comment_plural").text(plural)
				}
				$("#last_comment").val(json.comment_id)
				$(data).appendTo(".comments").hide().fadeIn("slow")
				$("#comment_delete_"+json.comment_id).click(function(){
					if (!confirm("<?php echo __("Are you sure you want to delete this comment?\\n\\nIt cannot be restored if you do this.", "comments"); ?>")) return false
					Comment.destroy(json.comment_id)
					return false
				})
			})
		}, complete: function(){
			$("#add_comment").loader(true)
		} })
	}
	$(".comment_edit_link").click(function(){
		var id = $(this).attr("id").replace(/comment_edit_/, "")
		Comment.edit(id)
		return false
	})
	$(".comment_delete_link").click(function(){
		if (!confirm("<?php echo __("Are you sure you want to delete this comment?\\n\\nIt cannot be restored if you do this.", "comments"); ?>")) return false
		var id = $(this).attr("id").replace(/comment_delete_/, "")
		Comment.destroy(id)
		return false
	})
//</script>
<?php
		}

		static function javascript() {
			$config = Config::current();
?>
//<script>
var editing = 0
var notice = 0
var Comment = {
	reload: function() {
		if ($(".comments").attr("id") == undefined) return;
		var id = $(".comments").attr("id").replace(/comments_/, "")
		if (editing == 0 && notice == 0 && $(".comments").children().size() < <?php echo $config->comments_per_page; ?>) {
			$.ajax({ type: "post", dataType: "json", url: "<?php echo $config->url; ?>/includes/ajax.php", data: "action=reload_comments&post_id="+id+"&last_comment="+$("#last_comment").val(), success: function(json) {
				$.each(json.comment_ids, function(i, id) {
					$("#last_comment").val(id)
					$.post("<?php echo $config->url; ?>/includes/ajax.php", { action: "show_comment", comment_id: id }, function(data){
						$(data).appendTo(".comments").hide().fadeIn("slow")
					})
				})
			} })
		}
	},
	edit: function(id) {
		editing++
		$("#comment_"+id).loader()
		$.post("<?php echo $config->url; ?>/includes/ajax.php", { action: "edit_comment", comment_id: id }, function(data) {
			if (isError(data)) return $("#comment_"+id).loader(true)
			$("#comment_"+id).loader(true).fadeOut("fast", function(){ $(this).html(data).fadeIn("fast", function(){
				$("#more_options_link_"+id).click(function(){
					if ($("#more_options_"+id).css("display") == "none") {
						$(this).html("<?php echo __("&laquo; Fewer Options"); ?>")
						$("#more_options_"+id).slideDown("slow");
					} else {
						$(this).html("<?php echo __("More Options &raquo;"); ?>")
						$("#more_options_"+id).slideUp("slow");
					}
					return false;
				})
				$("#comment_cancel_edit_"+id).click(function(){
					$("#comment_"+id).loader()
					$.post("<?php echo $config->url; ?>/includes/ajax.php", { action: "show_comment", comment_id: id }, function(data){
						$("#comment_"+id).replaceWith(data)
						$("#comment_"+id).loader(true)
						$("#comment_edit_"+id).click(function(){
							Comment.edit(id)
							return false
						})
						$("#comment_delete_"+id).click(function(){
							notice++
							if (!confirm("<?php echo __("Are you sure you want to delete this comment?\\n\\nIt cannot be restored if you do this.", "comments"); ?>")) return notice--
							Comment.destroy(id)
							return false
						})
					})
				})
				$("#comment_edit_"+id).ajaxForm({ beforeSubmit: function(){
					$("#comment_"+id).loader()
				}, success: function(response){
					editing--
					if (isError(response)) return $("#comment_"+id).loader(true)
					$.post("<?php echo $config->url; ?>/includes/ajax.php", { action: "show_comment", comment_id: id, reason: "edited" }, function(data) {
						if (isError(data)) return $("#comment_"+id).loader(true)
						$("#comment_"+id).loader(true)
						$("#comment_"+id).fadeOut("fast", function(){ $(this).replaceWith(data).fadeIn("fast", function(){
							$("#comment_edit_"+id).click(function(){
								Comment.edit(id)
								return false
							})
							$("#comment_delete_"+id).click(function(){
								notice++
								if (!confirm("<?php echo __("Are you sure you want to delete this comment?\\n\\nIt cannot be restored if you do this.", "comments"); ?>")) return notice--
								Comment.destroy(id)
								return false
							})
						}) })
					})
				} })
			}) })
		})
	},
	destroy: function(id) {
		notice--
		$("#comment_"+id).loader()
		$.post("<?php echo $config->url; ?>/includes/ajax.php", { action: "delete_comment", id: id }, function(response){
			$("#comment_"+id).loader(true)
			if (isError(response)) return
			$("#comment_"+id).animate({ height: "hide", opacity: "hide" })

			if ($(".comment_count").size() && $(".comment_plural").size()) {
				var count = parseInt($(".comment_count").text())
				count--
				$(".comment_count").text(count)
				var plural = (count == 1) ? "" : "s"
				$(".comment_plural").text(plural)
			}
		})
	}
}
//</script>
<?php
		}

		static function admin_javascript() {
?>
$(function(){
	$("#checkbox_header").html('<input type="checkbox" name="comments_all" id="comments_all" />')
	$("#comments_all").click(function(){
		$("#spam_form").find(":checkbox").not("#comments_all").each(function(){
	      this.checked = document.getElementById("comments_all").checked
		})
	})
})
<?php
		}

		static function ajax() {
			global $theme, $comment;
			header("Content-Type: application/x-javascript", true);

			$config = Config::current();
			$sql = SQL::current();
			$trigger = Trigger::current();
			$visitor = Visitor::current();
			switch($_POST['action']) {
				case "reload_comments":
					$last_comment = $sql->query("select `id` from `".$sql->prefix."comments`
					                             where
					                                 `post_id` = :post_id and (
					                                     `status` != 'denied' or (
					                                         `status` = 'denied' and (
					                                             `author_ip` = :current_ip or
					                                             `user_id` = :user_id
					                                         )
					                                     )
					                                 ) and
					                                 `status` != 'spam'
					                             order by `created_at` desc
					                             limit 1",
					                            array(
					                                ":post_id" => $_POST['post_id'],
					                                ":current_ip" => ip2long($_SERVER['REMOTE_ADDR']),
					                                ":user_id" => $visitor->id
					                            ))->fetchColumn();
					if ($last_comment > $_POST['last_comment']) {
						$new_comments = $sql->query("select `id` from `".$sql->prefix."comments`
						                             where
						                                 `post_id` = :post_id and
						                                 `id` > :last_comment and (
						                                     `status` != 'denied' or (
						                                         `status` = 'denied' and (
						                                             `author_ip` = :current_ip or
						                                             `user_id` = :user_id
						                                         )
						                                     )
						                                 ) and
						                                 `status` != 'spam'
						                             order by `created_at` asc",
						                            array(
						                                ":post_id" => $_POST['post_id'],
						                                ":last_comment" => $_POST['last_comment'],
						                                ":current_ip" => ip2long($_SERVER['REMOTE_ADDR']),
						                                ":user_id" => $visitor->id
						                            ));
						$count = 1;
						$ids = "";
						while ($the_comment = $new_comments->fetchObject()) {
							$ids.= $the_comment->id;
							if ($count != $new_comments->rowCount()) $ids.= ", ";
							$count++;
						}
?>
{ "comment_ids": [ <?php echo $ids; ?> ] }
<?php
					}
					break;
				case "show_comment":
					$comment->find($_POST['comment_id']);
					$trigger->call("show_comment", $comment->id);

					if (($comment->status != "pingback" and !$comment->status != "trackback") and !$visitor->group->can("code_in_comments", $comment->user_id))
						$comment->body = strip_tags($comment->body, "<".join("><", $config->allowed_comment_html).">");

					$comment->body = $trigger->filter("markup_comment_text", $comment->body);
					$theme->load("content/comment", array("comment" => $comment));
					break;
				case "delete_comment":
					if (!$visitor->group->can("delete_comment") or !isset($_POST['id']))
						break;

					$comment->delete($_POST['id']);
					break;
				case "edit_comment":
					if (!$visitor->group->can("edit_comment") or !isset($_POST['comment_id']))
						break;

					$comment->find($_POST['comment_id']);
?>
<form id="comment_edit_<?php echo $comment->id; ?>" class="inline comment" action="<?php echo $config->url."/admin/?action=update_comment"; ?>" method="post" accept-charset="utf-8">
	<p>
		<label for="body"><?php echo __("Body", "comments"); ?></label>
		<textarea name="body" rows="8" cols="40" class="wide"><?php echo fix($comment->body, "html"); ?></textarea>
	</p>
	<a id="more_options_link_<?php echo $comment->id; ?>" href="javascript:void(0)" class="more_options_link"><?php echo __("More Options &raquo;"); ?></a>
	<div id="more_options_<?php echo $comment->id; ?>" class="more_options" style="display: none">
		<p>
			<label for="author"><?php echo __("Author"); ?></label>
			<input class="text" type="text" name="author" value="<?php echo fix($comment->author, "html"); ?>" id="author" />
		</p>
		<p>
			<label for="author_url"><?php echo __("Author URL", "comments"); ?></label>
			<input class="text" type="text" name="author_url" value="<?php echo fix($comment->author_url, "html"); ?>" id="author_url" />
		</p>
		<p>
			<label for="author_email"><?php echo __("Author E-Mail", "comments"); ?></label>
			<input class="text" type="text" name="author_email" value="<?php echo fix($comment->author_email, "html"); ?>" id="author_email" />
		</p>
		<p>
			<label for="status"><?php echo __("Status"); ?></label>
			<select name="status" id="status">
				<option value="approved"<?php selected($comment->status, "approved"); ?>><?php echo __("Approved", "comments"); ?></option>
				<option value="denied"<?php selected($comment->status, "denied"); ?>><?php echo __("Denied", "comments"); ?></option>
				<option value="spam"<?php selected($comment->status, "spam"); ?>><?php echo __("Spam", "comments"); ?></option>
			</select>
		</p>
		<p>
			<label for="created_at"><?php echo __("Timestamp"); ?></label>
			<input class="text" type="text" name="created_at" value="<?php echo when("F jS, Y H:i:s", $comment->created_at); ?>" id="created_at" />
		</p>
		<br class="clear" />
	</div>
	<br />
	<input type="hidden" name="id" value="<?php echo fix($comment->id, "html"); ?>" id="id" />
	<input type="hidden" name="ajax" value="true" id="ajax" />
	<div class="buttons">
		<input type="submit" value="<?php echo __("Update"); ?>" accesskey="s" /> <?php echo __("or"); ?>
		<a href="javascript:void(0)" id="comment_cancel_edit_<?php echo $comment->id; ?>" class="cancel"><?php echo __("Cancel"); ?></a>
	</div>
</form>
<?php
					break;
			}
		}

		static function show_admin_manage_page($show_page, $sub) {
			$visitor = Visitor::current();
			if ($sub == "spam")
				return ($visitor->group->can("edit_comment") or $visitor->group->can("delete_comment"));
			else
				return $show_page;
		}

		static function import_wordpress_post($data, $id) {
			global $comment;
			if (isset($data["WP:COMMENT"])) {
				foreach ($data["WP:COMMENT"] as $the_comment) {
					$body = (isset($the_comment["WP:COMMENT_CONTENT"][0]["data"])) ? $the_comment["WP:COMMENT_CONTENT"][0]["data"] : "" ;
					$author = (isset($the_comment["WP:COMMENT_AUTHOR"][0]["data"])) ? $the_comment["WP:COMMENT_AUTHOR"][0]["data"] : "" ;
					$author_url = (isset($the_comment["WP:COMMENT_AUTHOR_URL"][0]["data"])) ? $the_comment["WP:COMMENT_AUTHOR_URL"][0]["data"] : "" ;
					$author_email = (isset($the_comment["WP:COMMENT_AUTHOR_EMAIL"][0]["data"])) ? $the_comment["WP:COMMENT_AUTHOR_EMAIL"][0]["data"] : "" ;
					$author_ip = (isset($the_comment["WP:COMMENT_AUTHOR_IP"][0]["data"])) ? $the_comment["WP:COMMENT_AUTHOR_IP"][0]["data"] : "" ;
					$status = (isset($the_comment["WP:COMMENT_APPROVED"][0]["data"]) and $the_comment["WP:COMMENT_APPROVED"][0]["data"] == "1") ? "approved" : "denied" ;

					$comment->add($body, $author, $author_url, $author_email, $author_ip, "", $status, $the_comment["WP:COMMENT_DATE"][0]["data"], $id, 0);
				}
			}
		}

		static function import_movabletype_post($data, $id) {
			global $comment;
			preg_match_all("/COMMENT:\nAUTHOR: (.*?)\nEMAIL: (.*?)\nIP: (.*?)\nURL: (.*?)\nDATE: (.*?)\n(.*?)\n-----/", $data, $comments);
			array_shift($comments);
			for ($i = 0; $i < count($comments[0]); $i++) {
				$comment->add($comments[5][$i], $comments[0][$i], $comments[3][$i], $comments[1][$i], $comments[2][$i], "", "approved", $comments[4][$i], $id, 0);
			}
		}

		static function import_textpattern_generate_array($array) {
			global $link;
			$get_comments = mysql_query("select * from `".$_POST['prefix']."discuss` where `parentid` = ".fix($array["ID"])." order by `discussid` asc", $link);
			while ($comment = mysql_fetch_array($get_comments)) {
				foreach ($comment as $key => $val) {
					$array["comments"][$comment["discussid"]][$key] = $val;
				}
			}
			return $array;
		}

		static function import_textpattern_post($array, $id) {
			global $comment;
			if (!isset($array["comments"])) return;
			foreach ($array["comments"] as $the_comment) {
				$translate_status = array(-1 => "spam", 0 => "denied", 1 => "approved");
				$status = str_replace(array_keys($translate_status), array_values($translate_status), $the_comment["visible"]);

				$comment->add($the_comment["message"], $the_comment["name"], $the_comment["web"], $the_comment["email"], $the_comment["ip"], "", $status, $the_comment["posted"], $id, 0);
			}
		}

		static function manage_permission($type) {
			return ($type == "spam") ? "comment" : $type ;
		}

		static function view_feed() {
			global $post, $action, $get_comments, $latest_timestamp, $title;

			$title = $post->title();
			fallback($title, ucfirst($post->feather)." Post #".$post->id);

			$sql = SQL::current();
			$visitor = Visitor::current();
			$get_comments = $sql->query("select * from `".$sql->prefix."comments`
			                             where
			                                 `post_id` = :post_id and (
			                                     `status` != 'denied' or (
			                                         `status` = 'denied' and (
			                                             `author_ip` = :current_ip or (
			                                                 `user_id` != '' and
			                                                 `user_id` = :user_id
			                                             )
			                                         )
			                                     )
			                                 ) and
			                                 `status` != 'spam'
			                             order by `created_at` desc",
			                            array(
			                                ":post_id" => $post->id,
			                                ":current_ip" => ip2long($_SERVER['REMOTE_ADDR']),
			                                ":user_id" => $visitor->id
			                            ));

			$latest_timestamp = $sql->query("select `created_at` from `".$sql->prefix."comments`
			                                 where
			                                     `post_id` = :post_id and (
			                                         `status` != 'denied' or (
			                                             `status` = 'denied' and (
			                                                 `author_ip` = :current_ip or (
			                                                     `user_id` != '' and
			                                                     `user_id` = :user_id
			                                                 )
			                                             )
			                                         )
			                                     ) and
			                                     `status` != 'spam'
			                                 order by `created_at` desc
			                                 limit 1",
			                                array(
			                                    ":post_id" => $post->id,
			                                    ":current_ip" => ip2long($_SERVER['REMOTE_ADDR']),
			                                    ":user_id" => $visitor->id
			                                ))->fetchColumn();

			$action = "comments_feed";
		}

		static function metaWeblog_getPost($post, $struct) {
			$struct['mt_allow_comments'] = intval($post->comment_status == 'open');
			return array($post, $struct);
		}

		static function metaWeblog_editPost_preQuery($struct, $post = null) {
			if (isset($struct['mt_allow_comments']))
				$_POST['option']['comment_status'] = ($struct['mt_allow_comments'] == 1) ? 'open' : 'closed';
		}

		static function filter_post() {
			global $post, $paginate, $comment, $viewing;
			$sql = SQL::current();
			$config = Config::current();
			$trigger = Trigger::current();
			$visitor = Visitor::current();
			$post->comment_count = $sql->count("comments",
		                                       "`post_id` = :post_id and (
		                                            `status` != 'denied' or (
		                                                `status` = 'denied' and (
		                                                    `author_ip` = :current_ip or
		                                                    `user_id` = :user_id
		                                                )
		                                            )
		                                        ) and
		                                        `status` != 'spam'",
		                                       array(
		                                           ":post_id" => $post->id,
		                                           ":current_ip" => ip2long($_SERVER['REMOTE_ADDR']),
		                                           ":user_id" => $visitor->id
		                                       ));
			$get_last_comment = $sql->query("select `id` from `".$sql->prefix."comments` where `post_id` = ".$sql->quote($post->id)." and (`status` != 'denied' or (`status` = 'denied' and (`author_ip` = '".ip2long($_SERVER['REMOTE_ADDR'])."' or `user_id` = ".$sql->quote($visitor->id)."))) and `status` != 'spam' order by `created_at` desc limit 1");
			$post->last_comment = ($get_last_comment->rowCount() > 0) ? $get_last_comment->fetchColumn() : 0 ;
			$post->commentable = $comment->user_can($post->id);

			if ($viewing) {
				$get_comments = $paginate->select("comments", # table
				                                  "*", # fields
				                                  "`post_id` = :post_id and (
				                                       `status` != 'denied' or (
				                                           `status` = 'denied' and (
				                                               `author_ip` = :current_ip or (
				                                                   `user_id` != '' and
				                                                   `user_id` = :current_user
				                                               )
				                                           )
				                                       )
				                                   ) and
				                                   `status` != 'spam'", #where
				                                  "`created_at` asc", # order
				                                  $config->comments_per_page, "comments_page",
				                                  array(
				                                      ":post_id" => $post->id,
				                                      ":current_ip" => ip2long($_SERVER['REMOTE_ADDR']),
				                                      ":current_user" => $visitor->id
				                                  ));

				$shown_dates = array();
				$comments = array();
				foreach ($get_comments->fetchAll() as $temp_comment) {
					$temp_comment["date_shown"] = in_array(when("m-d-Y", $temp_comment["created_at"]), $shown_dates);
					if (!in_array(when("m-d-Y", $temp_comment["created_at"]), $shown_dates))
						$shown_dates[] = when("m-d-Y", $temp_comment["created_at"]);

					if (($temp_comment["status"] != "pingback" and $temp_comment["status"] != "trackback") and !$visitor->group->can("code_in_comments", $temp_comment["user_id"]))
						$temp_comment["body"] = strip_tags($temp_comment["body"], "<".join("><", $config->allowed_comment_html).">");

					$temp_comment["body"] = $trigger->filter("markup_comment_text", $temp_comment["body"]);
					$temp_comment["is_author"] = (Post::info("user_id", $temp_comment["post_id"]) == $temp_comment["user_id"]);

					$comments[] = $temp_comment;
				}

				$post->comments = $comments;
			}
		}
	}
	$comments = new Comments();

	function post_comments($post_id, $text = null, $link = true, $echo = true){
		$post = new Post($post_id);

		fallback($text, __("% Comment[s]", "comments"));

		$sql = SQL::current();
			$visitor = Visitor::current();
		$count = $sql->count("comments",
		                     "`post_id` = :post_id and (
		                          `status` != 'denied' or (
		                              `status` = 'denied' and (
		                                  `author_ip` = :current_ip or
		                                  `user_id` = :user_id
		                              )
		                          )
		                      ) and
		                      `status` != 'spam'",
		                     array(
		                         ":post_id" => $post_id,
		                         ":current_ip" => ip2long($_SERVER['REMOTE_ADDR']),
		                         ":user_id" => $visitor->id
		                     ));
		$s = ($count == 1) ? "" : "s" ;
		$string = str_replace("[s]", '<span class="comment_plural">'.$s.'</span>', $text);
		$string = str_replace("%", '<span class="comment_count">'.$count.'</span>', $string);
		$string = ($link) ? '<a href="'.$post->url().'#comments">'.$string.'</a>' : $string ;

		if (!$echo) return $string;
		if ($echo) echo $string;
	}
