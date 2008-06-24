<?php
	$valid_files = "jquery.js plugins.js";
	if (!in_array($_GET['file'], explode(" ", $valid_files)) and strpos($_GET['file'], "theme/") === false)
		exit("Access Denied.");

	if (strpos($_GET['file'], "theme/") and strpos($_GET['file'], ".."))
		exit("GTFO.");

	if (extension_loaded('zlib')) {
		ob_start("ob_gzhandler");
		header("Content-Encoding: gzip");
	} else
		ob_start();

	header("Content-Type: application/x-javascript");
	header("Last-Modified: ".date("r", filemtime($_GET['file'])));

	if (strpos($_GET['file'], "theme/") === 0) {
		require "../common.php";
		if (file_exists(THEME_DIR."/javascripts/".substr($_GET['file'], 6)))
			readfile(THEME_DIR."/javascripts/".substr($_GET['file'], 6));
	} elseif (file_exists($_GET['file']))
		readfile($_GET['file']);
	else
		echo "alert('File not found: ".addslashes($_GET['file'])."')";

	ob_end_flush();
?>