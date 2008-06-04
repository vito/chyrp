<?php
	require "lib/textile.php";

	class Textilize extends Module {
		public function __construct() {
			$this->textile = new Textile();
			$this->setPriority("markup_post_text", 9);
			$this->addAlias("markup_page_text", "markup_post_text");
			$this->addAlias("markup_comment_text", "markup_post_text");
		}
		public function markup_post_text($text) {
			return $this->textile->TextileThis($text);
		}
	}