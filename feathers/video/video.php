<?php
	class Video extends Feathers implements Feather {
		public function __init() {
			$this->setField(array("attr" => "video",
			                      "type" => "text_block",
			                      "rows" => 4,
			                      "label" => __("Video", "video"),
			                      "bookmarklet" => $this->isVideo() ?
			                                        "url" :
			                                        ""));
			$this->setField(array("attr" => "caption",
			                      "type" => "text_block",
			                      "rows" => 4,
			                      "label" => __("Caption", "video"),
			                      "optional" => true,
			                      "preview" => true,
			                      "bookmarklet" => "selection"));

			$this->bookmarkletSelected($this->isVideo());

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
			return $post->embed."<br /><br />".$post->caption;
		}
		public function embed_tag($video) {
			if (preg_match("/http:\/\/(www\.|[a-z]{2}\.)?youtube\.com\/watch\?v=([^&]+)/", $video, $matches)) {
				return '<object type="application/x-shockwave-flash" class="object-youtube" data="http://'.$matches[1].'youtube.com/v/'.$matches[2].'" width="468" height="391"><param name="movie" value="http://'.$matches[1].'youtube.com/v/'.$matches[2].'" /><param name="FlashVars" value="playerMode=embedded" /></object>';
			} else if (preg_match("/http:\/\/(www\.)?vimeo.com\/([0-9]+)/", $video, $matches)) {
				$site = get_remote($video);
				preg_match('/<div id="vimeo_player_[0-9]+" class="player" style="width:([0-9]+)px;height:([0-9]+)px;">/',
				           $site,
				           $scale);
				return '<object type="application/x-shockwave-flash" class="object-vimeo" width="'.$scale[1].'" height="'.$scale[2].'" data="http://www.vimeo.com/moogaloop.swf?clip_id='.$matches[2].'&amp;server=www.vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=00adef&amp;fullscreen=1"><param name="allowfullscreen" value="true" /><param name="allowscriptaccess" value="always" /><param name="movie" value="http://www.vimeo.com/moogaloop.swf?clip_id='.$matches[2].'&amp;server=www.vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=00adef&amp;fullscreen=1" /></object>';
			} else if (preg_match('/http:\/\/(www\.)?metacafe.com\/watch\/([0-9]+)\/([^\/&\?]+)/', $video, $matches)) {
				return '<object type="application/x-shockwave-flash" class="object-metacafe" data="http://www.metacafe.com/fplayer/'.$matches[2].'/'.$matches[3].'.swf" width="400" height="345"></object>';
			} else if (preg_match("/http:\/\/(www\.)?revver.com\/video\/([0-9]+)/", $video, $matches)) {
				return '<script src="http://flash.revver.com/player/1.0/player.js?mediaId:'.$matches[2].';width:468;height:391;" type="text/javascript"></script>';
			} else if (preg_match("/http:\/\/(www\.)viddler\.com\/.+/", $video)) {
				$viddler_page = get_remote($video);

				if (preg_match("/<link\s+rel=\"video_src\"\s+href=\"http:\/\/(www\.)?viddler.com\/player\/([0-9a-fA-F]+)/", $viddler_page, $matches) and
				    preg_match("/<meta\s+name=\"video_height\"\s+content=\"([0-9]+)\"/", $viddler_page, $height) and
				    preg_match("/<meta\s+name=\"video_width\"\s+content=\"([0-9]+)\"/", $viddler_page, $width)) {
					return '<object type="application/x-shockwave-flash" data="http://www.viddler.com/player/'.$matches[2].'/" width="'.$width[1].'" height="'.$height[1].'" id="viddler_'.$matches[2].'" class="object-youtube"><param name="movie" value="http://www.viddler.com/player/'.$matches[2].'/" /><param name="allowScriptAccess" value="always" /><param name="allowFullScreen" value="true" /></object>';
				}

				return $video;
			} else {
				return $video;
			}
		}
		public function embed_tag_for($post, $max_width = 500) {
			$post->embed = preg_replace("/&([[:alnum:]_]+)=/", "&amp;\\1=", $post->embed);

			if (preg_match('/width=("|\')([0-9]+)("|\') height=("|\')([0-9]+)("|\')/', $post->embed, $scale)) {
				$match  = $scale[0];
				$width  = $scale[2];
				$height = $scale[5];

				$new_height = (int) (($max_width / $width) * $height);

				return str_replace($match, 'width="'.$max_width.'" height="'.$new_height.'"', $post->embed);
			}

			if (preg_match('/width:([0-9]+);height:([0-9]+);/', $post->embed, $scale)) {
				$match  = $scale[0];
				$width  = $scale[1];
				$height = $scale[2];

				$new_height = (int) (($max_width / $width) * $height);

				return str_replace($match, 'width:'.$max_width.';height:'.$new_height.';', $post->embed);
			}
		}
		public function isVideo() {
			if (!isset($_GET['url']))
				return false;

			if (preg_match("/http:\/\/(www\.|[a-z]{2}\.)?youtube\.com\/watch\?v=([^&]+)/", $_GET['url']) or
			    preg_match("/http:\/\/(www\.)?vimeo.com\/([0-9]+)/", $_GET['url']) or
			    preg_match('/http:\/\/(www\.)?metacafe.com\/watch\/([0-9]+)\/([^\/&\?]+)/', $_GET['url']) or
			    preg_match("/http:\/\/(www\.)?revver.com\/video\/([0-9]+)/", $_GET['url']) or
			    preg_match("/http:\/\/(www\.)viddler\.com\/.+/", $_GET['url']))
				return true;

			return false;
		}
	}
