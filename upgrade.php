<?php
	define('MAIN_DIR', dirname(__FILE__));
	define('INCLUDES_DIR', dirname(__FILE__)."/includes");

	define('YAML_LIB', (file_exists(INCLUDES_DIR."/lib/Yaml.php")) ?
	                   INCLUDES_DIR."/lib/Yaml.php" :
	                   INCLUDES_DIR."/lib/spyc.php");
	define('YAML_CLASS', (file_exists(INCLUDES_DIR."/lib/Yaml.php")) ? "Horde_Yaml" : "Spyc");
	define('YAML_LOAD', (YAML_CLASS == "Horde_Yaml") ? "load" : "YAMLLoad");
	define('YAML_DUMP', (YAML_CLASS == "Horde_Yaml") ? "dump" : "YAMLDump");

	define('CONFIG_FILE', (file_exists(INCLUDES_DIR."/config.yaml.php")) ?
	                      INCLUDES_DIR."/config.yaml.php" :
	                      INCLUDES_DIR."/config.yml.php");
	define('DATABASE_FILE', (file_exists(INCLUDES_DIR."/database.yaml.php")) ?
	                        INCLUDES_DIR."/database.yaml.php" :
	                        INCLUDES_DIR."/database.yml.php");

	require YAML_LIB;
	require_once INCLUDES_DIR."/lib/gettext/gettext.php";
	require_once INCLUDES_DIR."/lib/gettext/streams.php";

	function get_db($setting) {
		$config = file_get_contents(DATABASE_FILE);
		$config = preg_replace("/<\?php (.+) \?>\n?/", "", $config);

		$yaml = call_user_func(array(YAML_CLASS, YAML_LOAD), $config);

		return (isset($yaml[$setting])) ? $yaml[$setting] : false ;
	}

	function set_db($setting, $value, $message = null) {
		if (get_db($setting) == $value) return;

		if (!isset($message))
			$message = _f("Setting %s database setting to %s...", array($setting, print_r($value, true)));

		$config = file_get_contents(DATABASE_FILE);
		$config = preg_replace("/<\?php (.+) \?>\n?/", "", $config);

		$yaml = call_user_func(array(YAML_CLASS, YAML_LOAD), $config);

		$yaml[$setting] = $value;

		$protection = "<?php header(\"Status: 403\"); exit(\"Access denied.\"); ?>\n";

		$dump = $protection.call_user_func(array(YAML_CLASS, YAML_DUMP), $yaml);

		echo $message.test(@file_put_contents(DATABASE_FILE, $dump));
	}

	/**
	 * Function: load_translator
	 * Loads a .mo file for gettext translation.
	 *
	 * Parameters:
	 *     $domain - The name for this translation domain.
	 *     $mofile - The .mo file to read from.
	 */
	function load_translator($domain, $mofile) {
		global $l10n;

		if (isset($l10n[$domain]))
			return;

		if (is_readable($mofile))
			$input = new CachedFileReader($mofile);
		else
			return;

		$l10n[$domain] = new gettext_reader($input);
	}

	/**
	 * Function: __
	 * Returns a translated string.
	 *
	 * Parameters:
	 *     $text - The string to translate.
	 *     $domain - The translation domain to read from.
	 */
	function __($text, $domain = "chyrp") {
		global $l10n;
		return (isset($l10n[$domain])) ? $l10n[$domain]->translate($text) : $text ;
	}

	/**
	 * Function: _p
	 * Returns a plural (or not) form of a translated string.
	 *
	 * Parameters:
	 *     $single - Singular string.
	 *     $plural - Pluralized string.
	 *     $number - The number to judge by.
	 *     $domain - The translation domain to read from.
	 */
	function _p($single, $plural, $number, $domain = "chyrp") {
		global $l10n;
		return (isset($l10n[$domain])) ? $l10n[$domain]->ngettext($single, $plural, $number) : (($number != 1) ? $plural : $single) ;
	}

	/**
	 * Function: _f
	 * Returns a formatted translated string.
	 */
	function _f($string, $args = array(), $domain = "chyrp") {
		array_unshift($args, __($string, $domain));
		return call_user_func_array("sprintf", $args);
	}

	define('SQL_ADAPTER', (!get_db("adapter")) ? "mysql" : get_db("adapter"));

	if (SQL_ADAPTER == "mysql") {
		$sql = mysql_connect(get_db("host"), get_db("username"), get_db("password"));
		mysql_select_db(get_db("database"), $sql);
	} else
		$sql = new SQLiteDatabase(get_db("database"));

	function test($try) {
		if ($try)
			return " ".__("success!")."\n";
		else
			return " ".__("failed!")."\n";
	}

	function get_config($setting) {
		$config = file_get_contents(CONFIG_FILE);
		$config = preg_replace("/<\?php (.+) \?>\n?/", "", $config);

		$yaml = call_user_func(array(YAML_CLASS, YAML_LOAD), $config);

		return (isset($yaml[$setting])) ? $yaml[$setting] : false ;
	}

	load_translator("chyrp", INCLUDES_DIR."/locale/".get_config("locale").".mo");

	function set_config($setting, $value, $message = null) {
		if (get_config($setting) == $value) return;

		if (!isset($message))
			$message = _f("Setting %s setting to %s...", array($setting, print_r($value, true)));

		$config = file_get_contents(CONFIG_FILE);
		$config = preg_replace("/<\?php (.+) \?>\n?/", "", $config);

		$yaml = call_user_func(array(YAML_CLASS, YAML_LOAD), $config);

		$yaml[$setting] = $value;

		$protection = "<?php header(\"Status: 403\"); exit(\"Access denied.\"); ?>\n";

		$dump = $protection.call_user_func(array(YAML_CLASS, YAML_DUMP), $yaml);

		echo $message.test(@file_put_contents(CONFIG_FILE, $dump));
	}

	function add_config_if_not_exists($setting, $value) {
		if (!get_config($setting))
			echo set_config($setting, $value, _f("Adding %s setting...", array($setting)));
	}

	function remove_config($setting) {
		if (!get_config($setting)) return;

		$config = file_get_contents(CONFIG_FILE);
		$config = preg_replace("/<\?php (.+) \?>\n?/", "", $config);

		$yaml = call_user_func(array(YAML_CLASS, YAML_LOAD), $config);

		unset($yaml[$setting]);

		$protection = "<?php header(\"Status: 403\"); exit(\"Access denied.\"); ?>\n";

		$dump = $protection.call_user_func(array(YAML_CLASS, YAML_DUMP), $yaml);

		echo _f("Removing %s setting...", array($setting)).test(@file_put_contents(CONFIG_FILE, $dump));
	}

	function fix($string) {
		if (SQL_ADAPTER == "mysql")
			return mysql_real_escape_string($string);
		else
			return sqlite_escape_string($string);
	}

	function query($query) {
		global $sql;
		$query = str_replace("__", get_db("prefix"), $query);
		$query = str_replace("`", "", $query);
		return (SQL_ADAPTER == "mysql") ? mysql_query($query, $sql) : $sql->query($query) ;
	}

	function fetch_object($query) {
		if (SQL_ADAPTER == "mysql")
			return mysql_fetch_object($query);
		else
			return $query->fetchObject();
	}

	# HOLY WALL OF TEXT
	$html_entities = array("&zwnj;" => "&#8204;", "&aring;" => "&#229;", "&gt;" => "&#62;", "&yen;" => "&#165;", "&ograve;" => "&#242;", "&Chi;" => "&#935;", "&delta;" => "&#948;", "&rang;" => "&#9002;", "&sup;" => "&#8835;", "&trade;" => "&#8482;", "&Ntilde;" => "&#209;", "&xi;" => "&#958;", "&upsih;" => "&#978;", "&Yacute;" => "&#221;", "&Atilde;" => "&#195;", "&radic;" => "&#8730;", "&otimes;" => "&#8855;", "&aelig;" => "&#230;", "&oelig;" => "&#339;", "&equiv;" => "&#8801;", "&ni;" => "&#8715;", "&Psi;" => "&#936;", "&auml;" => "&#228;", "&Uuml;" => "&#220;", "&Epsilon;" => "&#917;", "&Yuml;" => "&#376;", "&lt;" => "&#60;", "&Icirc;" => "&#206;", "&shy;" => "&#173;", "&Upsilon;" => "&#933;", "&Lambda;" => "&#923;", "&yacute;" => "&#253;", "&Prime;" => "&#8243;", "&prime;" => "&#8242;", "&psi;" => "&#968;", "&Kappa;" => "&#922;", "&rsaquo;" => "&#8250;", "&Tau;" => "&#932;", "&darr;" => "&#8595;", "&ocirc;" => "&#244;", "&lrm;" => "&#8206;", "&zwj;" => "&#8205;", "&cedil;" => "&#184;", "&rlm;" => "&#8207;", "&Alpha;" => "&#913;", "&not;" => "&#172;", "&amp;" => "&#38;", "&AElig;" => "&#198;", "&oslash;" => "&#248;", "&acute;" => "&#180;", "&lceil;" => "&#8968;", "&iquest;" => "&#191;", "&uacute;" => "&#250;", "&laquo;" => "&#171;", "&dArr;" => "&#8659;", "&rdquo;" => "&#8221;", "&ge;" => "&#8805;", "&Igrave;" => "&#204;", "&nu;" => "&#957;", "&ccedil;" => "&#231;", "&lsaquo;" => "&#8249;", "&sube;" => "&#8838;", "&rarr;" => "&#8594;", "&sdot;" => "&#8901;", "&supe;" => "&#8839;", "&nbsp;" => "&#160;", "&lfloor;" => "&#8970;", "&lArr;" => "&#8656;", "&Auml;" => "&#196;", "&asymp;" => "&#8776;", "&Otilde;" => "&#213;", "&szlig;" => "&#223;", "&clubs;" => "&#9827;", "&agrave;" => "&#224;", "&Ocirc;" => "&#212;", "&ndash;" => "&#8211;", "&Theta;" => "&#920;", "&Pi;" => "&#928;", "&OElig;" => "&#338;", "&Scaron;" => "&#352;", "&frac14;" => "&#188;", "&egrave;" => "&#232;", "&sub;" => "&#8834;", "&iexcl;" => "&#161;", "&frac12;" => "&#189;", "&ordf;" => "&#170;", "&sum;" => "&#8721;", "&prop;" => "&#8733;", "&circ;" => "&#710;", "&ntilde;" => "&#241;", "&atilde;" => "&#227;", "&theta;" => "&#952;", "&prod;" => "&#8719;", "&nsub;" => "&#8836;", "&hArr;" => "&#8660;", "&rArr;" => "&#8658;", "&Oslash;" => "&#216;", "&emsp;" => "&#8195;", "&THORN;" => "&#222;", "&infin;" => "&#8734;", "&yuml;" => "&#255;", "&Mu;" => "&#924;", "&le;" => "&#8804;", "&Eacute;" => "&#201;", "&thinsp;" => "&#8201;", "&ecirc;" => "&#234;", "&bdquo;" => "&#8222;", "&Sigma;" => "&#931;", "&fnof;" => "&#402;", "&kappa;" => "&#954;", "&Aring;" => "&#197;", "&tilde;" => "&#732;", "&cup;" => "&#8746;", "&mdash;" => "&#8212;", "&uarr;" => "&#8593;", "&permil;" => "&#8240;", "&tau;" => "&#964;", "&Ugrave;" => "&#217;", "&eta;" => "&#951;", "&Agrave;" => "&#192;", "&sup1;" => "&#185;", "&forall;" => "&#8704;", "&eth;" => "&#240;", "&rceil;" => "&#8969;", "&iuml;" => "&#239;", "&gamma;" => "&#947;", "&lambda;" => "&#955;", "&harr;" => "&#8596;", "&reg;" => "&#174;", "&Egrave;" => "&#200;", "&sup3;" => "&#179;", "&dagger;" => "&#8224;", "&divide;" => "&#247;", "&Ouml;" => "&#214;", "&image;" => "&#8465;", "&alefsym;" => "&#8501;", "&igrave;" => "&#236;", "&otilde;" => "&#245;", "&pound;" => "&#163;", "&eacute;" => "&#233;", "&frasl;" => "&#8260;", "&ETH;" => "&#208;", "&lowast;" => "&#8727;", "&Nu;" => "&#925;", "&plusmn;" => "&#177;", "&chi;" => "&#967;", "&sup2;" => "&#178;", "&frac34;" => "&#190;", "&Aacute;" => "&#193;", "&cent;" => "&#162;", "&oline;" => "&#8254;", "&Beta;" => "&#914;", "&perp;" => "&#8869;", "&Delta;" => "&#916;", "&loz;" => "&#9674;", "&pi;" => "&#960;", "&iota;" => "&#953;", "&empty;" => "&#8709;", "&euml;" => "&#235;", "&brvbar;" => "&#166;", "&iacute;" => "&#237;", "&para;" => "&#182;", "&ordm;" => "&#186;", "&ensp;" => "&#8194;", "&uuml;" => "&#252;", "&there4;" => "&#8756;", "&part;" => "&#8706;", "&icirc;" => "&#238;", "&bull;" => "&#8226;", "&omicron;" => "&#959;", "&upsilon;" => "&#965;", "&copy;" => "&#169;", "&Iuml;" => "&#207;", "&Oacute;" => "&#211;", "&Xi;" => "&#926;", "&Dagger;" => "&#8225;", "&Ograve;" => "&#210;", "&Ucirc;" => "&#219;", "&cap;" => "&#8745;", "&mu;" => "&#956;", "&sigmaf;" => "&#962;", "&scaron;" => "&#353;", "&lsquo;" => "&#8216;", "&isin;" => "&#8712;", "&Zeta;" => "&#918;", "&minus;" => "&#8722;", "&deg;" => "&#176;", "&and;" => "&#8743;", "&real;" => "&#8476;", "&ang;" => "&#8736;", "&hellip;" => "&#8230;", "&curren;" => "&#164;", "&int;" => "&#8747;", "&ucirc;" => "&#251;", "&rfloor;" => "&#8971;", "&crarr;" => "&#8629;", "&ugrave;" => "&#249;", "&notin;" => "&#8713;", "&exist;" => "&#8707;", "&cong;" => "&#8773;", "&oplus;" => "&#8853;", "&times;" => "&#215;", "&Acirc;" => "&#194;", "&piv;" => "&#982;", "&Euml;" => "&#203;", "&Phi;" => "&#934;", "&Iacute;" => "&#205;", "&quot;" => "&#34;", "&Uacute;" => "&#218;", "&Omicron;" => "&#927;", "&ne;" => "&#8800;", "&Iota;" => "&#921;", "&nabla;" => "&#8711;", "&sbquo;" => "&#8218;", "&Rho;" => "&#929;", "&epsilon;" => "&#949;", "&Ecirc;" => "&#202;", "&zeta;" => "&#950;", "&Omega;" => "&#937;", "&acirc;" => "&#226;", "&sim;" => "&#8764;", "&phi;" => "&#966;", "&diams;" => "&#9830;", "&macr;" => "&#175;", "&larr;" => "&#8592;", "&Ccedil;" => "&#199;", "&aacute;" => "&#225;", "&uArr;" => "&#8657;", "&beta;" => "&#946;", "&Eta;" => "&#919;", "&weierp;" => "&#8472;", "&rho;" => "&#961;", "&micro;" => "&#181;", "&alpha;" => "&#945;", "&omega;" => "&#969;", "&middot;" => "&#183;", "&Gamma;" => "&#915;", "&euro;" => "&#8364;", "&lang;" => "&#9001;", "&spades;" => "&#9824;", "&rsquo;" => "&#8217;", "&uml;" => "&#168;", "&thorn;" => "&#254;", "&ouml;" => "&#246;", "&thetasym;" => "&#977;", "&or;" => "&#8744;", "&raquo;" => "&#187;", "&sect;" => "&#167;", "&ldquo;" => "&#8220;", "&hearts;" => "&#9829;", "&sigma;" => "&#963;", "&oacute;" => "&#243;");

	function name2codepoint($string) {
		global $html_entities;
		return str_replace(array_keys($html_entities), array_values($html_entities), $string);
	}

	function make_xml_safe(&$text) {
		$text = html_entity_decode($text, ENT_QUOTES, "utf-8");
		$text = name2codepoint(htmlentities($text, ENT_NOQUOTES, "utf-8"));
		return $text;
	}

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
			if (is_int($key) and empty($val)) {
				unset($data[$key]);
				continue;
			}

			if (is_array($val)) {
				$xml = $object->addChild($key);
				arr2xml($xml, $val);
			} else
				$object->addChild($key, trim(make_xml_safe($val)));
		}
	}


	#---------------------------------------------
	# Upgrading Actions
	#---------------------------------------------

	function move_yml_yaml() {
		if (file_exists(INCLUDES_DIR."/config.yml.php"))
			echo __("Moving /includes/config.yml.php to /includes/config.yaml.php...").
			     test(@rename(INCLUDES_DIR."/config.yml.php", INCLUDES_DIR."/config.yaml.php"));

		if (file_exists(INCLUDES_DIR."/database.yml.php"))
			echo __("Moving /includes/database.yml.php to /includes/database.yaml.php...").
			     test(@rename(INCLUDES_DIR."/database.yml.php", INCLUDES_DIR."/database.yaml.php"));
	}

	function update_protection() {
		foreach (array("database.yaml.php", "config.yaml.php") as $file) {
			if (substr_count(file_get_contents(INCLUDES_DIR."/".$file),
			                 "<?php header(\"Status: 403\"); exit(\"Access denied.\"); ?>"))
				continue;

			$contents = file_get_contents(INCLUDES_DIR."/".$file);
			$new_error = preg_replace("/<\?php (.+) \?>/",
			                     "<?php header(\"Status: 403\"); exit(\"Access denied.\"); ?>",
			                     $contents);

			echo _f("Updating protection code in %s...", array($file)).test(@file_put_contents(INCLUDES_DIR."/".$file, $new_error));
		}
	}

	function move_upload() {
		if (file_exists(MAIN_DIR."/upload") and !file_exists(MAIN_DIR."/uploads"))
			echo __("Renaming /upload directory to /uploads...").test(@rename(MAIN_DIR."/upload", MAIN_DIR."/uploads"));
	}

	function make_posts_safe() {
		# Replace all the posts' XML with SimpleXML well-formed XML.
		$get_posts = query("SELECT * FROM `__posts`");

		while ($post = fetch_object($get_posts)) {
			$xml = simplexml_load_string($post->xml, "SimpleXMLElement", LIBXML_NOCDATA);

			$parse = xml2arr($xml);
			array_walk_recursive($parse, "make_xml_safe");

			$new_xml = new SimpleXMLElement("<post></post>");
			arr2xml($new_xml, $parse);

			echo _f("Sanitizing XML data of post #%d...", array($post->id)).test(query("UPDATE `__posts` SET `xml` = '".fix($new_xml->asXML())."' WHERE `id` = '".fix($post->id)."'"));
		}
	}

	function update_groups() {
		if (!query("SELECT `view_site` FROM `__groups` LIMIT 1")) return;

		$groups = array();

		$get_groups = query("SELECT * FROM `__groups`");

		echo __("Backing up current groups table...").test($get_groups);
		if (!$get_groups) return;

		while ($group = fetch_object($get_groups)) {
			$groups[$group->name] = array();
			foreach ($group as $key => $val)
				if ($key != "name" and $val)
					$groups[$group->name][] = $key;
		}
		foreach ($groups as $key => &$val)
			$val = call_user_func(array(YAML_CLASS, YAML_DUMP), $val);

		$delete_groups = query("DROP TABLE IF EXISTS `__groups`");
		echo __("Deleting old groups table...").test($delete_groups);
		if (!$delete_groups) return;

		$groups_table = query("CREATE TABLE IF NOT EXISTS `__groups` (
		                           `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
		                           `name` VARCHAR(100) DEFAULT '',
	                               `permissions` LONGTEXT,
		                           UNIQUE (`name`)
		                       ) DEFAULT CHARSET=utf8");
		echo __("Creating new groups table...").test($groups_table);
		if (!$groups_table) return;

		$permissions_table = query("CREATE TABLE IF NOT EXISTS `__permissions` (
		                                `id` VARCHAR(100) DEFAULT '' PRIMARY KEY,
		                                `name` VARCHAR(100) DEFAULT ''
		                            ) DEFAULT CHARSET=utf8");
		echo __("Creating new permissions table...").test($permissions_table);
		if (!$permissions_table) return;

		$permissions = array("change_settings" => "Change Settings",
		                     "toggle_extensions" => "Toggle Extensions",
		                     "view_site" => "View Site",
		                     "view_private" => "View Private Posts",
		                     "view_draft" => "View Drafts",
		                     "view_own_draft" => "Edit Own Drafts",
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

		echo __("Inserting permissions...");
		foreach ($permissions as $id => $name)
			if (!query("INSERT INTO `__permissions` SET `id` = '".$id."', `name` = '".fix($name)."'"))
				exit(test(false));
		echo test(true);

		echo __("Restoring groups...");
		foreach($groups as $name => $permissions)
			if (!query("INSERT INTO `__groups` SET `name` = '".fix(ucfirst($name))."', `permissions` = '".fix($permissions)."'"))
				exit(test(false));
		echo test(true);
	}

	function create_sessions_table() {
		if (query("SELECT * FROM `__sessions`")) return;

		echo __("Creating sessions table...").test(query("CREATE TABLE `__sessions` (
		                                                  `id` VARCHAR(32) DEFAULT '',
		                                                  `data` LONGTEXT,
		                                                  `user_id` VARCHAR(16) DEFAULT '0',
		                                                  `created_at` DATETIME DEFAULT '0000-00-00 00:00:00',
		                                                  `updated_at` DATETIME DEFAULT '0000-00-00 00:00:00',
		                                                  PRIMARY KEY (`id`)
		                                              ) DEFAULT CHARSET=utf8") or die(mysql_error()));
	}

	function update_permissions_table() {
		$get_permissions = query("SELECT * FROM `__permissions`");
		while ($row = fetch_object($get_permissions))
			if (!is_numeric($row->id))
				return;

		echo __("Updating permissions table structure...").
		     test(query("ALTER TABLE `__permissions` CHANGE `id` `id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL"));

		$permissions = array("change_settings" => "Change Settings",
		                     "toggle_extensions" => "Toggle Extensions",
		                     "view_site" => "View Site",
		                     "view_private" => "View Private Posts",
		                     "view_draft" => "View Drafts",
		                     "view_own_draft" => "Edit Own Drafts",
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

		while ($permission = fetch_object($get_permissions))
			echo _f("Updating %s permission...", array($permission->name)).
			     test(query("UPDATE `__permissions` SET `id` = '".fix($permission->name)."', `name` = '".fix($permissions[$permission->name])."' WHERE `id` = ".$permission->id));

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
				margin: 1.25em 0 .5em;
				text-align: center;
			}
			h1.first {
				margin-top: .25em;
			}
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
			textarea {
				width: 97.75%;
				font-family: Monaco, monospace;
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
			ol {
				margin: 0 0 1em 2em;
			}
			p {
				margin-bottom: 1em;
			}
			.center {
				text-align: center;
			}
		</style>
	</head>
	<body>
		<div class="window">
<?php if (!empty($_POST)): ?>
			<textarea cols="30" rows="15"><?php
		move_yml_yaml();

		update_protection();

		set_config("theme", "stardust");

		add_config_if_not_exists("secure_hashkey", md5(random(32, true)));
		add_config_if_not_exists("enable_xmlrpc", true);
		add_config_if_not_exists("uploads_path", "/uploads/");
		add_config_if_not_exists("chyrp_url", get_config("url"));
		add_config_if_not_exists("feed_items", get_config("rss_posts"));

		remove_config("rss_posts");

		set_db("adapter", "mysql");

		move_upload();

		make_posts_safe();

		update_groups();

		create_sessions_table();

		# Needed from 2.0b3.1 -> 2.0rc1
		update_permissions_table();
?></textarea>
			<form action="index.php" method="get">
				<button type="submit" class="center"><?php echo __("Take me home."); ?></button>
			</form>
			<h1><?php echo __("Tips"); ?></h1>
			<ol>
				<li><?php echo __("<strong>Delete this file.</strong> You probably don't want other people running it."); ?></li>
				<li><?php echo __("If the admin area looks weird, try clearing your cache."); ?></li>
				<li><?php echo __("As of v2.0, Chyrp uses timezones to manage timestamps. Please set your install to the correct timezone at <a href=\"admin/index.php?action=general_settings\">General Settings</a>."); ?></li>
				<li><?php echo __("Make sure you check the group permissions &mdash; they might have changed, and you may need to update your group for you to perform some actions."); ?></li>
			</ol>
<?php else: ?>
			<h1 class="first"><?php echo __("Halt!"); ?></h1>
			<p><?php echo __("That button may look ready for a-clickin&rsquo;, but please take these preemptive measures before indulging:"); ?></p>
			<ol>
				<li><?php echo __("Disable any third-party Modules and Feathers."); ?></li>
				<li><?php echo __("Ensure that the Chyrp installation directory is writable by the server."); ?></li>
			</ol>
			<p><?php echo __("If any of the upgrade processes fail, it is safe to keep refreshing and trying again. If you cannot figure something out, please make a topic (with details!) at the <a href=\"http://chyrp.net/community/\">Chyrp Community</a>."); ?></p>
			<form action="upgrade.php" method="post">
				<button type="submit" class="center" name="upgrade"><?php echo __("Upgrade me!"); ?></button>
			</form>
<?php endif; ?>
		</div>
	</body>
</html>