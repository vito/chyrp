<?php
    # AutoLoading your Classes using Namespaces
    # URL: http://webdevrefinery.com/forums/topic/7073-autoloading-your-classes-using-namespaces/page__view__findpost__p__68241

    namespace Dropbox;

    function load($namespace) {
        $splitpath = explode("\\", $namespace);
        $path = "";
        $name = "";
        $firstword = true;
        for ($i = 0; $i < count($splitpath); $i++) {
            if ($splitpath[$i] && !$firstword) {
                if ($i == count($splitpath) - 1)
                    $name = $splitpath[$i];
                else
                    $path .= DIRECTORY_SEPARATOR . $splitpath[$i];
            }
            if ($splitpath[$i] && $firstword) {
                if ($splitpath[$i] != __NAMESPACE__)
                    break;
                    $firstword = false;
            }
        }
        if (!$firstword) {
            $fullpath = __DIR__ . $path . DIRECTORY_SEPARATOR . $name . ".php";
            return include_once($fullpath);
        }
        return false;
    }

    function loadPath($absPath) {
        return include_once($absPath);
    }

    spl_autoload_register(__NAMESPACE__ . "\load");
