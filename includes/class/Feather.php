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
			self::$filters[get_class($this)][] = array("field" => $field, "name" => $name);
		}

		/**
		 * Function: customFilter
		 * Allows a Feather to apply its own filter to a specified field.
		 */
		protected function customFilter($field, $name, $priority = 10) {
			self::$custom_filters[get_class($this)][] = array("field" => $field, "name" => $name);
		}

		/**
		 * Function: respondTo
		 * Allows a Feather to respond to a Trigger as a Module would.
		 */
		protected function respondTo($name, $function, $priority = 10) {
			Trigger::current()->priorities[$name][] = array("priority" => $priority, "function" => array(get_class($this), $function));
		}

		/**
		 * Function: setField
		 * Sets the feather's fields for creating/editing posts with that feather.
		 */
		protected function setField($options) {
			fallback($options["classes"], array());

			if (isset($options["class"]))
				$options["classes"][] = $options["class"];

			if (isset($options["preview"]) and $options["preview"])
				$options["classes"][] = "preview_me";

			$this->fields[$options["attr"]] = $options;
		}
	}
