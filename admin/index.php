<?php
	define('ADMIN', true);

	require_once "../includes/common.php";

	$action = fallback($_GET['action'], $admin->determine_action(), true);

	class AdminTwig {
		public function __construct() {
			global $action;
			$this->twig = new Twig_Loader(MAIN_DIR."/admin/layout/", (is_writable(MAIN_DIR."/admin/layout/cache") and DEBUG) ? MAIN_DIR."/admin/layout/cache" : null);
		}

		public function load($action) {
			global $admin, $paginate, $theme;

			$trigger = Trigger::current();
			$write    = $trigger->filter("write_pages", array());
			$manage   = $trigger->filter("manage_pages", array());
			$settings = $trigger->filter("settings_pages", array());
			$extend   = $trigger->filter("extend_pages", array());

			$admin->context["title"]      = camelize($action, true);
			$admin->context["site"]       = Config::current();
			$admin->context["visitor"]    = Visitor::current();
			$admin->context["logged_in"]  = logged_in();
			$admin->context["stats"]      = array("load" => timer_stop(), "queries" => SQL::current()->queries);
			$admin->context["route"]      = array("action" => $action);
			$admin->context["hide_admin"] = isset($_SESSION["chyrp_hide_admin"]);
			$admin->context["archives"]   = $theme->list_archives();
			$admin->context["pagination"] = $paginate;
			$admin->context["now"]        = time() + Config::current()->time_offset;
			$admin->context["now_server"] = time();
			$admin->context["version"]    = CHYRP_VERSION;
			$admin->context["POST"]       = $_POST;
			$admin->context["GET"]        = $_GET;

			$admin->context["selected"]   = array("write"    => (in_array($action, $write) or match("/^write_/", $action)) ?
			                                                     "selected" :
			                                                     "deselected",
			                                      "manage"   => (in_array($action, $manage) or match(array("/^manage_/", "/^edit_/", "/^delete_/"), $action)) ?
			                                                    "selected" :
			                                                    "deselected",
			                                      "settings" => (in_array($action, $settings) or match("/_settings$/", $action)) ?
			                                                    "selected" :
			                                                    "deselected",
			                                      "extend"   => (in_array($action, $extend) or match("/^extend_/", $action)) ?
			                                                    "selected" :
			                                                    "deselected");

			$admin->context["selected"] = $trigger->filter("nav_selected", $admin->context["selected"]);

			$admin->context["bookmarklet"] = "javascript:var%20d=document,w=window,e=w.getSelection,k=d.getSelection,x=d.selection,s=(e?e():(k)?k():(x?x.createRange().text:0)),f='".$admin->context["site"]->url."/includes/bookmarklet.php',l=d.location,e=encodeURIComponent,p='?url='+e(l.href)+'&title='+e(d.title)+'&selection='+e(s),u=f+p;a=function(){if(!w.open(u,'t','toolbar=0,resizable=0,status=1,width=450,height=430'))l.href=u;};if(/Firefox/.test(navigator.userAgent))setTimeout(a,0);else%20a();void(0)";

			if (method_exists($admin, $action))
				$admin->$action();

			Trigger::current()->call("admin_".$action);

			$admin->context["sql_debug"]  = SQL::current()->debug;

			if (!file_exists(MAIN_DIR."/admin/layout/pages/".$action.".twig"))
				error(__("Template Missing"), sprintf(__("Couldn't load template:<br /><br />%s"),"pages/".$action.".twig"));

			return $this->twig->getTemplate("pages/".$action.".twig")->display($admin->context);
		}
	}

	$twig = new AdminTwig();

	if ($action == "help")
		require "help.php";
	else
		$twig->load($action);
