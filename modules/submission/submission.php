<?php
    class Submission extends Modules {

        static function __install() {
            Group::add_permission("submit_article", "Submit Aritcles");
            Route::current()->add("submit/", "submit");
        }
    
        static function __uninstall() {
            Group::remove_permission("submit_article");
            Route::current()->remove("submit/");
        }
    
        public function parse_urls($urls) {
            $urls["/\/submit\//"] = "/?action=submit";
            return $urls;
        }

        /**
         * Function: submit
         * Submits a post to the blog owner.
         */
        public function route_submit() {
            if (!Visitor::current()->group->can("submit_article"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to submit articles."));

            if (!empty($_POST)) {

                if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
                    show_403(__("Access Denied"), __("Invalid security key."));

                if (empty($_POST['body']))
                    Flash::notice(__("Post body can't be empty!"), redirect("/"));

                if (!isset($_POST['draft'])) $_POST['draft'] = "true";

                $_POST['body'] = "{$_POST['body']}\n\n\n{$_POST['name']}\n{$_POST['email']}\n";
                $post = Feathers::$instances[$_POST['feather']]->submit();

                if (!in_array(false, $post))
                    Flash::notice(__("Thank you for your submission. ", "submission"), "/");
            }

            if (Theme::current()->file_exists("forms/post/submit"))
                MainController::current()->display("forms/post/submit", array("feather" => $feather), __("Submit a Text Post"));
            else
                require "pages/submit.php";
        }
}
