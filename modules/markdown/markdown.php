<?php
	require "lib/markdown.php";

	class Markdown extends Modules {
		public function __init() {
			$this->addAlias("markup_post_text", "markdownify", 9);
			$this->addAlias("markup_page_text", "markdownify", 9);
			$this->addAlias("markup_comment_text", "markdownify", 9);
			$this->addAlias("preview", "markdownify");
		}
		static function markdownify($text) {
			return Markdown($text);
		}
	}
