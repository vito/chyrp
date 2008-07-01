<?php
	class Link extends Feather {
		public function __construct() {
			$this->setField(array("attr" => "source", "type" => "text", "label" => __("URL", "link"), "bookmarklet" => "url"));
			$this->setField(array("attr" => "name", "type" => "text", "label" => __("Name", "link"), "bookmarklet" => "title"));
			$this->setField(array("attr" => "description", "type" => "text_block", "label" => __("Description", "link"), "optional" => true, "preview" => true, "bookmarklet" => "selection"));
			$this->setFilter("description", "markup_post_text");
			$this->respondTo("feed_url", "set_feed_url");
		}
		static function submit() {
			if (empty($_POST['source']))
				error(__("Error"), __("URL can't be empty."));

			if (!@parse_url($_POST['source'], PHP_URL_SCHEME))
				$_POST['source'] = "http://".$_POST['source'];

			$values = array("name" => $_POST['name'], "source" => $_POST['source'], "description" => $_POST['description']);
			$clean = (!empty($_POST['slug'])) ? $_POST['slug'] : sanitize($_POST['name']) ;
			$url = Post::check_url($clean);

			$post = Post::add($values, $clean, $url);

			$route = Route::current();
			if (isset($_POST['bookmarklet']))
				redirect($route->url("bookmarklet/done/"));
			else
				redirect($post->url());
		}
		static function update() {
			$post = new Post($_POST['id']);

			if (empty($_POST['source']))
				error(__("Error"), __("URL can't be empty."));

			if (!@parse_url($_POST['source'], PHP_URL_SCHEME))
				$_POST['source'] = "http://".$_POST['source'];

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
		static function set_feed_url($url, $post) {
			if ($post->feather != "link") return;
			return $post->source;
		}
	}
