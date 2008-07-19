<?php
	class Video extends Feathers implements Feather {
		public function __init() {
			$this->setField(array("attr" => "video",
			                      "type" => "text_block",
			                      "rows" => 4,
			                      "label" => __("Video", "video"),
			                      "bookmarklet" => (isset($_GET['url']) and
			                                        preg_match("/http:\/\/(www\.|[a-z]{2}\.)?youtube\.com\/watch\?v=([^&]+)/",
			                                                   $_GET['url'])) ?
			                                        "url" :
			                                        ""));
			$this->setField(array("attr" => "caption",
			                      "type" => "text_block",
			                      "rows" => 4,
			                      "label" => __("Caption", "video"),
			                      "optional" => true,
			                      "preview" => true,
			                      "bookmarklet" => "selection"));

			$this->bookmarkletSelected(isset($_GET['url']) and
			                           preg_match("/http:\/\/(www\.|[a-z]{2}\.)?youtube\.com\/watch\?v=([^&]+)/",
			                                      $_GET['url']));

			$this->setFilter("caption", "markup_post_text");
		}
		public function submit() {
			if (empty($_POST['video']))
				error(__("Error"), __("Video can't be blank."));

			return Post::add(array("embed" => $this->embed_tag($_POST['video']),
			                       "video" => $_POST['video'],
			                       "caption" => $_POST['caption']),
			                 $_POST['slug'],
			                 Post::check_url($_POST['slug']));
		}
		public function update() {
			if (empty($_POST['video']))
				error(__("Error"), __("Video can't be blank."));

			$post = new Post($_POST['id']);
			$post->update(array("embed" => $this->embed_tag($_POST['video']),
			                    "video" => $_POST['video'],
			                    "caption" => $_POST['caption']));
		}
		public function title($post) {
			return $post->title_from_excerpt();
		}
		public function excerpt($post) {
			return $post->caption;
		}
		public function feed_content($post) {
			return $post->caption;
		}
		public function embed_tag($video) {
			if (preg_match("/http:\/\/(www\.|[a-z]{2}\.)?youtube\.com\/watch\?v=([^&]+)/", $video, $matches)) {
				return '<object type="application/x-shockwave-flash" class="object-youtube" data="http://'.$matches[1].'youtube.com/v/'.$matches[2].'" width="468" height="391"><param name="movie" value="http://'.$matches[1].'youtube.com/v/'.$matches[2].'" /><param name="FlashVars" value="playerMode=embedded" /></object>';
			} else {
				return $video;
			}
		}
	}
