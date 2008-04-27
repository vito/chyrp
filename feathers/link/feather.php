<?php
	class Link extends Feather {
		public function __construct() {
			$this->setField("source", "text", "URL", "url");
			$this->setField("name", "text", "Name", "title");
			$this->setField("description", "text_block", "Description (optional)", "selection");
			$this->setFilter("description", "markup_post_text");
			$this->respondTo("feed_url", "set_feed_url");
		}
		static function submit() {
			if (empty($_POST['source']))
				error(__("Error"), __("URL can't be empty."));

			$values = array("name" => $_POST['name'], "source" => $_POST['source'], "description" => $_POST['description']);
			$clean = (!empty($_POST['slug'])) ? $_POST['slug'] : "" ;
			$url = Post::check_url($clean);

			$post = Post::add($values, $clean, $url);

			# Send any and all pingbacks to URLs in the description and source
			$config = Config::current();
			if ($config->send_pingbacks) {
				send_pingbacks($_POST['description'], $post->id);
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

			if (empty($_POST['source']))
				error(__("Error"), __("URL can't be empty."));

			$values = array("name" => $_POST['name'], "source" => $_POST['source'], "description" => $_POST['description']);

			$post->update($values);
		}
		static function title($post) {
			$return = $post->name;
			fallback($return, $post->title_from_excerpt());
			fallback($return, $post->source);
			return $return;
		}
		static function excerpt($post) {
			return $post->description;
		}
		static function feed_content($post) {
			return $post->description;
		}
		static function set_feed_url() {
			global $post;
			if ($post->feather != "link") return;
			return $post->source;
		}
	}
