<?php
	require "lib/smartypants.php";

	class Smartypants extends Module {
		public function __construct() {
			$this->setPriority("markup_post_text", 9);
		}
		static function markup_post_text($text) {
			return Smartypants($text);
		}
		static function markup_page_text($text) {
			return Smartypants($text);
		}
		static function markup_comment_text($text) {
			return Smartypants($text);
		}
	}
	new Smartypants();
