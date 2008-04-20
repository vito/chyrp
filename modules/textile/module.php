<?php	
	require "lib/textile.php";
	
	class Textile extends Module {
		public function __construct() {
			$this->setPriority("markup_post_text", 9);
		}
		static function markup_post_text($text) {
			$textile = new Textile2();
			return $textile->TextileThis($text);
		}
	}
	new Textile();
