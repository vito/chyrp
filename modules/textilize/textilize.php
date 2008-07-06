<?php
	require "lib/textile.php";

	class Textilize extends Module {
		public function __construct() {
			$this->textile = new Textile();
			$this->setPriority("textilize", 9);
			$this->addAlias("markup_post_text", "textilize");
			$this->addAlias("markup_page_text", "textilize");
			$this->addAlias("markup_comment_text", "textilize");
			$this->addAlias("preview", "textilize");
		}
		public function textilize($text) {
			return $this->textile->TextileThis($text);
		}
	}