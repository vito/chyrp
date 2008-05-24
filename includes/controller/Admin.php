<?php
	/**
	 * Class: Admin Controller
	 * The logic behind the Admin area.
	 */
	class AdminController {
		/**
		 * Variable: $context
		 * Contains the context for various admin pages, to be passed to the Twig templates.
		 */
		public $context = array();

		/**
		 * Function: write
		 * Post writing.
		 */
		public function write_post() {
			$visitor = Visitor::current();
			if (!$visitor->group()->can("add_post") and !$visitor->group()->can("add_draft"))
				error(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

			global $feathers;
			$this->context["feathers"]       = $feathers;
			$this->context["feather"]        = $feathers[fallback($_GET['feather'], Config::current()->enabled_feathers[0], true)];
			$this->context["GET"]["feather"] = fallback($_GET['feather'], Config::current()->enabled_feathers[0], true);
		}

		/**
		 * Function: add_post
		 * Adds a post when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function add_post() {
			$visitor = Visitor::current();
			if (!$visitor->group()->can("add_post") and !$visitor->group()->can("add_draft"))
				error(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));

			global $feathers;
			$feathers[$_POST['feather']]->submit();
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
				error(__("Access Denied"), __("You do not have sufficient privileges to edit this post."));

			global $feathers;
			$this->context["feather"] = $feathers[$this->context["post"]->feather];
		}

		/**
		 * Function: update_post
		 * Updates a post when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function update_post() {
			$post = new Post($_POST['id']);
			if (!$post->editable())
				error(__("Access Denied"), __("You do not have sufficient privileges to edit this post."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));

			global $feathers;
			$feathers[$post->feather]->update();

			if (!isset($_POST['ajax']))
				redirect("/admin/?action=manage_posts&updated=".$_POST['id']);
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
				error(__("Access Denied"), __("You do not have sufficient privileges to delete this post."));
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
				error(__("Access Denied"), __("Invalid security key."));

			$post = new Post($_POST['id']);
			if (!$post->deletable())
				error(__("Access Denied"), __("You do not have sufficient privileges to delete this post."));

			Post::delete($_POST['id']);

			redirect("/admin/?action=manage_posts&deleted=".$_POST['id']);
		}

		/**
		 * Function: manage_posts
		 * Post managing.
		 */
		public function manage_posts() {
			if (!Post::any_editable() and !Post::any_deletable())
				error(__("Access Denied"), __("You do not have sufficient privileges to manage any posts."));

			$params = array();
			$where = array();

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
					if ($test == "author") {
						$user = new User(null, array("where" => "`login` = :login", "params" => array(":login" => $equals)));
						$test = "user_id";
						$equals = $user->id;
					}
					$where[] = "`__posts`.`".$test."` = :".$test;
					$params[":".$test] = $equals;
				}

				$where[] = "`__posts`.`xml` LIKE :query";
				$params[":query"] = "%".$search."%";
			}

			if (!empty($_GET['month'])) {
				$where[] = "`__posts`.`created_at` LIKE :when";
				$params[":when"] = $_GET['month']."-%";
			}

			$this->context["posts"] = Post::find(array("where" => $where, "params" => $params, "per_page" => 25));

			if (!empty($_GET['updated']))
				$this->context["updated"] = new Post($_GET['updated']);

			$this->context["deleted"] = isset($_GET['deleted']);
		}

		/**
		 * Function: write_page
		 * Page creation.
		 */
		public function write_page() {
			if (!Visitor::current()->group()->can("add_page"))
				error(__("Access Denied"), __("You do not have sufficient privileges to create pages."));

			$this->context["pages"] = Page::find();
		}

		/**
		 * Function: add_page
		 * Adds a page when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function add_page() {
			if (!Visitor::current()->group()->can("add_page"))
				error(__("Access Denied"), __("You do not have sufficient privileges to create pages."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));

			$show_in_list = !empty($_POST['show_in_list']);
			$clean = (!empty($_POST['slug'])) ? $_POST['slug'] : sanitize($_POST['title']) ;
			$url = Page::check_url($clean);

			$page = Page::add($_POST['title'], $_POST['body'], $_POST['parent_id'], $show_in_list, $clean, $url);

			redirect($page->url());
		}

		/**
		 * Function: edit_page
		 * Page editing.
		 */
		public function edit_page() {
			if (!Visitor::current()->group()->can("edit_page"))
				error(__("Access Denied"), __("You do not have sufficient privileges to edit this page."));

			if (empty($_GET['id']))
				error(__("No ID Specified"), __("An ID is required to edit a page."));

			$this->context["page"] = new Page($_GET['id']);
			$this->context["pages"] = Page::find(array("where" => "`id` != :id", "params" => array(":id" => $_GET['id'])));
		}

		/**
		 * Function: update_page
		 * Updates a page when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function update_page() {
			if (!Visitor::current()->group()->can("edit_page"))
				error(__("Access Denied"), __("You do not have sufficient privileges to edit pages."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));

			$page = new Page($_POST['id']);
			$page->update($_POST['title'], $_POST['body'], $_POST['parent_id'], !empty($_POST['show_in_list']), $page->list_order, $_POST['slug']);

			if (!isset($_POST['ajax']))
				redirect("/admin/?action=manage_pages&updated=".$_POST['id']);
		}

		/**
		 * Function: delete_page
		 * Page deletion (confirm page).
		 */
		public function delete_page() {
			if (!Visitor::current()->group()->can("delete_page"))
				error(__("Access Denied"), __("You do not have sufficient privileges to delete pages."));

			$this->context["page"] = new Page($_GET['id']);
		}

		/**
		 * Function: destroy_page
		 * Destroys a page.
		 */
		public function destroy_page() {
			if (!Visitor::current()->group()->can("delete_page"))
				error(__("Access Denied"), __("You do not have sufficient privileges to delete pages."));

			if (empty($_POST['id']))
				error(__("No ID Specified"), __("An ID is required to delete a post."));

			if ($_POST['destroy'] == "bollocks")
				redirect("/admin/?action=manage_pages");

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));

			Page::delete($_POST['id']);

			redirect("/admin/?action=manage_pages&deleted=".$_POST['id']);
		}

		/**
		 * Function: manage_pages
		 * Page managing.
		 */
		public function manage_pages() {
			$visitor = Visitor::current();
			if (!$visitor->group()->can("edit_page") and !$visitor->group()->can("delete_page"))
				error(__("Access Denied"), __("You do not have sufficient privileges to manage pages."));

			$params = array();
			$where = array();

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
					if ($test == "author") {
						$user = new User(null, array("where" => "`login` = :login", "params" => array(":login" => $equals)));
						$test = "user_id";
						$equals = $user->id;
					}
					$where[] = "`__pages`.`".$test."` = :".$test;
					$params[":".$test] = $equals;
				}

				$where[] = "`__pages`.`title` LIKE :query OR `__pages`.`body` LIKE :query";
				$params[":query"] = "%".$search."%";
			}

			$this->context["pages"] = Page::find(array("where" => $where, "params" => $params, "per_page" => 25));

			if (!empty($_GET['updated']))
				$this->context["updated"] = new Page($_GET['updated']);

			$this->context["deleted"] = isset($_GET['deleted']);
		}

		/**
		 * Function: new_user
		 * User creation.
		 */
		public function new_user() {
			if (!Visitor::current()->group()->can("add_user"))
				error(__("Access Denied"), __("You do not have sufficient privileges to add users."));

			$config = Config::current();

			$this->context["default_group"] = new Group($config->default_group);
			$this->context["groups"] = Group::find(array("pagination" => false,
			                                             "where" => array("`id` != :guest_id", "`id` != :default_id"),
			                                             "params" => array(":guest_id" => $config->guest_group, "default_id" => $config->default_group),
			                                             "order" => "`id` desc"));
		}

		/**
		 * Function: add_user
		 * Add a user when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function add_user() {
			if (!Visitor::current()->group()->can("add_user"))
				error(__("Access Denied"), __("You do not have sufficient privileges to add users."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));

			if (empty($_POST['login']))
				error(__("Error"), __("Please enter a username for your account."));

			$check = new User(null, array("where" => "`login` = :login",
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

			User::add($_POST['login'], $_POST['password1'], $_POST['email'], $_POST['full_name'], $_POST['website'], $_POST['group']);

			redirect("/admin/?action=manage_users&added");
		}

		/**
		 * Function: edit_user
		 * User editing.
		 */
		public function edit_user() {
			if (!Visitor::current()->group()->can("edit_user"))
				error(__("Access Denied"), __("You do not have sufficient privileges to edit this user."));

			if (empty($_GET['id']))
				error(__("No ID Specified"), __("An ID is required to edit a user."));

			$this->context["user"] = new User($_GET['id']);
			$this->context["groups"] = Group::find(array("pagination" => false,
			                                             "order" => "`id` asc",
			                                             "where" => "`id` != :guest_id",
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
				error(__("Access Denied"), __("Invalid security key."));

			$visitor = Visitor::current();

			if (!$visitor->group()->can("edit_user"))
				error(__("Access Denied"), __("You do not have sufficient privileges to edit users."));

			$user = new User($_POST['id']);
			$password = (!empty($_POST['new_password1']) and $_POST['new_password1'] == $_POST['new_password2']) ?
			            md5($_POST['new_password1']) :
			            $user->password ;

			$user->update($_POST['login'], $password, $_POST['full_name'], $_POST['email'], $_POST['website'], $_POST['group']);

			if ($_POST['id'] == $visitor->id)
				cookie_cutter("chyrp_password", $password);

			redirect("/admin/?action=manage_users&updated");
		}

		/**
		 * Function: delete_user
		 * User deletion.
		 */
		public function delete_user() {
			if (!Visitor::current()->group()->can("delete_user"))
				error(__("Access Denied"), __("You do not have sufficient privileges to delete users."));

			$this->context["user"] = new User($_GET['id']);
		}

		/**
		 * Function: destroy_user
		 * Destroys a user.
		 */
		public function destroy_user() {
			if (!Visitor::current()->group()->can("delete_user"))
				error(__("Access Denied"), __("You do not have sufficient privileges to delete users."));

			if (empty($_POST['id']))
				error(__("No ID Specified"), __("An ID is required to delete a user."));

			if ($_POST['destroy'] == "bollocks")
				redirect("/admin/?action=manage_users");

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));

			User::delete($_POST['id']);

			redirect("/admin/?action=manage_users&deleted");
		}

		/**
		 * Function: manage_users
		 * User managing.
		 */
		public function manage_users() {
			$visitor = Visitor::current();
			if (!$visitor->group()->can("edit_user") and !$visitor->group()->can("delete_user") and !$visitor->group()->can("add_user"))
				error(__("Access Denied"), __("You do not have sufficient privileges to manage users."));

			$params = array();
			$where = array();

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
					$where[] = "`__pages`.`".$test."` = :".$test;
					$params[":".$test] = $equals;
				}

				$where[] = "`__users`.`login` LIKE :query OR `__users`.`full_name` LIKE :query OR `__users`.`email` LIKE :query OR `__users`.`website` LIKE :query";
				$params[":query"] = "%".$_GET['query']."%";
			}

			$this->context["users"] = User::find(array("where" => $where, "params" => $params, "per_page" => 25));

			$this->context["updated"] = isset($_GET['updated']);
			$this->context["deleted"] = isset($_GET['deleted']);
			$this->context["added"]   = isset($_GET['added']);
		}

		/**
		 * Function: new_group
		 * Group creation.
		 */
		public function new_group() {
			if (!Visitor::current()->group()->can("add_group"))
				error(__("Access Denied"), __("You do not have sufficient privileges to create groups."));

			$this->context["permissions"] = SQL::current()->query("select * from `__permissions`")->fetchAll();
		}

		/**
		 * Function: add_group
		 * Adds a group when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function add_group() {
			if (!Visitor::current()->group()->can("add_group"))
				error(__("Access Denied"), __("You do not have sufficient privileges to create groups."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));

			Group::add($_POST['name'], array_keys($_POST['permissions']));

			redirect("/admin/?action=manage_groups&added");
		}

		/**
		 * Function: edit_group
		 * Group editing.
		 */
		public function edit_group() {
			if (!Visitor::current()->group()->can("edit_group"))
				error(__("Access Denied"), __("You do not have sufficient privileges to edit groups."));

			$this->context["group"] = new Group($_GET['id']);
			$this->context["permissions"] = SQL::current()->query("select * from `__permissions`")->fetchAll();
		}

		/**
		 * Function: update_group
		 * Updates a group when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function update_group() {
			if (!Visitor::current()->group()->can("edit_group"))
				error(__("Access Denied"), __("You do not have sufficient privileges to edit groups."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));

			$permissions = array_keys($_POST['permissions']);

			$group = new Group($_POST['id']);

			if ($group->no_results)
				redirect("/admin/?action=manage_groups ");

			$group->update($_POST['name'], $permissions);
			redirect("/admin/?action=manage_groups&updated");
		}

		/**
		 * Function: delete_group
		 * Group deletion (confirm page).
		 */
		public function delete_group() {
			if (!Visitor::current()->group()->can("delete_group"))
				error(__("Access Denied"), __("You do not have sufficient privileges to delete groups."));

			$this->context["group"] = new Group($_GET['id']);
			$this->context["groups"] = Group::find(array("where" => "`id` != :group_id",
			                                             "order" => "`id` asc",
			                                             "pagination" => false,
			                                             "params" => array(":group_id" => $_GET['id'])));
		}

		/**
		 * Function: destroy_group
		 * Destroys a group.
		 */
		public function destroy_group() {
			if (!Visitor::current()->group()->can("delete_group"))
				error(__("Access Denied"), __("You do not have sufficient privileges to delete groups."));

			if (!isset($_POST['id']))
				error(__("No ID Specified"), __("An ID is required to delete a group."));

			if ($_POST['destroy'] == "bollocks")
				redirect("/admin/?action=manage_pages");

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));

			$group = new Group($_POST['id']);
			foreach ($group->members() as $user)
				$user->update($user->login, $user->password, $user->full_name, $user->email, $user->website, $_POST['move_group']);

			$config = Config::current();
			if (!empty($_POST['default_group']))
				$config->set("default_group", $_POST['default_group']);
			if (!empty($_POST['guest_group']))
				$config->set("guest_group", $_POST['guest_group']);

			Group::delete($_POST['id']);

			redirect("/admin/?action=manage_groups&deleted");
		}

		/**
		 * Function: manage_groups
		 * Group managing.
		 */
		public function manage_groups() {
			$visitor = Visitor::current();
			if (!$visitor->group()->can("edit_group") and !$visitor->group()->can("delete_group") and !$visitor->group()->can("add_group"))
				error(__("Access Denied"), __("You do not have sufficient privileges to manage groups."));

			if (!empty($_GET['search'])) {
				$user = new User(null, array("where" => "`login` = :search", "params" => array(":search" => $_GET['search'])));
				$this->context["groups"] = array($user->group());
			} else
				$this->context["groups"] = Group::find(array("per_page" => 25, "order" => "`id` asc"));

			$this->context["updated"] = isset($_GET['updated']);
			$this->context["deleted"] = isset($_GET['deleted']);
			$this->context["added"]   = isset($_GET['added']);
		}

		/**
		 * Function: extend_modules
		 * Module enabling/disabling.
		 */
		public function extend_modules() {
			if (!Visitor::current()->group()->can("toggle_extensions"))
				error(__("Access Denied"), __("You do not have sufficient privileges to enable/disable modules."));

			$config = Config::current();

			$this->context["enabled_modules"] = $this->context["disabled_modules"] = array();

			$issues = array();
			$dependencies = array();
			if ($open = opendir(MODULES_DIR)) {
				while (($folder = readdir($open)) !== false) {
					if (!file_exists(MODULES_DIR."/".$folder."/module.php") or !file_exists(MODULES_DIR."/".$folder."/info.yaml")) continue;

					if (file_exists(MODULES_DIR."/".$folder."/locale/".$config->locale.".mo"))
						load_translator($folder, MODULES_DIR."/".$folder."/locale/".$config->locale.".mo");

					$info = Spyc::YAMLLoad(MODULES_DIR."/".$folder."/info.yaml");

					$info["conflicts_true"] = array();

					if (!empty($info["conflicts"]))
						foreach ($info["conflicts"] as $conflict)
							if (file_exists(MODULES_DIR."/".$conflict."/module.php")) {
								$issues[$conflict] = $issues[$folder] = true;
								$info["conflicts_true"][] = $conflict;
							}

					if (!empty($info["depends"]))
						foreach ($info["depends"] as $dependency)
							if (!module_enabled($dependency))
								$dependencies[$folder] = true;

					$info["description"] = preg_replace("/<code>(.+)<\/code>/se", "'<code>'.htmlspecialchars('\\1').'</code>'", $info["description"]);
					$info["description"] = preg_replace("/<pre>(.+)<\/pre>/se", "'<pre>'.htmlspecialchars('\\1').'</pre>'", $info["description"]);

					$info["author"]["link"] = (!empty($info["author"]["url"])) ?
					                              '<a href="'.htmlspecialchars($info["author"]["url"]).'">'.htmlspecialchars($info["author"]["name"]).'</a>' :
					                              $info["author"]["name"] ;

					$category = (module_enabled($folder)) ? "enabled_modules" : "disabled_modules" ;
					$this->context[$category][$folder] = array("name" => $info["name"],
					                                           "url" => $info["url"],
					                                           "description" => $info["description"],
					                                           "author" => $info["author"],
					                                           "conflict" => isset($issues[$folder]),
					                                           "conflicts" => $info["conflicts_true"],
					                                           "depends" => isset($dependencies[$folder]),
					                                           "conflicts_class" => (isset($issues[$folder])) ? " conflict conflict_".join(" conflict_", $info["conflicts_true"]) : "",
					                                           "depends_class" => (isset($dependencies[$folder])) ? " depends" : "");
				}
			}

			if (isset($_GET['enabled'])) {
				if (file_exists(MODULES_DIR."/".$_GET['enabled']."/locale/".$config->locale.".mo"))
					load_translator($_GET['enabled'], MODULES_DIR."/".$_GET['enabled']."/locale/".$config->locale.".mo");

				$info = Spyc::YAMLLoad(MODULES_DIR."/".$_GET['enabled']."/info.yaml");
				fallback($info["uploader"], false);
				fallback($info["notifications"], array());

				foreach ($info["notifications"] as &$notification)
					$notification = addslashes(__($notification, $_GET['enabled']));

				if ($info["uploader"])
					if (!file_exists(MAIN_DIR."/upload"))
						$info["notifications"][] = __("Please create the <code>/upload</code> directory at your Chyrp install's root and CHMOD it to 777.");
					elseif (!is_writable(MAIN_DIR."/upload"))
						$info["notifications"][] = __("Please CHMOD <code>/upload</code> to 777.");
			}
		}

		/**
		 * Function: extend_feathers
		 * Feather enabling/disabling.
		 */
		public function extend_feathers() {
			if (!Visitor::current()->group()->can("toggle_extensions"))
				error(__("Access Denied"), __("You do not have sufficient privileges to enable/disable feathers."));

			$config = Config::current();

			$this->context["enabled_feathers"] = $this->context["disabled_feathers"] = array();

			if ($open = opendir(FEATHERS_DIR)) {
				while (($folder = readdir($open)) !== false) {
					if (!file_exists(FEATHERS_DIR."/".$folder."/feather.php") or !file_exists(FEATHERS_DIR."/".$folder."/info.yaml")) continue;

					if (file_exists(FEATHERS_DIR."/".$folder."/locale/".$config->locale.".mo"))
						load_translator($folder, FEATHERS_DIR."/".$folder."/locale/".$config->locale.".mo");

					$info = Spyc::YAMLLoad(FEATHERS_DIR."/".$folder."/info.yaml");

					$info["description"] = preg_replace("/<code>(.+)<\/code>/se", "'<code>'.htmlspecialchars('\\1').'</code>'", $info["description"]);
					$info["description"] = preg_replace("/<pre>(.+)<\/pre>/se", "'<pre>'.htmlspecialchars('\\1').'</pre>'", $info["description"]);

					$info["author"]["link"] = (!empty($info["author"]["url"])) ?
					                              '<a href="'.htmlspecialchars($info["author"]["url"]).'">'.htmlspecialchars($info["author"]["name"]).'</a>' :
					                              $info["author"]["name"] ;

					$category = (feather_enabled($folder)) ? "enabled_feathers" : "disabled_feathers" ;
					$this->context[$category][$folder] = array("name" => $info["name"],
					                                           "url" => $info["url"],
					                                           "description" => $info["description"],
					                                           "author" => $info["author"]);
				}
			}

			if (isset($_GET['enabled'])) {
				if (file_exists(FEATHERS_DIR."/".$_GET['enabled']."/locale/".$config->locale.".mo"))
					load_translator($_GET['enabled'], FEATHERS_DIR."/".$_GET['enabled']."/locale/".$config->locale.".mo");

				$info = Spyc::YAMLLoad(FEATHERS_DIR."/".$_GET['enabled']."/info.yaml");
				fallback($info["uploader"], false);
				fallback($info["notifications"], array());

				foreach ($info["notifications"] as &$notification)
					$notification = addslashes(__($notification, $_GET['enabled']));

				if ($info["uploader"])
					if (!file_exists(MAIN_DIR."/upload"))
						$info["notifications"][] = __("Please create the <code>/upload</code> directory at your Chyrp install's root and CHMOD it to 777.");
					elseif (!is_writable(MAIN_DIR."/upload"))
						$info["notifications"][] = __("Please CHMOD <code>/upload</code> to 777.");
			}
		}

		/**
		 * Function: extend_themes
		 * Theme switching/previewing.
		 */
		public function extend_themes() {
			$config = Config::current();

			$this->context["themes"] = array();
			$this->context["changed"] = isset($_GET['changed']);
			$this->context["current_theme"] = array("name" => $config->theme,
			                                        "screenshot" => (file_exists(THEMES_DIR."/".$config->theme."/screenshot.png") ?
			                                                            $config->url."/themes/".$config->theme."/screenshot.png" :
			                                                            $config->url."/admin/images/noscreenshot.png"),
					                                "info" => Spyc::YAMLLoad(THEMES_DIR."/".$config->theme."/info.yaml"));

			$current_info =& $this->context["current_theme"]["info"];
			$current_info["author"]["link"] = (!empty($this->context["current_theme"]["info"]["author"]["url"])) ?
			                                      '<a href="'.htmlspecialchars($current_info["author"]["url"]).'">'.htmlspecialchars($current_info["author"]["name"]).'</a>' :
			                                      $current_info["author"]["name"] ;
			$current_info["description"] = preg_replace("/<code>(.+)<\/code>/se", "'<code>'.htmlspecialchars('\\1').'</code>'", $current_info["description"]);
			$current_info["description"] = preg_replace("/<pre>(.+)<\/pre>/se", "'<pre>'.htmlspecialchars('\\1').'</pre>'", $current_info["description"]);

			if ($open = opendir(THEMES_DIR)) {
			     while (($folder = readdir($open)) !== false) {
					if (!file_exists(THEMES_DIR."/".$folder."/info.yaml"))
						continue;

					if (file_exists(THEMES_DIR."/".$folder."/locale/".$config->locale.".mo"))
						load_translator($folder, THEMES_DIR."/".$folder."/locale/".$config->locale.".mo");

					$info = Spyc::YAMLLoad(THEMES_DIR."/".$folder."/info.yaml");
					$info["author"]["link"] = (!empty($info["author"]["url"])) ?
					                              '<a href="'.$info["author"]["url"].'">'.$info["author"]["name"].'</a>' :
					                              $info["author"]["name"] ;
					$info["description"] = preg_replace("/<code>(.+)<\/code>/se", "'<code>'.htmlspecialchars('\\1').'</code>'", $info["description"]);
					$info["description"] = preg_replace("/<pre>(.+)<\/pre>/se", "'<pre>'.htmlspecialchars('\\1').'</pre>'", $info["description"]);

					$this->context["themes"][] = array("name" => $folder,
					                                   "screenshot" => (file_exists(THEMES_DIR."/".$folder."/screenshot.png") ?
					                                                       $config->url."/themes/".$folder."/screenshot.png" :
					                                                       $config->url."/admin/images/noscreenshot.png"),
					                                   "info" => $info);
				}
				closedir($open);
			}
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
					error(__("Access Denied"), __("You do not have sufficient privileges to enable/disable modules."));
				else
					error(__("Access Denied"), __("You do not have sufficient privileges to enable/disable feathers."));

			if (($type == "module" and module_enabled($_GET[$type])) or
			    ($type == "feather" and feather_enabled($_GET[$type])))
				redirect("/admin/?action=extend_modules");

			$enabled_array = ($type == "module") ? "enabled_modules" : "enabled_feathers" ;
			$folder        = ($type == "module") ? MODULES_DIR : FEATHERS_DIR ;

			require $folder."/".$_GET[$type]."/".$type.".php";

			$class_name = camelize($_GET[$type]);
			if (method_exists($class_name, "__install"))
				call_user_func(array($class_name, "__install"));

			$new = $config->$enabled_array;
			array_push($new, $_GET[$type]);
			$config->set($enabled_array, $new);

			redirect("/admin/?action=extend_".$type."s&enabled=".$_GET[$type]);
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
					error(__("Access Denied"), __("You do not have sufficient privileges to enable/disable modules."));
				else
					error(__("Access Denied"), __("You do not have sufficient privileges to enable/disable feathers."));

			if (($type == "module" and !module_enabled($_GET[$type])) or
			    ($type == "feather" and !feather_enabled($_GET[$type])))
				redirect("/admin/?action=extend_modules");

			$enabled_array = ($type == "module") ? "enabled_modules" : "enabled_feathers" ;
			$folder        = ($type == "module") ? MODULES_DIR : FEATHERS_DIR ;

			$class_name = camelize($_GET[$type]);
			if (method_exists($class_name, "__uninstall"))
				call_user_func(array($class_name, "__uninstall"), false);

			$config->set(($type == "module" ? "enabled_modules" : "enabled_feathers"),
			             array_diff($config->$enabled_array, array($_GET[$type])));

			redirect("/admin/?action=extend_".$type."s&enabled=".$_GET[$type]);
		}

		/**
		 * Function: settings
		 * Changes Chyrp settings. Shows an error if the user lacks permissions.
		 */
		public function settings() {
			if (!Visitor::current()->group()->can("change_settings"))
				error(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));

			$config = Config::current();
			switch($_POST['page']) {
				case "general":
					$config->set("name", $_POST['name']);
					$config->set("description", $_POST['description']);
					$config->set("url", $_POST['url']);
					$config->set("email", $_POST['email']);
					$config->set("time_offset", ($_POST['time_offset'] * 3600));
					$config->set("locale", $_POST['locale']);
					break;
				case "content":
					$config->set("feed_items", $_POST['feed_items']);
					$config->set("enable_trackbacking", !empty($_POST['enable_trackbacking']));
					$config->set("send_pingbacks", !empty($_POST['send_pingbacks']));
					$config->set("posts_per_page", $_POST['posts_per_page']);
					break;
				case "user":
					$config->set("can_register", !empty($_POST['can_register']));
					$config->set("default_group", $_POST['default_group']);
					$config->set("guest_group", $_POST['guest_group']);
					break;
				case "route":
					$config->set("clean_urls", !empty($_POST['clean_urls']));
					$config->set("post_url", $_POST['post_url']);
					break;
				default:
					$trigger = Trigger::current();
					$trigger->call("change_settings", $_POST['page']);
					break;
			}

			redirect("/admin/?action=".$_POST['page']."_settings&updated");
		}

		/**
		 * Function: general_settings
		 * General Settings page.
		 */
		public function general_settings() {
			$this->context["locales"] = array();

			if ($open = opendir(INCLUDES_DIR."/locale/")) {
			     while (($folder = readdir($open)) !== false) {
					$split = explode(".", $folder);
					if (end($split) == "mo")
						$this->context["locales"][] = array("code" => $split[0], "name" => lang_code($split[0]));
				}
				closedir($open);
			}
		}

		/**
		 * Function: user_settings
		 * User Settings page.
		 */
		public function user_settings() {
			$this->context["groups"] = Group::find(array("pagination" => false, "order" => "`id` desc"));
		}

		/**
		 * Function: change_theme
		 * Changes the theme. Shows an error if the user lacks permissions.
		 */
		public function change_theme() {
			if (!Visitor::current()->group()->can("change_settings"))
				error(__("Access Denied"), __("You do not have sufficient privileges to change settings."));
			if (empty($_GET['theme']))
				error(__("No Theme Specified"), __("You did not specify a theme to switch to."));

			Config::current()->set("theme", $_GET['theme']);

			redirect("/admin/?action=extend_themes&changed");
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
			redirect("/admin/?action=manage&sub=page&reordered");
		}

		/**
		 * Function: determine_action
		 * Determines through simple logic which page should be shown as the default when browsing to /admin/.
		 */
		public function determine_action() {
			$visitor = Visitor::current();

			# "Write > Post", if they can add posts or drafts.
			if ($visitor->group()->can("add_post") or $visitor->group()->can("add_draft"))
				return "write_post";

			# "Write > Page", if they can add pages.
			if ($visitor->group()->can("add_page"))
				return "write_page";

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

			# "Settings", if they can configure the installation.
			if ($visitor->group()->can("change_settings"))
				return "settings";
		}
	}
	$admin = new AdminController();
