<?php
    require "../../includes/common.php";

    # Get the session Id passed from SWFUpload. We have to do this to work-around the Flash Player Cookie Bug
    if (isset($_POST["PHPSESSID"])) {
        $_COOKIE[$_POST['PHPSESSNAME']] = $_POST['PHPSESSID'];
        session_id($_POST["PHPSESSID"]);
    }

    session_start();
    ini_set("html_errors", "0");

    $route = Route::current(MainController::current());

    if (!$visitor->group->can("add_post"))
        show_403(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

    # Check the upload
    if (!isset($_FILES["Filedata"]) || !is_uploaded_file($_FILES["Filedata"]["tmp_name"]) || $_FILES["Filedata"]["error"] != 0) {
        echo "ERROR:invalid upload";
        exit(0);
    }

    exit(upload($_FILES['Filedata']));
