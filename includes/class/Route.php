<?php
	/**
	 * Class: Route
	 * Holds information for URLs, redirecting, etc.
	 */
	class Route {
		# Array: $code
		# The translation array of the post URL setting to regular expressions.
		# Passed through the route_code filter.
		public $code = array('(year)'     => '([0-9]{4})',
		                     '(month)'    => '([0-9]{1,2})',
		                     '(day)'      => '([0-9]{1,2})',
		                     '(hour)'     => '([0-9]{1,2})',
		                     '(minute)'   => '([0-9]{1,2})',
		                     '(second)'   => '([0-9]{1,2})',
		                     '(id)'       => '([0-9]+)',
		                     '(author)'   => '([^\/]+)',
		                     '(clean)'    => '([^\/]+)',
		                     '(url)'      => '([^\/]+)',
		                     '(feather)'  => '([^\/]+)',
		                     '(feathers)' => '([^\/]+)');

		# Function: $urls
		# An array of clean URL => dirty URL translations.
		public $urls = array('/\/id\/([0-9]+)\//'                => '/?action=view&amp;id=$1',
		                     '/\/page\/(([^\/]+)\/)+/'           => '/?action=page&amp;url=$2',
		                     '/\/search\//'                      => '/?action=search',
		                     '/\/search\/([^\/]+)\//'            => '/?action=search&amp;query=$1',
		                     '/\/archive\/([0-9]{4})\/([0-9]{2})\//'
		                                                         => '/?action=archive&amp;year=$1&amp;month=$2',
		                     '/\/archive\/([0-9]{4})\/([0-9]{2})\/([0-9]{2})\//'
		                                                         => '/?action=archive&amp;year=$1&amp;month=$2&amp;day=$3',
		                     '/\/([^\/]+)\/feed\/([^\/]+)\//'    => '/?action=$1&amp;feed&amp;title=$2',
		                     '/\/([^\/]+)\/feed\//'              => '/?action=$1&amp;feed');

		# String: $action
		# The current action.
		public $action = "";

		# Array: $try
		# An array of (string) actions to try until one doesn't return false.
		public $try = array();

		# Boolean: $ajax
		# Shortcut to the AJAX constant (useful for Twig).
		public $ajax = AJAX;

		# Boolean: $success
		# Did <Route.init> call a successful route?
		public $success = false;

		# Boolean: $feed
		# Is the visitor requesting a feed?
		public $feed = false;

		# Variable: $controller
		# The Route's Controller.
		public $controller;

		/**
		 * Function: __construct
		 * Parse the URL to determine what to do.
		 */
		private function __construct($controller) {
			$this->controller = $controller;

			$config = Config::current();

			$this->action =& $_GET['action'];

			if (isset($_GET['feed']))
				$this->feed = true;

			# Parse the current URL and extract information.
			$parse = parse_url($config->url);
			fallback($parse["path"]);

			$this->safe_path = str_replace("/", "\\/", $parse["path"]);
			$this->request = preg_replace("/".$this->safe_path."/", "", $_SERVER['REQUEST_URI'], 1);
			$this->arg = explode("/", trim($this->request, "/"));

			if ($controller instanceof AdminController)
				$this->default_admin_action();

			if (!($controller instanceof MainController))
				return;

			$this->determine_action();

			# Check if we are viewing a custom route, and set the action/GET parameters accordingly.
			$this->check_custom_routes();

			# If the post viewing URL is the same as the page viewing URL, check for viewing a page first.
			if (preg_match("/^\((clean|url)\)\/?$/", $config->post_url)) {
				$this->check_viewing_page();
				$this->check_viewing_post();
			} else {
				$this->check_viewing_post();
				$this->check_viewing_page();
			}

			$this->try[] = fallback($this->arg[0], "index", true);
		}

		/**
		 * Function: init
		 * Begin running Controller actions until one of them doesn't return false.
		 *
		 * This will also call the route_xxxxx Triggers.
		 *
		 * Parameters:
		 *     $controller - The Controller to run methods on.
		 */
		public function init() {
			$trigger = Trigger::current();

			$trigger->call("route_init", $this);

			$try = $this->try;
			array_unshift($try, $this->action);

			foreach ($try as $key => $val) {
				if (is_numeric($key))
					list($method, $args) = array($val, array());
				else
					list($method, $args) = array($key, $val);

				$this->action = $method;

				if (method_exists($this->controller, $method))
					$response = call_user_func_array(array($this->controller, $method), $args);
				else
					$response = false;

				$name = strtolower(str_replace("Controller", "", get_class($this->controller)));
				if ($trigger->exists($name."_".$method) or $trigger->exists("route_".$method))
					$call = $trigger->call(array($name."_".$method, "route_".$method), $this->controller);
				else
					$call = false;

				if ($response !== false or $call !== false)
					return $this->success = true;
			}
		}

		/**
		 * Function: default_admin_action
		 * Determines what action to set as the default in the Admin for various situations.
		 */
		public function default_admin_action() {
			$visitor = Visitor::current();

			if (empty($this->action) or $this->action == "write") {
				# "Write > Post", if they can add posts or drafts.
				if (($visitor->group()->can("add_post") or $visitor->group()->can("add_draft")) and
				    !empty(Config::current()->enabled_feathers))
					return $this->action = "write_post";

				# "Write > Page", if they can add pages.
				if ($visitor->group()->can("add_page"))
					return $this->action = "write_page";
			}

			if (empty($this->action) or $this->action == "manage") {
				# "Manage > Posts", if they can manage any posts.
				if (Post::any_editable() or Post::any_deletable())
					return $this->action = "manage_posts";

				# "Manage > Pages", if they can manage pages.
				if ($visitor->group()->can("edit_page") or $visitor->group()->can("delete_page"))
					return $this->action = "manage_pages";

				# "Manage > Users", if they can manage users.
				if ($visitor->group()->can("edit_user") or $visitor->group()->can("delete_user"))
					return $this->action = "manage_users";

				# "Manage > Groups", if they can manage groups.
				if ($visitor->group()->can("edit_group") or $visitor->group()->can("delete_group"))
					return $this->action = "manage_groups";
			}

			if (empty($this->action) or $this->action == "settings") {
				# "General Settings", if they can configure the installation.
				if ($visitor->group()->can("change_settings"))
					return $this->action = "general_settings";
			}

			if (empty($this->action) or $this->action == "extend") {
				# "Modules", if they can configure the installation.
				if ($visitor->group()->can("toggle_extensions"))
					return $this->action = "modules";
			}

			Trigger::current()->filter($this->action, "admin_determine_action");
		}

		/**
		 * Function: determine_action
		 * Determine the action of the current URL.
		 */
		public function determine_action() {
			$config = Config::current();

			if (empty($this->arg[0])) # If they're just at /, don't bother with all this.
				return $this->action = "index";

			# Feed
			if (preg_match("/\/feed\/?$/", $this->request)) {
				$this->feed = true;

				if ($this->arg[0] == "feed") # Don't set $this->action to "feed" (bottom of this function).
					return $this->action = "index";
			}

			# Feed with a title parameter
			if (preg_match("/\/feed\/([^\/]+)\/?$/", $this->request, $title)) {
				$this->feed = true;
				$_GET['title'] = $title[1];

				if ($this->arg[0] == "feed") # Don't set $this->action to "feed" (bottom of this function).
					return $this->action = "index";
			}

			# Paginator
			if (preg_match_all("/\/((([^_\/]+)_)?page)\/([0-9]+)/", $this->request, $page_matches)) {
				foreach ($page_matches[1] as $key => $page_var)
					$_GET[$page_var] = (int) $page_matches[4][$key];

				if ($this->arg[0] == $page_matches[1][0]) # Don't fool ourselves into thinking we're viewing a page.
					return $this->action = (isset($config->routes["/"])) ? $config->routes["/"] : "index" ;
			}

			# Viewing a post by its ID
			if ($this->arg[0] == "id") {
				$_GET['id'] = $this->arg[1];
				return $this->action = "id";
			}

			# Archive
			if ($this->arg[0] == "archive") {
				# Make sure they're numeric; there might be a /page/ in there.
				if (isset($this->arg[1]) and is_numeric($this->arg[1]))
					$_GET['year'] = $this->arg[1];
				if (isset($this->arg[2]) and is_numeric($this->arg[2]))
					$_GET['month'] = $this->arg[2];
				if (isset($this->arg[3]) and is_numeric($this->arg[3]))
					$_GET['day'] = $this->arg[3];

				return $this->action = "archive";
			}

			# Searching
			if ($this->arg[0] == "search") {
				if (isset($this->arg[1]))
					$_GET['query'] = $this->arg[1];

				return $this->action = "search";
			}
		}

		/**
		 * Function: check_custom_routes
		 * Check to see if we're viewing a custom route, and if it is, parse it.
		 */
		public function check_custom_routes() {
			$config = Config::current();

			# Custom pages added by Modules, Feathers, Themes, etc.
			foreach ($config->routes as $route => $action) {
				if (is_numeric($action))
					$action = $this->arg[0];

				preg_match_all("/\(([^\)]+)\)/", $route, $matches);

				if ($route != "/")
					$route = trim($route, "/");

				$escape = preg_quote($route, "/");
				$to_regexp = preg_replace("/\\\\\(([^\)]+)\\\\\)/", "([^\/]+)", $escape);

				if ($route == "/")
					$to_regexp = "\$";

				if (preg_match("/^\/{$to_regexp}/", $this->request, $url_matches)) {
					array_shift($url_matches);

					if (isset($matches[1]))
						foreach ($matches[1] as $index => $parameter)
							$_GET[$parameter] = $url_matches[$index];

					$params = explode(";", $action);
					$action = $params[0];

					array_shift($params);
					foreach ($params as $param) {
						$split = explode("=", $param);
						$_GET[$split[0]] = $split[1];
					}

					$this->try[] = $action;
				}
			}
		}

		/**
		 * Function: check_viewing_page
		 * Check to see if we're viewing a page, and if it is, handle it.
		 */
		public function check_viewing_page() {
			if (empty($this->arg[0]))
				return;

			$this->try["page"] = array($this->arg);
		}

		/**
		 * Function: check_viewing_post
		 * Check to see if we're viewing a post, and if it is, handle it.
		 *
		 * Parameters:
		 *     $url - If this argument is passed, it will attempt to grab a post from a given URL.
		 *            If a post is found by that URL, it will be returned.
		 */
		public function check_viewing_post($url = false) {
			$config = Config::current();

			if (!$url and !empty($this->action))
				return;

			if (!$url and count($this->arg) == 1 and method_exists(MainController::current(), $this->arg[0]))
				return $this->action = $this->arg[0];

			$post_url = $config->post_url;

			$request = ($url ? $url : $this->request);
			foreach (explode("/", $post_url) as $path)
				foreach (preg_split("/\(([^\)]+)\)/", $path) as $leftover) {
					$request  = preg_replace("/".preg_quote($leftover)."/", "", $request, 1);
					$post_url = preg_replace("/".preg_quote($leftover)."/", "", $post_url, 1);
				}

			$args = explode("/", trim($request, "/"));

			$post_url = $this->key_regexp(rtrim($post_url, "/"));
			$post_url_attrs = array();
			preg_match_all("/\(([^\/]+)\)/", $config->post_url, $parameters);
			if (preg_match("/".$post_url."/", rtrim($request, "/"), $matches)) {
				array_shift($matches);

				foreach ($parameters[0] as $index => $parameter)
					if ($parameter[0] == "(")
						$post_url_attrs[rtrim(ltrim($parameter, "("), ")")] = urldecode($args[$index]);

				$this->try["view"] = array($post_url_attrs);
			}
		}

		/**
		 * Function: url
		 * Attempts to change the specified clean URL to a dirty URL if clean URLs is disabled.
		 * Use this for linking to things. The applicable URL conversions are passed through the
		 * parse_urls trigger.
		 *
		 * Parameters:
		 *     $url - The clean URL.
		 *
		 * Returns:
		 *     Clean URL - if $config->clean_urls is set to *true*.
		 *     Dirty URL - if $config->clean_urls is set to *false*.
		 */
		public function url($url, $use_chyrp_url = false) {
			$config = Config::current();

			if ($url[0] == "/")
				return (ADMIN or $use_chyrp_url) ?
				       Config::current()->chyrp_url.$url :
				       Config::current()->url.$url ;

			if ($config->clean_urls) { # If their post URL doesn't have a trailing slash, remove it from these as well.
				if (substr($url, 0, 5) == "page/") # Different URL for viewing a page
					$url = substr($url, 5);

				return (substr($config->post_url, -1) == "/" or $url == "search/") ?
				       $config->url."/".$url :
				       $config->url."/".rtrim($url, "/") ;
			}

			$urls = $this->urls;
			Trigger::current()->filter($urls, "parse_urls");

			foreach (array_diff_assoc($urls, $this->urls) as $key => $value)
				$urls[substr($key, 0, -1)."feed\//"] = "/".$value."&amp;feed";

			$urls["/\/(.*?)\/$/"] = "/?action=$1";

			return $config->url.preg_replace(
			       array_keys($urls),
			       array_values($urls),
			       "/".$url, 1);
		}

		/**
		 * Function: key_regexp
		 * Converts the values in $config->post_url to regular expressions.
		 *
		 * Parameters:
		 *     $key - Input URL with the keys from <Routes->$code>.
		 *
		 * Returns:
		 *     $key values replaced with their regular expressions from <Routes->$code>.
		 */
		private function key_regexp($key) {
			Trigger::current()->filter($this->code, "url_code");
			return str_replace(array_keys($this->code), array_values($this->code), str_replace("/", "\\/", $key));
		}

		/**
		 * Function: add
		 * Adds a route to Chyrp. Only needed for actions that have more than one parameter.
		 * For example, for /tags/ you won't need to do this, but you will for /tag/tag-name/.
		 *
		 * Parameters:
		 *     $path - The path to add. Wrap variables with parentheses, e.g. "tag/(name)/".
		 *     $action - The action the path points to.
		 *
		 * See Also:
		 *     <remove_route>
		 */
		public function add($path, $action) {
			$config = Config::current();

			$new_routes = $config->routes;
			$new_routes[$path] = $action;

			$config->set("routes", $new_routes);
		}

		/**
		 * Function: remove_route
		 * Removes a route from the install's .htaccess file.
		 *
		 * Parameters:
		 *     $path - The path to remove. Same as <add>.
		 *
		 * See Also:
		 *     <add_route>
		 */
		public function remove($path) {
			$config = Config::current();

			unset($config->routes[$path]);

			$config->set("routes", $config->routes);
		}

		/**
		 * Function: current
		 * Returns a singleton reference to the current class.
		 */
		public static function & current($controller = null) {
			static $instance = null;
			return $instance = (empty($instance)) ? new self($controller) : $instance ;
		}
	}
