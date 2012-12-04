<?php
    # Register the AutoLoad function
    require_once("lib/Dropbox/AutoLoader.php");
    require_once("model.dropbox.php");
    require_once(INCLUDES_DIR."/lib/FrontMatter.php");

    class Dropbox extends Modules {
        static function __install() {
            $set = array(Config::current()->set("module_dropbox",
                                          array("app_key"      => null,
                                                "app_secret"   => null,
                                                "oauth_token"  => null,
                                                "oauth_secret" => null,
                                                "user_id"      => null)));
        }

        static function __uninstall() {
            Config::current()->remove("module_dropbox");
        }

        static function manage_nav_pages($pages) {
            array_push($pages, "manage_dropbox");
            return $pages;
        }

        static function manage_nav($navs) {
            if (!Visitor::current()->group->can("add_post", "add_draft"))
                return $navs;
    
            $navs["manage_dropbox"] = array("title" => __("Dropbox Sync", "dropbox"),
                                            "selected" => array("manage_dropbox"));
    
            return $navs;
        }
    
        static function settings_nav($navs) {
            if (Visitor::current()->group->can("change_settings"))
                $navs["dropbox_settings"] = array("title" => __("Dropbox", "dropbox"));

            return $navs;
        }

        static function admin_dropbox_settings($admin) {
            $config = Config::current();

            if (!Visitor::current()->group->can("change_settings"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

            if (empty($_POST))
                return $admin->display("dropbox_settings");

            if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
                show_403(__("Access Denied"), __("Invalid security key."));

            if (isset($_POST['authorize'])) {
                $app_key    = $config->module_dropbox["app_key"];
                $app_secret = $config->module_dropbox["app_secret"];
                $callback   = url("/admin/?action=dropbox_oauth");

                try {
                	$storage = new \Dropbox\OAuth\Storage\Session;
                	# if (!$storage->get("access_token")) $storage->delete();
                	$storage->delete();
                	$OAuth = new \Dropbox\OAuth\Consumer\Curl($app_key, $app_secret, $storage, $callback);

                	# Build authorize URL and redirect to Dropbox
                	redirect($OAuth->getAuthoriseURL());
                } catch(\Dropbox\Exception $e) {
                	error("Dropbox Sync Error!", $e->getMessage());
                }
            }

            $set = array($config->set("module_dropbox",
                                array("app_key" => trim($_POST['app_key']),
                                      "app_secret" => trim($_POST['app_secret']))));

            if (!in_array(false, $set))
                Flash::notice(__("Settings updated."), "/admin/?action=dropbox_settings");
        }

        static function admin_dropbox_oauth($admin) {
            if (!Visitor::current()->group->can("change_settings"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

            if (!empty($_GET["uid"]) and !empty($_GET["oauth_token"])) {
                # The user is redirected here by Dropbox after the authorization screen
                $config = Config::current();
                $app_key    = $config->module_dropbox["app_key"];
                $app_secret = $config->module_dropbox["app_secret"];
                $storage = new \Dropbox\OAuth\Storage\Session;
                $OAuth = new \Dropbox\OAuth\Consumer\Curl($app_key, $app_secret, $storage);

                # Acquire the access token
                $token_data = get_object_vars($storage->get("access_token"));

                $set = array($config->set("module_dropbox",
                                    array("app_key"      => $app_key,
                                          "app_secret"   => $app_secret,
                                          "oauth_secret" => $token_data["oauth_token_secret"],
                                          "oauth_token"  => $token_data["oauth_token"],
                                          "user_id"  => $token_data["uid"])));

                if (!in_array(false, $set))
                    Flash::notice(__("Dropbox was successfully authorized.", "dropbox"), "/admin/?action=dropbox_settings");
            } elseif (isset($_GET["not_approved"]))
                    Flash::notice(__("Fine! You'll authorize it some other time.", "dropbox"), "/admin/?action=dropbox_settings");
        }


        static function sync_posts() {
            if (!Visitor::current()->group->can("add_post", "add_draft"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

            $path = "2012-11-26.md";
            $dropbox = new Dropbox;
            $file = $dropbox->get_file($path);

            $post = new FrontMatter($file);

            $data = array_merrge($post->fetch("title"), $post->fetch("content"));
            $post = Post::add($data,
                              $post->fetch("slug"),
                              Post::check_url($chyrp->url),
                              "text",
                              ($user_id ? $user_id : $visitor->id),
                              (bool) (int) $post->fetch("pinned"),
                              "public",
                              datetime($post->fetch("date")),
                              datetime($post->fetch("date")),
                              false);

            Flash::notice(_f("Post updated. <a href=\"%s\">View Post &rarr;</a>",
                             array($post->url())),
                             "/admin/?action=manage_posts");
            # Flash::notice(__("Post imported successfully."), "/admin/?action=manage_posts");
        }

        function parse_url_detail($url) {
            $parts = parse_url($url);

            if(isset($parts["query"]))
                parse_str(urldecode($parts["query"]), $parts["query"]);

            return $parts;
        }
    }
