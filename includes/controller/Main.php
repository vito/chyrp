<?php
	/**
	 * Class: Main Controller
	 * The logic behind the Chyrp install.
	 */
	class MainController {
		# Boolean: $displayed
		# Has anything been displayed?
		public $displayed = false;

		# Array: $context
		# Context for displaying pages.
		public $context = array();

		private function __construct() {}

		/**
		 * Function: index
		 * Grabs the posts for the main page.
		 */
		public function index() {
			$this->display("pages/index",
			               array("posts" => new Paginator(Post::find(array("placeholders" => true)),
			                                              Config::current()->posts_per_page)));
		}

		/**
		 * Function: archive
		 * Grabs the posts for the Archive page when viewing a year or a month.
		 */
		public function archive() {
			fallback($_GET['year']);
			fallback($_GET['month']);
			fallback($_GET['day']);

			if (isset($_GET['year']) and isset($_GET['month']) and isset($_GET['day']))
				$posts = new Paginator(Post::find(array("placeholders" => true,
				                                        "where" => array("created_at like" => $_GET['year']."-".$_GET['month']."-".$_GET['day']."%"))),
				                       Config::current()->posts_per_page);
			elseif (isset($_GET['year']) and isset($_GET['month']))
				$posts = new Paginator(Post::find(array("placeholders" => true,
				                                        "where" => array("created_at like" => $_GET['year']."-".$_GET['month']."%"))),
				                       Config::current()->posts_per_page);

			$sql = SQL::current();

			if (empty($_GET['year']) or empty($_GET['month'])) {
				if (!empty($_GET['year']))
					$timestamps = $sql->select("posts",
					                           array("DISTINCT YEAR(created_at) AS year",
					                                 "MONTH(created_at) AS month",
					                                 "created_at AS created_at",
					                                 "COUNT(id) AS posts"),
					                           array("YEAR(created_at)" => $_GET['year']),
					                           "created_at DESC, id DESC",
					                           array(),
					                           null,
					                           null,
					                           array("YEAR(created_at)", "MONTH(created_at)"));
				else
					$timestamps = $sql->select("posts",
					                           array("DISTINCT YEAR(created_at) AS year",
					                                 "MONTH(created_at) AS month",
					                                 "created_at AS created_at",
					                                 "COUNT(id) AS posts"),
					                           null,
					                           "created_at DESC, id DESC",
					                           array(),
					                           null,
					                           null,
					                           array("YEAR(created_at)", "MONTH(created_at)"));

				$archives = array();
				while ($time = $timestamps->fetchObject()) {
					$timestamp = mktime(0, 0, 0, $time->month + 1, 0, $time->year);
					$archives[$timestamp] = array("posts" => Post::find(array("where" => array("created_at like" => when("Y-m", $time->created_at)."%"))),
					                              "year" => $time->year,
					                              "month" => strftime("%B", $timestamp),
					                              "timestamp" => $timestamp,
					                              "url" => url("archive/".when("Y/m/", $time->created_at)));
				}

				$this->display("pages/archive", array("archives" => $archives), __("Archive"));
			} else {
				if (!is_numeric($_GET['year']) or !is_numeric($_GET['month']))
					error(__("Error"), __("Please enter a valid year and month."));

				$timestamp = mktime(0, 0, 0, $_GET['month'], fallback($_GET['day'], "1", true), $_GET['year']);
				$depth = isset($_GET['day']) ? "day" : (isset($_GET['month']) ? "month" : (isset($_GET['year']) ? "year" : ""));

				$this->display("pages/archive",
				               array("posts" => $posts,
				                     "archive" => array("year" => $_GET['year'],
				                                        "month" => strftime("%B", $timestamp),
				                                        "day" => strftime("%e", $timestamp),
				                                        "timestamp" => $timestamp,
				                                        "depth" => $depth)),
				               _f("Archive of %s", array(strftime("%B %Y", $timestamp))));
			}
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

			$_GET['query'] = urldecode($_GET['query']);

			if (empty($_GET['query']))
				return Flash::warning(__("Please enter a search term."));

			list($where, $params) = keywords($_GET['query'], "xml LIKE :query OR url LIKE :query");

			$posts = new Paginator(Post::find(array("placeholders" => true,
			                                        "where" => $where,
			                                        "params" => $params)),
				                   Config::current()->posts_per_page);

			$this->display(array("pages/search", "pages/index"),
			               array("posts" => $posts,
			                     "search" => urldecode($_GET['query'])),
			               fix(_f("Search results for \"%s\"", array(urldecode($_GET['query'])))));
		}

		/**
		 * Function: drafts
		 * Grabs the posts for viewing the Drafts lists.
		 */
		public function drafts() {
			$visitor = Visitor::current();

			if (!$visitor->group()->can("view_own_draft", "view_draft"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to view drafts."));

			$posts = new Paginator(Post::find(array("placeholders" => true,
			                                        "where" => array("status" => "draft",
			                                                         "user_id" => $visitor->id))),
				                   Config::current()->posts_per_page);

			$this->display(array("pages/drafts", "pages/index"),
			               array("posts" => $posts),
			               __("Drafts"));
		}

		/**
		 * Function: view
		 * Views a post.
		 */
		public function view($attrs = null) {
			if (isset($attrs))
				$post = Post::from_url($attrs, array("drafts" => true));
			else
				$post = new Post(null, array("where" => array("url" => urldecode(fallback($_GET['url'])))));

			if ($post->no_results)
				return false;

			if (!$post->theme_exists())
				error(__("Error"), __("The feather theme file for this post does not exist. The post cannot be displayed."));

			if ($post->status == "draft")
				Flash::message(__("This post is a draft."));

			$this->display(array("pages/view", "pages/index"),
			               array("post" => $post, "posts" => array($post)),
			               $post->title());
		}

		/**
		 * Function: page
		 * Handles page viewing.
		 */
		public function page($urls = null) {
			if (isset($urls)) { # Viewing with clean URLs, e.g. /parent/child/child-of-child/
				$valids = Page::find(array("where" => array("url" => $urls)));

				if (count($valids) == count($urls)) { # Make sure all page slugs are valid.
					foreach ($valids as $page)
						if ($page->url == end($urls)) # Loop until we reach the last one.
							break;
				} else
					return false; # A "link in the chain" is broken
			} else
				$page = new Page(null, array("where" => array("url" => $_GET['url'])));

			if ($page->no_results)
				return false; # Page not found; the 404 handling is handled externally.

			$this->display(array("pages/page", "pages/".$page->url), array("page" => $page), $page->title);
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
		 * Function: id
		 * Views a post by its static ID.
		 */
		public function id() {
			$post = new Post($_GET['id']);
			redirect($post->url());
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

			if (!empty($_POST)) {
				$route = Route::current();

				if (empty($_POST['login']))
					Flash::warning(__("Please enter a username for your account."));
				elseif (count(User::find(array("where" => array("login" => $_POST['login'])))))
					Flash::warning(__("That username is already in use."));

				if (empty($_POST['password1']) and empty($_POST['password2']))
					Flash::warning(__("Password cannot be blank."));
				elseif ($_POST['password1'] != $_POST['password2'])
					Flash::warning(__("Passwords do not match."));

				if (empty($_POST['email']))
					Flash::warning(__("E-mail address cannot be blank."));
				elseif (!preg_match("/^[[:alnum:]][a-z0-9_.-\+]*@[a-z0-9.-]+\.[a-z]{2,6}$/i", $_POST['email']))
					Flash::warning(__("Unsupported e-mail address."));

				if (!Flash::exists("warning")) {
					User::add($_POST['login'], $_POST['password1'], $_POST['email']);

					$_SESSION['login'] = $_POST['login'];
					$_SESSION['password'] = md5($_POST['password1']);

					Flash::notice(__("Registration successful."), "/");
				}
			}

			$this->display("forms/user/register", array(), __("Register"));
		}

		/**
		 * Function: login
		 * Process logging in. If the username and password are incorrect or if the user is already logged in, it will error.
		 */
		public function login() {
			if (logged_in())
				error(__("Error"), __("You're already logged in."));

			if (!empty($_POST)) {
				fallback($_POST['login']);
				fallback($_POST['password']);

				$trigger = Trigger::current();

				if ($trigger->exists("authenticate"))
					return $trigger->call("authenticate");

				if (!User::authenticate($_POST['login'], md5($_POST['password'])))
					if (!count(User::find(array("where" => array("login" => $_POST['login'])))))
						Flash::warning(__("There is no user with that login name."));
					else
						Flash::warning(__("Password incorrect."));

				if (!Flash::exists("warning")) {
					$_SESSION['login'] = $_POST['login'];
					$_SESSION['password'] = md5($_POST['password']);

					Flash::notice(__("Logged in."), "/");
				}
			}

			$this->display("forms/user/login", array(), __("Log In"));
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
			if (!logged_in())
				error(__("Error"), __("You must be logged in to access this area."));

			if (!empty($_POST)) {
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

			$this->display("forms/user/controls", array(), __("Controls"));
		}

		/**
		 * Function: lost_password
		 * Handles e-mailing lost passwords to a user's email address.
		 */
		public function lost_password() {
			if (!empty($_POST)) {
				$user = new User(null, array("where" => array("login" => $_POST['login'])));
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
					Flash::notice(_f("An e-mail has been sent to your e-mail address that contains a new password. Once you have logged in, you can change it at <a href=\"%s\">User Controls</a>.",
					              array(url("controls/"))));
				else {
					# Set their password back to what it was originally.
					$user->update($user->login,
					              $user->password,
					              $user->email,
					              $user->full_name,
					              $user->website,
					              $user->group_id);

					Flash::warning(__("E-Mail could not be sent. Password change cancelled."));
				}
			}

			$this->display("forms/user/lost_password", array(), __("Lost Password"));
		}

		/**
		 * Function: feed
		 * Grabs posts for the feed.
		 */
		public function feed($posts = null) {
			fallback($posts, Post::find(array("limit" => Config::current()->feed_items)));

			header("Content-Type: application/atom+xml; charset=UTF-8");

			if (!is_array($posts)) {
				$ids = array();
				foreach ($posts->array[0] as $result)
					$ids[] = $result["id"];

				$posts = Post::find(array("where" => array("id" => $ids)));
			}

			$latest_timestamp = 0;
			foreach ($posts as $post)
				if (strtotime($post->created_at) > $latest_timestamp)
					$latest_timestamp = strtotime($post->created_at);

			require "includes/feed.php";
		}

		/**
		 * Function: display
		 * Display the page.
		 *
		 * If "posts" is in the context and the visitor requested a feed, they will be served.
		 *
		 * Parameters:
		 *     $file - The theme file to display.
		 *     $context - The context for the file.
		 *     $title - The title for the page.
		 */
		public function display($file = null, $context = array(), $title = "") {
			if (!isset($file))
				return false; # If they viewed /display, this'll get called.

			$route = Route::current();
			$trigger = Trigger::current();

			# Serve feeds.
			if ($route->feed) {
				if ($trigger->call($route->action."_feed", $context))
					return;

				if (isset($context["posts"]))
					return $this->feed($context["posts"]);
			}

			$this->displayed = true;

			$theme = Theme::current();
			$theme->title = $title;

			$this->context = $context;

			$trigger->filter($this->context, array("main_context", "main_context_".str_replace("/", "_", $file)));

			$visitor = Visitor::current();
			$config = Config::current();

			$this->context["theme"]        = $theme;
			$this->context["flash"]        = Flash::current();
			$this->context["trigger"]      = $trigger;
			$this->context["modules"]      = Modules::$instances;
			$this->context["feathers"]     = Feathers::$instances;
			$this->context["title"]        = $theme->title;
			$this->context["site"]         = $config;
			$this->context["visitor"]      = $visitor;
			$this->context["route"]        = Route::current();
			$this->context["hide_admin"]   = isset($_COOKIE["hide_admin"]);
			$this->context["version"]      = CHYRP_VERSION;
			$this->context["now"]          = time();
			$this->context["debug"]        = DEBUG;
			$this->context["POST"]         = $_POST;
			$this->context["GET"]          = $_GET;
			$this->context["sql_queries"]  =& SQL::current()->queries;

			$this->context["visitor"]->logged_in = logged_in();

			$this->context["enabled_modules"] = array();
			foreach ($config->enabled_modules as $module)
				$this->context["enabled_modules"][$module] = true;

			$context["enabled_feathers"] = array();
			foreach ($config->enabled_feathers as $feather)
				$this->context["enabled_feathers"][$feather] = true;

			$this->context["sql_debug"] =& SQL::current()->debug;

			return $theme->load($file, $this->context);
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
