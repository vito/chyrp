<?php
    require_once "model.Like.php";

    class Likes extends Modules {
        static function __install() {
            Like::install();
        }

        static function __uninstall($confirm) {
            if ($confirm)
                Like::uninstall();
        }

        static function route_add_like() {
        	$post = new Post($_POST['post_id'], array("drafts" => true));
        	Like::add($post);
        }

        static function route_remove_like() {
        	Like::delete();	
        }

        public function post($post) {
            $post->has_many[] = "likes";
        }
    }
