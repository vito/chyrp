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
		private $tabs = "\t";
		private $page_list = "";

		/**
		 * Function: __construct
		 * Loads the Twig parser into <Theme>.
		 */
		public function __construct() {
			$visitor = Visitor::current();
			$this->directory = (isset($_GET['action']) and $_GET['action'] == "theme_preview" and !empty($_GET['theme']) and $visitor->group()->can("change_settings")) ?
			                   THEMES_DIR."/".$_GET['theme']."/" :
			                   THEME_DIR."/" ;

			$this->twig = new Twig_Loader($this->directory, ((is_writable(MAIN_DIR."/includes/twig_cache") and !DEBUG) ? MAIN_DIR."/includes/twig_cache" : null));
		}

		/**
		 * Function: list_pages
		 * Generates a recursive list of pages and their children. Outputs it as a <ul> list.
		 *
		 * Parameters:
		 *     $home_link - Whether or not to show the "Home" link
		 *     $home_text - Text for the "Home" link
		 */
		public function list_pages($home_link = true, $home_text = null, $main_class = "page_list", $list_class = "page_list_item", $show_order_fields = false) {
			global $action;
			fallback($home_text, __("Home"));

			$this->page_list.= '<ul class="'.$main_class.'">'."\n";

			$this->pages = Page::find(array("where" => "`show_in_list` = 1", "order" => "`list_order` asc", "pagination" => false));

			if ($home_link)
				$this->page_list.= $this->tabs.'<li class="'.$list_class.($action == "index" ? " selected" : "").'">'."\n".$this->tabs."\t".'<a href="'.Config::current()->url.'">'.$home_text.'</a>'."\n".$this->tabs.'</li>'."\n";

			foreach ($this->pages as $page)
				if ($page->parent_id == 0)
					$this->recurse_pages($page, $main_class, $list_class, $show_order_fields);

			$this->page_list = Trigger::current()->filter('list_pages', $this->page_list);

			$this->page_list.= "</ul>\n";
			return $this->page_list;
		}

		/**
		 * Function: recurse_pages
		 * Performs all of the recursion for generating the page lists. Used by <list_pages>.
		 *
		 * Parameters:
		 *     $id - The page ID to start at.
		 *
		 * See Also:
		 *     <list_pages>
		 */
		public function recurse_pages($page, $main_class = "page_list", $list_class = "page_list_item", $show_order_fields = false) {
			global $pages, $action;

			$selected = ($action == 'page' and $_GET['url'] == $page->url) ? ' selected' : '';
			$this->page_list.= sprintf($this->tabs.'<li class="%s" id="page_list_%s">'."\n".$this->tabs."\t".'<a href="%s">%s</a>', $list_class.$selected, $page->id, $page->url(), $page->title);

			if ($show_order_fields)
				$this->page_list.= ' <input type="text" size="2" name="list_order['.$page->id.']" value="'.$page->list_order.'" />';

			$count = 1;
			$children = array();
			foreach ($this->pages as $child)
				if ($child->parent_id == $page->id)
					$children[] = $child;

			foreach ($children as $child) {
				for ($i = 0; $i < $count; $i++)
					$this->tabs.= "\t";

				if ($count == 1)
					$this->page_list.= "\n".$this->tabs.'<ul class="'.$main_class.'">'."\n";
				$this->tabs .= "\t";
				$this->recurse_pages($child, $main_class, $list_class, $show_order_fields);

				if ($count == count($children))
					$this->page_list.= "\t".$this->tabs."</ul>\n";

				$count++;
			}

			if (count($children) == 0)
				$this->page_list.= "\n";

			$this->tabs = substr($this->tabs, 0, -2);

			$this->page_list.= ((isset($this->last_recursion) and $this->last_recursion) ? "\t" : "\t\t").$this->tabs."</li>\n";

			if (strlen($this->tabs) == 1)
				$this->last_recursion.= !isset($this->last_recursion);
		}

		/**
		 * Function: list_archives
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
		public function list_archives($limit = 0, $order_by = "created_at", $order = "desc") {
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
				                    "url"   => Route::current()->url("archive/".when("Y/m/", $date->created_at)),
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
			$theme = (isset($_GET['action']) and $_GET['action'] == "theme_preview" and !empty($_GET['theme']) and $visitor->group()->can("change_settings")) ?
			         $_GET['theme'] :
			         $config->theme ;

			$stylesheets = "";
			if (file_exists(THEME_DIR."/stylesheets/")) {
				$count = 1;
				$glob = glob(THEME_DIR."/stylesheets/*.css");
				foreach($glob as $file) {
					$file = basename($file);
					$stylesheets.= '<link rel="stylesheet" href="'.$config->chyrp_url.'/themes/'.$theme.'/stylesheets/'.$file.'" type="text/css" media="'.($file == "print.css" ? "print" : "screen").'" charset="utf-8" />'.(count($glob) == $count ? "" : "\n\t\t");
					$count++;
				}
			} else
				$stylesheets = '<link rel="stylesheet" href="'.$config->chyrp_url.'/themes/'.$theme.'/style.css" type="text/css" media="screen" charset="utf-8" />';

			return $stylesheets;
		}

		/**
		 * Function: javascripts
		 * Outputs the default JavaScript script references.
		 */
		public function javascripts() {
			global $paginate, $action;
			$target = $_GET;
			$args = "";
			$i = 1;
			foreach ($target as $val) {
				if (empty($val) or $val == $action) continue;
				$args.= "&amp;arg".$i."=".urlencode($val);
				$i++;
			}
			if (isset($paginate->page))
				$args.= "&amp;page=".$paginate->page;

			$config = Config::current();
			$trigger = Trigger::current();

			$javascripts = $trigger->filter("scripts", '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js" type="text/javascript" charset="utf-8"></script>'."\n\t\t".
			                                           '<script src="'.$config->chyrp_url.'/includes/lib/gz.php?file=plugins.js" type="text/javascript" charset="utf-8"></script>'."\n\t\t".
			                                           '<script src="'.$config->chyrp_url.'/includes/javascript.php?action='.$action.$args.'" type="text/javascript" charset="utf-8"></script>');

			return $javascripts;
		}

		/**
		 * Function: feeds
		 * Outputs the Feed references.
		 */
		public function feeds() {
			global $request, $pluralizations;

			$config = Config::current();
			$request = ($config->clean_urls) ? rtrim($request, "/") : htmlspecialchars($_SERVER['REQUEST_URI']) ;
			$append = ($config->clean_urls) ?
			              "/feed" :
			              ((count($_GET) == 1 and $_GET['action'] == "index") ?
			                "/?feed" :
			                  "&amp;feed") ;
			$append.= ($config->clean_urls) ?
			             "/".urlencode($this->title) :
			             '&amp;title='.urlencode($this->title) ;

			$route = Route::current();
			$feeds = '<link rel="alternate" type="application/atom+xml" title="'.$config->name.' Feed" href="'.$route->url("feed/").'" />'."\n";

			foreach ($pluralizations["feathers"] as $normal => $plural) {
				$feeds.= "\t\t".'<link rel="alternate" type="application/atom+xml" title="'.ucfirst($plural).' Feed" href="'.$route->url($plural."/feed/".urlencode(ucfirst($plural))."/").'" />'."\n";
			}

			$feeds.= "\t\t".'<link rel="alternate" type="application/atom+xml" title="Current Page (if applicable)" href="'.$config->url.$request.$append.'" />';
			return $feeds;
		}

		public function prepare($context) {
			global $action, $paginate;

			$this->context = array_merge($context, $this->context);

			$trigger = Trigger::current();
			$visitor = Visitor::current();
			$config = Config::current();

			$this->context["theme"]        = $this;
			$this->context["trigger"]      = $trigger;
			$this->context["title"]        = $this->title;
			$this->context["site"]         = $config;
			$this->context["feeds"]        = $this->feeds();
			$this->context["stylesheets"]  = $this->stylesheets();
			$this->context["javascripts"]  = $this->javascripts();
			$this->context["visitor"]      = $visitor;
			$this->context["archive_list"] = $this->list_archives();
			$this->context["page_list"]    = $this->list_pages();
			$this->context["theme"]        = array("url" => $config->chyrp_url."/themes/".$config->theme);
			$this->context["route"]        = array("action" => $action, "ajax" => AJAX);
			$this->context["hide_admin"]   = isset($_SESSION["chyrp_hide_admin"]);
			$this->context["pagination"]   = $paginate;
			$this->context["version"]      = CHYRP_VERSION;
			$this->context["POST"]         = $_POST;
			$this->context["GET"]          = $_GET;

			$this->context["visitor"]->logged_in = logged_in();
			$this->context = $trigger->filter("twig_global_context", $this->context);
			$this->context = $trigger->filter(str_replace("/", "_", $this->file), $this->context);

			$this->context["enabled_modules"] = array();
			foreach ($config->enabled_modules as $module)
				$this->context["enabled_modules"][$module] = true;

			$this->context["enabled_feathers"] = array();
			foreach ($config->enabled_feathers as $feather)
				$this->context["enabled_feathers"][$feather] = true;

			$this->context["stats"] = array("load" => timer_stop(), "queries" => SQL::current()->queries);
			$this->context["sql_debug"] = SQL::current()->debug;
		}

		/**
		 * Function: load
		 * Loads a theme's file and extracts the passed array into the scope.
		 */
		public function load($file, $context = array()) {
			global $action;

			if (is_array($file))
				for ($i = 0; $i < count($file); $i++)
					if (file_exists($this->directory.$file[$i].".twig") or ($i + 1) == count($file))
						return $this->load($file[$i], $context);

			if (!file_exists($this->directory.$file.".twig"))
				error(__("Template Missing"), _f("Couldn't load template:<br /><br />%s", array($file.".twig")));

			$this->file = $file;
			$this->prepare($context);

			$template = $this->twig->getTemplate($file.".twig");
			return $template->display($this->context);
		}
	}
	$theme = new Theme();
