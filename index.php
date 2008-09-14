<?php
	require_once "includes/common.php";

	$trigger->call("top");

	$route->init($main);

	if (!$route->success and !$main->displayed) {
		# Unknown action. Check for:
		#     1. Module-provided pages.
		#     2. Feather-provided pages.
		#     3. Theme-provided pages.

		$displayed = false;

		foreach ($config->enabled_modules as $module)
			if (file_exists(MODULES_DIR."/".$module."/pages/".$route->action.".php"))
				$displayed = require MODULES_DIR."/".$module."/pages/".$route->action.".php";

		if (!$displayed)
			foreach ($config->enabled_feathers as $feather)
				if (file_exists(FEATHERS_DIR."/".$feather."/pages/".$route->action.".php"))
					$displayed = require FEATHERS_DIR."/".$feather."/pages/".$route->action.".php";

		if (!$displayed and $theme->file_exists("pages/".$route->action))
			$theme->load("pages/".$route->action);
		elseif (!$displayed)
			show_404();
	}

	$trigger->call("bottom");

	ob_end_flush();
