<?php
    /**
     * Class: Love
     * The model for the love SQL table.
     *
     * See Also:
     *     <Model>
     */
    class Love extends Model {

         /**
         * Function: add
         * Adds a love to the database.
         *
         * Parameters:
         *     $post - The <Post> they're loving
         */
        static function add($post) {
            $sql = SQL::current();
            $visitor = Visitor::current();
            $loves=$sql->select("posts",
                         "love_user_id",
                         array("id" => $post->id));
            $loves=unserialize($loves);
            array_push($loves,$visitor->id);
            $loves=serialize($loves);
            $sql->update("posts",
                        array("id"=>$post->id),
                        array("love_user_id"=>$loves));
        }


        static function delete($love_id) {
             $sql = SQL::current();
            $visitor = Visitor::current();
            $loves=$sql->select("posts",
                         "love_user_id",
                         array("id" => $post->id));
            $loves=unserialize($loves);
            $deloved=false;
            foreach($loves as $key=>$value){
                if($deloved==false){
                    if($value==$visitor->id){
                        unset($loves[$key]);
                        $deloved=true;
                    }
                }
            }
            $loves=serialize($loves);
            $sql->update("posts",
                        array("id"=>$post->id),
                        array("love_user_id"=>$loves));
        }

    }
