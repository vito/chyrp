<?php
    /**
     * Class: Route
     * Holds information for URLs, redirecting, etc.
     */
    class Route {
        # String: $action
        # The current action.
        public $action = "";

        # Array: $try
        # An array of (string) actions to try until one doesn't return false.
        public $try = array();

        # Boolean: $ajax
        # Shortcut to the AJAX constant (useful for Twig).
        public $ajax = AJAX;

        # Boolean: $success
        # Did <Route.init> call a successful route?
        public $success = false;

        # Variable: $controller
        # The Route's Controller.
        public $controller;

        /**
         * Function: __construct
         * Parse the URL to determine what to do.
         */
        private function __construct($controller) {
            $this->controller = $controller;

            $config = Config::current();

            $this->action =& $_GET['action'];

            if (isset($_GET['feed']))
                $this->feed = true;

            # Parse the current URL and extract information.
            $parse = parse_url($config->url);
            fallback($parse["path"], "/");

            if (isset($controller->base))
                $parse["path"] = trim($parse["path"], "/")."/".trim($controller->base, "/")."/";

            $this->safe_path = str_replace("/", "\\/", $parse["path"]);
            $this->request = $parse["path"] == "/" ?
                                 $_SERVER['REQUEST_URI'] :
                                 preg_replace("/{$this->safe_path}?/", "", $_SERVER['REQUEST_URI'], 1) ;
            $this->arg = array_map("urldecode", explode("/", trim($this->request, "/")));

            if (method_exists($controller, "parse"))
                $controller->parse($this);

            $this->try[] = isset($this->action) ?
                               fallback($this->action, "index", true) :
                               (!substr_count($this->arg[0], "?") ?
                                   fallback($this->arg[0], "index", true) :
                                   "index") ;
        }

        /**
         * Function: init
         * Begin running Controller actions until one of them doesn't return false.
         *
         * This will also call the route_xxxxx Triggers.
         *
         * Parameters:
         *     $controller - The Controller to run methods on.
         */
        public function init() {
            $trigger = Trigger::current();

            $trigger->call("route_init", $this);

            $try = $this->try;

            if (isset($this->action))
                array_unshift($try, $this->action);

            $count = 0;
            foreach ($try as $key => $val) {
                if (is_numeric($key))
                    list($method, $args) = array($val, array());
                else
                    list($method, $args) = array($key, $val);

                $this->action = $method;

                $name = strtolower(str_replace("Controller", "", get_class($this->controller)));
                if ($trigger->exists($name."_".$method) or $trigger->exists("route_".$method))
                    $call = $trigger->call(array($name."_".$method, "route_".$method), $this->controller);
                else
                    $call = false;

                if (method_exists($this->controller, $method))
                    $response = call_user_func_array(array($this->controller, $method), $args);
                else
                    $response = false;

                if ($response !== false or $call !== false)
                    return $this->success = true;

                if (++$count == count($try) and isset($this->controller->fallback) and method_exists($this->controller, "display"))
                    call_user_func_array(array($this->controller, "display"), $this->controller->fallback);
            }
        }

        /**
         * Function: url
         * Attempts to change the specified clean URL to a dirty URL if clean URLs is disabled.
         * Use this for linking to things. The applicable URL conversions are passed through the
         * parse_urls trigger.
         *
         * Parameters:
         *     $url - The clean URL.
         *
         * Returns:
         *     Clean URL - if $config->clean_urls is set to *true*.
         *     Dirty URL - if $config->clean_urls is set to *false*.
         */
        public function url($url, $use_chyrp_url = false) {
            $config = Config::current();

            if ($url[0] == "/")
                return (ADMIN or $use_chyrp_url) ?
                           $config->chyrp_url.$url :
                           $config->url.$url ;
            else
                $url = substr($url, -1) == "/" ? $url : $url."/" ;

            if ($config->clean_urls) { # If their post URL doesn't have a trailing slash, remove it from these as well.
                if (substr($url, 0, 5) == "page/") # Different URL for viewing a page
                    $url = substr($url, 5);

                return (substr($config->post_url, -1) == "/" or $url == "search/") ?
                           $config->url."/".$url :
                           $config->url."/".rtrim($url, "/") ;
            }

            $urls = fallback($this->controller->urls, array());
            Trigger::current()->filter($urls, "parse_urls");

            foreach (array_diff_assoc($urls, $this->controller->urls) as $key => $value)
                $urls[substr($key, 0, -1)."feed\//"] = "/".$value."&amp;feed";

            $urls["/\/(.*?)\/$/"] = "/?action=$1";

            return $config->url.preg_replace(
                   array_keys($urls),
                   array_values($urls),
                   "/".$url, 1);
        }

        /**
         * Function: add
         * Adds a route to Chyrp. Only needed for actions that have more than one parameter.
         * For example, for /tags/ you won't need to do this, but you will for /tag/tag-name/.
         *
         * Parameters:
         *     $path - The path to add. Wrap variables with parentheses, e.g. "tag/(name)/".
         *     $action - The action the path points to.
         *
         * See Also:
         *     <remove>
         */
        public function add($path, $action) {
            $config = Config::current();

            $new_routes = $config->routes;
            $new_routes[$path] = $action;

            $config->set("routes", $new_routes);
        }

        /**
         * Function: remove
         * Removes a route added by <add>.
         *
         * Parameters:
         *     $path - The path to remove. Same as <add>.
         *
         * See Also:
         *     <add>
         */
        public function remove($path) {
            $config = Config::current();

            unset($config->routes[$path]);

            $config->set("routes", $config->routes);
        }

        /**
         * Function: current
         * Returns a singleton reference to the current class.
         */
        public static function & current($controller = null) {
            static $instance = null;

            if (!isset($controller) and empty($instance))
                error(__("Error"), __("Route was initiated without a Controller."), debug_backtrace());

            return $instance = (empty($instance)) ? new self($controller) : $instance ;
        }
    }
