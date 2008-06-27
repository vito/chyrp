<?php
	define('MAIN_DIR', dirname(__FILE__));
	define('INCLUDES_DIR', MAIN_DIR."/includes");
	define('DEBUG', true);
	define('JAVASCRIPT', false);
	define('ADMIN', false);
	define('AJAX', false);
	define('XML_RPC', false);
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', true);

	require_once INCLUDES_DIR."/class/QueryBuilder.php"; # SQL query builder
	require_once INCLUDES_DIR."/class/Timestamp.php"; # A smarter DateTime class
	require_once INCLUDES_DIR."/lib/spyc.php"; # YAML parser
	require_once INCLUDES_DIR."/class/Trigger.php";
	require_once INCLUDES_DIR."/class/Model.php";
	require_once INCLUDES_DIR."/model/User.php";
	require_once INCLUDES_DIR."/model/Visitor.php";
	require_once INCLUDES_DIR."/class/Session.php"; # Session handler

	# Configuration files
	require INCLUDES_DIR."/config.php";
	require INCLUDES_DIR."/database.php";

	# Translation stuff
	require INCLUDES_DIR."/lib/gettext/gettext.php";
	require INCLUDES_DIR."/lib/gettext/streams.php";

	# Helpers
	require INCLUDES_DIR."/helpers.php";

	sanitize_input($_GET);
	sanitize_input($_POST);
	sanitize_input($_COOKIE);
	sanitize_input($_REQUEST);

	$url = "http://".$_SERVER['HTTP_HOST'].str_replace("/install.php", "", $_SERVER['REQUEST_URI']);
	$index = (parse_url($url, PHP_URL_PATH)) ? "/".trim(parse_url($url, PHP_URL_PATH), "/")."/" : "/" ;
	$htaccess = "<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase ".str_replace("install.php", "", $index)."\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^.+$ index.php [L]\n</IfModule>";

	$htaccess_has_chyrp = (file_exists(MAIN_DIR."/.htaccess") and preg_match("/".preg_quote($htaccess, "/")."/", file_get_contents(MAIN_DIR."/.htaccess")));

	$errors = array();
	$installed = false;

	# TODO: Determine exact PHP version that is required for Chyrp
	# Note (from Alex): Minimum requirement for PDO::prepare is 5.1.0
	if (version_compare(PHP_VERSION, "5.1.0", "<"))
		$errors[] = __("Chyrp requires PHP 5. Installation cannot continue.");

	if (!class_exists("PDO") or (!in_array("mysql", PDO::getAvailableDrivers()) and !in_array("sqlite", PDO::getAvailableDrivers())))
		$errors[] = __("Chyrp requires either the SQLite or the MySQL PDO driver. Installation cannot continue.");

	if (file_exists(INCLUDES_DIR."/config.yaml.php") and file_exists(INCLUDES_DIR."/database.yaml.php") and file_exists(MAIN_DIR."/.htaccess")) {
		$sql->load(INCLUDES_DIR."/database.yaml.php");
		$config->load(INCLUDES_DIR."/config.yaml.php");

		if ($sql->connect(true) and !empty($config->url) and $sql->count("users"))
			error(__("Already Installed"), __("Chyrp is already correctly installed and configured."));
	} else {
		if (# Directory is NOT writable, .htaccess file does NOT already exist.
			(!is_writable(MAIN_DIR) and !file_exists(MAIN_DIR."/.htaccess")) or
			# .htaccess file DOES exist, IS writable, and it does NOT contain the Chyrp htaccess whatnot.
		    (file_exists(MAIN_DIR."/.htaccess") and !is_writable(MAIN_DIR."/.htaccess") and !$htaccess_has_chyrp))
			$errors[] = _f("STOP! Before you go any further, you must create a .htaccess file in Chyrp's install directory and put this in it:\n<pre>%s</pre>", array(htmlspecialchars($htaccess)));

		if (!is_writable(INCLUDES_DIR))
			$errors[] = __("Chyrp's includes directory is not writable by the server. In order for the installer to generate your configuration files, please CHMOD or CHOWN it so that Chyrp can write to it.");
	}

	if (!empty($_POST)) {
		if ($_POST['adapter'] == "sqlite" and !is_writable(MAIN_DIR))
			$errors[] = __("SQLite database file could not be created. Please CHMOD your Chyrp directory to 777 and try again.");
		else
			if ($_POST['adapter'] == "mysql")
				try {
					new PDO($_POST['adapter'].":host=".$_POST['host'].";".((!empty($_POST['port'])) ? "port=".$_POST['port'].";" : "")."dbname=".$_POST['database'], $_POST['username'], $_POST['password']);
				} catch(PDOException $e) {
					$errors[] = __("Could not connect to the specified database.");
				}
			elseif ($_POST['adapter'] == "sqlite")
				try {
					new PDO("sqlite:".$_POST['database']);
				} catch(PDOException $e) {
					$errors[] = __("Could not connect to specified database.");
				}

		if (empty($_POST['name']))
			$errors[] = __("Please enter a name for your website.");

		if (!isset($_POST['timezone']))
			$errors[] = __("Time zone cannot be blank.");

		if (empty($_POST['login']))
			$errors[] = __("Please enter a username for your account.");

		if (empty($_POST['password_1']))
			$errors[] = __("Password cannot be blank.");

		if ($_POST['password_1'] != $_POST['password_2'])
			$errors[] = __("Passwords do not match.");

		if (empty($_POST['email']))
			$errors[] = __("E-Mail address cannot be blank.");

		if (empty($errors)) {
			$sql->set("host", $_POST['host']);
			$sql->set("username", $_POST['username']);
			$sql->set("password", $_POST['password']);
			$sql->set("database", $_POST['database']);
			$sql->set("prefix", $_POST['prefix']);
			$sql->set("adapter", $_POST['adapter']);

			$sql->prefix = $_POST['prefix'];

			$sql->connect();

			# Posts table
			$sql->query("CREATE TABLE IF NOT EXISTS `__posts` (
			                 `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
			                 `xml` LONGTEXT DEFAULT '',
			                 `feather` VARCHAR(32) DEFAULT '',
			                 `clean` VARCHAR(128) DEFAULT '',
			                 `url` VARCHAR(128) DEFAULT '',
			                 `pinned` TINYINT(1) DEFAULT '0',
			                 `status` VARCHAR(32) DEFAULT 'public',
			                 `user_id` INTEGER DEFAULT '0',
			                 `created_at` DATETIME DEFAULT '0000-00-00 00:00:00',
			                 `updated_at` DATETIME DEFAULT '0000-00-00 00:00:00'
			             ) DEFAULT CHARSET=utf8");

			# Pages table
			$sql->query("CREATE TABLE IF NOT EXISTS `__pages` (
			                 `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
			                 `title` VARCHAR(250) DEFAULT '',
			                 `body` LONGTEXT DEFAULT '',
			                 `show_in_list` TINYINT(1) DEFAULT '1',
			                 `list_order` INTEGER DEFAULT '0',
			                 `clean` VARCHAR(128) DEFAULT '',
			                 `url` VARCHAR(128) DEFAULT '',
			                 `user_id` INTEGER DEFAULT '0',
			                 `parent_id` INTEGER DEFAULT '0',
			                 `created_at` DATETIME DEFAULT '0000-00-00 00:00:00',
			                 `updated_at` DATETIME DEFAULT '0000-00-00 00:00:00'
			             ) DEFAULT CHARSET=utf8");

			# Users table
			$sql->query("CREATE TABLE IF NOT EXISTS `__users` (
			                 `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
			                 `login` VARCHAR(64) DEFAULT '',
			                 `password` VARCHAR(32) DEFAULT '',
			                 `full_name` VARCHAR(250) DEFAULT '',
			                 `email` VARCHAR(128) DEFAULT '',
			                 `website` VARCHAR(128) DEFAULT '',
			                 `group_id` INTEGER DEFAULT '0',
			                 `joined_at` DATETIME DEFAULT '0000-00-00 00:00:00',
			                 UNIQUE (`login`)
			             ) DEFAULT CHARSET=utf8");

			# Groups table
			$sql->query("CREATE TABLE IF NOT EXISTS `__groups` (
			                 `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
			                 `name` VARCHAR(100) DEFAULT '',
		                     `permissions` LONGTEXT DEFAULT '',
			                 UNIQUE (`name`)
			             ) DEFAULT CHARSET=utf8");

			# Permissions table
			$sql->query("CREATE TABLE IF NOT EXISTS `__permissions` (
			                 `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
			                 `name` VARCHAR(100) DEFAULT '',
			                 UNIQUE (`name`)
			             ) DEFAULT CHARSET=utf8");

			# Sessions table
			$sql->query("CREATE TABLE IF NOT EXISTS `__sessions` (
			                 `id` VARCHAR(32) DEFAULT '',
			                 `data` LONGTEXT DEFAULT '',
			                 `user_id` VARCHAR(16) DEFAULT '0',
			                 `created_at` DATETIME DEFAULT '0000-00-00 00:00:00',
			                 `updated_at` DATETIME DEFAULT '0000-00-00 00:00:00',
			                 PRIMARY KEY (`id`)
			             ) DEFAULT CHARSET=utf8");

			$permissions = array("view_site",
			                     "view_private",
			                     "view_draft",
			                     "view_own_draft",
			                     "add_post",
			                     "add_draft",
			                     "edit_post",
			                     "edit_draft",
			                     "edit_own_post",
			                     "edit_own_draft",
			                     "delete_post",
			                     "delete_draft",
			                     "delete_own_post",
			                     "delete_own_draft",
			                     "add_page",
			                     "edit_page",
			                     "delete_page",
			                     "add_user",
			                     "edit_user",
			                     "delete_user",
			                     "add_group",
			                     "edit_group",
			                     "delete_group",
			                     "change_settings",
			                     "toggle_extensions");

			foreach ($permissions as $permission)
				$sql->insert("permissions", array("name" => ":permission"), array(":permission" => $permission));

			$groups = array(
				"admin" => Spyc::YAMLDump($permissions),
				"member" => Spyc::YAMLDump(array("view_site")),
				"friend" => Spyc::YAMLDump(array("view_site", "view_private")),
				"banned" => Spyc::YAMLDump(array()),
				"guest" => Spyc::YAMLDump(array("view_site"))
			);

			# Insert the default groups (see above)
			foreach($groups as $name => $permissions)
				$sql->insert("groups", array("name" => ":name", "permissions" => ":permissions"), array(":name" => ucfirst($name), ":permissions" => $permissions));

			if (!file_exists(MAIN_DIR."/.htaccess"))
				if (!@file_put_contents(MAIN_DIR."/.htaccess", $htaccess))
					$errors[] = _f("Could not generate .htaccess file. Clean URLs will not be available unless you create it and put this in it:\n<pre>%s</pre>", array(htmlspecialchars($htaccess)));
			elseif (file_exists(MAIN_DIR."/.htaccess") and !$htaccess_has_chyrp)
				if (!@file_put_contents(MAIN_DIR."/.htaccess", "\n\n".$htaccess, FILE_APPEND))
					$errors[] = _f("Could not generate .htaccess file. Clean URLs will not be available unless you create it and put this in it:\n<pre>%s</pre>", array(htmlspecialchars($htaccess)));

			$config->set("name", $_POST['name']);
			$config->set("description", $_POST['description']);
			$config->set("url", "");
			$config->set("chyrp_url", $url);
			$config->set("email", $_POST['email']);
			$config->set("locale", "en_US");
			$config->set("theme", "default");
			$config->set("posts_per_page", 5);
			$config->set("feed_items", 20);
			$config->set("clean_urls", false);
			$config->set("post_url", "(year)/(month)/(day)/(url)/");
			$config->set("timezone", $_POST['timezone']);
			$config->set("can_register", true);
			$config->set("default_group", 2);
			$config->set("guest_group", 5);
			$config->set("enable_trackbacking", true);
			$config->set("send_pingbacks", false);
			$config->set("secure_hashkey", md5(random(32, true)));
			$config->set("uploads_path", "/uploads/");
			$config->set("enabled_modules", array());
			$config->set("enabled_feathers", array("text"));
			$config->set("routes", array());

			$config->load(INCLUDES_DIR."/config.yaml.php");

			if (!$sql->select("users", "id", "`__users`.`login` = :login", null, array(":login" => $_POST['login']))->fetchColumn())
				$sql->insert("users",
				             array("login" => ":login",
				                   "password" => ":password",
				                   "email" => ":email",
				                   "website" => ":website",
				                   "group_id" => ":group_id",
				                   "joined_at" => ":joined_at"),
				             array(":login" => $_POST['login'],
				                   ":password" => md5($_POST['password_1']),
				                   ":email" => $_POST['email'],
				                   ":website" => $config->url,
				                   ":group_id" => 1,
				                   ":joined_at" => datetime()
				             ));

			$installed = true;
		}
	}

	function value_fallback($index, $fallback = "") {
		echo (isset($_POST[$index])) ? $_POST[$index] : $fallback ;
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title>Chyrp Installer</title>
		<style type="text/css" media="screen">
			h2 {
				font-size: 1.25em;
				font-weight: bold;
			}
			input[type="password"], input[type="text"], textarea, select {
				font-size: 1.25em;
				width: 23.3em;
				padding: .3em;
				border: .1em solid #ddd;
			}
			form hr {
				border: 0;
				padding-bottom: 1em;
				margin-bottom: 4em;
				border-bottom: .1em dashed #ddd;
			}
			form p {
				padding-bottom: 1em;
			}
			.sub {
				font-size: .8em;
				color: #777;
				font-weight: normal;
			}
			.sub.inline {
				float: left;
				margin-top: -1.5em !important;
			}
			.error {
				padding: .6em .8em .5em 2.75em;
				border-bottom: .1em solid #FBC2C4;
				color: #D12F19;
				background: #FBE3E4 url('./admin/images/icons/failure.png') no-repeat .7em center;
			}
			.error.last {
				margin: 0 0 1em 0;
			}
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
			}
			h1 {
				color: #ccc;
				font-size: 3em;
				margin: .25em 0 .5em;
				text-align: center;
			}
			code {
				color: #06B;
				font-family: Monaco, monospace;
			}
			label {
				display: block;
				font-weight: bold;
				border-bottom: .1em dotted #ddd;
				margin-bottom: .2em;
			}
			.footer {
				color: #777;
				margin-top: 1em;
				font-size: .9em;
				text-align: center;
			}
			.error {
				color: #F22;
			}
			a:link, a:visited {
				color: #6B0;
			}
			a.big,
			button {
				background: #eee;
				margin-top: 2em;
				display: block;
				text-align: left;
				padding: .75em 1em;
				color: #777;
				text-shadow: #fff .1em .1em 0;
				font: 1em normal "Lucida Grande", "Verdana", Helvetica, Arial, sans-serif;
				text-decoration: none;
				border: 0;
				cursor: pointer;
				-webkit-border-radius: .5em;
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
			strong {
				font-weight: normal;
				color: #f00;
			}
			ol {
				margin: 0 0 2em 2em;
			}
			p {
				margin-bottom: 1em;
			}
			.center {
				text-align: center;
			}
		</style>
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js" type="text/javascript" charset="utf-8"></script>
		<script src="includes/lib/plugins.js" type="text/javascript" charset="utf-8"></script>
		<script type="text/javascript">
			$(function(){
				$("#adapter").change(function(){
					if ($(this).val() == "sqlite") {
						$(document.createElement("span")).addClass("sub").css("display", "none").html("<?php echo __("(full path)"); ?>").appendTo("#database_field label").animate({ opacity: "show" })
						$("#host_field, #username_field, #password_field, #prefix_field").animate({ height: "hide", opacity: "hide" })
					} else {
						$("#database_field label .sub").animate({ opacity: "hide" }).remove()
						$("#host_field, #username_field, #password_field, #prefix_field").animate({ height: "show", opacity: "show" })
					}
				})
			})
		</script>
	</head>
	<body>
<?php foreach ($errors as $index => $error): ?>
		<div class="error<?php if ($index + 1 == count($errors)) echo " last"; ?>"><?php echo $error; ?></div>
<?php endforeach; ?>
		<div class="window">
<?php if (!$installed): ?>
			<form action="install.php" method="post" accept-charset="utf-8">
				<h1><?php echo __("Database Setup"); ?></h1>
				<p id="adapter_field">
					<label for="adapter"><?php echo __("Adapter"); ?></label>
					<select name="adapter" id="adapter">
						<?php if (in_array("mysql", PDO::getAvailableDrivers())): ?>
						<option value="mysql" selected="selected">MySQL</option>
						<?php endif; ?>
						<?php if (in_array("sqlite", PDO::getAvailableDrivers())): ?>
						<option value="sqlite">SQLite 3</option>
						<?php endif; ?>
					</select>
				</p>
				<p id="host_field">
					<label for="host"><?php echo __("Host"); ?> <span class="sub"><?php echo __("(usually ok as \"localhost\")"); ?></span></label>
					<input type="text" name="host" value="<?php value_fallback("host", ((isset($_ENV['DATABASE_SERVER'])) ? $_ENV['DATABASE_SERVER'] : "localhost")); ?>" id="host" />
				</p>
				<p id="username_field">
					<label for="username"><?php echo __("Username"); ?></label>
					<input type="text" name="username" value="<?php value_fallback("username"); ?>" id="username" />
				</p>
				<p id="password_field">
					<label for="password"><?php echo __("Password"); ?></label>
					<input type="password" name="password" value="<?php value_fallback("password"); ?>" id="password" />
				</p>
				<p id="database_field">
					<label for="database"><?php echo __("Database"); ?> </label>
					<input type="text" name="database" value="<?php value_fallback("database"); ?>" id="database" />
				</p>
				<p id="prefix_field">
					<label for="prefix"><?php echo __("Table Prefix"); ?> <span class="sub"><?php echo __("(optional)"); ?></span></label>
					<input type="text" name="prefix" value="<?php value_fallback("prefix"); ?>" id="prefix" />
				</p>

				<hr />

				<h1><?php echo __("Website Setup"); ?></h1>
				<p id="name_field">
					<label for="name"><?php echo __("Site Name"); ?></label>
					<input type="text" name="name" value="<?php value_fallback("name", __("My Awesome Site")); ?>" id="name" />
				</p>
				<p id="description_field">
					<label for="description"><?php echo __("Description"); ?></label>
					<textarea name="description" rows="2" cols="40"><?php value_fallback("description"); ?></textarea>
				</p>
				<p id="timezone_field">
					<label for="timezone"><?php echo __("What time is it?"); ?></label>
					<select name="timezone" id="timezone">
<?php foreach (utc_timezones() as $zone): ?>
						<option value="<?php echo $zone["name"]; ?>"<?php selected($zone["name"], fallback($_POST['timezone'], "Africa/Abidjan", true)); ?>>
							<?php echo when(__("%I:%M %p on %B %d, %Y"), $zone["now"], true); ?>
							(GMT<?php if ($zone["offset"] >= 0) echo "+"; ?><?php echo $zone["offset"]; ?>)
						</option>
<?php endforeach; ?>
					</select>
				</p>

				<hr />

				<h1><?php echo __("Admin Account"); ?></h1>
				<p id="login_field">
					<label for="login"><?php echo __("Username"); ?></label>
					<input type="text" name="login" value="<?php value_fallback("login", "Admin"); ?>" id="login" />
				</p>
				<p id="password_1_field">
					<label for="password_1"><?php echo __("Password"); ?></label>
					<input type="password" name="password_1" value="<?php value_fallback("password_1"); ?>" id="password_1" />
				</p>
				<p id="password_2_field">
					<label for="password_2"><?php echo __("Password"); ?> <span class="sub"><?php echo __("(again)"); ?></span></label>
					<input type="password" name="password_2" value="<?php value_fallback("password_2"); ?>" id="password_2" />
				</p>
				<p id="email_field">
					<label for="email"><?php echo __("E-Mail Address"); ?></label>
					<input type="text" name="email" value="<?php value_fallback("email"); ?>" id="email" />
				</p>

				<button type="submit"><?php echo __("Install! &rarr;"); ?></button>
			</form>
<?php else: ?>
			<h1><?php echo __("Done!"); ?></h1>
			<p>
				<?php echo __("Chyrp has been successfully installed."); ?>
			</p>
			<h2><?php echo __("So, what now?"); ?></h2>
			<ol>
				<li><?php echo __("<strong>Delete install.php</strong>, you won't need it anymore."); ?></li>
<?php if (!is_writable(INCLUDES_DIR."/caches")): ?>
				<li><?php echo __("CHMOD <code>/includes/caches</code> to 777."); ?></li>
<?php endif; ?>
				<li><a href="http://chyrp.net/extend/browse/translations"><?php echo __("Look for a translation for your language."); ?></a></li>
				<li><a href="http://chyrp.net/extend/browse/modules"><?php echo __("Install some Modules."); ?></a></li>
				<li><a href="http://chyrp.net/extend/browse/feathers"><?php echo __("Find some Feathers you want."); ?></a></li>
				<li><a href="getting_started.html"><?php echo __("Read &#8220;Getting Started&#8221;"); ?></a></li>
			</ol>
			<a class="big" href="<?php echo $config->url; ?>"><?php echo __("Take me to my site! &rarr;"); ?></a>
<?php
	endif;
?>
		</div>
	</body>
</html>
