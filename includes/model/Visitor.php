<?php
	class Visitor extends User {
		public $group;
		public $id = 0;

		public function __construct() {
			$config = Config::current();

			if (isset($_COOKIE['chyrp_user_id']) and isset($_COOKIE['chyrp_password']))
				if (User::authenticate(User::info("login", $_COOKIE['chyrp_user_id']), $_COOKIE['chyrp_password'])) {
					parent::__construct($_COOKIE['chyrp_user_id']);
					$this->group = new Group($this->group_id);
				}

			fallback($this->group, new Group($config->guest_group));
		}

		/**
		 * Function: current
		 * Returns a singleton reference to the current connection.
		 */
		public static function & current() {
			static $instance = null;
			if (empty($instance))
				$instance = new self();
			return $instance;
		}
	}
