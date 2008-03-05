<?php
	require "lib/markdown.php";
	
	class Markdown extends Module {
		public function __construct() {
			$this->setPriority("markup_post_text", 9);
		}
		static function markup_post_text($text) {
			return Markdown($text);
		}
		static function markup_page_text($text) {
			return Markdown($text);
		}
		static function markup_comment_text($text) {
			return Markdown($text);
		}
	}
	new Markdown();
