<?php
	# Constant: ADMIN
	# Is the user in the admin area? (true)
	define('ADMIN', true);

	require_once "../includes/common.php";

	# Prepare the Admin controller.
	$admin = AdminController::current();

	$route->init($admin);

	if (!$route->success or !$admin->displayed)
		$admin->display($route->action); # Attempt to display it; it'll go through Modules and Feathers.

	ob_end_flush();
