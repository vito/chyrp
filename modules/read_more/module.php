<?php
	class ReadMore extends Module {
		public function __construct() {
			$this->addAlias("markup_post_text", "makesafe", 8);

			if (ADMIN)
				$this->addAlias("markup_post_text", "read_more");
		}
		static function makesafe($text, $post) {
			if (!is_string($text)) return;
			if (!isset($post->id) or !strstr($text, "<!--more-->")) return $text;

			if (isset($_GET['feed']))
				return str_replace("<!--more-->", "", $text);

			# For the curious: e51b2b9a58824dd068d8777ec6e97e4d is a md5 of "replace me!"
			return str_replace("<!--more-->", '<a class="read_more" href="'.$post->url().'">e51b2b9a58824dd068d8777ec6e97e4d</a>(((more)))', $text);
		}
		# To be used in the Twig template as ${ post.body | read_more("Read more...") }
		static function read_more($text, $string = null) {
			if (!strstr($text, 'class="read_more"')) return $text;

			if (!isset($string) or $string instanceof Post) # If it's called from anywhere but Twig the post will be passed as a second argument.
				$string = __("Read More &raquo;", "theme");

			preg_match("/<a class=\"read_more\" href=\"[^\"]+\">e51b2b9a58824dd068d8777ec6e97e4d<\/a>\(\(\(more\)\)\)(<\/p>|<br \/>)/", $text, $ending_tag);
			$split_read = explode("(((more)))", $text);
			$split_read[0].= @$ending_tag[1];

			if (Route::current()->action == "view")
				return preg_replace('/<a class="read_more" href="([^"]+)">e51b2b9a58824dd068d8777ec6e97e4d<\/a>/', "", implode("\n\n", $split_read));

			return str_replace("e51b2b9a58824dd068d8777ec6e97e4d", $string, $split_read[0]);
		}
		static function title_from_excerpt($text) {
			$text = preg_replace("/(<p>)?<a class=\"read_more\" href=\"([^\"]+)\">e51b2b9a58824dd068d8777ec6e97e4d<\/a>(<\/p>|<br \/>)?/", "<!--more-->", $text);
			$split = explode("<!--more-->", $text);
			return $split[0];
		}
	}
