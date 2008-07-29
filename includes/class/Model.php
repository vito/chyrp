<?php
	/**
	 * Class: Model
	 * The basis for the Models system.
	 */
	class Model {
		# Array: $loaded_models
		# Caches every loaded module into an array of results.
		static $loaded_models = array();

		/**
		 * Function: grab
		 * Grabs a single model from the database.
		 *
		 * Parameters:
		 *     $model - The instantiated model class to pass the object to (e.g. Post).
		 *     $id - The ID of the model to grab. Can be null.
		 *     $options - An array of options, mostly SQL things.
		 *
		 * Options:
		 *     select - What to grab from the table. @(modelname)s@ by default.
		 *     from - Which table(s) to grab from? @(modelname)s.*@ by default.
		 *     left_join - A @LEFT JOIN@ associative array. Example: @array("table" => "foo", "where" => "foo = :bar")@
		 *     where - A string or array of conditions. @array("__(modelname)s.id = :id")@ by default.
		 *     params - An array of parameters to pass to PDO. @array(":id" => $id)@ by default.
		 *     group - A string or array of "GROUP BY" conditions.
		 *     order - What to order the SQL result by. @__(modelname)s.id DESC@ by default.
		 *     offset - Offset for SQL query.
		 *     read_from - An array to read from instead of performing another query.
		 */
		protected static function grab($model, $id, $options = array()) {
			$model_name = strtolower(get_class($model));

			if ($model_name == "visitor")
				$model_name = "user";

			# Is this model already in the cache?
			if (isset(self::$loaded_models[$model_name][$id])) {
				foreach (self::$loaded_models[$model_name][$id] as $key => $val)
					$model->$key = $val;

				$model->no_results = false;

				if (isset(self::$loaded_models[$model_name][$id]["queryString"]))
					$model->queryString = self::$loaded_models[$model_name][$id]["queryString"];

				if (isset(self::$loaded_models[$model_name][$id]["updated"]))
					$model->updated = self::$loaded_models[$model_name][$id]["updated"];

				return;
			}

			fallback($options["select"], "*");
			fallback($options["from"], ($model_name == "visitor" ? "users" : pluralize($model_name)));
			fallback($options["left_join"], array());
			fallback($options["where"], array());
			fallback($options["params"], array());
			fallback($options["group"], array());
			fallback($options["order"], "id DESC");
			fallback($options["offset"], null);
			fallback($options["read_from"], array());

			$options["where"] = (array) $options["where"];
			$options["from"] = (array) $options["from"];
			$options["select"] = (array) $options["select"];

			if (is_numeric($id)) {
				$options["where"][] = "id = :id";
				$options["params"][":id"] = $id;
			}

			$trigger = Trigger::current();
			$trigger->filter($options, $model_name."_grab");

			$sql = SQL::current();
			if (!empty($options["read_from"]))
				$read = $options["read_from"];
			else {
				$query = $sql->select($options["from"],
				                     $options["select"],
				                     $options["where"],
				                     $options["order"],
				                     $options["params"],
				                     1,
				                     $options["offset"],
				                     $options["group"],
				                     $options["left_join"]);
				$read = $query->fetch();
			}

			if (!count($read) or !$read)
				return $model->no_results = true;
			else
				$model->no_results = false;

			foreach ($read as $key => $val)
				if (!is_int($key))
					$model->$key = self::$loaded_models[$model_name][$read["id"]][$key] = $val;

			if (isset($query) and isset($query->query->queryString))
				$model->queryString = self::$loaded_models[$model_name][$read["id"]]["queryString"] = $query->query->queryString;

			if (isset($model->updated_at))
				$model->updated = self::$loaded_models[$model_name][$read["id"]]["updated"] = $model->updated_at != "0000-00-00 00:00:00";
		}

		/**
		 * Function: search
		 * Returns an array of model objects that are found by the $options array.
		 *
		 * Parameters:
		 *     $options - An array of options, mostly SQL things.
		 *     $options_for_object - An array of options for the instantiation of the model.
		 *
		 * Options:
		 *     select - What to grab from the table. @(modelname)s@ by default.
		 *     from - Which table(s) to grab from? @(modelname)s.*@ by default.
		 *     left_join - A @LEFT JOIN@ associative array. Example: @array("table" => "foo", "where" => "foo = :bar")@
		 *     where - A string or array of conditions. @array("__(modelname)s.id = :id")@ by default.
		 *     params - An array of parameters to pass to PDO. @array(":id" => $id)@ by default.
		 *     group - A string or array of "GROUP BY" conditions.
		 *     order - What to order the SQL result by. @__(modelname)s.id DESC@ by default.
		 *     offset - Offset for SQL query.
		 *     limit - Limit for SQL query.
		 *
		 * See Also:
		 *     <Model.grab>
		 */
		protected static function search($model, $options = array(), $options_for_object = array()) {
			$model_name = strtolower($model);

			if ($model_name == "visitor")
				$model_name = "user";

			fallback($options["select"], "*");
			fallback($options["from"], pluralize(strtolower($model)));
			fallback($options["left_join"], array());
			fallback($options["where"], null);
			fallback($options["params"], array());
			fallback($options["group"], array());
			fallback($options["order"], "id DESC");
			fallback($options["offset"], null);
			fallback($options["limit"], null);
			fallback($options["placeholders"], false);

			$options["where"]  = (array) $options["where"];
			$options["from"]   = (array) $options["from"];
			$options["select"] = (array) $options["select"];

			$trigger = Trigger::current();
			$trigger->filter($options, pluralize($model_name)."_get");

			$grab = SQL::current()->select($options["from"],
			                               $options["select"],
			                               $options["where"],
			                               $options["order"],
			                               $options["params"],
			                               $options["limit"],
			                               $options["offset"],
			                               $options["group"],
			                               $options["left_join"]);

			$shown_dates = array();
			$results = array();
			foreach ($grab->fetchAll() as $result) {
				if ($options["placeholders"]) {
					$results[] = $result;
					continue;
				}

				$options_for_object["read_from"] = $result;
				$result = new $model(null, $options_for_object);

				if (isset($result->created_at)) {
					$pinned = (isset($result->pinned) and $result->pinned);
					$shown = in_array(when("m-d-Y", $result->created_at), $shown_dates);

					$result->first_of_day = (!$pinned and !$shown and !AJAX);

					if (!$pinned and !$shown)
						$shown_dates[] = when("m-d-Y", $result->created_at);
				}

				$results[] = $result;
			}

			return ($options["placeholders"]) ? array($results, $model_name) : $results ;
		}

		/**
		 * Function: delete
		 * Deletes a given object. Calls the "delete_(model)" trigger with the objects ID.
		 *
		 * Parameters:
		 *     $model - The model name.
		 *     $id - The ID of the object to delete.
		 */
		protected static function destroy($model, $id) {
			$class = $model;
			$model = strtolower($model);
			if (Trigger::current()->exists("delete_".$model))
				Trigger::current()->call("delete_".$model, new $class($id));

			SQL::current()->delete(pluralize($model), "id = :id", array(":id" => $id));
		}
	}
