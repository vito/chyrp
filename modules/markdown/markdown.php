<?php
	require "lib/markdown.php";

	class Markdown extends Module {
		public function __construct() {
			$this->setPriority("markup_post_text", 9);
			$this->addAlias("markup_page_text", "markup_post_text");
			$this->addAlias("markup_comment_text", "markup_post_text");
		}
		static function markup_post_text($text) {
			return Markdown($text);
		}
	}
