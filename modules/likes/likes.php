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

        static function route_like() {
            $post = new Post($_POST['post_id']);
            $user = Visitor::current();
        	Like::add($post, $user);
        }

        static function route_unlike() {
            $post = new Post($_POST['post_id']);
            $user = Visitor::current()->id;
        	Like::remove($post, $user);
        }

        static function scripts($scripts) {
            $scripts[] = Config::current()->chyrp_url."/modules/likes/javascript.php";
            return $scripts;
        }

        static function ajax() {
            header("Content-Type: application/x-javascript", true);
            $visitor = Visitor::current();

            switch($_POST['action']) {
                case "show_likes":
                    $like = new Like($_POST['comment_id']);
                    $trigger->call("show_likes", $like);
?>
{ like_id: [ <?php echo $like->id; ?> ], total_likes: "<?php echo $like->total_likes; ?>" }
<?php
                    break;
                case "unlike":
                    Like::remove($_POST['post_id']);
                    break;
            }
        }

        public function post($post) {
            $post->has_many[] = "likes";
        }

        public function post_like_count_attr($attr, $post) {
            if (isset($this->like_counts))
                return oneof(@$this->like_counts[$post->id], 0);

            $counts = SQL::current()->select("likes",
                                       array("total_likes"),
                                       array("post_id" => $post->id))->fetch();

            return oneof(@$this->like_counts[$post->id], $counts[0]);
        }

        static function delete_post($post) {
            SQL::current()->delete("likes", array("post_id" => $post->id));
        }

        static function delete_user($user) {
            SQL::current()->update("likes", array("user_id" => $user->id), array("user_id" => 0));
        }
    }
