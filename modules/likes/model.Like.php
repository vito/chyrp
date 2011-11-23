<?php
    /**
     * Class: Love
     * The model for the love SQL table.
     *
     * See Also:
     *     <Model>
     */
    class Like extends Model {

         /**
         * Function: add
         * Adds a like to the database.
         *
         * Parameters:
         *     $post - The <Post> they're liking
         */
        static function add($post) {
            $sql = SQL::current();
            $visitor = Visitor::current();
            $likes = $sql->select("likes",
                            array("user_id", "likes"),
                            array("post_id" => $post->id));

            $user_ids = unserialize($likes->user_id);
            if (!in_array($visitor->id, $user_ids)) {
                array_push($user_ids, $visitor->id);
                $users = serialize($user_ids);
                $sql->update("likes", array(null, "post_id" => $post,
                                                  "user_id" => $user_ids,
                                                  "likes" => $likes->likes + 1));
            } else
                self::unlike($post);
        }

        static function unlike($post) {
            $sql = SQL::current();
            $visitor = Visitor::current();
            $likes = $sql->select("likes",
                            array("user_id", "likes"),
                            array("post_id" => $post->id));

            $loves = unserialize($loves);
            $unliked = false;

            foreach ($loves as $key => $value) {
                if ($unliked == false)
                    if ($value == $visitor->id) {
                        unset($loves[$key]);
                        $unliked = true;
                    }
            }
            $loves = serialize($loves);
            $sql->update("posts",
                   array("id" => $post->id),
                   array("love_user_id" => $loves));
        }

        static function install() {
            SQL::current()->query("CREATE TABLE IF NOT EXISTS __likes (
                                    id INTEGER PRIMARY KEY AUTO_INCREMENT,
                                    post_id INTEGER DEFAULT 0,
                                    user_id VARCHAR(400) DEFAULT 0,
                                    total_likes INTEGER DEFAULT 0
                                   ) DEFAULT CHARSET=utf8");

            Group::add_permission("like_posts", "Like Posts");
            Group::add_permission("unlike_posts", "Unlike Posts");
        }

        static function uninstall() {
            SQL::current()->query("DROP TABLE __likes");
            Group::remove_permission("like_posts");
            Group::remove_permission("unlike_posts");
        }

    }
