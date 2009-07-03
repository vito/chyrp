<?php
    class Cacher extends Modules {
        public function __init() {
            $this->user = (logged_in()) ? Visitor::current()->login : "guest" ;
            $this->path = INCLUDES_DIR."/caches/".sanitize($this->user);

            $this->caches = INCLUDES_DIR."/caches";
            $this->url = self_url();
            $this->file = $this->path."/".md5($this->url).".html";

            # If the cache directory is not writable, disable this module and cancel execution.
            if (!is_writable($this->caches))
                cancel_module("cacher");

            # Prepare actions that should result in new cache files.
            $this->prepare_cache_updaters();

            # Remove all expired files.
            $this->remove_expired();

            $config = Config::current();
            $config->cache_exclude = (array) $config->cache_exclude;

            if (!empty($config->cache_exclude))
                foreach ($config->cache_exclude as &$exclude)
                    if (substr($exclude, 7) != "http://")
                        $exclude = $config->url."/".ltrim($exclude, "/");
        }

        static function __install() {
            $config = Config::current();
            $config->set("cache_expire", 1800);
            $config->set("cache_exclude", array());
        }

        static function __uninstall() {
            $config = Config::current();
            $config->remove("cache_expire");
            $config->remove("cache_exclude");
        }

        public function route_init($route) {
            if (!empty($_POST) or
                !($route->controller instanceof MainController) or
                in_array($this->url, Config::current()->cache_exclude) or
                $this->cancelled or
                !file_exists($this->file) or
                Flash::exists())
                return;

            if (DEBUG)
                error_log("SERVING cache file for ".$this->url."...");

            $cache = file_get_contents($this->file);

            if (substr_count($cache, "<feed"))
                header("Content-Type: application/atom+xml; charset=UTF-8");

            exit($cache);
        }

        public function end($route) {
            if (!($route->controller instanceof MainController) or
                in_array($this->url, Config::current()->cache_exclude) or
                $this->cancelled or
                file_exists($this->file) or
                Flash::exists())
                return;

            if (DEBUG)
                error_log("GENERATING cache file for ".$this->url."...");

            # Generate the user's directory.
            if (!file_exists($this->path))
                mkdir($this->path);

            file_put_contents($this->file, ob_get_contents());
        }

        public function prepare_cache_updaters() {
            $regenerate = array("add_post",    "add_page",
                                "update_post", "update_page",
                                "delete_post", "delete_page",
                                "change_setting");

            Trigger::current()->filter($regenerate, "cacher_regenerate_triggers");
            foreach ($regenerate as $action)
                $this->addAlias($action, "regenerate");

            $post_triggers = array();
            foreach (Trigger::current()->filter($post_triggers, "cacher_regenerate_posts_triggers") as $action)
                $this->addAlias($action, "remove_post_cache");
        }

        public function remove_expired() {
            foreach ((array) glob($this->caches."/*/*.html") as $file) {
                if (time() - filemtime($file) > Config::current()->cache_expire)
                    @unlink($file);

                $dir = dirname($file);
                if (!count((array) glob($dir."/*")))
                    @rmdir($dir);
            }
        }

        public function regenerate() {
            if (DEBUG)
                error_log("REGENERATING");

            foreach ((array) glob($this->caches."/*/*.html") as $file)
                @unlink($file);
        }

        public function regenerate_local($user = null) {
            if (DEBUG)
                error_log("REGENERATING local user ".$this->user."...");

            $directory = (isset($user)) ? $this->caches."/".$user : $this->path ;
            foreach ((array) glob($directory."/*.html") as $file)
                @unlink($file);
        }

        public function remove_caches_for($url) {
            if (DEBUG)
                error_log("REMOVING caches for ".$url."...");

            foreach ((array) glob($this->caches."/*/".md5($url).".html") as $file)
                @unlink($file);
        }

        public function remove_post_cache($thing) {
            $this->remove_caches_for(htmlspecialchars_decode($thing->post()->url()));
        }

        public function update_user($user) {
            $this->regenerate_local(sanitize($user->login));
        }

        public function settings_nav($navs) {
            if (Visitor::current()->group->can("change_settings"))
                $navs["cache_settings"] = array("title" => __("Cache", "cacher"));

            return $navs;
        }

        public function admin_cache_settings($admin) {
            if (!Visitor::current()->group->can("change_settings"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

            if (empty($_POST))
                return $admin->display("cache_settings");

            if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
                show_403(__("Access Denied"), __("Invalid security key."));

            $exclude = (empty($_POST['cache_exclude']) ? array() : explode(", ", $_POST['cache_exclude']));

            $config = Config::current();
            if ($config->set("cache_expire", $_POST['cache_expire']) and $config->set("cache_exclude", $exclude))
                Flash::notice(__("Settings updated."), "/admin/?action=cache_settings");
        }

        public function admin_clear_cache() {
            if (!Visitor::current()->group->can("change_settings"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

            $this->regenerate();

            Flash::notice(__("Cache cleared.", "cacher"), "/admin/?action=cache_settings");
        }
    }
