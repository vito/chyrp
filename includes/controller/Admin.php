<?php
	/**
	 * Class: Admin Controller
	 * The logic behind the Admin area.
	 */
	class AdminController {
		# Array: $context
		# Contains the context for various admin pages, to be passed to the Twig templates.
		public $context = array();

		# String: $selected_bookmarklet
		# Holds the name of the Feather to be selected when they open the bookmarklet.
		public $selected_bookmarklet;

		private function __construct() { }

		/**
		 * Function: write
		 * Post writing.
		 */
		public function write_post() {
			$visitor = Visitor::current();
			if (!$visitor->group()->can("add_post", "add_draft"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

			$config = Config::current();

			if (empty($config->enabled_feathers))
				error(__("No Feathers"), __("Please install a feather or two in order to add a post."));

			fallback($_GET['feather'], $config->enabled_feathers[0]);

			global $feathers;

			$this->context["feathers"]       = $feathers;
			$this->context["feather"]        = $feathers[$_GET['feather']];
		}

		/**
		 * Function: bookmarklet
		 * Post writing, from the bookmarklet.
		 */
		public function bookmarklet() {
			$visitor = Visitor::current();
			if (!$visitor->group()->can("add_post", "add_draft"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

			$config = Config::current();

			if (empty($config->enabled_feathers))
				error(__("No Feathers"), __("Please install a feather or two in order to add a post."));

			if (!isset($this->selected_bookmarklet))
				fallback($feather, $config->enabled_feathers[0]);
			else
				$feather = $this->selected_bookmarklet;

			global $feathers;

			$this->context["done"] = isset($_GET['done']);

			$this->context["feathers"]         = $feathers;
			$this->context["selected_feather"] = $feathers[$feather];

			if (!$this->context["done"]) {
				$this->context["args"] = array("url" => urldecode(stripslashes($_GET['url'])),
				                               "title" => urldecode(stripslashes($_GET['title'])),
				                               "selection" => urldecode(stripslashes($_GET['selection'])));

				$this->context["args"]["page_url"]   =& $this->context["args"]["url"];
				$this->context["args"]["page_title"] =& $this->context["args"]["title"];
			}
		}

		/**
		 * Function: add_post
		 * Adds a post when the form is submitted.
		 */
		public function add_post() {
			$visitor = Visitor::current();
			if (!$visitor->group()->can("add_post", "add_draft"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			global $feathers;

			$post = $feathers[$_POST['feather']]->submit();

			if (!isset($_POST['bookmarklet']))
				Flash::notice(__("Post created!"), $post->redirect);
			else
				redirect($post->redirect);
		}

		/**
		 * Function: edit_post
		 * Post editing.
		 */
		public function edit_post() {
			if (empty($_GET['id']))
				error(__("No ID Specified"), __("An ID is required to edit a post."));

			$this->context["post"] = new Post($_GET['id'], array("filter" => false));

			if (!$this->context["post"]->editable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit this post."));

			global $feathers;
			$this->context["feather"] = $feathers[$this->context["post"]->feather];
		}

		/**
		 * Function: update_post
		 * Updates a post when the form is submitted.
		 */
		public function update_post() {
			$post = new Post($_POST['id']);

			if ($post->no_results)
				Flash::warning(__("Post not found."), "/admin/?action=manage_posts");

			if (!$post->editable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit this post."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			global $feathers;
			$feathers[$post->feather]->update();

			if (!isset($_POST['ajax']))
				Flash::notice(_f("Post updated. <a href=\"%s\">View Post &rarr;</a>",
				                 array($post->url())),
				              "/admin/?action=manage_posts");
			else
				exit((string) $_POST['id']);
		}

		/**
		 * Function: delete_post
		 * Post deletion (confirm page).
		 */
		public function delete_post() {
			$this->context["post"] = new Post($_GET['id']);

			if (!$this->context["post"]->deletable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to delete this post."));
		}

		/**
		 * Function: destroy_post
		 * Destroys a post (the real deal).
		 */
		public function destroy_post() {
			if (empty($_POST['id']))
				error(__("No ID Specified"), __("An ID is required to delete a post."));

			if ($_POST['destroy'] == "bollocks")
				redirect("/admin/?action=manage_posts");

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$post = new Post($_POST['id']);
			if (!$post->deletable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to delete this post."));

			Post::delete($_POST['id']);

			Flash::notice(__("Post deleted."), "/admin/?action=manage_posts");
		}

		/**
		 * Function: manage_posts
		 * Post managing.
		 */
		public function manage_posts() {
			if (!Post::any_editable() and !Post::any_deletable())
				show_403(__("Access Denied"), __("You do not have sufficient privileges to manage any posts."));

			$params = array();
			$where = array();

			if (!empty($_GET['query'])) {
				$search = "";
				$matches = array();

				$queries = explode(" ", $_GET['query']);
				foreach ($queries as $query)
					if (!preg_match("/([a-z0-9_]+):(.+)/", $query))
						$search.= $query;
					else
						$matches[] = $query;

				foreach ($matches as $match) {
					$match = explode(":", $match);
					$test = $match[0];
					$equals = $match[1];
					if ($test == "author") {
						$user = new User(null, array("where" => "login = :login", "params" => array(":login" => $equals)));
						$test = "user_id";
						$equals = $user->id;
					}
					$where[] = $test." = :".$test;
					$params[":".$test] = $equals;
				}

				if (!empty($search)) {
					$where[] = "xml LIKE :query";
					$params[":query"] = "%".$search."%";
				}
			}

			if (!empty($_GET['month'])) {
				$where[] = "created_at LIKE :when";
				$params[":when"] = $_GET['month']."-%";
			}

			$visitor = Visitor::current();
			if (!$visitor->group()->can("view_draft", "edit_draft", "edit_post", "delete_draft", "delete_post")) {
				$where[] = "user_id = :visitor_id";
				$params[':visitor_id'] = $visitor->id;
			}

			$this->context["posts"] = new Paginator(Post::find(array("placeholders" => true, "where" => $where, "params" => $params)), 25);
		}

		/**
		 * Function: write_page
		 * Page creation.
		 */
		public function write_page() {
			if (!Visitor::current()->group()->can("add_page"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to create pages."));

			$this->context["pages"] = Page::find();
		}

		/**
		 * Function: add_page
		 * Adds a page when the form is submitted.
		 */
		public function add_page() {
			if (!Visitor::current()->group()->can("add_page"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to create pages."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$show_in_list = !empty($_POST['show_in_list']);
			$clean = (!empty($_POST['slug'])) ? $_POST['slug'] : sanitize($_POST['title']) ;
			$url = Page::check_url($clean);

			$page = Page::add($_POST['title'], $_POST['body'], $_POST['parent_id'], $show_in_list, 0, $clean, $url);

			Flash::notice(__("Page created!"), $page->url());
		}

		/**
		 * Function: edit_page
		 * Page editing.
		 */
		public function edit_page() {
			if (!Visitor::current()->group()->can("edit_page"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit this page."));

			if (empty($_GET['id']))
				error(__("No ID Specified"), __("An ID is required to edit a page."));

			$this->context["page"] = new Page($_GET['id'], array("filter" => false));
			$this->context["pages"] = Page::find(array("where" => "id != :id", "params" => array(":id" => $_GET['id'])));
		}

		/**
		 * Function: update_page
		 * Updates a page when the form is submitted.
		 */
		public function update_page() {
			if (!Visitor::current()->group()->can("edit_page"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit pages."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$page = new Page($_POST['id']);

			if ($page->no_results)
				Flash::warning(__("Page not found."), "/admin/?action=manage_pages");

			$page->update($_POST['title'], $_POST['body'], $_POST['parent_id'], !empty($_POST['show_in_list']), $page->list_order, $_POST['slug']);

			if (!isset($_POST['ajax']))
				Flash::notice(_f("Page updated. <a href=\"%s\">View Page &rarr;</a>",
				                 array($page->url())),
				              "/admin/?action=manage_pages");
		}

		/**
		 * Function: reorder_pages
		 * Reorders pages.
		 */
		public function reorder_pages() {
			foreach ($_POST['list_order'] as $id => $order) {
				$page = new Page($id);
				$page->update($page->title, $page->body, $page->parent_id, $page->show_in_list, $order, $page->url);
			}

			Flash::notice(__("Pages reordered."), "/admin/?action=manage_pages");
		}

		/**
		 * Function: delete_page
		 * Page deletion (confirm page).
		 */
		public function delete_page() {
			if (!Visitor::current()->group()->can("delete_page"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to delete pages."));

			$this->context["page"] = new Page($_GET['id']);
		}

		/**
		 * Function: destroy_page
		 * Destroys a page.
		 */
		public function destroy_page() {
			if (!Visitor::current()->group()->can("delete_page"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to delete pages."));

			if (empty($_POST['id']))
				error(__("No ID Specified"), __("An ID is required to delete a post."));

			if ($_POST['destroy'] == "bollocks")
				redirect("/admin/?action=manage_pages");

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$page = new Page($_POST['id']);
			foreach ($page->children() as $child)
				if (isset($_POST['destroy_children']))
					Page::delete($child->id, true);
				else
					$child->update($child->title, $child->body, 0, $child->show_in_list, $child->list_order, $child->url);

			Page::delete($_POST['id']);

			Flash::notice(__("Page deleted."), "/admin/?action=manage_pages");
		}

		/**
		 * Function: manage_pages
		 * Page managing.
		 */
		public function manage_pages() {
			$visitor = Visitor::current();
			if (!$visitor->group()->can("edit_page") and !$visitor->group()->can("delete_page"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to manage pages."));

			$params = array();
			$where = array();

			if (!empty($_GET['query'])) {
				$search = "";
				$matches = array();

				$queries = explode(" ", $_GET['query']);
				foreach ($queries as $query)
					if (!preg_match("/([a-z0-9_]+):(.+)/", $query))
						$search.= $query;
					else
						$matches[] = $query;

				foreach ($matches as $match) {
					$match = explode(":", $match);
					$test = $match[0];
					$equals = $match[1];
					if ($test == "author") {
						$user = new User(null, array("where" => "login = :login", "params" => array(":login" => $equals)));
						$test = "user_id";
						$equals = ($user->no_results) ? 0 : $user->id ;
					}
					$where[] = $test." = :".$test;
					$params[":".$test] = $equals;
				}

				if (!empty($search)) {
					$where[] = "(title LIKE :query OR body LIKE :query)";
					$params[":query"] = "%".$search."%";
				}
			}

			$this->context["pages"] = new Paginator(Page::find(array("placeholders" => true, "where" => $where, "params" => $params)), 25);
		}

		/**
		 * Function: new_user
		 * User creation.
		 */
		public function new_user() {
			if (!Visitor::current()->group()->can("add_user"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to add users."));

			$config = Config::current();

			$this->context["default_group"] = new Group($config->default_group);
			$this->context["groups"] = Group::find(array("where" => array("id != :guest_id", "id != :default_id"),
			                                             "params" => array(":guest_id" => $config->guest_group, ":default_id" => $config->default_group),
			                                             "order" => "id DESC"));
		}

		/**
		 * Function: add_user
		 * Add a user when the form is submitted.
		 */
		public function add_user() {
			if (!Visitor::current()->group()->can("add_user"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to add users."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			if (empty($_POST['login']))
				error(__("Error"), __("Please enter a username for your account."));

			$check = new User(null, array("where" => "login = :login",
			                              "params" => array(":login" => $_POST['login'])));
			if (!$check->no_results)
				error(__("Error"), __("That username is already in use."));

			if (empty($_POST['password1']) or empty($_POST['password2']))
				error(__("Error"), __("Password cannot be blank."));
			if (empty($_POST['email']))
				error(__("Error"), __("E-mail address cannot be blank."));
			if ($_POST['password1'] != $_POST['password2'])
				error(__("Error"), __("Passwords do not match."));
			if (!eregi("^[[:alnum:]][a-z0-9_.-\+]*@[a-z0-9.-]+\.[a-z]{2,6}$",$_POST['email']))
				error(__("Error"), __("Unsupported e-mail address."));

			User::add($_POST['login'], $_POST['password1'], $_POST['email'], $_POST['full_name'], $_POST['website'], null, $_POST['group']);

			Flash::notice(__("User added."), "/admin/?action=manage_users");
		}

		/**
		 * Function: edit_user
		 * User editing.
		 */
		public function edit_user() {
			if (!Visitor::current()->group()->can("edit_user"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit this user."));

			if (empty($_GET['id']))
				error(__("No ID Specified"), __("An ID is required to edit a user."));

			$this->context["user"] = new User($_GET['id']);
			$this->context["groups"] = Group::find(array("order" => "id ASC",
			                                             "where" => "id != :guest_id",
			                                             "params" => array(":guest_id" => Config::current()->guest_group)));
		}

		/**
		 * Function: update_user
		 * Updates a user when the form is submitted.
		 */
		public function update_user() {
			if (empty($_POST['id']))
				error(__("No ID Specified"), __("An ID is required to edit a user."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$visitor = Visitor::current();

			if (!$visitor->group()->can("edit_user"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit users."));

			$user = new User($_POST['id']);

			if ($user->no_results)
				Flash::warning(__("User not found."), "/admin/?action=manage_user");

			$password = (!empty($_POST['new_password1']) and $_POST['new_password1'] == $_POST['new_password2']) ?
			            md5($_POST['new_password1']) :
			            $user->password ;

			$user->update($_POST['login'], $password, $_POST['email'], $_POST['full_name'], $_POST['website'], $_POST['group']);

			if ($_POST['id'] == $visitor->id)
				$_SESSION['password'] = $password;

			Flash::notice(__("User updated."), "/admin/?action=manage_users");
		}

		/**
		 * Function: delete_user
		 * User deletion.
		 */
		public function delete_user() {
			if (!Visitor::current()->group()->can("delete_user"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to delete users."));

			$this->context["user"] = new User($_GET['id']);
			$this->context["users"] = User::find(array("where" => "id != :deleting_id",
			                                           "params" => array(":deleting_id" => $_GET['id'])));
		}

		/**
		 * Function: destroy_user
		 * Destroys a user.
		 */
		public function destroy_user() {
			if (!Visitor::current()->group()->can("delete_user"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to delete users."));

			if (empty($_POST['id']))
				error(__("No ID Specified"), __("An ID is required to delete a user."));

			if ($_POST['destroy'] == "bollocks")
				redirect("/admin/?action=manage_users");

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$sql = SQL::current();
			$user = new User($_POST['id']);

			if (isset($_POST['posts'])) {
				if ($_POST['posts'] == "delete")
					foreach ($user->posts() as $post)
						Post::delete($post->id);
				elseif ($_POST['posts'] == "move")
					$sql->update("posts",
					             "user_id = :deleting_id",
					             array("user_id" => ":user_id"),
					             array(":user_id" => $_POST['move_posts'],
					                   ":deleting_id" => $user->id));
			}

			if (isset($_POST['pages'])) {
				if ($_POST['pages'] == "delete")
					foreach ($user->pages() as $page)
						Page::delete($page->id);
				elseif ($_POST['pages'] == "move")
					$sql->update("pages",
					             "user_id = :deleting_id",
					             array("user_id" => ":user_id"),
					             array(":user_id" => $_POST['move_pages'],
					                   ":deleting_id" => $user->id));
			}

			User::delete($_POST['id']);

			Flash::notice(__("User deleted."), "/admin/?action=manage_users");
		}

		/**
		 * Function: manage_users
		 * User managing.
		 */
		public function manage_users() {
			$visitor = Visitor::current();
			if (!$visitor->group()->can("edit_user") and !$visitor->group()->can("delete_user") and !$visitor->group()->can("add_user"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to manage users."));

			$params = array();
			$where = array();

			if (!empty($_GET['query'])) {
				$search = "";
				$matches = array();

				$queries = explode(" ", $_GET['query']);
				foreach ($queries as $query)
					if (!preg_match("/([a-z0-9_]+):(.+)/", $query))
						$search.= $query;
					else
						$matches[] = $query;

				foreach ($matches as $match) {
					$match = explode(":", $match);
					$test = $match[0];
					$equals = $match[1];
					if ($test == "group") {
						$group = new Group(null, array("where" => "name = :name", "params" => array(":name" => $equals)));
						$test = "group_id";
						$equals = ($group->no_results) ? 0 : $group->id ;
					}
					$where[] = $test." = :".$test;
					$params[":".$test] = $equals;
				}

				if (!empty($search)) {
					$where[] = "(login LIKE :query OR full_name LIKE :query OR email LIKE :query OR website LIKE :query)";
					$params[":query"] = "%".$_GET['query']."%";
				}
			}

			$this->context["users"] = new Paginator(User::find(array("placeholders" => true, "where" => $where, "params" => $params)), 25);
		}

		/**
		 * Function: new_group
		 * Group creation.
		 */
		public function new_group() {
			if (!Visitor::current()->group()->can("add_group"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to create groups."));

			$this->context["permissions"] = SQL::current()->select("permissions")->fetchAll();
		}

		/**
		 * Function: add_group
		 * Adds a group when the form is submitted.
		 */
		public function add_group() {
			if (!Visitor::current()->group()->can("add_group"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to create groups."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			Group::add($_POST['name'], array_keys($_POST['permissions']));

			Flash::notice(__("Group added."), "/admin/?action=manage_groups");
		}

		/**
		 * Function: edit_group
		 * Group editing.
		 */
		public function edit_group() {
			if (!Visitor::current()->group()->can("edit_group"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit groups."));

			$this->context["group"] = new Group($_GET['id']);
			$this->context["permissions"] = SQL::current()->select("permissions")->fetchAll();
		}

		/**
		 * Function: update_group
		 * Updates a group when the form is submitted.
		 */
		public function update_group() {
			if (!Visitor::current()->group()->can("edit_group"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to edit groups."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$permissions = array_keys($_POST['permissions']);

			$group = new Group($_POST['id']);

			if ($group->no_results)
				Flash::warning(__("Group not found."), "/admin/?action=manage_groups");

			$group->update($_POST['name'], $permissions);

			Flash::notice(__("Group updated."), "/admin/?action=manage_groups");
		}

		/**
		 * Function: delete_group
		 * Group deletion (confirm page).
		 */
		public function delete_group() {
			if (!Visitor::current()->group()->can("delete_group"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to delete groups."));

			$this->context["group"] = new Group($_GET['id']);
			$this->context["groups"] = Group::find(array("where" => "id != :group_id",
			                                             "order" => "id ASC",
			                                             "params" => array(":group_id" => $_GET['id'])));
		}

		/**
		 * Function: destroy_group
		 * Destroys a group.
		 */
		public function destroy_group() {
			if (!Visitor::current()->group()->can("delete_group"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to delete groups."));

			if (!isset($_POST['id']))
				error(__("No ID Specified"), __("An ID is required to delete a group."));

			if ($_POST['destroy'] == "bollocks")
				redirect("/admin/?action=manage_groups");

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$group = new Group($_POST['id']);
			foreach ($group->members() as $user)
				$user->update($user->login, $user->password, $user->email, $user->full_name, $user->website, $_POST['move_group']);

			$config = Config::current();
			if (!empty($_POST['default_group']))
				$config->set("default_group", $_POST['default_group']);
			if (!empty($_POST['guest_group']))
				$config->set("guest_group", $_POST['guest_group']);

			Group::delete($_POST['id']);

			Flash::notice(__("Group deleted."), "/admin/?action=manage_groups");
		}

		/**
		 * Function: manage_groups
		 * Group managing.
		 */
		public function manage_groups() {
			$visitor = Visitor::current();
			if (!$visitor->group()->can("edit_group") and !$visitor->group()->can("delete_group") and !$visitor->group()->can("add_group"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to manage groups."));

			if (!empty($_GET['search'])) {
				$user = new User(null, array("where" => "login = :search", "params" => array(":search" => $_GET['search'])));
				$this->context["groups"] = array($user->group());
			} else
				$this->context["groups"] = new Paginator(Group::find(array("placeholders" => true, "order" => "id ASC")), 10);
		}

		/**
		 * Function: export
		 * Export posts, pages, etc.
		 */
		public function export() {
			if (!Visitor::current()->group()->can("add_post"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to export content."));

			if (empty($_POST))
				return;

			$config = Config::current();
			$trigger = Trigger::current();
			$route = Route::current();
			$exports = array();

			if (isset($_POST['posts'])) {
				if (!empty($_POST['filter_posts'])) {
					$search = "";
					$matches = array();

					$queries = explode(" ", $_POST['filter_posts']);
					foreach ($queries as $query)
						if (!preg_match("/([a-z0-9_]+):(.+)/", $query))
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
						$where[] = "xml LIKE :query";
						$params[":query"] = "%".$search."%";
					}
				} else
					list($where, $params) = array(false, array());

				$posts = Post::find(array("where" => $where, "params" => $params, "order" => "id ASC"),
				                    array("filter" => false));

				$latest_timestamp = 0;
				foreach ($posts as $post)
					if (strtotime($post->created_at) > $latest_timestamp)
						$latest_timestamp = strtotime($post->created_at);

				$id = substr(strstr($config->url, "//"), 2);
				$id = str_replace("#", "/", $id);
				$id = preg_replace("/(".preg_quote(parse_url($config->url, PHP_URL_HOST)).")/", "\\1,".date("Y", $latest_timestamp).":", $id, 1);

				$posts_atom = '<?xml version="1.0" encoding="utf-8"?>'."\r";
				$posts_atom.= '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:chyrp="http://chyrp.net/export/1.0/">'."\r";
				$posts_atom.= '	<title>'.safe($config->name).' Posts</title>'."\r";
				$posts_atom.= '	<subtitle>'.safe($config->description).'</subtitle>'."\r";
				$posts_atom.= '	<id>tag:'.parse_url($config->url, PHP_URL_HOST).','.date("Y", $latest_timestamp).':Chyrp</id>'."\r";
				$posts_atom.= '	<updated>'.date("c", $latest_timestamp).'</updated>'."\r";
				$posts_atom.= '	<link href="'.$config->url.'" rel="self" type="application/atom+xml" />'."\r";
				$posts_atom.= '	<generator uri="http://chyrp.net/" version="'.CHYRP_VERSION.'">Chyrp</generator>'."\r";

				foreach ($posts as $post) {
					$title = fix($post->title(), false);
					fallback($title, ucfirst($post->feather)." Post #".$post->id);

					$updated = ($post->updated) ? $post->updated_at : $post->created_at ;

					$tagged = substr(strstr(url("id/".$post->id."/"), "//"), 2);
					$tagged = str_replace("#", "/", $tagged);
					$tagged = preg_replace("/(".preg_quote(parse_url($post->url(), PHP_URL_HOST)).")/", "\\1,".when("Y-m-d", $updated).":", $tagged, 1);

					$split = explode("\n", $post->xml);
					array_shift($split);
					$content = implode("\n", $split);
					$content = preg_replace("/(^<|<\/)post>/", "", $content);
					$content = preg_replace("/><([^\/])/", ">\n\t\t\t<\\1", $content);

					$url = $post->url();
					$posts_atom.= '	<entry xml:base="'.fix($url).'">'."\r";
					$posts_atom.= '		<title type="html">'.$title.'</title>'."\r";
					$posts_atom.= '		<id>tag:'.$tagged.'</id>'."\r";
					$posts_atom.= '		<updated>'.when("c", $updated).'</updated>'."\r";
					$posts_atom.= '		<published>'.when("c", $post->created_at).'</published>'."\r";
					$posts_atom.= '		<link href="'.fix($trigger->filter($url, "post_export_url", $post)).'" />'."\r";
					$posts_atom.= '		<author chyrp:user_id="'.$post->user_id.'">'."\r";
					$posts_atom.= '			<name>'.safe(fallback($post->user()->full_name, $post->user()->login, true)).'</name>'."\r";

					if (!empty($post->user()->website))
						$posts_atom.= '			<uri>'.safe($post->user()->website).'</uri>'."\r";

					$posts_atom.= '			<chyrp:login>'.safe($post->user()->login).'</chyrp:login>'."\r";
					$posts_atom.= '		</author>'."\r";
					$posts_atom.= '		<content>'."\r";
					$posts_atom.= '			'.$content;
					$posts_atom.= '		</content>'."\r";

					foreach (array("feather", "clean", "url", "pinned", "status") as $attr)
						$posts_atom.= '		<chyrp:'.$attr.'>'.safe($post->$attr).'</chyrp:'.$attr.'>'."\r";

					$trigger->filter($posts_atom, "posts_export", $post);

					$posts_atom.= '	</entry>'."\r";

				}
				$posts_atom.= '</feed>'."\r";

				$exports["posts.atom"] = $posts_atom;
			}

			if (isset($_POST['pages'])) {
				if (!empty($_POST['filter_pages'])) {
					$search = "";
					$matches = array();

					$queries = explode(" ", $_POST['filter_pages']);
					foreach ($queries as $query)
						if (!preg_match("/([a-z0-9_]+):(.+)/", $query))
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
						$where[] = "(title LIKE :query OR body LIKE :query)";
						$params[":query"] = "%".$search."%";
					}
				} else
					list($where, $params) = array(null, array());

				$pages = Page::find(array("where" => $where, "params" => $params, "order" => "id ASC"),
				                    array("filter" => false));

				$latest_timestamp = 0;
				foreach ($pages as $page)
					if (strtotime($page->created_at) > $latest_timestamp)
						$latest_timestamp = strtotime($page->created_at);

				$pages_atom = '<?xml version="1.0" encoding="utf-8"?>'."\r";
				$pages_atom.= '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:chyrp="http://chyrp.net/export/1.0/">'."\r";
				$pages_atom.= '	<title>'.fix($config->name).' Pages</title>'."\r";
				$pages_atom.= '	<subtitle>'.fix($config->description).'</subtitle>'."\r";
				$pages_atom.= '	<id>tag:'.parse_url($config->url, PHP_URL_HOST).','.date("Y", $latest_timestamp).':Chyrp</id>'."\r";
				$pages_atom.= '	<updated>'.date("c", $latest_timestamp).'</updated>'."\r";
				$pages_atom.= '	<link href="'.$config->url.'" rel="self" type="application/atom+xml" />'."\r";
				$pages_atom.= '	<generator uri="http://chyrp.net/" version="'.CHYRP_VERSION.'">Chyrp</generator>'."\r";

				foreach ($pages as $page) {
					$updated = ($page->updated) ? $page->updated_at : $page->created_at ;

					$tagged = substr(strstr($page->url(), "//"), 2);
					$tagged = str_replace("#", "/", $tagged);
					$tagged = preg_replace("/(".preg_quote(parse_url($page->url(), PHP_URL_HOST)).")/", "\\1,".when("Y-m-d", $updated).":", $tagged, 1);

					$url = $page->url();
					$pages_atom.= '	<entry xml:base="'.fix($url).'" chyrp:parent_id="'.$page->parent_id.'">'."\r";
					$pages_atom.= '		<title type="html">'.safe($page->title).'</title>'."\r";
					$pages_atom.= '		<id>tag:'.$tagged.'</id>'."\r";
					$pages_atom.= '		<updated>'.when("c", $updated).'</updated>'."\r";
					$pages_atom.= '		<published>'.when("c", $page->created_at).'</published>'."\r";
					$pages_atom.= '		<link href="'.fix($trigger->filter($url, "page_export_url", $page)).'" />'."\r";
					$pages_atom.= '		<author chyrp:user_id="'.fix($page->user_id).'">'."\r";
					$pages_atom.= '			<name>'.safe(fallback($page->user()->full_name, $page->user()->login, true)).'</name>'."\r";

					if (!empty($page->user()->website))
						$pages_atom.= '			<uri>'.safe($page->user()->website).'</uri>'."\r";

					$pages_atom.= '			<chyrp:login>'.safe($page->user()->login).'</chyrp:login>'."\r";
					$pages_atom.= '		</author>'."\r";
					$pages_atom.= '		<content type="html">'.safe($page->body).'</content>'."\r";

					foreach (array("show_in_list", "list_order", "clean", "url") as $attr)
						$pages_atom.= '		<chyrp:'.$attr.'>'.safe($page->$attr).'</chyrp:'.$attr.'>'."\r";


					$trigger->filter($pages_atom, "pages_export", $page);

					$pages_atom.= '	</entry>'."\r";
				}
				$pages_atom.= '</feed>'."\r";

				$exports["pages.atom"] = $pages_atom;
			}

			if (isset($_POST['groups'])) {
				if (!empty($_POST['filter_groups'])) {
					$search = "";
					$matches = array();

					$queries = explode(" ", $_POST['filter_groups']);
					foreach ($queries as $query)
						if (!preg_match("/([a-z0-9_]+):(.+)/", $query))
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
				} else
					list($where, $params) = array(null, array());

				$groups = Group::find(array("where" => $where, "params" => $params, "order" => "id ASC"));

				$groups_yaml = array("groups" => array(),
				                     "permissions" => array());

				foreach (SQL::current()->select("permissions")->fetchAll() as $permission)
					$groups_yaml["permissions"][$permission["id"]] = $permission["name"];

				foreach ($groups as $index => $group)
					$groups_yaml["groups"][$group->name] = $group->permissions;

				$exports["groups.yaml"] = Horde_Yaml::dump($groups_yaml);
			}

			if (isset($_POST['users'])) {
				if (!empty($_POST['filter_users'])) {
					$search = "";
					$matches = array();

					$queries = explode(" ", $_POST['filter_users']);
					foreach ($queries as $query)
						if (!preg_match("/([a-z0-9_]+):(.+)/", $query))
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
						$where[] = "(login LIKE :query OR full_name LIKE :query OR email LIKE :query OR website LIKE :query)";
						$params[":query"] = "%".$_GET['query']."%";
					}
				} else
					list($where, $params) = array(null, array());

				$users = User::find(array("where" => $where, "params" => $params, "order" => "id ASC"));

				$users_yaml = array();
				foreach ($users as $user) {
					$users_yaml[$user->login] = array();

					foreach ($user as $name => $attr)
						if ($name != "no_results" and $name != "group_id" and $name != "id" and $name != "login")
							$users_yaml[$user->login][$name] = $attr;
						elseif ($name == "group_id")
							$users_yaml[$user->login]["group"] = $user->group()->name;
				}

				$exports["users.yaml"] = Horde_Yaml::dump($users_yaml);
			}

			$trigger->filter($exports, "export");

			require INCLUDES_DIR."/lib/zip.php";

			$zip = new ZipFile();
			foreach ($exports as $filename => $content)
				$zip->addFile($content, $filename);

			$zip_contents = $zip->file();

			$filename = sanitize(camelize($config->name), false, true)."_Export_".date("Y-m-d");
			header("Content-type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"".$filename.".zip\"");
			header("Content-length: ".strlen($zip_contents)."\n\n");

			echo $zip_contents;

			exit;
		}

		/**
		 * Function: import
		 * Importing content from other systems.
		 */
		public function import() {
			if (!Visitor::current()->group()->can("add_post"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to import content."));
		}

		/**
		 * Function: import_chyrp
		 * Chyrp importing.
		 */
		public function import_chyrp() {
			if (empty($_POST))
				redirect("/admin/?action=import");

			if (!Visitor::current()->group()->can("add_post"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to import content."));

			if (isset($_FILES['posts_file']) and $_FILES['posts_file']['error'] == 0)
				if (!$posts = simplexml_load_file($_FILES['posts_file']['tmp_name']) or $posts->generator != "Chyrp")
					Flash::warning(__("Chyrp Posts export file is invalid."), "/admin/?action=import");

			if (isset($_FILES['pages_file']) and $_FILES['pages_file']['error'] == 0)
				if (!$pages = simplexml_load_file($_FILES['pages_file']['tmp_name']) or $pages->generator != "Chyrp")
					Flash::warning(__("Chyrp Pages export file is invalid."), "/admin/?action=import");

			if (ini_get("memory_limit") < 20)
				ini_set("memory_limit", "20M");

			$trigger = Trigger::current();
			$visitor = Visitor::current();
			$sql = SQL::current();

			function media_url_scan(&$value) {
				$config = Config::current();

				$regexp_url = preg_quote($_POST['media_url'], "/");
				if (preg_match_all("/{$regexp_url}([^\.\!,\?;\"\'<>\(\)\[\]\{\}\s\t ]+)\.([a-zA-Z0-9]+)/", $value, $media))
					foreach ($media[0] as $matched_url) {
						$filename = upload_from_url($matched_url);
						$value = str_replace($matched_url, $config->url.$config->uploads_path.$filename, $value);
					}
			}

			if (isset($_FILES['groups_file']) and $_FILES['groups_file']['error'] == 0) {
				$import = Horde_Yaml::loadFile($_FILES['groups_file']['tmp_name']);

				foreach ($import["groups"] as $name => $permissions)
					if (!$sql->count("groups", "name = :name", array(":name" => $name)))
						$trigger->call("import_chyrp_group", Group::add($name, (array) $permissions));

				foreach ($import["permissions"] as $id => $name)
					if (!$sql->count("permissions", "id = :id", array(":id" => $id)))
						$sql->insert("permissions",
						             array("id" => ":id", "name" => ":name"),
						             array(":id" => $id, ":name" => $name));
			}

			if (isset($_FILES['users_file']) and $_FILES['users_file']['error'] == 0) {
				$users = Horde_Yaml::loadFile($_FILES['users_file']['tmp_name']);

				foreach ($users as $login => $user) {
					$group_id = $sql->select("groups", "id", "name = :name", "id DESC",
					                         array(":name" => $user["group"]))->fetchColumn();

					$group = ($group_id) ? $group_id : $config->default_group ;

					if (!$sql->count("users", "login = :login", array(":login" => $login)))
						$user = User::add($login,
						                  $user["password"],
						                  $user["email"],
						                  $user["full_name"],
						                  $user["website"],
						                  $user["joined_at"],
						                  $group);

					$trigger->call("import_chyrp_user", $user);
				}
			}

			if (isset($_FILES['posts_file']) and $_FILES['posts_file']['error'] == 0)
				foreach ($posts->entry as $entry) {
					$chyrp = $entry->children("http://chyrp.net/export/1.0/");

					$login = $entry->author->children("http://chyrp.net/export/1.0/")->login;
					$user_id = $sql->select("users", "id", "login = :login", "id DESC",
					                        array(":login" => $login))->fetchColumn();

					$data = Post::xml2arr($entry->content);

					if (!empty($_POST['media_url']))
						array_walk_recursive($data, "media_url_scan");

					$post = Post::add($data,
					                  $chyrp->clean,
					                  Post::check_url($chyrp->url),
					                  $chyrp->feather,
					                  ($user_id ? $user_id : $visitor->id),
					                  (bool) (int) $chyrp->pinned,
					                  $chyrp->status,
					                  datetime($entry->published),
					                  ($entry->updated == $entry->published) ?
					                      "0000-00-00 00:00:00" :
					                      datetime($entry->updated),
					                  "",
					                  false);

					$trigger->call("import_chyrp_post", $entry, $post);
				}

			if (isset($_FILES['pages_file']) and $_FILES['pages_file']['error'] == 0)
				foreach ($pages->entry as $entry) {
					$chyrp = $entry->children("http://chyrp.net/export/1.0/");
					$attr  = $entry->attributes("http://chyrp.net/export/1.0/");

					$login = $entry->author->children("http://chyrp.net/export/1.0/")->login;
					$user_id = $sql->select("users", "id", "login = :login", "id DESC",
					                        array(":login" => $login))->fetchColumn();

					$page = Page::add($entry->title,
					                  $entry->content,
					                  $attr->parent_id,
					                  (bool) (int) $chyrp->show_in_list,
					                  $chyrp->list_order,
					                  $chyrp->clean,
					                  Page::check_url($chyrp->url),
					                  datetime($entry->published),
					                  ($entry->updated == $entry->published) ? "0000-00-00 00:00:00" : datetime($entry->updated),
					                  ($user_id ? $user_id : $visitor->id));

					$trigger->call("import_chyrp_page", $entry, $page);
				}

			Flash::notice(__("Chyrp content successfully imported!"), "/admin/?action=import");
		}

		/**
		 * Function: import_wordpress
		 * WordPress importing.
		 */
		public function import_wordpress() {
			if (empty($_POST))
				redirect("/admin/?action=import");

			if (!Visitor::current()->group()->can("add_post"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to import content."));

			$config = Config::current();

			if (!in_array("text", $config->enabled_feathers))
				error(__("Missing Feather"), __("Importing from WordPress requires the Text feather to be installed and enabled."));

			if (ini_get("memory_limit") < 20)
				ini_set("memory_limit", "20M");

			$trigger = Trigger::current();

			$stupid_xml = file_get_contents($_FILES['xml_file']['tmp_name']);
			$sane_xml = preg_replace(array("/<wp:comment_content>/", "/<\/wp:comment_content>/"),
			                         array("<wp:comment_content><![CDATA[", "]]></wp:comment_content>"),
			                         $stupid_xml);

			$sane_xml = str_replace(array("<![CDATA[<![CDATA[", "]]>]]>"),
			                        array("<![CDATA[", "]]>"),
			                        $sane_xml);

			$fix_amps_count = 1;
			while ($fix_amps_count)
				$sane_xml = preg_replace("/<wp:meta_value>(.+)&(?!amp;)(.+)<\/wp:meta_value>/m",
				                         "<wp:meta_value>\\1&amp;\\2</wp:meta_value>",
				                         $sane_xml, -1, $fix_amps_count);

			$xml = simplexml_load_string($sane_xml, "SimpleXMLElement", LIBXML_NOCDATA);

			if (!$xml or !strpos($xml->channel->generator, "wordpress.org"))
				Flash::warning(__("File does not seem to be a valid WordPress export file."),
				               "/admin/?action=import");

			foreach ($xml->channel->item as $item) {
				$wordpress = $item->children("http://wordpress.org/export/1.0/");
				$content   = $item->children("http://purl.org/rss/1.0/modules/content/");
				if ($wordpress->status == "attachment" or $item->title == "zz_placeholder")
					continue;

				$regexp_url = preg_quote($_POST['media_url'], "/");
				if (!empty($_POST['media_url']) and
				    preg_match_all("/{$regexp_url}([^\.\!,\?;\"\'<>\(\)\[\]\{\}\s\t ]+)\.([a-zA-Z0-9]+)/",
				                   $content->encoded,
				                   $media))
					foreach ($media[0] as $matched_url) {
						$filename = upload_from_url($matched_url);
						$content->encoded = str_replace($matched_url, $config->url.$config->uploads_path.$filename, $content->encoded);
					}

				$clean = (isset($wordpress->post_name)) ? $wordpress->post_name : sanitize($item->title) ;

				if (empty($wordpress->post_type) or $wordpress->post_type == "post") {
					$status_translate = array("publish" => "public",
					                          "draft"   => "draft",
					                          "private" => "private",
					                          "static"  => "public",
					                          "object"  => "public",
					                          "inherit" => "public",
					                          "future"  => "draft",
					                          "pending" => "draft");

					$data = array("title" => trim($item->title), "body" => trim($content->encoded));

					$post = Post::add($data,
					                  $clean,
					                  Post::check_url($clean),
					                  "text",
					                  null,
					                  false,
					                  $status_translate[(string) $wordpress->status],
					                  ($wordpress->post_date == "0000-00-00 00:00:00") ? datetime() : $wordpress->post_date,
					                  null,
					                  "",
					                  false);

					$trigger->call("import_wordpress_post", $item, $post);
				} elseif ($wordpress->post_type == "page") {
					$page = Page::add(trim($item->title),
					                  trim($content->encoded),
					                  0,
					                  true,
					                  0,
					                  $clean,
					                  Page::check_url($clean),
					                  ($wordpress->post_date == "0000-00-00 00:00:00") ? datetime() : $wordpress->post_date);

					$trigger->call("import_wordpress_page", $item, $page);
				}
			}

			Flash::notice(__("WordPress content successfully imported!"), "/admin/?action=import");
		}

		/**
		 * Function: import_tumblr
		 * Tumblr importing.
		 */
		public function import_tumblr() {
			if (empty($_POST))
				redirect("/admin/?action=import");

			if (!Visitor::current()->group()->can("add_post"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to import content."));

			$config = Config::current();
			if (!in_array("text", $config->enabled_feathers) or
			    !in_array("video", $config->enabled_feathers) or
			    !in_array("audio", $config->enabled_feathers) or
			    !in_array("chat", $config->enabled_feathers) or
			    !in_array("photo", $config->enabled_feathers) or
			    !in_array("quote", $config->enabled_feathers) or
			    !in_array("link", $config->enabled_feathers))
				error(__("Missing Feather"), __("Importing from Tumblr requires the Text, Video, Audio, Chat, Photo, Quote, and Link feathers to be installed and enabled."));

			if (ini_get("memory_limit") < 20)
				ini_set("memory_limit", "20M");

			if (!parse_url($_POST['tumblr_url'], PHP_URL_SCHEME))
				$_POST['tumblr_url'] = "http://".$_POST['tumblr_url'];

			set_time_limit(3600);
			$url = rtrim($_POST['tumblr_url'], "/")."/api/read?num=50";
			$api = preg_replace("/<(\/?)([^\-]+)\-([^ >]+)/", "<\\1\\2_\\3", get_remote($url));
			$api = preg_replace("/ ([a-z]+)\-([a-z]+)=/", " \\1_\\2=", $api);
			$xml = simplexml_load_string($api);

			if (!isset($xml->tumblelog))
				Flash::warning(__("The URL you specified does not seem to be a valid Tumblr site."),
				               "/admin/?action=import");

			$already_in = $posts = array();
			foreach ($xml->posts->post as $post) {
				$posts[] = $post;
				$already_in[] = $post->attributes()->id;
			}

			while ($xml->posts->attributes()->total > count($posts)) {
				set_time_limit(3600);
				$api = preg_replace("/<(\/?)([a-z]+)\-([a-z]+)/", "<\\1\\2_\\3", get_remote($url."&start=".count($posts)));
				$api = preg_replace("/ ([a-z]+)\-([a-z]+)=/", " \\1_\\2=", $api);
				$xml = simplexml_load_string($api, "SimpleXMLElement", LIBXML_NOCDATA);
				foreach ($xml->posts->post as $post)
					if (!in_array($post->attributes()->id, $already_in)) {
						$posts[] = $post;
						$already_in[] = $post->attributes()->id;
					}
			}

			function reverse($a, $b) {
				if (empty($a) or empty($b)) return 0;
				return (strtotime($a->attributes()->date) < strtotime($b->attributes()->date)) ? -1 : 1 ;
			}

			set_time_limit(3600);
			usort($posts, "reverse");

			foreach ($posts as $key => $post) {
				set_time_limit(3600);
				if ($post->attributes()->type == "audio")
					continue; # Can't import Audio posts since Tumblr has the files locked in to Amazon.

				$translate_types = array("regular" => "text", "conversation" => "chat");

				$clean = "";
				switch($post->attributes()->type) {
					case "regular":
						$title = fallback($post->regular_title);
						$values = array("title" => $title,
						                "body" => $post->regular_body);
						$clean = sanitize($title);
						break;
					case "video":
						$values = array("embed" => $post->video_player,
						                "caption" => fallback($post->video_caption));
						break;
					case "conversation":
						$title = fallback($post->conversation_title);

						$lines = array();
						foreach ($post->conversation_line as $line)
							$lines[] = $line->attributes()->label." ".$line;

						$values = array("title" => $title,
						                "dialogue" => implode("\n", $lines));
						$clean = sanitize($title);
						break;
					case "photo":
						$values = array("filename" => upload_from_url($post->photo_url[0]),
						                "caption" => fallback($post->photo_caption));
						break;
					case "quote":
						$values = array("quote" => $post->quote_text,
						                "source" => preg_replace("/^&mdash; /", "",
						                                         fallback($post->quote_source)));
						break;
					case "link":
						$name = fallback($post->link_text);
						$values = array("name" => $name,
						                "source" => $post->link_url,
						                "description" => fallback($post->link_description));
						$clean = sanitize($name);
						break;
				}

				$new_post = Post::add($values,
				                      $clean,
				                      Post::check_url($clean),
				                      fallback($translate_types[(string) $post->attributes()->type], (string) $post->attributes()->type),
				                      null,
				                      false,
				                      "public",
				                      datetime((int) $post->attributes()->unix_timestamp),
					                  null,
					                  "",
					                  false);

				Trigger::current()->call("import_tumble", $post, $new_post);
			}

			Flash::notice(__("Tumblr content successfully imported!"), "/admin/?action=import");
		}

		/**
		 * Function: import_textpattern
		 * TextPattern importing.
		 */
		public function import_textpattern() {
			if (empty($_POST))
				redirect("/admin/?action=import");

			if (!Visitor::current()->group()->can("add_post"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to import content."));

			$config = Config::current();
			$trigger = Trigger::current();

			$dbcon = $dbsel = false;
			if ($link = @mysql_connect($_POST['host'], $_POST['username'], $_POST['password'])) {
				$dbcon = true;
				$dbsel = @mysql_select_db($_POST['database'], $link);
			}

			if (!$dbcon or !$dbsel)
				Flash::warning(__("Could not connect to the specified TextPattern database."),
				               "/admin/?action=import");

			mysql_query("SET NAMES 'utf8'");

			$get_posts = mysql_query("SELECT * FROM {$_POST['prefix']}textpattern ORDER BY ID ASC", $link) or error(__("Database Error"), mysql_error());
			$posts = array();
			while ($post = mysql_fetch_array($get_posts))
				$posts[$post["ID"]] = $post;

			foreach ($posts as $post) {
				$regexp_url = preg_quote($_POST['media_url'], "/");
				if (!empty($_POST['media_url']) and
				    preg_match_all("/{$regexp_url}([^\.\!,\?;\"\'<>\(\)\[\]\{\}\s\t ]+)\.([a-zA-Z0-9]+)/",
				                   $post["Body"],
				                   $media))
					foreach ($media[0] as $matched_url) {
						$filename = upload_from_url($matched_url);
						$post["Body"] = str_replace($matched_url, $config->url.$config->uploads_path.$filename, $post["Body"]);
					}

				$status_translate = array(1 => "draft",
				                          2 => "private",
				                          3 => "draft",
				                          4 => "public",
				                          5 => "public");

				$clean = fallback($post["url_title"], sanitize($post["Title"]));

				$new_post = Post::add(array("title" => $post["Title"],
				                            "body" => $post["Body"]),
				                      $clean,
				                      Post::check_url($clean),
				                      "text",
				                      null,
				                      ($post["Status"] == "5"),
				                      $status_translate[$post["Status"]],
				                      $post["Posted"],
					                  null,
					                  "",
					                  false);

				$trigger->call("import_textpattern_post", $post, $new_post);
			}

			mysql_close($link);

			Flash::notice(__("TextPattern content successfully imported!"), "/admin/?action=import");
		}

		/**
		 * Function: import_movabletype
		 * MovableType importing.
		 */
		public function import_movabletype() {
			if (empty($_POST))
				redirect("/admin/?action=import");

			if (!Visitor::current()->group()->can("add_post"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to import content."));

			$config = Config::current();
			$trigger = Trigger::current();

			$dbcon = $dbsel = false;
			if ($link = @mysql_connect($_POST['host'], $_POST['username'], $_POST['password'])) {
				$dbcon = true;
				$dbsel = @mysql_select_db($_POST['database'], $link);
			}

			if (!$dbcon or !$dbsel)
				Flash::warning(__("Could not connect to the specified MovableType database."),
				               "/admin/?action=import");

			mysql_query("SET NAMES 'utf8'");

			$get_posts = mysql_query("SELECT * FROM mt_entry ORDER BY entry_id ASC", $link) or error(__("Database Error"), mysql_error());
			$posts = array();
			while ($post = mysql_fetch_array($get_posts))
				$posts[$post["entry_id"]] = $post;

			foreach ($posts as $post) {
				$body = $post["entry_text"];

				if (!empty($post["entry_text_more"]))
					$body.= "\n\n<!--more-->\n\n".$post["entry_text_more"];

				$regexp_url = preg_quote($_POST['media_url'], "/");
				if (!empty($_POST['media_url']) and
				    preg_match_all("/{$regexp_url}([^\.\!,\?;\"\'<>\(\)\[\]\{\}\s\t ]+)\.([a-zA-Z0-9]+)/",
				                   $body,
				                   $media))
					foreach ($media[0] as $matched_url) {
						$filename = upload_from_url($matched_url);
						$body = str_replace($matched_url, $config->url.$config->uploads_path.$filename, $body);
					}

				$status_translate = array(1 => "draft",
				                          2 => "public",
				                          3 => "draft",
				                          4 => "draft");

				$clean = fallback($post["entry_basename"], sanitize($post["entry_title"]));

				if ($post["entry_class"] == "entry") {
					$new_post = Post::add(array("title" => $post["entry_title"],
					                            "body" => $body),
					                      $clean,
					                      Post::check_url($clean),
					                      "text",
					                      null,
					                      false,
					                      $status_translate[$post["entry_status"]],
					                      $post["entry_authored_on"],
					                      $post["entry_modified_on"],
					                      "",
					                      false);
					$trigger->call("import_movabletype_post", $post, $new_post, $link);
				} elseif ($post["entry_class"] == "page") {
					$new_page = Page::add($post["entry_title"], $body, 0, true, 0, $clean, Page::check_url($clean));
					$trigger->call("import_movabletype_page", $post, $new_page, $link);
				}
			}

			mysql_close($link);

			Flash::notice(__("MovableType content successfully imported!"), "/admin/?action=import");
		}

		/**
		 * Function: modules
		 * Module enabling/disabling.
		 */
		public function modules() {
			if (!Visitor::current()->group()->can("toggle_extensions"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to enable/disable modules."));

			$config = Config::current();

			$this->context["enabled_modules"] = $this->context["disabled_modules"] = array();

			if (!$open = @opendir(MODULES_DIR))
				return Flash::warning(__("Could not read modules directory."));

			$classes = array();

			while (($folder = readdir($open)) !== false) {
				if (!file_exists(MODULES_DIR."/".$folder."/".$folder.".php") or !file_exists(MODULES_DIR."/".$folder."/info.yaml")) continue;

				if (file_exists(MODULES_DIR."/".$folder."/locale/".$config->locale.".mo"))
					load_translator($folder, MODULES_DIR."/".$folder."/locale/".$config->locale.".mo");

				if (!isset($classes[$folder]))
					$classes[$folder] = array($folder);
				else
					array_unshift($classes[$folder], $folder);

				$info = Horde_Yaml::loadFile(MODULES_DIR."/".$folder."/info.yaml");

				$info["conflicts_true"] = array();
				$info["depends_true"] = array();

				if (!empty($info["conflicts"])) {
					$classes[$folder][] = "conflict";

					foreach ((array) $info["conflicts"] as $conflict)
						if (file_exists(MODULES_DIR."/".$conflict."/".$conflict.".php"))
							$classes[$folder][] = "conflict_".$conflict;
				}

				$dependencies_needed = array();
				if (!empty($info["depends"])) {
					$classes[$folder][] = "depends";

					foreach ((array) $info["depends"] as $dependency) {
						if (!module_enabled($dependency)) {
							if (!in_array("missing_dependency", $classes[$folder]))
								$classes[$folder][] = "missing_dependency";

							$classes[$folder][] = "needs_".$dependency;

							$dependencies_needed[] = $dependency;
						}

						$classes[$folder][] = "depends_".$dependency;

						fallback($classes[$dependency], array());
						$classes[$dependency][] = "depended_by_".$folder;
					}
				}

				fallback($info["name"], $folder);
				fallback($info["version"], "0");
				fallback($info["url"]);
				fallback($info["description"]);
				fallback($info["author"], array("name" => "", "url" => ""));
				fallback($info["help"]);

				$info["description"] = __($info["description"], $folder);
				$info["description"] = preg_replace(array("/<code>(.+)<\/code>/se", "/<pre>(.+)<\/pre>/se"),
				                                    array("'<code>'.fix('\\1').'</code>'", "'<pre>'.fix('\\1').'</pre>'"),
				                                    $info["description"]);

				$info["author"]["link"] = (!empty($info["author"]["url"])) ?
				                          '<a href="'.fix($info["author"]["url"]).'">'.fix($info["author"]["name"]).'</a>' :
				                          $info["author"]["name"] ;

				$category = (module_enabled($folder)) ? "enabled_modules" : "disabled_modules" ;
				$this->context[$category][$folder] = array("name" => $info["name"],
				                                           "version" => $info["version"],
				                                           "url" => $info["url"],
				                                           "description" => $info["description"],
				                                           "author" => $info["author"],
				                                           "help" => $info["help"],
				                                           "classes" => $classes[$folder],
				                                           "dependencies_needed" => $dependencies_needed);
			}

			foreach ($this->context["enabled_modules"] as $module => &$attrs)
				$attrs["classes"] = $classes[$module];

			foreach ($this->context["disabled_modules"] as $module => &$attrs)
				$attrs["classes"] = $classes[$module];
		}

		/**
		 * Function: feathers
		 * Feather enabling/disabling.
		 */
		public function feathers() {
			if (!Visitor::current()->group()->can("toggle_extensions"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to enable/disable feathers."));

			$config = Config::current();

			$this->context["enabled_feathers"] = $this->context["disabled_feathers"] = array();

			if (!$open = @opendir(FEATHERS_DIR))
				return Flash::warning(__("Could not read feathers directory."));

			while (($folder = readdir($open)) !== false) {
				if (!file_exists(FEATHERS_DIR."/".$folder."/".$folder.".php") or !file_exists(FEATHERS_DIR."/".$folder."/info.yaml")) continue;

				if (file_exists(FEATHERS_DIR."/".$folder."/locale/".$config->locale.".mo"))
					load_translator($folder, FEATHERS_DIR."/".$folder."/locale/".$config->locale.".mo");

				$info = Horde_Yaml::loadFile(FEATHERS_DIR."/".$folder."/info.yaml");

				fallback($info["name"], $folder);
				fallback($info["version"], "0");
				fallback($info["url"]);
				fallback($info["description"]);
				fallback($info["author"], array("name" => "", "url" => ""));
				fallback($info["help"]);

				$info["description"] = __($info["description"], $folder);
				$info["description"] = preg_replace("/<code>(.+)<\/code>/se", "'<code>'.fix('\\1').'</code>'", $info["description"]);
				$info["description"] = preg_replace("/<pre>(.+)<\/pre>/se", "'<pre>'.fix('\\1').'</pre>'", $info["description"]);

				$info["author"]["link"] = (!empty($info["author"]["url"])) ?
				                              '<a href="'.fix($info["author"]["url"]).'">'.fix($info["author"]["name"]).'</a>' :
				                              $info["author"]["name"] ;

				$category = (feather_enabled($folder)) ? "enabled_feathers" : "disabled_feathers" ;
				$this->context[$category][$folder] = array("name" => $info["name"],
				                                           "version" => $info["version"],
				                                           "url" => $info["url"],
				                                           "description" => $info["description"],
				                                           "author" => $info["author"],
				                                           "help" => $info["help"]);
			}
		}

		/**
		 * Function: themes
		 * Theme switching/previewing.
		 */
		public function themes() {
			$config = Config::current();

			$this->context["themes"] = array();

			if (!$open = @opendir(THEMES_DIR))
				return Flash::warning(__("Could not read themes directory."));

		     while (($folder = readdir($open)) !== false) {
				if (!file_exists(THEMES_DIR."/".$folder."/info.yaml"))
					continue;

				if (file_exists(THEMES_DIR."/".$folder."/locale/".$config->locale.".mo"))
					load_translator($folder, THEMES_DIR."/".$folder."/locale/".$config->locale.".mo");

				$info = Horde_Yaml::loadFile(THEMES_DIR."/".$folder."/info.yaml");

				fallback($info["name"], $folder);
				fallback($info["version"], "0");
				fallback($info["url"]);
				fallback($info["description"]);
				fallback($info["author"], array("name" => "", "url" => ""));

				$info["author"]["link"] = (!empty($info["author"]["url"])) ?
				                              '<a href="'.$info["author"]["url"].'">'.$info["author"]["name"].'</a>' :
				                              $info["author"]["name"] ;
				$info["description"] = preg_replace("/<code>(.+)<\/code>/se",
				                                    "'<code>'.fix('\\1').'</code>'",
				                                    $info["description"]);

				$info["description"] = preg_replace("/<pre>(.+)<\/pre>/se",
				                                    "'<pre>'.fix('\\1').'</pre>'",
				                                    $info["description"]);

				$this->context["themes"][] = array("name" => $folder,
				                                   "screenshot" => (file_exists(THEMES_DIR."/".$folder."/screenshot.png") ?
				                                                       $config->chyrp_url."/themes/".$folder."/screenshot.png" :
				                                                       $config->chyrp_url."/admin/images/noscreenshot.png"),
				                                   "info" => $info);
			}
			closedir($open);
		}

		/**
		 * Function: enable
		 * Enables a module or feather.
		 */
		public function enable() {
			$config  = Config::current();
			$visitor = Visitor::current();

			$type = (isset($_GET['module'])) ? "module" : "feather" ;

			if (!$visitor->group()->can("toggle_extensions"))
				if ($type == "module")
					show_403(__("Access Denied"), __("You do not have sufficient privileges to enable/disable modules."));
				else
					show_403(__("Access Denied"), __("You do not have sufficient privileges to enable/disable feathers."));

			if ($type == "module" and module_enabled($_GET[$type]))
				Flash::warning(__("Module already enabled."), "/admin/?action=modules");

			if ($type == "feather" and feather_enabled($_GET[$type]))
				Flash::warning(__("Feather already enabled."), "/admin/?action=feathers");

			$enabled_array = ($type == "module") ? "enabled_modules" : "enabled_feathers" ;
			$folder        = ($type == "module") ? MODULES_DIR : FEATHERS_DIR ;

			require $folder."/".$_GET[$type]."/".$_GET[$type].".php";

			$class_name = camelize($_GET[$type]);
			if (method_exists($class_name, "__install"))
				call_user_func(array($class_name, "__install"));

			$new = $config->$enabled_array;
			array_push($new, $_GET[$type]);
			$config->set($enabled_array, $new);

			if (file_exists($folder."/".$_GET[$type]."/locale/".$config->locale.".mo"))
				load_translator($_GET[$type], $folder."/".$_GET[$type]."/locale/".$config->locale.".mo");

			$info = Horde_Yaml::loadFile($folder."/".$_GET[$type]."/info.yaml");
			fallback($info["uploader"], false);
			fallback($info["notifications"], array());

			foreach ($info["notifications"] as &$notification)
				$notification = __($notification, $_GET[$type]);

			if ($info["uploader"])
				if (!file_exists(MAIN_DIR.$config->uploads_path))
					$info["notifications"][] = _f("Please create the <code>%s</code> directory at your Chyrp install's root and CHMOD it to 777.", array($config->uploads_path));
				elseif (!is_writable(MAIN_DIR.$config->uploads_path))
					$info["notifications"][] = _f("Please CHMOD <code>%s</code> to 777.", array($config->uploads_path));

			foreach ($info["notifications"] as $message)
				Flash::message($message);

			if ($type == "module")
				Flash::notice(_f("&#8220;%s&#8221; module enabled.",
				                 array($info["name"])),
				              "/admin/?action=".pluralize($type));
			elseif ($type == "feather")
				Flash::notice(_f("&#8220;%s&#8221; feather enabled.",
				                 array($info["name"])),
				              "/admin/?action=".pluralize($type));
		}

		/**
		 * Function: disable
		 * Disables a module or feather.
		 */
		public function disable() {
			$config  = Config::current();
			$visitor = Visitor::current();

			$type = (isset($_GET['module'])) ? "module" : "feather" ;

			if (!$visitor->group()->can("toggle_extensions"))
				if ($type == "module")
					show_403(__("Access Denied"), __("You do not have sufficient privileges to enable/disable modules."));
				else
					show_403(__("Access Denied"), __("You do not have sufficient privileges to enable/disable feathers."));

			if ($type == "module" and !module_enabled($_GET[$type]))
				Flash::warning(__("Module already disabled."), "/admin/?action=modules");

			if ($type == "feather" and !feather_enabled($_GET[$type]))
				Flash::warning(__("Feather already disabled."), "/admin/?action=feathers");

			$enabled_array = ($type == "module") ? "enabled_modules" : "enabled_feathers" ;
			$folder        = ($type == "module") ? MODULES_DIR : FEATHERS_DIR ;

			$class_name = camelize($_GET[$type]);
			if (method_exists($class_name, "__uninstall"))
				call_user_func(array($class_name, "__uninstall"), false);

			$config->set(($type == "module" ? "enabled_modules" : "enabled_feathers"),
			             array_diff($config->$enabled_array, array($_GET[$type])));

			$info = Horde_Yaml::loadFile($folder."/".$_GET[$type]."/info.yaml");
			if ($type == "module")
				Flash::notice(_f("&#8220;%s&#8221; module disabled.",
				                 array($info["name"])),
				              "/admin/?action=".pluralize($type));
			elseif ($type == "feather")
				Flash::notice(_f("&#8220;%s&#8221; feather disabled.",
				                 array($info["name"])),
				              "/admin/?action=".pluralize($type));
		}

		/**
		 * Function: change_theme
		 * Changes the theme.
		 */
		public function change_theme() {
			if (!Visitor::current()->group()->can("change_settings"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));
			if (empty($_GET['theme']))
				error(__("No Theme Specified"), __("You did not specify a theme to switch to."));

			$config = Config::current();

			$config->set("theme", $_GET['theme']);

			if (file_exists(THEMES_DIR."/".$_GET['theme']."/locale/".$config->locale.".mo"))
				load_translator($_GET['theme'], THEMES_DIR."/".$_GET['theme']."/locale/".$config->locale.".mo");

			$info = Horde_Yaml::loadFile(THEMES_DIR."/".$_GET['theme']."/info.yaml");
			fallback($info["notifications"], array());

			foreach ($info["notifications"] as &$notification)
				$notification = __($notification, $_GET['theme']);

			foreach ($info["notifications"] as $message)
				Flash::message($message);

			Flash::notice(_f("Theme changed to &#8220;%s&#8221;.", array($info["name"])), "/admin/?action=themes");
		}

		/**
		 * Function: general_settings
		 * General Settings page.
		 */
		public function general_settings() {
			if (!Visitor::current()->group()->can("change_settings"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

			$this->context["locales"] = array();

			if ($open = opendir(INCLUDES_DIR."/locale/")) {
			     while (($folder = readdir($open)) !== false) {
					$split = explode(".", $folder);
					if (end($split) == "mo")
						$this->context["locales"][] = array("code" => $split[0], "name" => lang_code($split[0]));
				}
				closedir($open);
			}

			$this->context["timezones"] = timezones(true);

			if (empty($_POST))
				return;

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$config = Config::current();
			$set = array($config->set("name", $_POST['name']),
			             $config->set("description", $_POST['description']),
			             $config->set("chyrp_url", rtrim($_POST['chyrp_url'], '/')),
			             $config->set("url", rtrim(fallback($_POST['url'], $_POST['chyrp_url'], true), '/')),
			             $config->set("email", $_POST['email']),
			             $config->set("timezone", $_POST['timezone']),
			             $config->set("locale", $_POST['locale']));

			if (!in_array(false, $set))
				Flash::notice(__("Settings updated."), "/admin/?action=general_settings");
		}

		/**
		 * Function: user_settings
		 * User Settings page.
		 */
		public function user_settings() {
			if (!Visitor::current()->group()->can("change_settings"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

			$this->context["groups"] = Group::find(array("order" => "id DESC"));

			if (empty($_POST))
				return;

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$config = Config::current();
			$set = array($config->set("can_register", !empty($_POST['can_register'])),
			             $config->set("default_group", $_POST['default_group']),
			             $config->set("guest_group", $_POST['guest_group']));

			if (!in_array(false, $set))
				Flash::notice(__("Settings updated."), "/admin/?action=user_settings");
		}

		/**
		 * Function: content_settings
		 * Content Settings page.
		 */
		public function content_settings() {
			if (!Visitor::current()->group()->can("change_settings"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

			if (empty($_POST))
				return;

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$config = Config::current();
			$set = array($config->set("posts_per_page", $_POST['posts_per_page']),
			             $config->set("feed_items", $_POST['feed_items']),
			             $config->set("feed_url", $_POST['feed_url']),
			             $config->set("uploads_path", $_POST['uploads_path']),
			             $config->set("enable_trackbacking", !empty($_POST['enable_trackbacking'])),
			             $config->set("send_pingbacks", !empty($_POST['send_pingbacks'])),
			             $config->set("enable_xmlrpc", !empty($_POST['enable_xmlrpc'])));

			if (!in_array(false, $set))
				Flash::notice(__("Settings updated."), "/admin/?action=content_settings");
		}

		/**
		 * Function: route_settings
		 * Route Settings page.
		 */
		public function route_settings() {
			if (!Visitor::current()->group()->can("change_settings"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

			if (empty($_POST))
				return;

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$config = Config::current();
			$set = array($config->set("clean_urls", !empty($_POST['clean_urls'])),
			             $config->set("post_url", $_POST['post_url']));

			if (!in_array(false, $set))
				Flash::notice(__("Settings updated."), "/admin/?action=route_settings");
		}

		/**
		 * Function: determine_action
		 * Determines through simple logic which page should be shown as the default when browsing to /admin/.
		 */
		public function determine_action($action = null) {
			$visitor = Visitor::current();

			if (!isset($action) or $action == "write") {
				# "Write > Post", if they can add posts or drafts.
				if (($visitor->group()->can("add_post") or $visitor->group()->can("add_draft")) and !empty(Config::current()->enabled_feathers))
					return "write_post";

				# "Write > Page", if they can add pages.
				if ($visitor->group()->can("add_page"))
					return "write_page";
			}

			if (!isset($action) or $action == "manage") {
				# "Manage > Posts", if they can manage any posts.
				if (Post::any_editable() or Post::any_deletable())
					return "manage_posts";

				# "Manage > Pages", if they can manage pages.
				if ($visitor->group()->can("edit_page") or $visitor->group()->can("delete_page"))
					return "manage_pages";

				# "Manage > Users", if they can manage users.
				if ($visitor->group()->can("edit_user") or $visitor->group()->can("delete_user"))
					return "manage_users";

				# "Manage > Groups", if they can manage groups.
				if ($visitor->group()->can("edit_group") or $visitor->group()->can("delete_group"))
					return "manage_groups";
			}

			if (!isset($action) or $action == "settings") {
				# "General Settings", if they can configure the installation.
				if ($visitor->group()->can("change_settings"))
					return "general_settings";
			}

			if (!isset($action) or $action == "extend") {
				# "Modules", if they can configure the installation.
				if ($visitor->group()->can("toggle_extensions"))
					return "modules";
			}

			$extended = $action;
			Trigger::current()->filter($extended, "determine_action");
			if ($extended != $action)
				return $extended;

			if (!isset($action))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to access this area."));
		}

		public function handle_redirects($action) {
			$redirectable = array("write", "manage", "settings", "extend");
			Trigger::current()->filter($redirectable, "admin_redirectables");
			if (!in_array($action, $redirectable)) return;

			$redirect = $this->determine_action($action);
			if (!empty($redirect))
				redirect("/admin/?action=".$redirect);
		}

		/**
		 * Function: help
		 * Sets the $title and $body for various help IDs.
		 */
		public function help($id = null) {
			if (!isset($id))
				redirect("/admin/");

			global $title, $body;

			switch($id) {
				case "filtering_results":
					$title = __("Filtering Results");
					$body = "<p>".__("Use this to search for specific items. You can either enter plain text to match the item with, or use keywords:")."</pre>";
					$body.= "<h2>".__("Keywords")."</h2>";
					$body.= "<cite><strong>".__("Usage")."</strong>: <code>attr:val</code></cite>\n".__("Use this syntax to quickly match specific results. Keywords will modify the query to match items where <code>attr</code> is equal to <code>val</code> (case insensitive).");
					break;
				case "slugs":
					$title = __("Post Slugs");
					$body = __("Post slugs are strings to use for the URL of a post. They are directly respondible for the <code>(url)</code> attribute in a post's clean URL, or the <code>/?action=view&amp;url=<strong>foo</strong></code> in a post's dirty URL. A post slug should not contain any special characters other than hyphens.");
					break;
				case "trackbacks":
					$title = __("Trackbacks");
					$body = __("Trackbacks are special urls to posts from other blogs that your post is related to or references. The other blog will be notified of your post, and in some cases a comment will automatically be added to the post in question linking back to your post. It's basically a way to network between blogs via posts.");
					break;
				case "alternate_urls":
					$title = __("Alternate URL");
					$body = "<p>".__("An alternate URL will allow you to keep Chyrp in its own directory, while having your site URLs point to someplace else. For example, you could have Chyrp in a <code>/chyrp</code> directory, and have your site at <code>/</code>. There are two requirements for this to work.")."</p>\n\n";
					$body.= "<ol>\n\t<li>".__("Create an <code>index.php</code> file in your destination directory with the following in it:")."\n\n";
					$body.= "<pre><code>&lt;?php
    require \"path/to/chyrp/index.php\";
?&gt;</code></pre>";
					$body.= "</li>\n\t<li>".__("Move the .htaccess file from the original Chyrp directory, and change the <code>RewriteBase</code> line to reflect the new website location.")."</li>\n</ol>";
			}
		}

		/**
		 * Function: current
		 * Returns a singleton reference to the current class.
		 */
		public static function & current() {
			static $instance = null;
			return $instance = (empty($instance)) ? new self() : $instance ;
		}
	}
	$admin = AdminController::current();
