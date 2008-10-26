<?php
    require "lib/markdown.php";

    class Markdown extends Modules {
        public function __init() {
            $this->addAlias("markup_text", "markdownify", 9);
            $this->addAlias("preview", "markdownify");
        }
        static function markdownify($text) {
            return Markdown($text);
        }
    }
