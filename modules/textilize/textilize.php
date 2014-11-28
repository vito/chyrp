<?php
    require "lib/textile.php";

    class Textilize extends Modules {
        public function __init() {
            $this->textile = new Textile();
            $this->addAlias("markup_text", "textile", 8);
            $this->addAlias("preview", "textile", 8);
        }
        public function textile($text) {
            return $this->textile->TextileThis($text);
        }
    }
