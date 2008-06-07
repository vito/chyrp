<?php
	/**
	 * Class: Page
	 * The Page model.
	 */
	class Page extends Model {
		public $no_results = false;
		public $id = 0;

		/**
		 * Function: __construct
		 * See Also:
		 *     <Model::grab>
		 */
		public function __construct($page_id = null, $options = array()) {
			if (!isset($page_id) and empty($options)) return;
			parent::grab($this, $page_id, $options);
			$this->slug =& $this->url;
		}

		/**
		 * Function: find
		 * See Also:
		 *     <Model::search>
		 */
		static function find($options = array()) {
			return parent::search(get_class(), $options);
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
		static function add($title, $body, $parent_id, $show_in_list, $list_order = 0, $clean, $url) {
			$sql = SQL::current();
			$visitor = Visitor::current();
			$sql->insert("pages",
			             array(
			                 "title" => ":title",
			                 "body" => ":body",
			                 "user_id" => ":user_id",
			                 "parent_id" => ":parent_id",
			                 "show_in_list" => ":show_in_list",
			                 "list_order" => ":list_order",
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
			                 ":list_order" => $list_order,
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
		public function update($title, $body, $parent_id, $show_in_list, $list_order, $url) {
			if (!isset($this->id)) return;

			if ($title != $this->title or $body != $this->body)
				$updated = datetime();
			else
				$updated = "0000-00-00 00:00:00";

			$sql = SQL::current();
			$sql->update("pages",
			             "`__pages`.`id` = :id",
			             array(
			                 "title" => ":title",
			                 "body" => ":body",
			                 "parent_id" => ":parent_id",
			                 "show_in_list" => ":show_in_list",
			                 "list_order" => ":list_order",
			                 "updated_at" => ":updated_at",
			                 "url" => ":url"
			             ),
			             array(
			                 ":title" => $title,
			                 ":body" => $body,
			                 ":parent_id" => $parent_id,
			                 ":show_in_list" => $show_in_list,
			                 ":list_order" => $list_order,
			                 ":updated_at" => $updated,
			                 ":url" => $url,
			                 ":id" => $this->id
			             ));

			$trigger = Trigger::current();
			$trigger->call("update_page", $this->id);
		}

		/**
		 * Function: delete
		 * Deletes the given page. Calls the "delete_page" trigger and passes the <Page> as an argument.
		 *
		 * Parameters:
		 *     $id - The page to delete. Child pages if this page will be removed as well.
		 */
		static function delete($id, $recursive = false) {
			if ($recursive) {
				$page = new self($id);
				foreach ($page->children() as $child)
					self::delete($child->id);
			}

			parent::destroy(get_class(), $id);
		}

		/**
		 * Function: info
		 * Grabs a specified column from a page's SQL row.
		 *
		 * Parameters:
		 *     $column - The name of the SQL column.
		 *     $page_id - The page ID to grab from.
		 *
		 * Returns:
		 *     SQL result - if the SQL result isn't empty.
		 */
		static function info($column, $page_id) {
			return SQL::current()->select("pages", $column, "`__pages`.`id` = :id", "`__pages`.`id` desc", array(":id" => $page_id))->fetchColumn();
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
			                      "`__pages`.`id` = :id",
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

			while (isset($page->parent_id) and $page->parent_id) {
				$url[] = $page->parent()->url;
				$page = $page->parent();
			}

			return Route::current()->url("page/".implode('/', array_reverse($url)));
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
		 * Function: children
		 * Returns a page's children.
		 */
		public function children() {
			return parent::search(get_class(), array("where" => "`parent_id` = :id", "params" => array(":id" => $this->id)));
		}

		/**
		 * Function: user
		 * Returns a page's creator. Example: $page->user()->full_name
		 */
		public function user() {
			return new User($this->user_id);
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
			echo $before.'<a href="'.$config->chyrp_url.'/admin/?action=edit_page&amp;id='.$this->id.'" title="Edit" class="page_edit_link edit_link" id="page_edit_'.$this->id.'">'.$text.'</a>'.$after;
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
			echo $before.'<a href="'.$config->chyrp_url.'/admin/?action=delete_page&amp;id='.$this->id.'" title="Delete" class="page_delete_link delete_link" id="page_delete_'.$this->id.'">'.$text.'</a>'.$after;
		}
	}
