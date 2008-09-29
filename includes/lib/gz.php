<?php
    $valid_files = "jquery.js plugins.js";
    if (!in_array($_GET['file'], explode(" ", $valid_files)) and strpos($_GET['file'], "/themes/") === false)
        exit("Access Denied.");

    if (strpos($_GET['file'], "/themes/") and strpos($_GET['file'], ".."))
        exit("GTFO.");

    if (extension_loaded('zlib')) {
        ob_start("ob_gzhandler");
        header("Content-Encoding: gzip");
    } else
        ob_start();

    header("Content-Type: application/x-javascript");

    if (strpos($_GET['file'], "/themes/") === 0) {
        # Constant: MAIN_DIR
        # Absolute path to the Chyrp root
        define('MAIN_DIR', dirname(dirname(dirname(__FILE__))));

        header("Last-Modified: ".date("r", filemtime(MAIN_DIR.$_GET['file'])));

        if (file_exists(MAIN_DIR.$_GET['file']))
            readfile(MAIN_DIR.$_GET['file']);
        else
            echo "alert('File not found: ".addslashes($_GET['file'])."')";
    } elseif (file_exists($_GET['file'])) {
        header("Last-Modified: ".date("r", filemtime($_GET['file'])));
        readfile($_GET['file']);
    } else
        echo "alert('File not found: ".addslashes($_GET['file'])."')";

    ob_end_flush();

