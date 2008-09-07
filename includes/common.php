<?php
	/**
	 * File: Common
	 *
	 * Chyrp - A Lightweight Blogging Engine
	 *
	 * Version:
	 *     v2.0
	 *
	 * License:
	 *     GPL-3
	 *
	 * Chyrp Copyright:
	 *     Copyright (c) 2008 Alex Suraci, <http://toogeneric.com/>
	 */

	if (version_compare(PHP_VERSION, "5.1.3", "<"))
		exit("Chyrp requires PHP 5.1.3 or greater. Installation cannot continue.");

	# Constant: CHYRP_VERSION
	# Chyrp's version number.
	define('CHYRP_VERSION', "2.0");

	# Constant: DEBUG
	# Should Chyrp use debugging processes?
	define('DEBUG', true);

	# Constant: ADMIN
	# Is this the JavaScript file?
	if (!defined('JAVASCRIPT')) define('JAVASCRIPT', false);

	# Constant: ADMIN
	# Is the user in the admin area?
	if (!defined('ADMIN')) define('ADMIN', false);

	# Constant: AJAX
	# Is this being run from an AJAX request?
	if (!defined('AJAX')) define('AJAX', false);

	# Constant: XML_RPC
	# Is this being run from XML-RPC?
	if (!defined('XML_RPC')) define('XML_RPC', false);

	# Constant: TRACKBACK
	# Is this being run from a trackback request?
	if (!defined('TRACKBACK')) define('TRACKBACK', false);

	# Constant: UPGRADING
	# Is the user running the upgrader? (false)
	define('UPGRADING', false);

	# Constant: INSTALLING
	# Is the user running the installer? (false)
	define('INSTALLING', false);

	# Constant: TESTER
	# Is the site being run by the automated tester?
	define('TESTER', isset($_SERVER['HTTP_USER_AGENT']) and $_SERVER['HTTP_USER_AGENT'] == "tester.rb");

	# Constant: INDEX
	# Is the requested file /index.php?
	define('INDEX', (pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME) == "index.php") and !ADMIN);

	# Constant: MAIN_DIR
	# Absolute path to the Chyrp root
	define('MAIN_DIR', dirname(dirname(__FILE__)));

	# Constant: INCLUDES_DIR
	# Absolute path to /includes
	define('INCLUDES_DIR', MAIN_DIR."/includes");

	# Constant: MODULES_DIR
	# Absolute path to /modules
	define('MODULES_DIR', MAIN_DIR."/modules");

	# Constant: FEATHERS_DIR
	# Absolute path to /feathers
	define('FEATHERS_DIR', MAIN_DIR."/feathers");

	# Constant: THEMES_DIR
	# Absolute path to /themes
	define('THEMES_DIR', MAIN_DIR."/themes");

	# Not installed?
	if (!file_exists(INCLUDES_DIR."/config.yaml.php"))
		redirect("install.php");

	# Set error reporting levels, and headers for Chyrp's JS files.
	if (JAVASCRIPT) {
		error_reporting(0);
		header("Content-Type: application/x-javascript");
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Mon, 03 Jun 1991 05:30:00 GMT");
	} else
		error_reporting(E_ALL | E_STRICT); # Make sure E_STRICT is on so Chyrp remains errorless.

	# Use GZip compression if available.
	if (!AJAX and extension_loaded("zlib") and
	    !ini_get("zlib.output_compression") and
	    isset($_SERVER['HTTP_ACCEPT_ENCODING']) and
	    substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], "gzip")) {
		ob_start("ob_gzhandler");
		header("Content-Encoding: gzip");
	} else
		ob_start();

	# File: Helpers
	# Various functions used throughout Chyrp's code.
	require_once INCLUDES_DIR."/helpers.php";

	# File: Gettext
	# Gettext library.
	require_once INCLUDES_DIR."/lib/gettext/gettext.php";

	# File: Streams
	# Streams library.
	require_once INCLUDES_DIR."/lib/gettext/streams.php";

	# File: YAML
	# Horde YAML parsing library.
	require_once INCLUDES_DIR."/lib/YAML.php";

	# File: Config
	# See Also:
	#     <Config>
	require_once INCLUDES_DIR."/class/Config.php";

	# File: SQL
	# See Also:
	#     <SQL>
	require_once INCLUDES_DIR."/class/SQL.php";

	set_timezone($config->timezone);

	$sql->connect();

	sanitize_input($_GET);
	sanitize_input($_POST);
	sanitize_input($_COOKIE);
	sanitize_input($_REQUEST);

	# Set the error handler to exit on error.
	if (TESTER)
		set_error_handler("error_panicker");

	# File: Model
	# See Also:
	#     <Model>
	require_once INCLUDES_DIR."/class/Model.php";

	# File: User
	# See Also:
	#     <User>
	require_once INCLUDES_DIR."/model/User.php";

	# File: Visitor
	# See Also:
	#     <Visitor>
	require_once INCLUDES_DIR."/model/Visitor.php";

	# File: Post
	# See Also:
	#     <Post>
	require_once INCLUDES_DIR."/model/Post.php";

	# File: Page
	# See Also:
	#     <Page>
	require_once INCLUDES_DIR."/model/Page.php";

	# File: Group
	# See Also:
	#     <Group>
	require_once INCLUDES_DIR."/model/Group.php";

	# File: Session
	# See Also:
	#     <Session>
	require_once INCLUDES_DIR."/class/Session.php";

	# File: Flash
	# See Also:
	#     <Flash>
	require_once INCLUDES_DIR."/class/Flash.php";

	# File: Theme
	# See Also:
	#     <Theme>
	require_once INCLUDES_DIR."/class/Theme.php";

	# File: Trigger
	# See Also:
	#     <Trigger>
	require_once INCLUDES_DIR."/class/Trigger.php";

	# File: Module
	# See Also:
	#     <Module>
	require_once INCLUDES_DIR."/class/Modules.php";

	# File: Feathers
	# See Also:
	#     <Feathers>
	require_once INCLUDES_DIR."/class/Feathers.php";

	# File: Paginator
	# See Also:
	#     <Paginator>
	require_once INCLUDES_DIR."/class/Paginator.php";

	# File: Twig
	# Chyrp's templating engine.
	require_once INCLUDES_DIR."/class/Twig.php";

	# File: Route
	# See Also:
	#     <Route>
	require_once INCLUDES_DIR."/class/Route.php";

	# File: Main
	# See Also:
	#     <Main Controller>
	require_once INCLUDES_DIR."/controller/Main.php";

	# File: Admin
	# See Also:
	#     <Admin Controller>
	require_once INCLUDES_DIR."/controller/Admin.php";

	# File: Feather
	# See Also:
	#     <Feather>
	require_once INCLUDES_DIR."/interface/Feather.php";

	# Start the session.
	session();

	# Begin the timer.
	timer_start();

	# Set the locale to their config.
	set_locale($config->locale);

	# Prepare the notifier.
	$flash = Flash::current();

	/**
	 * Array: $feathers
	 * Contains all of the enabled Feather's Classes.
	 */
	$feathers = array();

	/**
	 * Array: $modules
	 * Contains all of the enabled Module's Classes.
	 */
	$modules = array();

	# Load the $feathers array.
	foreach ($config->enabled_feathers as $index => $feather) {
		if (!file_exists(FEATHERS_DIR."/".$feather."/".$feather.".php")) {
			unset($config->enabled_feathers[$index]);
			continue;
		}

		if (file_exists(FEATHERS_DIR."/".$feather."/locale/".$config->locale.".mo"))
			load_translator($feather, FEATHERS_DIR."/".$feather."/locale/".$config->locale.".mo");

		require FEATHERS_DIR."/".$feather."/".$feather.".php";

		$camelized = camelize($feather);
		if (!class_exists($camelized))
			continue;

		$feathers[$feather] = new $camelized;
		$feathers[$feather]->safename = $feather;

		if (!ADMIN and $route->action != "feed")
			continue;

		foreach (YAML::load(FEATHERS_DIR."/".$feather."/info.yaml") as $key => $val)
			$feathers[$feather]->$key = (is_string($val)) ? __($val, $feather) : $val ;
	}

	# Load the $modules array.
	foreach ($config->enabled_modules as $index => $module) {
		if (!file_exists(MODULES_DIR."/".$module."/".$module.".php")) {
			unset($config->enabled_modules[$index]);
			continue;
		}

		if (file_exists(MODULES_DIR."/".$module."/locale/".$config->locale.".mo"))
			load_translator($module, MODULES_DIR."/".$module."/locale/".$config->locale.".mo");

		require MODULES_DIR."/".$module."/".$module.".php";

		$camelized = camelize($module);
		if (!class_exists($camelized))
			continue;

		$modules[$module] = new $camelized;
		$modules[$module]->safename = $module;

		if (!ADMIN)
			continue;

		foreach (YAML::load(MODULES_DIR."/".$module."/info.yaml") as $key => $val)
			$modules[$module]->$key = (is_string($val)) ? __($val, $module) : $val ;
	}

	# Initialize all modules.
	foreach ($feathers as $feather)
		if (method_exists($feather, "__init"))
			$feather->__init();

	foreach ($modules as $module)
		if (method_exists($module, "__init"))
			$module->__init();

	# Parse the clean URL into $_GET actions.
	$route->determine_action();

	# Load the Visitor.
	$visitor = Visitor::current();

	# Constant: PREVIEWING
	# Is the user previewing a theme?
	define('PREVIEWING', ($visitor->group()->can("change_settings") and
	                         !empty($_GET['action']) and
	                         $_GET['action'] == "theme_preview" and
	                         !empty($_GET['theme'])));

	# Constant: THEME_DIR
	# Absolute path to /themes/(current/previewed theme)
	define('THEME_DIR', MAIN_DIR."/themes/".(PREVIEWING ? $_GET['theme'] : $config->theme));

	# Constant: THEME_URL
	# URL to /themes/(current/previewed theme)
	define('THEME_URL', $config->chyrp_url."/themes/".(PREVIEWING ? $_GET['theme'] : $config->theme));

	# Initialize the theme.
	$theme = Theme::current();

	# Load the theme's info into the Theme class.
	foreach (YAML::load(THEME_DIR."/info.yaml") as $key => $val)
		$theme->$key = $val;

	# Set the content-type to the theme's "type" setting, or "text/html".
	header("Content-type: ".(INDEX ? fallback($theme->type, "text/html") : "text/html")."; charset=UTF-8");

	# Check if we are viewing a custom route, and set the action/GET parameters accordingly.
	$route->check_custom_routes();

	# If the post viewing URL is the same as the page viewing URL, check for viewing a page first.
	if (preg_match("/^\((clean|url)\)\/?$/", $config->post_url)) {
		$route->check_viewing_page();
		$route->check_viewing_post();
	} else {
		$route->check_viewing_post();
		$route->check_viewing_page();
	}

	if (INDEX or ADMIN)
		$trigger->call("runtime");

	# Array: $statuses
	# An array of post statuses that <Visitor> can view.
	$statuses = array("public");

	if (logged_in())
		$statuses[] = "registered_only";

	if ($visitor->group()->can("view_private"))
		$statuses[] = "private";

	Post::$private = "(status IN ('".implode("', '", $statuses)."') OR".
	                 " status LIKE '%{".$visitor->group()->id."}%')";
	Post::$enabled_feathers = "feather IN ('".implode("', '", $config->enabled_feathers)."')";

	# Load the translation engine
	load_translator("chyrp", INCLUDES_DIR."/locale/".$config->locale.".mo");

	# Check if the user can view the site, call the appropriate controller functions, and serve any feeds.
	if (INDEX or ADMIN or AJAX) {
		if (!$visitor->group()->can("view_site") and
		    !in_array($route->action, array("login", "logout", "register", "lost_password")))
			if ($trigger->exists("can_not_view_site"))
				$trigger->call("can_not_view_site");
			else
				show_403(__("Access Denied"), __("You are not allowed to view this site."));

		# If we're viewing a feed, make sure the feed items displayed is correct.
		if (isset($_GET['feed']))
			$config->posts_per_page = $config->feed_items;

		# Call the controller function for the current action, and any extension routes.
		if (INDEX) {
			if (method_exists($main, $route->action))
				call_user_func(array($main, $route->action));

			$trigger->call("route_".$route->action, $theme);
		}

		# Serve the feed.
		if (isset($_GET['feed']))
			if ($trigger->exists($route->action."_feed")) # Custom feeds?
				$trigger->call($route->action."_feed");
			elseif (isset($posts)) # Are there already posts to show?
				$route->action = "feed";
			else
				redirect(fallback($config->feed_url, url("feed/"), true)); # Really? Nothing? Too bad. MAIN FEED 4 U.
	}
