<?php
    /**
     * Class: Love
     * The model for the love SQL table.
     *
     * See Also:
     *     <Model>
     */
    class Like extends Model {
        public $belongs_to = array("post", "user");

        /**
         * Function: __construct
         * See Also:
         *     <Model::grab>
         */
        public function __construct($post_id, $options = array()) {
            parent::grab($this, $post_id, $options);

            if ($this->no_results)
                return false;

            Trigger::current()->filter($this, "like");
        }

         /**
         * Function: add
         * Adds a like to the database.
         *
         * Parameters:
         *     $post - The <Post> they're liking
         */
        static function add($post, $user) {
            $sql = SQL::current();
            $user = new User($user);

            $like = $sql->select("likes",
                           array("post_id, total_likes"),
                           array("post_id" => $post->id))->fetchObject();

            if (!$like) {
                $sql->insert("likes",
                       array("post_id" => $post->id,
                             "user_id" => serialize($user->id),
                             "total_likes" => 1));
            } else {
                if (!self::user_liked($user->id, $like->user_id)) {
                    $sql->update("likes", array("post_id" => $like->post_id),
                                          array("user_id" => self::user_update($user->id, $like->user_id),
                                                "total_likes" => $like->total_likes+1));                    
                } else
                    self::remove($like->id, $post->id, $user->id);
            }

            $like = new self($sql->latest("likes"));
            Trigger::current()->call("like", $like);
            return $like;

            if (isset($_POST['ajax']))
                exit("{ \"like_id\": \"".$like["id"]."\", \"total_likes\": \"".$like["total_likes"]."\" }");
        }

/*
        static function update($post, $user) {
            $like = new self($post);
            $user_ids[] = unserialize($like->user_id);
            if (!in_array($user, $user_ids)) {
                array_push($user_ids, $user);
                $users = serialize($user_ids);
            } else
                $users = serialize($user_ids);

            $sql = SQL::current();
            $sql->update("likes", array("id" => $like->id),
                                  array("user_id" => $users,
                                        "total_likes" => $like->total_likes+1));
        }
*/

        static function remove($id, $post, $user) {
            $like = new self($post);

            if ($like->no_results)
                return false;

            $user_ids[] = unserialize($like->user_id);
            foreach ($user_ids as $key => $value)
                    if ($value == $user)
                        unset($user_ids[$key]);

            $users = serialize($user_ids);

            SQL::current()->update("likes",
                         array("post_id" => $like->post_id),
                         array("user_id" => $users,
                               "total_likes" => $like->total_likes-1));

            Trigger::current()->call("unliked", $like, $post, $user, $like->total_likes);
        }

        static function user_update($user, $serialized) {
            $user_ids[] = @unserialize($serialized);

            if (!in_array($user, $user_ids)) {
                array_push($user_ids, $user);
                $users = serialize($user_ids);
            } else
                $users = serialize($user_ids);

            return $users;
        }

        static function user_liked($user, $serialized) {
            $user_ids[] = @unserialize($serialized);

            if (!in_array($user, $user_ids)) {
                return false;
            } else
                return true;
        }

        static function install() {
            SQL::current()->query("CREATE TABLE IF NOT EXISTS __likes (
                                    id INTEGER PRIMARY KEY AUTO_INCREMENT,
                                    post_id INTEGER NOT NULL,
                                    user_id VARCHAR(400) NOT NULL,
                                    total_likes INTEGER NOT NULL DEFAULT 0
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
