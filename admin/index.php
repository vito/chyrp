<?php
	define('ADMIN', true);
	define('AJAX', isset($_POST['ajax']) and $_POST['ajax'] == "true");

	require_once "../includes/common.php";

	$route->init($admin);
