<?php
    /**
     * Class: Love
     * The model for the love SQL table.
     *
     * See Also:
     *     <Model>
     */
    class Love extends Model {
        public $no_results = false;

        public $belongs_to = array("post", "user");

         /**
         * Function: add
         * Adds a love to the database.
         *
         * Parameters:
         *     $author - The name of the commenter.
         *     $post - The <Post> they're commenting on.
         */
        static function add($author,$post) {
            $sql = SQL::current();
            $sql->insert("loves",
                         array("author" => strip_tags($author),
                               "post_id" => $post->id);
            $new = new self($sql->latest("comments"));
            return $new;
        }


        static function delete($love_id) {
            SQL::current()->delete("comments", array("id" => $love_id));
        }

    }
