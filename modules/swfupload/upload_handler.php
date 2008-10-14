<?php
    $_COOKIE[$_POST['PHPSESSNAME']] = $_POST['PHPSESSID'];

    require "../../includes/common.php";

    if (!$visitor->group->can("add_post"))
        show_403(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

    exit(upload($_FILES['Filedata']));
