<?php
	/**
	 * File: Common
	 * 
	 * Chyrp - A Lightweight Blogging Engine
	 * 
	 * Version:
	 * 	v1.1.3
	 * 
	 * License:
	 * 	GPL-3
	 * 
	 * Chyrp Copyright:
	 * 	Copyright (c) 2008 Alex Suraci, <http://i.am.toogeneric.com/>
	 */
	
	# Constant: CHYRP_VERSION
	# Chyrp's version number.
	define('CHYRP_VERSION', "1.1.3");
	if (!defined('JAVASCRIPT')) define('JAVASCRIPT', false);
	if (!defined('ADMIN')) define('ADMIN', false);
	if (!defined('AJAX')) define('AJAX', false);
	if (!defined('XML_RPC')) define('XML_RPC', false);
	if (extension_loaded('zlib')) ob_start("ob_gzhandler"); else ob_start();
	if (!JAVASCRIPT and !XML_RPC) header("Content-type: text/html; charset=UTF-8");
	
	# Constant: MAIN_DIR
	# Absolute path to the Chyrp root
	define('MAIN_DIR', pathinfo(dirname(__FILE__), PATHINFO_DIRNAME)); # Path for /
	# Constant: INCLUDES_DIR
	# Absolute path to /includes
	define('INCLUDES_DIR', MAIN_DIR."/includes"); # Path for /includes/
	
	# Not installed?
	if (!file_exists(INCLUDES_DIR."/config.yaml.php") or !file_exists(INCLUDES_DIR."/database.yaml.php")) {
		header("Location: install.php");
		exit;
	}
	
	require_once INCLUDES_DIR."/input.php"; # Input sanitizer
	require_once INCLUDES_DIR."/lib/spyc.php"; # YAML parser
	
	require_once INCLUDES_DIR."/config.php"; # Configuration
	require_once INCLUDES_DIR."/database.php"; # Database/SQL jazz
	
	# Translation stuff
	require_once INCLUDES_DIR."/lib/gettext/gettext.php";
	require_once INCLUDES_DIR."/lib/gettext/streams.php";
	
	# File: l10n
	# Loads localization functions.
	require_once INCLUDES_DIR."/lib/l10n.php";
	
	# Load the configuration settings
	$config->load(INCLUDES_DIR."/config.yaml.php");
	
	# Constant: MODULES_DIR
	# Absolute path to /modules
	define('MODULES_DIR', MAIN_DIR."/modules");
	# Constant: FEATHERS_DIR
	# Absolute path to /feathers
	define('FEATHERS_DIR', MAIN_DIR."/feathers");
	# Constant: THEMES_DIR
	# Absolute path to /themes
	define('THEMES_DIR', MAIN_DIR."/themes");
	# Constant: THEME_DIR
	# Absolute path to /themes/(current theme)
	define('THEME_DIR', MAIN_DIR."/themes/".$config->theme);
	# Constant: THEME_URL
	# URL to the current theme's folder
	define('THEME_URL', $config->url."/themes/".$config->theme);
	
	header("X-Pingback: ".$config->url."/includes/xmlrpc.php");
	
	if (!ADMIN and !JAVASCRIPT and !XML_RPC and strpos($_SERVER['REQUEST_URI'], "?"))
		$config->clean_urls = false;
	
	if (!count($config->enabled_feathers))
		error(__("No Feathers"), __("There aren't any feathers enabled."));
	
	$sql->connect();
	
	# File: Helpers
	require_once INCLUDES_DIR."/helpers.php";
	
	# File: Trigger
	# See Also:
	# 	<Trigger>
	require_once INCLUDES_DIR."/class/Trigger.php";
	
	# File: Module
	# See Also:
	# 	<Module>
	require_once INCLUDES_DIR."/class/Module.php";
	
	# File: Feather
	# See Also:
	# 	<Feather>
	require_once INCLUDES_DIR."/class/Feather.php";
	
	# File: Pagination
	# See Also:
	# 	<Pagination>
	require_once INCLUDES_DIR."/class/Pagination.php";
	
	# File: Theme
	# See Also:
	# 	<Theme>
	require_once INCLUDES_DIR."/class/Theme.php";
	
	# File: Route
	# See Also:
	# 	<Route>
	require_once INCLUDES_DIR."/class/Route.php";
	
	# File: User
	# See Also:
	# 	<User>
	require_once INCLUDES_DIR."/model/User.php";
	
	# File: Post
	# See Also:
	# 	<Post>
	require_once INCLUDES_DIR."/model/Post.php";
	
	# File: Page
	# See Also:
	# 	<Page>
	require_once INCLUDES_DIR."/model/Page.php";
	
	# File: Group
	# See Also:
	# 	<Group>
	require_once INCLUDES_DIR."/model/Group.php";
	
	# File: Main
	# See Also:
	# 	<Main Controller>
	require_once INCLUDES_DIR."/controller/Main.php";
	
	# File: Admin
	# See Also:
	# 	<Admin Controller>
	if (ADMIN)
		require_once INCLUDES_DIR."/controller/Admin.php";
	
	timer_start();
	
	/**
	 * Array: $feathers
	 * Contains all of the enabled Feather's Classes.
	 */
	$feathers = array();
	$plural_feathers = array();
	foreach ($config->enabled_feathers as $feather) {
		if (file_exists(FEATHERS_DIR."/".$feather."/locale/".$config->locale.".mo"))
			load_translator($feather, FEATHERS_DIR."/".$feather."/locale/".$config->locale.".mo");
		
		$info = Spyc::YAMLLoad(FEATHERS_DIR."/".$feather."/info.yaml");
		fallback($info["plural"], null);
		
		require FEATHERS_DIR."/".$feather."/feather.php";
		$class = camelize($feather);
		$feathers[$feather] = new $class();
		$index = (isset($info["plural"])) ? $info["plural"] : $feather."s" ;
		$plural_feathers[$index] = $feather;
	}
	
	foreach ($config->enabled_modules as $module) {
		if (file_exists(MODULES_DIR."/".$module."/locale/".$config->locale.".mo"))
			load_translator($module, MODULES_DIR."/".$module."/locale/".$config->locale.".mo");
		
		$module_class = camelize($module);
		require MODULES_DIR."/".$module."/module.php";
	}
	
	$route->determine_action();
	
	$action = (isset($_GET['action'])) ? strip_tags($_GET['action']) : "index" ;

	if (XML_RPC)
		$action = "XML-RPC";	
	else {
		/**
		 * Integer: $current_user
		 * The current user's ID. If not logged in, defaults to 0.
		 */
		$current_user = (int) fallback($_COOKIE['chyrp_user_id'], 0, true);
		
		# Load the current user's information into User
		$user->load();
		
		# Load the current user's group's information into Group
		$group->load();
	
		/**
		 * Function: error
		 * Shows an error message.
		 * 
		 * Parameters:
		 * 	$title - The title for the error dialog.
		 * 	$body - The message for the error dialog.
		 */
		function error($title, $body) {
			require (defined('THEME_DIR') and file_exists(THEME_DIR."/content/error.php")) ? THEME_DIR."/content/error.php" : INCLUDES_DIR."/error.php" ;
			exit;
		}
		
		if (!$user->can("view_site") and $action != "process_login" and $action != "login" and $action != "logout")
			error(__("Access Denied"), __("You are not allowed to view this site."));
	}
	
	# Load the translation engine
	load_translator("chyrp", INCLUDES_DIR."/locale/".$config->locale.".mo");
	
	# File: Snippets
	# The current theme's Snippets.
	require THEME_DIR."/snippets.php";
	$snippet = new Snippet();
	
	# Load the theme translator
	if (file_exists(THEME_DIR."/locale/".$config->locale.".mo"))
		load_translator("theme", THEME_DIR."/locale/".$config->locale.".mo");
	
	$trigger->call("runtime");
	
	if (!JAVASCRIPT and !XML_RPC) {
		/**
		 * Boolean: $is_feed
		 * Whether they're viewing the feed or not.
		 */
		$is_feed = isset($_GET['feed']);
		
		if (in_array($action, array_keys($plural_feathers)))
			$action = "feather";
		
		/**
		 * String: $private
		 * SQL "where" text for which posts the current user can view.
		 */
		$statuses = array("public");
		if ($user->logged_in())
			$statuses[] = "registered_only";
		if ($user->can("view_private"))
			$statuses[] = "private";
		if ($action == "view" and $user->can("view_draft"))
			$statuses[] = "draft";
		$private = "`status` in ('".implode("', '", $statuses)."')";
		
		/**
		 * String: $enabled_feathers
		 * SQL "where" text for each of the feathers. Prevents posts of a disabled Feather from showing.
		 */
		$enabled_feathers = " and `feather` in ('".implode("', '", $config->enabled_feathers)."')";
		
		if (!empty($action) and (method_exists($main, $action) or (ADMIN and method_exists($admin, $action) or ADMIN and $trigger->exists("admin_".$action)) or $trigger->exists("route_".$action))) {
			if ($is_feed)
				$config->posts_per_page = $config->feed_items;
			
			if (method_exists($main, $action))
				$main->$action();
			
			if (ADMIN and method_exists($admin, $action))
				$admin->$action();
			
			# Call any plugin route functions
			$trigger->call("route_".$action);
			
			if (ADMIN)
				$trigger->call("admin_".$action);
		}
		
		/**
		 * Boolean: $viewing
		 * Returns whether they're viewing a post or not.
		 */
		$viewing = ($action == "view");
		
		if ($is_feed)
			if ($trigger->exists($action."_feed")) # What about custom feeds?
				$trigger->call($action."_feed");
			elseif (isset($get_posts)) # Are there already posts to show?
				$action = "feed";
			else
				$route->redirect($route->url("feed/")); # Really? Nothing? Too bad. MAIN FEED 4 U.
	}
