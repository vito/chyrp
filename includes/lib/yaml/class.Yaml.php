<?php
	/**
	 * Yaml class.
	 *
	 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
	 */
	class Yaml
	{
	  /**
	   * Load YAML into a PHP array statically
	   *
	   * The load method, when supplied with a YAML stream (string or file),
	   * will do its best to convert YAML in a file into a PHP array.
	   *
	   *  Usage:
	   *  <code>
	   *   $array = YAML::Load('config.yml');
	   *   print_r($array);
	   *  </code>
	   *
	   * @param string $input Path of YAML file or string containing YAML
	   *
	   * @return array
	   */
	  public static function load($input)
	  {
	    $file = '';

	    // if input is a file, process it
	    if (strpos($input, "\n") === false && is_file($input))
	    {
	      $file = $input;

	      ob_start();
	      $retval = include($input);
	      $content = ob_get_clean();

	      // if an array is returned by the config file assume it's in plain php form else in yaml
	      $input = is_array($retval) ? $retval : $content;
	    }

	    // if an array is returned by the config file assume it's in plain php form else in yaml
	    if (is_array($input))
	    {
	      return $input;
	    }

		if (function_exists("syck_load"))
			return syck_load($input);

	    require_once dirname(__FILE__).'/class.YamlParser.php';

	    $yaml = new YamlParser();

	    try
	    {
	      $ret = $yaml->parse($input);
	    }
	    catch (Exception $e)
	    {
	      throw new InvalidArgumentException(sprintf('Unable to parse %s: %s', $file ? sprintf('file "%s"', $file) : 'string', $e->getMessage()));
	    }

	    return $ret;
	  }

	  /**
	   * Dump YAML from PHP array statically
	   *
	   * The dump method, when supplied with an array, will do its best
	   * to convert the array into friendly YAML.
	   *
	   * @param array $array PHP array
	   *
	   * @return string
	   */
	  public static function dump($array, $inline = 2)
	  {
	    require_once dirname(__FILE__).'/class.YamlDumper.php';

	    $yaml = new YamlDumper();

	    return $yaml->dump($array, $inline);
	  }
	}
