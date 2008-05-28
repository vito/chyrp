<?php
	class Video extends Feather {
		public function __construct() {
			$this->setField(array("attr" => "video", "type" => "text_block", "rows" => 4, "label" => "Video"));
			$this->setField(array("attr" => "caption", "type" => "text_block", "rows" => 4, "label" => "Caption", "optional" => true, "preview" => true, "bookmarklet" => "selection"));
			$this->setFilter("caption", "markup_post_text");
		}
		static function submit() {
			if (empty($_POST['video']))
				error(__("Error"), __("Video can't be blank."));

			$embed = self::embed_tag($_POST['video']);
			$values = array("embed" => $embed, "caption" => $_POST['caption']);
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
		static function update() {
			$post = new Post($_POST['id']);

			if (empty($_POST['video']))
				error(__("Error"), __("Video can't be blank."));

			$embed = embed_tag($_POST['video']);
			$values = array("embed" => $embed, "caption" => $_POST['caption']);

			$post->update($values);
		}
		static function title($post) {
			return $post->title_from_excerpt();
		}
		static function excerpt($post) {
			return $post->caption;
		}
		static function feed_content($post) {
			return $post->embed."\n<br />\n<br />\n".$post->caption;
		}
		static function embed_tag($video) {
			if (preg_match("/http:\/\/(www\.|[a-z]{2}\.)?youtube\.com\/watch\?v=([^&]+)/", $video, $matches)) {
				return '<object type="application/x-shockwave-flash" class="object-youtube" data="http://'.$matches[1].'youtube.com/v/'.$matches[2].'" width="468" height="391"><param name="movie" value="http://'.$matches[1].'youtube.com/v/'.$matches[2].'" /><param name="FlashVars" value="playerMode=embedded" /></object>';
			} else {
				return $video;
			}
		}
	}
