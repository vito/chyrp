<?php
	class Text extends Feather {
		public function __construct() {
			$this->setFilter("body", "markup_post_text");
		}
		static function submit() {
			if (empty($_POST['body']))
				error(__("Error"), __("Body can't be blank."));
			
			$values = array("title" => $_POST['title'], "body" => $_POST['body']);
			$clean = (!empty($_POST['slug'])) ? $_POST['slug'] : sanitize($_POST['title']) ;
			$url = Post::check_url($clean);
			
			$post = Post::add($values, $clean, $url);
			
			# Send any and all pingbacks to URLs in the body
			$config = Config::current();
			if ($config->send_pingbacks)
				send_pingbacks($_POST['body'], $post->id);
			
			$route = Route::current();
			if (isset($_POST['bookmarklet']))
				$route->redirect($route->url("bookmarklet/done/"));
			else
				$route->redirect($post->url());
		}
		static function update() {
			$post = new Post($_POST['id']);
			
			if (empty($_POST['body']))
				error(__("Error"), __("Body can't be blank."));
			
			$values = array("title" => $_POST['title'], "body" => $_POST['body']);
			
			$post->update($yaml);
		}
		static function title($id) {
			$post = new Post($id);
			return fallback($post->title, $post->title_from_excerpt(), true);
		}
		static function excerpt($id) {
			$post = new Post($id);
			return $post->body;
		}
		static function feed_content($id) {
			$post = new Post($id);
			return $post->body;
		}
	}
