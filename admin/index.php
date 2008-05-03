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
			global $admin;

			if (!file_exists(MAIN_DIR."/admin/layout/pages/".$action.".twig"))
				error(__("Template Missing"), sprintf(__("Couldn't load template:<br /><br />%s"),"pages/".$action.".twig"));

			$template = $this->twig->getTemplate("pages/".$action.".twig");
			return $template->display($admin->determine_context($action));
		}
	}

	$twig = new AdminTwig();

	if ($action == "help")
		require FEATHERS_DIR."/".$_GET['feather']."/help.php";
	else
		$twig->load($action);
