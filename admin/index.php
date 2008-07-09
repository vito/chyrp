<?php
	define('ADMIN', true);

	require_once "../includes/common.php";

	$action = ($_GET['action'] == "index" or !isset($_GET['action'])) ? $admin->determine_action() : $_GET['action'];

	class AdminTwig {
		public function __construct() {
			$this->twig = new Twig_Loader(MAIN_DIR."/admin/layout", (is_writable(INCLUDES_DIR."/caches") and !DEBUG) ? INCLUDES_DIR."/caches" : null);
		}

		public function load($action) {
			global $admin, $theme;

			# Are there any extension-added pages?
			$trigger = Trigger::current();
			foreach (array("write" => array(),
			               "manage" => array(),
			               "settings" => array(),
			               "extend" => array("modules", "feathers", "themes")) as $main_nav => $val) {
				$$main_nav = $val;
				$trigger->filter($$main_nav, $main_nav."_pages");
			}

			$admin->context["theme"]       = $theme;
			$admin->context["flash"]       = Flash::current();
			$admin->context["trigger"]     = $trigger;
			$admin->context["title"]       = camelize($action, true);
			$admin->context["site"]        = Config::current();
			$admin->context["visitor"]     = Visitor::current();
			$admin->context["logged_in"]   = logged_in();
			$admin->context["route"]       = array("action" => $action);
			$admin->context["hide_admin"]  = isset($_SESSION["chyrp_hide_admin"]);
			$admin->context["now"]         = now();
			$admin->context["now_server"]  = time();
			$admin->context["version"]     = CHYRP_VERSION;
			$admin->context["POST"]        = $_POST;
			$admin->context["GET"]         = $_GET;

			$admin->context["selected"]   = array("write"    => (in_array($action, $write) or match("/^write_/", $action)) ?
			                                                    "selected" :
			                                                    "deselected",
			                                      "manage"   => (in_array($action, $manage) or match(array("/^manage_/", "/^edit_/", "/^delete_/"), $action)) ?
			                                                    "selected" :
			                                                    "deselected",
			                                      "settings" => (in_array($action, $settings) or match("/_settings$/", $action)) ?
			                                                    "selected" :
			                                                    "deselected",
			                                      "extend"   => (in_array($action, $extend)) ?
			                                                    "selected" :
			                                                    "deselected");

			$this->subnav_context();

			$trigger->filter($admin->context["selected"], "nav_selected");

			$admin->context["bookmarklet"] = "javascript:var%20d=document,w=window,e=w.getSelection,k=d.getSelection,x=d.selection,s=(e?e():(k)?k():(x?x.createRange().text:0)),f='".$admin->context["site"]->url."/includes/bookmarklet.php',l=d.location,e=encodeURIComponent,p='?url='+e(l.href)+'&title='+e(d.title)+'&selection='+e(s),u=f+p;a=function(){if(!w.open(u,'t','toolbar=0,resizable=0,status=1,width=450,height=430'))l.href=u;};if(/Firefox/.test(navigator.userAgent))setTimeout(a,0);else%20a();void(0)";

			if (method_exists($admin, $action))
				$admin->$action();

			$trigger->call("admin_".$action);

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
			global $admin, $action;

			$trigger = Trigger::current();

			$admin->context["subnav"] = array();
			$subnav =& $admin->context["subnav"];

			$subnav["write"] = array();
			$pages = array("manage" => array());

			foreach (Config::current()->enabled_feathers as $index => $feather) {
				$info = Horde_Yaml::loadFile(FEATHERS_DIR."/".$feather."/info.yaml");
				$subnav["write"]["write_post&feather=".$feather] = array("title" => __($info["name"], $feather),
				                                                         "attributes" => ' id="list_feathers['.$feather.']"',
				                                                         "selected" => (isset($_GET['feather']) and $_GET['feather'] == $feather) or
				                                                                       (!isset($_GET['feather']) and $action == "write_post" and !$index));
			}

			# Write navs
			$subnav["write"]["write_page"] = array("title" => __("Page"));
			$trigger->filter($subnav["write"], array("admin_write_nav", "write_nav"));
			$pages["write"] = array_merge(array("write_post"), array_keys($subnav["write"]));;

			# Manage navs
			$subnav["manage"] = array("manage_posts"  => array("title" => __("Posts"), "selected" => array("edit_post", "delete_post")),
			                          "manage_pages"  => array("title" => __("Pages"), "selected" => array("edit_page", "delete_page")),
			                          "manage_users"  => array("title" => __("Users"), "selected" => array("edit_user", "delete_user")),
			                          "manage_groups" => array("title" => __("Groups"), "selected" => array("edit_group", "delete_group")));
			$trigger->filter($subnav["manage"], "manage_nav");

			$subnav["manage"]["import"] = array("title" => __("Import"));
			$subnav["manage"]["export"] = array("title" => __("Export"));

			foreach (array_keys($subnav["manage"]) as $manage)
				$pages["manage"] = array_merge($pages["manage"], array($manage,
				                                                       preg_replace("/manage_(.+)/e",
				                                                                    "'edit_'.depluralize('\\1')",
				                                                                    $manage),
				                                                       preg_replace("/manage_(.+)/e",
				                                                                    "'delete_'.depluralize('\\1')",
				                                                                    $manage)));

			# Settings navs
			$subnav["settings"] = array("general_settings" => array("title" => __("General")),
			                            "content_settings" => array("title" => __("Content")),
			                            "user_settings"    => array("title" => __("Users")),
			                            "route_settings"   => array("title" => __("Routes")));
			$trigger->filter($subnav["settings"], "settings_nav");
			$pages["settings"] = array_keys($subnav["settings"]);

			# Extend navs
			$subnav["extend"] = array("modules"  => array("title" => __("Modules")),
			                          "feathers" => array("title" => __("Feathers")),
			                          "themes"   => array("title" => __("Themes")));
			$trigger->filter($subnav["extend"], "extend_nav");
			$pages["extend"] = array_keys($subnav["extend"]);

			foreach (array("write", "manage", "settings", "extend") as $main_nav)
				foreach ($trigger->filter($pages[$main_nav], $main_nav."_nav_pages") as $extend)
					$subnav[$extend] =& $subnav[$main_nav];

			$trigger->filter($subnav, "admin_subnav");
		}
	}

	$twig = new AdminTwig();

	if ($action == "help")
		require "help.php";
	else
		$twig->load($action);
