<?php
	class Chat extends Feather {
		public function __construct() {
			$this->customFilter("dialogue", "format_dialogue");
			$this->setFilter("dialogue", "markup_post_text");
			$this->respondTo("preview_chat", "format_dialogue");
		}
		static function submit() {
			$config = Config::current();
			
			if (empty($_POST['dialogue']))
				error(__("Error"), __("Dialogue can't be blank."));
			
			$yaml = Spyc::YAMLDump(array("title" => $_POST['title'], "dialogue" => $_POST['dialogue']));
			$clean = (!empty($_POST['slug'])) ? $_POST['slug'] : sanitize($_POST['title']) ;
			$url = Post::check_url($clean);
			
			$post = Post::add($yaml, $clean, $url);
			
			# Send any and all pingbacks to URLs in the dialogue
			if ($config->send_pingbacks)
				send_pingbacks($_POST['dialogue'], $post->id);
			
			$route = Route::current();
			if (isset($_POST['bookmarklet']))
				$route->redirect($route->url("bookmarklet/done/"));
			else
				$route->redirect($post->url());
		}
		static function update() {
			$post = new Post($_POST['id']);
			
			if (empty($_POST['dialogue']))
				error(__("Error"), __("Dialogue can't be blank."));
			
			$yaml = Spyc::YAMLDump(array("title" => $_POST['title'], "dialogue" => $_POST['dialogue']));
			
			$post->update($yaml);
		}
		static function title($id) {
			global $post;
			$post = new Post($id);
			
			$dialogue = explode("\n", $post->dialogue);
			$line = preg_replace("/[ ]?[\[|\(]?[0-9]{1,2}:[0-9]{2}(:[0-9]{2})?[ ]?(pm|am)?[\]|\)]?[ ]?/i", "", $dialogue[0]);
			$first_line = preg_replace("/([<]?)([^:|>]+)( \(me\)?)(:|>) (.+)/i", "\\1\\2\\4 \\5", $dialogue[0]);
			
			return fallback($post->title, $first_line, true);
		}
		static function excerpt($id) {
			$post = new Post($id);
			return $post->dialogue;
		}
		static function feed_content($id) {
			$post = new Post($id);
			return $post->dialogue;
		}
		static function format_dialogue($text) {
			$split = explode("\n", $text);
			$return = "<ul>";
			$count = 0;
			$my_name = "";
			foreach ($split as $line) {
				# Remove the timstamps
				$line = preg_replace("/[ ]?[\[|\(]?[0-9]{1,2}:[0-9]{2}(:[0-9]{2})?[ ]?(pm|am)?[\]|\)]?[ ]?/i", "", $line);
			
				preg_match("/([<]?)([^:|>]+)(:|>) (.+)/i", $line, $matches);
				if (empty($matches)) continue;
				
				if (strpos($matches[2], " (me)") or $my_name == $matches[2]) {
					$me = " me";
					$my_name = str_replace(" (me)", "", $matches[2]);
				} else
					$me = "";
				
				$username = str_replace(" (me)", "", $matches[1].$matches[2].$matches[3]);
				$class = ($count % 2) ? "even" : "odd" ;
				$return.= '<li class="'.$class.$me.'">';
				$return.= '<span class="label">'.htmlentities($username, ENT_NOQUOTES, "utf-8").'</span> '.$matches[4]."\n";
				$return.= '</li>';
				$count++;
			}
			$return.= "</ul>";
		
			return $return;
		}
	}
