<?php
    require "lib/textile.php";

    class Textilize extends Modules {
        public function __init() {
            $this->textile = new Textile();
            $this->addAlias("markup_post_text", "textile", 9);
            $this->addAlias("markup_page_text", "textile", 9);
            $this->addAlias("markup_comment_text", "textile", 9);
            $this->addAlias("preview", "textile");
        }
        public function textile($text) {
            return $this->textile->TextileThis($text);
        }
    }
