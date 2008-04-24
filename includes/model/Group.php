<?php
	$current_group = array("id" => 0);

	/**
	 * Class: Group
	 * The model for the Groups SQL table.
	 */
	class Group {
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
			global $current_group;

			$where = fallback($options["where"], "", true);
			$params = isset($options["params"]) ? $options["params"] : array();
			$read_from = (isset($options["read_from"])) ? $options["read_from"] : array() ;

			$sql = SQL::current();
			if ((!empty($read_from) && $read_from))
				$read = $read_from;
			elseif (isset($group_id) and $group_id == $current_group["id"])
				$read = $current_group;
			elseif (!empty($where))
				$read = $sql->select("groups",
				                     "*",
				                     $where,
				                     "id",
				                     $params,
				                     1)->fetch();
			else
				$read = $sql->select("groups",
				                     "*",
				                     "`id` = :groupid",
				                     "id",
				                     array(
				                         ":groupid" => $group_id
				                     ),
				                     1)->fetch();

			if (!count($read) or !$read)
				return $this->no_results = true;

			foreach ($read as $key => $val)
				if (!is_int($key))
					$this->$key = $current_group[$key] = $val;

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

			$sql->query("insert into `".$sql->prefix."groups`
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

			$sql->query("update `".$sql->prefix."groups` set `name` = :name, `permissions` = :permissions where `id` = :id", $params);

			$trigger = Trigger::current();
			$trigger->call("update_group", array($this, $name, $permissions));
		}

		/**
		 * Function: delete
		 * Deletes a given group. Calls the "delete_group" trigger with the group's ID.
		 *
		 * Parameters:
		 *     $group_id - The group to delete.
		 */
		static function delete($group_id) {
			$trigger = Trigger::current();
			if ($trigger->exists("delete_group"))
				$trigger->call("delete_group", new self($group_id));

			$sql = SQL::current();
			$sql->query("delete from `".$sql->prefix."groups`
			             where `id` = :id",
			            array(
			                ":id" => $group_id
			            ));
		}

		/**
		 * Function: info
		 * Grabs a specified column from a group's SQL row.
		 *
		 * Parameters:
		 *     $column - The name of the SQL column.
		 *     $group_id - The group ID to grab from.
		 *     $fallback - What to display if the result is empty.
		 *
		 * Returns:
		 *     SQL result - if the SQL result isn't empty.
		 *     $fallback - if the SQL result is empty.
		 */
		static function info($column, $group_id, $fallback = false) {
			global $current_group;

			if ($current_group["id"] == $group_id and isset($current_group[$column]))
				return $current_group[$column];

			$sql = SQL::current();
			$grab_column = $sql->select("groups",
			                            $column,
			                            "`id` = :id",
			                            "id",
			                            array(
			                                ':id' => $group_id
			                            ));
			return ($grab_column->rowCount() == 1) ? $grab_column->fetchColumn() : $fallback ;
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

			$check = $sql->query("select `name` from `".$sql->prefix."permissions` where `name` = '".$name."'")->fetchColumn();

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
			$sql->query("delete from `".$sql->prefix."permissions` where `name` = '".$name."'");
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
			$get_count = $sql->query("select count(`id`) from `".$sql->prefix."users`
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
			echo $before.'<a href="'.$config->url.'/admin/?action=edit&amp;sub=group&amp;id='.$this->id.'" title="Edit" class="group_edit_link" id="group_edit_'.$this->id.'">'.$text.'</a>'.$after;
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
			echo $before.'<a href="'.$config->url.'/admin/?action=delete&amp;sub=group&amp;id='.$this->id.'" title="Delete" class="group_delete_link" id="group_delete_'.$this->id.'">'.$text.'</a>'.$after;
		}
	}
