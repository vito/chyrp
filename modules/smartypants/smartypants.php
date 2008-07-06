<?php
	require "lib/smartypants.php";

	class Smartypants extends Module {
		public function __construct() {
			$this->addAlias("markup_post_text", "smartify");
			$this->addAlias("markup_page_text", "smartify");
			$this->addAlias("markup_comment_text", "smartify");
			$this->addAlias("preview", "smartify");
		}
		static function smartify($text) {
			return Smartypants($text);
		}
	}
