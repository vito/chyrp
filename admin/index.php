<?php
	define('ADMIN', true);

	require_once "../includes/common.php";

	$action = fallback($_GET['action'], $admin->determine_action(), true);

	class AdminTwig {
		public function __construct() {
			global $action;
			if (!DEBUG)
				$this->twig = new Twig_Loader(MAIN_DIR."/admin/layout/", (is_writable(MAIN_DIR."/admin/layout/cache") ? MAIN_DIR."/admin/layout/cache" : null));
			else
				$this->twig = new Twig_Loader(MAIN_DIR."/admin/layout/", null);
		}

		public function load($action) {
			global $admin, $paginate;

			$admin->context["title"]      = camelize($action, true);
			$admin->context["site"]       = Config::current();
			$admin->context["visitor"]    = Visitor::current();
			$admin->context["logged_in"]  = logged_in();
			$admin->context["stats"]      = array("load" => timer_stop(), "queries" => SQL::current()->queries);
			$admin->context["route"]      = array("action" => $action);
			$admin->context["hide_admin"] = isset($_COOKIE["chyrp_hide_admin"]);
			$admin->context["sql_debug"]  = SQL::current()->debug;
			$admin->context["pagination"] = $paginate;
			$admin->context["POST"]       = $_POST;
			$admin->context["GET"]        = $_GET;

			if (method_exists($admin, $action))
				$admin->$action();

			Trigger::current()->call("admin_".$action);

			if (!file_exists(MAIN_DIR."/admin/layout/pages/".$action.".twig"))
				error(__("Template Missing"), sprintf(__("Couldn't load template:<br /><br />%s"),"pages/".$action.".twig"));

			return $this->twig->getTemplate("pages/".$action.".twig")->display($admin->context);
		}
	}

	$twig = new AdminTwig();

	if ($action == "help")
		require FEATHERS_DIR."/".$_GET['feather']."/help.php";
	else
		$twig->load($action);
