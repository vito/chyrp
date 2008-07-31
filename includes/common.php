<?php
	/**
	 * File: Common
	 *
	 * Chyrp - A Lightweight Blogging Engine
	 *
	 * Version:
	 *     v2.0 RC1
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
	define('CHYRP_VERSION', "2.0 RC1");

	# Constant: DEBUG
	# Should Chyrp use debugging processes?
	define('DEBUG', false);

	# Fallback all these definitions.
	if (!defined('JAVASCRIPT')) define('JAVASCRIPT', false);
	if (!defined('ADMIN'))      define('ADMIN', false);
	if (!defined('AJAX'))       define('AJAX', false);
	if (!defined('XML_RPC'))    define('XML_RPC', false);
	if (!defined('TRACKBACK'))  define('TRACKBACK', false);

	define('UPGRADING', false);

	# Constant: INDEX
	# Is the requested file index.php?
	define('INDEX', (pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME) == "index.php") and !ADMIN);

	# Use GZip compression if available.
	if (!AJAX and extension_loaded("zlib") and
	    !ini_get("zlib.output_compression") and
	    isset($_SERVER['HTTP_ACCEPT_ENCODING']) and
	    substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], "gzip")) {
		ob_start("ob_gzhandler");
		header("Content-Encoding: gzip");
	} else
		ob_start();

	if (JAVASCRIPT) {
		error_reporting(0);
		header("Content-Type: application/x-javascript");
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Mon, 03 Jun 1991 05:30:00 GMT");
	} else
		error_reporting(E_ALL | E_STRICT); # Make sure E_STRICT is on so Chyrp remains errorless.

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

	# File: Helpers
	# Various functions used throughout Chyrp's code.
	require_once INCLUDES_DIR."/helpers.php";

	# Not installed?
	if (!file_exists(INCLUDES_DIR."/config.yaml.php"))
		redirect("install.php");

	require_once INCLUDES_DIR."/class/Query.php"; # SQL query handler
	require_once INCLUDES_DIR."/class/QueryBuilder.php"; # SQL query builder
	require_once INCLUDES_DIR."/lib/Yaml.php"; # YAML parser

	require_once INCLUDES_DIR."/class/Config.php"; # Configuration
	require_once INCLUDES_DIR."/class/SQL.php"; # Database/SQL jazz

	# Translation stuff
	require_once INCLUDES_DIR."/lib/gettext/gettext.php";
	require_once INCLUDES_DIR."/lib/gettext/streams.php";

	if (function_exists("date_default_timezone_set"))
		date_default_timezone_set($config->timezone);
	else
		ini_set("date.timezone", $config->timezone);

	header("X-Pingback: ".$config->chyrp_url."/includes/xmlrpc.php");

	$sql->connect();

	sanitize_input($_GET);
	sanitize_input($_POST);
	sanitize_input($_COOKIE);
	sanitize_input($_REQUEST);

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

	if (!JAVASCRIPT)
		session();

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

	timer_start();

	$flash = Flash::current();

	set_locale($config->locale);

	# Require feathers/modules and load their translations.
	foreach ($config->enabled_feathers as $index => $feather) {
		if (!file_exists(FEATHERS_DIR."/".$feather."/".$feather.".php")) {
			unset($config->enabled_feathers[$index]);
			continue;
		}

		if (file_exists(FEATHERS_DIR."/".$feather."/locale/".$config->locale.".mo"))
			load_translator($feather, FEATHERS_DIR."/".$feather."/locale/".$config->locale.".mo");

		require FEATHERS_DIR."/".$feather."/".$feather.".php";

		$info = Horde_Yaml::loadFile(FEATHERS_DIR."/".$feather."/info.yaml");
		$pluralizations[$feather] = $pluralizations["feathers"][$feather] = fallback($info["plural"], pluralize($feather), true);
	}

	foreach ($config->enabled_modules as $index => $module) {
		if (!file_exists(MODULES_DIR."/".$module."/".$module.".php")) {
			unset($config->enabled_modules[$index]);
			continue;
		}

		if (file_exists(MODULES_DIR."/".$module."/locale/".$config->locale.".mo"))
			load_translator($module, MODULES_DIR."/".$module."/locale/".$config->locale.".mo");

		require MODULES_DIR."/".$module."/".$module.".php";
	}

	# Load the /clean/urls into their correct $_GET values.
	if (INDEX or ADMIN)
		$route->determine_action();

	# Variable: $visitor
	# Holds the current user and their group.
	$visitor = Visitor::current();

	$config->theme = ($visitor->group()->can("change_settings") and
	                      !empty($_GET['action']) and
	                      $_GET['action'] == "theme_preview" and
	                      !empty($_GET['theme'])) ?
	                 $_GET['theme'] :
	                 $config->theme;

	# Constant: THEME_DIR
	# Absolute path to /themes/(current theme)
	define('THEME_DIR', MAIN_DIR."/themes/".$config->theme);

	# Constant: THEME_URL
	# URL to /themes/(current theme)
	define('THEME_URL', $config->chyrp_url."/themes/".$config->theme);

	$theme = Theme::current();

	foreach (Horde_Yaml::loadFile(THEME_DIR."/info.yaml") as $key => $val)
		$theme->$key = $val;

	if (INDEX)
		header("Content-type: ".fallback($theme->type, "text/html")."; charset=UTF-8");
	elseif (AJAX)
		header("Content-type: text/html; charset=UTF-8");

	# These are down here so that the modules are
	# initialized after the $_GET values are filled.
	/**
	 * Array: $feathers
	 * Contains all of the enabled Feather's Classes.
	 */
	$feathers = array();
	foreach ($config->enabled_feathers as $feather) {
		$camelized = camelize($feather);
		if (!class_exists($camelized)) continue;

		$feathers[$feather] = new $camelized;
		$feathers[$feather]->safename = $feather;

		if (!ADMIN) continue;

		foreach (Horde_Yaml::loadFile(FEATHERS_DIR."/".$feather."/info.yaml") as $key => $val)
			$feathers[$feather]->$key = (is_string($val)) ? __($val, $feather) : $val ;
	}

	/**
	 * Array: $modules
	 * Contains all of the enabled Module's Classes.
	 */
	$modules = array();
	foreach ($config->enabled_modules as $module) {
		$camelized = camelize($module);
		if (!class_exists($camelized)) continue;

		$modules[$module] = new $camelized;
		$modules[$module]->safename = $module;

		if (!ADMIN) continue;

		foreach (Horde_Yaml::loadFile(MODULES_DIR."/".$module."/info.yaml") as $key => $val)
			$modules[$module]->$key = (is_string($val)) ? __($val, $module) : $val ;
	}

	# Now that they're all instantiated, call __init().
	foreach ($feathers as $feather)
		if (method_exists($feather, "__init"))
			$feather->__init();
	foreach ($modules as $module)
		if (method_exists($module, "__init"))
			$module->__init();

	if (INDEX) {
		$route->check_custom_routes();

		# If the post viewing URL is the same as the page viewing URL, check for viewing a page first.
		if (preg_match("/^\((clean|url)\)\/?$/", $config->post_url)) {
			$route->check_viewing_page();
			$route->check_viewing_post(true);
		} else {
			$route->check_viewing_post();
			$route->check_viewing_page(true);
		}
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

	$draft_situations = array($route->action == "view", $route->action == "drafts", ADMIN, AJAX);
	$trigger->filter($draft_situations, "draft_situations");
	if (in_array(true, $draft_situations) and $visitor->group()->can("view_draft"))
		$statuses[] = "draft";

	Post::$private = "status IN ('".implode("', '", $statuses)."')";
	Post::$enabled_feathers = "feather IN ('".implode("', '", $config->enabled_feathers)."')";

	# Load the translation engine
	load_translator("chyrp", INCLUDES_DIR."/locale/".$config->locale.".mo");

	# Load the theme translator
	if (file_exists(THEME_DIR."/locale/".$config->locale.".mo"))
		load_translator("theme", THEME_DIR."/locale/".$config->locale.".mo");

	if (INDEX or ADMIN or AJAX) {
		if (!$visitor->group()->can("view_site") and !in_array($route->action, array("login", "logout", "register", "lost_password")))
			if ($trigger->exists("can_not_view_site"))
				$trigger->call("can_not_view_site");
			else
				show_403(__("Access Denied"), __("You are not allowed to view this site."));

		if (isset($_GET['feed']))
			$config->posts_per_page = $config->feed_items;

		if (!ADMIN and method_exists($main, $route->action))
			call_user_func(array($main, $route->action));

		# Call any plugin route functions
		if (INDEX)
			$trigger->call("route_".$route->action);

		if (isset($_GET['feed']))
			if ($trigger->exists($route->action."_feed")) # What about custom feeds?
				$trigger->call($route->action."_feed");
			elseif (isset($posts)) # Are there already posts to show?
				$route->action = "feed";
			else
				redirect(fallback($config->feed_url, url("feed/"), true)); # Really? Nothing? Too bad. MAIN FEED 4 U.
	}
