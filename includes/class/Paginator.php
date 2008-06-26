<?php
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
		public function __construct($array, $per_page = 5, $name = "page", $model = true, $page = null) {
			$this->array = $array;

			if ($model)
				list($array, $model_name) = $array;

			$this->per_page = $per_page;
			$this->name = $name;
			$this->model = $model;

			$this->total = count($array);
			$this->page = fallback($page, (isset($_GET[$name]) ? $_GET[$name] : 1), true);
			$this->pages = ceil($this->total / $this->per_page);
			$this->offset = ($this->page - 1) * $this->per_page;

			$this->result = array();
			for ($i = $this->offset; $i < ($this->offset + $this->per_page); $i++)
				if (isset($array[$i]))
					$this->result[] = ($model) ? new $model_name($array[$i]) : $array[$i] ;

			$shown_dates = array();
			if ($model)
				foreach ($this->result as &$result)
					if (isset($result->created_at)) {
						$result->date_shown = in_array(when("m-d-Y", $result->created_at), $shown_dates);
						if (!in_array(when("m-d-Y", $result->created_at), $shown_dates))
							$shown_dates[] = when("m-d-Y", $result->created_at);
					}

			$this->paginated = $this->paginate = $this->list =& $this->result;
		}

		/**
		 * Function: next
		 * Returns the next pagination sequence.
		 */
		public function next() {
			return new self($this->array, $this->per_page, $this->name, $this->model, $this->page + 1);
		}

		/**
		 * Function: prev
		 * Returns the next pagination sequence.
		 */
		public function prev() {
			return new self($this->array, $this->per_page, $this->name, $this->model, $this->page - 1);
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
			$request = rtrim($_SERVER['REQUEST_URI'], "/");
			$only_page = (count($_GET) == 1 and isset($_GET[$this->name]));

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
			$request = rtrim($_SERVER['REQUEST_URI'], "/");
			$only_page = (count($_GET) == 1 and isset($_GET[$this->name]));

			$config = Config::current();
			if (!$config->clean_urls or !$clean_urls or ADMIN)
				$mark = (strpos($request, "?") and !$only_page) ? "&" : "?" ;

			return ($config->clean_urls and $clean_urls and !ADMIN) ?
			       preg_replace("/(\/".$this->name."\/([0-9]+)|$)/", "/".$this->name."/".($this->page - 1), "http://".$_SERVER['HTTP_HOST'].$request, 1) :
			       preg_replace("/([\?&]".$this->name."=([0-9]+)|$)/", $mark.$this->name."=".($this->page - 1), "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 1) ;
		}
	}
