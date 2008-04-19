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
		
		private $pages = array();
		
		/**
		 * Function: __construct
		 * Loads the Twig parser into <Theme>.
		 */
		public function __construct() {
			$this->twig = new Twig_Loader(THEME_DIR, (is_writable(MAIN_DIR."/includes/twig_cache") ? MAIN_DIR."/includes/twig_cache" : null));
		}
		
		/**
		 * Function: list_pages
		 * Generates a recursive list of pages and their children. Outputs it as a <ul> list.
		 * 
		 * Parameters:
		 * 	$home_link - Whether or not to show the "Home" link
		 * 	$home_text - Text for the "Home" link
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
		 * 	$id - The page ID to start at.
		 * 
		 * See Also:
		 * 	<list_pages>
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
		 * 	$limit - Amount of months to list
		 * 	$order_by - What to sort it by
		 * 	$order - "asc" or "desc"
		 * 
		 * Returns:
		 * 	$archives - The array. Each entry as "month", "year", and "url" values, stored as an array.
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
		 * 	$name - The name of the snippet.
		 */
		public function snippet_exists($name) {
			global $snippet;
			return method_exists($snippet, $name);
		}
		
		/**
		 * Function: title
		 * Returns a title for the current page.
		 * 
		 * Parameters:
		 * 	$separator - The separator for the title, e.g. " &raquo; "
		 * 	$title - The title to show. If null, it'll fall back on to <Theme->$title> (which is the more standard procedure for this function).
		 */
		public function title($seperator = "&nbsp;&raquo;&nbsp;", $title = null) {
			$config = Config::current();
			$this->title = (!isset($title)) ? $this->title : $title ;
			$this->title = (empty($this->title)) ? $config->name : $config->name.$seperator.$this->title ;
			return $this->title;
		}
		
		/**
		 * Function: stylesheets
		 * Outputs the default stylesheet links.
		 */
		public function stylesheets() {
			$config = Config::current();
			$theme = (isset($_GET['action']) and $_GET['action'] == "theme_preview" and !empty($_GET['theme']) and $user->can("change_settings")) ?
			         $_GET['theme'] :
			         $config->theme ;
?>
<link rel="stylesheet" href="<?php echo $config->url."/themes/".$theme ?>/stylesheets/screen.css" type="text/css" media="screen" charset="utf-8" />
		<link rel="stylesheet" href="<?php echo $config->url."/themes/".$theme ?>/stylesheets/print.css" type="text/css" media="print" charset="utf-8" />
<?php
			$trigger = Trigger::current();
			$trigger->call("stylesheets");
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
?>
<script src="<?php echo $config->url; ?>/includes/lib/gz.php?file=jquery.js" type="text/javascript" charset="utf-8"></script>
		<script src="<?php echo $config->url; ?>/includes/lib/gz.php?file=forms.js" type="text/javascript" charset="utf-8"></script>
		<script src="<?php echo $config->url; ?>/includes/javascript.php?action=<?php echo $action.$args; ?>" type="text/javascript" charset="utf-8"></script>
<?php
			$trigger = Trigger::current();
			$trigger->call("scripts");
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
			echo '<link rel="alternate" type="application/atom+xml" title="'.$config->name.' Feed" href="'.$route->url("feed/").'" />';
			foreach ($plural_feathers as $plural => $normal)
				echo "\t\t".'<link rel="alternate" type="application/atom+xml" title="'.ucfirst($plural).' Feed" href="'.$route->url($plural."/feed/").'" />'."\n";
			echo "\t\t".'<link rel="alternate" type="application/atom+xml" title="Current Page (if applicable)" href="'.$config->url.$request.$append.'" />'."\n";
		}
		
		/**
		 * Function: load
		 * Loads a theme's file and extracts the passed array into the scope.
		 */
		public function load($file, $context = array()) {
			fallback($_GET['action'], "index");
			$abs_file = (isset($_GET['action']) and $_GET['action'] == "theme_preview" and !empty($_GET['theme']) and $user->can("change_settings")) ?
			            THEMES_DIR."/".$_GET['theme']."/".$file.".twig" :
			            THEME_DIR."/".$file.".twig" ;
			
			if (!file_exists($abs_file))
				error(__("Theme Template Missing"), sprintf(__("Couldn't load theme template:<br /><br />%s"), $file.".twig"));
			
			$template = $this->twig->getTemplate($abs_file);
			$template->display($context);
		}
	}
	$theme = new Theme();
