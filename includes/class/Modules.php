<?php
	/**
	 * Class: Modules
	 * Contains various functions, acts as the backbone for all modules.
	 */
	class Modules {
		/**
		 * Function: setPriority
		 * Sets the priority of an action for the module this function is called from.
		 */
		protected function setPriority($name, $priority) {
			Trigger::current()->priorities[$name][] = array("priority" => $priority, "function" => array($this, $name));
		}

		/**
		 * Function: addAlias
		 * Allows a module to respond to a trigger with multiple functions and custom priorities.
		 */
		protected function addAlias($name, $function, $priority = 10) {
			Trigger::current()->priorities[$name][] = array("priority" => $priority, "function" => array($this, $function));
		}
	}
