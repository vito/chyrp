<?php
	require_once "./includes/common.php";
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
			p {
				margin-bottom: 1em;
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
				border-bottom: 1px dotted #ddd;
				margin-bottom: 2px;
			}
			select {
				margin-bottom: 1em;
			}
			.footer {
				color: #777;
				margin-top: 1em;
				font-size: .9em;
				text-align: center;
			}
			.error {
				color: #F22;
				font-size: 12px;
			}
			a:link, a:visited {
				color: #6B0;
			}
			a:hover {
				text-decoration: underline;
			}
			a.big {
				background: #eee;
				margin-top: 2em;
				display: block;
				padding: .75em 1em;
				color: #777;
				text-shadow: #fff 1px 1px 0;
				font: 1em normal "Lucida Grande", "Verdana", Helvetica, Arial, sans-serif;
				text-decoration: none;
				-webkit-border-radius: .5em;
				-moz-border-radius: .5em;
			}
			a.big:hover {
				background: #f5f5f5;
			}
			a.big:active {
				background: #e0e0e0;
			}
			strong {
				font-weight: normal;
				color: #f00;
			}
			ol {
				margin: 0 0 2em 2em;
			}
			p, li {
				margin-bottom: 1em;
			}
			.center {
				text-align: center;
			}
		</style>
	</head>
	<body>
		<div class="window">
<?php
	$current_version = 2000;

	function to_1130() {
		global $config;
		$config->set("secure_hashkey", md5(random(32, true)));
	}
	function to_2000() {
		global $config, $sql, $misc;
		$sql->adapter = null;
		$config->set("enable_xmlrpc", true);
		$config->set("uploads_path", "/uploads/");
		$config->set("chyrp_url", $config->url);
		$config->set("feed_items", $config->rss_posts);
		$config->remove("rss_posts");
		$sql->set("adapter", "mysql");
		if (empty($sql->prefix)) { # For some reason the prefix was being removed in my remote testing.
			$sql->prefix = "blah";
			$sql->set("prefix", "");
			$sql->prefix = "";
		}

		if (!@rename(MAIN_DIR."/upload", MAIN_DIR."/uploads"))
			echo "<p>".__("Uploads directory could not be renamed. Please rename it to <code>/uploads</code>")."</p>";

		# Replace all the posts' XML with SimpleXML well-formed XML.
		$get_posts = $sql->query("SELECT * FROM `".$sql->prefix."posts`");
		while ($post = $sql->fetch_object($get_posts)) {
			$xml = simplexml_load_string($post->xml, "SimpleXMLElement", LIBXML_NOCDATA);

			foreach ($xml as $key => $val)
			    $xml->$key = make_xml_safe(trim($val));

			$sql->query("UPDATE `".$sql->prefix."posts` SET `xml` = '".$misc->fix($xml->asXML())."' WHERE `id` = '".$misc->fix($post->id)."'");
		}

		$groups = array();
		# Upgrade the Groups/Permissions stuff
		$get_groups = $sql->query("SELECT * FROM `".$sql->prefix."groups`");
		while ($group = $sql->fetch_object($get_groups)) {
			$groups[$group->name] = array();
			foreach ($group as $key => $val)
				if ($key != "name" and $val)
					$groups[$group->name][] = $key;
		}
		foreach ($groups as $key => &$val)
			$val = Horde_Yaml::dump($val);

		$sql->query("DROP TABLE IF EXISTS `".$sql->prefix."groups`");

		# Groups table
		$sql->query("CREATE TABLE IF NOT EXISTS `".$sql->prefix."groups` (
		                 `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
		                 `name` VARCHAR(100) DEFAULT '',
	                     `permissions` LONGTEXT DEFAULT '',
		                 UNIQUE (`name`)
		             ) DEFAULT CHARSET=utf8");

		# Permissions table
		$sql->query("CREATE TABLE IF NOT EXISTS `".$sql->prefix."permissions` (
		                 `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
		                 `name` VARCHAR(100) DEFAULT '',
		                 UNIQUE (`name`)
		             ) DEFAULT CHARSET=utf8");

		# Sessions table
		$sql->query("CREATE TABLE IF NOT EXISTS `".$sql->prefix."sessions` (
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
			$sql->query("INSERT INTO `".$sql->prefix."permissions` SET `name` = '".$permission."'");

		foreach($groups as $name => $permissions)
			$sql->query("INSERT INTO `".$sql->prefix."groups` SET `name` = '".$misc->fix(ucfirst($name))."', `permissions` = '".$misc->fix($permissions)."'");

		if (file_exists(INCLUDES_DIR."/config.yml.php") and file_exists(INCLUDES_DIR."/database.yml.php")) {
			foreach (array("database.yml.php" => "database.yaml.php",
			               "config.yml.php"   => "config.yaml.php") as $from => $to) {
				$file = @file_get_contents(INCLUDES_DIR."/".$from);
				$nerr = str_replace("<?php\n\tif (strpos(\$_SERVER['REQUEST_URI'], \"".$from."\"))\n\t\texit(\"Gtfo.\");\n?>",
				                    "<?php header(\"Status: 403\"); exit(\"Access denied.\"); ?>",
				                    $file);
				if (!@file_put_contents(INCLUDES_DIR."/".$from, $nerr))
					echo "<p>".sprintf(__("Config file's error message could not be updated. Please replace the <code>&lt;?php &hellip; ?&gt;</code> block in ".$to." with: %s"),
					                   "<code>&lt;?php header(\"Status: 403\"); exit(\"Access denied.\"); ?&gt;</code>")."</p>";
			}

			if (!@rename(INCLUDES_DIR."/config.yml.php", INCLUDES_DIR."/config.yaml.php"))
				echo "<p>".__("Config file could not be renamed. Please rename it to <code>/includes/config.yaml.php</code>")."</p>";

			if (!@rename(INCLUDES_DIR."/database.yml.php", INCLUDES_DIR."/database.yaml.php"))
				echo "<p>".__("Database config file could not be renamed. Please rename it to <code>/includes/database.yaml.php</code>")."</p>";
		}
	}
	if (!function_exists("random")) { # Upgrade to 1.1.3.x
		function random($length, $specialchars = false) {
			$pattern = "1234567890abcdefghijklmnopqrstuvwxyz";

			if ($specialchars)
				$pattern.= "!@#$%^&*()?~";

			$len = ($specialchars) ? 47 : 35 ;

			$key = $pattern{rand(0, $len)};
			for($i = 1; $i < $length; $i++) {
				$key.= $pattern{rand(0, $len)};
			}
			return $key;
		}
	}

	$html_entities = array(
	    "&zwnj;" => "&#8204;",
	    "&aring;" => "&#229;",
	    "&gt;" => "&#62;",
	    "&yen;" => "&#165;",
	    "&ograve;" => "&#242;",
	    "&Chi;" => "&#935;",
	    "&delta;" => "&#948;",
	    "&rang;" => "&#9002;",
	    "&sup;" => "&#8835;",
	    "&trade;" => "&#8482;",
	    "&Ntilde;" => "&#209;",
	    "&xi;" => "&#958;",
	    "&upsih;" => "&#978;",
	    "&Yacute;" => "&#221;",
	    "&Atilde;" => "&#195;",
	    "&radic;" => "&#8730;",
	    "&otimes;" => "&#8855;",
	    "&aelig;" => "&#230;",
	    "&oelig;" => "&#339;",
	    "&equiv;" => "&#8801;",
	    "&ni;" => "&#8715;",
	    "&Psi;" => "&#936;",
	    "&auml;" => "&#228;",
	    "&Uuml;" => "&#220;",
	    "&Epsilon;" => "&#917;",
	    "&Yuml;" => "&#376;",
	    "&lt;" => "&#60;",
	    "&Icirc;" => "&#206;",
	    "&shy;" => "&#173;",
	    "&Upsilon;" => "&#933;",
	    "&Lambda;" => "&#923;",
	    "&yacute;" => "&#253;",
	    "&Prime;" => "&#8243;",
	    "&prime;" => "&#8242;",
	    "&psi;" => "&#968;",
	    "&Kappa;" => "&#922;",
	    "&rsaquo;" => "&#8250;",
	    "&Tau;" => "&#932;",
	    "&darr;" => "&#8595;",
	    "&ocirc;" => "&#244;",
	    "&lrm;" => "&#8206;",
	    "&zwj;" => "&#8205;",
	    "&cedil;" => "&#184;",
	    "&rlm;" => "&#8207;",
	    "&Alpha;" => "&#913;",
	    "&not;" => "&#172;",
	    "&amp;" => "&#38;",
	    "&AElig;" => "&#198;",
	    "&oslash;" => "&#248;",
	    "&acute;" => "&#180;",
	    "&lceil;" => "&#8968;",
	    "&iquest;" => "&#191;",
	    "&uacute;" => "&#250;",
	    "&laquo;" => "&#171;",
	    "&dArr;" => "&#8659;",
	    "&rdquo;" => "&#8221;",
	    "&ge;" => "&#8805;",
	    "&Igrave;" => "&#204;",
	    "&nu;" => "&#957;",
	    "&ccedil;" => "&#231;",
	    "&lsaquo;" => "&#8249;",
	    "&sube;" => "&#8838;",
	    "&rarr;" => "&#8594;",
	    "&sdot;" => "&#8901;",
	    "&supe;" => "&#8839;",
	    "&nbsp;" => "&#160;",
	    "&lfloor;" => "&#8970;",
	    "&lArr;" => "&#8656;",
	    "&Auml;" => "&#196;",
	    "&asymp;" => "&#8776;",
	    "&Otilde;" => "&#213;",
	    "&szlig;" => "&#223;",
	    "&clubs;" => "&#9827;",
	    "&agrave;" => "&#224;",
	    "&Ocirc;" => "&#212;",
	    "&ndash;" => "&#8211;",
	    "&Theta;" => "&#920;",
	    "&Pi;" => "&#928;",
	    "&OElig;" => "&#338;",
	    "&Scaron;" => "&#352;",
	    "&frac14;" => "&#188;",
	    "&egrave;" => "&#232;",
	    "&sub;" => "&#8834;",
	    "&iexcl;" => "&#161;",
	    "&frac12;" => "&#189;",
	    "&ordf;" => "&#170;",
	    "&sum;" => "&#8721;",
	    "&prop;" => "&#8733;",
	    "&circ;" => "&#710;",
	    "&ntilde;" => "&#241;",
	    "&atilde;" => "&#227;",
	    "&theta;" => "&#952;",
	    "&prod;" => "&#8719;",
	    "&nsub;" => "&#8836;",
	    "&hArr;" => "&#8660;",
	    "&rArr;" => "&#8658;",
	    "&Oslash;" => "&#216;",
	    "&emsp;" => "&#8195;",
	    "&THORN;" => "&#222;",
	    "&infin;" => "&#8734;",
	    "&yuml;" => "&#255;",
	    "&Mu;" => "&#924;",
	    "&le;" => "&#8804;",
	    "&Eacute;" => "&#201;",
	    "&thinsp;" => "&#8201;",
	    "&ecirc;" => "&#234;",
	    "&bdquo;" => "&#8222;",
	    "&Sigma;" => "&#931;",
	    "&fnof;" => "&#402;",
	    "&kappa;" => "&#954;",
	    "&Aring;" => "&#197;",
	    "&tilde;" => "&#732;",
	    "&cup;" => "&#8746;",
	    "&mdash;" => "&#8212;",
	    "&uarr;" => "&#8593;",
	    "&permil;" => "&#8240;",
	    "&tau;" => "&#964;",
	    "&Ugrave;" => "&#217;",
	    "&eta;" => "&#951;",
	    "&Agrave;" => "&#192;",
	    "&sup1;" => "&#185;",
	    "&forall;" => "&#8704;",
	    "&eth;" => "&#240;",
	    "&rceil;" => "&#8969;",
	    "&iuml;" => "&#239;",
	    "&gamma;" => "&#947;",
	    "&lambda;" => "&#955;",
	    "&harr;" => "&#8596;",
	    "&reg;" => "&#174;",
	    "&Egrave;" => "&#200;",
	    "&sup3;" => "&#179;",
	    "&dagger;" => "&#8224;",
	    "&divide;" => "&#247;",
	    "&Ouml;" => "&#214;",
	    "&image;" => "&#8465;",
	    "&alefsym;" => "&#8501;",
	    "&igrave;" => "&#236;",
	    "&otilde;" => "&#245;",
	    "&pound;" => "&#163;",
	    "&eacute;" => "&#233;",
	    "&frasl;" => "&#8260;",
	    "&ETH;" => "&#208;",
	    "&lowast;" => "&#8727;",
	    "&Nu;" => "&#925;",
	    "&plusmn;" => "&#177;",
	    "&chi;" => "&#967;",
	    "&sup2;" => "&#178;",
	    "&frac34;" => "&#190;",
	    "&Aacute;" => "&#193;",
	    "&cent;" => "&#162;",
	    "&oline;" => "&#8254;",
	    "&Beta;" => "&#914;",
	    "&perp;" => "&#8869;",
	    "&Delta;" => "&#916;",
	    "&loz;" => "&#9674;",
	    "&pi;" => "&#960;",
	    "&iota;" => "&#953;",
	    "&empty;" => "&#8709;",
	    "&euml;" => "&#235;",
	    "&brvbar;" => "&#166;",
	    "&iacute;" => "&#237;",
	    "&para;" => "&#182;",
	    "&ordm;" => "&#186;",
	    "&ensp;" => "&#8194;",
	    "&uuml;" => "&#252;",
	    "&there4;" => "&#8756;",
	    "&part;" => "&#8706;",
	    "&icirc;" => "&#238;",
	    "&bull;" => "&#8226;",
	    "&omicron;" => "&#959;",
	    "&upsilon;" => "&#965;",
	    "&copy;" => "&#169;",
	    "&Iuml;" => "&#207;",
	    "&Oacute;" => "&#211;",
	    "&Xi;" => "&#926;",
	    "&Dagger;" => "&#8225;",
	    "&Ograve;" => "&#210;",
	    "&Ucirc;" => "&#219;",
	    "&cap;" => "&#8745;",
	    "&mu;" => "&#956;",
	    "&sigmaf;" => "&#962;",
	    "&scaron;" => "&#353;",
	    "&lsquo;" => "&#8216;",
	    "&isin;" => "&#8712;",
	    "&Zeta;" => "&#918;",
	    "&minus;" => "&#8722;",
	    "&deg;" => "&#176;",
	    "&and;" => "&#8743;",
	    "&real;" => "&#8476;",
	    "&ang;" => "&#8736;",
	    "&hellip;" => "&#8230;",
	    "&curren;" => "&#164;",
	    "&int;" => "&#8747;",
	    "&ucirc;" => "&#251;",
	    "&rfloor;" => "&#8971;",
	    "&crarr;" => "&#8629;",
	    "&ugrave;" => "&#249;",
	    "&notin;" => "&#8713;",
	    "&exist;" => "&#8707;",
	    "&cong;" => "&#8773;",
	    "&oplus;" => "&#8853;",
	    "&times;" => "&#215;",
	    "&Acirc;" => "&#194;",
	    "&piv;" => "&#982;",
	    "&Euml;" => "&#203;",
	    "&Phi;" => "&#934;",
	    "&Iacute;" => "&#205;",
	    "&quot;" => "&#34;",
	    "&Uacute;" => "&#218;",
	    "&Omicron;" => "&#927;",
	    "&ne;" => "&#8800;",
	    "&Iota;" => "&#921;",
	    "&nabla;" => "&#8711;",
	    "&sbquo;" => "&#8218;",
	    "&Rho;" => "&#929;",
	    "&epsilon;" => "&#949;",
	    "&Ecirc;" => "&#202;",
	    "&zeta;" => "&#950;",
	    "&Omega;" => "&#937;",
	    "&acirc;" => "&#226;",
	    "&sim;" => "&#8764;",
	    "&phi;" => "&#966;",
	    "&diams;" => "&#9830;",
	    "&macr;" => "&#175;",
	    "&larr;" => "&#8592;",
	    "&Ccedil;" => "&#199;",
	    "&aacute;" => "&#225;",
	    "&uArr;" => "&#8657;",
	    "&beta;" => "&#946;",
	    "&Eta;" => "&#919;",
	    "&weierp;" => "&#8472;",
	    "&rho;" => "&#961;",
	    "&micro;" => "&#181;",
	    "&alpha;" => "&#945;",
	    "&omega;" => "&#969;",
	    "&middot;" => "&#183;",
	    "&Gamma;" => "&#915;",
	    "&euro;" => "&#8364;",
	    "&lang;" => "&#9001;",
	    "&spades;" => "&#9824;",
	    "&rsquo;" => "&#8217;",
	    "&uml;" => "&#168;",
	    "&thorn;" => "&#254;",
	    "&ouml;" => "&#246;",
	    "&thetasym;" => "&#977;",
	    "&or;" => "&#8744;",
	    "&raquo;" => "&#187;",
	    "&sect;" => "&#167;",
	    "&ldquo;" => "&#8220;",
	    "&hearts;" => "&#9829;",
	    "&sigma;" => "&#963;",
	    "&oacute;" => "&#243;"
	);

	if (!function_exists("name2codepoint")) { # Upgrade to 2.0
		function name2codepoint($string) {
			global $html_entities;
			return str_replace(array_keys($html_entities), array_values($html_entities), $string);
		}
	}

	function make_xml_safe($text) { # Upgrade to 2.0
		return name2codepoint(htmlentities($text, ENT_NOQUOTES, "utf-8", false));
	}

	if (!empty($_POST)) {
		for ($i = (int) $_POST['version']; $i <= $current_version; $i++)
			if (is_callable("to_".($i + 1)))
				call_user_func("to_".($i + 1));
?>
			<h1><?php echo __("All done!"); ?></h1>
			<p><?php echo __("Next, back up your current installation and replace the old files with the new. Be careful of what you're overwriting &ndash; some systems will remove directories and upload the new ones, instead of merging them. <strong>Make sure you don't replace or remove your <code>/includes/database.yaml.php</code> and <code>/includes/config.yaml.php</code> files.</strong>"); ?></p>
			<p><?php echo __("Finished?"); ?></p>
			<p><?php echo __("Yay!"); ?></p>
			<br />
			<h1><?php echo __("Tips"); ?></h1>
			<ol>
				<li><?php echo __("Chyrp 2.0 uses Timezones instead of the previous method of using your offset from the server's time. <strong>Be sure to set yours under Settings > General.</strong>"); ?></li>
				<li><?php echo __("There are many new and extended Group permissions with this release. If you are the administrator, you'll probably want to enable them for your group, under Manage > Groups."); ?></li>
				<li><?php echo __("If the admin section looks ugly to you, refresh your browser or clear your cache. The admin area is completely redesigned in 2.0."); ?></li>
			</ol>
			<a class="big" href="<?php echo $config->url; ?>"><?php echo __("Take me to my site! &rarr;"); ?></a>
<?php
	} else {
?>
			<h1><?php echo __("Upgrade"); ?></h1>
			<form action="upgrade.php" method="post" accept-charset="utf-8">
				<p><?php echo __("Before upgrading, please disable all modules and feathers that don't come with Chyrp (you can leave the Text feather enabled)."); ?></p>
				<p><?php echo __("You may also want to create an index.html file alongside your index.php to serve as a placeholder during the upgrade."); ?></p>
				<p><?php echo __("If your version number is not listed, you do not have to upgrade."); ?></p>
				<label for="version"><?php echo __("What are you upgrading from?"); ?></label>
				<select name="version">
					<option value="1130">1.1.3.x</option>
					<option value="1100">1.1.x</option>
				</select>
				<p class="center"><input type="submit" value="<?php echo __("Upgrade &rarr;"); ?>"></p>
			</form>
<?php
	}
?>
		</div>
	</body>
</html>
