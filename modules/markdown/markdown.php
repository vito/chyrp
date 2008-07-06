<?php
	require "lib/markdown.php";

	class Markdown extends Module {
		public function __construct() {
			$this->setPriority("markdownify", 9);
			$this->addAlias("markup_post_text", "markdownify");
			$this->addAlias("markup_page_text", "markdownify");
			$this->addAlias("markup_comment_text", "markdownify");
			$this->addAlias("preview", "markdownify");
		}
		static function markdownify($text) {
			return Markdown($text);
		}
	}
