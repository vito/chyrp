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

		/**
		 * Function: __construct
		 * Loads the Twig parser into <Theme>.
		 */
		public function __construct() {
			$visitor = Visitor::current();
			$this->directory = (isset($_GET['action']) and $_GET['action'] == "theme_preview" and !empty($_GET['theme']) and $visitor->group->can("change_settings")) ?
			                   THEMES_DIR."/".$_GET['theme']."/" :
			                   THEME_DIR."/" ;
			$this->twig = new Twig_Loader($this->directory, (is_writable(MAIN_DIR."/includes/twig_cache") ? MAIN_DIR."/includes/twig_cache" : null));
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
			$sql = SQL::current();
			$query = $sql->query("select * from `".$sql->prefix."pages` where `show_in_list` = 1 order by `list_order` asc");
			while ($row = $query->fetch()) {
				$this->pages[$row["id"]] = array("id" => $row["id"], "title" => $row["title"], "parent" => $row["parent_id"], "url" => $row["url"], "order" => $row["list_order"]);
			}

			echo '<ul class="'.$main_class.'">'."\n";

			$config = Config::current();
			if ($home_link) {
				$selected = ($action == 'index') ? ' selected' : '';
				echo '<li class="'.$list_class.$selected.'"><a href="'.$config->url.'">'.$home_text.'</a></li>'."\n";
			}

			foreach ($this->pages as $id => $values)
				if ($values["parent"] == 0)
					$this->recurse_pages($values["id"], $main_class, $list_class, $show_order_fields);

			echo "</ul>\n";
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
		public function recurse_pages($id, $main_class = "page_list", $list_class = "page_list_item", $show_order_fields = false) {
			global $pages, $action;
			$route = Route::current();

			$selected = ($action == 'page' and $_GET['url'] == $this->pages[$id]["url"]) ? ' selected' : '';
			echo '<li class="'.$list_class.$selected.'" id="page_list_'.$id.'"><a href="'.$route->url("page/".$this->pages[$id]["url"]."/").'">'.$this->pages[$id]["title"].'</a>';

			if ($show_order_fields)
				echo ' <input type="text" size="2" name="list_order['.$id.']" value="'.$this->pages[$id]["order"].'" />';

			$sql = SQL::current();
			$get_children = $sql->query("select * from `".$sql->prefix."pages` where `parent_id` = :parent and `show_in_list` = 1 order by `list_order` asc", array(':parent' => $id));
			$count = 1;
			while ($row = $get_children->fetch()) {
				if ($count == 1)
					echo "\n".'<ul class="'.$main_class.'">'."\n";

				$this->recurse_pages($row["id"], $main_class, $list_class, $show_order_fields);

				if ($count == $get_children->rowCount())
					echo "</ul>\n";
				$count++;
			}
			echo "</li>\n";
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
			$get_dates = $sql->query("select distinct year(`created_at`) as `year`, month(`created_at`) as `month`, `created_at`, count(`id`) as `posts` from `".$sql->prefix."posts` where `status` = 'public' group by year(`created_at`), month(`created_at`) order by `".$order_by."` ".$order.((0 != $limit) ? (" limit ".$limit) : ""));
			$archives = array();
			$count = 0;
			$route = Route::current();
			while ($the_post = $get_dates->fetchObject()) {
				$archives[$count]["month"] = when("F", $the_post->created_at);
				$archives[$count]["year"] = when("Y", $the_post->created_at);
				$archives[$count]["url"] = $route->url("archive/".when("Y/m/", $the_post->created_at));
				$archives[$count]["count"] = $the_post->posts;
				$count++;
			}
			return $archives;
		}

		/**
		 * Function: snippet_exists
		 * Returns whether a snippet exists with the specified $name.
		 *
		 * Parameters:
		 *     $name - The name of the snippet.
		 */
		public function snippet_exists($name) {
			global $snippet;
			return method_exists($snippet, $name);
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
			$theme = (isset($_GET['action']) and $_GET['action'] == "theme_preview" and !empty($_GET['theme']) and $visitor->group->can("change_settings")) ?
			         $_GET['theme'] :
			         $config->theme ;

			$stylesheets = $trigger->filter("stylesheets", '<link rel="stylesheet" href="'.$config->url.'/themes/'.$theme.'/stylesheets/screen.css" type="text/css" media="screen" charset="utf-8" />'."\n\t\t".'<link rel="stylesheet" href="'.$config->url.'/themes/'.$theme.'/stylesheets/print.css" type="text/css" media="print" charset="utf-8" />');

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

			$javascripts = $trigger->filter("scripts", '<script src="'.$config->url.'/includes/lib/gz.php?file=jquery.js" type="text/javascript" charset="utf-8"></script>'."\n\t\t".'<script src="'.$config->url.'/includes/lib/gz.php?file=forms.js" type="text/javascript" charset="utf-8"></script>'."\n\t\t".'<script src="'.$config->url.'/includes/javascript.php?action='.$action.$args.'" type="text/javascript" charset="utf-8"></script>');

			return $javascripts;
		}

		/**
		 * Function: feeds
		 * Outputs the Feed references.
		 */
		public function feeds() {
			global $request, $plural_feathers;

			$config = Config::current();
			$request = ($config->clean_urls) ? rtrim($request, "/") : htmlspecialchars($_SERVER['REQUEST_URI']) ;
			$append = ($config->clean_urls) ?
			              "/feed" :
			              ((count($_GET) == 1 and $_GET['action'] == "index") ?
			                "/?feed" :
			                  "&amp;feed") ;

			$route = Route::current();
			$feeds = '<link rel="alternate" type="application/atom+xml" title="'.$config->name.' Feed" href="'.$route->url("feed/").'" />'."\n";
			foreach ($plural_feathers as $plural => $normal)
				$feeds.= "\t\t".'<link rel="alternate" type="application/atom+xml" title="'.ucfirst($plural).' Feed" href="'.$route->url($plural."/feed/").'" />'."\n";
			$feeds.= "\t\t".'<link rel="alternate" type="application/atom+xml" title="Current Page (if applicable)" href="'.$config->url.$request.$append.'" />';
			return $feeds;
		}

		/**
		 * Function: load
		 * Loads a theme's file and extracts the passed array into the scope.
		 */
		public function load($file, $context = array()) {
			global $action, $viewing;

			$visitor = Visitor::current();
			fallback($_GET['action'], "index");
			if (!file_exists($this->directory.$file.".twig"))
				error(__("Theme Template Missing"), sprintf(__("Couldn't load theme template:<br /><br />%s"), $file.".twig"));

			$can = array();
			foreach ($visitor->group->permissions as $permission)
				$can[$permission] = true;

			$context["title"] = $this->title;
			$context["site"] = Config::current();
			$context["theme"] = array("feeds" => $this->feeds(),
			                          "stylesheets" => $this->stylesheets(),
			                          "javascripts" => $this->javascripts());
			$context["user"] = array("logged_in" => logged_in(), "can" => $can);
			$context["archive_list"] = $this->list_archives();
			$context["stats"] = array("load" => timer_stop(), "queries" => SQL::current()->queries);
			$context["route"] = array("action" => $action);
			$context["viewing"] = $viewing;

			if (logged_in())
				foreach ($visitor as $key => $val)
					$context["user"][$key] = $val;

			$trigger = Trigger::current();
			$context = $trigger->filter("twig_global_context", $context);
			$context = $trigger->filter(str_replace("/", "_", $file), $context);

			$template = $this->twig->getTemplate($file.".twig");
			return $template->display($context);
		}
	}
	$theme = new Theme();
