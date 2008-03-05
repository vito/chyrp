<?php
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
	function __($text, $domain = "chyrp") {
		global $l10n;
		return (isset($l10n[$domain])) ? $l10n[$domain]->translate($text) : $text ;
	}
	function _p($single, $plural, $number, $domain = "chyrp") {
		global $l10n;
		return (isset($l10n[$domain])) ? $l10n[$domain]->ngettext($single, $plural, $number) : (($number != 1) ? $plural : $single) ;
	}
?>