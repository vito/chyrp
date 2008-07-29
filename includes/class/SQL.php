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

		# Variable: $db
		# Holds the currently running database.
		public $db;

		# Variable: $error
		# Holds an error message from the last attempted query.
		public $error = "";

		/**
		 * Function: __construct
		 * The class constructor is private so there is only one connection.
		 */
		private function __construct() {
			$database = (!UPGRADING) ?
			            fallback(Config::current()->database, array(), true) :
			            Config::get("database") ;

			if (!empty($database))
				foreach ($database as $setting => $value)
					$this->$setting = $value;

			$this->connected = false;
		}

		/**
		 * Function: method
		 * Returns the proper method of connecting and interacting with the database.
		 */
		public function method() {
			# We really don't need PDO anymore, since we have the two we supported with it hardcoded (kinda).
			# Keeping this here for when/if we decide to add support for more database engines, like Postgres and MSSQL.
			#if (class_exists("PDO") and (in_array("mysql", PDO::getAvailableDrivers()) or in_array("sqlite", PDO::getAvailableDrivers())))
			#	return "pdo";

			if (isset($this->adapter)) {
				if ($this->adapter == "mysql" and class_exists("MySQLi"))
					return "mysqli";
				elseif ($this->adapter == "mysql" and function_exists("mysql_connect"))
					return "mysql";
				elseif ($this->adapter == "sqlite" and in_array("sqlite", PDO::getAvailableDrivers()))
					return "pdo";
			} else
				if (class_exists("MySQLi"))
					return "mysqli";
				elseif (function_exists("mysql_connect"))
					return "mysql";
				elseif (in_array("mysql", PDO::getAvailableDrivers()))
					return "pdo";

			exit(__("Cannot find a way to connect to a database."));
		}

		/**
		 * Function: set
		 * Sets a variable's value.
		 *
		 * Parameters:
		 *     $setting - The setting name.
		 *     $value - The new value. Can be boolean, numeric, an array, a string, etc.
		 *     $overwrite - If the setting exists and is the same value, should it be overwritten?
		 */
		public function set($setting, $value, $overwrite = true) {
			if (isset($this->$setting) and $this->$setting == $value and !$overwrite and !UPGRADING)
				return false;

			if (!UPGRADING)
				$config = Config::current();

			$database = (!UPGRADING) ? $config->database : Config::get("database") ;

			# Add the setting
			$database[$setting] = $this->$setting = $value;

			return (!UPGRADING) ? $config->set("database", $database) : Config::set("database", $database) ;
		}

		/**
		 * Function: connect
		 * Connects to the SQL database.
		 *
		 * Parameters:
		 *     $checking - Return a boolean of whether or not it could connect, instead of showing an error.
		 */
		public function connect($checking = false) {
			if ($this->connected)
				return true;

			if (!isset($this->database))
				self::__construct();

			switch($this->method()) {
				case "pdo":
					try {
						if (empty($this->database))
							throw new PDOException("No database specified.");

						if ($this->adapter == "sqlite") {
							$this->db = new PDO("sqlite:".$this->database, null, null, array(PDO::ATTR_PERSISTENT => true));
							$this->db->sqliteCreateFunction("YEAR", array($this, "year_from_datetime"), 1);
							$this->db->sqliteCreateFunction("MONTH", array($this, "month_from_datetime"), 1);
							$this->db->sqliteCreateFunction("DAY", array($this, "day_from_datetime"), 1);
						} else
							$this->db = new PDO($this->adapter.":host=".$this->host.";".((isset($this->port)) ? "port=".$this->port.";" : "")."dbname=".$this->database,
							                    $this->username,
							                    $this->password,
							                    array(PDO::ATTR_PERSISTENT => true));

						$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					} catch (PDOException $error) {
						$this->error = $error->getMessage();
						return ($checking) ? false : error(__("Database Error"), $this->error) ;
					}
					break;
				case "mysqli":
					$this->db = @new MySQLi($this->host, $this->username, $this->password, $this->database);
					$this->error = mysqli_connect_error();

					if (mysqli_connect_errno())
						return ($checking) ? false : error(__("Database Error"), $this->error) ;

					break;
				case "mysql":
					$this->db = @mysql_connect($this->host, $this->username, $this->password);
					$this->error = mysql_error();

					if (!$this->db or !@mysql_select_db($this->database))
						return ($checking) ? false : error(__("Database Error"), $this->error) ;

					break;
			}

			if ($this->adapter == "mysql")
				new Query("SET NAMES 'utf8'");

			return $this->connected = true;
		}

		/**
		 * Function: query
		 * Executes a query and increases <SQL->$queries>.
		 * If the query results in an error, it will die and show the error.
		 *
		 * Parameters:
		 *     $query - Query to execute.
		 *     $params - An associative array of parameters used in the query.
		 *     $throw_exceptions - Should an exception be thrown if the query fails?
		 */
		public function query($query, $params = array(), $throw_exceptions = false) {
			# Ensure that every param in $params exists in the query.
			# If it doesn't, remove it from $params.
			foreach ($params as $name => $val)
				if (!strpos($query, $name))
					unset($params[$name]);

			$query = str_replace("`", "", str_replace("__", $this->prefix, $query));

			if ($this->adapter == "sqlite")
				$query = str_ireplace(" DEFAULT CHARSET=utf8", "", str_ireplace("AUTO_INCREMENT", "AUTOINCREMENT", $query));

			++$this->queries;

			$query = new Query($query, $params, $throw_exceptions);

			return (!$query->query and UPGRADING) ? false : $query ;
		}

		/**
		 * Function: count
		 * Performs a counting query and returns the number of matching rows.
		 *
		 * Parameters:
		 *     $tables - An array (or string) of tables to count results on.
		 *     $conds - An array (or string) of conditions to match.
		 *     $params - An associative array of parameters used in the query.
		 */
		public function count($tables, $conds = null, $params = array(), $throw_exceptions = false) {
			return $this->query(QueryBuilder::build_count($tables, $conds), $params, $throw_exceptions)->fetchColumn();
		}

		/**
		 * Function: select
		 * Performs a SELECT with given criteria and returns the query result object.
		 *
		 * Parameters:
		 *     $tables - An array (or string) of tables to grab results from.
		 *     $fields - Fields to select.
		 *     $conds - An array (or string) of conditions to match.
		 *     $order - ORDER BY statement. Can be an array.
		 *     $params - An associative array of parameters used in the query.
		 *     $limit - Limit for results.
		 *     $offset - Offset for the select statement.
		 *     $group - GROUP BY statement. Can be an array.
		 *     $left_join - An array of additional LEFT JOINs.
		 */
		public function select($tables, $fields = "*", $conds = null, $order = null, $params = array(), $limit = null, $offset = null, $group = null, $left_join = null, $throw_exceptions = false) {
			return $this->query(QueryBuilder::build_select($tables, $fields, $conds, $order, $limit, $offset, $group, $left_join), $params, $throw_exceptions);
		}

		/**
		 * Function: insert
		 * Performs an INSERT with given data.
		 *
		 * Parameters:
		 *     $table - Table to insert to.
		 *     $data - An associative array of data to insert.
		 *     $params - An associative array of parameters used in the query.
		 */
		public function insert($table, $data, $params = array(), $throw_exceptions = false) {
			return $this->query(QueryBuilder::build_insert($table, $data), $params, $throw_exceptions);
		}

		/**
		 * Function: replace
		 * Performs a REPLACE with given data.
		 *
		 * Parameters:
		 *     $table - Table to insert to.
		 *     $data - An associative array of data to insert.
		 *     $params - An associative array of parameters used in the query.
		 */
		public function replace($table, $data, $params = array(), $throw_exceptions = false) {
			return $this->query(QueryBuilder::build_replace($table, $data), $params, $throw_exceptions);
		}

		/**
		 * Function: update
		 * Performs an UDATE with given criteria and data.
		 *
		 * Parameters:
		 *     $table - Table to update.
		 *     $conds - Rows to update.
		 *     $data - An associative array of data to update.
		 *     $params - An associative array of parameters used in the query.
		 */
		public function update($table, $conds, $data, $params = array(), $throw_exceptions = false) {
			return $this->query(QueryBuilder::build_update($table, $conds, $data), $params, $throw_exceptions);
		}

		/**
		 * Function: delete
		 * Performs a DELETE with given criteria.
		 *
		 * Parameters:
		 *     $table - Table to delete from.
		 *     $conds - Rows to delete..
		 *     $params - An associative array of parameters used in the query.
		 */
		public function delete($table, $conds, $params = array(), $throw_exceptions = false) {
			return $this->query(QueryBuilder::build_delete($table, $conds), $params, $throw_exceptions);
		}

		/**
		 * Function: latest
		 * Returns the last inserted ID.
		 */
		public function latest() {
			switch($this->method()) {
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

		/**
		 * Function: year_from_datetime
		 * Returns the year of a datetime.
		 */
		public function year_from_datetime($datetime) {
			return when("Y", $datetime);
		}

		/**
		 * Function: month_from_datetime
		 * Returns the month of a datetime.
		 */
		public function month_from_datetime($datetime) {
			return when("m", $datetime);
		}

		/**
		 * Function: day_from_datetime
		 * Returns the day of a datetime.
		 */
		public function day_from_datetime($datetime) {
			return when("d", $datetime);
		}
	}

	$sql = SQL::current();
