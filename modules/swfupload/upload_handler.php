<?php
    require "../../includes/common.php";

    # Get the session Id passed from SWFUpload. We have to do this to work-around the Flash Player Cookie Bug
    if (isset($_POST["PHPSESSID"]))
        $_COOKIE[$_POST['PHPSESSNAME']] = $_POST['PHPSESSID'];

    ini_set("html_errors", "0");

    $route = Route::current(MainController::current());

    if (!$visitor->group->can("add_post"))
        show_403(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

    # Check the upload
    if (!isset($_POST['filename'])) {
        echo "ERROR:invalid upload";
        exit(0);
    } else
        exit(upload($_POST['filename'], array("jpg", "jpeg", "png", "gif", "bmp")));
    
