<?php
	/**
	 * Class: Trigger
	 * Controls and keeps track of all of the Triggers and events.
	 */
	class Trigger {
		private $called = array();
		public $priorities = array();
		private $modified = array();

		private function __construct() {}

		/**
		 * Function: cmp
		 * Sorts actions by priority when used with usort.
		 */
		function cmp($a, $b) {
			if (empty($a) or empty($b)) return 0;
			return ($a["priority"] < $b["priority"]) ? -1 : 1 ;
		}

		/**
		 * Function: call
		 * Calls a trigger, passing the $arg to any actions for it.
		 * If $arg is an array, the actions are called with call_user_func_array.
		 *
		 * Parameters:
		 *     $name - The name of the trigger.
		 *     $arg - Arguments to pass to the actions.
		 *     $array - If $arg is an array, should it be passed as multiple values or a single array?
		 */
		public function call($name, $arg = null, $array = true) {
			global $modules;
			if (!isset($modules)) return;

			$caller = (is_array($arg) and $array) ? "call_user_func_array" : "call_user_func" ;

			$this->called[$name] = array();
			if (isset($this->priorities[$name])) { # Predefined priorities?
				usort($this->priorities[$name], array($this, "cmp"));

				foreach ($this->priorities[$name] as $action) {
					$caller($action["function"], $arg);
					$this->called[$name][] = $action["function"];
				}
			}

			foreach ($modules as $module)
				if (!in_array(array($module, $name), $this->called[$name]) and is_callable(array($module, $name)))
					$caller(array($module, $name), $arg);
		}

		/**
		 * Function: filter
		 * Filters a string or array through a trigger's actions.
		 * Similar to <call>, except this is stackable and is intended to
		 * modify something instead of inject code.
		 *
		 * Parameters:
		 *     $name - The name of the trigger.
		 *     $target - Arguments to pass to the actions.
		 *     $arguments - Argument(s) to pass to the filter function.
		 *
		 * Returns:
		 *     $target, filtered through any/all actions for the trigger $name.
		 */
		public function filter(&$target, $name) {
			if (is_array($name)) {
				foreach ($name as $filter)
					$this->filter($target, $filter);

				return;
			}

			global $modules;
			if (!isset($modules) or (isset($this->exists[$name]) and !$this->exists[$name]) or !$this->exists($name))
				return $target;

			$arguments = func_get_args();
			array_shift($arguments);
			array_shift($arguments);
			array_unshift($arguments, $target);

			$this->called[$name] = array();

			if (isset($this->priorities[$name])) { # Predefined priorities?
				usort($this->priorities[$name], array($this, "cmp"));

				foreach ($this->priorities[$name] as $action) {
					$this->modified[$name] = call_user_func_array($action["function"], $arguments);
					$this->called[$name][] = $action["function"];
				}
			}

			foreach ($modules as $module)
				if (!in_array(array($module, $name), $this->called[$name]) and is_callable(array($module, $name)))
					$this->modified[$name] = call_user_func_array(array($module, $name), $arguments);

			$this->modified[$name] = null;

			return $target = $this->modified($name, $target);
		}

		/**
		 * Function: modified
		 * A little helper function for <filter>.
		 */
		function modified($name, $target) {
			return (!isset($this->modified[$name])) ? $target : $this->modified[$name] ;
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
			if (isset($this->exists[$name]))
				return $this->exists[$name];

			global $modules;

			foreach (Config::current()->enabled_modules as $module)
				if (is_callable(array($modules[$module], $name)))
					return $this->exists[$name] = true;

			if (isset($this->priorities[$name]))
				return $this->exists[$name] = true;

			$this->exists[$name] = false;
		}

		/**
		 * Function: current
		 * Returns a singleton reference to the current connection.
		 */
		public static function & current() {
			static $instance = null;
			return $instance = (empty($instance)) ? new self() : $instance ;
		}
	}
	$trigger = Trigger::current();
