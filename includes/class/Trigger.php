<?php
	/**
	 * Class: Trigger
	 * Controls and keeps track of all of the Triggers and events.
	 */

	/**
	 * Function: cmp
	 * Sorts actions by priority when used with usort.
	 */
	function cmp($a, $b) {
		if (empty($a) or empty($b)) return 0;
		return ($a["priority"] < $b["priority"]) ? -1 : 1 ;
	}

	class Trigger {
		private $called = array();
		public $priorities = array();
		private $modified_text = array();

		private function __construct() {}

		/**
		 * Function: call
		 * Calls a trigger, passing the $arg to any actions for it.
		 * If $arg is an array, the actions are called with call_user_func_array.
		 * This function also calls any theme Snippets that have $name as their function name.
		 *
		 * Parameters:
		 *     $name - The name of the trigger.
		 *     $arg - Arguments to pass to the actions.
		 *     $array - If $arg is an array, should it be passed as multiple values or a single array?
		 */
		public function call($name, $arg = null, $array = true) {
			global $snippet;
			$caller = (is_array($arg) and $array) ? "call_user_func_array" : "call_user_func" ;

			if (isset($this->priorities[$name])) { # Predefined priorities?
				usort($this->priorities[$name], "cmp");

				foreach ($this->priorities[$name] as $action) {
					$caller($action["function"], $arg);
					$this->called[] = $action["function"];
				}
			}

			$config = Config::current();
			foreach ($config->enabled_modules as $module) {
				$camelized = camelize($module);

				if (in_array(array($camelized, $name), $this->called))
					continue;

				if (is_callable(array($camelized, $name)))
					$caller(array($camelized, $name), $arg);
			}

			if (method_exists($snippet, $name))
				$caller(array($snippet, $name), $arg);
		}

		/**
		 * Function: filter
		 * Filters a string or array through a trigger's actions.
		 * Similar to <call>, except this is stackable and is intended to
		 * modify something instead of inject code.
		 *
		 * Parameters:
		 *     $name - The name of the trigger.
		 *     $arg - Arguments to pass to the actions.
		 *     $array - If $arg is an array, should it be passed as multiple arguments?
		 *
		 * Returns:
		 *     $arg, filtered through any/all actions for the trigger $name.
		 */
		public function filter($name, $arg = "", $array = false) {
			global $snippet;
			$caller = (is_array($arg) and $array) ? "call_user_func_array" : "call_user_func" ;

			if (isset($this->priorities[$name])) { # Predefined priorities?
				usort($this->priorities[$name], "cmp");
				foreach ($this->priorities[$name] as $action) {
					$this->modified_text[$name] = $caller($action["function"], $this->modified($name, $arg));
					$this->called[] = $action["function"];
				}
			}

			$config = Config::current();
			foreach ($config->enabled_modules as $module) {
				$camelized = camelize($module);

				if (in_array(array($camelized, $name), $this->called))
					continue;

				if (is_callable(array($camelized, $name))) {
					$this->modified_text[$name] = $caller(array($camelized, $name), $this->modified($name, $arg));
				}
			}

			if (method_exists($snippet, $name))
				$this->modified_text[$name] = $caller(array($snippet, $name), $this->modified($name, $arg));

			$final = $this->modified($name, $arg);

			$this->modified_text[$name] = null;

			return $final;
		}

		/**
		 * Function: modified
		 * A little helper function for <filter>.
		 */
		function modified($name, $arg) {
			return (!isset($this->modified_text[$name])) ? $arg : $this->modified_text[$name] ;
		}

		/**
		 * Function: remove
		 * Unregisters a given $action from a $trigger.
		 *
		 * Parameters:
		 *     $trigger - The trigger to unregister from.
		 *     $action - The action name.
		 */
		public function remove($trigger, $action) {
			foreach ($this->actions[$trigger] as $index => $func) {
				if ($func == $action) {
					unset($this->actions[$trigger][$key]);
					return;
				}
			}
			$this->actions[$trigger]["disabled"][] = $action;
		}

		/**
		 * Function: exists
		 * Checks if there are any actions for a given $trigger.
		 *
		 * Parameters:
		 *     $trigger - The trigger name.
		 *
		 * Returns:
		 *     true - if there are actions for the trigger.
		 */
		public function exists($name) {
			global $snippet;
			$config = Config::current();
			foreach ($config->enabled_modules as $module)
				if (is_callable(array(camelize($module), $name)))
					return true;

			if (method_exists($snippet, $name))
				return true;

			if (isset($this->priorities[$name]))
				return true;
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
	$trigger = Trigger::current();
