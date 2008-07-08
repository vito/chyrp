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

		/**
		 * Function: __construct
		 * Prepare the structure of the "flash" session value.
		 */
		private function __construct() {
			fallback($_SESSION['flash'], array());
			fallback($_SESSION['flash']['notice'], array());
			fallback($_SESSION['flash']['warning'], array());
			fallback($_SESSION['flash']['message'], array());
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
			$_SESSION['flash']['messages'][] = Trigger::current()->filter($message, "flash_message");

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
			$_SESSION['flash']['notices'][] = Trigger::current()->filter($message, "flash_notice_message");

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
			$_SESSION['flash']['warnings'][] = Trigger::current()->filter($message, "flash_warning_message");

			if (isset($redirect_to))
				redirect($redirect_to);
		}

		/**
		 * Function: messages
		 * Sets <Flash.$messages> to $_SESSION['flash']['messages'] and destroys the session value.
		 *
		 * Returns:
		 *     <Flash.$messages>
		 */
		public function messages() {
			if (isset($_SESSION['flash']['messages'])) {
				$this->messages = $_SESSION['flash']['messages'];
				unset($_SESSION['flash']['messages']);
			}

			return $this->messages;
		}

		/**
		 * Function: notices
		 * Sets <Flash.$notices> to $_SESSION['flash']['notices'] and destroys the session value.
		 *
		 * Returns:
		 *     <Flash.$notices>
		 */
		public function notices() {
			if (isset($_SESSION['flash']['notices'])) {
				$this->notices = $_SESSION['flash']['notices'];
				unset($_SESSION['flash']['notices']);
			}

			return $this->notices;
		}

		/**
		 * Function: warnings
		 * Sets <Flash.$warnings> to $_SESSION['flash']['warnings'] and destroys the session value.
		 *
		 * Returns:
		 *     <Flash.$warnings>
		 */
		public function warnings() {
			if (isset($_SESSION['flash']['warnings'])) {
				$this->warnings = $_SESSION['flash']['warnings'];
				unset($_SESSION['flash']['warnings']);
			}

			return $this->warnings;
		}

		/**
		 * Function: all
		 * Returns an associative array of all messages and destroys their session values.
		 *
		 * Returns:
		 *     <Flash.$all>
		 */
		public function all() {
			if (isset($_SESSION['flash'])) {
				$this->all = $_SESSION['flash'];
				unset($_SESSION['flash']);
			}

			return $this->all;
		}

		/**
		 * Function: exists
		 * Checks for flash messages.
		 *
		 * Parameters:
		 *     $type - message, notice, or warning.
		 */
		static function exists($type = null) {
			if (isset($type))
				return !empty($_SESSION['flash'][pluralize($type)]);
			else
				foreach ($_SESSION['flash'] as $flash)
					if (!empty($flash))
						return true;
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
