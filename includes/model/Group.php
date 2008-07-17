<?php
	/**
	 * Class: Group
	 * The Group model.
	 * See Also:
	 *     <Model>
	 */
	class Group extends Model {
		/**
		 * Function: __construct
		 * See Also:
		 *     <Model::grab>
		 */
		public function __construct($group_id = null, $options = array()) {
			parent::grab($this, $group_id, $options);

			if ($this->no_results)
				return false;

			$this->permissions = (!empty($this->permissions)) ? Horde_Yaml::load($this->permissions) : array() ;
		}

		/**
		 * Function: find
		 * See Also:
		 *     <Model::search>
		 */
		static function find($options = array(), $options_for_object = array()) {
			return parent::search(get_class(), $options, $options_for_object);
		}

		/**
		 * Function: can
		 * Checks if the group can perform the specified functions.
		 */
		public function can() {
			if ($this->no_results)
				return false;

			$functions = func_get_args();

			# OR comparison
			if (end($functions) !== true) {
				foreach ($functions as $function)
					if (in_array($function, $this->permissions)) return true;

				return false;
			}
			# AND comparison
			else {
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
			                       array(":name"  => $name,   ":permissions"  => Horde_Yaml::dump($permissions)));

			$group = new self($sql->latest());

			Trigger::current()->call("add_group", $group);

			return $group;
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
			if ($this->no_results)
				return false;

			$sql = SQL::current();
			$sql->update("groups", "id = :id",
			             array("name" => ":name", "permissions" => ":permissions"),
			             array(":name" => $name, ":permissions" => Horde_Yaml::dump($permissions), ":id" => $this->id));

			Trigger::current()->call("update_group", $this, $name, $permissions);
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
		 * Function: add_permission
		 * Adds a permission to the Groups table.
		 *
		 * Parameters:
		 *     $id - The ID for the permission, like "can_do_something".
		 *     $name - The name for the permission, like "Can Do Something". Defaults to the camelized ID while keeping spaces.
		 */
		static function add_permission($id, $name = null) {
			$sql = SQL::current();

			if ($sql->count("permissions", "id = :id", array(":id" => $id)))
				return; # Permission already exists.

			fallback($name, camelize($id, true));
			$sql->insert("permissions", array("id" => ":id", "name" => ":name"), array(":id" => $id, ":name" => $name));
		}

		/**
		 * Function: remove_permission
		 * Removes a permission from the Groups table.
		 *
		 * Parameters:
		 *     $id - The ID of the permission to remove.
		 */
		static function remove_permission($id) {
			SQL::current()->delete("permissions", "id = :id", array(":id" => $id));
		}

		/**
		 * Function: size
		 * Returns the amount of users in the.
		 */
		public function size() {
			if ($this->no_results)
				return false;

			return (isset($this->size)) ? $this->size :
			       $this->size = SQL::current()->count("users",
			                                           "group_id = :group_id",
			                                           array(":group_id" => $this->id)) ;
		}

		/**
		 * Function: members
		 * Returns all the members of the group.
		 */
		public function members() {
			if ($this->no_results)
				return false;

			return User::find(array("where" => "group_id = :group_id",
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
		public function edit_link($text = null, $before = null, $after = null) {
			if ($this->no_results or !Visitor::current()->group()->can("edit_group"))
				return false;

			fallback($text, __("Edit"));

			echo $before.'<a href="'.Config::current()->chyrp_url.'/admin/?action=edit_group&amp;id='.$this->id.'" title="Edit" class="group_edit_link edit_link" id="group_edit_'.$this->id.'">'.$text.'</a>'.$after;
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
			if ($this->no_results or !Visitor::current()->group()->can("delete_group"))
				return false;

			fallback($text, __("Delete"));

			echo $before.'<a href="'.Config::current()->chyrp_url.'/admin/?action=delete_group&amp;id='.$this->id.'" title="Delete" class="group_delete_link delete_link" id="group_delete_'.$this->id.'">'.$text.'</a>'.$after;
		}
	}
