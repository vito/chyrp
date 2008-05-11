<?php
	/**
	 * Class: Group
	 * The model for the Groups SQL table.
	 */
	class Group extends Model {
		public $no_results = false;

		/**
		 * Function: __construct
		 * Grabs the specified group and injects it into the <Group> class.
		 *
		 * Parameters:
		 *     $group_id - The group's unique ID.
		 *     $options - An array of options:
		 *         where: A SQL query to grab the group by.
		 *         params: Parameters to use for the "where" option.
		 *         read_from: An associative array of values to load into the <Group> class.
		 */
		public function __construct($group_id = null, $options = array()) {
			parent::grab($this, $group_id, $options);
			$this->permissions = Spyc::YAMLLoad($this->permissions);
		}

		/**
		 * Function: can
		 * Checks if the group can perform $function.
		 *
		 * Parameters:
		 *     $function - The function to check.
		 */
		public function can($function) {
			return in_array($function, $this->permissions);
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
			$query = "";

			$sql = SQL::current();
			$fields = array("`name`" => ":name", "`permissions`" => ":permissions");
			$params = array(":name" => $name, ":permissions" => Spyc::YAMLDump($permissions));

			$sql->query("insert into `__groups`
			             (".implode(",", array_keys($fields)).")
			             values
			             (".implode(",", array_values($fields)).")",
			            $params);

			$id = $sql->db->lastInsertId();
			$trigger = Trigger::current();
			$trigger->call("add_group", array($id, $name, $permissions));
			return $id;
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

			$fields = array("`name`" => ":name", "`permissions`" => ":permissions");
			$params = array(":name" => $name, ":permissions" => Spyc::YAMLDump($permissions), ":id" => $this->id);

			$sql->query("update `__groups` set `name` = :name, `permissions` = :permissions where `id` = :id", $params);

			$trigger = Trigger::current();
			$trigger->call("update_group", array($this, $name, $permissions));
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
		 * Function: find
		 * Grab all groups that match the passed options.
		 *
		 * Returns:
		 * An array of <Group>s from the result.
		 */
		static function find($options = array()) {
			return parent::search(get_class(), $options);
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
			return SQL::current()->select("groups", $column, "`id` = :id", "`id` desc", array(":id" => $group_id))->fetchColumn();
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

			$check = $sql->query("select `name` from `__permissions` where `name` = '".$name."'")->fetchColumn();

			if ($check == $name)
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
			$sql = SQL::current();
			$sql->query("delete from `__permissions` where `name` = '".$name."'");
		}

		/**
		 * Function: user_count
		 * Returns the amount of users in a given group.
		 *
		 * Parameters:
		 *     $group_id - The group ID.
		 */
		static function count_users($group_id) {
			$sql = SQL::current();
			$get_count = $sql->query("select count(`id`) from `__users`
			                          where `group_id` = :id",
			                         array(
			                             ":id" => $group_id
			                         ));
			$count = $get_count->fetchColumn();
			return $count;
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
			echo $before.'<a href="'.$config->url.'/admin/?action=edit_group&amp;id='.$this->id.'" title="Edit" class="group_edit_link" id="group_edit_'.$this->id.'">'.$text.'</a>'.$after;
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
			echo $before.'<a href="'.$config->url.'/admin/?action=delete_group&amp;id='.$this->id.'" title="Delete" class="group_delete_link" id="group_delete_'.$this->id.'">'.$text.'</a>'.$after;
		}
	}
