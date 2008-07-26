<?php
	/**
	 * File: Helpers
	 * Various functions used throughout Chyrp's code.
	 */

	# Integer: $time_start
	# Times Chyrp.
	$time_start = 0;

	# Integer: $pluralizations
	# Holds predefined pluralizations, typically provided by modules/feathers.
	$pluralizations = array("feathers" => array());

	/**
	 * Function: session
	 * Begins Chyrp's custom session storage whatnots.
	 */
	function session() {
		session_set_save_handler(array("Session", "open"),
		                         array("Session", "close"),
		                         array("Session", "read"),
		                         array("Session", "write"),
		                         array("Session", "destroy"),
		                         array("Session", "gc"));
		session_set_cookie_params(60 * 60 * 24 * 30);
		session_name("ChyrpSession");
		session_start();
	}

	/**
	 * Function: error
	 * Shows an error message.
	 *
	 * Parameters:
	 *     $title - The title for the error dialog.
	 *     $body - The message for the error dialog.
	 */
	function error($title, $body) {
		# Clear all output sent before this error.
		if (($foo = ob_get_contents()) !== false) {
			ob_end_clean();

			# Since the header might already be set to gzip, start output buffering again.
			if (extension_loaded("zlib") and !ini_get("zlib.output_compression") and
				isset($_SERVER['HTTP_ACCEPT_ENCODING']) and
			    substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], "gzip")) {
				ob_start("ob_gzhandler");
				header("Content-Encoding: gzip");
			} else
				ob_start();
		} else {
			# If output buffering is not started, assume this
			# is sent from the Session class or somewhere deep.
			error_log($title.": ".$body);
			exit;
		}

		# Display the error.
		require (defined('THEME_DIR') and file_exists(THEME_DIR."/content/error.php")) ? THEME_DIR."/content/error.php" : INCLUDES_DIR."/error.php" ;

		if ($foo !== false)
			ob_end_flush();

		exit;
	}

	/**
	 * Function: show_403
	 * Shows an error message with a 403 status.
	 *
	 * Parameters:
	 *     $title - The title for the error dialog.
	 *     $body - The message for the error dialog.
	 */
	function show_403($title, $body) {
		header("Status: 403");
		error($title, $body);
	}

	/**
	 * Function: logged_in
	 * Returns whether or not they are logged in by returning the <Visitor.$id> (which defaults to 0).
	 */
	function logged_in() {
		return Visitor::current()->id != 0;
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

	/**
	 * Function: redirect
	 * Redirects to the given URL and exits immediately.
	 */
	function redirect($url, $use_chyrp_url = false) {
		# Handle URIs without domain
		if ($url[0] == "/")
			$url = (ADMIN or $use_chyrp_url) ?
			       Config::current()->chyrp_url.$url :
			       Config::current()->url.$url ;
		elseif (class_exists("Route") and !substr_count($url, "://"))
			$url = url($url);

		header("Location: ".html_entity_decode($url));
		exit;
	}

	/**
	 * Function: url
	 * Mask for Route->url().
	 */
	function url($url) {
		return Route::current()->url($url);
	}

	/**
	 * Function: pluralize
	 * Returns a pluralized string. This is a port of Rails's pluralizer.
	 *
	 * Parameters:
	 *     $string - The string to pluralize.
	 */
	function pluralize($string) {
		global $pluralizations;
		if (in_array($string, array_keys($pluralizations)))
			return $pluralizations[$string];
		else {
			$uncountable = array("moose", "sheep", "fish", "series", "species", "rice", "money", "information", "equipment", "piss");

			if (in_array($string, $uncountable))
				return $string;

			$replacements = array("/person/i" => "people",
			                      "/man/i" => "men",
			                      "/child/i" => "children",
			                      "/cow/i" => "kine",
			                      "/goose/i" => "geese",
			                      "/(penis)$/i" => "\\1es", # Take that, Rails!
			                      "/(ax|test)is$/i" => "\\1es",
			                      "/(octop|vir)us$/i" => "\\1ii",
			                      "/(cact)us$/i" => "\\1i",
			                      "/(alias|status)$/i" => "\\1es",
			                      "/(bu)s$/i" => "\\1ses",
			                      "/(buffal|tomat)o$/i" => "\\1oes",
			                      "/([ti])um$/i" => "\\1a",
			                      "/sis$/i" => "ses",
			                      "/(hive)$/i" => "\\1s",
			                      "/([^aeiouy]|qu)y$/i" => "\\1ies",
			                      "/^(ox)$/i" => "\\1en",
			                      "/(matr|vert|ind)(?:ix|ex)$/i" => "\\1ices",
			                      "/(x|ch|ss|sh)$/i" => "\\1es",
			                      "/([m|l])ouse$/i" => "\\1ice",
			                      "/(quiz)$/i" => "\\1zes");

			$replaced = preg_replace(array_keys($replacements), array_values($replacements), $string, 1);

			if ($replaced == $string)
				return $string."s";
			else
				return $replaced;
		}
	}

	/**
	 * Function: depluralize
	 * Returns a depluralized string. This is the inverse of <pluralize>.
	 *
	 * Parameters:
	 *     $string - The string to depluralize.
	 */
	function depluralize($string) {
		global $pluralizations;

		$copy = $pluralizations;
		unset($copy["feathers"]);
		$reversed = array_flip($copy);

		if (isset($reversed[$string]))
			return $reversed[$string];
		else {
			$replacements = array("/people/i" => "person",
			                      "/^men/i" => "man",
			                      "/children/i" => "child",
			                      "/kine/i" => "cow",
			                      "/geese/i" => "goose",
			                      "/(penis)es$/i" => "\\1",
			                      "/(ax|test)es$/i" => "\\1is",
			                      "/(octopi|viri|cact)i$/i" => "\\1us",
			                      "/(alias|status)es$/i" => "\\1",
			                      "/(bu)ses$/i" => "\\1s",
			                      "/(buffal|tomat)oes$/i" => "\\1o",
			                      "/([ti])a$/i" => "\\1um",
			                      "/ses$/i" => "sis",
			                      "/(hive)s$/i" => "\\1",
			                      "/([^aeiouy]|qu)ies$/i" => "\\1y",
			                      "/^(ox)en$/i" => "\\1",
			                      "/(vert|ind)ices$/i" => "\\1ex",
			                      "/(matr)ices$/i" => "\\1ix",
			                      "/(x|ch|ss|sh)es$/i" => "\\1",
			                      "/([m|l])ice$/i" => "\\1ouse",
			                      "/(quiz)zes$/i" => "\\1");

			$replaced = preg_replace(array_keys($replacements), array_values($replacements), $string, 1);

			if ($replaced == $string and substr($string, -1) == "s")
				return substr($string, 0, -1);
			else
				return $replaced;
		}
	}

	/**
	 * Function: truncate
	 * Truncates a string to the passed length, appending an ellipsis to the end.
	 *
	 * Parameters:
	 *     $text - String to shorten.
	 *     $numb - Length of the shortened string.
	 *     $keep_words - Whether or not to keep words in-tact.
	 *     $minimum - If the truncated string is less than this and $keep_words is true, it will act as if $keep_words is false.
	 */
	function truncate($text, $numb = 50, $keep_words = true, $minimum = 10) {
		# Entities only represent one character when rendered, so treat them as one character.
		preg_match_all("/&([^\s;]+);/", $text, $entities);
		foreach ($entities[0] as $entity)
			$numb += strlen($entity) - 1;

		$original = $text;
		$numb -= 3;
		if (strlen($text) > $numb) {
			if (function_exists('mb_strcut')) {
				if ($keep_words) {
					$text = mb_strcut($text, 0, $numb, "utf-8");
					$text = mb_strcut($text, 0 , strrpos($text, " "), "utf-8");

					if (strlen($text) < $minimum)
						$text = mb_strcut($original, 0, $numb, "utf-8");

					$text.= "...";
				} else {
					$text = mb_strcut($text, 0, $numb, "utf-8")."...";
				}
			} else {
				if ($keep_words) {
					$text = substr($text, 0, $numb);
					$text = substr($text, 0 , strrpos($text, " "));

					if (strlen($text) < $minimum)
						$text = substr($text, 0, $numb);

					$text.= "...";
				} else {
					$text = substr($text, 0, $numb)."...";
				}
			}
		}
		return $text;
	}

	/**
	 * Function: nobreak
	 * Returns a string with whitespace converted to non-breakable spaces.
	 */
	function nobreak($string) {
		return preg_replace("/\s+/", "&nbsp;", $string);
	}

	/**
	 * Function: when
	 * Returns date formatting for a string that isn't a regular time() value
	 *
	 * Parameters:
	 *     $formatting - The formatting for date().
	 *     $time - The string to convert to time (typically a datetime).
	 *     $strftime - Use `strftime` instead of `date`?
	 */
	function when($formatting, $when, $strftime = false) {
		$time = (is_numeric($when)) ? $when : strtotime($when) ;

		if ($strftime)
			return strftime($formatting, $time);
		else
			return date($formatting, $time);
	}

	/**
	 * Function: datetime
	 * Returns a standard datetime string based on either the passed timestamp or their time offset, usually for MySQL inserts.
	 *
	 * Parameters:
	 *     $when - An optional timestamp.
	 */
	function datetime($when = null) {
		fallback($when, time());

		$time = (is_numeric($when)) ? $when : strtotime($when) ;

		return date("Y-m-d H:i:s", $time);
	}

	/**
	 * Function: fix
	 * Returns a HTML-sanitized version of a string.
	 */
	function fix($string, $quotes = true, $decode_first = true) {
		$quotes = ($quotes) ? ENT_QUOTES : ENT_NOQUOTES ;

		if ($decode_first)
			$string = html_entity_decode($string, ENT_QUOTES, "utf-8");

		return htmlspecialchars($string, $quotes, "utf-8");
	}

	/**
	 * Function: safe
	 * Returns a HTML-sanitized version of a string.
	 */
	function safe($string) {
		$string = html_entity_decode($string, ENT_QUOTES, "utf-8");
		return name2codepoint(htmlentities($string, ENT_NOQUOTES, "utf-8"));
	}

	/**
	 * Function: unsafe
	 * Returns the reverse of safe().
	 */
	function unsafe($string) {
		return codepoint2name(html_entity_decode($string, ENT_QUOTES, "utf-8"));
	}

	/**
	 * Function: lang_code
	 * Returns the passed language code (e.g. en_US) to the human-readable text (e.g. English (US))
	 *
	 * Parameters:
	 *     $code - The language code to convert
	 *
	 * Credits:
	 *     This is from TextPattern, modified to match Chyrp's language code formatting.
	 */
	function lang_code($code) {
		$langs = array("ar_DZ" => "جزائري عربي",
		               "ca_ES" => "Català",
		               "cs_CZ" => "Čeština",
		               "da_DK" => "Dansk",
		               "de_DE" => "Deutsch",
		               "el_GR" => "Ελληνικά",
		               "en_GB" => "English (GB)",
		               "en_US" => "English (US)",
		               "es_ES" => "Español",
		               "et_EE" => "Eesti",
		               "fi_FI" => "Suomi",
		               "fr_FR" => "Français",
		               "gl_GZ" => "Galego (Galiza)",
		               "he_IL" => "עברית",
		               "hu_HU" => "Magyar",
		               "id_ID" => "Bahasa Indonesia",
		               "is_IS" => "Íslenska",
		               "it_IT" => "Italiano",
		               "ja_JP" => "日本語",
		               "lv_LV" => "Latviešu",
		               "nl_NL" => "Nederlands",
		               "no_NO" => "Norsk",
		               "pl_PL" => "Polski",
		               "pt_PT" => "Português",
		               "ro_RO" => "Română",
		               "ru_RU" => "Русский",
		               "sk_SK" => "Slovenčina",
		               "sv_SE" => "Svenska",
		               "th_TH" => "ไทย",
		               "uk_UA" => "Українська",
		               "vi_VN" => "Tiếng Việt",
		               "zh_CN" => "中文(简体)",
		               "zh_TW" => "中文(繁體)",
		               "bg_BG" => "Български");
		return (isset($langs[$code])) ? str_replace(array_keys($langs), array_values($langs), $code) : $code ;
	}

	/**
	 * Function: sanitize
	 * Returns a sanitized string, typically for URLs.
	 *
	 * Parameters:
	 *     $string - The string to sanitize.
	 *     $anal - If set to *true*, will remove all non-alphanumeric characters.
	 */
	function sanitize($string, $force_lowercase = true, $anal = false) {
		$strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]", "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;", "—", "–", ",", "<", ".", ">", "/", "?");
		$clean = trim(str_replace($strip, "", strip_tags($string)));
		$clean = remove_accents($clean);
		$clean = preg_replace('/\s+/', "-", $clean);
		$clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
		return ($force_lowercase) ?
			(function_exists('mb_strtolower')) ?
				mb_strtolower($clean, 'UTF-8') :
				strtolower($clean) :
			$clean;
	}

	/**
	 * Function: seems_utf8
	 * Determines whether a string seems to use UTF-8 characters.
	 *
	 * Credits:
	 *     This is from WordPress.
	 */
	function seems_utf8($Str) {
		for ($i=0; $i<strlen($Str); $i++) {
			if (ord($Str[$i]) < 0x80) continue; # 0bbbbbbb
			elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
			elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
			elseif ((ord($Str[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
			elseif ((ord($Str[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
			elseif ((ord($Str[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
			else return false; # Does not match any model
			for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
				if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80))
				return false;
			}
		}
		return true;
	}

	/**
	 * Function: remove_accents
	 * Removes accents from letters in a string.
	 *
	 * Credits:
	 *     This is from WordPress.
	 */
	function remove_accents($string) {
		if (!preg_match('/[\x80-\xff]/', $string))
			return $string;

		if (seems_utf8($string)) {
			$chars = array(
			// Decompositions for Latin-1 Supplement
			chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
			chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
			chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
			chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
			chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
			chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
			chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
			chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
			chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
			chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
			chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
			chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
			chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
			chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
			chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
			chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
			chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
			chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
			chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
			chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
			chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
			chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
			chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
			chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
			chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
			chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
			chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
			chr(195).chr(191) => 'y',
			// Decompositions for Latin Extended-A
			chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
			chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
			chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
			chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
			chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
			chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
			chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
			chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
			chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
			chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
			chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
			chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
			chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
			chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
			chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
			chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
			chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
			chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
			chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
			chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
			chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
			chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
			chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
			chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
			chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
			chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
			chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
			chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
			chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
			chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
			chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
			chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
			chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
			chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
			chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
			chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
			chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
			chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
			chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
			chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
			chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
			chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
			chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
			chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
			chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
			chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
			chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
			chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
			chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
			chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
			chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
			chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
			chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
			chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
			chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
			chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
			chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
			chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
			chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
			chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
			chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
			chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
			chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
			chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
			// Euro Sign
			chr(226).chr(130).chr(172) => 'E',
			// GBP (Pound) Sign
			chr(194).chr(163) => '');

			$string = strtr($string, $chars);
		} else {
			// Assume ISO-8859-1 if not UTF-8
			$chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
				.chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
				.chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
				.chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
				.chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
				.chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
				.chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
				.chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
				.chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
				.chr(252).chr(253).chr(255);

			$chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

			$string = strtr($string, $chars['in'], $chars['out']);
			$double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
			$double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
			$string = str_replace($double_chars['in'], $double_chars['out'], $string);
		}

		return $string;
	}

	/**
	 * Function: trackback_respond
	 * Responds to a trackback request.
	 *
	 * Parameters:
	 *     $error - Is this an error?
	 *     $message - Message to return.
	 */
	function trackback_respond($error = false, $message = "") {
		header("Content-Type: text/xml; charset=utf-8");
		if ($error) {
			echo '<?xml version="1.0" encoding="utf-8"?'.">\n";
			echo "<response>\n";
			echo "<error>1</error>\n";
			echo "<message>".$message."</message>\n";
			echo "</response>";
			exit;
		} else {
			echo '<?xml version="1.0" encoding="utf-8"?'.">\n";
			echo "<response>\n";
			echo "<error>0</error>\n";
			echo "</response>";
		}
		exit;
	}

	/**
	 * Function: trackback_send
	 * Sends a trackback request.
	 *
	 * Parameters:
	 *     $post - The post we're sending from.
	 *     $target - The URL we're sending to.
	 */
	function trackback_send($post, $target) {
		if (empty($target)) return false;

		$target = parse_url($target);
		$title = $post->title();
		fallback($title, ucfirst($post->feather)." Post #".$post->id);
		$excerpt = strip_tags(truncate($post->excerpt(), 255));

		if (!empty($target["query"])) $target["query"] = "?".$target["query"];
		if (empty($target["port"])) $target["port"] = 80;

		$connect = fsockopen($target["host"], $target["port"]);
		if (!$connect) return false;

		$config = Config::current();
		$query = "url=".rawurlencode($post->url())."&title=".rawurlencode($title)."&blog_name=".rawurlencode($config->name)."&excerpt=".rawurlencode($excerpt);

		fwrite($connect, "POST ".$target["path"].$target["query"]." HTTP/1.1\n");
		fwrite($connect, "Host: ".$target["host"]."\n");
		fwrite($connect, "Content-type: application/x-www-form-urlencoded\n");
		fwrite($connect, "Content-length: ". strlen($query)."\n");
		fwrite($connect, "Connection: close\n\n");
		fwrite($connect, $query);

		fclose($connect);

		return true;
	}

	/**
	 * Function: send_pingbacks
	 * Sends pingback requests to the URLs in a string.
	 *
	 * Parameters:
	 *     $string - The string to crawl for pingback URLs.
	 *     $post - The post we're sending from.
	 */
	function send_pingbacks($string, $post) {
		foreach (grab_urls($string) as $url)
			if ($ping_url = pingback_url($url)) {
				if (!class_exists("IXR_Client"))
					require INCLUDES_DIR."/lib/ixr.php";

				$client = new IXR_Client($ping_url);
				$client->timeout = 3;
				$client->useragent.= " -- Chyrp/".CHYRP_VERSION;
				$client->query("pingback.ping", $post->url(), $url);
			}
	}

	/**
	 * Function: grab_urls
	 * Crawls a string for links.
	 *
	 * Parameters:
	 *     $string - The string to crawl.
	 *
	 * Returns:
	 *     $matches[] - An array of all URLs found in the string.
	 */
	function grab_urls($string) {
		$regexp = "/<a[^>]+href=[\"|']([^\"]+)[\"|']>[^<]+<\/a>/";
		preg_match_all(Trigger::current()->filter($regexp, "link_regexp"), stripslashes($string), $matches);
		$matches = $matches[1];
		return $matches;
	}

	/**
	 * Function: pingback_url
	 * Checks if a URL is pingback-capable.
	 *
	 * Parameters:
	 *     $url - The URL to check.
	 *
	 * Returns:
	 *     $url - The pingback target, if the URL is pingback-capable.
	 */
	function pingback_url($url) {
		extract(parse_url($url), EXTR_SKIP);
		if (!isset($host)) return false;

		$path = (!isset($path)) ? '/' : $path ;
		if (isset($query)) $path.= '?'.$query;
		$port = (isset($port)) ? $port : 80 ;

		# Connect
		$connect = @fsockopen($host, $port, $errno, $errstr, 2);
		if (!$connect) return false;

		# Send the GET headers
		fwrite($connect, "GET $path HTTP/1.1\r\n");
		fwrite($connect, "Host: $host\r\n");
		fwrite($connect, "User-Agent: Chyrp/".CHYRP_VERSION."\r\n\r\n");

		# Check for X-Pingback header
		$headers = "";
		while (!feof($connect)) {
			$line = fgets($connect, 512);
			if (trim($line) == "") break;
			$headers.= trim($line)."\n";

			if (preg_match("/X-Pingback: (.+)/i", $line, $matches))
				return trim($matches[1]);

			# Nothing's found so far, so grab the content-type
			# for the <link> search afterwards
			if (preg_match("/Content-Type: (.+)/i", $headers, $matches))
				$content_type = trim($matches[1]);
		}

		# No header found, check for <link>
		if (preg_match('/(image|audio|video|model)/i', $content_type)) return false;
		$size = 0;
		while (!feof($connect)) {
			$line = fgets($connect, 1024);
			if (preg_match("/<link rel=[\"|']pingback[\"|'] href=[\"|']([^\"]+)[\"|'] ?\/?>/i", $line, $link))
				return $link[1];
			$size += strlen($line);
			if ($size > 2048) return false;
		}

		fclose($connect);

		return false;
	}

	/**
	 * Function: camelize
	 * Converts a given string to camel-case.
	 *
	 * Parameters:
	 *     $string - The string to camelize.
	 *     $keep_spaces - Whether or not to convert underscores to spaces or remove them.
	 *
	 * Returns:
	 *     A CamelCased string.
	 */
	function camelize($string, $keep_spaces = false) {
		$lower = strtolower($string);
		$deunderscore = str_replace("_", " ", $lower);
		$dehyphen = str_replace("-", " ", $deunderscore);
		$final = ucwords($dehyphen);

		if (!$keep_spaces)
			$final = str_replace(" ", "", $final);

		return $final;
	}

	/**
	 * Function: decamelize
	 * Decamelizes a string.
	 *
	 * Parameters:
	 *     $string - The string to decamelize.
	 *
	 * Returns:
	 *     A de_camel_cased string.
	 *
	 * See Also:
	 * <camelize>
	 */
	function decamelize($string) {
		return strtolower(preg_replace("/([a-z])([A-Z])/", "\\1_\\2", $string));
	}

	/**
	 * Function: selected
	 * If $val1 == $val2, outputs ' selected="selected"'
	 */
	function selected($val1, $val2, $return = false) {
		if ($val1 == $val2)
			if ($return)
				return ' selected="selected"';
			else
				echo ' selected="selected"';
	}

	/**
	 * Function: checked
	 * If $val == 1 (true), outputs ' checked="checked"'
	 */
	function checked($val) {
		if ($val == 1) echo ' checked="checked"';
	}

	/**
	 * Function: module_enabled
	 * Returns whether the given module is enabled or not.
	 *
	 * Parameters:
	 *     $name - The folder name of the module.
	 */
	function module_enabled($name) {
		$config = Config::current();
		return in_array($name, $config->enabled_modules);
	}

	/**
	 * Function: feather_enabled
	 * Returns whether the given feather is enabled or not.
	 *
	 * Parameters:
	 *     $name - The folder name of the feather.
	 */
	function feather_enabled($name) {
		$config = Config::current();
		return in_array($name, $config->enabled_feathers);
	}

	/**
	 * Function: fallback
	 * Gracefully falls back a given variable if it's empty or not set.
	 *
	 * Parameters:
	 *     &$variable - The variable to check for.
	 *     $fallback - What to set if the variable is empty or not set.
	 *     $return - Whether to set it or to return.
	 *
	 * Returns:
	 *     $variable = $fallback - If $return is false and $variable is empty or not set.
	 *     $fallback - If $return is true and $variable is empty or not set.
	 */
	function fallback(&$variable, $fallback = null, $return = false) {
		if (is_bool($variable))
			return $variable;

		$set = (!isset($variable) or empty($variable) or (is_string($variable) and trim($variable) == ""));

		if (!$return and $set)
			$variable = $fallback;

		return $set ? $fallback : $variable ;
	}

	/**
	 * Function: random
	 * Returns a random string.
	 *
	 * Parameters:
	 *     $length - How long the string should be.
	 */
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

	/**
	 * Function: unique_filename
	 * Makes a given filename unique for the uploads directory.
	 *
	 * Parameters:
	 *     $name - The name to check.
	 *
	 * Returns:
	 *     $name - A unique version of the given $name.
	 */
	function unique_filename($name, $num = 2) {
		if (!file_exists(MAIN_DIR.Config::current()->uploads_path.$name))
			return $name;

		$name = explode(".", $name);

		# Handle "double extensions"
		foreach (array("tar.gz", "tar.bz", "tar.bz2") as $extension) {
			list($first, $second) = explode(".", $extension);
			$file_first =& $name[count($name) - 2];
			if ($file_first == $first and end($name) == $second) {
				$file_first = $first.".".$second;
				array_pop($name);
			}
		}

		$ext = ".".array_pop($name);

		$try = implode(".", $name)."-".$num.$ext;
		if (!file_exists(MAIN_DIR.Config::current()->uploads_path.$try))
			return $try;

		return unique_filename(implode(".", $name).$ext, $num + 1);
	}

	/**
	 * Function: upload
	 * Moves an uploaded file to the uploads directory.
	 *
	 * Parameters:
	 *     $file - The $_FILES value.
	 *     $extension - An array of valid extensions (case-insensitive).
	 *     $path - A sub-folder in the uploads directory (optional).
	 *     $put - Use copy() instead of move_uploaded_file()?
	 *
	 * Returns:
	 *     $filename - The resulting filename from the upload.
	 */
	function upload($file, $extension = null, $path = "", $put = false) {
		$file_split = explode(".", $file['name']);

		$original_ext = end($file_split);

		# Handle "double extensions"
		foreach (array("tar.gz", "tar.bz", "tar.bz2") as $ext) {
			list($first, $second) = explode(".", $ext);
			$file_first =& $file_split[count($file_split) - 2];
			if ($file_first == $first and end($file_split) == $second) {
				$file_first = $first.".".$second;
				array_pop($file_split);
			}
		}

		$file_ext = end($file_split);

		if (is_array($extension)) {
			if (!in_array(strtolower($file_ext), $extension) and !in_array(strtolower($original_ext), $extension)) {
				$list = "";
				for ($i = 0; $i < count($extension); $i++) {
					$comma = "";
					if (($i + 1) != count($extension)) $comma = ", ";
					if (($i + 2) == count($extension)) $comma = ", and ";
					$list.= "<code>*.".$extension[$i]."</code>".$comma;
				}
				error(__("Invalid Extension"), _f("Only %s files are supported.", array($list)));
			}
		} elseif (isset($extension) and strtolower($file_ext) != strtolower($extension) and strtolower($original_ext) != strtolower($extension))
			error(__("Invalid Extension"), _f("Only %s files are supported.", array("*.".$extension)));

		array_pop($file_split);
		$file_clean = implode(".", $file_split);
		$file_clean = sanitize($file_clean, false).".".$file_ext;
		$filename = unique_filename($file_clean);

		$message = __("Couldn't upload file. CHMOD <code>".MAIN_DIR.Config::current()->uploads_path."</code> to 777 and try again. If this problem persists, it's probably timing out; in which case, you must contact your system administrator to increase the maximum POST and upload sizes.");
		if ($put) {
			if (!@copy($file['tmp_name'], MAIN_DIR.Config::current()->uploads_path.$path.$filename))
				error(__("Error"), $message);
		} elseif (!@move_uploaded_file($file['tmp_name'], MAIN_DIR.Config::current()->uploads_path.$path.$filename))
			error(__("Error"), $message);

		return $filename;
	}

	/**
	 * Function: upload_from_url
	 * Copy a file from a specified URL to their upload directory.
	 *
	 * Parameters:
	 *     $url - The URL to copy.
	 *     $extension - An array of valid extensions (case-insensitive).
	 *     $path - A sub-folder in the uploads directory (optional).
	 *
	 * See Also:
	 *     <upload>
	 */
	function upload_from_url($url, $extension = null, $path = "") {
		$file = tempnam(sys_get_temp_dir(), "chyrp");
		file_put_contents($file, get_remote($url));

		$fake_file = array("name" => basename(parse_url($url, PHP_URL_PATH)),
		                   "tmp_name" => $file);

		return upload($fake_file, $extension, $path, true);
	}

	/**
	 * Function: timer_start
	 * Starts the timer.
	 */
	function timer_start() {
		global $time_start;
		$mtime = explode(" ", microtime());
		$mtime = $mtime[1] + $mtime[0];
		$time_start = $mtime;
	}

	/**
	 * Function: timer_stop
	 * Stops the timer and returns the total time.
	 *
	 * Parameters:
	 *     $precision - Number of decimals places to round to.
	 *
	 * Returns:
	 *     A formatted number with the given $precision.
	 */
	function timer_stop($precision = 3) {
		global $time_start;
		$mtime = microtime();
		$mtime = explode(" ", $mtime);
		$mtime = $mtime[1] + $mtime[0];
		$time_end = $mtime;
		$time_total = $time_end - $time_start;
		return number_format($time_total, $precision);
	}

	/**
	 * Function: normalize
	 * Attempts to normalize all newlines and whitespace into single spaces.
	 */
	function normalize($string) {
		$trimmed = trim($string);
		$newlines = str_replace("\n\n", " ", $trimmed);
		$newlines = str_replace("\n", "", $newlines);
		$normalized = preg_replace("/\s+/", " ", $newlines);
		return $normalized;
	}

	/**
	 * Function: get_remote
	 * Grabs the contents of a website/location.
	 */
	function get_remote($url) {
		extract(parse_url($url), EXTR_SKIP);

		if (ini_get("allow_url_fopen")) {
			$content = @file_get_contents($url);
		} elseif (function_exists("curl_init")) {
			$handle = curl_init();
			curl_setopt($handle, CURLOPT_URL, $url);
			curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 1);
			curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($handle, CURLOPT_TIMEOUT, 60);
			$content = curl_exec($handle);
			curl_close($handle);
		} else {
			$path = (!isset($path)) ? '/' : $path ;
			if (isset($query)) $path.= '?'.$query;
			$port = (isset($port)) ? $port : 80 ;

			$connect = @fsockopen($host, $port, $errno, $errstr, 2);
			if (!$connect) return false;

			# Send the GET headers
			fwrite($connect, "GET ".$path." HTTP/1.1\r\n");
			fwrite($connect, "Host: ".$host."\r\n");
			fwrite($connect, "User-Agent: Chyrp/".CHYRP_VERSION."\r\n\r\n");

			$content = "";
			while (!feof($connect)) {
				$line = fgets($connect, 128);
				if (preg_match("/\r\n/", $line)) continue;

				$content.= $line;
			}

			fclose($connect);
		}

		return $content;
	}

	/**
	 * Function: filter_highlight
	 * Cleans up a highlight_string(), applying CSS classes and removing
	 * extra linebreaks so that it can be wrapped in a <pre>.
	 */
	function filter_highlight($string) {
		$colours = array('style="color: #0000BB"' => 'class="php_default"',
		                 'style="color: #007700"' => 'class="php_keyword"',
                         'style="color: #DD0000"' => 'class="php_string"',
		                 'style="color: #FF8000"' => 'class="php_comment"');
		$nl = str_replace("\n", "", $string);
		$br = str_replace("#000000\"><br />", "#000000\">", $nl);
		$nbsp = str_replace("&amp;nbsp;", "&nbsp;", $br);
		$classes = str_replace(array_keys($colours), array_values($colours), $nbsp);
		return $classes;
	}

	/**
	 * Function: self_url
	 * Returns the current URL.
	 */
	function self_url() {
		$split = explode("/", $_SERVER['SERVER_PROTOCOL']);
		$protocol = strtolower($split[0]);
		$default_port = ($protocol == "http") ? 80 : 443 ;
		$port = ($_SERVER['SERVER_PORT'] == $default_port) ? "" : ":".$_SERVER['SERVER_PORT'] ;
		return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
	}

	/**
	 * Function: show_404
	 * Shows a 404 error message, extracting the passed array into the scope.
	 *
	 * Parameters:
	 *     $scope - An array of values to extract into the scope.
	 */
	 function show_404() {
		header("HTTP/1.1 404 Not Found");

		if (!defined('CHYRP_VERSION'))
			exit("404 Not Found");

		$theme = Theme::current();

		$theme->title = "404";

		if ($theme->file_exists("pages/404"))
			$theme->load("pages/404");
		else {
?>
		<h1><?php echo __("Not Found", "theme"); ?></h1>
		<div class="post body"><?php echo __("Sorry, but you are looking for something that isn't here."); ?></div>
<?php
		}
		exit;
	}

	/**
	 * Function: month_to_number
	 * Converts a month name (e.g. June) to its number (e.g. 6)
	 *
	 * Parameters:
	 *     $name - The month.
	 */
	function month_to_number($month) {
		$int = array_search($month, array("January", "February", "March", "April", "May", "June", "July", "August", "September", "August", "November", "December")) + 1;
		return ($int < 9) ? "0".$int : $int ;
	}

	/**
	 * Function: cookie_cutter
	 * Sets a cookie.
	 *
	 * Parameters:
	 *     $name - The name of the cookie.
	 *     $data - The data to store in the cookie.
	 *     $time - The timestamp (time()) at which point the cookie expire.
	 */
	function cookie_cutter($name, $data, $time = null) {
		fallback($time, time() + 2592000); # 30 days
		$config = Config::current();
		$host = parse_url($config->url, PHP_URL_HOST);
		$host = ($host == "localhost") ? null : '.'.parse_url($config->url, PHP_URL_HOST) ;

		#if (version_compare(PHP_VERSION, '5.2.0', '>='))
		#	return setcookie($name, $data, $time, '/', $host, false, true);
		#else
			return setcookie($name, $data, $time, "/", $host);
	}

	/**
	 * Function: set_locale
	 * Set locale in a platform-independent way
	 *
	 * Parameters:
	 *     $locale - the locale name ('en_US', 'uk_UA', 'fr_FR' etc.)
	 *
	 * Returns:
	 *     The encoding name used by locale-aware functions.
	 */
    function set_locale($locale) { # originally via http://www.onphp5.com/article/22; heavily modified
		if ($locale == "en_US") return; # en_US is the default in Chyrp; their system may have
		                                # its own locale setting and no Chyrp translation available
		                                # for their locale, so let's just leave it alone.

		list($lang, $cty) = explode("_", $locale);
		$locales = array($locale.".UTF-8", $lang, "en_US.UTF-8", "en");
		$result = setlocale(LC_ALL, $locales);

		return (!strpos($result, 'UTF-8')) ? "CP".preg_replace('~\.(\d+)$~', "\\1", $result) : "UTF-8" ;
    }

	/**
	 * Function: sanitize_input
	 * Makes sure no inherently broken ideas such as magic_quotes break our application
	 *
	 * Parameters:
	 *     $data - The array to be sanitized, usually one of ($_GET, $_POST, $_COOKIE, $_REQUEST)
	 */
	function sanitize_input(&$data) {
		foreach ($data as &$value)
			if (is_array($value))
				sanitize_input($value);
			else
				$value = get_magic_quotes_gpc() ? stripslashes($value) : $value ;
	}

	/**
	 * Function: match
	 * Try and match a string against an array of regular expressions.
	 *
	 * Parameters:
	 *     $try - An array of regular expressions, or a single regular expression.
	 *     $haystack - The string to test.
	 */
	function match($try, $haystack) {
		if (is_string($try))
			return preg_match($try, $haystack);

		foreach ($try as $needle)
			if (preg_match($needle, $haystack))
				return true;

		return false;
	}

	/**
	 * Function: cancel_module
	 * Temporarily removes a module from $config->enabled_modules.
	 */
	 function cancel_module($target) {
		$this_disabled = array();

		$config = Config::current();
		foreach ($config->enabled_modules as $module)
			if ($module != $target)
				$this_disabled[] = $module;

		return $config->enabled_modules = $this_disabled;
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

	/**
	 * Function: name2codepoint
	 * Converts HTML characters like &mdash; to their codepoint version.
	 */
	function name2codepoint($string) {
		global $html_entities;
		return str_replace(array_keys($html_entities), array_values($html_entities), $string);
	}

	/**
	 * Function: name2codepoint
	 * Converts HTML codepoints like &#8212; to their named version.
	 */
	function codepoint2name($string) {
		global $html_entities;
		return str_replace(array_values($html_entities), array_keys($html_entities), $string);
	}

	/**
	 * Function: timezones
	 * Returns an array of timezones that have unique offsets. Doesn't count deprecated timezones.
	 */
	function timezones() {
		$timezones = array("Africa/Abidjan" => 0,
		                   "Africa/Accra" => 0,
		                   "Africa/Addis_Ababa" => 10800,
		                   "Africa/Algiers" => 3600,
		                   "Africa/Asmara" => 10800,
		                   "Africa/Asmera" => 10800,
		                   "Africa/Bamako" => 0,
		                   "Africa/Bangui" => 3600,
		                   "Africa/Banjul" => 0,
		                   "Africa/Bissau" => 0,
		                   "Africa/Blantyre" => 7200,
		                   "Africa/Brazzaville" => 3600,
		                   "Africa/Bujumbura" => 7200,
		                   "Africa/Cairo" => 10800,
		                   "Africa/Casablanca" => 0,
		                   "Africa/Ceuta" => 7200,
		                   "Africa/Conakry" => 0,
		                   "Africa/Dakar" => 0,
		                   "Africa/Dar_es_Salaam" => 10800,
		                   "Africa/Djibouti" => 10800,
		                   "Africa/Douala" => 3600,
		                   "Africa/El_Aaiun" => 0,
		                   "Africa/Freetown" => 0,
		                   "Africa/Gaborone" => 7200,
		                   "Africa/Harare" => 7200,
		                   "Africa/Johannesburg" => 7200,
		                   "Africa/Kampala" => 10800,
		                   "Africa/Khartoum" => 10800,
		                   "Africa/Kigali" => 7200,
		                   "Africa/Kinshasa" => 3600,
		                   "Africa/Lagos" => 3600,
		                   "Africa/Libreville" => 3600,
		                   "Africa/Lome" => 0,
		                   "Africa/Luanda" => 3600,
		                   "Africa/Lubumbashi" => 7200,
		                   "Africa/Lusaka" => 7200,
		                   "Africa/Malabo" => 3600,
		                   "Africa/Maputo" => 7200,
		                   "Africa/Maseru" => 7200,
		                   "Africa/Mbabane" => 7200,
		                   "Africa/Mogadishu" => 10800,
		                   "Africa/Monrovia" => 0,
		                   "Africa/Nairobi" => 10800,
		                   "Africa/Ndjamena" => 3600,
		                   "Africa/Niamey" => 3600,
		                   "Africa/Nouakchott" => 0,
		                   "Africa/Ouagadougou" => 0,
		                   "Africa/Porto-Novo" => 3600,
		                   "Africa/Sao_Tome" => 0,
		                   "Africa/Timbuktu" => 0,
		                   "Africa/Tripoli" => 7200,
		                   "Africa/Tunis" => 7200,
		                   "Africa/Windhoek" => 3600,
		                   "America/Adak" => -32400,
		                   "America/Anchorage" => -28800,
		                   "America/Anguilla" => -14400,
		                   "America/Antigua" => -14400,
		                   "America/Araguaina" => -10800,
		                   "America/Argentina/Buenos_Aires" => -10800,
		                   "America/Argentina/Catamarca" => -10800,
		                   "America/Argentina/ComodRivadavia" => -10800,
		                   "America/Argentina/Cordoba" => -10800,
		                   "America/Argentina/Jujuy" => -10800,
		                   "America/Argentina/La_Rioja" => -10800,
		                   "America/Argentina/Mendoza" => -10800,
		                   "America/Argentina/Rio_Gallegos" => -10800,
		                   "America/Argentina/San_Juan" => -10800,
		                   "America/Argentina/San_Luis" => -10800,
		                   "America/Argentina/Tucuman" => -10800,
		                   "America/Argentina/Ushuaia" => -10800,
		                   "America/Aruba" => -14400,
		                   "America/Asuncion" => -14400,
		                   "America/Atikokan" => -18000,
		                   "America/Atka" => -32400,
		                   "America/Bahia" => -10800,
		                   "America/Barbados" => -14400,
		                   "America/Belem" => -10800,
		                   "America/Belize" => -21600,
		                   "America/Blanc-Sablon" => -14400,
		                   "America/Boa_Vista" => -14400,
		                   "America/Bogota" => -18000,
		                   "America/Boise" => -21600,
		                   "America/Buenos_Aires" => -10800,
		                   "America/Cambridge_Bay" => -21600,
		                   "America/Campo_Grande" => -14400,
		                   "America/Cancun" => -18000,
		                   "America/Caracas" => -16200,
		                   "America/Catamarca" => -10800,
		                   "America/Cayenne" => -10800,
		                   "America/Cayman" => -18000,
		                   "America/Chicago" => -18000,
		                   "America/Chihuahua" => -21600,
		                   "America/Coral_Harbour" => -18000,
		                   "America/Cordoba" => -10800,
		                   "America/Costa_Rica" => -21600,
		                   "America/Cuiaba" => -14400,
		                   "America/Curacao" => -14400,
		                   "America/Danmarkshavn" => 0,
		                   "America/Dawson" => -25200,
		                   "America/Dawson_Creek" => -25200,
		                   "America/Denver" => -21600,
		                   "America/Detroit" => -14400,
		                   "America/Dominica" => -14400,
		                   "America/Edmonton" => -21600,
		                   "America/Eirunepe" => -18000,
		                   "America/El_Salvador" => -21600,
		                   "America/Ensenada" => -25200,
		                   "America/Fort_Wayne" => -14400,
		                   "America/Fortaleza" => -10800,
		                   "America/Glace_Bay" => -10800,
		                   "America/Godthab" => -7200,
		                   "America/Goose_Bay" => -10800,
		                   "America/Grand_Turk" => -14400,
		                   "America/Grenada" => -14400,
		                   "America/Guadeloupe" => -14400,
		                   "America/Guatemala" => -21600,
		                   "America/Guayaquil" => -18000,
		                   "America/Guyana" => -14400,
		                   "America/Halifax" => -10800,
		                   "America/Havana" => -14400,
		                   "America/Hermosillo" => -25200,
		                   "America/Indiana/Indianapolis" => -14400,
		                   "America/Indiana/Knox" => -18000,
		                   "America/Indiana/Marengo" => -14400,
		                   "America/Indiana/Petersburg" => -14400,
		                   "America/Indiana/Tell_City" => -18000,
		                   "America/Indiana/Vevay" => -14400,
		                   "America/Indiana/Vincennes" => -14400,
		                   "America/Indiana/Winamac" => -14400,
		                   "America/Indianapolis" => -14400,
		                   "America/Inuvik" => -21600,
		                   "America/Iqaluit" => -14400,
		                   "America/Jamaica" => -18000,
		                   "America/Jujuy" => -10800,
		                   "America/Juneau" => -28800,
		                   "America/Kentucky/Louisville" => -14400,
		                   "America/Kentucky/Monticello" => -14400,
		                   "America/Knox_IN" => -18000,
		                   "America/La_Paz" => -14400,
		                   "America/Lima" => -18000,
		                   "America/Los_Angeles" => -25200,
		                   "America/Louisville" => -14400,
		                   "America/Maceio" => -10800,
		                   "America/Managua" => -21600,
		                   "America/Manaus" => -14400,
		                   "America/Marigot" => -14400,
		                   "America/Martinique" => -14400,
		                   "America/Mazatlan" => -21600,
		                   "America/Mendoza" => -10800,
		                   "America/Menominee" => -18000,
		                   "America/Merida" => -18000,
		                   "America/Mexico_City" => -18000,
		                   "America/Miquelon" => -7200,
		                   "America/Moncton" => -10800,
		                   "America/Monterrey" => -18000,
		                   "America/Montevideo" => -10800,
		                   "America/Montreal" => -14400,
		                   "America/Montserrat" => -14400,
		                   "America/Nassau" => -14400,
		                   "America/New_York" => -14400,
		                   "America/Nipigon" => -14400,
		                   "America/Nome" => -28800,
		                   "America/Noronha" => -7200,
		                   "America/North_Dakota/Center" => -18000,
		                   "America/North_Dakota/New_Salem" => -18000,
		                   "America/Panama" => -18000,
		                   "America/Pangnirtung" => -14400,
		                   "America/Paramaribo" => -10800,
		                   "America/Phoenix" => -25200,
		                   "America/Port-au-Prince" => -18000,
		                   "America/Port_of_Spain" => -14400,
		                   "America/Porto_Acre" => -18000,
		                   "America/Porto_Velho" => -14400,
		                   "America/Puerto_Rico" => -14400,
		                   "America/Rainy_River" => -18000,
		                   "America/Rankin_Inlet" => -18000,
		                   "America/Recife" => -10800,
		                   "America/Regina" => -21600,
		                   "America/Resolute" => -18000,
		                   "America/Rio_Branco" => -18000,
		                   "America/Rosario" => -10800,
		                   "America/Santiago" => -14400,
		                   "America/Santo_Domingo" => -14400,
		                   "America/Sao_Paulo" => -10800,
		                   "America/Scoresbysund" => 0,
		                   "America/Shiprock" => -21600,
		                   "America/St_Barthelemy" => -14400,
		                   "America/St_Johns" => -9000,
		                   "America/St_Kitts" => -14400,
		                   "America/St_Lucia" => -14400,
		                   "America/St_Thomas" => -14400,
		                   "America/St_Vincent" => -14400,
		                   "America/Swift_Current" => -21600,
		                   "America/Tegucigalpa" => -21600,
		                   "America/Thule" => -10800,
		                   "America/Thunder_Bay" => -14400,
		                   "America/Tijuana" => -25200,
		                   "America/Toronto" => -14400,
		                   "America/Tortola" => -14400,
		                   "America/Vancouver" => -25200,
		                   "America/Virgin" => -14400,
		                   "America/Whitehorse" => -25200,
		                   "America/Winnipeg" => -18000,
		                   "America/Yakutat" => -28800,
		                   "America/Yellowknife" => -21600,
		                   "Antarctica/Casey" => 28800,
		                   "Antarctica/Davis" => 25200,
		                   "Antarctica/DumontDUrville" => 36000,
		                   "Antarctica/Mawson" => 21600,
		                   "Antarctica/McMurdo" => 43200,
		                   "Antarctica/Palmer" => -14400,
		                   "Antarctica/Rothera" => -10800,
		                   "Antarctica/South_Pole" => 43200,
		                   "Antarctica/Syowa" => 10800,
		                   "Antarctica/Vostok" => 21600,
		                   "Arctic/Longyearbyen" => 7200,
		                   "Asia/Aden" => 10800,
		                   "Asia/Almaty" => 21600,
		                   "Asia/Amman" => 10800,
		                   "Asia/Anadyr" => 46800,
		                   "Asia/Aqtau" => 18000,
		                   "Asia/Aqtobe" => 18000,
		                   "Asia/Ashgabat" => 18000,
		                   "Asia/Ashkhabad" => 18000,
		                   "Asia/Baghdad" => 10800,
		                   "Asia/Bahrain" => 10800,
		                   "Asia/Baku" => 18000,
		                   "Asia/Bangkok" => 25200,
		                   "Asia/Beirut" => 10800,
		                   "Asia/Bishkek" => 21600,
		                   "Asia/Brunei" => 28800,
		                   "Asia/Calcutta" => 19800,
		                   "Asia/Choibalsan" => 32400,
		                   "Asia/Chongqing" => 28800,
		                   "Asia/Chungking" => 28800,
		                   "Asia/Colombo" => 19800,
		                   "Asia/Dacca" => 21600,
		                   "Asia/Damascus" => 10800,
		                   "Asia/Dhaka" => 21600,
		                   "Asia/Dili" => 32400,
		                   "Asia/Dubai" => 14400,
		                   "Asia/Dushanbe" => 18000,
		                   "Asia/Gaza" => 10800,
		                   "Asia/Harbin" => 28800,
		                   "Asia/Ho_Chi_Minh" => 25200,
		                   "Asia/Hong_Kong" => 28800,
		                   "Asia/Hovd" => 25200,
		                   "Asia/Irkutsk" => 32400,
		                   "Asia/Istanbul" => 10800,
		                   "Asia/Jakarta" => 25200,
		                   "Asia/Jayapura" => 32400,
		                   "Asia/Jerusalem" => 10800,
		                   "Asia/Kabul" => 16200,
		                   "Asia/Kamchatka" => 46800,
		                   "Asia/Karachi" => 18000,
		                   "Asia/Kashgar" => 28800,
		                   "Asia/Katmandu" => 20700,
		                   "Asia/Kolkata" => 19800,
		                   "Asia/Krasnoyarsk" => 28800,
		                   "Asia/Kuala_Lumpur" => 28800,
		                   "Asia/Kuching" => 28800,
		                   "Asia/Kuwait" => 10800,
		                   "Asia/Macao" => 28800,
		                   "Asia/Macau" => 28800,
		                   "Asia/Magadan" => 43200,
		                   "Asia/Makassar" => 28800,
		                   "Asia/Manila" => 28800,
		                   "Asia/Muscat" => 14400,
		                   "Asia/Nicosia" => 10800,
		                   "Asia/Novosibirsk" => 25200,
		                   "Asia/Omsk" => 25200,
		                   "Asia/Oral" => 18000,
		                   "Asia/Phnom_Penh" => 25200,
		                   "Asia/Pontianak" => 25200,
		                   "Asia/Pyongyang" => 32400,
		                   "Asia/Qatar" => 10800,
		                   "Asia/Qyzylorda" => 21600,
		                   "Asia/Rangoon" => 23400,
		                   "Asia/Riyadh" => 10800,
		                   "Asia/Saigon" => 25200,
		                   "Asia/Sakhalin" => 39600,
		                   "Asia/Samarkand" => 18000,
		                   "Asia/Seoul" => 32400,
		                   "Asia/Shanghai" => 28800,
		                   "Asia/Singapore" => 28800,
		                   "Asia/Taipei" => 28800,
		                   "Asia/Tashkent" => 18000,
		                   "Asia/Tbilisi" => 14400,
		                   "Asia/Tehran" => 16200,
		                   "Asia/Tel_Aviv" => 10800,
		                   "Asia/Thimbu" => 21600,
		                   "Asia/Thimphu" => 21600,
		                   "Asia/Tokyo" => 32400,
		                   "Asia/Ujung_Pandang" => 28800,
		                   "Asia/Ulaanbaatar" => 28800,
		                   "Asia/Ulan_Bator" => 28800,
		                   "Asia/Urumqi" => 28800,
		                   "Asia/Vientiane" => 25200,
		                   "Asia/Vladivostok" => 39600,
		                   "Asia/Yakutsk" => 36000,
		                   "Asia/Yekaterinburg" => 21600,
		                   "Asia/Yerevan" => 18000,
		                   "Atlantic/Azores" => 0,
		                   "Atlantic/Bermuda" => -10800,
		                   "Atlantic/Canary" => 3600,
		                   "Atlantic/Cape_Verde" => -3600,
		                   "Atlantic/Faeroe" => 3600,
		                   "Atlantic/Faroe" => 3600,
		                   "Atlantic/Jan_Mayen" => 7200,
		                   "Atlantic/Madeira" => 3600,
		                   "Atlantic/Reykjavik" => 0,
		                   "Atlantic/South_Georgia" => -7200,
		                   "Atlantic/St_Helena" => 0,
		                   "Atlantic/Stanley" => -14400,
		                   "Australia/ACT" => 36000,
		                   "Australia/Adelaide" => 34200,
		                   "Australia/Brisbane" => 36000,
		                   "Australia/Broken_Hill" => 34200,
		                   "Australia/Canberra" => 36000,
		                   "Australia/Currie" => 36000,
		                   "Australia/Darwin" => 34200,
		                   "Australia/Eucla" => 31500,
		                   "Australia/Hobart" => 36000,
		                   "Australia/LHI" => 37800,
		                   "Australia/Lindeman" => 36000,
		                   "Australia/Lord_Howe" => 37800,
		                   "Australia/Melbourne" => 36000,
		                   "Australia/North" => 34200,
		                   "Australia/NSW" => 36000,
		                   "Australia/Perth" => 28800,
		                   "Australia/Queensland" => 36000,
		                   "Australia/South" => 34200,
		                   "Australia/Sydney" => 36000,
		                   "Australia/Tasmania" => 36000,
		                   "Australia/Victoria" => 36000,
		                   "Australia/West" => 28800,
		                   "Australia/Yancowinna" => 34200,
		                   "Europe/Amsterdam" => 7200,
		                   "Europe/Andorra" => 7200,
		                   "Europe/Athens" => 10800,
		                   "Europe/Belfast" => 3600,
		                   "Europe/Belgrade" => 7200,
		                   "Europe/Berlin" => 7200,
		                   "Europe/Bratislava" => 7200,
		                   "Europe/Brussels" => 7200,
		                   "Europe/Bucharest" => 10800,
		                   "Europe/Budapest" => 7200,
		                   "Europe/Chisinau" => 10800,
		                   "Europe/Copenhagen" => 7200,
		                   "Europe/Dublin" => 3600,
		                   "Europe/Gibraltar" => 7200,
		                   "Europe/Guernsey" => 3600,
		                   "Europe/Helsinki" => 10800,
		                   "Europe/Isle_of_Man" => 3600,
		                   "Europe/Istanbul" => 10800,
		                   "Europe/Jersey" => 3600,
		                   "Europe/Kaliningrad" => 10800,
		                   "Europe/Kiev" => 10800,
		                   "Europe/Lisbon" => 3600,
		                   "Europe/Ljubljana" => 7200,
		                   "Europe/London" => 3600,
		                   "Europe/Luxembourg" => 7200,
		                   "Europe/Madrid" => 7200,
		                   "Europe/Malta" => 7200,
		                   "Europe/Mariehamn" => 10800,
		                   "Europe/Minsk" => 10800,
		                   "Europe/Monaco" => 7200,
		                   "Europe/Moscow" => 14400,
		                   "Europe/Nicosia" => 10800,
		                   "Europe/Oslo" => 7200,
		                   "Europe/Paris" => 7200,
		                   "Europe/Podgorica" => 7200,
		                   "Europe/Prague" => 7200,
		                   "Europe/Riga" => 10800,
		                   "Europe/Rome" => 7200,
		                   "Europe/Samara" => 18000,
		                   "Europe/San_Marino" => 7200,
		                   "Europe/Sarajevo" => 7200,
		                   "Europe/Simferopol" => 10800,
		                   "Europe/Skopje" => 7200,
		                   "Europe/Sofia" => 10800,
		                   "Europe/Stockholm" => 7200,
		                   "Europe/Tallinn" => 10800,
		                   "Europe/Tirane" => 7200,
		                   "Europe/Tiraspol" => 10800,
		                   "Europe/Uzhgorod" => 10800,
		                   "Europe/Vaduz" => 7200,
		                   "Europe/Vatican" => 7200,
		                   "Europe/Vienna" => 7200,
		                   "Europe/Vilnius" => 10800,
		                   "Europe/Volgograd" => 14400,
		                   "Europe/Warsaw" => 7200,
		                   "Europe/Zagreb" => 7200,
		                   "Europe/Zaporozhye" => 10800,
		                   "Europe/Zurich" => 7200,
		                   "Indian/Antananarivo" => 10800,
		                   "Indian/Chagos" => 21600,
		                   "Indian/Christmas" => 25200,
		                   "Indian/Cocos" => 23400,
		                   "Indian/Comoro" => 10800,
		                   "Indian/Kerguelen" => 18000,
		                   "Indian/Mahe" => 14400,
		                   "Indian/Maldives" => 18000,
		                   "Indian/Mauritius" => 14400,
		                   "Indian/Mayotte" => 10800,
		                   "Indian/Reunion" => 14400,
		                   "Pacific/Apia" => -39600,
		                   "Pacific/Auckland" => 43200,
		                   "Pacific/Chatham" => 45900,
		                   "Pacific/Easter" => -21600,
		                   "Pacific/Efate" => 39600,
		                   "Pacific/Enderbury" => 46800,
		                   "Pacific/Fakaofo" => -36000,
		                   "Pacific/Fiji" => 43200,
		                   "Pacific/Funafuti" => 43200,
		                   "Pacific/Galapagos" => -21600,
		                   "Pacific/Gambier" => -32400,
		                   "Pacific/Guadalcanal" => 39600,
		                   "Pacific/Guam" => 36000,
		                   "Pacific/Honolulu" => -36000,
		                   "Pacific/Johnston" => -36000,
		                   "Pacific/Kiritimati" => 50400,
		                   "Pacific/Kosrae" => 39600,
		                   "Pacific/Kwajalein" => 43200,
		                   "Pacific/Majuro" => 43200,
		                   "Pacific/Marquesas" => -34200,
		                   "Pacific/Midway" => -39600,
		                   "Pacific/Nauru" => 43200,
		                   "Pacific/Niue" => -39600,
		                   "Pacific/Norfolk" => 41400,
		                   "Pacific/Noumea" => 39600,
		                   "Pacific/Pago_Pago" => -39600,
		                   "Pacific/Palau" => 32400,
		                   "Pacific/Pitcairn" => -28800,
		                   "Pacific/Ponape" => 39600,
		                   "Pacific/Port_Moresby" => 36000,
		                   "Pacific/Rarotonga" => -36000,
		                   "Pacific/Saipan" => 36000,
		                   "Pacific/Samoa" => -39600,
		                   "Pacific/Tahiti" => -36000,
		                   "Pacific/Tarawa" => 43200,
		                   "Pacific/Tongatapu" => 46800,
		                   "Pacific/Truk" => 36000,
		                   "Pacific/Wake" => 43200,
		                   "Pacific/Wallis" => 43200,
		                   "Pacific/Yap" => 36000);

		$zones = array();
		$offsets = array();
		$reverse = (isset(Config::current()->timezone)) ? $timezones[Config::current()->timezone] : 0 ;
		foreach ($timezones as $timezone => $offset) {
			if (!in_array($offset, $offsets))
				$zones[] = array("offset" => ($offsets[] = $offset) / 3600,
				                 "name" => $timezone,
				                 "now" => time() - $reverse + $offset);
		}

		function by_time($a, $b) {
			return ($a["now"] < $b["now"]) ? -1 : 1;
		}

		usort($zones, "by_time");

		return $zones;
	}
