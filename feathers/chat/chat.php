<?php
    class Chat extends Feathers implements Feather {
        public function __init() {
            $this->setField(array("attr" => "title",
                                  "type" => "text",
                                  "label" => __("Title", "chat"),
                                  "optional" => true));
            $this->setField(array("attr" => "dialogue",
                                  "type" => "text_block",
                                  "label" => __("Dialogue", "chat"),
                                  "preview" => true,
                                  "help" => "chat_dialogue",
                                  "bookmarklet" => "selection"));

            $this->customFilter("dialogue", "format_dialogue");

            $this->setFilter("title", array("markup_title", "markup_post_title"));
            $this->setFilter("dialogue", array("markup_text", "markup_post_text"));

            $this->respondTo("preview_chat", "format_dialogue");
            $this->respondTo("help_chat_dialogue", "help");
        }

        public function submit() {
            if (empty($_POST['dialogue']))
                error(__("Error"), __("Dialogue can't be blank."));

            fallback($_POST['slug'], sanitize($_POST['title']));

            return Post::add(array("title" => $_POST['title'],
                                    "dialogue" => $_POST['dialogue']),
                             $_POST['slug'],
                             Post::check_url($_POST['slug']));
        }

        public function update($post) {
            if (empty($_POST['dialogue']))
                error(__("Error"), __("Dialogue can't be blank."));

            $post->update(array("title" => $_POST['title'],
                                "dialogue" => $_POST['dialogue']));
        }

        public function title($post) {
            $dialogue = oneof($post->dialogue_unformatted, $post->dialogue);

            $dialogue = explode("\n", $dialogue);
            $line = preg_replace("/[ ]?[\[|\(]?[0-9]{1,2}:[0-9]{2}(:[0-9]{2})?[ ]?(pm|am)?[\]|\)]?[ ]?/i", "", $dialogue[0]);
            $first_line = preg_replace("/([<]?)([^:|>]+)( \(me\)?)(:|>) (.+)/i", "\\1\\2\\4 \\5", $dialogue[0]);

            return oneof($post->title, $first_line);
        }

        public function excerpt($post) {
            return $post->dialogue;
        }

        public function feed_content($post) {
            return $post->dialogue;
        }

        public function format_dialogue($text, $post = null) {
            if (isset($post))
                $post->dialogue_unformatted = $text;
                    
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
                $return.= '<span class="label">'.fix($username, false).'</span> '.$matches[4]."\n";
                $return.= '</li>';
                $count++;
            }
            $return.= "</ul>";

            # If they're previewing.
            if (!isset($post))
                $return = preg_replace("/(<li class=\"(even|odd) me\"><span class=\"label\">)(.+)(<\/span> (.+)\n<\/li>)/", "\\1<strong>\\3</strong>\\4", $return);

            return $return;
        }

        public function help() {
            $title = __("Dialogue Formatting", "chat");

            $body = "<p>".__("To give yourself a special CSS class, append \" (me)\" to your username, like so:", "chat")."</p>\n";
            $body.= "<ul class=\"list\">\n";
            $body.= "\t<li>&quot;&lt;Alex&gt;&quot; &rarr; &quot;&lt;Alex (me)&gt;&quot;</li>\n";
            $body.= "\t<li>&quot;Alex:&quot; &rarr; &quot;Alex (me):&quot;</li>\n";
            $body.= "</ul>\n";
            $body.= "<p>".__("This only has to be done to the first occurrence of the username.", "chat")."</p>";

            return array($title, $body);
        }
    }
