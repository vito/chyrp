<?php
	class Photo extends Feather {
		public function __construct() {
			$this->setField(array("attr" => "photo", "type" => "file", "label" => __("Photo", "photo")));
			$this->setField(array("attr" => "from_url", "type" => "text", "label" => __("From URL?", "photo"), "optional" => true));
			$this->setField(array("attr" => "caption", "type" => "text_block", "label" => __("Caption", "photo"), "optional" => true, "preview" => true, "bookmarklet" => "selection"));
			$this->setFilter("caption", "markup_post_text");
			$this->respondTo("delete_post", "delete_file");
			$this->respondTo("filter_post", "filter_post");
		}
		public function submit() {
			$filename = "";
			if (isset($_FILES['photo']) and $_FILES['photo']['error'] == 0)
				$filename = upload($_FILES['photo'], array("jpg", "jpeg", "png", "gif", "tiff", "bmp"));
			elseif (!empty($_POST['from_url'])) {
				$file = tempnam(sys_get_temp_dir(), "chyrp");
				file_put_contents($file, get_remote($_POST['from_url']));
				$fake_file = array("name" => basename(parse_url($_POST['from_url'], PHP_URL_PATH)),
				                   "tmp_name" => $file);
				$filename = upload($fake_file, array("jpg", "jpeg", "png", "gif", "tiff", "bmp"), "", true);
			} else
				error(__("Error"), __("Couldn't upload photo."));

			$values = array("filename" => $filename, "caption" => $_POST['caption']);
			$clean = (!empty($_POST['slug'])) ? $_POST['slug'] : "" ;
			$url = Post::check_url($clean);

			$post = Post::add($values, $clean, $url);

			# Send any and all pingbacks to URLs in the caption
			$config = Config::current();
			if ($config->send_pingbacks)
				send_pingbacks($_POST['caption'], $post->id);

			$route = Route::current();
			if (isset($_POST['bookmarklet']))
				redirect($route->url("bookmarklet/done/"));
			else
				redirect($post->url());
		}
		public function update() {
			$post = new Post($_POST['id']);

			if (isset($_FILES['photo']) and $_FILES['photo']['error'] == 0) {
				delete_photo_file($_POST['id']);
				$filename = upload($_FILES['photo']);
			} else
				$filename = $post->filename;

			$values = array("filename" => $filename, "caption" => $_POST['caption']);

			$post->update($values);
		}
		public function title($post) {
			$caption = $post->title_from_excerpt();
			return fallback($caption, $post->filename, true);
		}
		public function excerpt($post) {
			return $post->caption;
		}
		public function feed_content($post) {
			return self::image_tag_for($post->filename, 500, 500)."<br /><br />".$post->caption;
		}
		public function delete_file($post) {
			if ($post->feather != "photo") return;
			unlink(MAIN_DIR.Config::current()->uploads_path.$post->filename);
		}
		public function filter_post($post) {
			if ($post->feather != "photo") return;
			$post->image = $this->image_tag_for($post->filename);
		}
		public function image_tag_for($filename, $max_width = 500, $max_height = null, $more_args = "q=100") {
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			$config = Config::current();
			return '<a href="'.$config->chyrp_url.$config->uploads_path.$filename.'"><img src="'.$config->chyrp_url.'/feathers/photo/lib/phpThumb.php?src='.$config->chyrp_url.$config->uploads_path.urlencode($filename).'&amp;w='.$max_width.'&amp;h='.$max_height.'&amp;f='.$ext.'&amp;'.$more_args.'" alt="'.$filename.'" /></a>';
		}
	}
