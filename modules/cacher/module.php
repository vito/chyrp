<?php
	class Cacher extends Module {
		public function __init() {
			$this->user = (logged_in()) ? Visitor::current()->login : "guest" ;
			$this->caches = dirname(__FILE__)."/cache";
			$this->path = dirname(__FILE__)."/cache/".sanitize($this->user);
			$this->url = self_url();
			$this->file = $this->path."/".md5($this->url).".html";

			# If the cache directory is not writable, disable this module and cancel execution.
			if (!is_writable($this->caches))
				cancel_module("cacher");

			# Prepare actions that should result in new cache files.
			$this->prepare_cache_updaters();

			# Remove all expired files.
			$this->remove_expired();
		}

		static function __install() {
			$config = Config::current();
			$config->set("cache_expire", 1800);
		}

		static function __uninstall() {
			$config = Config::current();
			$config->remove("cache_expire");
		}

		public function runtime() {
			if (!file_exists($this->file))
				return;

			if (DEBUG)
				error_log("SERVING cache file for ".$this->url."...");

			exit(file_get_contents($this->file));
		}

		public function bottom() {
			if (file_exists($this->file))
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
			                    "delete_post", "delete_page");
			foreach ($regenerate as $action)
				$this->addAlias($action, "regenerate");

			foreach (array("add_comment", "update_comment", "delete_comment") as $action)
				$this->addAlias($action, "remove_post_cache");
		}

		public function remove_expired() {
			foreach (glob($this->caches."/*/*.html") as $file) {
				if (time() - filemtime($file) > Config::current()->cache_expire)
					unlink($file);

				$dir = dirname($file);
				if (!count(glob($dir."/*")))
					rmdir($dir);
			}
		}

		public function regenerate() {
			if (DEBUG)
				error_log("REGENERATING");

			foreach (glob($this->caches."/*/*.html") as $file)
				unlink($file);
		}

		public function regenerate_local($user = null) {
			if (DEBUG)
				error_log("REGENERATING local user ".$this->user."...");

			$directory = (isset($user)) ? $this->caches."/".$user : $this->path ;
			foreach (glob($directory."/*.html") as $file)
				unlink($file);
		}

		public function remove_caches_for($url) {
			if (DEBUG)
				error_log("REMOVING caches for ".$url."...");

			foreach (glob($this->caches."/*/".md5($url).".html") as $file)
				unlink($file);
		}

		public function remove_post_cache($comment) {
			$this->remove_caches_for($comment->post()->url());
		}

		public function update_user($user) {
			$this->regenerate_local(sanitize($user->login));
		}

		public function settings_nav($navs) {
			if (Visitor::current()->group()->can("change_settings"))
				$navs["cache_settings"] = array("title" => __("Cache", "cacher"));

			return $navs;
		}

		public function admin_cache_settings() {
			global $admin;

			if (!Visitor::current()->group()->can("change_settings"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

			if (empty($_POST))
				return;

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				error(__("Access Denied"), __("Invalid security key."));

			Config::current()->set("cache_expire", $_POST['cache_expire']);

			$admin->context["updated"] = true;
		}
	}