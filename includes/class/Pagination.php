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
		public function select($tables, $fields, $conds, $order = null, $limit = 5, $var = "page", $params = array(), $group = null, $left_join = null) {
			$sql = SQL::current();
			$total_results = $sql->count($tables, $conds, $params, $left_join);

			$this->$var = (isset($_GET[$var])) ? $_GET[$var] : 1 ;
			$this->total_pages = ceil($total_results / $limit);
			$this->offset = ($this->$var - 1) * $limit;

			return $sql->select($tables, $fields, $conds, $order, $params, $limit, $this->offset, $group, $left_join);
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
