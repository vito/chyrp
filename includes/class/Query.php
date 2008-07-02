<?php
	class Query {
		public $query = "";

		public function __construct($query, $params = array()) {
			$this->db =& SQL::current()->db;
			$this->interface =& SQL::current()->interface;

			switch($this->interface) {
				case "pdo":
					try {
						$this->query = $this->db->prepare($query);
						$result = $this->query->execute($params);
						$this->query->setFetchMode(PDO::FETCH_ASSOC);
						if (!$result) throw new PDOException;
					} catch (PDOException $error) {
						$message = preg_replace("/SQLSTATE\[.*?\]: .+ [0-9]+ (.*?)/", "\\1", $error->getMessage());

						if (XML_RPC or $throw_exceptions)
							throw new Exception($message);

						if (DEBUG)
							$message.= "\n\n".$query."\n\n<pre>".print_r($params, true)."</pre>\n\n<pre>".$error->getTraceAsString()."</pre>";

						$this->db = null;

						error(__("Database Error"), $message);
					}
					break;
				case "mysqli":
					foreach ($params as $name => $val)
						$query = str_replace($name, "'".$this->db->escape_string($val)."'", $query);
					$this->query = $this->db->query($query);
					break;
			}
		}

		public function fetchColumn($column = 0) {
			switch($this->interface) {
				case "pdo":
					return $this->query->fetchColumn($column);
					break;
				case "mysqli":
					$result = $this->query->fetch_array();
					return $result[$column];
					break;
			}
		}

		public function fetch($style = null, $orientation = null, $offset = null) {
			switch($this->interface) {
				case "pdo":
					return $this->query->fetch($style, $orientation, $offset);
				case "mysqli":
					return $this->query->fetch_array();
			}
		}

		public function fetchObject($style = null, $orientation = null, $offset = null) {
			switch($this->interface) {
				case "pdo":
					return $this->query->fetchObject($style, $orientation, $offset);
				case "mysqli":
					return $this->query->fetch_object();
			}
		}

		public function fetchAll() {
			switch($this->interface) {
				case "pdo":
					return $this->query->fetchAll($style, $column_index, $ctor_args);
				case "mysqli":
					$results = array();
					while ($row = $this->query->fetch_assoc())
						$results[] = $row;
					return $results;
			}
		}
	}
