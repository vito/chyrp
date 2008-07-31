<?php
	define('ADMIN', true);
	define('AJAX', isset($_POST['ajax']) and $_POST['ajax'] == "true");

	require_once "../includes/common.php";

	$action =& $_GET['action'];

	if ($action == "index" or !isset($_GET['action']))
		$action = $admin->determine_action();

	$admin->handle_redirects($action);

	class AdminTwig {
		public function __construct() {
			$this->twig = new Twig_Loader(MAIN_DIR."/admin/layout", (is_writable(INCLUDES_DIR."/caches") and !DEBUG) ? INCLUDES_DIR."/caches" : null);
		}

		public function load($action) {
			global $feathers, $modules;

			# Are there any extension-added pages?
			$trigger = Trigger::current();
			foreach (array("write" => array(),
			               "manage" => array("import", "export"),
			               "settings" => array(),
			               "extend" => array("modules", "feathers", "themes")) as $main_nav => $val) {
				$$main_nav = $val;
				$trigger->filter($$main_nav, $main_nav."_pages");
			}

			$visitor = Visitor::current();
			$admin = AdminController::current();

			$admin->context["theme"]       = Theme::current();
			$admin->context["flash"]       = Flash::current();
			$admin->context["trigger"]     = $trigger;
			$admin->context["title"]       = camelize($action, true);
			$admin->context["site"]        = Config::current();
			$admin->context["visitor"]     = $visitor;
			$admin->context["logged_in"]   = logged_in();
			$admin->context["route"]       = Route::current();
			$admin->context["hide_admin"]  = isset($_SESSION["hide_admin"]);
			$admin->context["now"]         = time();
			$admin->context["version"]     = CHYRP_VERSION;
			$admin->context["debug"]       = DEBUG;
			$admin->context["feathers"]    = $feathers;
			$admin->context["modules"]     = $modules;
			$admin->context["POST"]        = $_POST;
			$admin->context["GET"]         = $_GET;

			$admin->context["navigation"] = array();

			$show = array("write" => array($visitor->group()->can("add_draft", "add_post", "add_page")),
			              "manage" => array($visitor->group()->can("view_own_draft",
			                                                       "view_draft",
			                                                       "edit_own_draft",
			                                                       "edit_own_post",
			                                                       "edit_post",
			                                                       "delete_own_draft",
			                                                       "delete_own_post",
			                                                       "delete_post",
			                                                       "add_page",
			                                                       "edit_page",
			                                                       "delete_page",
			                                                       "add_user",
			                                                       "edit_user",
			                                                       "delete_user",
			                                                       "add_group",
			                                                       "edit_group",
			                                                       "delete_group")),
			              "settings" => array($visitor->group()->can("change_settings")),
			              "extend" => array($visitor->group()->can("toggle_extensions")));

			foreach ($show as $name => &$arr)
				$trigger->filter($arr, $name."_nav_show");

			$admin->context["navigation"]["write"] = array("title" => __("Write"),
			                                               "show" => in_array(true, $show["write"]),
			                                               "selected" => (in_array($action, $write) or
			                                                             match("/^write_/", $action)));

			$admin->context["navigation"]["manage"] = array("title" => __("Manage"),
			                                                "show" => in_array(true, $show["manage"]),
			                                                "selected" => (in_array($action, $manage) or
			                                                              match(array("/^manage_/",
			                                                                          "/^edit_/",
			                                                                          "/^delete_/",
			                                                                          "/^new_/"), $action)));

			$admin->context["navigation"]["settings"] = array("title" => __("Settings"),
			                                                  "show" => in_array(true, $show["settings"]),
			                                                  "selected" => (in_array($action, $settings) or
			                                                                match("/_settings$/", $action)));

			$admin->context["navigation"]["extend"] = array("title" => __("Extend"),
			                                                "show" => in_array(true, $show["extend"]),
			                                                "selected" => (in_array($action, $extend)));

			$this->subnav_context();

			$trigger->filter($admin->context["selected"], "nav_selected");

			if (method_exists($admin, $action))
				$admin->$action();

			$trigger->call("admin_".$action, $admin);

			$admin->context["sql_debug"]  = SQL::current()->debug;

			$template = MAIN_DIR."/admin/layout/pages/".$action.".twig";

			$config = Config::current();
			if (!file_exists($template)) {
				foreach (array(MODULES_DIR => $config->enabled_modules, FEATHERS_DIR => $config->enabled_feathers) as $path => $try)
					foreach ($try as $extension)
						if (file_exists($path."/".$extension."/pages/admin/".$action.".twig"))
							$template = $path."/".$extension."/pages/admin/".$action.".twig";

				if (!file_exists($template))
					error(__("Template Missing"), _f("Couldn't load template:<br /><br />%s", array("pages/".$action.".twig")));
			}

			return $this->twig->getTemplate($template)->display($admin->context);
		}

		public function subnav_context() {
			global $action;

			$trigger = Trigger::current();
			$visitor = Visitor::current();
			$admin   = AdminController::current();

			$admin->context["subnav"] = array();
			$subnav =& $admin->context["subnav"];

			$subnav["write"] = array();
			$pages = array("manage" => array());

			foreach (Config::current()->enabled_feathers as $index => $feather) {
				$info = Horde_Yaml::loadFile(FEATHERS_DIR."/".$feather."/info.yaml");
				$subnav["write"]["write_post&feather=".$feather] = array("title" => __($info["name"], $feather),
			                                                             "show" => $visitor->group()->can("add_draft", "add_post"),
				                                                         "attributes" => ' id="list_feathers['.$feather.']"',
				                                                         "selected" => (isset($_GET['feather']) and $_GET['feather'] == $feather) or
				                                                                       (!isset($_GET['feather']) and $action == "write_post" and !$index));
			}

			# Write navs
			$subnav["write"]["write_page"] = array("title" => __("Page"),
			                                       "show" => $visitor->group()->can("add_page"));
			$trigger->filter($subnav["write"], array("admin_write_nav", "write_nav"));
			$pages["write"] = array_merge(array("write_post"), array_keys($subnav["write"]));;

			# Manage navs
			$subnav["manage"] = array("manage_posts"  => array("title" => __("Posts"),
			                                                   "show" => (Post::any_editable() or Post::any_deletable()),
			                                                   "selected" => array("edit_post", "delete_post")),
			                          "manage_pages"  => array("title" => __("Pages"),
			                                                   "show" => ($visitor->group()->can("edit_page", "delete_page")),
			                                                   "selected" => array("edit_page", "delete_page")),
			                          "manage_users"  => array("title" => __("Users"),
			                                                   "show" => ($visitor->group()->can("add_user",
			                                                                                     "edit_user",
			                                                                                     "delete_user")),
			                                                   "selected" => array("edit_user", "delete_user", "new_user")),
			                          "manage_groups" => array("title" => __("Groups"),
			                                                   "show" => ($visitor->group()->can("add_group",
			                                                                                     "edit_group",
			                                                                                     "delete_group")),
			                                                   "selected" => array("edit_group", "delete_group", "new_group")));
			$trigger->filter($subnav["manage"], "manage_nav");

			$subnav["manage"]["import"] = array("title" => __("Import"),
			                                    "show" => ($visitor->group()->can("add_post")));
			$subnav["manage"]["export"] = array("title" => __("Export"),
			                                    "show" => ($visitor->group()->can("add_post")));

			$pages["manage"][] = "new_user";
			$pages["manage"][] = "new_group";
			foreach (array_keys($subnav["manage"]) as $manage)
				$pages["manage"] = array_merge($pages["manage"], array($manage,
				                                                       preg_replace("/manage_(.+)/e",
				                                                                    "'edit_'.depluralize('\\1')",
				                                                                    $manage),
				                                                       preg_replace("/manage_(.+)/e",
				                                                                    "'delete_'.depluralize('\\1')",
				                                                                    $manage)));

			# Settings navs
			$subnav["settings"] = array("general_settings" => array("title" => __("General"),
			                                                        "show" => $visitor->group()->can("change_settings")),
			                            "content_settings" => array("title" => __("Content"),
			                                                        "show" => $visitor->group()->can("change_settings")),
			                            "user_settings"    => array("title" => __("Users"),
			                                                        "show" => $visitor->group()->can("change_settings")),
			                            "route_settings"   => array("title" => __("Routes"),
			                                                        "show" => $visitor->group()->can("change_settings")));
			$trigger->filter($subnav["settings"], "settings_nav");
			$pages["settings"] = array_keys($subnav["settings"]);

			# Extend navs
			$subnav["extend"] = array("modules"  => array("title" => __("Modules"),
			                                              "show" => $visitor->group()->can("toggle_extensions")),
			                          "feathers" => array("title" => __("Feathers"),
			                                              "show" => $visitor->group()->can("toggle_extensions")),
			                          "themes"   => array("title" => __("Themes"),
			                                              "show" => $visitor->group()->can("toggle_extensions")));
			$trigger->filter($subnav["extend"], "extend_nav");
			$pages["extend"] = array_keys($subnav["extend"]);

			foreach (array_keys($subnav) as $main_nav)
				foreach ($trigger->filter($pages[$main_nav], $main_nav."_nav_pages") as $extend)
					$subnav[$extend] =& $subnav[$main_nav];

			foreach ($subnav as $main_nav => &$sub_nav)
				foreach ($sub_nav as &$nav)
					$nav["show"] = (!isset($nav["show"]) or $nav["show"]);

			$trigger->filter($subnav, "admin_subnav");
		}
	}

	$twig = new AdminTwig();

	if ($action == "help")
		require "help.php";
	else
		$twig->load($action);
