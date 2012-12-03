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
            $line = preg_replace("/^\s*[\[\(]?[0-9]{1,2}:[0-9]{2}(:[0-9]{2})?\s*(pm|am)?[\]|\)]?\s*/i", "", $dialogue[0]);
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
            $links = array();
            foreach ($split as $line) {
                # Remove the timstamps
                $line = preg_replace("/^\s*[\[\(]?[0-9]{1,2}:[0-9]{2}(:[0-9]{2})?\s*(pm|am)?[\]|\)]?\s*/i", "", $line);

                preg_match("/(<?)(.+)(:|>)\s*(.+)/i", $line, $matches);

                if (empty($matches))
                    continue;

                if (preg_match("/\s*\(([^\)]+)\)$/", $matches[2], $attribution))
                    if ($attribution[1] == "me") {
                        $my_name = $matches[2] = str_replace($attribution[0], "", $matches[2]);
                    } else {
                        $matches[2] = str_replace($attribution[0], "", $matches[2]);
                        $links[$matches[2]] = $attribution[1];
                    }

                $link = oneof(@$links[$matches[2]], "");

                $me = ($my_name == $matches[2] ? " me" : "");

                $username = $matches[1].$matches[2].$matches[3];
                $class = ($count % 2 ? "even" : "odd");
                $return.= '<li class="'.$class.$me.'">';

                if (!empty($link))
                    $return.= '<span class="label">'.$matches[1].'<a href="'.$link.'">'.fix($matches[2], false).'</a>'.$matches[3].'</span> '.$matches[4]."\n";
                else
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

            $body.= "<p>".__("To attribute a name to a URL, append the URL in parentheses, preceded by a space, to the username:", "chat")."</p>\n";
            $body.= "<ul class=\"list\">\n";
            $body.= "\t<li>&quot;&lt;John&gt;&quot; &rarr; &quot;&lt;John (http://example.com/)&gt;&quot;</li>\n";
            $body.= "\t<li>&quot;John:&quot; &rarr; &quot;John (http://example.com/):&quot;</li>\n";
            $body.= "</ul>\n";
            $body.= "<p>".__("This also only has to be done to the first occurrence of the username. It cannot be combined with attributing someone as yourself (because they're already at your site anyway).", "chat")."</p>";

            return array($title, $body);
        }
    }
