<?php
    # Register the AutoLoad function
    require_once("lib/Dropbox/AutoLoader.php");

    class Dropbox extends Modules {
        static function __install() {
            $set = array(Config::current()->set("module_dropbox",
                                          array("app_key"      => null,
                                                "app_secret"   => null,
                                                "oauth_token"  => null,
                                                "oauth_secret" => null,
                                                "uid"          => null)));
        }

        static function __uninstall() {
            Config::current()->remove("module_dropbox");
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
                	echo $e->getMessage() . PHP_EOL;
                	exit("Setup failed! Please try running setup again.");
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

            # The user is redirected here by Dropbox after the authorization screen
            $config = Config::current();
            $app_key    = $config->module_dropbox["app_key"];
            $app_secret = $config->module_dropbox["app_secret"];
            $storage = new \Dropbox\OAuth\Storage\Session;
            $OAuth = new \Dropbox\OAuth\Consumer\Curl($app_key, $app_secret, $storage);

            # Acquire the access token
            $token_data = get_object_vars($storage->get("access_token"));

            $set = array($config->set("module_dropbox",
                                array("app_key" => $app_key,
                                      "app_secret" => $app_secret,
                                      "oauth_secret" => $token_data["oauth_token_secret"],
                                      "oauth_token" => $token_data["oauth_token"],
                                      "uid" => $token_data["uid"])));

            if (!in_array(false, $set))
                Flash::notice(__("Dropbox was successfully authorized."), "/admin/?action=dropbox_settings");
            else
                Flash::warning(__("Dropbox couldn't be authorized."), "/admin/?action=dropbox_settings");
        }

        function parse_url_detail($url) {
            $parts = parse_url($url);

            if(isset($parts["query"]))
                parse_str(urldecode($parts["query"]), $parts["query"]);

            return $parts;
        }
    }
