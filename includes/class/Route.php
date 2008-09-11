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

		# Boolean: $ajax
		# Shortcut to the AJAX constant (useful for Twig).
		public $ajax = AJAX;

		# Array: $post_url_attrs
		# Contains an associative array of URL key to value arguments if we're viewing a post.
		public $post_url_attrs = array();

		/**
		 * Function: __construct
		 * Parse the URL to determine what to do.
		 */
		private function __construct() {
			$this->determine_action();

			# Check if we are viewing a custom route, and set the action/GET parameters accordingly.
			$this->check_custom_routes();

			# If the post viewing URL is the same as the page viewing URL, check for viewing a page first.
			if (preg_match("/^\((clean|url)\)\/?$/", Config::current()->post_url)) {
				$this->check_viewing_page();
				$this->check_viewing_post();
			} else {
				$this->check_viewing_post();
				$this->check_viewing_page();
			}
		}

		/**
		 * Function: determine_action
		 * Determine the action of the current URL.
		 */
		public function determine_action() {
			global $page;
			$config = Config::current();

			$this->action =& $_GET['action'];

			# Parse the current URL and extract information.
			$parse = parse_url($config->url);
			fallback($parse["path"]);

			$this->safe_path = str_replace("/", "\\/", $parse["path"]);
			$this->request = preg_replace("/".$this->safe_path."/", "", $_SERVER['REQUEST_URI'], 1);
			$this->arg = explode("/", trim($this->request, "/"));

			if (empty($this->arg[0])) # If they're just at /, don't bother with all this.
				return $this->action = "index";

			# Feed
			if (preg_match("/\/feed\/?$/", $this->request)) {
				$_GET['feed'] = "true";

				if ($this->arg[0] == "feed") # Don't set $this->action to "feed" (bottom of this function).
					return $this->action = "index";
			}

			# Feed with a title parameter
			if (preg_match("/\/feed\/([^\/]+)\/?$/", $this->request, $title)) {
				$_GET['feed'] = "true";
				$_GET['title'] = $title[1];

				if ($this->arg[0] == "feed") # Don't set $this->action to "feed" (bottom of this function).
					return $this->action = "index";
			}

			# Viewing a post by its ID
			if ($this->arg[0] == "id") {
				$_GET['id'] = $this->arg[1];
				return $this->action = "id";
			}

			# Paginator
			if (preg_match_all("/\/((([^_\/]+)_)?page)\/([0-9]+)/", $this->request, $page_matches)) {
				foreach ($page_matches[1] as $key => $page_var) {
					$index = array_search($page_var, $this->arg);
					$_GET[$page_var] = $this->arg[$index + 1];
				}

				if ($index == 0) # Don't set $this->action to "page" (bottom of this function).
					return $this->action = (isset($config->routes["/"])) ? $config->routes["/"] : "index" ;
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
				if (isset($this->arg[1]) and strpos($this->request, "?action=search&query="))
					redirect(str_replace("?action=search&query=", "", $this->request));

				if (isset($this->arg[1]))
					$_GET['query'] = $this->arg[1];

				return $this->action = "search";
			}

			# Bookmarklet
			if ($this->arg[0] == "bookmarklet") {
				$_GET['status'] = $this->arg[1];
				return $this->action = "bookmarklet";
			}
		}

		/**
		 * Function: check_custom_routes
		 * Check to see if we're viewing a custom route, and if it is, parse it.
		 */
		public function check_custom_routes() {
			if (!INDEX)
				return;

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

					$its_fine = true;

					# This trigger filter must stay low-level and not call anything that
					# ends up calling Route::current() in any way, to prevent recursion.
					Trigger::current()->filter($its_fine, "check_route_".$action, $action);

					if ($its_fine)
						return $this->action = $action;
				}
			}
		}

		/**
		 * Function: check_viewing_page
		 * Check to see if we're viewing a page, and if it is, handle it.
		 */
		public function check_viewing_page() {
			if (!INDEX)
				return;

			global $page;

			$config = Config::current();

			if (!empty($this->action))
				return;

			if (count($this->arg) == 1 and method_exists(MainController::current(), $this->arg[0]))
				return $this->action = $this->arg[0];

			$valids = Page::find(array("where" => "url IN ('".implode("', '", $this->arg)."')"));

			if (count($valids) == count($this->arg)) {
				foreach ($valids as $page)
					if ($page->url == end($this->arg))
						return list($_GET['url'], $this->action) = array($page->url, "page");
			} elseif (!preg_match("/^\((clean|url)\)\/?$/", $config->post_url)) # This is the last route parse.
				return $this->action = fallback($this->arg[0], "index");
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
			if (!$url and !INDEX)
				return;

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
			preg_match_all("/\(([^\/]+)\)/", $config->post_url, $parameters);
			if (preg_match("/".$post_url."/", rtrim($request, "/"), $matches)) {
				array_shift($matches);

				foreach ($parameters[0] as $index => $parameter)
					if ($parameter[0] == "(")
						$this->post_url_attrs[rtrim(ltrim($parameter, "("), ")")] = urldecode($args[$index]);

				$check = Post::from_url($this->post_url_attrs, array("filter" => false));

				if (!$check->no_results)
					return ($url ? $check : $this->action = "view");
				elseif ($url)
					return false;
			}

			if (preg_match("/^\((clean|url)\)\/?$/", $config->post_url)) # This is the last route parse.
				return $this->action = fallback($this->arg[0], "index");
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
		public static function & current() {
			static $instance = null;
			return $instance = (empty($instance)) ? new self() : $instance ;
		}
	}
