<?php
	/**
	 * Class: Flash
	 * Stores messages (notice, warning, message) to display to the user after a redirect.
	 */
	class Flash {
		private $notices = array();
		private $warnings = array();
		private $messages = array();

		private $all = array();

		static $exists = false;

		/**
		 * Function: __construct
		 * Removes empty notification variables from the session.
		 */
		private function __construct() {
			foreach (array("messages", "notices", "warnings") as $type)
				if (isset($_SESSION[$type]) and empty($_SESSION[$type]))
					unset($_SESSION[$type]);
		}

		/**
		 * Function: prepare
		 * Prepare the structure of the "flash" session value.
		 */
		static function prepare($type) {
			if (!isset($_SESSION))
				$_SESSION = array();

			if (!isset($_SESSION[$type]))
				$_SESSION[$type] = array();
		}

		/**
		 * Function: message
		 * Add a message (neutral) to the session.
		 *
		 * Parameters:
		 *     $message - Message to display.
		 *     $redirect_to - URL to redirect to after the message is stored.
		 */
		static function message($message, $redirect_to = null) {
			self::prepare("messages");

			$_SESSION['messages'][] = Trigger::current()->filter($message, "flash_message", $redirect_to);

			if (isset($redirect_to))
				redirect($redirect_to);
		}

		/**
		 * Function: notice
		 * Add a notice (positive) message to the session.
		 *
		 * Parameters:
		 *     $message - Message to display.
		 *     $redirect_to - URL to redirect to after the message is stored.
		 */
		static function notice($message, $redirect_to = null) {
			self::prepare("notices");

			$_SESSION['notices'][] = Trigger::current()->filter($message, "flash_notice_message", $redirect_to);

			if (isset($redirect_to))
				redirect($redirect_to);
		}

		/**
		 * Function: warning
		 * Add a warning (negative) message to the session.
		 *
		 * Parameters:
		 *     $message - Message to display.
		 *     $redirect_to - URL to redirect to after the message is stored.
		 */
		static function warning($message, $redirect_to = null) {
			self::prepare("warnings");

			$_SESSION['warnings'][] = Trigger::current()->filter($message, "flash_warning_message", $redirect_to);

			if (isset($redirect_to))
				redirect($redirect_to);
		}

		/**
		 * Function: messages
		 * Sets <Flash.$messages> to $_SESSION['messages'] and destroys the session value.
		 *
		 * Returns:
		 *     <Flash.$messages>
		 */
		public function messages() {
			return $this->serve("messages");
		}

		/**
		 * Function: notices
		 * Sets <Flash.$notices> to $_SESSION['notices'] and destroys the session value.
		 *
		 * Returns:
		 *     <Flash.$notices>
		 */
		public function notices() {
			return $this->serve("notices");
		}

		/**
		 * Function: warnings
		 * Sets <Flash.$warnings> to $_SESSION['warnings'] and destroys the session value.
		 *
		 * Returns:
		 *     <Flash.$warnings>
		 */
		public function warnings() {
			return $this->serve("warnings");
		}

		/**
		 * Function: all
		 * Returns an associative array of all messages and destroys their session values.
		 *
		 * Returns:
		 *     <Flash.$all>
		 */
		public function all() {
			return array("messages" => $this->messages(),
			             "notices" => $this->notices(),
			             "warnings" => $this->warnings());
		}

		/**
		 * Function: serve
		 * Serves a message of type $type and destroys it from the session.
		 */
		public function serve($type) {
			if (!empty($_SESSION[$type]))
				self::$exists = true;

			if (isset($_SESSION[$type])) {
				$this->$type = $_SESSION[$type];
				$_SESSION[$type] = array();
			}

			return $this->$type;
		}

		/**
		 * Function: exists
		 * Checks for flash messages.
		 *
		 * Parameters:
		 *     $type - message, notice, or warning.
		 */
		static function exists($type = null) {
			if (self::$exists)
				return self::$exists;

			if (isset($type))
				return self::$exists = !empty($_SESSION[pluralize($type)]);
			else
				foreach (array("messages", "notices", "warnings") as $type)
					if (!empty($_SESSION[$type]))
						return self::$exists = true;

			return false;
		}

		/**
		 * Function: current
		 * Returns a singleton reference to the current class.
		 */
		public static function & current() {
			static $instance = null;
			return $instance = (empty($instance)) ? new self() : $instance ;
		}
	}
