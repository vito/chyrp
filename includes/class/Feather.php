<?php
	/**
	 * Class: Feather
	 * Contains various functions, acts as the backbone for all feathers.
	 */
	class Feather {
		static $filters = array();
		static $custom_filters = array();
		
		/**
		 * Function: setFilter
		 * Applies a filter to a specified field of the Feather.
		 */
		protected function setFilter($field, $name) {
			$class = get_class($this);
			self::$filters[$class][] = array("field" => $field, "name" => $name);
		}
		
		/**
		 * Function: customFilter
		 * Allows a Feather to apply its own filter to a specified field.
		 */
		protected function customFilter($field, $name, $priority = 10) {
			$class = get_class($this);
			self::$custom_filters[$class][] = array("field" => $field, "name" => $name);
		}
		
		/**
		 * Function: respondTo
		 * Allows a Feather to respond to a Trigger as a Module would.
		 */
		protected function respondTo($name, $function, $priority = 10) {
			$class = get_class($this);
			$trigger = Trigger::current();
			$trigger->priorities[$name][] = array("priority" => $priority, "function" => array($class, $function));
		}
	}
