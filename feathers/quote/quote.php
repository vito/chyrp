<?php
    class Quote extends Feathers implements Feather {
        public function __init() {
            $this->setField(array("attr" => "quote",
                                  "type" => "text_block",
                                  "rows" => 5,
                                  "label" => __("Quote", "quote"),
                                  "preview" => true,
                                  "bookmarklet" => "selection"));
            $this->setField(array("attr" => "source",
                                  "type" => "text_block",
                                  "rows" => 5,
                                  "label" => __("Source", "quote"),
                                  "optional" => true,
                                  "preview" => true,
                                  "bookmarklet" => "page_link"));

            $this->setFilter("quote", array("markup_text", "markup_post_text"));
            $this->setFilter("source", array("markup_text", "markup_post_text"));
        }

        public function submit() {
            if (empty($_POST['quote']))
                error(__("Error"), __("Quote can't be empty.", "quote"));

            return Post::add(array("quote" => $_POST['quote'],
                                   "source" => $_POST['source']),
                             $_POST['slug'],
                             Post::check_url($_POST['slug']));
        }

        public function update($post) {
            if (empty($_POST['quote']))
                error(__("Error"), __("Quote can't be empty."));

            $post->update(array("quote" => $_POST['quote'],
                                "source" => $_POST['source']));
        }

        public function title($post) {
            return $post->title_from_excerpt();
        }

        public function excerpt($post) {
            return $post->quote;
        }

        public function add_dash($text) {
            return preg_replace("/(<p(\s+[^>]+)?>|^)/si", "\\1&mdash; ", $text, 1);
        }

        public function feed_content($post) {
            $body = "<blockquote>\n\t";
            $body.= $post->quote;
            $body.= "\n</blockquote>\n";
            $body.= $post->source;
            return $body;
        }
    }
