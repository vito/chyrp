<?php
    class Love extends Modules {
        static function __install() {
            $sql = SQL::current();
            $sql->query("CREATE TABLE IF NOT EXISTS __loves (
                             id INTEGER PRIMARY KEY AUTO_INCREMENT,
                             name VARCHAR(250) DEFAULT 'Guest',
                             post_id INTEGER DEFAULT 0
                         ) DEFAULT CHARSET=utf8");

        }

        static function __uninstall($confirm) {
            if ($confirm)
                SQL::current()->query("DROP TABLE __loves");        
        }
        static function route_add_love(){
        	$post = new Post($_POST['post_id'], array("drafts" => true));
        	Comment::add($_POST['author'],
                            $post);
        }
        static function remove_love()

	}
