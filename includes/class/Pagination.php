<?php
	/**
	 * Class: Pagination
	 * Automates pagination.
	 */
	class Pagination {
		/**
		 * Function: select
		 * Performs a select statement that takes pagination into account.
		 *
		 * Returns:
		 *     A paginated SQL select.
		 *
		 * See Also:
		 * <SQL.select>
		 */
		public function select($tables, $fields, $conds, $order = null, $per_page = 5, $var = "page", $params = array(), $group = null, $left_join = null) {
			$sql = SQL::current();

			$this->per_page = $per_page;
			$this->total_results = $sql->count($tables, $conds, $params);

			$this->$var = (isset($_GET[$var])) ? $_GET[$var] : 1 ;
			$this->total_pages = ceil($this->total_results / $this->per_page);
			$this->offset = ($this->$var - 1) * $this->per_page;

			return $sql->select($tables, $fields, $conds, $order, $params, $per_page, $this->offset, $group, $left_join);
		}

		/**
		 * Function: next_page
		 * Checks whether or not it makes sense to show the Next Page link.
		 *
		 * Parameters:
		 *     $var - The variable to check for.
		 */
		public function next_page($var = "page") {
			if (!isset($this->$var) or !isset($this->total_pages)) return false;
			if ($this->$var != $this->total_pages and $this->total_pages != 1 and $this->total_pages != 0) return true;
		}

		/**
		 * Function: prev_page
		 * Checks whether or not it makes sense to show the Previous Page link.
		 *
		 * Parameters:
		 *     $var - The variable to check for.
		 */
		public function prev_page($var = "page") {
			if (!isset($this->$var)) return false;
			if ($this->$var != 1) return true;
		}

		/**
		 * Function: next_link
		 * Outputs a link to the next page.
		 *
		 * Parameters:
		 *     $text - The text for the link.
		 *     $class - The CSS class for the link.
		 *     $var - The variable to link for.
		 *     $clean_urls - Whether to link with dirty or clean URLs.
		 */
		public function next_link($text = null, $class = "next_page", $var = "page", $clean_urls = true) {
			fallback($text, __("Next &rarr;", "theme"));
			if ($this->next_page($var))
				echo '<a class="'.$class.'" id="next_page_'.$var.'" href="'.$this->next_page_url($var, $clean_urls).'">'.$text.'</a>';
		}

		/**
		 * Function: prev_link
		 * Outputs a link to the previous page.
		 *
		 * Parameters:
		 *     $text - The text for the link.
		 *     $class - The CSS class for the link.
		 *     $var - The variable to link for.
		 *     $clean_urls - Whether to link with dirty or clean URLs.
		 */
		public function prev_link($text = null, $class = "prev_page", $var = "page", $clean_urls = true) {
			fallback($text, __("&larr; Previous", "theme"));
			if ($this->prev_page($var))
				echo '<a class="'.$class.'" id="prev_page_'.$var.'" href="'.$this->prev_page_url($var, $clean_urls).'">'.$text.'</a>';
		}

		/**
		 * Function: next_page_url
		 * Returns the URL to the next page.
		 *
		 * Parameters:
		 *     $var - The variable to link to.
		 *     $clean_urls - Whether to link with dirty or clean URLs.
		 */
		public function next_page_url($var = "page", $clean_urls = true) {
			global $action;
			$request = rtrim($_SERVER['REQUEST_URI'], "/");
			$only_page = (count($_GET) == 2 and $_GET['action'] == "index" and isset($_GET[$var]));

			$config = Config::current();
			if (!$config->clean_urls or !$clean_urls or ADMIN)
				$mark = (strpos($request, "?") and !$only_page) ? "&" : "?" ;

			return ($config->clean_urls and $clean_urls and !ADMIN) ?
			       preg_replace("/(\/".$var."\/([0-9]+)|$)/", "/".$var."/".($this->$var + 1), "http://".$_SERVER['HTTP_HOST'].$request, 1) :
			       preg_replace("/([\?&]".$var."=([0-9]+)|$)/", $mark.$var."=".($this->$var + 1), "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 1) ;
		}

		/**
		 * Function: prev_page_url
		 * Returns the URL to the previous page.
		 *
		 * Parameters:
		 *     $var - The variable to link to.
		 *     $clean_urls - Whether to link with dirty or clean URLs.
		 */
		public function prev_page_url($var = "page", $clean_urls = true) {
			global $action;
			$request = rtrim($_SERVER['REQUEST_URI'], "/");
			$only_page = (count($_GET) == 2 and $_GET['action'] == "index" and isset($_GET[$var]));

			$config = Config::current();
			if (!$config->clean_urls or !$clean_urls or ADMIN)
				$mark = (strpos($request, "?") and !$only_page) ? "&" : "?" ;

			return ($config->clean_urls and $clean_urls and !ADMIN) ?
			       preg_replace("/(\/".$var."\/([0-9]+)|$)/", "/".$var."/".($this->$var - 1), "http://".$_SERVER['HTTP_HOST'].$request, 1) :
			       preg_replace("/([\?&]".$var."=([0-9]+)|$)/", $mark.$var."=".($this->$var - 1), "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 1) ;
		}
	}
	$paginate = new Pagination();


	/**
	 * Class: Paginator
	 * Paginates over an array.
	 */
	class Paginator {
		/**
		 * Function: __construct
		 * Prepares an array for pagination.
		 *
		 * Parameters:
		 *     $array - The array to paginate.
		 *     $per_page - Amount of items per page.
		 *     $name - The name of the $_GET parameter to use for determining the current page.
		 *     $model - If this is true, each item in $array that gets shown on the page will be
		 *              initialized as a model of whatever is passed as the second argument to $array.
		 *              The first argument of $array is expected to be an array of IDs.
		 *
		 * Returns:
		 *     A paginated array $per_page or smaller.
		 */
		public function __construct($array, $per_page = 5, $name = "page", $model = true) {
			if ($model) {
				$model_name = $array[1];
				$array = $array[0];
			}

			$this->per_page = $per_page;
			$this->name = $name;

			$this->total = count($array);
			$this->page = (isset($_GET[$name])) ? $_GET[$name] : 1 ;
			$this->pages = ceil($this->total / $this->per_page);
			$this->offset = ($this->page - 1) * $this->per_page;

			$this->result = array();
			for ($i = $this->offset; $i < ($this->offset + $per_page); $i++)
				if (isset($array[$i]))
					$this->result[] = ($model) ? new $model_name($array[$i]) : $array[$i] ;

			$this->paginated = $this->paginate = $this->list = $this->result;
		}

		/**
		 * Function: next_page
		 * Checks whether or not it makes sense to show the Next Page link.
		 */
		public function next_page() {
			if (!isset($this->page) or !isset($this->pages)) return false;
			if ($this->page != $this->pages and $this->pages != 1 and $this->pages != 0) return true;
		}

		/**
		 * Function: prev_page
		 * Checks whether or not it makes sense to show the Previous Page link.
		 */
		public function prev_page() {
			if (!isset($this->page)) return false;
			if ($this->page != 1) return true;
		}

		/**
		 * Function: next_link
		 * Outputs a link to the next page.
		 *
		 * Parameters:
		 *     $text - The text for the link.
		 *     $class - The CSS class for the link.
		 *     $clean_urls - Whether to link with dirty or clean URLs.
		 */
		public function next_link($text = null, $class = "next_page", $clean_urls = true) {
			fallback($text, __("Next &rarr;", "theme"));
			if ($this->next_page())
				echo '<a class="'.$class.'" id="next_page_'.$this->name.'" href="'.$this->next_page_url($clean_urls).'">'.$text.'</a>';
		}

		/**
		 * Function: prev_link
		 * Outputs a link to the previous page.
		 *
		 * Parameters:
		 *     $text - The text for the link.
		 *     $class - The CSS class for the link.
		 *     $clean_urls - Whether to link with dirty or clean URLs.
		 */
		public function prev_link($text = null, $class = "prev_page", $clean_urls = true) {
			fallback($text, __("&larr; Previous", "theme"));
			if ($this->prev_page())
				echo '<a class="'.$class.'" id="prev_page_'.$this->name.'" href="'.$this->prev_page_url($clean_urls).'">'.$text.'</a>';
		}

		/**
		 * Function: next_page_url
		 * Returns the URL to the next page.
		 *
		 * Parameters:
		 *     $clean_urls - Whether to link with dirty or clean URLs.
		 */
		public function next_page_url($clean_urls = true) {
			global $action;
			$request = rtrim($_SERVER['REQUEST_URI'], "/");
			$only_page = (count($_GET) == 2 and $_GET['action'] == "index" and isset($_GET[$this->name]));

			$config = Config::current();
			if (!$config->clean_urls or !$clean_urls or ADMIN)
				$mark = (strpos($request, "?") and !$only_page) ? "&" : "?" ;

			return ($config->clean_urls and $clean_urls and !ADMIN) ?
			       preg_replace("/(\/".$this->name."\/([0-9]+)|$)/", "/".$this->name."/".($this->page + 1), "http://".$_SERVER['HTTP_HOST'].$request, 1) :
			       preg_replace("/([\?&]".$this->name."=([0-9]+)|$)/", $mark.$this->name."=".($this->page + 1), "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 1) ;
		}

		/**
		 * Function: prev_page_url
		 * Returns the URL to the previous page.
		 *
		 * Parameters:
		 *     $clean_urls - Whether to link with dirty or clean URLs.
		 */
		public function prev_page_url($clean_urls = true) {
			global $action;
			$request = rtrim($_SERVER['REQUEST_URI'], "/");
			$only_page = (count($_GET) == 2 and $_GET['action'] == "index" and isset($_GET[$this->name]));

			$config = Config::current();
			if (!$config->clean_urls or !$clean_urls or ADMIN)
				$mark = (strpos($request, "?") and !$only_page) ? "&" : "?" ;

			return ($config->clean_urls and $clean_urls and !ADMIN) ?
			       preg_replace("/(\/".$this->name."\/([0-9]+)|$)/", "/".$this->name."/".($this->page - 1), "http://".$_SERVER['HTTP_HOST'].$request, 1) :
			       preg_replace("/([\?&]".$this->name."=([0-9]+)|$)/", $mark.$this->name."=".($this->page - 1), "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 1) ;
		}
	}
