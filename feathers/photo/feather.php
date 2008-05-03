<?php
	class Photo extends Feather {
		public function __construct() {
			$this->setField(array("attr" => "photo", "type" => "file", "label" => "Photo"));
			$this->setField(array("attr" => "caption", "type" => "text_block", "label" => "Caption", "optional" => true, "preview" => true, "bookmarklet" => "selection"));
			$this->setFilter("caption", "markup_post_text");
			$this->respondTo("delete_post", "delete_file");
			$this->respondTo("filter_post", "filter_post");
		}
		static function submit() {
			$filename = "";
			if (isset($_FILES['photo']) and $_FILES['photo']['error'] == 0) {
				$filename = upload($_FILES['photo'], array("jpg", "jpeg", "png", "gif", "tiff", "bmp"));
			} else {
				error(__("Error"), __("Couldn't upload photo."));
			}

			$values = array("filename" => $filename, "caption" => $_POST['caption']);
			$clean = (!empty($_POST['slug'])) ? $_POST['slug'] : "" ;
			$url = Post::check_url($clean);

			$post = Post::add($values, $clean, $url);

			# Send any and all pingbacks to URLs in the caption
			$config = Config::current();
			if ($config->send_pingbacks)
				send_pingbacks($_POST['caption'], $post->id);

			if (isset($_POST['bookmarklet']))
				redirect($route->url("bookmarklet/done/"));
			else
				redirect($post->url());
		}
		static function update() {
			$post = new Post($_POST['id']);

			if (isset($_FILES['photo']) and $_FILES['photo']['error'] == 0) {
				delete_photo_file($_POST['id']);
				$filename = upload($_FILES['photo']);
			} else {
				$filename = $post->filename;
			}

			$values = array("filename" => $filename, "caption" => $_POST['caption']);

			$post->update($values);
		}
		static function title($post) {
			$caption = $post->title_from_excerpt();
			return fallback($caption, $post->filename, true);
		}
		static function excerpt($post) {
			return $post->caption;
		}
		static function feed_content($post) {
			return image_tag_for($post->filename, 500, 500)."<br /><br />".$post->caption;
		}
		static function delete_file($post) {
			if ($post->feather != "photo") return;
			unlink(MAIN_DIR."/upload/".$post->filename);
		}
		static function filter_post($post) {
			if ($post->feather != "photo") return;
			$post->image = image_tag_for($post->filename);
		}
	}

	function image_tag_for($filename, $max_width = null, $max_height = null, $more_args = "q=100") {
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$config = Config::current();
		return '<a href="'.$config->url.'/upload/'.$filename.'"><img src="'.$config->url.'/feathers/photo/lib/phpThumb.php?src='.$config->url.'/upload/'.strtolower(urlencode($filename)).'&amp;w='.$max_width.'&amp;h='.$max_height.'&amp;f='.$ext.'&amp;'.$more_args.'" alt="'.$filename.'" /></a>';
	}
