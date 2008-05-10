<?php
	/**
	 * Class: Main Controller
	 * The logic behind the Chyrp install.
	 */
	class MainController {
		/**
		 * Function: index
		 * Grabs the posts for the main page.
		 */
		public function index() {
			global $posts;
			$posts = Post::find();
		}

		/**
		 * Function: archive
		 * Grabs the posts for the Archive page when viewing a year or a month.
		 */
		public function archive() {
			global $private, $posts;
			if (!isset($_GET['month'])) return;

			if (isset($_GET['day']))
				$posts = Post::find(array("where" => array("`created_at` like :date", $private),
				                          "params" => array(":date" => $_GET['year']."-".$_GET['month']."-".$_GET['day']."%")));
			else
				$posts = Post::find(array("where" => array("`created_at` like :date", $private),
				                          "params" => array(":date" => $_GET['year']."-".$_GET['month']."%")));
		}

		/**
		 * Function: search
		 * Grabs the posts for a search query.
		 */
		public function search() {
			global $private, $posts;
			fallback($_GET['query'], "");
			$posts = Post::find(array("where" => "`xml` like :query and ".$private,
			                          "params" => array(":query" => '%'.urldecode($_GET['query']).'%')));
		}

		/**
		 * Function: drafts
		 * Grabs the posts for viewing the Drafts lists. Shows an error if the user lacks permissions.
		 */
		public function drafts() {
			global $posts;
			if (!Visitor::current()->group()->can("view_draft"))
				error(__("Access Denied"), __("You do not have sufficient privileges to view drafts."));

			$posts = Post::find(array("where" => "`status` = 'draft'"));
		}

		/**
		 * Function: feather
		 * Views posts of a specific feather.
		 */
		public function feather() {
			global $private, $posts;
			$posts = Post::find(array("where" => "`feather` = :feather and ".$private,
			                          "params" => array(":feather" => depluralize($_GET['action']))));
		}

		/**
		 * Function: feed
		 * Grabs posts for the feed.
		 */
		public function feed() {
			global $posts;
			$posts = Post::find();
		}

		/**
		 * Function: view
		 * Views a post.
		 */
		public function view() {
			global $action, $private, $post;

			$trigger = Trigger::current();
			$config = Config::current();
			$sql = SQL::current();
			if (!$config->clean_urls)
				return $post = Post::grab(null, array("where" => array($private, "`url` = :url"), "params" => array(":url" => $_GET['url'])));

			# Check for a post...
			$where = array($private);
			$times = array("year", "month", "day", "hour", "minute", "second");

			preg_match_all("/\(([^\)]+)\)/", $config->post_url, $matches);
			$params = array();
			foreach ($matches[1] as $attr)
				if (in_array($attr, $times)) {
					$where[] = $attr."(`created_at`) = :created".$attr;
					$params[':created'.$attr] = $_GET[$attr];
				} elseif ($attr == "author") {
					$where[] = "`user_id` = :attrauthor";
					$params[':attrauthor'] = $sql->select("users",
					                                      "id",
					                                      "`login` = :login",
					                                      "id",
					                                      array(
					                                          ":login" => $_GET['author']
					                                      ), 1)->fetchColumn();
				} elseif ($attr == "feathers") {
					$where[] = "`feather` = :feather";
					$params[':feather'] = depluralize($_GET['feathers']);
				} else {
					list($where, $params, $attr) = $trigger->filter('main_controller_view', array($where, $params, $attr), true);

					if ($attr !== null) {
						$where[] = "`".$attr."` = :attr".$attr;
						$params[':attr'.$attr] = $_GET[$attr];
					}
				}

			$post = new Post(null, array("where" => $where, "params" => $params));

			if ($post->no_results) {
				# Check for a page...
				fallback($_GET['url'], "");
				$check_page = $sql->count("pages",
				                          "`url` = :url",
				                          array(
				                              ':url' => $_GET['url']
				                          ));
				if ($check_page == 1)
					return $action = $url;
			}

			return (!$post->no_results) ? $action = "view" : $action = $_GET['url'] ;
		}

		/**
		 * Function: id
		 * Views a post by its static ID.
		 */
		public function id() {
			global $post;
			$post = new Post($_GET['id']);
		}

		/**
		 * Function: theme_preview
		 * Handles theme previewing.
		 */
		public function theme_preview() {
			global $action;
			$visitor = Visitor::current();
			if (!$visitor->group()->can("change_settings")) {
				$this->index();
				return $action = "index";
			}
			if (empty($_GET['theme']))
				error(__("Error"), __("Please specify a theme to preview."));

			$this->index();
			return $action = "index";
		}

		/**
		 * Function: toggle_admin
		 * Toggles the Admin control panel (if available).
		 */
		public function toggle_admin() {
			if (!isset($_COOKIE['chyrp_hide_admin']))
				cookie_cutter("chyrp_hide_admin", "true");
			else
				cookie_cutter("chyrp_hide_admin", null, 0);

			$route = Route::current();
			redirect("/");
		}

		/**
		 * Function: process_registration
		 * Process registration. If registration is disabled or if the user is already logged in, it will error.
		 */
		public function process_registration() {
			$config = Config::current();
			if (!$config->can_register)
				error(__("Registration Disabled"), __("I'm sorry, but this site is not allowing registration."));
			if (logged_in())
				error(__("Error"), __("You're already logged in."));

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

			Trigger::current()->call('process_registration');

			User::add($_POST['login'], $_POST['password1'], $_POST['email']);

			cookie_cutter("chyrp_login", $_POST['login']);
			cookie_cutter("chyrp_password", md5($_POST['password1']));

			$route = Route::current();
			redirect('/');
		}

		/**
		 * Function: process_login
		 * Process logging in. If the username and password are incorrect or if the user is already logged in, it will error.
		 */
		public function process_login() {
			if (!User::authenticate($_POST['login'], md5($_POST['password'])))
				error(__("Error"), __("Login incorrect."));
			if (logged_in())
				error(__("Error"), __("You're already logged in."));

			$sql = SQL::current();
			$get_id = $sql->query("select `id` from `".$sql->prefix."users`
			                       where `login` = :login",
			                      array(
			                          ':login' => $_POST['login']
			                      ));

			cookie_cutter("chyrp_login", $_POST['login']);
			cookie_cutter("chyrp_password", md5($_POST['password']));

			$route = Route::current();
			redirect('/');
		}

		/**
		 * Function: logout
		 * Logs the current user out. If they are not logged in, it will error.
		 */
		public function logout() {
			if (!logged_in())
				error(__("Error"), __("You aren't logged in."));

			cookie_cutter("chyrp_login", "", time() - 2592000);
			cookie_cutter("chyrp_password", "", time() - 2592000);

			$route = Route::current();
			redirect('/');
		}

		/**
		 * Function: update_self
		 * Updates the current user when the form is submitted. Shows an error if they aren't logged in.
		 */
		public function update_self() {
			if (empty($_POST)) return;
			if (!logged_in())
				error(__("Error"), __("You must be logged in to access this area."));

			$visitor = Visitor::current();

			$password = (!empty($_POST['new_password1']) and $_POST['new_password1'] == $_POST['new_password2']) ?
			            md5($_POST['new_password1']) :
			            $visitor->password ;

			$visitor->update($visitor->login, $password, $_POST['full_name'], $_POST['email'], $_POST['website'], $visitor->group()->id);

			cookie_cutter("chyrp_password", $password);

			$route = Route::current();
			redirect('/');
		}
	}
	$main = new MainController();
