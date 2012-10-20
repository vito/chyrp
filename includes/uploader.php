<?php
    require "common.php";

    if (!$visitor->group->can("add_post"))
        show_403(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

    ini_set("html_errors", "0");

    $route = Route::current(MainController::current());

    if (!empty($_FILES)) {
        $_FILES['file']['type'] = strtolower($_FILES['file']['type']);

        # Verify that the file being uploaded is of a supported type
        if ($_FILES['file']['type'] == 'image/png' 
         || $_FILES['file']['type'] == 'image/jpg' 
         || $_FILES['file']['type'] == 'image/gif' 
         || $_FILES['file']['type'] == 'image/jpeg'
         || $_FILES['file']['type'] == 'image/pjpeg') {

            $path = Config::current()->chyrp_url."/uploads/";
            # setting file's mysterious name
            //$file = $dir.md5(date('YmdHis')).'.jpg';

            $file = upload($_FILES['file'], array("jpg", "jpeg", "gif", "png", "bmp"));

            # Send the JSON back to the browser for Redactor
            echo stripslashes(json_encode(array("filelink" => $path.$file)));
        }
    } else {
        error(__("Upload Error"), __("ERROR: Invalid Upload"));
        exit(0);
    }
