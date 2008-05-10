<?php
	/**
	 * Class: User
	 * The model for the Users SQL table.
	 */
	class User extends Model {
		public $no_results = false;
		public $can = array();

		/**
		 * Function: __construct
		 * Grabs the specified user and injects it into the <User> class.
		 *
		 * Parameters:
		 *     $user_id - The user's unique ID.
		 *     $options - An array of options:
		 *         where: A SQL query to grab the user by.
		 *         params: Parameters to use for the "where" option.
		 *         read_from: An associative array of values to load into the <User> class.
		 */
		public function __construct($user_id, $options = array()) {
			parent::grab($this, $user_id, $options);

			if (!$this->no_results)
				foreach ($this->group()->permissions as $permission)
					$this->can[$permission] = true;
		}

		/**
		 * Function: authenticate
		 * Checks to see if a given Login and Password match a user in the database.
		 *
		 * Parameters:
		 *     $login - The Login to check.
		 *     $password - The matching Password to check.
		 *
		 * Returns:
		 *     true - if a match is found.
		 */
		static function authenticate($login, $password) {
			$check = new self(null, array("where" => array("`login` = :login", "`password` = :password"),
			                              "params" => array(":login" => $login, ":password" => $password)));
			return !$check->no_results;
		}

		/**
		 * Function: info
		 * Grabs a specified column from a user's SQL row.
		 *
		 * Parameters:
		 *     $column - The name of the SQL column.
		 *     $user_id - The user ID to grab from.
		 *
		 * Returns:
		 *     SQL result - if the SQL result isn't empty.
		 */
		static function info($column, $user_id) {
			return SQL::current()->select("users", $column, "`id` = :id", "`id` desc", array(":id" => $user_id))->fetchColumn();
		}

		/**
		 * Function: add
		 * Adds a user to the database with the passed username, password, and e-mail.
		 *
		 * Calls the add_user trigger with the inserted ID.
		 *
		 * Parameters:
		 *     $login - The Login for the new user.
		 *     $password - The Password for the new user. Don't MD5 this, it's done in the function.
		 *     $email - The E-Mail for the new user.
		 *
		 * Returns:
		 *     $id - The newly created users ID.
		 *
		 * See Also:
		 *     <update>
		 */
		static function add($login, $password, $email, $full_name = '', $website = '', $group_id = null) {
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

			return new self($id);
		}

		/**
		 * Function: update
		 * Updates the user with the given login, password, full name, e-mail, website, and <Group> ID.
		 *
		 * Passes all of the arguments to the update_user trigger.
		 *
		 * Parameters:
		 *     $login - The new Login to set.
		 *     $password - The new Password to set.
		 *     $full_name - The new Full Name to set.
		 *     $email - The new E-Mail to set.
		 *     $website - The new Website to set.
		 *     $group_id - The new <Group> to set.
		 *
		 * See Also:
		 *     <add>
		 */
		public function update($login, $password, $full_name, $email, $website, $group_id) {
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
			                ":id" => $this->id
			            ));
			$trigger = Trigger::current();
			$trigger->call("update_user", array($this->id, $login, $password, $full_name, $email, $website, $group_id));
		}

		/**
		 * Function: delete
		 * Deletes a given user. Calls the "delete_user" trigger and passes the <User> as an argument.
		 *
		 * Parameters:
		 *     $id - The user to delete.
		 */
		static function delete($id) {
			parent::destroy(get_class(), $id);
		}

		/**
		 * Function: find
		 * Grab all users that match the passed options.
		 *
		 * Returns:
		 * An array of <User>s from the result.
		 */
		static function find($options = array()) {
			return parent::search(get_class(), $options);
		}

		/**
		 * Function: group
		 * Returns a user's group. Example: $user->group()->can("do_something")
		 */
		public function group() {
			return new Group($this->group_id);
		}

		/**
		 * Function: edit_link
		 * Outputs an edit link for the user, if they can edit_user.
		 *
		 * Parameters:
		 *     $text - The text to show for the link.
		 *     $before - If the link can be shown, show this before it.
		 *     $after - If the link can be shown, show this after it.
		 */
		public function edit_link($text = null, $before = null, $after = null) {
			fallback($text, __("Edit"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=edit_user&amp;id='.$this->id.'" title="Edit" class="user_edit_link" id="user_edit_'.$this->id.'">'.$text.'</a>'.$after;
		}

		/**
		 * Function: delete_link
		 * Outputs an delete link for the user, if they can delete_user.
		 *
		 * Parameters:
		 *     $text - The text to show for the link.
		 *     $before - If the link can be shown, show this before it.
		 *     $after - If the link can be shown, show this after it.
		 */
		public function delete_link($text = null, $before = null, $after = null) {
			fallback($text, __("Delete"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=delete_user&amp;id='.$this->id.'" title="Delete" class="user_delete_link" id="user_delete_'.$this->id.'">'.$text.'</a>'.$after;
		}
	}
