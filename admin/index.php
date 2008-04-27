<?php
	define('ADMIN', true);
	require_once "../includes/common.php";

	$action = fallback($_GET['action'], $admin->determine_action(), true);

	class AdminTwig {
		public function __construct() {
			global $action;
			$this->twig = new Twig_Loader(MAIN_DIR."/admin/layout/", null);#, (is_writable(MAIN_DIR."/admin/layout/cache") ? MAIN_DIR."/admin/layout/cache" : null));
		}

		public function load($action) {
			global $admin;
			$template = $this->twig->getTemplate("pages/".$action.".twig");
			return $template->display($admin->determine_context($action));
		}
	}
	$twig = new AdminTwig();

	$twig->load($action);
?>