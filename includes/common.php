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

	# Constant: CHYRP_VERSION
	# Chyrp's version number.
	define('CHYRP_VERSION', "2.0");

	# Constant: DEBUG
	# Should Chyrp use debugging processes?
	define('DEBUG', true);

	# Make sure E_STRICT is on so Chyrp remains errorless.
	error_reporting(E_ALL | E_STRICT);

	# Fallback all these definitions.
	if (!defined('JAVASCRIPT'))  define('JAVASCRIPT', false);
	if (!defined('BOOKMARKLET')) define('BOOKMARKLET', false);
	if (!defined('ADMIN'))       define('ADMIN', false);
	if (!defined('AJAX'))        define('AJAX', false);
	if (!defined('XML_RPC'))     define('XML_RPC', false);
	if (!defined('TRACKBACK'))   define('TRACKBACK', false);

	# Use GZip compression if available.
	if (extension_loaded("zlib")) {
		ob_start("ob_gzhandler");
		header("Content-Encoding: gzip");
	} else
		ob_start();

	if (!JAVASCRIPT and !XML_RPC)
		header("Content-type: text/html; charset=UTF-8");

	# Constant: MAIN_DIR
	# Absolute path to the Chyrp root
	define('MAIN_DIR', pathinfo(dirname(__FILE__), PATHINFO_DIRNAME));

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
	if (!file_exists(INCLUDES_DIR."/config.yaml.php") or !file_exists(INCLUDES_DIR."/database.yaml.php"))
		redirect("install.php");

	require_once INCLUDES_DIR."/class/QueryBuilder.php"; # SQL query builder
	require_once INCLUDES_DIR."/lib/spyc.php"; # YAML parser

	require_once INCLUDES_DIR."/config.php"; # Configuration
	require_once INCLUDES_DIR."/database.php"; # Database/SQL jazz

	# Translation stuff
	require_once INCLUDES_DIR."/lib/gettext/gettext.php";
	require_once INCLUDES_DIR."/lib/gettext/streams.php";

	# Load the configuration settings
	$config->load(INCLUDES_DIR."/config.yaml.php");

	# Constant: THEME_DIR
	# Absolute path to /themes/(current theme)
	define('THEME_DIR', MAIN_DIR."/themes/".$config->theme);

	header("X-Pingback: ".$config->chyrp_url."/includes/xmlrpc.php");

	if (!ADMIN and !JAVASCRIPT and !XML_RPC and !TRACKBACK and strpos($_SERVER['REQUEST_URI'], "?"))
		$config->clean_urls = false;

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

	if (!JAVASCRIPT) {
		session_set_save_handler(array("Session", "open"),
		                         array("Session", "close"),
		                         array("Session", "read"),
		                         array("Session", "write"),
		                         array("Session", "destroy"),
		                         array("Session", "gc"));
		session_set_cookie_params(60 * 60 * 24 * 30);
		session_name(sanitize(camelize($config->name)."ChyrpSession", false, true));
		session_start();
	}

	# File: Trigger
	# See Also:
	#     <Trigger>
	require_once INCLUDES_DIR."/class/Trigger.php";

	# File: Module
	# See Also:
	#     <Module>
	require_once INCLUDES_DIR."/class/Module.php";

	# File: Feather
	# See Also:
	#     <Feather>
	require_once INCLUDES_DIR."/class/Feather.php";

	# File: Paginator
	# See Also:
	#     <Paginator>
	require_once INCLUDES_DIR."/class/Paginator.php";

	# File: Twig
	# Chyrp's templating engine.
	require_once INCLUDES_DIR."/class/Twig.php";

	# File: Theme
	# See Also:
	#     <Theme>
	require_once INCLUDES_DIR."/class/Theme.php";

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

	timer_start();

	set_locale($config->locale);

	foreach ($config->enabled_feathers as $feather) {
		if (file_exists(FEATHERS_DIR."/".$feather."/locale/".$config->locale.".mo"))
			load_translator($feather, FEATHERS_DIR."/".$feather."/locale/".$config->locale.".mo");

		require FEATHERS_DIR."/".$feather."/feather.php";

		$info = Spyc::YAMLLoad(FEATHERS_DIR."/".$feather."/info.yaml");
		$pluralizations[$feather] = $pluralizations["feathers"][$feather] = fallback($info["plural"], pluralize($feather), true);
	}

	foreach ($config->enabled_modules as $module) {
		if (file_exists(MODULES_DIR."/".$module."/locale/".$config->locale.".mo"))
			load_translator($module, MODULES_DIR."/".$module."/locale/".$config->locale.".mo");

		require MODULES_DIR."/".$module."/module.php";
	}

	# These are down here so that the modules are
	# initialized after the $_GET values are filled.
	/**
	 * Array: $feathers
	 * Contains all of the enabled Feather's Classes.
	 */
	$feathers = array();
	foreach ($config->enabled_feathers as $feather) {
		$camelized = camelize($feather);
		$feathers[$feather] = new $camelized;
		$feathers[$feather]->safename = $feather;
		foreach (Spyc::YAMLLoad(FEATHERS_DIR."/".$feather."/info.yaml") as $key => $val)
			$feathers[$feather]->$key = $val;
	}

	/**
	 * Array: $modules
	 * Contains all of the enabled Module's Classes.
	 */
	$modules = array();
	foreach ($config->enabled_modules as $module) {
		$camelized = camelize($module);
		$modules[$module] = new $camelized();
		$modules[$module]->safename = $module;
		foreach (Spyc::YAMLLoad(MODULES_DIR."/".$module."/info.yaml") as $key => $val)
			$modules[$module]->$key = $val;
	}

	# Load the /clean/urls into their correct $_GET values.
	$route->determine_action();

	$action = (isset($_GET['action'])) ? strip_tags($_GET['action']) : "index" ;

	# Load the translation engine
	load_translator("chyrp", INCLUDES_DIR."/locale/".$config->locale.".mo");

	# Load the theme translator
	if (file_exists(THEME_DIR."/locale/".$config->locale.".mo"))
		load_translator("theme", THEME_DIR."/locale/".$config->locale.".mo");

	if (!JAVASCRIPT and !XML_RPC) {
		# Variable: $visitor
		# Holds the current user and their group.
		$visitor = Visitor::current();

		if (!$visitor->group()->can("view_site") and !in_array($action, array("process_login", "login", "logout", "process_registration", "register")))
			if ($trigger->exists("can_not_view_site"))
				$trigger->call("can_not_view_site");
			else
				error(__("Access Denied"), __("You are not allowed to view this site."));

		$trigger->call("runtime");

		if (in_array($action, array_values($pluralizations["feathers"])))
			$action = "feather";

		# Array: $statuses
		# An array of post statuses that <Visitor> can view.
		$statuses = array("public");
		if (logged_in())
			$statuses[] = "registered_only";
		if ($visitor->group()->can("view_private"))
			$statuses[] = "private";
		if ($action == "view" and $visitor->group()->can("view_draft"))
			$statuses[] = "draft";

		/**
		 * String: $private
		 * SQL "where" text for which posts the current user can view.
		 */
		$private = "`__posts`.`status` in ('".implode("', '", $statuses)."')";

		/**
		 * String: $enabled_feathers
		 * SQL "where" text for each of the feathers. Prevents posts of a disabled Feather from showing.
		 */
		$enabled_feathers = " and `__posts`.`feather` in ('".implode("', '", $config->enabled_feathers)."')";

		if (isset($_GET['feed']))
			$config->posts_per_page = $config->feed_items;

		if (!ADMIN and method_exists($main, $action))
			$main->$action();

		# Call any plugin route functions
		if (!ADMIN)
			$trigger->call("route_".$action);

		if (isset($_GET['feed']))
			if ($trigger->exists($action."_feed")) # What about custom feeds?
				$trigger->call($action."_feed");
			elseif (isset($posts)) # Are there already posts to show?
				$action = "feed";
			else
				redirect($route->url("feed/")); # Really? Nothing? Too bad. MAIN FEED 4 U.
	}
