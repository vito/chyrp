<?php
	if ($_GET['file'] != "jquery.js" and $_GET['file'] != "forms.js")
		exit("gtfo.");
	
	if (extension_loaded('zlib')) {
		ob_start("ob_gzhandler");
		header("Content-Encoding: gzip");
	} else
		ob_start();
	
	header("Content-Type: application/x-javascript");
	header("Last-Modified: ".@date("r", filemtime($_GET['file'])));
	
	readfile($_GET['file']);
	
	ob_end_flush();
?>