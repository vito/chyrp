<?php
    class Love extends Modules {
        static function __install() {
            $sql = SQL::current();
            $sql->query("ALTER TABLE __posts 
            			ADD love_user_id VARCHAR");

        }

        static function __uninstall($confirm) {
            if ($confirm)
                SQL::current()->query("DROP TABLE __loves");        
        }
        static function route_add_love(){
        	$post = new Post($_POST['post_id'], array("drafts" => true));
        	Love::add($post);
        }
        static function route_remove_love(){
        	Love::delete();	
        }

    }
