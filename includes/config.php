<?php
	if (!defined("INCLUDES_DIR")) define("INCLUDES_DIR", dirname(__FILE__));
	
	/**
	 * Class: Config
	 * Holds all of the configuration variables for the entire site, as well as Module settings.
	 */
	class Config {
		/**
		 * The class constructor is private so there is only one instance and config is guaranteed to be kept in sync.
		 */
		private function __construct() {}
		
		/**
		 * Variable: $yaml
		 * Holds all of their YAML settings as a $key => $val array.
		 */
		var $yaml = array();
		
		/**
		 * Function: load
		 * Loads a given configuation YAML file.
		 * 
		 * Parameters:
		 * 	$file - The YAML file to load into <Config>.
		 */
		public function load($file) {
			$this->yaml = Spyc::YAMLLoad($file);
			$arrays = array("enabled_modules", "enabled_feathers", "routes");
			foreach ($this->yaml as $setting => $value)
				if (in_array($setting, $arrays) and empty($value))
					$this->$setting = array();
				elseif (!is_int($setting)) # Don't load the "---"
					$this->$setting = (is_string($value)) ? stripslashes($value) : $value ;
		}
		
		/**
		 * Function: set
		 * Sets a variable's value.
		 * 
		 * Parameters:
		 * 	$setting - The setting name.
		 * 	$value - The new value. Can be boolean, numeric, an array, a string, etc.
		 */
		public function set($setting, $value) {
			if (isset($this->$setting) and $this->$setting == $value) return false; # No point in changing it
			
			# Add the PHP protection!
			$contents = "<?php header(\"Status: 401\"); exit(\"Access denied.\"); ?>\n";
			
			# Add the setting
			$this->yaml[$setting] = $value;
			
			if (isset($this->yaml[0]) and $this->yaml[0] == "--")
				unset($this->yaml[0]);
			
			# Generate the new YAML settings
			$contents.= Spyc::YAMLDump($this->yaml, 2, 60);
			
			file_put_contents(CONFIG_DIR."/config.yaml.php", $contents);
		}
		
		/**
		 * Function: remove
		 * Removes a configuration setting.
		 * 
		 * Parameters:
		 * 	$setting - The name of the variable to remove.
		 */
		public function remove($setting) {
			# Add the PHP protection!
			$contents = "<?php\n";
			$contents.= "\tif (strpos(\$_SERVER['REQUEST_URI'], \"config.yaml.php\"))\n";
			$contents.= "\t\texit(\"Gtfo.\");\n";
			$contents.= "?>\n";
			
			# Add the setting
			unset($this->yaml[$setting]);
			
			if (isset($this->yaml[0]) and $this->yaml[0] == "--")
				unset($this->yaml[0]);
			
			# Generate the new YAML settings
			$contents.= Spyc::YAMLDump($this->yaml, 2, 60);
			
			file_put_contents(CONFIG_DIR."/config.yaml.php", $contents);
		}
		
		public function get_feathers() {
			$feathers = array();
			$sql = SQL::current();
			foreach ($this->enabled_feathers as $key => $the_feather)
				$feathers[] = $sql->quote($the_feather);
			return $feathers;
		}
		
		/**
		 * Function: current
		 * Returns a singleton reference to the current configuration.
		 */
		public static function & current() {
			static $instance = null;
			if (empty($instance))
				$instance = new self();
			return $instance;
		}
	}
	$config = Config::current();
