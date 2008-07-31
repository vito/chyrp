<?php
	header("Content-type: text/html; charset=UTF-8");

	define('DEBUG', true);
	define('UPGRADING', true);
	define('XML_RPC', true);
	define('MAIN_DIR', dirname(__FILE__));
	define('INCLUDES_DIR', dirname(__FILE__)."/includes");

	function config_file() {
		if (file_exists(INCLUDES_DIR."/config.yaml.php"))
			return INCLUDES_DIR."/config.yaml.php";

		if (file_exists(INCLUDES_DIR."/config.yml.php"))
			return INCLUDES_DIR."/config.yml.php";

		if (file_exists(INCLUDES_DIR."/config.php"))
			return INCLUDES_DIR."/config.php";

		exit("Config file not found.");
	}

	function database_file() {
		if (file_exists(INCLUDES_DIR."/database.yaml.php"))
			return INCLUDES_DIR."/database.yaml.php";

		if (file_exists(INCLUDES_DIR."/database.yml.php"))
			return INCLUDES_DIR."/database.yml.php";

		if (file_exists(INCLUDES_DIR."/database.php"))
			return INCLUDES_DIR."/database.php";

		return false;
	}

	function using_yaml() {
		return (basename(config_file()) != "config.php" and basename(database_file()) != "database.php");
	}

	# Evaluate the code in their config files, but with the classes renamed, so we can safely retrieve the values.
	if (!using_yaml()) {
		eval(str_replace(array("<?php", "?>", "Config"),
		                 array("", "", "OldConfig"),
		                 file_get_contents(config_file())));

		if (database_file())
			eval(str_replace(array("<?php", "?>", "SQL"),
			                 array("", "", "OldSQL"),
			                 file_get_contents(database_file())));
	}

	require_once INCLUDES_DIR."/helpers.php";
	require_once INCLUDES_DIR."/lib/Yaml.php";
	require_once INCLUDES_DIR."/lib/gettext/gettext.php";
	require_once INCLUDES_DIR."/lib/gettext/streams.php";

	$yaml = array();
	$yaml["config"] = array();
	$yaml["database"] = array();

	if (using_yaml()) {
		$yaml["config"] = Horde_Yaml::load(preg_replace("/<\?php(.+)\?>\n?/s", "", file_get_contents(config_file())));

		if (database_file())
			$yaml["database"] = Horde_Yaml::load(preg_replace("/<\?php(.+)\?>\n?/s",
			                                                            "",
			                                                            file_get_contents(database_file())));
		else
			$yaml["database"] = fallback($yaml["config"]["database"], array());
	} else {
		foreach ($config as $name => $val)
			$yaml["config"][$name] = $val;

		foreach ($sql as $name => $val)
			$yaml["database"][$name] = $val;
	}

	# Load the current SQL library (this overrides the $sql variable)
	require INCLUDES_DIR."/class/Query.php";
	require INCLUDES_DIR."/class/QueryBuilder.php";
	require INCLUDES_DIR."/class/SQL.php";

	fallback($yaml["database"]["adapter"], "mysql");

	foreach ($yaml["database"] as $name => $value)
		$sql->$name = $value;

	$sql->connect();

	class Config {
		static function get($setting) {
			global $yaml;
			return (isset($yaml["config"][$setting])) ? $yaml["config"][$setting] : false ;
		}

		static function set($setting, $value, $message = null) {
			if (self::get($setting) == $value) return;

			global $yaml;

			if (!isset($message))
				$message = _f("Setting %s to %s...", array($setting, normalize(print_r($value, true))));

			$yaml["config"][$setting] = $value;

			$protection = "<?php header(\"Status: 403\"); exit(\"Access denied.\"); ?>\n";

			$dump = $protection.Horde_Yaml::dump($yaml["config"]);

			echo $message.test(@file_put_contents(INCLUDES_DIR."/config.yaml.php", $dump));
		}

		static function check($setting) {
			global $yaml;
			return (isset($yaml["config"][$setting]));
		}

		static function fallback($setting, $value) {
			if (!self::check($setting))
				echo self::set($setting, $value, _f("Adding %s setting...", array($setting)));
		}

		static function remove($setting) {
			if (!self::check($setting)) return;

			global $yaml;

			unset($yaml["config"][$setting]);

			$protection = "<?php header(\"Status: 403\"); exit(\"Access denied.\"); ?>\n";

			$dump = $protection.Horde_Yaml::dump($yaml["config"]);

			echo _f("Removing %s setting...", array($setting)).
			     test(@file_put_contents(INCLUDES_DIR."/config.yaml.php", $dump));
		}
	}

	load_translator("chyrp", INCLUDES_DIR."/locale/".Config::get("locale").".mo");

	function test($try) {
		$sql = SQL::current();
		if (!empty($sql->error)) {
			$info = "\n".$sql->error."\n\n";
			$sql->error = "";
		} else
			$info = "";

		if ($try)
			return " <span class=\"yay\">".__("success!")."</span>\n";
		else
			return " <span class=\"boo\">".__("failed!")."</span>\n".$info;
	}

	function make_xml_safe(&$text) {
		return htmlspecialchars($text, ENT_NOQUOTES, "utf-8");
	}

	function xml2arr($parse) {
		if (empty($parse))
			return "";

		$parse = (array) $parse;

		foreach ($parse as &$val)
			if (get_class($val) == "SimpleXMLElement")
				$val = xml2arr($val);

		return $parse;
	}

	function arr2xml(&$object, $data) {
		foreach ($data as $key => $val) {
			if (is_int($key) and (empty($val) or trim($val) == "")) {
				unset($data[$key]);
				continue;
			}

			if (is_array($val)) {
				$xml = $object->addChild($key);
				arr2xml($xml, $val);
			} else
				$object->addChild($key, trim(fix($val, false, false)));
		}
	}

	#---------------------------------------------
	# Upgrading Actions
	#---------------------------------------------

	function fix_htaccess() {
		$url = "http://".$_SERVER['HTTP_HOST'].str_replace("/upgrade.php", "", $_SERVER['REQUEST_URI']);
		$index = (parse_url($url, PHP_URL_PATH)) ? "/".trim(parse_url($url, PHP_URL_PATH), "/")."/" : "/" ;

		$path = preg_quote($index, "/");
		$htaccess_has_chyrp = (file_exists(MAIN_DIR."/.htaccess") and preg_match("/<IfModule mod_rewrite\.c>\n([\s]*)RewriteEngine On\n([\s]*)RewriteBase {$path}\n([\s]*)RewriteCond %\{REQUEST_FILENAME\} !-f\n([\s]*)RewriteCond %\{REQUEST_FILENAME\} !-d\n([\s]*)RewriteRule (\^\.\+\\$|\!\\.\(gif\|jpg\|png\|css\)) index\.php \[L\]\n([\s]*)<\/IfModule>/", file_get_contents(MAIN_DIR."/.htaccess")));
		if ($htaccess_has_chyrp)
			return;

		$htaccess = "<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase {$index}\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^.+$ index.php [L]\n</IfModule>";

		if (!file_exists(MAIN_DIR."/.htaccess"))
			echo __("Generating .htaccess file...").
			     test(@file_put_contents(MAIN_DIR."/.htaccess", $htaccess));
		else
			echo __("Appending to .htaccess file...").
			     test(@file_put_contents(MAIN_DIR."/.htaccess", "\n\n".$htaccess, FILE_APPEND));
	}

	function tweets_to_posts() {
		if (SQL::current()->query("SELECT * FROM __tweets"))
			echo __("Renaming tweets table to posts...").
			     test(SQL::current()->query("RENAME TABLE __tweets TO __posts"));

		if (SQL::current()->query("SELECT add_tweet FROM __groups"))
			echo __("Renaming add_tweet permission to add_post...").
			     test(SQL::current()->query("ALTER TABLE __groups CHANGE add_tweet add_post TINYINT(1) NOT NULL DEFAULT '0'"));

		if (SQL::current()->query("SELECT edit_tweet FROM __groups"))
			echo __("Renaming edit_tweet permission to edit_post...").
			     test(SQL::current()->query("ALTER TABLE __groups CHANGE edit_tweet edit_post TINYINT(1) NOT NULL DEFAULT '0'"));

		if (SQL::current()->query("SELECT delete_tweet FROM __groups"))
			echo __("Renaming delete_tweet permission to delete_post...").
			     test(SQL::current()->query("ALTER TABLE __groups CHANGE delete_tweet delete_post TINYINT(1) NOT NULL DEFAULT '0'"));

		if (Config::check("tweets_per_page")) {
			Config::fallback("posts_per_page", Config::get("tweets_per_page"));
			Config::remove("tweets_per_page");
		}
	}

	function pages_parent_id_column() {
		if (SQL::current()->query("SELECT parent_id FROM __pages"))
			return;

		echo __("Adding parent_id column to pages table...").
		     test(SQL::current()->query("ALTER TABLE __pages ADD parent_id INT(11) NOT NULL DEFAULT '0' AFTER user_id"));
	}

	function pages_list_order_column() {
		if (SQL::current()->query("SELECT list_order FROM __pages"))
			return;

		echo __("Adding list_order column to pages table...").
		     test(SQL::current()->query("ALTER TABLE __pages ADD list_order INT(11) NOT NULL DEFAULT '0' AFTER show_in_list"));
	}

	function remove_beginning_slash_from_post_url() {
		if (substr(Config::get("post_url"), 0, 1) == "/")
			Config::set("post_url", ltrim(Config::get("post_url"), "/"));
	}

	function move_yml_yaml() {
		if (file_exists(INCLUDES_DIR."/config.yml.php"))
			echo __("Moving /includes/config.yml.php to /includes/config.yaml.php...").
			     test(@rename(INCLUDES_DIR."/config.yml.php", INCLUDES_DIR."/config.yaml.php"));
	}

	function update_protection() {
		if (!file_exists(INCLUDES_DIR."/config.yaml.php") or
		    substr_count(file_get_contents(INCLUDES_DIR."/config.yaml.php"),
		                 "<?php header(\"Status: 403\"); exit(\"Access denied.\"); ?>"))
			return;

		$contents = file_get_contents(INCLUDES_DIR."/config.yaml.php");
		$new_error = preg_replace("/<\?php (.+) \?>/",
		                     "<?php header(\"Status: 403\"); exit(\"Access denied.\"); ?>",
		                     $contents);

		echo __("Updating protection code in config.yaml.php...").
		     test(@file_put_contents(INCLUDES_DIR."/config.yaml.php", $new_error));
	}

	function theme_default_to_stardust() {
		if (Config::get("theme") != "default") return;
		Config::set("theme", "stardust");
	}

	function default_db_adapter_to_mysql() {
		$sql = SQL::current();
		if (isset($sql->adapter)) return;
		$sql->set("adapter", "mysql");
	}

	function move_upload() {
		if (file_exists(MAIN_DIR."/upload") and !file_exists(MAIN_DIR."/uploads"))
			echo __("Renaming /upload directory to /uploads...").test(@rename(MAIN_DIR."/upload", MAIN_DIR."/uploads"));
	}

	function make_posts_safe() {
		if (!$posts = SQL::current()->query("SELECT * FROM __posts"))
			return;

		# Replace all the posts' CDATAized XML with well-formed XML.
		while ($post = $posts->fetchObject()) {
			if (!substr_count($post->xml, "<![CDATA["))
				continue;

			$xml = simplexml_load_string($post->xml, "SimpleXMLElement", LIBXML_NOCDATA);

			$parse = xml2arr($xml);
			array_walk_recursive($parse, "make_xml_safe");

			$new_xml = new SimpleXMLElement("<post></post>");
			arr2xml($new_xml, $parse);

			echo _f("Sanitizing XML data of post #%d...", array($post->id)).
			     test(SQL::current()->update("posts",
			                                 "id = :post_id",
			                                 array("xml" => ":xml"),
			                                 array(":xml" => $new_xml->asXML(),
			                                       ":post_id" => $post->id)));
		}
	}

	function update_groups_to_yaml() {
		if (!SQL::current()->query("SELECT view_site FROM __groups")) return;

		$get_groups = SQL::current()->query("SELECT * FROM __groups");
		echo __("Backing up current groups table...").test($get_groups);
		if (!$get_groups) return;

		$groups = array();
		# Generate an array of groups, name => permissions.
		while ($group = $get_groups->fetchObject()) {
			$groups[$group->name] = array();
			foreach ($group as $key => $val)
				if ($key != "name" and $val)
					$groups[$group->name][] = $key;
		}

		# Convert permissions array to a YAML dump.
		foreach ($groups as $key => &$val)
			$val = Horde_Yaml::dump($val);

		$drop_groups = SQL::current()->query("DROP TABLE __groups");
		echo __("Dropping old groups table...").test($drop_groups);
		if (!$drop_groups) return;

		$groups_table = SQL::current()->query("CREATE TABLE IF NOT EXISTS __groups (
		                                           id INTEGER PRIMARY KEY AUTO_INCREMENT,
		                                           name VARCHAR(100) DEFAULT '',
	                                               permissions LONGTEXT,
		                                           UNIQUE (name)
		                                       ) DEFAULT CHARSET=utf8");
		echo __("Creating new groups table...").test($groups_table);
		if (!$groups_table) return;

		foreach($groups as $name => $permissions)
			echo _f("Restoring group \"%s\"...", array(ucfirst($name))).
			     test(SQL::current()->insert("groups",
			                                 array("name" => ":name",
			                                       "permissions" => ":permissions"),
			                                 array(":name" => ucfirst($name),
			                                       ":permissions" => $permissions)));
	}

	function add_permissions_table() {
		if (SQL::current()->query("SELECT * FROM __permissions")) return;

		$permissions_table = SQL::current()->query("CREATE TABLE __permissions (
		                                                id VARCHAR(100) DEFAULT '' PRIMARY KEY,
		                                                name VARCHAR(100) DEFAULT ''
		                                            ) DEFAULT CHARSET=utf8");
		echo __("Creating new permissions table...").test($permissions_table);
		if (!$permissions_table) return;

		$permissions = array("change_settings" => "Change Settings",
		                     "toggle_extensions" => "Toggle Extensions",
		                     "view_site" => "View Site",
		                     "view_private" => "View Private Posts",
		                     "view_draft" => "View Drafts",
		                     "view_own_draft" => "View Own Drafts",
		                     "add_post" => "Add Posts",
		                     "add_draft" => "Add Drafts",
		                     "edit_post" => "Edit Posts",
		                     "edit_draft" => "Edit Drafts",
		                     "edit_own_post" => "Edit Own Posts",
		                     "edit_own_draft" => "Edit Own Drafts",
		                     "delete_post" => "Delete Posts",
		                     "delete_draft" => "Delete Drafts",
		                     "delete_own_post" => "Delete Own Posts",
		                     "delete_own_draft" => "Delete Own Drafts",
		                     "add_page" => "Add Pages",
		                     "edit_page" => "Edit Pages",
		                     "delete_page" => "Delete Pages",
		                     "add_user" => "Add Users",
		                     "edit_user" => "Edit Users",
		                     "delete_user" => "Delete Users",
		                     "add_group" => "Add Groups",
		                     "edit_group" => "Edit Groups",
		                     "delete_group" => "Delete Groups");

		foreach ($permissions as $id => $name)
			echo _f("Inserting permission \"%s\"...", array($name)).
			     test(SQL::current()->insert("permissions",
			                                 array("id" => ":id",
			                                       "name" => ":name"),
			                                 array(":id" => $id,
			                                       ":name" => $name)));
	}

	function add_sessions_table() {
		if (SQL::current()->query("SELECT * FROM __sessions")) return;

		echo __("Creating sessions table...").
		     test(SQL::current()->query("CREATE TABLE __sessions (
		                                     id VARCHAR(40) DEFAULT '',
		                                     data LONGTEXT,
		                                     user_id INTEGER DEFAULT '0',
		                                     created_at DATETIME DEFAULT '0000-00-00 00:00:00',
		                                     updated_at DATETIME DEFAULT '0000-00-00 00:00:00',
		                                     PRIMARY KEY (id)
		                                 ) DEFAULT CHARSET=utf8") or die(mysql_error()));
	}

	function update_permissions_table() {
		# If there are any non-numeric IDs in the permissions database, assume this is already done.
		$check = SQL::current()->query("SELECT * FROM __permissions");
		while ($row = $check->fetchObject())
			if (!is_numeric($row->id))
				return;

		$permissions_backup = array();
		$get_permissions = SQL::current()->query("SELECT * FROM __permissions");
		echo __("Backing up current permissions table...").test($get_permissions);
		if (!$get_permissions) return;

		while ($permission = $get_permissions->fetchObject())
			$permissions_backup[] = $permission->name;

		$drop_permissions = SQL::current()->query("DROP TABLE __permissions");
		echo __("Dropping old permissions table...").test($drop_permissions);
		if (!$drop_permissions) return;

		echo __("Creating new permissions table...").
		     test(SQL::current()->query("CREATE TABLE IF NOT EXISTS __permissions (
			                                 id VARCHAR(100) DEFAULT '' PRIMARY KEY,
			                                 name VARCHAR(100) DEFAULT ''
			                             ) DEFAULT CHARSET=utf8"));

		$permissions = array("change_settings" => "Change Settings",
		                     "toggle_extensions" => "Toggle Extensions",
		                     "view_site" => "View Site",
		                     "view_private" => "View Private Posts",
		                     "view_draft" => "View Drafts",
		                     "view_own_draft" => "View Own Drafts",
		                     "add_post" => "Add Posts",
		                     "add_draft" => "Add Drafts",
		                     "edit_post" => "Edit Posts",
		                     "edit_draft" => "Edit Drafts",
		                     "edit_own_post" => "Edit Own Posts",
		                     "edit_own_draft" => "Edit Own Drafts",
		                     "delete_post" => "Delete Posts",
		                     "delete_draft" => "Delete Drafts",
		                     "delete_own_post" => "Delete Own Posts",
		                     "delete_own_draft" => "Delete Own Drafts",
		                     "add_page" => "Add Pages",
		                     "edit_page" => "Edit Pages",
		                     "delete_page" => "Delete Pages",
		                     "add_user" => "Add Users",
		                     "edit_user" => "Edit Users",
		                     "delete_user" => "Delete Users",
		                     "add_group" => "Add Groups",
		                     "edit_group" => "Edit Groups",
		                     "delete_group" => "Delete Groups");

		foreach ($permissions_backup as $id) {
			$name = isset($permissions[$id]) ? $permissions[$id] : camelize($id, true);
			echo _f("Restoring permission \"%s\"...", array($name)).
			     test(SQL::current()->insert("permissions",
			                                 array("id" => ":id",
			                                       "name" => ":name"),
			                                 array(":id" => $id,
			                                       ":name" => $name)));
		}

	}

	function update_custom_routes() {
		$custom_routes = Config::get("routes");
		if (empty($custom_routes)) return;

		$new_routes = array();
		foreach ($custom_routes as $key => $route) {
			if (!is_int($key))
				return;

			$split = array_filter(explode("/", $route));

			if (!isset($split[0]))
				return;

			echo _f("Updating custom route %s to new format...", array($route)).
			     test(isset($split[0]) and $new_routes[$route] = $split[0]);
		}

		Config::set("routes", $new_routes, "Setting new custom routes configuration...");
	}

	function remove_database_config() {
		if (file_exists(INCLUDES_DIR."/database.yaml.php"))
			echo __("Removing database.yaml.php file...").
			     test(@unlink(INCLUDES_DIR."/database.yaml.php"));
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title><?php echo __("Chyrp Upgrader"); ?></title>
		<style type="text/css" media="screen">
			html, body, ul, ol, li,
			h1, h2, h3, h4, h5, h6,
			form, fieldset, a, p {
				margin: 0;
				padding: 0;
				border: 0;
			}
			html {
				font-size: 62.5%;
			}
			body {
				font: 1.25em/1.5em normal "Verdana", Helvetica, Arial, sans-serif;
				color: #626262;
				background: #e8e8e8;
				padding: 0 0 5em;
			}
			.window {
				width: 30em;
				background: #fff;
				padding: 2em;
				margin: 5em auto 0;
				-webkit-border-radius: 2em;
				-moz-border-radius: 2em;
			}
			h1 {
				color: #ccc;
				font-size: 3em;
				margin: 1em 0 .5em;
				text-align: center;
			}
			h1.first {
				margin-top: .25em;
			}
			h1.what_now {
				margin-top: .5em;
			}
			code {
				color: #06B;
				font-family: Monaco, monospace;
			}
			a:link, a:visited {
				color: #6B0;
			}
			pre.pane {
				height: 15em;
				overflow-y: auto;
				margin: -2.5em -2.5em 4em;
				padding: 2.5em;
				background: #333;
				color: #fff;
				-webkit-border-top-left-radius: 2.5em;
				-webkit-border-top-right-radius: 2.5em;
			}
			span.yay { color: #0f0; }
			span.boo { color: #f00; }
			a.big,
			button {
				background: #eee;
				display: block;
				text-align: center;
				margin-top: 2em;
				padding: .75em 1em;
				color: #777;
				text-shadow: #fff .1em .1em 0;
				font: 1em normal "Lucida Grande", "Verdana", Helvetica, Arial, sans-serif;
				text-decoration: none;
				border: 0;
				cursor: pointer;
				-webkit-border-radius: .5em;
				-moz-border-radius: .5em;
			}
			button {
				width: 100%;
			}
			a.big:hover,
			button:hover {
				background: #f5f5f5;
			}
			a.big:active,
			button:active {
				background: #e0e0e0;
			}
			ul, ol {
				margin: 0 0 1em 2em;
			}
			li {
				margin-bottom: .5em;
			}
			ul {
				margin-bottom: 1.5em;
			}
			p {
				margin-bottom: 1em;
			}
		</style>
	</head>
	<body>
		<div class="window">
<?php if (!empty($_POST) and $_POST['upgrade'] == "yes"): ?>
			<pre class="pane"><?php
		fix_htaccess();

		tweets_to_posts();

		pages_parent_id_column();

		pages_list_order_column();

		remove_beginning_slash_from_post_url();

		move_yml_yaml();

		update_protection();

		theme_default_to_stardust();

		Config::fallback("secure_hashkey", md5(random(32, true)));
		Config::fallback("enable_xmlrpc", true);
		Config::fallback("uploads_path", "/uploads/");
		Config::fallback("chyrp_url", Config::get("url"));
		Config::fallback("feed_items", Config::get("rss_posts"));

		Config::fallback("timezone", "America/Indiana/Indianapolis");

		Config::remove("rss_posts");

		Config::remove("time_offset");

		Config::fallback("database", $yaml["database"]);

		default_db_adapter_to_mysql();

		move_upload();

		make_posts_safe();

		update_groups_to_yaml();

		add_permissions_table();

		add_sessions_table();

		update_permissions_table();

		update_custom_routes();

		remove_database_config();

		foreach ((array) Config::get("enabled_modules") as $module)
			if (file_exists(MAIN_DIR."/modules/".$module."/upgrades.php")) {
				echo _f("Calling \"%s\" module's upgrader...", array($module))."\n";
				require MAIN_DIR."/modules/".$module."/upgrades.php";
			}

		foreach ((array) Config::get("enabled_feathers") as $feather)
			if (file_exists(MAIN_DIR."/feathers/".$feather."/upgrades.php")) {
				echo _f("Calling \"%s\" feather's upgrader...", array($feather))."\n";
				require MAIN_DIR."/feathers/".$feather."/upgrades.php";
			}
?>

<?php echo __("Done!"); ?>

</pre>
			<h1 class="what_now"><?php echo __("What now?"); ?></h1>
			<ol>
				<li><?php echo __("Look through the results up there for any failed tasks. If you see any and you can't figure out why, you can ask for help at the <a href=\"http://chyrp.net/community/\">Chyrp Community</a>."); ?></li>
				<li><?php echo __("If any of your Modules or Feathers have new versions available for this release, check if an <code>upgrades.php</code> file exists in their main directory. If that file exists, run this upgrader again after enabling the Module or Feather and it will run the upgrade tasks."); ?></li>
				<li><?php echo __("When you are done, you can delete this file. It doesn't pose any real threat on its own, but you should delete it anyway, just to be sure."); ?></li>
			</ol>
			<h1 class="tips"><?php echo __("Tips"); ?></h1>
			<ul>
				<li><?php echo __("If the admin area looks weird, try clearing your cache."); ?></li>
				<li><?php echo __("As of v2.0, Chyrp uses time zones to determine timestamps. Please set your installation to the correct timezone at <a href=\"admin/index.php?action=general_settings\">General Settings</a>."); ?></li>
				<li><?php echo __("Check the group permissions &ndash; they might have changed."); ?></li>
			</ul>
			<a class="big" href="<?php echo (Config::check("url") ? Config::get("url") : Config::get("chyrp_url")); ?>"><?php echo __("All done!"); ?></a>
<?php else: ?>
			<h1 class="first"><?php echo __("Halt!"); ?></h1>
			<p><?php echo __("That button may look ready for a-clickin&rsquo;, but please take these preemptive measures before indulging:"); ?></p>
			<ol>
				<li><?php echo __("<strong>Make a backup of your installation.</strong> You never know."); ?></li>
				<li><?php echo __("Disable any third-party Modules and Feathers."); ?></li>
				<li><?php echo __("Ensure that the Chyrp installation directory is writable by the server."); ?></li>
			</ol>
			<p><?php echo __("If any of the upgrade processes fail, you can safely keep refreshing &ndash; it will only attempt to do tasks that are not already successfully completed. If you cannot figure something out, please make a topic (with details!) at the <a href=\"http://chyrp.net/community/\">Chyrp Community</a>."); ?></p>
			<form action="upgrade.php" method="post">
				<button type="submit" name="upgrade" value="yes"><?php echo __("Upgrade me!"); ?></button>
			</form>
<?php endif; ?>
		</div>
	</body>
</html>