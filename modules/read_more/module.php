<?php
	class ReadMore extends Module {
		public function __construct() {
			$this->addAlias("markup_post_text", "makesafe", 8);
			if (isset($_GET['feed']))
				$this->addAlias("markup_post_text", "read_more");
		}
		static function makesafe($text) {
			global $post, $viewing;
			if (!isset($post->id) or !strstr($text, "<!--more-->")) return $text;

			# For the curious: e51b2b9a58824dd068d8777ec6e97e4d is a md5 of "replace me!"
			return str_replace("<!--more-->", '<a class="read_more" href="'.$post->url().'">e51b2b9a58824dd068d8777ec6e97e4d</a>(((more)))', $text);
		}
		# To be used in the Twig template as ${ post.body | read_more("Read more...") }
		static function read_more($text, $string = null) {
			global $post, $viewing;
			if (!isset($post->id) or !strstr($text, 'class="read_more"')) return $text;

			fallback($string, __("Read More &raquo;", "theme"));
			#$text = preg_replace("/(<p>)?(<a class=\"read_more\" href=\"([^\"]+)\">e51b2b9a58824dd068d8777ec6e97e4d<\/a>)(<\/p>|<br \/>)?/", "\\2", $text);
			$split_read = explode("(((more)))", $text);

			if ($viewing)
				return preg_replace('/<a class="read_more" href="([^"]+)">e51b2b9a58824dd068d8777ec6e97e4d<\/a>/', "", implode("\n\n", $split_read));

			return str_replace("e51b2b9a58824dd068d8777ec6e97e4d", $string, $split_read[0]);
		}
		static function title_from_excerpt($text) {
			$text = preg_replace("/(<p>)?<a class=\"read_more\" href=\"([^\"]+)\">e51b2b9a58824dd068d8777ec6e97e4d<\/a>(<\/p>|<br \/>)?/", "<!--more-->", $text);
			$split = explode("<!--more-->", $text);
			return $split[0];
		}
	}
