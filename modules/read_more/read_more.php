<?php
    class ReadMore extends Modules {
        public function __init() {
            $this->addAlias("markup_post_text", "makesafe", 8);
        }

		# Replace the "read more" indicator before markup modules get to it.
        static function makesafe($text, $post = null) {
            if (!is_string($text) or !preg_match("/<!--more(\((.+)\))?-->/", $text)) return $text;

            $controller = Route::current()->controller;
            if ($controller instanceof MainController and $controller->feed)
                return str_replace("<!--more-->", "", $text);

            $url = (isset($post) and !$post->no_results) ? $post->url() : "#" ;

            # For the curious: e51b2b9a58824dd068d8777ec6e97e4d is a md5 of "replace me!"
            return preg_replace("/<!--more(\((.+)\))?-->/", '<a class="read_more" href="'.$url.'">e51b2b9a58824dd068d8777ec6e97e4d</a>(((more\\1)))', $text);
        }

        # To be used in the Twig template as ${ post.body | read_more("Read more...") }
        static function read_more($text, $string = null) {
            if (!substr_count($text, "e51b2b9a58824dd068d8777ec6e97e4d")) return $text;

            preg_match("/<a class=\"read_more\" href=\"[^\"]+\">e51b2b9a58824dd068d8777ec6e97e4d<\/a>\(\(\(more(\((.+)\))?\)\)\)(<\/p>|<br \/>)?/", $text, $more);
            $split_read = preg_split("/\(\(\(more(\((.+)\))?\)\)\)/", $text);
            $split_read[0].= @$more[3];

			if (!empty($more[2]))
				$string = $more[2];
            elseif (!isset($string) or $string instanceof Post) # If it's called from anywhere but Twig the post will be passed as a second argument.
                $string = __("Read More &raquo;", "theme");

            if (Route::current()->action == "view")
                return preg_replace('/(<p>)?<a class="read_more" href="([^"]+)">e51b2b9a58824dd068d8777ec6e97e4d<\/a>(<\/p>(\n\n<\/p>(\n\n)?)?)?/', "", implode("\n\n", $split_read));

            return str_replace("e51b2b9a58824dd068d8777ec6e97e4d", $string, $split_read[0]);
        }

        static function title_from_excerpt($text) {
            $split = preg_split("/(<p>)?<a class=\"read_more\" href=\"([^\"]+)\">e51b2b9a58824dd068d8777ec6e97e4d<\/a>(<\/p>(\n\n<\/p>(\n\n)?)?|<br \/>)?/", $text);
            return $split[0];
        }

        public function preview($text) {
            return preg_replace("/<!--more(\((.+)\))?-->/", "<hr />", $text);
        }
    }
