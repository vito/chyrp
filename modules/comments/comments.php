<?php
	require_once "model.Comment.php";
	require_once "lib/Defensio.php";

	class Comments extends Modules {
		public function __init() {
			$this->addAlias("metaWeblog_newPost_preQuery", "metaWeblog_editPost_preQuery");
			$this->addAlias("post_grab", "posts_get");
		}

		static function __install() {
			$sql = SQL::current();
			$sql->query("CREATE TABLE IF NOT EXISTS __comments (
			                 id INTEGER PRIMARY KEY AUTO_INCREMENT,
			                 body LONGTEXT,
			                 author VARCHAR(250) DEFAULT '',
			                 author_url VARCHAR(128) DEFAULT '',
			                 author_email VARCHAR(128) DEFAULT '',
			                 author_ip INT(10) DEFAULT '0',
			                 author_agent VARCHAR(255) DEFAULT '',
			                 status VARCHAR(32) default 'denied',
			                 signature VARCHAR(32) DEFAULT '',
			                 post_id INTEGER DEFAULT '0',
			                 user_id INTEGER DEFAULT '0',
			                 created_at DATETIME DEFAULT '0000-00-00 00:00:00',
			                 updated_at DATETIME DEFAULT '0000-00-00 00:00:00'
			             ) DEFAULT CHARSET=utf8");

			$config = Config::current();
			$config->set("default_comment_status", "denied");
			$config->set("allowed_comment_html", array("strong", "em", "blockquote", "code", "pre", "a"));
			$config->set("comments_per_page", 25);
			$config->set("defensio_api_key", null);
			$config->set("auto_reload_comments", 30);
			$config->set("enable_reload_comments", false);

			Group::add_permission("add_comment", "Add Comments");
			Group::add_permission("add_comment_private", "Add Comments to Private Posts");
			Group::add_permission("edit_comment", "Edit Comments");
			Group::add_permission("edit_own_comment", "Edit Own Comments");
			Group::add_permission("delete_comment", "Delete Comments");
			Group::add_permission("delete_own_comment", "Delete Own Comments");
			Group::add_permission("code_in_comments", "Can Use HTML In Comments");
		}

		static function __uninstall($confirm) {
			if ($confirm)
				SQL::current()->query("DROP TABLE __comments");

			$config = Config::current();
			$config->remove("default_comment_status");
			$config->remove("allowed_comment_html");
			$config->remove("comments_per_page");
			$config->remove("defensio_api_key");
			$config->remove("auto_reload_comments");
			$config->remove("enable_reload_comments");

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
			                $post);
		}

		static function admin_update_comment() {
			if (empty($_POST))
				redirect("/admin/?action=manage_comments");

			$comment = new Comment($_POST['id']);
			if (!$comment->editable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit this comment.", "comments"));

			$visitor = Visitor::current();
			$status = ($visitor->group()->can("edit_comment")) ? $_POST['status'] : $comment->status ;
			$created_at = ($visitor->group()->can("edit_comment")) ? datetime($_POST['created_at']) : $comment->created_at ;
			$comment->update($_POST['author'],
			                 $_POST['author_email'],
			                 $_POST['author_url'],
			                 $_POST['body'],
			                 $status,
			                 $created_at);

			if (isset($_POST['ajax']))
				exit("{ comment_id: ".$_POST['id']." }");

			if ($_POST['status'] == "spam")
				Flash::notice(__("Comment updated."), "/admin/?action=manage_spam");
			else
				Flash::notice(_f("Comment updated. <a href=\"%s\">View Comment &rarr;</a>",
				                 array($comment->post()->url()."#comment_".$comment->id),
				                 "comments"),
				              "/admin/?action=manage_comments");
		}

		static function admin_delete_comment() {
			$admin = AdminController::current();

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
				show_403(__("Access Denied"), __("Invalid security key."));

			$comment = new Comment($_POST['id']);
			if (!$comment->deletable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to delete this comment.", "comments"));

			Comment::delete($_POST['id']);

			if (isset($_POST['ajax']))
				exit;

			Flash::notice(__("Comment deleted."));

			if ($comment->status == "spam")
				redirect("/admin/?action=manage_spam");
			else
				redirect("/admin/?action=manage_comments");
		}

		static function admin_manage_spam() {
			if (!Visitor::current()->group()->can("edit_comment", "delete_comment", true))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to manage any comments.", "comments"));

			$params = array();
			$where = array("status = 'spam'");

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
					$where[] = $test." = :".$test;
					$params[":".$test] = $equals;
				}

				if (!empty($search)) {
					$where[] = "(body LIKE :query)";
					$params[":query"] = "%".$search."%";
				}
			}

			AdminController::current()->context["comments"] = new Paginator(Comment::find(array("placeholders" => true, "where" => $where, "params" => $params)), 25);
		}

		static function admin_purge_spam() {
			if (!Visitor::current()->group()->can("delete_comment"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to delete comments.", "comments"));

			SQL::current()->delete("comments", "status = 'spam'");

			Flash::notice(__("All spam deleted.", "comments"), "/admin/?action=manage_spam");
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

		static function trackback_receive($url, $title, $excerpt, $blog_name) {
			$sql = SQL::current();
			$count = $sql->count("comments",
			                     array("post_id = :id",
			                           "author_url = :url"),
			                     array(":id" => $_GET['id'],
			                           ":url" => $_POST['url']));
			if ($count)
				trackback_respond(true, __("A ping from that URL is already registered.", "comments"));

			Comment::create($blog_name,
			                "",
			                $_POST["url"],
			                '<strong><a href="'.fix($url).'">'.fix($title).'</a></strong>'."\n".$excerpt,
			                new Post($_GET["id"]),
			                "trackback");
		}

		static function pingback($id, $to, $from, $title, $excerpt) {
			$sql = SQL::current();
			$count = $sql->count("comments",
			                     array("post_id = :id",
			                           "author_url = :url"),
			                     array(":id" => $id,
			                           ":url" => $from));
			if ($count)
				return new IXR_Error(48, __("A ping from that URL is already registered.", "comments"));

			Comment::create($title,
			                "",
			                $from,
			                $excerpt,
			                new Post($id),
			                "pingback");
		}

		static function delete_post($post) {
			SQL::current()->delete("comments", "post_id = :id", array(":id" => $post->id));
		}

		static function delete_user($user) {
			SQL::current()->update("comments", "user_id = :id", array("user_id" => 0), array(":id" => $user->id));
		}

		static function admin_comment_settings() {
			if (!Visitor::current()->group()->can("change_settings"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

			if (empty($_POST))
				return;

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$config = Config::current();
			$set = array($config->set("allowed_comment_html", explode(", ", $_POST['allowed_comment_html'])),
			             $config->set("default_comment_status", $_POST['default_comment_status']),
			             $config->set("comments_per_page", $_POST['comments_per_page']),
			             $config->set("auto_reload_comments", $_POST['auto_reload_comments']),
			             $config->set("enable_reload_comments", isset($_POST['enable_reload_comments'])));

			if (!empty($_POST['defensio_api_key'])) {
				$_POST['defensio_api_key'] = trim($_POST['defensio_api_key']);
				$defensio = new Defensio($config->url, $_POST['defensio_api_key']);
				if ($defensio->errorsExist()) {
					Flash::warning(__("Invalid Defensio API key."));
					$set[] = false;
				} else
					$set[] = $config->set("defensio_api_key", $_POST['defensio_api_key']);
			}

			if (!in_array(false, $set))
				Flash::notice(__("Settings updated."), "/admin/?action=comment_settings");
		}

		static function settings_nav($navs) {
			if (Visitor::current()->group()->can("change_settings"))
				$navs["comment_settings"] = array("title" => __("Comments", "comments"));

			return $navs;
		}

		static function manage_nav($navs) {
			if (!Comment::any_editable() and !Comment::any_deletable())
				return $navs;

			$navs["manage_comments"] = array("title" => __("Comments", "comments"),
			                                 "selected" => array("edit_comment", "delete_comment"));

			if (Visitor::current()->group()->can("edit_comment", "delete_comment"))
				$navs["manage_spam"]     = array("title" => __("Spam", "comments"));

			return $navs;
		}

		static function manage_nav_pages($pages) {
			array_push($pages, "manage_comments", "manage_spam", "edit_comment", "delete_comment");
			return $pages;
		}

		public function admin_edit_comment() {
			if (empty($_GET['id']))
				error(__("No ID Specified"), __("An ID is required to edit a comment.", "comments"));

			$admin = AdminController::current();

			$admin->context["comment"] = new Comment($_GET['id'], array("filter" => false));

			if (!$admin->context["comment"]->editable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit this comment.", "comments"));
		}

		static function admin_manage_comments() {
			if (!Comment::any_editable() and !Comment::any_deletable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to manage any comments.", "comments"));

			$params = array();
			$where = array("status != 'spam'");

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
					$where[] = $test." = :".$test;
					$params[":".$test] = $equals;
				}

				if (!empty($search)) {
					$where[] = "(body LIKE :query)";
					$params[":query"] = "%".$search."%";
				}
			}

			$visitor = Visitor::current();
			if (!$visitor->group()->can("edit_comment", "delete_comment", true)) {
				$where[] = "user_id = :visitor_id";
				$params[":visitor_id"] = $visitor->id;
			}

			AdminController::current()->context["comments"] = new Paginator(Comment::find(array("placeholders" => true, "where" => $where, "params" => $params)), 25);
		}

		static function admin_bulk_comments() {
			$from = (!isset($_GET['from'])) ? "manage_comments" : "manage_spam" ;

			if (!isset($_POST['comment']))
				Flash::warning(__("No comments selected."), "/admin/?action=".$from);

			$comments = array_keys($_POST['comment']);

			if (isset($_POST['delete'])) {
				foreach ($comments as $comment) {
					$comment = new Comment($comment);
					if ($comment->deletable())
						Comment::delete($comment->id);
				}

				Flash::notice(__("Selected comments deleted.", "comments"));
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

					$sql->update("comments", "id = :id", array("status" => ":status"), array(":id" => $comment->id, ":status" => "denied"));
				}

				Flash::notice(__("Selected comments denied.", "comments"));
			}
			if (isset($_POST['approve'])) {
				foreach ($comments as $comment) {
					$comment = new Comment($comment);
					if (!$comment->editable())
						continue;

					if ($comment->status == "spam")
						$false_positives[] = $comment->signature;

					$sql->update("comments", "id = :id", array("status" => ":status"), array(":id" => $comment->id, ":status" => "approved"));
				}

				Flash::notice(__("Selected comments approved.", "comments"));
			}
			if (isset($_POST['spam'])) {
				foreach ($comments as $comment) {
					$comment = new Comment($comment);
					if (!$comment->editable())
						continue;

					$sql->update("comments", "id = :id", array("status" => ":status"), array(":id" => $comment->id, ":status" => "spam"));

					$false_negatives[] = $comment->signature;
				}

				Flash::notice(__("Selected comments marked as spam.", "comments"));
			}

			if (!empty($config->defensio_api_key)) {
				$defensio = new Defensio($config->url, $config->defensio_api_key);
				if (!empty($false_positives))
					$defensio->submitFalsePositives(array("owner-url" => $config->url, "signatures" => implode(",", $false_positives)));
				if (!empty($false_negatives))
					$defensio->submitFalseNegatives(array("owner-url" => $config->url, "signatures" => implode(",", $false_negatives)));
			}

			redirect("/admin/?action=".$from);
		}

		static function manage_posts_column_header() {
			echo '<th>'.__("Comments", "comments").'</th>';
		}

		static function manage_posts_column($post) {
			echo '<td align="center"><a href="'.$post->url().'#comments">'.$post->comment_count.'</a></td>';
		}

		static function scripts($scripts) {
			$scripts[] = Config::current()->chyrp_url."/modules/comments/javascript.php";
			return $scripts;
		}

		static function ajax() {
			header("Content-Type: application/x-javascript", true);

			$config  = Config::current();
			$sql     = SQL::current();
			$trigger = Trigger::current();
			$visitor = Visitor::current();
			$theme   = Theme::current();

			switch($_POST['action']) {
				case "reload_comments":
					$post = new Post($_POST['post_id']);
					if ($post->latest_comment > $_POST['last_comment']) {
						$new_comments = $sql->select("comments",
						                             "id",
						                             array("post_id = :post_id",
						                                   "id > :last_comment",
						                                   "status != 'spam'",
						                                   "(status != 'denied' OR (
						                                        status = 'denied' AND (
						                                            (
						                                                author_ip != 0 AND
						                                                author_ip = :current_ip
						                                            ) OR (
						                                                user_id != 0 AND
						                                                user_id = :visitor_id
						                                            )
						                                        )
						                                    ))"),
						                             "created_at ASC",
						                             array(":post_id" => $_POST['post_id'],
						                                   ":last_comment" => $_POST['last_comment'],
						                                   ":current_ip" => ip2long($_SERVER['REMOTE_ADDR']),
						                                   ":visitor_id" => $visitor->id
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
					else
						require "edit_form.php";

					break;
			}
		}

		public function import_chyrp_post($entry, $post) {
			$chyrp = $entry->children("http://chyrp.net/export/1.0/");
			if (!isset($chyrp->comment)) return;

			$sql = SQL::current();

			foreach ($chyrp->comment as $comment) {
				$chyrp = $comment->children("http://chyrp.net/export/1.0/");
				$comment = $comment->children("http://www.w3.org/2005/Atom");

				$login = $comment->author->children("http://chyrp.net/export/1.0/")->login;
				$user_id = $sql->select("users", "id", "login = :login", "id DESC",
				                        array(":login" => $login))->fetchColumn();

				Comment::add(unsafe($comment->content),
				             unsafe($comment->author->name),
				             unsafe($comment->author->uri),
				             unsafe($comment->author->email),
				             $chyrp->author->ip,
				             unsafe($chyrp->author->agent),
				             $chyrp->status,
				             $chyrp->signature,
				             datetime($comment->published),
				             ($comment->published == $comment->updated) ? "0000-00-00 00:00:00" : datetime($comment->updated),
				             $post,
				             ($user_id ? $user_id : 0));
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
				fallback($comment->comment_author_IP, "");

				Comment::add($comment->comment_content,
				             $comment->comment_author,
				             $comment->comment_author_url,
				             $comment->comment_author_email,
				             $comment->comment_author_IP,
				             "",
				             (isset($comment->comment_approved) && $comment->comment_approved == "1" ? "approved" : "denied"),
				             "",
				             $comment->comment_date,
				             null,
				             $post,
				             0);
			}
		}

		static function import_textpattern_post($array, $post, $link) {
			$get_comments = mysql_query("SELECT * FROM {$_POST['prefix']}txp_discuss WHERE parentid = {$array["ID"]} ORDER BY discussid ASC", $link) or error(__("Database Error"), mysql_error());

			while ($comment = mysql_fetch_array($get_comments)) {
				$translate_status = array(-1 => "spam",
				                          0 => "denied",
				                          1 => "approved");
				$status = str_replace(array_keys($translate_status), array_values($translate_status), $comment["visible"]);

				Comment::add($comment["message"],
				             $comment["name"],
				             $comment["web"],
				             $comment["email"],
				             $comment["ip"],
				             "",
				             $status,
				             "",
				             $comment["posted"],
				             null,
				             $post,
				             0);
			}
		}

		static function import_movabletype_post($array, $post, $link) {
			$get_comments = mysql_query("SELECT * FROM mt_comment WHERE comment_entry_id = {$array["entry_id"]} ORDER BY comment_id ASC", $link) or error(__("Database Error"), mysql_error());

			while ($comment = mysql_fetch_array($get_comments))
				Comment::add($comment["comment_text"],
				             $comment["comment_author"],
				             $comment["comment_url"],
				             $comment["comment_email"],
				             $comment["comment_ip"],
				             "",
				             ($comment["comment_visible"] ? "approved" : denied),
				             "",
				             $comment["comment_created_on"],
				             $comment["comment_modified_on"],
				             $post,
				             0);
		}

		static function view_feed() {
			global $post, $comments, $title;

			$title = $post->title();
			fallback($title, ucfirst($post->feather)." Post #".$post->id);

			$title = _f("Comments on &#8220;%s&#8221;", array(fix($title)), "comments");

			$ids = array_reverse($post->comments->array[0]);

			$comments = array();
			for ($i = 0; $i < 20; $i++)
				if (isset($ids[$i]))
					$comments[] = new Comment(null, array("read_from" => $ids[$i]));

			Route::current()->action = "comments_feed";
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
			$sql = SQL::current();
			$config = Config::current();
			$trigger = Trigger::current();
			$visitor = Visitor::current();
			$route = Route::current();
			$post->commentable = Comment::user_can($post);

			if (isset($route->action) and $route->action == "view") {
				$get_comments = $sql->select("comments", # table
				                             "*", # fields
				                             array("post_id = :post_id",
				                                   "status != 'spam'",
				                                   "(status != 'denied' OR (
				                                        status = 'denied' AND (
				                                            (
				                                                author_ip != 0 AND
				                                                author_ip = :current_ip
				                                            ) OR (
				                                                user_id != 0 AND
				                                                user_id = :visitor_id
				                                            )
				                                        )
				                                    ))"),
				                             "created_at ASC", # order
				                             array(
				                                 ":post_id" => $post->id,
				                                 ":current_ip" => ip2long($_SERVER['REMOTE_ADDR']),
				                                 ":visitor_id" => $visitor->id
				                             ));

				$post->comments = array();
				foreach ($get_comments->fetchAll() as $comment)
					$post->comments[] = $comment;

				$post->comments = new Paginator(array($post->comments, "Comment"), $config->comments_per_page, "comments_page");

				$shown_dates = array();
				foreach ($post->comments->paginated as &$comment) {
					$comment->date_shown = in_array(when("m-d-Y", $comment->created_at), $shown_dates);
					if (!in_array(when("m-d-Y", $comment->created_at), $shown_dates))
						$shown_dates[] = when("m-d-Y", $comment->created_at);

					$comment->is_author = ($post->user_id == $comment->user_id);
				}
			}
		}

		static function posts_get($options) {
			$options["select"][]  = "COUNT(comments.id) AS comment_count";
			$options["select"][]  = "MAX(comments.created_at) AS latest_comment";

			$options["left_join"][] = array("table" => "comments",
			                                "where" => array("post_id = posts.id",
			                                                 "status != 'spam'",
			                                                 "(status != 'denied' OR (
			                                                      status = 'denied' AND (
			                                                          (
			                                                              author_ip != 0 AND
			                                                              author_ip = :current_ip
			                                                          ) OR (
			                                                              user_id != 0 AND
			                                                              user_id = :visitor_id
			                                                          )
			                                                      )
			                                                  ))"));

			$options["params"][":current_ip"] = ip2long($_SERVER['REMOTE_ADDR']);
			$options["params"][":visitor_id"] = Visitor::current()->id;

			$options["group"][] = "id";

			return $options;
		}

		public function cacher_regenerate_posts_triggers($array) {
			$array = array_merge($array, array("add_comment", "update_comment", "delete_comment"));
			return $array;
		}

		public function posts_export($atom, $post) {
			$comments = Comment::find(array("where" => "post_id = :post_id",
			                                "params" => array(":post_id" => $post->id)),
			                          array("filter" => false));

			foreach ($comments as $comment) {
				$updated = ($comment->updated) ? $comment->updated_at : $comment->created_at ;

				$atom.= "		<chyrp:comment>\r";
				$atom.= '			<updated>'.when("c", $updated).'</updated>'."\r";
				$atom.= '			<published>'.when("c", $comment->created_at).'</published>'."\r";
				$atom.= '			<author chyrp:user_id="'.$comment->user_id.'">'."\r";
				$atom.= "				<name>".safe($comment->author)."</name>\r";
				if (!empty($comment->author_url))
					$atom.= "				<uri>".safe($comment->author_url)."</uri>\r";
				$atom.= "				<email>".safe($comment->author_email)."</email>\r";
				$atom.= "				<chyrp:login>".safe(fallback($comment->user()->login))."</chyrp:login>\r";
				$atom.= "				<chyrp:ip>".long2ip($comment->author_ip)."</chyrp:ip>\r";
				$atom.= "				<chyrp:agent>".safe($comment->author_agent)."</chyrp:agent>\r";
				$atom.= "			</author>\r";
				$atom.= "			<content>".safe($comment->body)."</content>\r";

				foreach (array("status", "signature") as $attr)
					$atom.= "			<chyrp:".$attr.">".safe($comment->$attr)."</chyrp:".$attr.">\r";

				$atom.= "		</chyrp:comment>\r";
			}

			return $atom;
		}

		public function manage_nav_show($possibilities) {
			$possibilities[] = (Comment::any_editable() or Comment::any_deletable());
			return $possibilities;
		}

		public function determine_action($action) {
			if ($action != "manage") return;

			if (Comment::any_editable() or Comment::any_deletable())
				return "manage_comments";
		}

		public function route_comments_rss() {
			header("HTTP/1.1 301 Moved Permanently");
			redirect("comments_feed/");
		}
	}
