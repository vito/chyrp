<?php
	class ReadMore extends Module {
		public function __construct() {
			$this->addAlias("markup_post_text", "makesafe", 8);
			$this->addAlias("markup_post_text", "split");
		}
		static function title_from_excerpt($text) {
			$text = preg_replace("/(<p>)?<a id=\"read_more_([0-9]+)\" class=\"read_more\" href=\"([^\"]+)\">([^<]+)<\/a>(<\/p>|<br \/>)?/", "<!--more-->", $text);
			$split = explode("<!--more-->", $text);
			return $split[0];
		}
		static function makesafe($text) {
			global $post, $viewing;
			if (!isset($post->id) or !strstr($text, "<!--more-->")) return $text;

			$trigger = Trigger::current();
			return str_replace("<!--more-->", '<a id="read_more_'.$post->id.'" class="read_more" href="'.$post->url().'">'.$trigger->filter("read_more").'</a>(((more)))', $text);
		}
		static function split($text) {
			global $post, $viewing;
			if (!isset($post->id) or !strstr($text, 'id="read_more_'.$post->id.'"')) return $text;

			$text = preg_replace("/(<p>)?(<a id=\"read_more_([0-9]+)\" class=\"read_more\" href=\"([^\"]+)\">([^<]+)<\/a>)(<\/p>|<br \/>)?/", "\\2", $text);
			$split_read = explode("(((more)))", $text);

			if ($viewing)
				return preg_replace('/<a id="read_more_([0-9]+)" class="read_more" href="([^"]+)">([^<]+)<\/a>/', "", $split_read[0].$split_read[1]);

			return $split_read[0];
		}
	}
	new ReadMore();
