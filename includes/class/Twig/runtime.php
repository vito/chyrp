<?php
/**
 * Twig::Runtime
 * ~~~~~~~~~~~~~
 *
 * The twig runtime environment.
 *
 * :copyright: 2008 by Armin Ronacher.
 * :license: BSD.
 */


$twig_filters = array(
	// formatting filters
	'date' =>		'twig_date_format_filter',
	'numberformat' =>	'number_format',
	'moneyformat' =>	'money_format',
	'filesizeformat' =>	'twig_filesize_format_filter',
	'format' =>		'sprintf',

	// numbers
	'even' =>		'twig_is_even_filter',
	'odd' =>		'twig_is_odd_filter',

	// escaping and encoding
	'escape' =>		'htmlspecialchars',
	'e' =>			'htmlspecialchars',
	'urlencode' =>		'twig_urlencode_filter',

	// string filters
	'title' =>		'twig_title_string_filter',
	'capitalize' =>		'twig_capitalize_string_filter',
	'upper' =>		'strtoupper',
	'lower' =>		'strtolower',
	'strip' =>		'trim',
	'rstrip' =>		'rtrim',
	'lstrip' =>		'ltrim',

	// array helpers
	'join' =>		'twig_join_filter',
	'reverse' =>		'array_reverse',
	'length' =>		'count',
	'count' =>		'count',

	// iteration and runtime
	'default' =>		'twig_default_filter',
	'keys' =>		'array_keys',
	'items' =>		'twig_get_array_items_filter'
);


class Twig_LoopContextIterator implements Iterator
{
	public $context;
	public $seq;
	public $idx;
	public $length;
	public $parent;

	public function __construct(&$context, $seq, $parent)
	{
		$this->context = $context;
		$this->seq = $seq;
		$this->idx = 0;
		$this->length = count($seq);
		$this->parent = $parent;
	}

	public function rewind() {}

	public function key() {}

	public function valid()
	{
		return $this->idx < $this->length;
	}

	public function next()
	{
		$this->idx++;
	}

	public function current()
	{
		return $this;
	}
}

/**
 * This is called like an ordinary filter just with the name of the filter
 * as first argument.  Currently we just raise an exception here but it
 * would make sense in the future to allow dynamic filter lookup for plugins
 * or something like that.
 */
function twig_missing_filter($name)
{
	throw new Twig_RuntimeError("filter '$name' does not exist.");
}

function twig_get_attribute($context, $obj, $item)
{
	if (is_array($obj) && isset($obj[$item]))
		return $obj[$item];
	if (!is_object($obj))
		return NULL;
	if (method_exists($obj, $item))
		return call_user_func(array($obj, $item));
	if (property_exists($obj, $item)) {
		$tmp = get_object_vars($obj);
		return $tmp[$item];
	}
	$method = 'get' . ucfirst($item);
	if (method_exists($obj, $method))
		return call_user_func(array($obj, $method));
	return NULL;
}

function twig_iterate(&$context, $seq)
{
	$parent = isset($context['loop']) ? $context['loop'] : null;
	$seq = twig_make_array($seq);
	$context['loop'] = array('parent' => $parent, 'iterated' => false);
	return new Twig_LoopContextIterator($context, $seq, $parent);
}

function twig_set_loop_context(&$context, $iterator, $target)
{
	$context[$target] = $iterator->seq[$iterator->idx];
	$context['loop'] = twig_make_loop_context($iterator);
}

function twig_set_loop_context_multitarget(&$context, $iterator, $targets)
{
	$values = $iterator->seq[$iterator->idx];
	if (!is_array($values))
		$values = array($values);
	$idx = 0;
	foreach ($values as $value) {
		if (!isset($targets[$idx]))
			break;
		$context[$targets[$idx++]] = $value;
	}
	$context['loop'] = twig_make_loop_context($iterator);
}

function twig_make_loop_context($iterator)
{
	return array(
		'parent' =>     $iterator->parent,
		'length' =>     $iterator->length,
		'index0' =>     $iterator->idx,
		'index' =>      $iterator->idx + 1,
		'revindex0' =>  $iterator->length - $iterator->idx - 1,
		'revindex '=>   $iterator->length - $iterator->idx,
		'first' =>      $iterator->idx == 0,
		'last' =>       $iterator->idx - 1 == $iterator->length,
		'iterated' =>	true
	);
}

function twig_make_array($object)
{
	if (is_array($object))
		return array_values($object);
	elseif (is_object($object)) {
		$result = array();
		foreach ($object as $value)
			$result[] = $value;
		return $result;
	}
	return array();
}

function twig_date_format_filter($timestamp, $format='F j, Y, G:i')
{
	return date($format, $timestamp);
}

function twig_urlencode_filter($string, $raw=false)
{
	if ($raw)
		return rawurlencode($url);
	return urlencode($url);
}

function twig_join_filter($value, $glue='')
{
	return implode($glue, $value);
}

function twig_default_filter($value, $default='')
{
	return is_null($value) ? $default : $value;
}

function twig_get_array_items_filter($array)
{
	$result = array();
	foreach ($array as $key => $value)
		$result[] = array($key, $value);
	return $result;
}

function twig_filesize_format_filter($value)
{
	$value = max(0, (int)$value);
	$places = strlen($value);
	if ($places <= 9 && $places >= 7) {
		$value = number_format($value / 1048576, 1);
		return "$value MB";
	}
	if ($places >= 10) {
		$value = number_format($value / 1073741824, 1);
		return "$value GB";
	}
	$value = number_format($value / 1024, 1);
	return "$value KB";
}

function twig_is_even_filter($value)
{
	return $value % 2 == 0;
}

function twig_is_odd_filter($value)
{
	return $value % 2 == 1;
}


// add multibyte extensions if possible
if (function_exists('mb_get_info')) {
	function twig_upper_filter($string)
	{
		$template = twig_get_current_template();
		if (!is_null($template->charset))
			return mb_strtoupper($string, $template->charset);
		return strtoupper($string);
	}

	function twig_lower_filter($string)
	{
		$template = twig_get_current_template();
		if (!is_null($template->charset))
			return mb_strtolower($string, $template->charset);
		return strtolower($string);
	}

	function twig_title_string_filter($string)
	{
		$template = twig_get_current_template();
		if (is_null($template->charset))
			return ucwords(strtolower($string));
		return mb_convert_case($string, MB_CASE_TITLE, $template->charset);
	}

	function twig_capitalize_string_filter($string)
	{
		$template = twig_get_current_template();
		if (is_null($template->charset))
			return ucfirst(strtolower($string));
		return mb_strtoupper(mb_substr($string, 0, 1, $template->charset)) .
		       mb_strtolower(mb_substr($string, 1, null, $template->charset));
	}

	// override the builtins
	$twig_filters['upper'] = 'twig_upper_filter';
	$twig_filters['lower'] = 'twig_lower_filter';
}

// and byte fallback
else {
	function twig_title_string_filter($string)
	{
		return ucwords(strtolower($string));
	}

	function twig_capitalize_string_filter($string)
	{
		return ucfirst(strtolower($string));
	}
}
