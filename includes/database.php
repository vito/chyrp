<?php
	/**
	 * Class: SQL
	 * Contains the database settings and functions for interacting with the SQL database.
	 */
	class SQL {
		/**
		 * Function: __construct
		 * The class constructor is private so there is only one connection.
		 */
		private function __construct() {
			$this->connected = false;
		}

		/**
		 * Integer: $queries
		 * Number of queries it takes to load the page.
		 */
		public $queries = 0;
		public $db;

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
		public function set($setting, $value) {
			if (isset($this->$setting) and $this->$setting == $value) return false; # No point in changing it

			# Add the PHP protection!
			$contents = "<?php header(\"Status: 403\"); exit(\"Access denied.\"); ?>\n";

			# Add the setting
			$this->yaml[$setting] = $value;

			if (isset($this->yaml[0]) and $this->yaml[0] == "--")
				unset($this->yaml[0]);

			# Generate the new YAML settings
			$contents.= Spyc::YAMLDump($this->yaml, false, 0);

			file_put_contents(INCLUDES_DIR."/database.yaml.php", $contents);
		}

		/**
		 * Function: connect
		 * Connects to the SQL database.
		 */
		public function connect($checking = false) {
			$this->load(INCLUDES_DIR."/database.yaml.php");
			if ($this->connected)
				return true;
			try {
				$this->db = new PDO($this->adapter.":host=".$this->host.";".((isset($this->port)) ? "port=".$this->port.";" : "")."dbname=".$this->database,
				                    $this->username,
				                    $this->password, array(PDO::ATTR_PERSISTENT => true));
				$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				if ($this->adapter == "mysql")
					$this->db->query("set names 'utf8';");
				$this->connected = true;
				return true;
			} catch (PDOException $error) {
				$message = preg_replace("/[A-Z]+\[[0-9]+\]: .+ [0-9]+ (.*?)/", "\\1", $error->getMessage());
				return ($checking) ? false : error(__("Database Error"), $message) ;
			}
		}

		/**
		 * Function: query
		 * Executes a query and increases <SQL->$queries>.
		 * If the query results in an error, it will die and show the error.
		 */
		public function query($query, $params = array(), $throw_exceptions = false) {
			$this->queries++;
			fallback($this->debug, array());

			try {
				$q = $this->db->prepare($query);
				$result = $q->execute($params);
				if (defined('DEBUG') and DEBUG) {
					#echo '<div class="sql_query" style="position: relative; z-index: 1000"><span style="background: rgba(0,0,0,.5); padding: 0 1px; border: 1px solid rgba(0,0,0,.25); color: white; font: 9px/14px normal \'Monaco\', monospace;">'.$query.'</span></div>';
					$trace = debug_backtrace();
					$target = $trace[$index = 0];

					while (strpos($target["file"], "database.php")) # Getting a traceback from this file is pretty
						$target = $trace[$index++];                 # useless (mostly when using $sql->select() and such)

					$this->debug[] = array("number" => $this->queries, "file" => str_replace(MAIN_DIR."/", "", $target["file"]), "line" => $target["line"], "string" => normalize(str_replace(array_keys($params), array_values($params), $query)));
				}
				if (!$result) throw PDOException();
			} catch (PDOException $error) {
				$message = preg_replace("/SQLSTATE\[[0-9]+\]: .+ [0-9]+ (.*?)/", "\\1", $error->getMessage());

				if (XML_RPC or $throw_exceptions)
					throw new Exception($message);

				error(__("Database Error"), $message);
			}

			return $q;
		}

		/**
		 * Function: count
		 * Performs a counting query and returns the number of matching rows.
		 */
		public function count($tables, $conds, $params = array())
		{
			return $this->query(QueryBuilder::build_count($tables, $conds), $params)->fetchColumn();
		}

		/**
		 * Function: select
		 * Performs a SELECT with given criteria and returns the query result object.
		 */
		public function select($tables, $fields, $conds, $order = null, $params = array(), $limit = null, $offset = null)
		{
			return $this->query(QueryBuilder::build_select($tables, $fields, $conds, $order, $limit, $offset), $params);
		}

		/**
		 * Function: insert
		 * Performs an INSERT with given data.
		 */
		public function insert($table, $data, $params = array())
		{
			return $this->query(QueryBuilder::build_insert($table, $data), $params);
		}

		/**
		 * Function: update
		 * Performs an UDATE with given criteria and data.
		 */
		public function update($table, $conds, $data, $params = array())
		{
			return $this->query(QueryBuilder::build_update($table, $conds, $data), $params);
		}

		/**
		 * Function: delete
		 * Performs a DELETE with given criteria.
		 */
		public function delete($table, $conds, $params = array())
		{
			return $this->query(QueryBuilder::build_delete($table, $conds), $params);
		}

		/**
		 * Function: quote
		 * Quotes the passed variable as needed for use in a query.
		 */
		public function quote($var) {
			return $this->db->quote($var);
		}

		/**
		 * Function: current
		 * Returns a singleton reference to the current connection.
		 */
		public static function & current() {
			static $instance = null;
			if (empty($instance))
				$instance = new self();
			return $instance;
		}
	}

	$sql = SQL::current();
