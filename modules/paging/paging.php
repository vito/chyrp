<?php
    class Paging extends Modules {
        public function __init() {
            $this->addAlias("markup_post_text", "makesafe", 8);
            $this->addAlias("markup_post_text", "split");
        }
        public function makesafe($text, $post) {
            return str_replace("<!--page-->", "(((page)))", $text);
        }
        public function split($text, $post) {
            if (!strpos($text, "(((page)))"))
                return $text;

            $text = preg_replace("/(<p>)?(\(\(\(page\)\)\))(<\/p>|<br \/>)?/", "\\2", $text);
            $split_pages = explode("(((page)))", $text);

            $post->paginated = new Paginator($split_pages, 1, (Route::current()->action == "view" ? "page" : "post_page"));

            return $post->paginated->result[0];
        }
        static function filter_post($post) {
            $post->next_page = false;
            $post->prev_page = false;
        }
    }
