<?php
	/**
	 * Class: Main Controller
	 * The logic behind the Chyrp install.
	 */
	class MainController {
		private function __construct() { }

		/**
		 * Function: index
		 * Grabs the posts for the main page.
		 */
		public function index() {
			global $posts;
			$posts = new Paginator(Post::find(array("placeholders" => true)), Config::current()->posts_per_page);
		}

		/**
		 * Function: archive
		 * Grabs the posts for the Archive page when viewing a year or a month.
		 */
		public function archive() {
			global $posts;
			if (!isset($_GET['month'])) return;

			if (isset($_GET['day']))
				$posts = new Paginator(Post::find(array("placeholders" => true,
				                                        "where" => "created_at LIKE :date",
				                                        "params" => array(":date" => $_GET['year']."-".$_GET['month']."-".$_GET['day']."%"))),
				                       Config::current()->posts_per_page);
			else
				$posts = new Paginator(Post::find(array("placeholders" => true,
				                                        "where" => "created_at LIKE :date",
				                                        "params" => array(":date" => $_GET['year']."-".$_GET['month']."%"))),
				                       Config::current()->posts_per_page);
		}

		/**
		 * Function: search
		 * Grabs the posts for a search query.
		 */
		public function search() {
			fallback($_GET['query'], "");
			$config = Config::current();

			if ($config->clean_urls and substr_count($_SERVER['REQUEST_URI'], "?"))
				redirect("search/".urlencode($_GET['query'])."/");

			global $posts;

			if (!empty($_GET['query'])) {
				$_GET['query'] = urldecode($_GET['query']);

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

			$posts = new Paginator(Post::find(array("placeholders" => true,
			                                        "where" => $where,
			                                        "params" => $params)),
				                   Config::current()->posts_per_page);
		}

		/**
		 * Function: drafts
		 * Grabs the posts for viewing the Drafts lists.
		 */
		public function drafts() {
			$visitor = Visitor::current();
			if (!$visitor->group()->can("view_own_draft", "view_draft"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to view drafts."));

			global $posts;
			$posts = new Paginator(Post::find(array("placeholders" => true,
			                                        "where" => array("status = 'draft'",
			                                                         "user_id = :current_user"),
			                                        "params" => array(":current_user" => $visitor->id))),
				                   Config::current()->posts_per_page);
		}

		/**
		 * Function: feather
		 * Views posts of a specific feather.
		 */
		public function feather() {
			global $posts;
			$posts = new Paginator(Post::find(array("placeholders" => true,
			                                        "where" => "feather = :feather",
			                                        "params" => array(":feather" => depluralize($_GET['feather'])))),
				                   Config::current()->posts_per_page);
		}

		/**
		 * Function: page
		 * Handles page viewing.
		 */
		public function page() {
			global $page;

			if (!isset($page))
				$page = new Page(null, array("where" => "url = :url", "params" => array(":url" => $_GET['url'])));
		}

		/**
		 * Function: feed
		 * Grabs posts for the feed.
		 */
		public function feed() {
			global $posts;
			$posts = new Paginator(Post::find(array("placeholders" => true)), Config::current()->posts_per_page);
		}

		/**
		 * Function: rss
		 * Redirects to /feed (backwards compatibility).
		 */
		public function rss() {
			header("HTTP/1.1 301 Moved Permanently");
			redirect(fallback(Config::current()->feed_url, url("feed/"), true));
		}

		/**
		 * Function: view
		 * Views a post.
		 */
		public function view() {
			global $post;

			$config = Config::current();
			$route = Route::current();

			$get = array_map("urldecode", $_GET);

			if (!$config->clean_urls)
				$post = new Post(null, array("where" => "url = :url",
				                             "params" => array(":url" => fallback($get['url']))));
			else
				$post = Post::from_url($route->post_url_attrs);

			if ($post->no_results)
				show_404();
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
			$visitor = Visitor::current();
			$route = Route::current();

			if (!$visitor->group()->can("change_settings"))
				redirect("/");

			if (empty($_GET['theme']))
				error(__("Error"), __("Please specify a theme to preview."));

			$this->index();
			return $route->action = "index";
		}

		/**
		 * Function: toggle_admin
		 * Toggles the Admin control panel (if available).
		 */
		public function toggle_admin() {
			if (!isset($_SESSION['hide_admin']))
				$_SESSION['hide_admin'] = true;
			else
				unset($_SESSION['hide_admin']);

			redirect("/");
		}

		/**
		 * Function: register
		 * Process registration. If registration is disabled or if the user is already logged in, it will error.
		 */
		public function register() {
			$config = Config::current();
			if (!$config->can_register)
				error(__("Registration Disabled"), __("I'm sorry, but this site is not allowing registration."));

			if (logged_in())
				error(__("Error"), __("You're already logged in."));

			if (empty($_POST))
				return;

			$route = Route::current();

			if (empty($_POST['login']))
				return Flash::warning(__("Please enter a username for your account."));

			if (count(User::find(array("where" => "login = :login",
			                           "params" => array(":login" => $_POST['login'])))))
				Flash::warning(__("That username is already in use."));

			if (empty($_POST['password1']) and empty($_POST['password2']))
				Flash::warning(__("Password cannot be blank."));
			elseif ($_POST['password1'] != $_POST['password2'])
				Flash::warning(__("Passwords do not match."));

			if (empty($_POST['email']))
				Flash::warning(__("E-mail address cannot be blank."));
			elseif (!eregi("^[[:alnum:]][a-z0-9_.-\+]*@[a-z0-9.-]+\.[a-z]{2,6}$",$_POST['email']))
				Flash::warning(__("Unsupported e-mail address."));

			if (Flash::exists("warning"))
				return;

			User::add($_POST['login'], $_POST['password1'], $_POST['email']);

			$_SESSION['login'] = $_POST['login'];
			$_SESSION['password'] = md5($_POST['password1']);

			Flash::notice(__("Registration successful."), "/");
		}

		/**
		 * Function: login
		 * Process logging in. If the username and password are incorrect or if the user is already logged in, it will error.
		 */
		public function login() {
			if (logged_in())
				error(__("Error"), __("You're already logged in."));

			if (empty($_POST))
				return;

			fallback($_POST['login']);
			fallback($_POST['password']);

			if (!User::authenticate($_POST['login'], md5($_POST['password'])))
				if (!count(User::find(array("where" => "login = :login",
				                           "params" => array(":login" => $_POST['login'])))))
					Flash::warning(__("There is no user with that login name."));
				else
					Flash::warning(__("Password incorrect."));

			if (Flash::exists("warning"))
				return;

			$_SESSION['login'] = $_POST['login'];
			$_SESSION['password'] = md5($_POST['password']);

			Flash::notice(__("Logged in."), "/");
		}

		/**
		 * Function: logout
		 * Logs the current user out. If they are not logged in, it will error.
		 */
		public function logout() {
			if (!logged_in())
				error(__("Error"), __("You aren't logged in."));

			session_destroy();

			session();

			Flash::notice(__("Logged out."), "/");
		}

		/**
		 * Function: controls
		 * Updates the current user when the form is submitted. Shows an error if they aren't logged in.
		 */
		public function controls() {
			if (empty($_POST))
				return;

			if (!logged_in())
				error(__("Error"), __("You must be logged in to access this area."));

			$visitor = Visitor::current();

			$password = (!empty($_POST['new_password1']) and $_POST['new_password1'] == $_POST['new_password2']) ?
			            md5($_POST['new_password1']) :
			            $visitor->password ;

			$visitor->update($visitor->login,
			                 $password,
			                 $_POST['email'],
			                 $_POST['full_name'],
			                 $_POST['website'],
			                 $visitor->group()->id);

			$_SESSION['password'] = $password;

			Flash::notice(__("Your profile has been updated."), "/");
		}

		/**
		 * Function: lost_password
		 * Handles e-mailing lost passwords to a user's email address.
		 */
		public function lost_password() {
			if (empty($_POST))
				return;

			$user = new User(null, array("where" => "login = :login", "params" => array(":login" => $_POST['login'])));
			if ($user->no_results)
				return Flash::warning(__("Invalid user specified."));

			$new_password = random(16);

			$user->update($user->login,
			              md5($new_password),
			              $user->email,
			              $user->full_name,
			              $user->website,
			              $user->group_id);

			$sent = @mail($user->email,
				          __("Lost Password Request"),
				          _f("%s,\n\nWe have received a request for a new password for your account at %s.\n\nPlease log in with the following password, and feel free to change it once you've successfully logged in:\n\t%s",
				             array($user->login, $config->name, $new_password)));

			if ($sent)
				return Flash::notice(_f("An e-mail has been sent to your e-mail address that contains a new password. Once you have logged in, you can change it at <a href=\"%s\">User Controls</a>.",
				                         array(url("controls/"))));

			# Set their password back to what it was originally.
			$user->update($user->login,
			              $user->password,
			              $user->email,
			              $user->full_name,
			              $user->website,
			              $user->group_id);

			Flash::warning(__("E-Mail could not be sent. Password change cancelled."));
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
	$main = MainController::current();
