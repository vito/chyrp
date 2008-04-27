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
		protected function setField($attr, $type, $label, $bookmarklet) {
			// switch($type) {
			// 	case "text":
			// 		$input = '<input class="text" type="text" name="'.$attr.'" value="${ post.'.$attr.' | escape }" id="'.$attr.'" />';
			// 		break;
			// 	case "text_block":
			// 		$input = '<textarea class="long" name="'.$attr.'" id="'.$attr.'">${ post.'.$attr.' | escape }</textarea>';
			// 		break;
			// 	case "file":
			// 		$input = '<input type="file" name="'.$attr.'" id="'.$attr.'" />';
			// 		break;
			// }
			$this->fields[$attr] = array("attr" => $attr, "type" => $type, "label" => $label, "bookmarklet" => $bookmarklet);
			#$fields = "<p>\n";
			#$fields.= "\t".'<label for="'.$attr.'">'.__($label, decamelize(get_class($this))).'</label>'."\n";
			#$fields.= "\t".$input."\n";
			#$fields.= "</p>\n";
		}
	}
