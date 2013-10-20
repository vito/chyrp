<?php
    class Emoji extends Modules {
        public function __init() {
           $this->addAlias("markup_text", "emote", 9);
           $this->addAlias("markup_post_text","emote", 9);
           $this->addAlias("preview", "emote", 9);
           $this->addAlias("preview_post", "emote", 9);
        }

        static function emote($text) {
            $emoji = array(
                ':angry:' => '&#x1f620;',
                ':blush:' => '&#x1f633;',
                ':bored:' => '&#x1f629;',
                'B)' => '&#x1f60e;',
                '8)' => '&#x1f60e;',
                ':\'(' => '&#x1f622;',
                ':cry:' => '&#x1f622;',
                ':3' => '&#x1f638;',
                '^_^' => '&#x1f601;',
                'x_x' => '&#x1f635;',
                '>:-)' => '&#x1f608;',
                'o:-)' => '&#x1f607;',
                ':-*' => '&#x1f618;',
                ':-|' => '&#x1f611;',
                ':-\\' => '&#x1f615;',
                ':-/' => '&#x1f615;',
                ':-s' => '&#x1f616;',
                ':-D' => '&#x1f603;',
                ':D' => '&#x1f603;',
                '=D' => '&#x1f603;',
                '<3' => '&#x1f60d;',
                ':love:' => '&#x1f60d;',
                ':P' => '&#x1f61b;',
                ':-P' => '&#x1f61b;',
                ':p' => '&#x1f61b;',
                ':-p' => '&#x1f61b;',
                ':ooo:' => '&#x1f62e;',
                ':-(' => '&#x1f61f;',
                ':(' => '&#x1f61f;',
                '=(' => '&#x1f61f;',
                ':(' => '&#x1f61f;',
                ':O' => '&#x1f632;',
                ':-O' => '&#x1f632;',
                ':)' => '&#x1f600;',
                ':-)' => '&#x1f600;',
                '=)' => '&#x1f60a;',
                ':->' => '&#x1f60f;',
                ':>' => '&#x1f60f;',
                'O_O' => '&#x1f632;',
                ':-x' => '&#x1f636;',
                ';-)' => '&#x1f609;',
                ';)' => '&#x1f609;',
            );

            foreach($emoji as $key => $value) {
                $text =  str_ireplace($key, '<span class="emoji">'.$value.'</span>', $text);
            }

            return $text;
        }
    }
?>