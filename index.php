<?php
    if (version_compare(PHP_VERSION, "5.1.3", "<"))
        exit("Chyrp requires PHP 5.1.3 or greater.");

    require_once "includes/common.php";

    # Prepare the controller.
    $main = MainController::current();

    # Parse the route.
    $route = Route::current($main);

    # Check if the user can view the site.
    if (!$visitor->group->can("view_site") and
        !in_array($route->action, array("login", "logout", "register", "lost_password")))
        if ($trigger->exists("can_not_view_site"))
            $trigger->call("can_not_view_site");
        else
            show_403(__("Access Denied"), __("You are not allowed to view this site."));

    # Execute the appropriate Controller responder.
    $route->init();

#     error("Database Error", "Unknown column 'users.where' in 'where clause'
# 
# <pre>SELECT users.*
# FROM users
# WHERE (users.where IN ('alex'))
# ORDER BY users.id DESC
# 
# 
# <pre>Array
# (
# )
# </pre>
# 
# <pre>#0 /var/www/rosiba/includes/class/SQL.php(201): Query->__construct(Object(SQL), 'SELECT users.*?...', Array, false)
# #1 /var/www/rosiba/includes/class/SQL.php(238): SQL->query('SELECT __users....', Array, false)
# #2 /var/www/rosiba/includes/class/Model.php(155): SQL->select(Array, Array, Array, 'id DESC', Array, NULL, NULL, Array, Array)
# #3 /var/www/rosiba/includes/model/User.php(19): Model::grab(Object(User), Array, Array)
# #4 /var/www/rosiba/includes/model/Post.php(684): User->__construct(Array)
# #5 /var/www/rosiba/includes/controller/Main.php(374): Post::from_url(Array, Array)
# #6 [internal function]: MainController->view(Array)
# #7 /var/www/rosiba/includes/class/Route.php(99): call_user_func_array(Array, Array)
# #8 /var/www/rosiba/index.php(22): Route->init()
# #9 {main}</pre>");

    # If the route failed or nothing was displayed, check for:
    #     1. Module-provided pages.
    #     2. Feather-provided pages.
    #     3. Theme-provided pages.
    if (!$route->success and !$main->displayed) {
        $displayed = false;

        foreach ($config->enabled_modules as $module)
            if (file_exists(MODULES_DIR."/".$module."/pages/".$route->action.".php"))
                $displayed = require MODULES_DIR."/".$module."/pages/".$route->action.".php";

        if (!$displayed)
            foreach ($config->enabled_feathers as $feather)
                if (file_exists(FEATHERS_DIR."/".$feather."/pages/".$route->action.".php"))
                    $displayed = require FEATHERS_DIR."/".$feather."/pages/".$route->action.".php";

        if (!$displayed and $theme->file_exists("pages/".$route->action))
            $main->display("pages/".$route->action);
        elseif (!$displayed)
            show_404();
    }

    $trigger->call("end", $route);

    ob_end_flush();

