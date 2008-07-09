<?php
	/**
	 * Class: Theme
	 * Various helper functions for the theming engine.
	 */
	class Theme {
		/**
		 * String: $title
		 * The title for the current page.
		 */
		public $title = "";
		private $twig;
		private $directory;
		private $pages = array();
		private $context = array();

		/**
		 * Function: __construct
		 * Loads the Twig parser into <Theme>.
		 */
		private function __construct() {
			$visitor = Visitor::current();
			$config = Config::current();

			$this->theme = (isset($_GET['action']) and $_GET['action'] == "theme_preview" and !empty($_GET['theme']) and $visitor->group()->can("change_settings")) ?
			               $_GET['theme'] :
			               $config->theme;
			$this->directory = THEMES_DIR."/".$this->theme."/";

			$this->twig = new Twig_Loader($this->directory, ((is_writable(INCLUDES_DIR."/caches") and !DEBUG) ? INCLUDES_DIR."/caches" : null));

			$this->url = $config->chyrp_url."/themes/".$this->theme;
		}

		/**
		 * Function: pages_list
		 * Returns a simple array of list items to be used by the theme to generate a recursive array of pages.
		 */
		public function pages_list() {
			$this->pages = Page::find(array("where" => "`show_in_list` = 1", "order" => "`list_order` asc"));

			foreach ($this->pages as $page)
				$this->end_tags_for[$page->id] = $this->children[$page->id] = array();

			foreach ($this->pages as $page)
				if ($page->parent_id != 0)
					$this->children[$page->parent_id][] = $page;

			foreach ($this->pages as $page)
				if ($page->parent_id == 0)
					$this->recurse_pages($page);

			$array = array();

			foreach ($this->pages_flat as $page) {
				$array[$page->id] = array();
				$my_array =& $array[$page->id];

				$my_array["has_children"] = !empty($this->children[$page->id]);

				if ($my_array["has_children"])
					$this->end_tags_for[$this->get_last_linear_child($page->id)][] = array("</ul>", "</li>");

				$my_array["end_tags"] =& $this->end_tags_for[$page->id];
				$my_array["page"] = $page;
			}

			return $array;
		}

		/**
		 * Function: get_last_linear_child
		 * Helper function to <Theme.pages_list>
		 */
		public function get_last_linear_child($page, $origin = null) {
			fallback($origin, $page);

			$this->linear_children[$origin] = $page;
			foreach ($this->children[$page] as $child)
				$this->get_last_linear_child($child->id, $origin);

			return $this->linear_children[$origin];
		}

		/**
		 * Function: recurse_pages
		 * Helper function to <Theme.pages_list>
		 */
		public function recurse_pages($page) {
			$this->pages_flat[] = $page;

			foreach ($this->children[$page->id] as $child)
				$this->recurse_pages($child);
		}

		/**
		 * Function: archive_list
		 * Generates an array of all of the archives, by month.
		 *
		 * Parameters:
		 *     $limit - Amount of months to list
		 *     $order_by - What to sort it by
		 *     $order - "asc" or "desc"
		 *
		 * Returns:
		 *     $archives - The array. Each entry as "month", "year", and "url" values, stored as an array.
		 */
		public function archives_list($limit = 0, $order_by = "created_at", $order = "desc") {
			$sql = SQL::current();
			$dates = $sql->select("posts",
			                       "DISTINCT YEAR(`created_at`) AS `year`, MONTH(`created_at`) AS `month`, `created_at`, COUNT(`id`) AS `posts`",
			                       "`status` = 'public'",
			                       "`__posts`.`{$order_by}` ".strtoupper($order),
			                       array(),
			                       ($limit == 0) ? null : $limit,
			                       null,
			                       "YEAR(`created_at`), MONTH(`created_at`)");

			$archives = array();
			while ($date = $dates->fetchObject())
				$archives[] = array("month" => $date->month,
				                    "year"  => $date->year,
				                    "when"  => $date->created_at,
				                    "url"   => url("archive/".when("Y/m/", $date->created_at)),
				                    "count" => $date->posts);

			return $archives;
		}

		/**
		 * Function: file_exists
		 * Returns whether the specified Twig file exists or not.
		 *
		 * Parameters:
		 *     $file - The file's name
		 */
		public function file_exists($file) {
			return file_exists($this->directory.$file.".twig");
		}

		/**
		 * Function: stylesheets
		 * Outputs the default stylesheet links.
		 */
		public function stylesheets() {
			$visitor = Visitor::current();
			$config = Config::current();
			$trigger = Trigger::current();

			$stylesheets = "";
			if (file_exists(THEMES_DIR."/".$this->theme."/stylesheets/") or file_exists(THEMES_DIR."/".$this->theme."/css/")) {
				$count = 1;
				$glob = glob(THEMES_DIR."/".$this->theme."/{stylesheets,css}/*.{css,css.php}", GLOB_BRACE);

				foreach($glob as $file) {
					$path = preg_replace("/(.+)\/themes\/(.+)/", "/themes/\\2", $file);
					$file = basename($file);

					if ($file == "ie.css")
					    $stylesheets.= "<!--[if IE]>";
					if (preg_match("/^ie([0-9\.]+)\.css/", $file, $matches))
					    $stylesheets.= "<!--[if IE ".$matches[1]."]>";
					elseif (preg_match("/(lt|gt)ie([0-9\.]+)\.css/", $file, $matches))
					    $stylesheets.= "<!--[if ".$matches[1]." IE ".$matches[2]."]>";

					$stylesheets.= '<link rel="stylesheet" href="'.$config->chyrp_url.$path.'" type="text/css" media="'.($file == "print.css" ? "print" : "screen").'" charset="utf-8" />';

					if ($file == "ie.css" or preg_match("/(lt|gt)?ie([0-9\.]+)\.css/", $file))
						$stylesheets.= "<![endif]-->";

					if ($count != count($glob))
						$stylesheets.= "\n\t\t";

					$count++;
				}
			} else
				$stylesheets = '<link rel="stylesheet" href="'.$config->chyrp_url.'/themes/'.$this->theme.'/style.css" type="text/css" media="screen" charset="utf-8" />';

			return $stylesheets;
		}

		/**
		 * Function: javascripts
		 * Outputs the default JavaScript script references.
		 */
		public function javascripts() {
			global $posts;

			$route = Route::current();

			$args = "";
			$i = 0;
			foreach ($_GET as $val)
				if (!empty($val) and $val != $route->action)
					$args.= "&amp;arg".++$i."=".urlencode($val);

			# if (isset($posts))
			#     $args.= "&amp;next_post=".$posts->next()->paginated[0]->id;

			$config = Config::current();
			$trigger = Trigger::current();

			$javascripts = array($config->chyrp_url."/includes/lib/gz.php?file=jquery.js",
			                     $config->chyrp_url."/includes/lib/gz.php?file=plugins.js",
			                     $config->chyrp_url.'/includes/javascript.php?action='.$route->action.$args);
			Trigger::current()->filter($javascripts, "scripts");

			$javascripts = '<script src="'.
			               implode('" type="text/javascript" charset="utf-8"></script>'."\n\t\t".'<script src="', $javascripts).
			               '" type="text/javascript" charset="utf-8"></script>';

			if (file_exists(THEMES_DIR."/".$this->theme."/javascripts/") or file_exists(THEMES_DIR."/".$this->theme."/js/")) {
				foreach(glob(THEMES_DIR."/".$this->theme."/{javascripts,js}/*.js", GLOB_BRACE) as $file)
					$javascripts.= "\n\t\t".'<script src="'.$config->chyrp_url.'/includes/lib/gz.php?file='.preg_replace("/(.+)\/themes\/(.+)/", "/themes/\\2", $file).'" type="text/javascript" charset="utf-8"></script>';

				foreach(glob(THEMES_DIR."/".$this->theme."/{javascripts,js}/*.js.php", GLOB_BRACE) as $file)
					$javascripts.= "\n\t\t".'<script src="'.$config->chyrp_url.preg_replace("/(.+)\/themes\/(.+)/", "/themes/\\2", $file).'" type="text/javascript" charset="utf-8"></script>';
			}

			return $javascripts;
		}

		/**
		 * Function: feeds
		 * Outputs the Feed references.
		 */
		public function feeds() {
			global $pluralizations;

			$config = Config::current();
			$request = ($config->clean_urls) ? rtrim(Route::current()->request, "/") : htmlspecialchars($_SERVER['REQUEST_URI']) ;
			$append = ($config->clean_urls) ?
			              "/feed" :
			              ((count($_GET) == 1 and Route::current()->action == "index") ?
			                "/?feed" :
			                  "&amp;feed") ;
			$append.= ($config->clean_urls) ?
			             "/".urlencode($this->title) :
			             '&amp;title='.urlencode($this->title) ;

			$route = Route::current();
			$feeds = '<link rel="alternate" type="application/atom+xml" title="'.$config->name.' Feed" href="'.fallback($config->feed_url, url("feed/"), true).'" />'."\n";

			foreach ($pluralizations["feathers"] as $normal => $plural) {
				$feeds.= "\t\t".'<link rel="alternate" type="application/atom+xml" title="'.ucfirst($plural).' Feed" href="'.url($plural."/feed/".urlencode(ucfirst($plural))."/").'" />'."\n";
			}

			$feeds.= "\t\t".'<link rel="alternate" type="application/atom+xml" title="Current Page (if applicable)" href="'.$config->url.$request.$append.'" />';
			return $feeds;
		}

		public function prepare($context) {
			global $modules, $feathers;

			$this->context = array_merge($context, $this->context);

			$trigger = Trigger::current();
			$visitor = Visitor::current();
			$config = Config::current();

			$this->context["theme"]        = $this;
			$this->context["flash"]        = Flash::current();
			$this->context["trigger"]      = $trigger;
			$this->context["modules"]      = $modules;
			$this->context["feathers"]     = $feathers;
			$this->context["title"]        = $this->title;
			$this->context["site"]         = $config;
			$this->context["visitor"]      = $visitor;
			$this->context["route"]        = Route::current();
			$this->context["hide_admin"]   = isset($_COOKIE["chyrp_hide_admin"]);
			$this->context["version"]      = CHYRP_VERSION;
			$this->context["now"]          = now();
			$this->context["POST"]         = $_POST;
			$this->context["GET"]          = $_GET;

			$this->context["visitor"]->logged_in = logged_in();
			$trigger->filter($this->context, array("twig_global_context", "twig_context_".str_replace("/", "_", $this->file)));

			$this->context["enabled_modules"] = array();
			foreach ($config->enabled_modules as $module)
				$this->context["enabled_modules"][$module] = true;

			$this->context["enabled_feathers"] = array();
			foreach ($config->enabled_feathers as $feather)
				$this->context["enabled_feathers"][$feather] = true;

			$this->context["sql_debug"] = SQL::current()->debug;
		}

		public function sql_queries() {
			return SQL::current()->queries;
		}

		public function load_time() {
			return timer_stop();
		}

		/**
		 * Function: load
		 * Loads a theme's file and extracts the passed array into the scope.
		 */
		public function load($file, $context = array()) {
			if (is_array($file))
				for ($i = 0; $i < count($file); $i++) {
					$check = ($file[$i][0] == '/' or preg_match("/[a-zA-Z]:\\\/", $file[$i])) ? $file[$i] : $this->directory.$file[$i] ;
					if (file_exists($check.".twig") or ($i + 1) == count($file))
						return $this->load($file[$i], $context);
				}

			$file = ($file[0] == "/" or preg_match("/[a-zA-Z]:\\\/", $file)) ? $file : $this->directory.$file ;
			if (!file_exists($file.".twig"))
				error(__("Template Missing"), _f("Couldn't load template: <code>%s</code>", array($file.".twig")));

			$this->file = $file;
			$this->prepare($context);

			$template = $this->twig->getTemplate($file.".twig");
			return $template->display($this->context);
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
