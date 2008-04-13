<?php
	class Quote extends Feather {
		public function __construct() {
			$this->setFilter("quote", "markup_post_text");
			$this->setFilter("source", "markup_post_text");
		}
		static function submit() {
			if (empty($_POST['quote']))
				error(__("Error"), __("Quote can't be empty."));
			
			$values = array("quote" => $_POST['quote'], "source" => $_POST['source']);
			$clean = (!empty($_POST['slug'])) ? $_POST['slug'] : "" ;
			$url = Post::check_url($clean);
			
			$post = Post::add($values, $clean, $url);
			
			# Send any and all pingbacks to URLs in the quote and source
			$config = Config::current();
			if ($config->send_pingbacks) {
				send_pingbacks($_POST['quote'], $post->id);
				send_pingbacks($_POST['source'], $post->id);
			}
			
			$route = Route::current();
			if (isset($_POST['bookmarklet']))
				$route->redirect($route->url("bookmarklet/done/"));
			else
				$route->redirect($post->url());
		}
		static function update() {
			$post = new Post($_POST['id']);
			
			if (empty($_POST['quote']))
				error(__("Error"), __("Quote can't be empty."));
			
			$values = array("quote" => $_POST['quote'], "source" => $_POST['source']);
			
			$post->update($yaml);
		}
		static function title($id) {
			$post = new Post($id);
			return $post->title_from_excerpt();
		}
		static function excerpt($id) {
			$post = new Post($id);
			return $post->quote;
		}
		static function add_dash($text) {
			return preg_replace("/(<p(\s+[^>]+)?>|^)/si", "\\1&mdash; ", $text, 1);
		}
		static function feed_content($id) {
			$post = new Post($id);
			$body = "<blockquote>";
			$body.= $post->quote;
			$body.= "</blockquote>";
			
			if ($post->source != "")
				$body.= self::add_dash($post->source);
			
			return $body;
		}
	}
