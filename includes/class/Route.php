<?php
	/**
	 * Class: Route
	 * Holds information for URLs, redirecting, etc.
	 */
	class Route {
		/**
		 * Array: $code
		 * The translation array of the post URL setting to regular expressions.
		 * Passed through the route_code filter.
		 */
		public $code = array('(year)'    => '([0-9]{4})',
		                     '(month)'   => '([0-9]{1,2})',
		                     '(day)'     => '([0-9]{1,2})',
		                     '(hour)'    => '([0-9]{1,2})',
		                     '(minute)'  => '([0-9]{1,2})',
		                     '(second)'  => '([0-9]{1,2})',
		                     '(id)'      => '([0-9]+)',
		                     '(author)'  => '([^\/]+)',
		                     '(clean)'   => '([^\/]+)',
		                     '(url)'     => '([^\/]+)',
		                     '(feather)' => '([^\/]+)');
		
		/**
		 * Function: __construct
		 * Filters the key => val code so that modules may extend it.
		 */
		private function __construct() {
			$trigger = Trigger::current();
			$this->code = $trigger->filter("route_code", $this->code, true);
		}
		
		/**
		 * Function: url
		 * Attempts to change the specified clean URL to a dirty URL if clean URLs is disabled.
		 * Use this for linking to things. The applicable URL conversions are passed through the
		 * parse_urls trigger.
		 * 
		 * Parameters:
		 * 	$clean_url - The clean URL.
		 * 
		 * Returns:
		 * 	Clean URL - if $config->clean_urls is set to *true*.
		 * 	Dirty URL - if $config->clean_urls is set to *false*.
		 */
		public function url($clean_url) {
			$config = Config::current();
			if ($config->clean_urls) { # If their post URL doesn't have a trailing slash, remove it from these as well.
				if (substr($clean_url, 0, 5) == "page/") # Different URL for viewing a page
					$clean_url = substr($clean_url, 5);
				
				return (substr($config->post_url, -1) == "/" or $clean_url == "search/") ?
				       $config->url."/".$clean_url :
				       $config->url."/".rtrim($clean_url, "/") ;
			}
			
			$clean_url = "/".$clean_url;
			
			$urls = array(
				"/\/id\/([0-9]+)\//" => "?action=view&amp;id=$1",
				"/\/page\/([^\/]+)\//" => "?action=page&amp;url=$1",
				"/\/search\//" => "?action=search",
				"/\/search\/([^\/]+)\//" => "?action=search&amp;query=$1",
				"/\/archive\/([^\/]+)\/([^\/]+)\//" => "?action=archive&amp;year=$1&amp;month=$2",
				"/\/bookmarklet\/([^\/]+)\//" => "?action=bookmarklet&amp;status=$1",
				"/\/theme_preview\/([^\/]+)\//" => "?action=theme_preview&amp;theme=$1",
				"/\/([^\/]+)\/feed\//" => "?action=$1&amp;feed"
			);
			
			$trigger = Trigger::current();
			$urls = $trigger->filter("parse_urls", $urls);
			$urls["/\/(.*?)\/$/"] = "?action=$1";
			$converted = preg_replace(array_keys($urls), array_values($urls), $clean_url);
			
			return $config->url."/".$converted;
		}
		
		/**
		 * Function: redirect
		 * Redirects to the given URL and exits immediately.
		 */
		public function redirect($url) {
			if ($url[0] == '/') {
				# handle URIs without domain
				$config = Config::current();
				$url = $config->url.$url;
			}
			header("Location: ".html_entity_decode($url));
			exit;
		}
		
		/**
		 * Function: key_regexp
		 * Converts the values in $config->post_url to regular expressions.
		 * 
		 * Parameters:
		 * 	$key - Input URL with the keys from <Routes->$code>.
		 * 
		 * Returns:
		 * 	$regexp - $key values replaced with their regular expressions from <Routes->$code>.
		 */
		private function key_regexp($key) {
			$trigger = Trigger::current();
			$this->code = $trigger->filter("url_code", $this->code);
			$replace = str_replace("/", "\\/", $key);
			$replace = str_replace(array_keys($this->code), array_values($this->code), $replace);
			
			return $replace;
		}
		
		/**
		 * Function: determine_action
		 * This meaty function determines what exactly to do with the URL.
		 */
		public function determine_action() {
			global $plural_feathers, $request, $grab_page;
			$config = Config::current();
			if (ADMIN or JAVASCRIPT or AJAX or XML_RPC or !$config->clean_urls) return;

			# Parse the current URL and extract information.
			$parse = parse_url($config->url);
			fallback($parse["path"]);
			
			$safe_path = str_replace("/", "\\/", $parse["path"]);
			$request = preg_replace("/".$safe_path."/", "", $_SERVER['REQUEST_URI'], 1);
			$arg = explode("/", trim($request, "/"));
			
			# Viewing a post by its ID
			if ($arg[0] == "id") {
				$_GET['id'] = $arg[1];
				return $_GET['action'] = "id";
			}
			
			# Pagination
			if (preg_match_all("/\/((([^_\/]+)_)?page)\/([0-9]+)/", $request, $page_matches)) {
				foreach ($page_matches[1] as $key => $page_var) {
					$index = array_search($page_var, $arg);
					$_GET[$page_var] = $arg[$index + 1];
				}
				
				if ($index == 0) # Don't set the $_GET['action'] to "page" (bottom of this function).
					return $_GET['action'] = "index";
			}
			
			# Feed
			if (preg_match("/\/feed\/?$/", $request)) {
				$_GET['feed'] = "true";
				
				if ($arg[0] == "feed") # Don't set the $_GET['action'] to "feed" (bottom of this function).
					return $_GET['action'] = "index";
			}
			
			# Archive
			if ($arg[0] == "archive") {
				if (isset($arg[1]))
					$_GET['year'] = $arg[1];
				if (isset($arg[2]))
					$_GET['month'] = $arg[2];
				
				return $_GET['action'] = "archive";
			}
			
			# Searching
			if ($arg[0] == "search") {
				if (isset($arg[1]) and strpos($request, "?action=search&query="))
					$this->redirect(str_replace("?action=search&query=", "", $request));
				
				if (isset($arg[1]))
					$_GET['query'] = $arg[1];
				
				return $_GET['action'] = "search";
			}
			
			# Theme Previewing
			if ($arg[0] == "theme_preview" and !empty($arg[1])) {
				$_GET['theme'] = $arg[1];
				return $_GET['action'] = "theme_preview";
			}
			
			# Bookmarklet
			if ($arg[0] == "bookmarklet") {
				$_GET['status'] = $arg[1];
				return $_GET['action'] = "bookmarklet";
			}
			
			# Viewing Feathers
			if (in_array($arg[0], array_keys($plural_feathers)) and (empty($arg[1]) or $arg[1] == "feed" or $arg[1] == "page"))
				return $_GET['action'] = $arg[0];
			
			# Custom pages added by Modules, Feathers, Themes, etc.
			foreach ($config->routes as $route)
				if (preg_match_all("/\(([^\)]+)\)/", $route, $matches)) {
					if (substr($config->post_url, -1) != "/")
						$route = rtrim($route, "/");
					
					$fix_slashes = str_replace("/", "\\/", $route);
					$to_regexp = preg_replace("/\(([^\)]+)\)/", "([^\/]+)", $fix_slashes);
					
					if (preg_match("/".$to_regexp."/", $request, $url_matches)) {
						array_shift($url_matches);
						
						foreach ($matches[1] as $index => $parameter)
							$_GET[$parameter] = $url_matches[$index];
						
						return $_GET['action'] = $arg[0];
					}
				}
			
			# Post viewing
			$post_url = $this->key_regexp($config->post_url);
			preg_match_all("/([^\/]+)(\/|$)/", $config->post_url, $parameters);
			if (preg_match("/".$post_url."/", $request, $matches)) {
				array_shift($matches);
				
				foreach ($parameters[1] as $index => $parameter) {
					if (substr($parameters[1][$index], 0, 1) != "(") continue;
					
					$no_parentheses = rtrim(ltrim($parameter, "("), ")");
					$_GET[$no_parentheses] = urldecode($arg[$index]);
				}

				return $_GET['action'] = "view";
			}
			
			# Page viewing
			$page = new Page(null, array("where" => "`url` = :url", "params" => array(":url" => $arg[0])));
			if (!$page->no_results) {
				$_GET['url'] = $arg[0];
				return $_GET['action'] = "page";
			}

			return $_GET['action'] = (empty($arg[0])) ? "index" : $arg[0] ;
		}
		
		/**
		 * Function: add
		 * Adds a route to Chyrp. Only needed for actions that have more than one parameter.
		 * For example, for /tags/ you won't need to do this, but you will for /tag/tag-name/.
		 * 
		 * Parameters:
		 * 	$path - The path to add. Wrap variables with parentheses, e.g. "/tag/(name)/".
		 * 
		 * See Also:
		 * 	<remove_route>
		 */
		public function add($path) {
			$config = Config::current();
			$new_routes = $config->routes;
			$new_routes[] = $path;
			$config->set("routes", $new_routes);
		}
		
		/**
		 * Function: remove_route
		 * Removes a route from the install's .htaccess file.
		 * 
		 * Parameters:
		 * 	$path - The path to remove. Same as <add>.
		 * 
		 * See Also:
		 * 	<add_route>
		 */
		public function remove($path) {
			$new_routes = array();
			$config = Config::current();
			
			foreach ($config->routes as $route) {
				if ($route == $path) continue;
				$new_routes[] = $route;
			}
			
			$config->set("routes", $new_routes);
		}
		
		/**
		 * Function: current
		 * Returns a singleton reference to the current connection.
		 */
		public static function & current() {
			static $instance = null;
			if (empty($instance))
				$instance = new self();
			return $instance;
		}
	}
	$route = Route::current();
