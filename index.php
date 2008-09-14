<?php
	require_once "includes/common.php";

	# Prepare the Main Controller.
	$main = MainController::current();

	$trigger->call("top");

	$route->init($main);

	# If the route failed or nothing was displayed, check for:
	#     1. Module-provided pages.
	#     2. Feather-provided pages.
	#     3. Theme-provided pages.
	if (!$route->success or !$main->displayed) {
		$displayed = false;

		foreach ($config->enabled_modules as $module)
			if (file_exists(MODULES_DIR."/".$module."/pages/".$route->action.".php"))
				$displayed = require MODULES_DIR."/".$module."/pages/".$route->action.".php";

		if (!$displayed)
			foreach ($config->enabled_feathers as $feather)
				if (file_exists(FEATHERS_DIR."/".$feather."/pages/".$route->action.".php"))
					$displayed = require FEATHERS_DIR."/".$feather."/pages/".$route->action.".php";

		if (!$displayed and $theme->file_exists("pages/".$route->action))
			$main->display("pages/".$route->action);
		elseif (!$displayed)
			show_404();
	}

	$trigger->call("bottom");

	ob_end_flush();
