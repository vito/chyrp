<?php
	$valid_files = "jquery.js forms.js jquery.ui.js jquery.dimensions.js ifixpng.js interface.js";
	if (!in_array($_GET['file'], explode(" ", $valid_files)))
		exit("gtfo.");

	if (extension_loaded('zlib')) {
		ob_start("ob_gzhandler");
		header("Content-Encoding: gzip");
	} else
		ob_start();

	header("Content-Type: application/x-javascript");
	header("Last-Modified: ".@date("r", filemtime($_GET['file'])));

	if (file_exists($_GET['file']))
		readfile($_GET['file']);
	else
		echo "alert('File not found: ".addslashes($_GET['file'])."')";

	ob_end_flush();
?>