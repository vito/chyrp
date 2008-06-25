<?php
	require_once "model.Comment.php";
	require_once "lib/Defensio.php";

	class Comments extends Module {
		public function __construct() {
			$this->addAlias('metaWeblog_newPost_preQuery', 'metaWeblog_editPost_preQuery');
			$this->addAlias("post_grab", "posts_get");
		}

		static function __install() {
			$visitor = Visitor::current();
			$sql = SQL::current();
			$sql->query("CREATE TABLE IF NOT EXISTS `__comments` (
			                 `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
			                 `body` LONGTEXT DEFAULT '',
			                 `author` VARCHAR(250) DEFAULT '',
			                 `author_url` VARCHAR(128) DEFAULT '',
			                 `author_email` VARCHAR(128) DEFAULT '',
			                 `author_ip` INT(10) DEFAULT '0',
			                 `author_agent` VARCHAR(255) DEFAULT '',
			                 `status` VARCHAR(32) default 'denied',
			                 `signature` VARCHAR(32) DEFAULT '',
			                 `post_id` INTEGER DEFAULT '0',
			                 `user_id` INTEGER DEFAULT '0',
			                 `created_at` DATETIME DEFAULT '0000-00-00 00:00:00'
			             ) default charset=utf8");
			$config = Config::current();
			$config->set("default_comment_status", "denied");
			$config->set("allowed_comment_html", array("strong", "em", "blockquote", "code", "pre", "a"));
			$config->set("comments_per_page", 25);
			$config->set("defensio_api_key", null);
			Group::add_permission("add_comment");
			Group::add_permission("add_comment_private");
			Group::add_permission("edit_comment");
			Group::add_permission("edit_own_comment");
			Group::add_permission("delete_comment");
			Group::add_permission("delete_own_comment");
			Group::add_permission("code_in_comments");
		}

		static function __uninstall($confirm) {
			if ($confirm)
				SQL::current()->query("DROP TABLE `__comments`");

			$config = Config::current();
			$config->remove("default_comment_status");
			$config->remove("allowed_comment_html");
			$config->remove("comments_per_page");
			$config->remove("defensio_api_key");
			Group::remove_permission("add_comment");
			Group::remove_permission("add_comment_private");
			Group::remove_permission("edit_comment");
			Group::remove_permission("edit_own_comment");
			Group::remove_permission("delete_comment");
			Group::remove_permission("delete_own_comment");
			Group::remove_permission("code_in_comments");
		}

		static function route_add_comment() {
			$post = new Post($_POST['post_id']);
			if (!Comment::user_can($post->id))
				show_403(__("Access Denied"), __("You cannot comment on this post.", "comments"));

			if (empty($_POST['author'])) error(__("Error"), __("Author can't be blank.", "comments"));
			if (empty($_POST['email']))  error(__("Error"), __("E-Mail address can't be blank.", "comments"));
			if (empty($_POST['body']))   error(__("Error"), __("Message can't be blank.", "comments"));
			Comment::create($_POST['author'],
			                $_POST['email'],
			                $_POST['url'],
			                $_POST['body'],
			                $_POST['post_id']);
		}

		static function admin_update_comment() {
			if (empty($_POST))
				redirect("/admin/?action=manage_comments");

			$comment = new Comment($_POST['id']);
			if (!$comment->editable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit this comment.", "comments"));

			$comment->update($_POST['author'],
			                 $_POST['author_email'],
			                 $_POST['author_url'],
			                 $_POST['body'],
			                 $_POST['status'],
			                 datetime($_POST['created_at']));

			if (isset($_POST['ajax']))
				exit("{ comment_id: ".$_POST['id']." }");

			if ($_POST['status'] == "spam")
				redirect("/admin/?action=manage_spam&updated=".$comment->id);
			else
				redirect("/admin/?action=manage_comments&updated=".$comment->id);
		}

		static function admin_delete_comment() {
			global $admin;
			$admin->context["comment"] = new Comment($_GET['id']);
			if (!$admin->context["comment"]->deletable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to delete this comment.", "comments"));
		}

		static function admin_destroy_comment() {
			if (empty($_POST['id']))
				error(__("No ID Specified"), __("An ID is required to delete a comment.", "comments"));

			if ($_POST['destroy'] == "bollocks")
				redirect("/admin/?action=manage_comments");

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));

			$comment = new Comment($_POST['id']);
			if (!$comment->deletable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to delete this comment.", "comments"));

			Comment::delete($_POST['id']);

			if (isset($_POST['ajax']))
				exit;

			if ($comment->status == "spam")
				redirect("/admin/?action=manage_spam&deleted");
			else
				redirect("/admin/?action=manage_comments&deleted");
		}

		static function admin_mark_spam() {
			$comment = new Comment($_GET['id']);
			if (!$comment->editable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit this comment.", "comments"));

			$sql = SQL::current();
			$sql->update("comments", "`id` = :id", array("status" => "spam"), array(":id" => $_GET['id']));

			$config = Config::current();
			if (!empty($config->defensio_api_key)) {
				$defensio = new Defensio($config->url, $config->defensio_api_key);
				$defensio->submitFalseNegatives(array("owner-url" => $config->url, "signatures" => $comment->signature));
			}

			redirect("/admin/?action=manage_comments&spammed");
		}

		static function admin_approve_comment($action) {
			$comment = new Comment($_GET['id']);
			if (!$comment->editable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit this comment.", "comments"));

			$sql = SQL::current();
			$sql->update("comments", "`id` = :id", array("status" => "approved"), array(":id" => $_GET['id']));

			redirect("/admin/?action=manage_comments&approved");
		}

		static function admin_deny_comment() {
			$comment = new Comment($_GET['id']);
			if (!$comment->editable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit this comment.", "comments"));

			$sql = SQL::current();
			$sql->update("comments", "`id` = :id", array("status" => "approved"), array(":id" => $_GET['id']));

			redirect("/admin/?action=manage_comments&denied");
		}

		static function admin_manage_spam($action) {
			if (!Comment::any_editable() and !Comment::any_deletable())
				error(__("Access Denied"), __("You do not have sufficient privileges to manage any comments.", "comments"));

			global $admin;

			$params = array();
			$where = array("`__comments`.`status` = 'spam'");

			if (!empty($_GET['query'])) {
				$search = "";
				$matches = array();

				$queries = explode(" ", $_GET['query']);
				foreach ($queries as $query)
					if (!strpos($query, ":"))
						$search.= $query;
					else
						$matches[] = $query;

				foreach ($matches as $match) {
					$match = explode(":", $match);
					$test = $match[0];
					$equals = $match[1];
					$where[] = "`__comments`.`".$test."` = :".$test;
					$params[":".$test] = $equals;
				}

				$where[] = "(`__comments`.`body` LIKE :query)";
				$params[":query"] = "%".$search."%";
			}

			$admin->context["comments"] = new Paginator(Comment::find(array("placeholders" => true, "where" => $where, "params" => $params)), 25);

			if (!empty($_GET['updated']))
				$admin->context["updated"] = new Comment($_GET['updated']);

			$admin->context["deleted"]       = isset($_GET['deleted']);
			$admin->context["purged"]        = isset($_GET['purged']);
			$admin->context["bulk_deleted"]  = isset($_GET['bulk_deleted']);
			$admin->context["bulk_approved"] = isset($_GET['bulk_approved']);
			$admin->context["bulk_denied"]   = isset($_GET['bulk_denied']);
		}

		static function admin_purge_spam() {
			if (!Visitor::current()->group()->can("delete_comment"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to delete comments.", "comments"));

			SQL::current()->delete("comments", "`status` = 'spam'");

			redirect("/admin/?action=manage_spam&purged");
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

		static function edit_post_options($post) {
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
			$count = $sql->count("comments", array("`post_id` = :id", "`author_url` = :url"), array(
			                        ":id" => $_GET['id'],
			                        ":url" => $_POST['url']
			                    ));
			if ($count == 1)
				trackback_respond(true, __("A ping from that URL is already registered.", "comments"));

			$url = fix($url, "html");
			$title = fix($title, "html");
			Comment::create($blog_name,
			                "",
			                $_POST["url"],
			                "<strong><a href=\"$url\">$title</a></strong> $excerpt",
			                $_GET["id"],
			                "trackback");
		}

		static function pingback($id, $to, $from, $title, $excerpt) {
			global $comment;

			$sql = SQL::current();
			$count = $sql->count("comments", array("`post_id` = :id", "`author_url` = :url"), array(
			                        ":id" => $id,
			                        ":url" => $from
			                    ));
			if ($count == 1)
				return new IXR_Error(48, __("A ping from that URL is already registered.", "comments"));

			Comment::create($title,
			                "",
			                $from,
			                $excerpt,
			                $id,
			                "pingback");
		}

		static function delete_post($post) {
			SQL::current()->delete("comments", "`post_id` = :id", array(":id" => $post->id));
		}

		static function admin_comment_settings() {
			global $admin;

			if (!Visitor::current()->group()->can("change_settings"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

			if (empty($_POST))
				return;

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));

			$config = Config::current();
			$config->set("allowed_comment_html", explode(", ", $_POST['allowed_comment_html']));
			$config->set("default_comment_status", $_POST['default_comment_status']);
			$config->set("comments_per_page", $_POST['comments_per_page']);

			if (!empty($_POST['defensio_api_key'])) {
				$_POST['defensio_api_key'] = trim($_POST['defensio_api_key']);
				$defensio = new Defensio($config->url, $_POST['defensio_api_key']);
				if ($defensio->errorsExist())
					$admin->context["invalid_defensio"] = true;
				else
					$config->set("defensio_api_key", $_POST['defensio_api_key']);
			}

			$admin->context["updated"] = true;
		}

		static function settings_nav($navs) {
			if (Visitor::current()->group()->can("change_settings"))
				$navs["comment_settings"] = array("title" => __("Comments", "comments"));

			return $navs;
		}

		static function manage_nav($navs) {
			if (!Comment::any_editable() and !Comment::any_deletable())
				return $navs;

			$navs["manage_comments"] = array("title" => __("Comments", "comments"), "selected" => array("edit_comment", "delete_comment"));
			$navs["manage_spam"]     = array("title" => __("Spam", "comments"));
			return $navs;
		}

		static function manage_nav_pages($pages) {
			array_push($pages, "manage_comments", "manage_spam", "edit_comment", "delete_comment");
			return $pages;
		}

		public function admin_edit_comment() {
			global $admin;
			if (empty($_GET['id']))
				error(__("No ID Specified"), __("An ID is required to edit a comment.", "comments"));

			$admin->context["comment"] = new Comment($_GET['id'], array("filter" => false));

			if (!$admin->context["comment"]->editable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit this comment.", "comments"));
		}

		static function admin_manage_comments() {
			if (!Comment::any_editable() and !Comment::any_deletable())
				error(__("Access Denied"), __("You do not have sufficient privileges to manage any comments.", "comments"));

			global $admin;

			$params = array();
			$where = array("`__comments`.`status` != 'spam'");

			if (!empty($_GET['query'])) {
				$search = "";
				$matches = array();

				$queries = explode(" ", $_GET['query']);
				foreach ($queries as $query)
					if (!strpos($query, ":"))
						$search.= $query;
					else
						$matches[] = $query;

				foreach ($matches as $match) {
					$match = explode(":", $match);
					$test = $match[0];
					$equals = $match[1];
					$where[] = "`__comments`.`".$test."` = :".$test;
					$params[":".$test] = $equals;
				}

				$where[] = "(`__comments`.`body` LIKE :query)";
				$params[":query"] = "%".$search."%";
			}

			$admin->context["comments"] = new Paginator(Comment::find(array("placeholders" => true, "where" => $where, "params" => $params)), 25);

			if (!empty($_GET['updated']))
				$admin->context["updated"] = new Comment($_GET['updated']);

			$admin->context["deleted"]       = isset($_GET['deleted']);
			$admin->context["bulk_deleted"]  = isset($_GET['bulk_deleted']);
			$admin->context["bulk_approved"] = isset($_GET['bulk_approved']);
			$admin->context["bulk_denied"]   = isset($_GET['bulk_denied']);
			$admin->context["bulk_spammed"]  = isset($_GET['bulk_spammed']);
		}

		static function admin_bulk_comments() {
			$from = (!isset($_GET['from'])) ? "manage_comments" : "manage_spam" ;
			if (!isset($_POST['comment']))
				redirect("/admin/?action=".$from);

			$comments = array_keys($_POST['comment']);

			if (isset($_POST['delete'])) {
				foreach ($comments as $comment)
					Comment::delete($comment);

				redirect("/admin/?action=".$from."&bulk_deleted");
			}

			$false_positives = array();
			$false_negatives = array();

			$sql = SQL::current();
			$config = Config::current();

			if (isset($_POST['deny'])) {
				foreach ($comments as $comment) {
					$comment = new Comment($comment);
					if (!$comment->editable())
						continue;

					if ($comment->status == "spam")
						$false_positives[] = $comment->signature;

					$sql->update("comments", "`__comments`.`id` = :id", array("status" => ":status"), array(":id" => $comment->id, ":status" => "denied"));
				}

				redirect("/admin/?action=".$from."&bulk_denied");
			}
			if (isset($_POST['approve'])) {
				foreach ($comments as $comment) {
					$comment = new Comment($comment);
					if (!$comment->editable())
						continue;

					if ($comment->status == "spam")
						$false_positives[] = $comment->signature;

					$sql->update("comments", "`__comments`.`id` = :id", array("status" => ":status"), array(":id" => $comment->id, ":status" => "approved"));
				}

				redirect("/admin/?action=".$from."&bulk_approved");
			}
			if (isset($_POST['spam'])) {
				foreach ($comments as $comment) {
					$comment = new Comment($comment);
					if (!$comment->editable())
						continue;

					$sql->update("comments", "`__comments`.`id` = :id", array("status" => ":status"), array(":id" => $comment->id, ":status" => "spam"));

					$false_negatives[] = $comment->signature;
				}

				redirect("/admin/?action=".$from."&bulk_spammed");
			}

			if (!empty($config->defensio_api_key)) {
				$defensio = new Defensio($config->url, $config->defensio_api_key);
				if (!empty($false_positives))
					$defensio->submitFalsePositives(array("owner-url" => $config->url, "signatures" => implode(",", $false_positives)));
				if (!empty($false_negatives))
					$defensio->submitFalseNegatives(array("owner-url" => $config->url, "signatures" => implode(",", $false_negatives)));
			}
		}

		static function admin_manage_posts_column_header() {
			echo '<th>'.__("Comments", "comments").'</th>';
		}

		static function admin_manage_posts_column($id) {
			global $comment;
			$post = new Post($id);
			echo '<td align="center"><a href="'.$post->url().'#comments">'.$post->comment_count.'</a></td>';
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
			$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "show_comment", comment_id: json.comment_id, reason: "added" }, function(data) {
				if ($(".comment_count").size() && $(".comment_plural").size()) {
					var count = parseInt($(".comment_count:first").text())
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
			$.ajax({ type: "post", dataType: "json", url: "<?php echo $config->chyrp_url; ?>/includes/ajax.php", data: "action=reload_comments&post_id="+id+"&last_comment="+$("#last_comment").val(), success: function(json) {
				$.each(json.comment_ids, function(i, id) {
					$("#last_comment").val(id)
					$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "show_comment", comment_id: id }, function(data){
						$(data).appendTo(".comments").hide().fadeIn("slow")
					})
				})
			} })
		}
	},
	edit: function(id) {
		editing++
		$("#comment_"+id).loader()
		$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "edit_comment", comment_id: id }, function(data) {
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
					$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "show_comment", comment_id: id }, function(data){
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
					$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "show_comment", comment_id: id, reason: "edited" }, function(data) {
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
		$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "delete_comment", id: id }, function(response){
			$("#comment_"+id).loader(true)
			if (isError(response)) return
			$("#comment_"+id).animate({ height: "hide", opacity: "hide" })

			if ($(".comment_count").size() && $(".comment_plural").size()) {
				var count = parseInt($(".comment_count:first").text())
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
					$post = new Post($_POST['post_id']);
					if ($post->latest_comment > $_POST['last_comment']) {
						$new_comments = $sql->select("comments",
						                             "`id`",
						                             array("`post_id` = :post_id",
						                                   "`id` > :last_comment",
						                                   "(`status` != 'denied' OR ( `status` = 'denied'",
						                                   "(`author_ip` = :current_ip OR (`user_id` != ''",
						                                   "`user_id` = :user_id))))",
						                                   "`status` != 'spam'"),
						                             "`created_at` ASC",
						                             array(":post_id" => $_POST['post_id'],
						                                   ":last_comment" => $_POST['last_comment'],
						                                   ":current_ip" => ip2long($_SERVER['REMOTE_ADDR']),
						                                   ":user_id" => $visitor->id
						                             ));

						$ids = array();
						while ($the_comment = $new_comments->fetchObject())
							$ids[] = $the_comment->id;
?>
{ "comment_ids": [ <?php echo implode(", ", $ids); ?> ] }
<?php
					}
					break;
				case "show_comment":
					$comment = new Comment($_POST['comment_id']);
					$trigger->call("show_comment", $comment);

					$group = ($comment->user_id) ? $comment->user()->group() : new Group(Config::current()->guest_group) ;
					if (($comment->status != "pingback" and !$comment->status != "trackback") and !$group->can("code_in_comments"))
						$comment->body = strip_tags($comment->body, "<".join("><", $config->allowed_comment_html).">");

					$comment->body = $trigger->filter("markup_comment_text", $comment->body);
					$theme->load("content/comment", array("comment" => $comment));
					break;
				case "delete_comment":
					$comment = new Comment($_POST['id']);
					if (!$comment->deletable())
						break;

					Comment::delete($_POST['id']);
					break;
				case "edit_comment":
					$comment = new Comment($_POST['comment_id'], array("filter" => false));
					if (!$comment->editable())
						break;

					if ($theme->file_exists("forms/comment/edit"))
						$theme->load("forms/comment/edit", array("comment" => $comment));
					else {
?>
<form id="comment_edit_<?php echo $comment->id; ?>" class="inline_edit comment_edit" action="<?php echo $config->chyrp_url."/admin/?action=update_comment"; ?>" method="post" accept-charset="utf-8">
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
		<div class="clear"></div>
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
					}
					break;
			}
		}

		static function import_wordpress_post($item, $post) {
			$wordpress = $item->children("http://wordpress.org/export/1.0/");
			if (!isset($wordpress->comment)) return;

			foreach ($wordpress->comment as $comment) {
				$comment = $comment->children("http://wordpress.org/export/1.0/");
				fallback($comment->comment_content, "");
				fallback($comment->comment_author, "");
				fallback($comment->comment_author_url, "");
				fallback($comment->comment_author_email, "");
				fallback($comment->comment_author_ip, "");

				Comment::add($comment->comment_content,
				             $comment->comment_author,
				             $comment->comment_author_url,
				             $comment->comment_author_email,
				             $comment->comment_author_ip,
				             "",
				             (isset($comment->comment_approved) && $comment->comment_approved == "1" ? "approved" : "denied"),
				             "",
				             $comment->comment_date,
				             $post->id,
				             0);
			}
		}

		static function import_movabletype_post($item, $id) {
			global $comment;
			preg_match_all("/COMMENT:\nAUTHOR: (.*?)\nEMAIL: (.*?)\nIP: (.*?)\nURL: (.*?)\nDATE: (.*?)\n(.*?)\n-----/", $data, $comments);
			array_shift($comments);

			for ($i = 0; $i < count($comments[0]); $i++)
				Comment::add($comments[5][$i], $comments[0][$i], $comments[3][$i], $comments[1][$i], $comments[2][$i], "", "approved", $comments[4][$i], $id, 0);
		}

		static function import_textpattern_generate_array($array) {
			global $link;
			$get_comments = mysql_query("select * from `".$_POST['prefix']."txp_discuss` where `parentid` = ".fix($array["ID"])." order by `discussid` asc", $link) or die(mysql_error());

			while ($comment = mysql_fetch_array($get_comments))
				foreach ($comment as $key => $val)
					$array["comments"][$comment["discussid"]][$key] = $val;

			return $array;
		}

		static function import_textpattern_post($array, $post) {
			global $comment;
			if (!isset($array["comments"])) return;
			foreach ($array["comments"] as $comment) {
				$translate_status = array(-1 => "spam", 0 => "denied", 1 => "approved");
				$status = str_replace(array_keys($translate_status), array_values($translate_status), $comment["visible"]);

				Comment::add($comment["message"], $comment["name"], $comment["web"], $comment["email"], $comment["ip"], "", $status, "", $comment["posted"], $post->id, 0);
			}
		}

		static function view_feed() {
			global $post, $action, $comments, $title;

			$title = $post->title();
			fallback($title, ucfirst($post->feather)." Post #".$post->id);

			$title = _f("Comments on &#8220;%s&#8221;", array(htmlspecialchars($title)), "comments");

			$ids = array_reverse($post->comments->array[0]);

			$comments = array();
			for ($i = 0; $i < 20; $i++)
				$comments[] = new Comment($ids[$i]);

			$action = "comments_rss";
		}

		static function metaWeblog_getPost($struct, $post) {
			if (isset($post->comment_status))
				$struct['mt_allow_comments'] = intval($post->comment_status == 'open');
			else
				$struct['mt_allow_comments'] = 1;

			return $struct;
		}

		static function metaWeblog_editPost_preQuery($struct, $post = null) {
			if (isset($struct['mt_allow_comments']))
				$_POST['option']['comment_status'] = ($struct['mt_allow_comments'] == 1) ? 'open' : 'closed';
		}

		static function filter_post($post) {
			global $action;
			$sql = SQL::current();
			$config = Config::current();
			$trigger = Trigger::current();
			$visitor = Visitor::current();
			$post->commentable = Comment::user_can($post);

			if ($action == "view") {
				$get_comments = $sql->select("comments", # table
				                             "`__comments`.`id`", # fields
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
				                             array(
				                                 ":post_id" => $post->id,
				                                 ":current_ip" => ip2long($_SERVER['REMOTE_ADDR']),
				                                 ":current_user" => $visitor->id
				                             ));

				$post->comments = array();
				foreach ($get_comments->fetchAll() as $comment)
					$post->comments[] = $comment["id"];

				$post->comments = new Paginator(array($post->comments, "Comment"), $config->comments_per_page, "comments_page");

				$shown_dates = array();
				foreach ($post->comments->paginated as &$comment) {
					$comment->date_shown = in_array(when("m-d-Y", $comment->created_at), $shown_dates);
					if (!in_array(when("m-d-Y", $comment->created_at), $shown_dates))
						$shown_dates[] = when("m-d-Y", $comment->created_at);

					$group = ($comment->user_id) ? $comment->user()->group() : new Group(Config::current()->guest_group) ;
					if (($comment->status != "pingback" and $comment->status != "trackback") and !$group->can("code_in_comments"))
						$comment->body = strip_tags($comment->body, "<".join("><", $config->allowed_comment_html).">");

					$comment->body = $trigger->filter("markup_comment_text", $comment->body);
					$comment->is_author = ($post->user_id == $comment->user_id);
				}
			}
		}

		static function posts_get($options) {
			$options["select"][]  = "COUNT(`__comments`.`id`) as `comment_count`";
			$options["select"][]  = "MAX(`__comments`.`created_at`) as `latest_comment`";

			$options["left_join"][] = array("table" => "comments",
			                                "where" => array("`__comments`.`post_id` = `__posts`.`id` AND
			                                                  `__comments`.`status` != 'denied' OR (
			                                                      `__comments`.`status` = 'denied' AND (
			                                                          `__comments`.`author_ip` = :current_ip OR (
			                                                              `__comments`.`user_id` != '' AND
			                                                              `__comments`.`user_id` = :user_id
			                                                          )
			                                                      )
			                                                  )",
			                                                 "`__comments`.`status` != 'spam'"));

			$options["params"][":current_ip"] = ip2long($_SERVER['REMOTE_ADDR']);
			$options["params"][":user_id"]    = Visitor::current()->id;

			$options["group"][] = "`__posts`.`id`";

			return $options;
		}
	}
