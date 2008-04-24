<?php
	/**
	 * Class: Admin Controller
	 * The logic behind the Admin area.
	 */
	class AdminController {
		/**
		 * Function: add_post
		 * Adds a post when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function add_post() {
			global $feathers;
			if (empty($_POST)) return;

			$config = Config::current();
			$visitor = Visitor::current();

			if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));
			if (!$visitor->group->can("add_post"))
				error(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

			$feathers[$_POST['feather']]->submit();
		}

		/**
		 * Function: add_page
		 * Adds a page when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function add_page() {
			$visitor = Visitor::current();
			if (empty($_POST)) return;
			$config = Config::current();
			if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));
			if (!$visitor->group->can('add_page'))
				error(__("Access Denied"), __("You do not have sufficient privileges to create pages."));

			$show_in_list = !empty($_POST['show_in_list']);
			$clean = (!empty($_POST['slug'])) ? $_POST['slug'] : sanitize($_POST['title']) ;
			$url = Page::check_url($clean);

			$page = Page::add($_POST['title'], $_POST['body'], $_POST['parent_id'], $show_in_list, $clean, $url);

			$route = Route::current();
			$route->redirect($page->url());
		}

		/**
		 * Function: add_user
		 * Add a user when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function add_user() {
			$visitor = Visitor::current();
			if (empty($_POST)) return;
			$config = Config::current();
			if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));
			if (!$visitor->group->can("edit_user"))
				error(__("Access Denied"), __("You do not have sufficient privileges to edit users."));

			if (empty($_POST['login']))
				error(__("Error"), __("Please enter a username for your account."));

			$sql = SQL::current();
			$check_user = $sql->query("select count(`id`) from `".$sql->prefix."users`
			                           where `login` = :login",
			                          array(
			                              ':login' => $_POST['login']
			                          ));
			if ($check_user->fetchColumn())
				error(__("Error"), __("That username is already in use."));

			if (empty($_POST['password1']) or empty($_POST['password2']))
				error(__("Error"), __("Password cannot be blank."));
			if (empty($_POST['email']))
				error(__("Error"), __("E-mail address cannot be blank."));
			if ($_POST['password1'] != $_POST['password2'])
				error(__("Error"), __("Passwords do not match."));
			if (!eregi("^[[:alnum:]][a-z0-9_.-\+]*@[a-z0-9.-]+\.[a-z]{2,6}$",$_POST['email']))
				error(__("Error"), __("Unsupported e-mail address."));

			User::add($_POST['login'], $_POST['password1'], $_POST['email'], $_POST['full_name'], $_POST['website'], $_POST['group_id']);

			$route = Route::current();
			$route->redirect("/admin/?action=manage&sub=user&added");
		}

		/**
		 * Function: add_group
		 * Adds a group when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function add_group() {
			if (empty($_POST)) return;
			$config = Config::current();
			$visitor = Visitor::current();
			if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));
			if (!$visitor->group->can('add_group'))
				error(__("Access Denied"), __("You do not have sufficient privileges to create groups."));

			Group::add($_POST['name'], array_keys($_POST['permissions']));

			$route = Route::current();
			$route->redirect("/admin/?action=manage&sub=group&added");
		}

		/**
		 * Function: update_post
		 * Updates a post when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function update_post() {
			global $feathers;
			if (empty($_POST)) return;
			$config = Config::current();
			$visitor = Visitor::current();
			if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));
			if (!$visitor->group->can('edit_post'))
				error(__("Access Denied"), __("You do not have sufficient privileges to edit posts."));

			$feather = Post::info("feather", $_POST['id']);
			$feathers[$feather]->update();

			if (!isset($_POST['ajax'])) {
				$route = Route::current();
				$route->redirect("/admin/?action=manage&sub=post&updated=".$_POST['id']);
			}
			else
				exit((string) $_POST['id']);
		}

		/**
		 * Function: update_page
		 * Updates a page when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function update_page() {
			if (empty($_POST)) return;
			$config = Config::current();
			$visitor = Visitor::current();
			if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));
			if (!$visitor->group->can('edit_page'))
				error(__("Access Denied"), __("You do not have sufficient privileges to edit pages."));

			$show_in_list = !empty($_POST['show_in_list']);

			$page = new Page($_POST['id']);
			$page->update($_POST['title'], $_POST['body'], $_POST['parent_id'], $show_in_list, $_POST['slug']);

			if (!isset($_POST['ajax'])) {
				$route->redirect("/admin/?action=manage&sub=page&updated=".$_POST['id']);
				$route = Route::current();
			}
		}

		/**
		 * Function: update_user
		 * Updates a user when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function update_user() {
			if (empty($_POST)) return;

			$config = Config::current();
			$visitor = Visitor::current();

			if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));
			if (!$visitor->group->can("edit_user"))
				error(__("Access Denied"), __("You do not have sufficient privileges to edit users."));

			$user = new User($_POST['id']);
			$password = (!empty($_POST['new_password1']) and $_POST['new_password1'] == $_POST['new_password2']) ?
									md5($_POST['new_password1']) :
									$user->password ;

			$user->update($_POST['login'], $password, $_POST['full_name'], $_POST['email'], $_POST['website'], $_POST['group']);

			if ($_POST['id'] == $visitor->id)
				setcookie("chyrp_password", $password, time() + 2592000, "/"); # 30 days

			$route = Route::current();
			$route->redirect("/admin/?action=manage&sub=user&updated");
		}

		/**
		 * Function: update_group
		 * Updates a group when the form is submitted. Shows an error if the user lacks permissions.
		 */
		public function update_group() {
			$visitor = Visitor::current();
			$route = Route::current();
			if (empty($_POST)) return;
			$config = Config::current();
			if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));
			if (!$visitor->group->can('edit_group'))
				error(__("Access Denied"), __("You do not have sufficient privileges to edit groups."));

			$permissions = array_keys($_POST['permissions']);

			$group = new Group($_POST['id']);

			if ($group->no_results)
				$route->redirect("/admin/?action=manage&sub=group");

			$group->update($_POST['name'], $permissions);
			$route->redirect("/admin/?action=manage&sub=group&updated");
		}

		/**
		 * Function: edit
		 * Grabs the information for post editing in the Admin area. Shows an error if the user lacks permissions.
		 */
		public function edit() {
			$visitor = Visitor::current();
			/*
				TODO Figure out why this and the below are different.
			*/
			$type = $_GET['sub'];
			if (!$visitor->group->can("edit_".$type))
				error(__("Access Denied"), sprintf(__("You do not have sufficient privileges to edit %ss."), $type));
			if (empty($_GET['id']))
				error(__("No ID Specified"), sprintf(__("An ID is required to edit a %s."), $type));

			if ($type == "post")
				$post = new Post($_GET['id']);

			if ($type == "page")
				$page = new Page($_GET['id']);

			if ($type == "group")
				$group = new Group($_GET['id']);
		}

		/**
		 * Function: delete
		 * Grabs the information for post deleting in the Admin area. Shows an error if the user lacks permissions.
		 */
		public function delete() {
			$visitor = Visitor::current();
			$type = $_GET['sub'];
			if (!$visitor->group->can("delete_".$type))
				error(__("Access Denied"), sprintf(__("You do not have sufficient privileges to delete %ss."), $type));
			if (empty($_GET['id']))
				error(__("No ID Specified"), sprintf(__("An ID is required to delete a %s."), $type));

			if ($type == "post")
				$post = new Post($_GET['id']);

			if ($type == "page")
				$page = new Page($_GET['id']);
		}

		/**
		 * Function: delete_post_real
		 * Deletes a post. Shows an error if the user lacks permissions.
		 */
		public function delete_post_real() {
			$visitor = Visitor::current();
			if (empty($_POST)) return;
			$config = Config::current();
			if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));
			if (!$visitor->group->can("delete_post"))
				error(__("Access Denied"), __("You do not have sufficient privileges to delete posts."));

			$post = new Post($_POST['id']);
			$post->delete();

			$route = Route::current();
			$route->redirect("/admin/?action=manage&sub=post&deleted");
		}

		/**
		 * Function: delete_page_real
		 * Deletes a page. Shows an error if the user lacks permissions.
		 */
		public function delete_page_real() {
			$visitor = Visitor::current();
			if (empty($_POST)) return;
			$config = Config::current();
			if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));
			if (!$visitor->group->can('delete_page'))
				error(__("Access Denied"), __("You do not have sufficient privileges to delete pages."));

			$page = new Page($_POST['id']);
			$page->delete();

			$route = Route::current();
			$route->redirect("/admin/?action=manage&sub=page&deleted");
		}

		/**
		 * Function: delete_user_real
		 * Deletes a user. Shows an error if the user lacks permissions.
		 */
		public function delete_user_real() {
			$visitor = Visitor::current();
			if (empty($_POST)) return;
			$config = Config::current();
			if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));
			if (!$visitor->group->can('delete_user'))
				error(__("Access Denied"), __("You do not have sufficient privileges to delete users."));

			$user = new User($_POST['id']);
			$user->delete();

			$route = Route::current();
			$route->redirect("/admin/?action=manage&sub=user&deleted");
		}

		/**
		 * Function: delete_group_real
		 * Deletes a group. Shows an error if the user lacks permissions.
		 */
		public function delete_group_real() {
			$visitor = Visitor::current();
			if (empty($_POST)) return;
			$config = Config::current();
			if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));
			if (!$visitor->group->can("delete_group"))
				error(__("Access Denied"), __("You do not have sufficient privileges to delete groups."));

			$sql = SQL::current();
			$get_users = $sql->query("select * from `".$sql->prefix."users`
			                          where `group_id` = :id",
			                         array(
			                             ":id" => $_POST['id']
			                         ));
			foreach ($get_users->fetchAll() as $user) {
				$user = new User($user["id"]);
				$user->update($user->login, $user->password, $user->full_name, $user->email, $user->website, $_POST['move_group']);
			}

			if (!empty($_POST['default_group']))
				$config->set("default_group", $_POST['default_group']);
			if (!empty($_POST['guest_group']))
				$config->set("guest_group", $_POST['guest_group']);

			$group = new Group($_POST['id']);
			$group->delete();

			$route = Route::current();
			$route->redirect("/admin/?action=manage&sub=group&deleted");
		}

		/**
		 * Function: toggle
		 * Enables or disables a module or feather. Shows an error if the user lacks permissions.
		 */
		public function toggle() {
			$visitor = Visitor::current();
			if (!$visitor->group->can("change_settings"))
				if (isset($_GET['module']))
					error(__("Access Denied"), __("You do not have sufficient privileges to enable/disable modules."));
				else
					error(__("Access Denied"), __("You do not have sufficient privileges to enable/disable feathers."));

			$type = (isset($_GET['module'])) ? "module" : "feather" ;
			$enabled_check = $type."_enabled";
			$enabled_array = "enabled_".$type."s";
			$folder = ($type == "module") ? MODULES_DIR : FEATHERS_DIR ;

			$config = Config::current();
			if (!$enabled_check($_GET[$type])) {
				$new = $config->$enabled_array;
				$new[] = $_GET[$type];

				if (file_exists($folder."/".$_GET[$type]."/locale/".$config->locale.".mo"))
					load_translator($_GET[$type], $folder."/".$_GET[$type]."/locale/".$config->locale.".mo");

				$info = Spyc::YAMLLoad(MODULES_DIR."/".$_GET[$type]."/info.yaml");
				fallback($info["uploader"], false);
				fallback($info["notifications"], array());

				require MAIN_DIR."/".$type."s/".$_GET[$type]."/".$type.".php";

				$route = Route::current();
				if ($info["uploader"])
					if (!file_exists(MAIN_DIR."/upload"))
						$info["notifications"][] = __("Please create the <code>/upload</code> directory at your Chyrp install's root and CHMOD it to 777.");
					elseif (!is_writable(MAIN_DIR."/upload"))
						$info["notifications"][] = __("Please CHMOD <code>/upload</code> to 777.");

				$class_name = camelize($_GET[$type]);
				if (method_exists($class_name, "__install"))
					call_user_func(array($class_name, "__install"));

				$config->set($enabled_array, $new);

				if (!isset($_POST['ajax']))
					$route->redirect("/admin/?action=extend&sub=".$type."s&enabled=".$_GET[$type]);
				else {
					$begin = '{ notifications: [';
					$notifications = "";
					for ($i = 0; $i < count($info["notifications"]); $i++) {
						$notifications.= '"'.addslashes(__($info["notifications"][$i], $_GET[$type])).'"';
						if ($i + 1 != count($info["notifications"])) $notifications.= ', ';
					}
					$end = '] }';
					exit($begin.$notifications.$end);
				}
			} else {
				$new = array();

				foreach ($config->$enabled_array as $ext)
					if ($ext != $_GET[$type]) $new[] = $ext;

				$class_name = camelize($_GET[$type]);
				if (method_exists($class_name, "__uninstall"))
					call_user_func(array($class_name, "__uninstall"), ($_POST['confirm'] == "1"));

				$config->set($enabled_array, $new);

				if (!isset($_POST['ajax']))
					$route->redirect("/admin/?action=extend&sub=".$type."s&disabled=".$_GET[$type]);
				else
					exit('{ notifications: [] }');
			}
		}

		/**
		 * Function: settings
		 * Changes Chyrp settings. Shows an error if the user lacks permissions.
		 */
		public function settings() {
			$visitor = Visitor::current();
			if (empty($_POST)) return;
			$config = Config::current();
			if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));
			if (!$visitor->group->can("change_settings"))
				error(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

			switch($_GET['sub']) {
				case "website":
					$can_register = !empty($_POST['can_register']);
					$time_offset = $_POST['time_offset'] * 3600;

					$config->set("name", $_POST['name']);
					$config->set("description", $_POST['description']);
					$config->set("url", $_POST['url']);
					$config->set("email", $_POST['email']);
					$config->set("can_register", $can_register);
					$config->set("default_group", $_POST['default_group']);
					$config->set("guest_group", $_POST['guest_group']);
					$config->set("time_offset", $time_offset);
					$config->set("posts_per_page", $_POST['posts_per_page']);
					$config->set("locale", $_POST['locale']);
					break;
				case "syndication":
					$enable_trackbacking = !empty($_POST['enable_trackbacking']);
					$send_pingbacks = !empty($_POST['send_pingbacks']);

					$config->set("feed_items", $_POST['feed_items']);
					$config->set("enable_trackbacking", $enable_trackbacking);
					$config->set("send_pingbacks", $send_pingbacks);
					break;
				case "routes":
					$clean_urls = !empty($_POST['clean_urls']);
					$config->set("clean_urls", $clean_urls);
					$config->set("post_url", $_POST['post_url']);
					break;
				default:
					$trigger = Trigger::current();
					$trigger->call("change_settings", $_GET['sub']);
					break;
			}
			$route = Route::current();
			$route->redirect("/admin/?action=settings&sub=".$_GET['sub']."&updated");
		}

		/**
		 * Function: change_theme
		 * Changes the theme. Shows an error if the user lacks permissions.
		 */
		public function change_theme() {
			$visitor = Visitor::current();
			if (!$visitor->group->can('change_settings') or empty($_GET['theme'])) return;
			$config = Config::current();
			$config->set("theme", $_GET['theme']);
			$route = Route::current();
			$route->redirect("/admin/?action=extend&sub=themes&changed");
		}
	}
	$admin = new AdminController();
