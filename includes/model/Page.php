<?php
	$current_page = array("id" => 0);

	/**
	 * Class: Page
	 * The model for the Pages SQL table.
	 */
	class Page {
		public $no_results = false;

		/**
		 * Function: __construct
		 * Grabs the specified page and injects it into the <Page> class.
		 *
		 * Parameters:
		 *     $page_id - The page's unique ID.
		 *     $options - An array of options:
		 *         where: A SQL query to grab the page by.
		 *         params: Parameters to use for the "where" option.
		 *         read_from: An associative array of values to load into the <Page> class.
		 */
		public function __construct($page_id, $options = array()) {
			global $current_page;

			$where = fallback($options["where"], "", true);
			$params = isset($options["params"]) ? $options["params"] : array();
			$read_from = (isset($options["read_from"])) ? $options["read_from"] : array() ;

			$sql = SQL::current();
			if ((!empty($read_from) && $read_from))
				$read = $read_from;
			elseif (isset($page_id) and $page_id == $current_page["id"])
				$read = $current_page;
			elseif (!empty($where))
				$read = $sql->select("pages",
				                     "*",
				                     $where,
				                     "id",
				                     $params,
				                     1)->fetch();
			else
				$read = $sql->select("pages",
				                     "*",
				                     "`id` = :pageid",
				                     "id",
				                     array(
				                         ":pageid" => $page_id
				                     ),
				                     1)->fetch();

			if (!count($read) or !$read)
				return $this->no_results = true;

			foreach ($read as $key => $val)
				if (!is_int($key))
					$this->$key = $current_page[$key] = $val;

			$this->user = new User($this->user_id);
		}

		/**
		 * Function: add
		 * Adds a page to the database.
		 *
		 * Calls the add_page trigger with the inserted ID.
		 *
		 * Parameters:
		 *     $title - The Title for the new page.
		 *     $body - The Body for the new page.
		 *     $parent_id - The ID of the new page's parent page (0 for none).
		 *     $show_in_list - Whether or not to show it in the pages list.
		 *     $clean - The sanitized URL (or empty to default to "(feather).(new page's id)").
		 *     $url - The unique URL (or empty to default to "(feather).(new page's id)").
		 *
		 * Returns:
		 *     $id - The newly created page's ID.
		 *
		 * See Also:
		 *     <update>
		 */
		static function add($title, $body, $parent_id, $show_in_list, $clean, $url) {
			$sql = SQL::current();
			$visitor = Visitor::current();
			$sql->insert("pages",
			             array(
			                 "title" => ":title",
			                 "body" => ":body",
			                 "user_id" => ":user_id",
			                 "parent_id" => ":parent_id",
			                 "show_in_list" => ":show_in_list",
			                 "clean" => ":clean",
			                 "url" => ":url",
			                 "created_at" => ":created_at"
			             ),
			             array(
			                 ":title" => $title,
			                 ":body" => $body,
			                 ":user_id" => $visitor->id,
			                 ":parent_id" => $parent_id,
			                 ":show_in_list" => $show_in_list,
			                 ":clean" => $clean,
			                 ":url" => $url,
			                 ":created_at" => datetime()
			             ));
			$id = $sql->db->lastInsertId();

			$trigger = Trigger::current();
			$trigger->call("add_page", $id);

			return new self($id);
		}

		/**
		 * Function: update
		 * Updates the given page.
		 *
		 * Parameters:
		 *     $page_id - The page to update.
		 *     $title - The new Title.
		 *     $body - The new Bod.
		 *     $parent_id - The new parent ID.
		 *     $show_in_list - Whether or not to show it in the pages list.
		 *     $url - The new page URL.
		 */
		public function update($title, $body, $parent_id, $show_in_list, $url) {
			if (!isset($this->id)) return;

			$sql = SQL::current();
			$sql->update("pages",
			             "`id` = :id",
			             array(
			                 "title" => ":title",
			                 "body" => ":body",
			                 "parent_id" => ":parent_id",
			                 "show_in_list" => ":show_in_list",
			                 "updated_at" => ":updated_at",
			                 "url" => ":url"
			             ),
			             array(
			                 ":title" => $title,
			                 ":body" => $body,
			                 ":parent_id" => $parent_id,
			                 ":show_in_list" => $show_in_list,
			                 ":updated_at" => datetime(),
			                 ":url" => $url,
			                 ":id" => $this->id
			             ));

			$trigger = Trigger::current();
			$trigger->call("update_page", $this->id);
		}

		/**
		 * Function: delete
		 * Deletes the given page.
		 *
		 * Parameters:
		 *     $page_id - The page to delete. Sub-pages if this page will be removed as well.
		 */
		public function delete() {
			if (!isset($this->id)) return;

			$trigger = Trigger::current();
			$trigger->call("delete_page", $id);

			$sql = SQL::current();
			$sql->delete("pages",
			             "`id` = :id",
			             array(
			                 ":id" => $this->id
			             ));

			$get_sub_pages = $sql->select("pages",
			                              "id",
			                              "`parent_id` = :id",
			                              "id",
			                              array(
			                                  ":id" => $this->id
			                              ));
			while ($sub_page = $get_sub_pages->fetchObject()) {
				$sub = new self($sub_page->id);
				$sub->delete();
			}
		}

		/**
		 * Function: info
		 * Grabs a specified column from a page's SQL row.
		 *
		 * Parameters:
		 *     $column - The name of the SQL column.
		 *     $page_id - The page ID to grab from.
		 *     $fallback - What to display if the result is empty.
		 *
		 * Returns:
		 *     false - if $page_id isn't set.
		 *     SQL result - if the SQL result isn't empty.
		 *     $fallback - if the SQL result is empty.
		 */
		static function info($column, $page_id, $fallback = false) {
			global $current_page;

			if ($current_page["id"] == $page_id)
				return $current_page[$column];

			$sql = SQL::current();
			$grab_column = $sql->select("pages",
			                            $column,
			                            "`id` = :id",
			                            "id",
			                            array(
			                                ":id" => $page_id
			                            ));
			return ($grab_column->rowCount() == 1) ? $grab_column->fetchColumn() : $fallback ;
		}

		/**
		 * Function: exists
		 * Checks if a page exists.
		 *
		 * Parameters:
		 *     $page_id - The page ID to check
		 *
		 * Returns:
		 *     true - if a page with that ID is in the database.
		 */
		static function exists($page_id) {
			$sql = SQL::current();
			$check = $sql->select("pages",
			                      "id",
			                      "`id` = :id",
			                      "id",
			                      array(
			                          ":id" => $page_id
			                      ));
			return $check->rowCount();
		}

		/**
		 * Function: check_url
		 * Checks if a given clean URL is already being used as another page's URL.
		 *
		 * Parameters:
		 *     $clean - The clean URL to check.
		 *
		 * Returns:
		 *     $url - The unique version of the passed clean URL. If it's not used, it's the same as $clean. If it is, a number is appended.
		 */
		static function check_url($clean) {
			$sql = SQL::current();
			$check_url = $sql->select("pages",
			                          "id",
			                          "`clean` = :clean",
			                          "id",
			                          array(
			                              ':clean' => $clean
			                          ));
			$count = $check_url->rowCount() + 1;
			return ($count == 1 or empty($clean)) ? $clean : $clean."_".$count ;
		}

		/**
		 * Function: url
		 * Returns a page's URL.
		 */
		public function url() {
			$url = array('', $this->url);
			$page = $this;

			while (isset($page->parent)) {
				$url[] = $page->parent->url;
				$page = $page->parent;
			}

			$route = Route::current();
			return $route->url('page/'.implode('/', array_reverse($url)));
		}

		/**
		 * Function: parent
		 * Returns a page's parent. Example: $page->parent()->parent()->title
		 */
		public function parent() {
			if (!$this->parent_id) return;
			return new self($this->parent_id);
		}

		/**
		 * Function: edit_link
		 * Outputs an edit link for the page, if the <User.can> edit_page.
		 *
		 * Parameters:
		 *     $text - The text to show for the link.
		 *     $before - If the link can be shown, show this before it.
		 *     $after - If the link can be shown, show this after it.
		 */
		public function edit_link($text = null, $before = null, $after = null){
			$visitor = Visitor::current();
			if (!isset($this->id) or !$visitor->group()->can("edit_page")) return false;

			fallback($text, __("Edit"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=edit&amp;sub=page&amp;id='.$this->id.'" title="Edit" class="page_edit_link" id="page_edit_'.$this->id.'">'.$text.'</a>'.$after;
		}

		/**
		 * Function: delete_link
		 * Outputs a delete link for the page, if the <User.can> delete_page.
		 *
		 * Parameters:
		 *     $text - The text to show for the link.
		 *     $before - If the link can be shown, show this before it.
		 *     $after - If the link can be shown, show this after it.
		 */
		public function delete_link($text = null, $before = null, $after = null){
			$visitor = Visitor::current();
			if (!isset($this->id) or !$visitor->group()->can("delete_page")) return false;

			fallback($text, __("Delete"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=delete&amp;sub=page&amp;id='.$this->id.'" title="Delete" class="page_delete_link" id="page_delete_'.$this->id.'">'.$text.'</a>'.$after;
		}
	}
