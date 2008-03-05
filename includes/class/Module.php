<?php
	/**
	 * Class: Module
	 * Contains various functions, acts as the backbone for all modules.
	 */
	class Module {
		/**
		 * Function: setPriority
		 * Sets the priority of an action for the module this function is called from.
		 */
		protected function setPriority($name, $priority) {
			$class = get_class($this);
			$trigger = Trigger::current();
			$trigger->priorities[$name][] = array("priority" => $priority, "function" => array($class, $name));
		}
		
		/**
		 * Function: addAlias
		 * Allows a module to respond to a trigger with multiple functions and custom priorities.
		 */
		protected function addAlias($name, $function, $priority = 10) {
			$class = get_class($this);
			$trigger = Trigger::current();
			$trigger->priorities[$name][] = array("priority" => $priority, "function" => array($class, $function));
		}
	}
