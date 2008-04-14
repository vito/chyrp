<?php
	/**
	 * Class: Group
	 * The model for the Groups SQL table.
	 */
	class Group {
		/**
		 * Function: load
		 * Loads a given user into the <Group> class.
		 * 
		 * Parameters:
		 * 	$group_id - The group ID to load. If no group is given, it defaults to the $current_user's group ID. 
		 * 						 	If they are not logged in and no group ID is given, it will load the Guest group.
		 */
		function load($group_id = null) {
			global $user;
			$config = Config::current();
			$group = (isset($group_id)) ? $group_id : (($user->logged_in()) ? $user->group_id : $config->guest_group) ;
			if (!isset($group)) return;
			
			$sql = SQL::current();
			foreach ($sql->query("select * from `".$sql->prefix."groups`
			                      where `id` = :id",
			                     array(
			                     	":id" => $group
			                     ))->fetch() as $key => $val)
				if (!is_int($key))
					$this->$key = $val;
			
			$permissions = Spyc::YAMLLoad($this->permissions);
			foreach ($permissions as $name, $bool)
				$this->$name = $bool;
		}
		
		/**
		 * Function: add
		 * Adds a group to the database with the passed Name and Permissions array.
		 * 
		 * Calls the add_group trigger with the inserted ID, name, and permissions.
		 * 
		 * Parameters:
		 * 	$name - The group's Name
		 * 	$permissions - An array of all of the permissions.
		 * 
		 * See Also:
		 * 	<update>
		 */
		function add($name, $permissions) {
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
		 * 	$group_id - The group to update.
		 * 	$name - The new Name to set.
		 * 	$permissions - An array of the new permissions to set.
		 */
		function update($group_id, $name, $permissions) {
			$sql = SQL::current();
			
			$fields = array("`name`" => ":name", "`permissions`" => ":permissions");
			$params = array(":name" => $name, ":permissions" => Spyc::YAMLDump($permissions), ":id" => $group_id);
			
			$sql->query("update `".$sql->prefix."groups`
			             set ".implode(",", $fields)."
			             where `id` = :id",
			            $params);
			
			$trigger = Trigger::current();
			$trigger->call("update_group", array($group_id, $name, $permissions));
		}
		
		/**
		 * Function: delete
		 * Deletes a given group. Calls the "delete_group" trigger with the groups ID.
		 * 
		 * Parameters:
		 * 	$group_id - The group to delete.
		 */
		function delete($group_id) {
			$trigger = Trigger::current();
			$trigger->call("delete_group", $group_id);
			
			$sql = SQL::current();
			$sql->query("delete from `".$sql->prefix."groups`
			             where `id` = :id",
			            array(
			            	":id" => $group_id
			            ));
		}
		
		/**
		 * Function: add_permission
		 * Adds a permission to the Groups table.
		 * 
		 * Parameters:
		 * 	$name - The name of the permission to add. The naming scheme is simple; for example, 
		 * 					"code_in_comments" gets converted to "Code In Comments" at the group editing page.
		 */
		function add_permission($name) {
			$sql = SQL::current();
			
			$permissions = $sql->query("select `name` `".$sql->prefix."permissions`")->fetch();
			
			if (in_array($name, $permissions))
				return; # Permission already exists.
			
			$sql->insert("permissions", array("name" => ":name"), array(":name" => $name));
		}
		
		/**
		 * Function: remove_permission
		 * Removes a permission from the Groups table.
		 * 
		 * Parameters:
		 * 	$name - The permission name to remove.
		 */
		function remove_permission($name) {
			$sql = SQL::current();
			$sql->query("delete from `".$sql->prefix."permissions` where `name` = '".$name."'");
		}
		
		/**
		 * Function: user_count
		 * Returns the amount of users in a given group.
		 * 
		 * Parameters:
		 * 	$group_id - The group ID.
		 */
		function user_count($group_id) {
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
		 * Outputs an edit link for the given group ID, if the <User.can> edit_group.
		 * 
		 * Parameters:
		 * 	$group_id - The group ID for the link.
		 * 	$text - The text to show for the link.
		 * 	$before - If the link can be shown, show this before it.
		 * 	$after - If the link can be shown, show this after it.
		 */
		function edit_link($group_id, $text = null, $before = null, $after = null){
			global $user;
			if (!$user->can("edit_group")) return;
			fallback($text, __("Edit"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=edit&amp;sub=group&amp;id='.$group_id.'" title="Edit" class="group_edit_link" id="group_edit_'.$group_id.'">'.$text.'</a>'.$after;
		}
		
		/**
		 * Function: delete_link
		 * Outputs an delete link for the given group ID, if the <User.can> delete_group.
		 * 
		 * Parameters:
		 * 	$group_id - The group ID for the link.
		 * 	$text - The text to show for the link.
		 * 	$before - If the link can be shown, show this before it.
		 * 	$after - If the link can be shown, show this after it.
		 */
		function delete_link($group_id, $text = null, $before = null, $after = null){
			global $user;
			if (!$user->can("delete_group")) return;
			fallback($text, __("Delete"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=delete&amp;sub=group&amp;id='.$group_id.'" title="Delete" class="group_delete_link" id="group_delete_'.$group_id.'">'.$text.'</a>'.$after;
		}
	}
	$group = new Group();
