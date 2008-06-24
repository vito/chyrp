<?php
	/**
	 * Class: Timestamp
	 * An improved DateTime.
	 */
	class Timestamp extends DateTime {
		/**
		 * Function: __construct
		 * Same as DateTime::__construct, except the Timezone value doesn't have to be a
		 * DateTimeZone object, and falls back onto the Chyrp timezone setting if unspecified.
		 *
		 * Parameters:
		 *     $when - A new time or a modification (anything allowed by strtotime).
		 *     $timezone - A valid PHP timezone. Defaults to Config->timezone.
		 */
		public function __construct($when, $timezone = null) {
			fallback($timezone, Config::current()->timezone);
			list($this->when, $this->timezone) = array($when, $timezone);
			parent::__construct($when, new DateTimeZone($timezone));
		}

		/**
		 * Function:
		 * Returns a new Timestamp object based on the modifications provided.
		 * Similar to DateTime::change, but this isn't destructive.
		 *
		 * Parameters:
		 *     $when - A new time or a modification (anything allowed by strtotime).
		 */
		public function alter($when) {
			$copy = new self($this->when, $this->timezone);
			$copy->modify($when);
			$copy->when = $copy->format("c");
			return $copy;
		}
	}
