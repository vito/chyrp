<?php
	/**
	 * Class: Feathers
	 * Contains various functions, acts as the backbone for all feathers.
	 */
	class Feathers {
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
		protected function respondTo($name, $function = null, $priority = 10) {
			fallback($function, $name);
			Trigger::current()->priorities[$name][] = array("priority" => $priority, "function" => array($this, $function));
		}

		/**
		 * Function: setField
		 * Sets the feather's fields for creating/editing posts with that feather.
		 *
		 * Options:
		 *     attr - The technical name for the field. Think $post->attr.
		 *     type - The field type. (text, file, text_block, or select)
		 *     label - The label for the field.
		 *     preview - Is this field previewable? (Only use one per feather.)
		 *     optional - Is this field optional?
		 *     bookmarklet - What to fill this field by in the bookmarklet.
		 *                   url or page_url - The URL of the page they're viewing when they open the bookmarklet.
		 *                   title or page_title - The title of the page they're viewing when they open the bookmarklet.
		 *                   selection - Their selection on the page they're viewing when they open the bookmarklet.
		 */
		protected function setField($options) {
			fallback($options["classes"], array());

			if (isset($options["class"]))
				$options["classes"][] = $options["class"];

			if (isset($options["preview"]) and $options["preview"])
				$options["classes"][] = "preview_me";

			$this->fields[$options["attr"]] = $options;
		}

		/**
		 * Function: bookmarkletSelected
		 * If $boolean is true, the Feather that this function is called from will be selected when they open the Bookmarklet.
		 */
		protected function bookmarkletSelected($boolean = true) {
			if ($boolean)
				AdminController::current()->selected_bookmarklet = $this->safename;
		}
	}
