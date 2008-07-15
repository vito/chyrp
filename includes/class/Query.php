<?php
	/**
	 * Class: Query
	 * Handles a query based on the <SQL.interface>.
	 */
	class Query {
		# Variable: $query
		# Holds the current query.
		public $query = "";

		/**
		 * Function: __construct
		 * Creates a query based on the <SQL.interface>.
		 *
		 * Parameters:
		 *     $query - Query to execute.
		 *     $params - An associative array of parameters used in the query.
		 */
		public function __construct($query, $params = array(), $throw_exceptions = false) {
			$this->db =& SQL::current()->db;
			$this->interface =& SQL::current()->interface;

			$this->params = $params;
			$this->throw_exceptions = $throw_exceptions;

			if (defined('DEBUG') and DEBUG) {
				$trace = debug_backtrace();
				$target = $trace[$index = 0];

				# Getting a traceback from these files doesn't help much.
				while (match(array("/SQL\.php/", "/Model\.php/", "/\/model\//"), $target["file"]))
					if (isset($trace[$index + 1]["file"]))
						$target = $trace[$index++];
					else
						break;

				SQL::current()->debug[] = array("number" => SQL::current()->queries,
				                                "file" => str_replace(MAIN_DIR."/", "", $target["file"]),
				                                "line" => $target["line"],
				                                "query" => str_replace("\n", "\\n", str_replace(array_keys($params), array_values($params), $query)));
			}

			switch($this->interface) {
				case "pdo":
					try {
						$this->query = $this->db->prepare($query);
						$result = $this->query->execute($params);
						$this->query->setFetchMode(PDO::FETCH_ASSOC);
						$this->queryString = $this->query->queryString;
						if (!$result) throw new PDOException;
					} catch (PDOException $error) {
						$this->handle($error);
					}
					break;
				case "mysqli":
					foreach ($params as $name => $val)
						$query = preg_replace("/{$name}([^a-zA-Z0-9_]|$)/", "'".$this->db->escape_string($val)."'\\1", $query);

					$this->queryString = $query;

					try {
						if (!$this->query = $this->db->query($query))
							throw new Exception($this->db->error);
					} catch (Exception $error) {
						$this->handle($error);
					}
					break;
				case "mysql":
					foreach ($params as $name => $val)
						$query = preg_replace("/{$name}([^a-zA-Z0-9_]|$)/", "'".mysql_real_escape_string($val)."'\\1", $query);

					$this->queryString = $query;

					try {
						if (!$this->query = @mysql_query($query))
							throw new Exception(mysql_error());
					} catch (Exception $error) {
						$this->handle($error);
					}

					break;
				case "sqlite":
					foreach ($params as $name => $val)
						$query = preg_replace("/{$name}([^a-zA-Z0-9_]|$)/", "'".sqlite_escape_string($val)."'\\1", $query);

					$this->queryString = $query;

					try {
						if (!$this->query = @$this->db->query($query, SQLITE_BOTH, $this->error))
							throw new Exception($this->error);
					} catch (Exception $error) {
						$this->handle($error);
					}

					break;
			}
		}

		/**
		 * Function: fetchColumn
		 * Fetches a column of the first row.
		 *
		 * Parameters:
		 *     $column - The offset of the column to grab. Default 0.
		 */
		public function fetchColumn($column = 0) {
			switch($this->interface) {
				case "pdo":
					return $this->query->fetchColumn($column);
				case "mysqli":
					$result = $this->query->fetch_array();
					return $result[$column];
				case "mysql":
					$result = mysql_fetch_array($this->query);
					return $result[$column];
				case "sqlite":
					$result = $this->query->fetch();
					return $result[$column];
			}
		}

		/**
		 * Function: fetch
		 * Returns the first row as an array.
		 */
		public function fetch() {
			switch($this->interface) {
				case "pdo":
					return $this->query->fetch();
				case "mysqli":
					return $this->query->fetch_array();
				case "mysql":
					return mysql_fetch_array($this->query);
				case "sqlite":
					return $this->query->fetch();
			}
		}

		/**
		 * Function: fetchObject
		 * Returns the first row as an object.
		 */
		public function fetchObject() {
			switch($this->interface) {
				case "pdo":
					return $this->query->fetchObject();
				case "mysqli":
					return $this->query->fetch_object();
				case "mysql":
					return mysql_fetch_object($this->query);
				case "sqlite":
					return $this->query->fetchObject();
			}
		}

		/**
		 * Function: fetchAll
		 * Returns an array of every result.
		 */
		public function fetchAll($style = null) {
			switch($this->interface) {
				case "pdo":
					return $this->query->fetchAll($style);
				case "mysqli":
					$results = array();

					while ($row = $this->query->fetch_assoc())
						$results[] = $row;

					return $results;
				case "mysql":
					$results = array();

					while ($row = mysql_fetch_assoc($this->query))
						$results[] = $row;

					return $results;
				case "sqlite":
					$results = array();

					while ($row = $this->query->fetch())
						$results[] = $row;

					return $results;
			}
		}

		public function handle($error) {
			$message = $error->getMessage();

			if (DEBUG)
				$message.= "\n\n<pre>".print_r($this->queryString, true)."\n\n<pre>".print_r($this->params, true)."</pre>\n\n<pre>".$error->getTraceAsString()."</pre>";

			if (XML_RPC or $this->throw_exceptions)
				throw new Exception($message);

			error(__("Database Error"), $message);
		}
	}
