<?php
	define('BASE_DIR', dirname(__FILE__));
	define('INCLUDES_DIR', BASE_DIR."/includes");
	define('JAVASCRIPT', false);
	define('ADMIN', false);
	define('AJAX', false);
	define('XML_RPC', false);
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', true);

	# Input sanitizer
	require INCLUDES_DIR."/input.php";

	# YAML parser
	require INCLUDES_DIR."/lib/spyc.php";

	# Configuration files
	require INCLUDES_DIR."/config.php";
	require INCLUDES_DIR."/database.php";

	# Translation stuff
	require INCLUDES_DIR."/lib/gettext/gettext.php";
	require INCLUDES_DIR."/lib/gettext/streams.php";
	require INCLUDES_DIR."/lib/l10n.php";

	# Helpers
	require INCLUDES_DIR."/helpers.php";

	function error($title, $body) {
		require INCLUDES_DIR."/error.php";
		exit;
	}

	$url = "http://".$_SERVER['HTTP_HOST'].str_replace("/install.php", "", $_SERVER['REQUEST_URI']);
	$index = (parse_url($url, PHP_URL_PATH)) ? "/".trim(parse_url($url, PHP_URL_PATH), "/")."/" : "/" ;
	$htaccess = "<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase ".str_replace("install.php", "", $index)."\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^.+$ index.php [L]\n</IfModule>";

	$errors = array();
	$installed = false;

	if (file_exists(INCLUDES_DIR."/config.yaml.php") and file_exists(INCLUDES_DIR."/database.yaml.php") and file_exists(BASE_DIR."/.htaccess")) {
		$sql->load(INCLUDES_DIR."/database.yaml.php");
		$config->load(INCLUDES_DIR."/config.yaml.php");

		if ($sql->connect(true) and !empty($config->url) and $sql->query("select count(`id`) from `".$sql->prefix."users`")->fetchColumn())
			error(__("Already Installed"), __("Chyrp is already correctly installed and configured."));
	}
	else {
		if (!is_writable(BASE_DIR))
			$errors[] = sprintf(__("STOP! Before you go any further, you must create a .htaccess file in Chyrp's install directory and put this in it:\n<pre>%s</pre>."), htmlspecialchars($htaccess));

		if (!is_writable(INCLUDES_DIR))
			$errors[] = __("Chyrp's includes directory is not writable by the server.");
	}

	if (!empty($_POST)) {
		if (!@mysql_connect($_POST['host'], $_POST['username'], $_POST['password']))
			$errors[] = sprintf(__("Could not connect to the MySQL server: %s"), mysql_error());
		else
			if (!@mysql_select_db($_POST['database']))
				$errors[] = __("Could not switch to the MySQL database.");

		if (empty($_POST['name']))
			$errors[] = __("Please enter a name for your website.");

		if (!isset($_POST['time_offset']))
			$errors[] = __("Time offset cannot be blank.");

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
			$sql->set("adapter", "mysql");

			$sql->prefix = $_POST['prefix'];

			$sql->connect();

			# Posts table
			$sql->query("create table if not exists `".$sql->prefix."posts` (
			             	`id` int(11) not null auto_increment, 
			             	`xml` longtext not null,
			             	`feather` varchar(32) not null default '', 
			             	`clean` varchar(128) not null default '', 
			             	`url` varchar(128) not null default '', 
			             	`pinned` tinyint(1) not null default '0', 
			             	`status` enum('public','draft','private','registered_only') not null default 'public', 
			             	`user_id` int(11) not null default '0', 
			             	`created_at` datetime not null default '0000-00-00 00:00:00', 
			             	`updated_at` datetime not null default '0000-00-00 00:00:00', 
			             	primary key (`id`)
			             ) default charset=utf8");

			# Pages table
			$sql->query("create table if not exists `".$sql->prefix."pages` (
			             	`id` int(11) not null auto_increment, 
			             	`title` varchar(250) not null default '', 
			             	`body` longtext not null, 
			             	`user_id` int(11) not null default '0', 
			             	`parent_id` int(11) not null default '0', 
			             	`show_in_list` tinyint(1) not null default '1', 
			             	`list_order` int(11) not null default '0', 
			             	`clean` varchar(128) not null default '', 
			             	`url` varchar(128) not null default '', 
			             	`created_at` datetime not null default '0000-00-00 00:00:00', 
			             	`updated_at` datetime not null default '0000-00-00 00:00:00', 
			             	primary key (`id`)
			             ) default charset=utf8");

			# Users table
			$sql->query("create table if not exists `".$sql->prefix."users` (
			             	`id` int(11) not null auto_increment, 
			             	`login` varchar(64) not null default '', 
			             	`password` varchar(32) not null default '', 
			             	`full_name` varchar(250) not null default '', 
			             	`email` varchar(128) not null default '', 
			             	`website` varchar(128) not null default '', 
			             	`group_id` int(11) not null default '0', 
			             	`joined_at` datetime not null default '0000-00-00 00:00:00', 
			             	primary key (`id`), 
			             	unique (`login`)
			             ) default charset=utf8");

			# Groups table
			$sql->query("create table if not exists `".$sql->prefix."groups` (
			             	`id` int(11) not null auto_increment, 
			             	`name` varchar(100) not null default '', 
		                 	`permissions` longtext not null, 
			             	primary key (`id`), 
			             	unique (`name`)
			             ) default charset=utf8");

			# Permissions table
			$sql->query("create table if not exists `".$sql->prefix."permissions` (
			             	`id` int(11) not null auto_increment, 
			             	`name` varchar(100) not null default '', 
			             	primary key (`id`), 
			             	unique (`name`)
			             ) default charset=utf8");

			$permissions = array("view_site", "change_settings", "add_post", "edit_post", "delete_post", "view_private", "view_draft", "add_page", "edit_page", "delete_page", "edit_user", "delete_user", "add_group", "edit_group", "delete_group");

			foreach ($permissions as $permission)
				$sql->query("insert into `".$sql->prefix."permissions` set `name` = '".$permission."'");

			$groups = array(
				"admin" => Spyc::YAMLDump($permissions),
				"member" => Spyc::YAMLDump(array("view_site")),
				"friend" => Spyc::YAMLDump(array("view_site", "view_private")),
				"banned" => Spyc::YAMLDump(array()),
				"guest" => Spyc::YAMLDump(array("view_site", "view_private"))
			);

			# Insert the default groups (see above)
			foreach($groups as $name => $permission)
				$sql->query("insert into `".$sql->prefix."groups` set 
			                 `name` = '".ucfirst($name)."', 
			                 `permissions` = '".$permission."'");

			if (!file_exists(BASE_DIR."/.htaccess") and !is_writable(BASE_DIR))
				$errors[] = __("Could not generate .htaccess file. Clean URLs will not be available.");
			else {
				$open_htaccess = fopen(BASE_DIR."/.htaccess", "w");
				fwrite($open_htaccess, $htaccess);
				fclose($open_htaccess);
			}

			$config->set("name", $_POST['name']);
			$config->set("description", $_POST['description']);
			$config->set("url", $url);
			$config->set("email", $_POST['email']);
			$config->set("locale", "en_US");
			$config->set("theme", "default");
			$config->set("posts_per_page", 5);
			$config->set("feed_items", 20);
			$config->set("clean_urls", false);
			$config->set("post_url", "(year)/(month)/(day)/(url)/");
			$config->set("time_offset", $_POST['time_offset'] * 3600);
			$config->set("can_register", true);
			$config->set("default_group", 2);
			$config->set("guest_group", 5);
			$config->set("enable_trackbacking", true);
			$config->set("send_pingbacks", false);
			$config->set("secure_hashkey", md5(random(32, true)));
			$config->set("enabled_modules", array());
			$config->set("enabled_feathers", array("text"));
			$config->set("routes", array());

			$config->load(INCLUDES_DIR."/config.yaml.php");

			if (!$sql->query("select `id` from `".$sql->prefix."users` where `login` = :login", array(":login" => $_POST['login']))->rowCount())
				$sql->query("insert into `".$sql->prefix."users` set 
				             	`login` = :login, 
				             	`password` = :password, 
				             	`email` = :email, 
				             	`website` = :website, 
				             	`group_id` = 1, 
				             	`joined_at` = :datetime",
				            array(
				            	":login" => $_POST['login'],
				            	":password" => md5($_POST['password_1']),
				            	":email" => $_POST['email'],
				            	":website" => $config->url,
				            	":datetime" => datetime()
				            ));

			setcookie("chyrp_user_id", $sql->db->lastInsertId(), time() + 2592000, "/");
			setcookie("chyrp_password", md5($_POST['password_1']), time() + 2592000, "/");

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
			body {
				font: .8em/1.5em normal "Lucida Grande", "Trebuchet MS", Verdana, Helvetica, Arial, sans-serif;
				color: #333;
				background: #eee;
				margin: 0;
				padding: 0;
			}
			a {
				color: #0088FF;
			}
			h1 {
				font-size: 1.75em;
				margin-top: 0;
				color: #aaa;
				font-weight: bold;
			}
			h2 {
				font-size: 1.25em;
				font-weight: bold;
			}
			ol {
				margin: 0 0 1em;
				padding: 0 0 0 2em;
			}
			label {
				display: block;
				font-weight: bold;
				border-bottom: 1px dotted #ddd;
				margin-bottom: 2px;
			}
			input[type="password"], input[type="text"], textarea {
				margin-bottom: 1em;
				font-size: 1.25em;
				width: 242px;
				padding: 3px;
				border: 1px solid #ddd;
			}
			textarea {
				margin-bottom: .75em;
			}
			select {
				margin-bottom: 1em;
			}
			form p {
				padding-bottom: 1em;
				margin-bottom: 2em;
				border-bottom: 1px dashed #ddd;
			}
			form p.extra {
				padding-bottom: 2em;
			}
			.window {
				width: 250px;
				margin: 25px auto;
				padding: 1em;
				border: 1px solid #ddd;
				background: #fff;
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
			.center {
				text-align: center;
				padding: 0;
				margin-bottom: 1em;
				border: 0;
			}
			.error {
				padding: 6px 8px 5px 30px;
				border-bottom: 1px solid #FBC2C4;
				color: #D12F19;
				background: #FBE3E4 url('./admin/icons/failure.png') no-repeat 7px center;
			}
			.error.last {
				margin: 0 0 1em 0;
			}
			.done {
				font-size: 1.25em;
				font-weight: bold;
				text-decoration: none;
				color: #555;
			}
		</style>
	</head>
	<body>
<?php foreach ($errors as $index => $error): ?>
		<div class="error<?php if ($index + 1 == count($errors)) echo " last"; ?>"><?php echo $error; ?></div>
<?php endforeach; ?>
		<div class="window">
<?php if (!$installed): ?>
			<form action="install.php" method="post" accept-charset="utf-8">
				<h1><?php echo __("Database Setup"); ?></h1>
				<p>
					<label for="host"><?php echo __("Host"); ?> <span class="sub"><?php echo __("(usually ok as \"localhost\")"); ?></span></label>
					<input type="text" name="host" value="<?php value_fallback("host", ((isset($_ENV['DATABASE_SERVER'])) ? $_ENV['DATABASE_SERVER'] : "localhost")); ?>" id="host" />
					<label for="username"><?php echo __("Username"); ?></label>
					<input type="text" name="username" value="<?php value_fallback("username"); ?>" id="username" />
					<label for="password"><?php echo __("Password"); ?></label>
					<input type="password" name="password" value="<?php value_fallback("password"); ?>" id="password" />
					<label for="database"><?php echo __("Database"); ?></label>
					<input type="text" name="database" value="<?php value_fallback("database"); ?>" id="database" />
					<label for="prefix"><?php echo __("Table Prefix"); ?> <span class="sub"><?php echo __("(optional)"); ?></span></label>
					<input type="text" name="prefix" value="<?php value_fallback("prefix"); ?>" id="prefix" />
				</p>

				<h1><?php echo __("Website Setup"); ?></h1>
				<p class="extra">
					<label for="name"><?php echo __("Site Name"); ?></label>
					<input type="text" name="name" value="<?php value_fallback("name", __("My Awesome Site")); ?>" id="name" />
					<label for="description"><?php echo __("Description"); ?></label>
					<textarea name="description" rows="2" cols="40"><?php value_fallback("description"); ?></textarea>
					<label for="time_offset"><?php echo __("Time Offset"); ?></label>
					<input type="text" name="time_offset" value="0" id="time_offset" />
					<span class="sub inline">(server time: <?php echo @date("F jS, Y g:i A"); ?>)</span>
				</p>

				<h1><?php echo __("Admin Account"); ?></h1>
				<p>
					<label for="login"><?php echo __("Username"); ?></label>
					<input type="text" name="login" value="<?php value_fallback("login", "Admin"); ?>" id="login" />
					<label for="password_1"><?php echo __("Password"); ?></label>
					<input type="password" name="password_1" value="<?php value_fallback("password_1"); ?>" id="password_1" />
					<label for="password_2"><?php echo __("Password"); ?> <span class="sub"><?php echo __("(again)"); ?></span></label>
					<input type="password" name="password_2" value="<?php value_fallback("password_2"); ?>" id="password_2" />
					<label for="email"><?php echo __("E-Mail Address"); ?></label>
					<input type="text" name="email" value="<?php value_fallback("email"); ?>" id="email" />
				</p>

				<p class="center"><input type="submit" value="<?php echo __("Install!"); ?>"></p>
			</form>
<?php else: ?>
			<h1><?php echo __("Done!"); ?></h1>
			<p>
				<?php echo __("Chyrp has been successfully installed."); ?>
			</p>
			<h2>So, what now?</h2>
			<ol>
				<li><?php echo __("<strong>Delete install.php</strong>, you won't need it anymore."); ?></li>
				<li><a href="http://chyrp.net/extend/browse/translations"><?php echo __("Look for a translation for your language."); ?></a></li>
				<li><a href="http://chyrp.net/extend/browse/modules"><?php echo __("Install some Modules."); ?></a></li>
				<li><a href="http://chyrp.net/extend/browse/feathers"><?php echo __("Find some Feathers you want."); ?></a></li>
				<li><a href="getting_started.html"><?php echo __("Read &#8220;Getting Started&#8221;"); ?></a></li>
			</ol>
			<a class="done" href="<?php echo $config->url; ?>"><?php echo __("Take me to my site! &rarr;"); ?></a>
<?php
	endif;
?>
		</div>
	</body>
</html>
