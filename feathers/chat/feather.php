<?php
	class Chat extends Feather {
		public function __construct() {
			$this->setField(array("attr" => "title", "type" => "text", "label" => __("Title", "chat"), "optional" => true));
			$this->setField(array("attr" => "dialogue", "type" => "text_block", "label" => __("Dialogue", "chat"), "preview" => true, "help" => "chat_dialogue", "bookmarklet" => "selection"));
			$this->customFilter("dialogue", "format_dialogue");
			$this->setFilter("dialogue", "markup_post_text");
			$this->respondTo("preview_chat", "format_dialogue");
			$this->respondTo("help_chat_dialogue", "help");
		}
		static function submit() {
			if (empty($_POST['dialogue']))
				error(__("Error"), __("Dialogue can't be blank."));

			$values = array("title" => $_POST['title'], "dialogue" => $_POST['dialogue']);
			$clean = (!empty($_POST['slug'])) ? $_POST['slug'] : sanitize($_POST['title']) ;
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

			if (empty($_POST['dialogue']))
				error(__("Error"), __("Dialogue can't be blank."));

			$values = array("title" => $_POST['title'], "dialogue" => $_POST['dialogue']);

			$post->update($values);
		}
		static function title($post) {
			$dialogue = explode("\n", $post->dialogue);
			$line = preg_replace("/[ ]?[\[|\(]?[0-9]{1,2}:[0-9]{2}(:[0-9]{2})?[ ]?(pm|am)?[\]|\)]?[ ]?/i", "", $dialogue[0]);
			$first_line = preg_replace("/([<]?)([^:|>]+)( \(me\)?)(:|>) (.+)/i", "\\1\\2\\4 \\5", $dialogue[0]);

			return fallback($post->title, $first_line, true);
		}
		static function excerpt($post) {
			return $post->dialogue;
		}
		static function feed_content($post) {
			return $post->dialogue;
		}
		static function format_dialogue($text) {
			$split = explode("\n", $text);
			$return = '<ul class="dialogue">';
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
		static function help() {
			global $title, $body;
			$title = __("Dialogue Formatting", "chat");
			$body = "<p>".__("To give yourself a special CSS class, append \" (me)\" to your username, like so:", "chat")."</p>\n";
			$body.= "<ul class=\"list\">\n";
			$body.= "\t<li>&quot;&lt;Alex&gt;&quot; &rarr; &quot;&lt;Alex (me)&gt;&quot;</li>\n";
			$body.= "\t<li>&quot;Alex:&quot; &rarr; &quot;Alex (me):&quot;</li>\n";
			$body.= "</ul>\n";
			$body.= "<p>".__("This only has to be done to the first occurrence of the username.", "chat")."</p>";
		}
	}
