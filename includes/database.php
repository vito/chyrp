<?php
	/**
	 * Class: SQL
	 * Contains the database settings and functions for interacting with the SQL database.
	 */
	class SQL {
		# Array: $debug
		# Holds debug information for SQL queries.
		public $debug = array();

		# Integer: $queries
		# Number of queries it takes to load the page.
		public $queries = 0;

		public $db;

		/**
		 * Function: __construct
		 * The class constructor is private so there is only one connection.
		 */
		private function __construct() {
			$this->connected = false;

			if (class_exists("PDO") and (in_array("mysql", PDO::getAvailableDrivers()) or in_array("sqlite", PDO::getAvailableDrivers())))
				$this->interface = "pdo";
			elseif (class_exists("MySQLi"))
				$this->interface = "mysqli";
			else
				$this->interface = "mysql";

			$this->interface = "mysql";
		}

		/**
		 * Function: load
		 * Loads a given database YAML file.
		 *
		 * Parameters:
		 *     $file - The YAML file to load into <SQL>.
		 */
		public function load($file) {
			$this->yaml = Spyc::YAMLLoad($file);
			foreach ($this->yaml as $setting => $value)
				if (!is_int($setting)) # Don't load the "---"
					$this->$setting = $value;
		}

		/**
		 * Function: set
		 * Sets a variable's value.
		 *
		 * Parameters:
		 *     $setting - The setting name.
		 *     $value - The new value. Can be boolean, numeric, an array, a string, etc.
		 */
		public function set($setting, $value, $overwrite = true) {
			global $errors;
			if (isset($this->$setting) and ($this->$setting == $value or !$overwrite))
				return false;

			# Add the PHP protection!
			$contents = "<?php header(\"Status: 403\"); exit(\"Access denied.\"); ?>\n";

			# Add the setting
			$this->yaml[$setting] = $value;

			if (isset($this->yaml['<?php header("Status']))
				unset($this->yaml['<?php header("Status']);

			# Generate the new YAML settings
			$contents.= Spyc::YAMLDump($this->yaml, false, 0);

			if (!@file_put_contents(INCLUDES_DIR."/database.yaml.php", $contents) and is_array($errors))
				$errors[] = _f("Could not set \"<code>%s</code>\" database setting because <code>%s</code> is not writable.", array($setting, "/includes/database.yaml.php"));
		}

		/**
		 * Function: connect
		 * Connects to the SQL database.
		 */
		public function connect($checking = false) {
			$this->load(INCLUDES_DIR."/database.yaml.php");
			if ($this->connected)
				return true;

			switch($this->interface) {
				case "pdo":
					try {
						if ($this->adapter == "sqlite") {
							$this->db = new PDO("sqlite:".$this->database, null, null, array(PDO::ATTR_PERSISTENT => true));

							$this->db->sqliteCreateFunction("YEAR", "year_from_datetime", 1);
							$this->db->sqliteCreateFunction("MONTH", "month_from_datetime", 1);
							$this->db->sqliteCreateFunction("DAY", "day_from_datetime", 1);
						} else
							$this->db = new PDO($this->adapter.":host=".$this->host.";".((isset($this->port)) ? "port=".$this->port.";" : "")."dbname=".$this->database,
							                    $this->username,
							                    $this->password,
							                    array(PDO::ATTR_PERSISTENT => true));
						$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
						if ($this->adapter == "mysql")
							$this->db->query("set names 'utf8';");
						$this->connected = true;
						return true;
					} catch (PDOException $error) {
						$message = preg_replace("/SQLSTATE\[.*?\]: .+ [0-9]+ (.*?)/", "\\1", $error->getMessage());
						return ($checking) ? false : error(__("Database Error"), $message) ;
					}
					break;
				case "mysqli":
					$this->db = new MySQLi($this->host, $this->username, $this->password, $this->database);
					break;
				case "mysql":
					$this->db = @mysql_connect($this->host, $this->username, $this->password);
					@mysql_select_db($this->database);
					break;
			}
		}

		/**
		 * Function: query
		 * Executes a query and increases <SQL->$queries>.
		 * If the query results in an error, it will die and show the error.
		 */
		public function query($query, $params = array(), $throw_exceptions = false) {
			# Ensure that every param in $params exists in the query.
			# If it doesn't, remove it from $params.
			foreach ($params as $name => $val)
				if (!strpos($query, $name))
					unset($params[$name]);

			$query = str_replace("`", "", str_replace("__", $this->prefix, $query));

			if ($this->adapter == "sqlite")
				$query = preg_replace("/ DEFAULT CHARSET=utf8/i", "", preg_replace("/AUTO_INCREMENT/i", "AUTOINCREMENT", $query));

			++$this->queries;

			return new Query($query, $params);
		}

		/**
		 * Function: count
		 * Performs a counting query and returns the number of matching rows.
		 */
		public function count($tables, $conds = null, $params = array(), $left_join = null) {
			return $this->query(QueryBuilder::build_count($tables, $conds, $left_join), $params)->fetchColumn();
		}

		/**
		 * Function: select
		 * Performs a SELECT with given criteria and returns the query result object.
		 */
		public function select($tables, $fields = "*", $conds = null, $order = null, $params = array(), $limit = null, $offset = null, $group = null, $left_join = null) {
			return $this->query(QueryBuilder::build_select($tables, $fields, $conds, $order, $limit, $offset, $group, $left_join), $params);
		}

		/**
		 * Function: insert
		 * Performs an INSERT with given data.
		 */
		public function insert($table, $data, $params = array()) {
			return $this->query(QueryBuilder::build_insert($table, $data), $params);
		}

		/**
		 * Function: update
		 * Performs an UDATE with given criteria and data.
		 */
		public function update($table, $conds, $data, $params = array()) {
			return $this->query(QueryBuilder::build_update($table, $conds, $data), $params);
		}

		/**
		 * Function: delete
		 * Performs a DELETE with given criteria.
		 */
		public function delete($table, $conds, $params = array()) {
			return $this->query(QueryBuilder::build_delete($table, $conds), $params);
		}

		/**
		 * Function: latest
		 * Returns the last inserted ID.
		 */
		public function latest() {
			switch($this->interface) {
				case "pdo":
					return $this->db->lastInsertId();
					break;
				case "mysqli":
					return $this->db->insert_id;
					break;
				case "mysql":
					return @mysql_insert_id();
					break;
			}
		}

		/**
		 * Function: current
		 * Returns a singleton reference to the current connection.
		 */
		public static function & current() {
			static $instance = null;
			return $instance = (empty($instance)) ? new self() : $instance ;
		}
	}

	$sql = SQL::current();
