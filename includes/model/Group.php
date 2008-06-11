<?php
	/**
	 * Class: Group
	 * The Group model.
	 */
	class Group extends Model {
		public $no_results = false;
		public $has = array();

		/**
		 * Function: __construct
		 * See Also:
		 *     <Model::grab>
		 */
		public function __construct($group_id = null, $options = array()) {
			parent::grab($this, $group_id, $options);

			if ($this->no_results)
				return false;

			$this->permissions = Spyc::YAMLLoad($this->permissions);
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
		 * Function: can
		 * Checks if the group can perform the specified functions.
		 */
		public function can() {
			$functions = func_get_args();

			# OR comparison
			if (end($functions) !== true)
			{
				foreach ($functions as $function)
					if (in_array($function, $this->permissions)) return true;

				return false;
			}
			# AND comparison
			else
			{
				array_pop($functions);

				foreach ($functions as $function)
					if (!in_array($function, $this->permissions)) return false;

				return true;
			}
		}

		/**
		 * Function: add
		 * Adds a group to the database with the passed Name and Permissions array.
		 *
		 * Calls the add_group trigger with the ID, name, and permissions or the new group.
		 *
		 * Parameters:
		 *     $name - The group's name
		 *     $permissions - An array of the permissions.
		 *
		 * See Also:
		 *     <update>
		 */
		static function add($name, $permissions) {
			$sql = SQL::current();
			$sql->insert("groups", array("name" => ":name", "permissions" => ":permissions"),
			                       array(":name"  => $name,   ":permissions"  => Spyc::YAMLDump($permissions)));

			$id = $sql->db->lastInsertId();
			Trigger::current()->call("add_group", array($id, $name, $permissions));
			return new self($id);
		}

		/**
		 * Function: update
		 * Updates a group with the given name and permissions, and passes arguments to the update_group trigger..
		 *
		 * Parameters:
		 *     $group_id - The group to update.
		 *     $name - The new Name to set.
		 *     $permissions - An array of the new permissions to set.
		 */
		public function update($name, $permissions) {
			$sql = SQL::current();
			$sql->update("groups", "`__groups`.`id` = :id",
			             array("name" => ":name", "permissions" => ":permissions"),
			             array(":name" => $name, ":permissions" => Spyc::YAMLDump($permissions), ":id" => $this->id));

			Trigger::current()->call("update_group", array($this, $name, $permissions));
		}

		/**
		 * Function: delete
		 * Deletes a given group. Calls the "delete_group" trigger and passes the <Group> as an argument.
		 *
		 * Parameters:
		 *     $id - The group to delete.
		 */
		static function delete($id) {
			parent::destroy(get_class(), $id);
		}

		/**
		 * Function: info
		 * Grabs a specified column from a group's SQL row.
		 *
		 * Parameters:
		 *     $column - The name of the SQL column.
		 *     $group_id - The group ID to grab from.
		 *
		 * Returns:
		 *     SQL result - if the SQL result isn't empty.
		 */
		static function info($column, $group_id) {
			return SQL::current()->select("groups", $column, "`__groups`.`id` = :id", "`__groups`.`id` desc", array(":id" => $group_id))->fetchColumn();
		}

		/**
		 * Function: add_permission
		 * Adds a permission to the Groups table.
		 *
		 * Parameters:
		 *     $name - The name of the permission to add. The naming scheme is simple; for example,
		 *             "code_in_comments" gets converted to "Code In Comments" at the group editing page.
		 */
		static function add_permission($name) {
			$sql = SQL::current();

			if ($sql->count("permissions", "`name` = :name", array(":name" => $name)))
				return; # Permission already exists.

			$sql->insert("permissions", array("name" => ":name"), array(":name" => $name));
		}

		/**
		 * Function: remove_permission
		 * Removes a permission from the Groups table.
		 *
		 * Parameters:
		 *     $name - The permission name to remove.
		 */
		static function remove_permission($name) {
			SQL::current()->delete("permissions", "`name` = :name", array(":name" => $name));
		}

		/**
		 * Function: size
		 * Returns the amount of users in the.
		 */
		public function size() {
			return (isset($this->size)) ? $this->size : $this->size = SQL::current()->count("users", "`group_id` = :group_id", array(":group_id" => $this->id)) ;
		}

		/**
		 * Function: members
		 * Returns all the members of the group.
		 */
		public function members() {
			return User::find(array("where" => "`group_id` = :group_id",
			                        "params" => array(":group_id" => $this->id)));
		}

		/**
		 * Function: edit_link
		 * Outputs an edit link for the group, if the user can.
		 *
		 * Parameters:
		 *     $text - The text to show for the link.
		 *     $before - If the link can be shown, show this before it.
		 *     $after - If the link can be shown, show this after it.
		 */
		public function edit_link($text = null, $before = null, $after = null){
			$visitor = Visitor::current();
			if (!$visitor->group()->can("edit_group")) return;
			fallback($text, __("Edit"));
			$config = Config::current();
			echo $before.'<a href="'.$config->chyrp_url.'/admin/?action=edit_group&amp;id='.$this->id.'" title="Edit" class="group_edit_link edit_link" id="group_edit_'.$this->id.'">'.$text.'</a>'.$after;
		}

		/**
		 * Function: delete_link
		 * Outputs an delete link for the group, if the user can.
		 *
		 * Parameters:
		 *     $text - The text to show for the link.
		 *     $before - If the link can be shown, show this before it.
		 *     $after - If the link can be shown, show this after it.
		 */
		public function delete_link($text = null, $before = null, $after = null){
			$visitor = Visitor::current();
			if (!$visitor->group()->can("delete_group")) return;
			fallback($text, __("Delete"));
			$config = Config::current();
			echo $before.'<a href="'.$config->chyrp_url.'/admin/?action=delete_group&amp;id='.$this->id.'" title="Delete" class="group_delete_link delete_link" id="group_delete_'.$this->id.'">'.$text.'</a>'.$after;
		}
	}
