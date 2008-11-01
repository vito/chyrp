<?php
    require "lib/smartypants.php";

    class Smartypants extends Modules {
        public function __init() {
            $this->addAlias("markup_text", "smartify", 9);
            $this->addAlias("markup_title", "smartify", 9);
            $this->addAlias("preview", "smartify", 9);
        }
        static function smartify($text) {
            return Smartypants($text);
        }
    }
