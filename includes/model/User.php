<?php
	/**
	 * Class: User
	 * The model for the Users SQL table.
	 */
	class User {
		/**
		 * Function: load
		 * Loads a given user into the <User> class.
		 * 
		 * Parameters:
		 * 	$user_id - The user ID to load. If no user is given, it defaults to the $current_user.
		 * 						 If they are not logged in and no user ID is given, nothing happens.
		 */
		function load($user_id = null, $password = null) {
			global $current_user;
			if (!XML_RPC and (empty($_COOKIE['chyrp_user_id']) or empty($_COOKIE['chyrp_password']))) return false;
			fallback($user_id, $current_user);
			if (empty($user_id)) return;
			fallback($password, $_COOKIE['chyrp_password']);

			$sql = SQL::current();
			$result = $sql->query("select * from `".$sql->prefix."users`
			                        where
			                        	`id` = :id and
			                        	`password` = :password",
			                        array(
			                       		":id" => $user_id,
			                       		":password" => $password
			                        ))->fetch();

			if (!$result)
				return;

			foreach ($result as $key => $val)
				if (!is_int($key))
					$this->$key = $val;
		}

		/**
		 * Function: authenticate
		 * Checks to see if a given Login and Password match a user in the database.
		 * 
		 * Parameters:
		 * 	$login - The Login to check.
		 * 	$password - The matching Password to check.
		 * 
		 * Returns:
		 * 	true - if a match is found.
		 */
		function authenticate($login, $password) {
			if (isset($this->id)) return true;

			$sql = SQL::current();
			$check_user = $sql->query("select `id` from `{$sql->prefix}users`
			                           where
			                           	`login` = :login and
			                           	`password` = :password",
			                          array(
			                          	":login" => $login,
			                          	":password" => $password
			                          ));
			return ($check_user->fetchColumn());
		}

		/**
		 * Function: logged_in
		 * Checks to see if the current visitor is logged in. If Cookies are set, it validates them to make sure.
		 * 
		 * Returns:
		 * 	true - if they are logged in with a valid Username and Password.
		 */
		function logged_in() {
			if (!XML_RPC and (empty($_COOKIE['chyrp_user_id']) or empty($_COOKIE['chyrp_password']))) return false;
			if (isset($this->id)) return true;

			$sql = SQL::current();
			$check_user = $sql->query("select count(`id`) from `".$sql->prefix."users`
			                           where
			                           	`id` = :id and
			                           	`password` = :password",
			                          array(
			                          	":id" => $_COOKIE['chyrp_user_id'],
			                          	":password" => $_COOKIE['chyrp_password']
			                          ));
			return ($check_user->fetchColumn() == 1);
		}

		/**
		 * Function: info
		 * Grabs a specified column from a users SQL row.
		 * 
		 * Parameters:
		 * 	$column - The name of the SQL column.
		 * 	$user_id - The user ID to grab from. If not given, defaults to $current_user.
		 * 	$fallback - What to display if the result is empty.
		 * 
		 * Returns:
		 * 	false - if $user_id isn't set and they aren't logged in.
		 * 	SQL result - if the SQL result isn't empty.
		 * 	$fallback - if the SQL result is empty.
		 */
		function info($column, $user_id = null, $fallback = null) {
			global $current_user;
			$user = (is_null($user_id)) ? $current_user : $user_id ;
			if (isset($this->id) and $this->id == $user) return ($this->$column == "") ? $fallback : $this->$column ;

			$sql = SQL::current();
			$grab_info = $sql->query("select `".$column."` from `".$sql->prefix."users`
			                          where `id` = :id",
			                         array(
			                         	":id" => $user
			                         ));
			if ($grab_info->rowCount() == 1)
				return ($grab_info->fetchColumn() == "") ? $fallback : $grab_info->fetchColumn() ;
			return $fallback;
		}

		/**
		 * Function: can
		 * Checks to see if a user can perform a specified function.
		 * 
		 * Parameters:
		 * 	$function - The permission name from their <Group>.
		 * 	$user_id - The user ID to check. If not given, defaults to $current_user or the Guest group.
		 * 
		 * Returns:
		 * 	true - if their group can perform the specified function.
		 */
		function can($function, $user_id = null) {
			global $group, $current_user;
			fallback($user_id, $current_user);
			$config = Config::current();
			$sql = SQL::current();

			if (is_null($user_id) and ($this->logged_in() and $group->id == $this->group_id) or (!$this->logged_in() and $group->id == $config->guest_group))
				return isset($group->$function);

			$group_id = (!$this->logged_in()) ? $config->guest_group : $this->info("group_id", $user_id) ;
			$permissions = $sql->query("select `permissions` from `".$sql->prefix."groups`
			                            where `id` = :id",
			                           array(
			                           	":id" => $group_id
			                           ))->fetchColumn();
			$permissions = Spyc::YAMLLoad($permissions);

			return in_array($function, $permissions);
		}

		/**
		 * Function: add
		 * Adds a user to the database with the passed username, password, and e-mail.
		 * 
		 * Calls the add_user trigger with the inserted ID.
		 * 
		 * Parameters:
		 * 	$login - The Login for the new user.
		 * 	$password - The Password for the new user. Don't MD5 this, it's done in the function.
		 * 	$email - The E-Mail for the new user.
		 * 
		 * Returns:
		 * 	$id - The newly created users ID.
		 * 
		 * See Also:
		 * 	<update>
		 */
		function add($login, $password, $email, $full_name = '', $website = '', $group_id = null) {
			$config = Config::current();
			$sql = SQL::current();
			$sql->query("insert into `".$sql->prefix."users`
			             (`login`, `password`, `email`, `full_name`, `website`, `group_id`, `joined_at`)
			             values
			             (:login, :password, :email, :full_name, :website, :group_id, :joined_at)",
			            array(
			            	":login" => strip_tags($login),
			            	":password" => md5($password),
			            	":email" => strip_tags($email),
			            	":full_name" => strip_tags($full_name),
			            	":website" => strip_tags($website),
			            	":group_id" => ($group_id) ? intval($group_id) : $config->default_group,
			            	":joined_at" => datetime()
			            ));
			$id = $sql->db->lastInsertId();
			$trigger = Trigger::current();
			$trigger->call("add_user", $id);
			return $id;
		}

		/**
		 * Function: update
		 * Updates a user with the given login, password, full name, e-mail, website, and <Group> ID.
		 * 
		 * Passes all of the arguments to the update_user trigger.
		 * 
		 * Parameters:
		 * 	$user_id - The user to update.
		 * 	$login - The new Login to set.
		 * 	$password - The new Password to set.
		 * 	$full_name - The new Full Name to set.
		 * 	$email - The new E-Mail to set.
		 * 	$website - The new Website to set.
		 * 	$group_id - The new <Group> to set.
		 * 
		 * See Also:
		 * 	<add>
		 */
		function update($user_id, $login, $password, $full_name, $email, $website, $group_id) {
			$sql = SQL::current();
			$sql->query("update `".$sql->prefix."users`
			             set
			             	`login` = :login,
			             	`password` = :password,
			             	`full_name` = :full_name,
			             	`email` = :email,
			             	`website` = :website,
			             	`group_id` = :group_id
			             where `id` = :id",
			            array(
			            	":login" => $login,
			            	":password" => $password,
			            	":full_name" => $full_name,
			            	":email" => $email,
			            	":website" => $website,
			            	":group_id" => $group_id,
			            	":id" => $user_id
			            ));
			$trigger = Trigger::current();
			$trigger->call("update_user", array($user_id, $login, $password, $full_name, $email, $website, $group_id));
		}

		/**
		 * Function: delete
		 * Deletes a given user. Calls the "delete_user" trigger with the users ID.
		 * 
		 * Parameters:
		 * 	$user_id - The user to delete.
		 */
		function delete($user_id) {
			$trigger = Trigger::current();
			$trigger->call("delete_user", $user_id);

			$sql = SQL::current();
			$sql->query("delete from `".$sql->prefix."users`
			             where `id` = :id",
			            array(
			            	":id" => $user_id
			            ));
		}

		/**
		 * Function: edit_link
		 * Outputs an edit link for the given user ID, if they <can> edit_user.
		 * 
		 * Parameters:
		 * 	$user_id - The user ID for the link.
		 * 	$text - The text to show for the link.
		 * 	$before - If the link can be shown, show this before it.
		 * 	$after - If the link can be shown, show this after it.
		 */
		function edit_link($user_id, $text = null, $before = null, $after = null){
			if (!$this->can('edit_user')) return;
			fallback($text, __("Edit"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=edit&amp;sub=user&amp;id='.$user_id.'" title="Edit" class="user_edit_link" id="user_edit_'.$user_id.'">'.$text.'</a>'.$after;
		}

		/**
		 * Function: delete_link
		 * Outputs a delete link for the given user ID, if they <can> delete_user.
		 * 
		 * Parameters:
		 * 	$user_id - The user ID for the link.
		 * 	$text - The text to show for the link.
		 * 	$before - If the link can be shown, show this before it.
		 * 	$after - If the link can be shown, show this after it.
		 */
		function delete_link($user_id, $text = null, $before = null, $after = null){
			if (!$this->can('delete_user')) return;
			fallback($text, __("Delete"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=delete&amp;sub=user&amp;id='.$user_id.'" title="Delete" class="user_delete_link" id="user_delete_'.$user_id.'">'.$text.'</a>'.$after;
		}

		function get_viewable_statuses($draft = false) {
			$statuses = array('public');
			if ($this->logged_in()) $statuses[] = 'registered_only';
			if ($this->can('view_private')) $statuses[] = 'private';
			if ($draft and $this->can('view_draft')) $statuses[] = 'draft';
			$sql = SQL::current();
			foreach ($statuses as & $status)
				$status = $sql->quote($status);
			return $statuses;
		}
	}
	$user = new User();
