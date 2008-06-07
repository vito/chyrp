<?php
	class Cacher extends Module {
		public function __construct() {
			$this->user = (logged_in()) ? Visitor::current()->login : "guest" ;
			$this->caches = dirname(__FILE__)."/cache";
			$this->expire_file = $this->caches."/LAST_EXPIRED";
			$this->path = dirname(__FILE__)."/cache/".$this->user;
			$this->url = self_url();
			$this->file = $this->path."/".md5($this->url).".html";

			# If the cache directory is not writable, disable this module and cancel execution.
			if (!is_writable($this->caches))
				cancel_module("cacher");

			$regenerate = array("add_post",    "add_page",    "add_comment",
			                    "update_post", "update_page", "update_comment",
			                    "delete_post", "delete_page", "delete_comment");
			foreach ($regenerate as $action)
				$this->addAlias($action, "regenerate");

			# Generate the user's directory.
			if (!file_exists($this->path))
				mkdir($this->path);

			if (!file_exists($this->expire_file))
				touch($this->expire_file);

			if (time() - filemtime($this->expire_file) > Config::current()->cache_expire)
				$this->regenerate();
		}

		public function __install() {
			$config = Config::current();
			$config->set("cache_expire", 1800);
		}

		public function __uninstall() {
			$config = Config::current();
			$config->remove("cache_expire");
		}

		public function runtime() {
			if (!file_exists($this->file))
				return;

			error_log("SERVING cache file for ".$this->url."...");
			exit(file_get_contents($this->file));
		}

		public function bottom() {
			if (file_exists($this->file))
				return;

			error_log("GENERATING cache file for ".$this->url."...");
			file_put_contents($this->file, ob_get_contents());
		}

		public function regenerate() {
			error_log("REGENERATING");

			touch($this->expire_file);
			foreach (glob($this->caches."/*/*.html") as $file)
				unlink($file);
		}

		public function regenerate_local() {
			error_log("REGENERATING local user ".$this->user."...");

			foreach (glob($this->path."/*.html") as $file)
				unlink($file);
		}

		public function update_user($user) {
			if ($user->login == $this->user)
				$this->regenerate_local();
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