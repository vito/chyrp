<?php
    class BBSController {
        # Array: $urls
        # An array of clean URL => dirty URL translations.
        public $urls = array('/\/id\/([0-9]+)\//'             => '/?action=view&amp;id=$1',
                             '/\/page\/(([^\/]+)\/)+/'        => '/?action=page&amp;url=$2',
                             '/\/search\//'                   => '/?action=search',
                             '/\/search\/([^\/]+)\//'         => '/?action=search&amp;query=$1',
                             '/\/archive\/([0-9]{4})\/([0-9]{2})\//'
                                                              => '/?action=archive&amp;year=$1&amp;month=$2',
                             '/\/archive\/([0-9]{4})\/([0-9]{2})\/([0-9]{2})\//'
                                                              => '/?action=archive&amp;year=$1&amp;month=$2&amp;day=$3',
                             '/\/([^\/]+)\/feed\/([^\/]+)\//' => '/?action=$1&amp;feed&amp;title=$2',
                             '/\/([^\/]+)\/feed\//'           => '/?action=$1&amp;feed');

        # Boolean: $displayed
        # Has anything been displayed?
        public $displayed = false;

        # Array: $context
        # Context for displaying pages.
        public $context = array();

        # Boolean: $feed
        # Is the visitor requesting a feed?
        public $feed = false;

        public function __construct() {
            $cache = (is_writable(INCLUDES_DIR."/caches") and
                      !DEBUG and
                      !PREVIEWING and
                      !defined('CACHE_TWIG') or CACHE_TWIG);
            $this->twig = new Twig_Loader(THEME_DIR,
                                          $cache ?
                                              INCLUDES_DIR."/caches" :
                                              null) ;
        }

        public function index() {
            $forums = Forum::find();

            $this->display("bbs/index",
                           array("forums" => $forums),
                           __("Index"));
        }

        public function topic() {
            if (!isset($_GET['url']))
                exit; # TODO

            $topic = new Topic(null, array("where" => array("url" => $_GET['url'])));

            if ($topic->no_results)
                exit; # TODO

            $this->display("bbs/topic/view",
                           array("topic" => $topic),
                           $topic->title);
        }

        public function forum() {
            if (!isset($_GET['url']))
                exit; # TODO

            $forum = new Forum(null, array("where" => array("url" => $_GET['url'])));

            if ($forum->no_results)
                exit; # TODO

            $this->display("bbs/forum",
                           array("forum" => $forum),
                           $forum->name);
        }

        public function parse($route) {
            $config = Config::current();

            if ($this->feed)
                $this->post_limit = $config->feed_items;
            else
                $this->post_limit = $config->posts_per_page;

            array_shift($route->arg);

            if (empty($route->arg[0]) and !isset($config->routes["/"])) # If they're just at /, don't bother with all this.
                return $route->action = "index";

            # Protect non-responder functions.
            if (in_array($route->arg[0], array("__construct", "parse", "display", "current")))
                show_404();

            # Feed
            if (preg_match("/\/feed\/?$/", $route->request)) {
                $this->feed = true;
                $this->post_limit = $config->feed_items;

                if ($route->arg[0] == "feed") # Don't set $route->action to "feed" (bottom of this function).
                    return $route->action = "index";
            }

            # Feed with a title parameter
            if (preg_match("/\/feed\/([^\/]+)\/?$/", $route->request, $title)) {
                $this->feed = true;
                $this->post_limit = $config->feed_items;
                $_GET['title'] = $title[1];

                if ($route->arg[0] == "feed") # Don't set $route->action to "feed" (bottom of this function).
                    return $route->action = "index";
            }

            # Paginator
            if (preg_match_all("/\/((([^_\/]+)_)?page)\/([0-9]+)/", $route->request, $page_matches)) {
                foreach ($page_matches[1] as $key => $page_var)
                    $_GET[$page_var] = (int) $page_matches[4][$key];

                if ($route->arg[0] == $page_matches[1][0]) # Don't fool ourselves into thinking we're viewing a page.
                    return $route->action = (isset($config->routes["/"])) ? $config->routes["/"] : "index" ;
            }

            # Searching
            if ($route->arg[0] == "search") {
                if (isset($route->arg[1]))
                    $_GET['query'] = $route->arg[1];

                return $route->action = "search";
            }

            # Custom pages added by Modules, Feathers, Themes, etc.
            foreach ($config->routes as $path => $action) {
                if (is_numeric($action))
                    $action = $route->arg[0];

                preg_match_all("/\(([^\)]+)\)/", $path, $matches);

                if ($path != "/")
                    $path = trim($path, "/");

                $escape = preg_quote($path, "/");
                $to_regexp = preg_replace("/\\\\\(([^\)]+)\\\\\)/", "([^\/]+)", $escape);

                if ($path == "/")
                    $to_regexp = "\$";

                if (preg_match("/^\/{$to_regexp}/", $route->request, $url_matches)) {
                    array_shift($url_matches);

                    if (isset($matches[1]))
                        foreach ($matches[1] as $index => $parameter)
                            $_GET[$parameter] = urldecode($url_matches[$index]);

                    $params = explode(";", $action);
                    $action = $params[0];

                    array_shift($params);
                    foreach ($params as $param) {
                        $split = explode("=", $param);
                        $_GET[$split[0]] = fallback($split[1], "", true);
                    }

                    $route->try[] = $action;
                }
            }
        }

        /**
         * Function: resort
         * Queue a failpage in the event that none of the routes are successful.
         */
        public function resort($file, $context, $title = null) {
            $this->fallback = array($file, $context, $title);
            return false;
        }

        /**
         * Function: display
         * Display the page.
         *
         * If "posts" is in the context and the visitor requested a feed, they will be served.
         *
         * Parameters:
         *     $file - The theme file to display.
         *     $context - The context for the file.
         *     $title - The title for the page.
         */
        public function display($file, $context = array(), $title = "") {
            if (is_array($file))
                for ($i = 0; $i < count($file); $i++) {
                    $check = ($file[$i][0] == "/" or preg_match("/[a-zA-Z]:\\\/", $file[$i])) ?
                                 $file[$i] :
                                 THEME_DIR."/".$file[$i] ;

                    if (file_exists($check.".twig") or ($i + 1) == count($file))
                        return $this->display($file[$i], $context, $title);
                }

            $this->displayed = true;

            $route = Route::current();
            $trigger = Trigger::current();

            # Serve feeds.
            if ($this->feed) {
                if ($trigger->exists($route->action."_feed"))
                    return $trigger->call($route->action."_feed", $context);

                if (isset($context["posts"]))
                    return $this->feed($context["posts"]);
            }

            $this->context = array_merge($context, $this->context);

            $visitor = Visitor::current();
            $config = Config::current();

            $this->context["theme"]        = Theme::current();
            $this->context["flash"]        = Flash::current();
            $this->context["trigger"]      = $trigger;
            $this->context["modules"]      = Modules::$instances;
            $this->context["feathers"]     = Feathers::$instances;
            $this->context["title"]        = $title;
            $this->context["site"]         = $config;
            $this->context["visitor"]      = $visitor;
            $this->context["route"]        = Route::current();
            $this->context["hide_admin"]   = isset($_COOKIE["hide_admin"]);
            $this->context["version"]      = CHYRP_VERSION;
            $this->context["now"]          = time();
            $this->context["debug"]        = DEBUG;
            $this->context["POST"]         = $_POST;
            $this->context["GET"]          = $_GET;
            $this->context["sql_queries"] =& SQL::current()->queries;

            $this->context["visitor"]->logged_in = logged_in();

            $this->context["enabled_modules"] = array();
            foreach ($config->enabled_modules as $module)
                $this->context["enabled_modules"][$module] = true;

            $context["enabled_feathers"] = array();
            foreach ($config->enabled_feathers as $feather)
                $this->context["enabled_feathers"][$feather] = true;

            $this->context["sql_debug"] =& SQL::current()->debug;

            $trigger->filter($this->context, array("bbs_context", "bbs_context_".str_replace("/", "_", $file)));

            $file = ($file[0] == "/" or preg_match("/[a-zA-Z]:\\\/", $file)) ? $file : THEME_DIR."/".$file ;
            if (!file_exists($file.".twig"))
                error(__("Template Missing"), _f("Couldn't load template: <code>%s</code>", array($file.".twig")));

            try {
                return $this->twig->getTemplate($file.".twig")->display($this->context);
            } catch (Exception $e) {
                $prettify = preg_replace("/([^:]+): (.+)/", "\\1: <code>\\2</code>", $e->getMessage());
                error(__("Error"), $prettify, debug_backtrace());
            }
        }

        /**
         * Function: current
         * Returns a singleton reference to the current class.
         */
        public static function & current() {
            static $instance = null;
            return $instance = (empty($instance)) ? new self() : $instance ;
        }
    }

