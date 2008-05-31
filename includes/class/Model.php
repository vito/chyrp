<?php
	$loaded_models = array();

	/**
	 * Class: Model
	 * The basis for the Models system.
	 */
	class Model {
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
		 *     where - A string or array of conditions. @array("`__(modelname)s`.`id` = :id")@ by default.
		 *     params - An array of parameters to pass to PDO. @array(":id" => $id)@ by default.
		 *     group - A string or array of "GROUP BY" conditions.
		 *     order - What to order the SQL result by. @`__(modelname)s`.`id` DESC@ by default.
		 *     offset - Offset for SQL query.
		 *     read_from - An array to read from instead of performing another query.
		 */
		protected static function grab($model, $id, $options = array()) {
			global $loaded_models, $action;
			$model_name = strtolower(get_class($model));

			if ($model_name == "visitor")
				$model_name = "user";

			fallback($options["select"], "__".$model_name."s.*");
			fallback($options["from"], ($model_name == "visitor" ? "users" : $model_name."s"));
			fallback($options["left_join"], array());
			fallback($options["where"], array("`__".$model_name."s`.`id` = :id"));
			fallback($options["params"], array(":id" => $id));
			fallback($options["group"], array());
			fallback($options["order"], "`__".$model_name."s`.`id` DESC");
			fallback($options["offset"], null);
			fallback($options["read_from"], array());

			$options["where"] = (array) $options["where"];
			$options["from"] = (array) $options["from"];
			$options["select"] = (array) $options["select"];

			$trigger = Trigger::current();
			$options = $trigger->filter($action."_".$model_name."_grab", $options);
			$options = $trigger->filter($model_name."_grab", $options);

			$sql = SQL::current();
			if ((!empty($options["read_from"])))
				$read = $options["read_from"];
			elseif (isset($loaded_models[$model_name][$id]))
				$read = $loaded_models[$model_name][$id];
			else
				$read = $sql->select($options["from"],
				                     $options["select"],
				                     $options["where"],
				                     $options["order"],
				                     $options["params"],
				                     1,
				                     $options["offset"],
				                     $options["group"],
				                     $options["left_join"])->fetch();

			if (!count($read) or !$read)
				return $model->no_results = true;

			foreach ($read as $key => $val)
				if (!is_int($key))
					$model->$key = $loaded_models[$model_name][$read["id"]][$key] = $val;

			if (isset($model->updated_at))
				$model->updated = $model->updated_at != "0000-00-00 00:00:00";
		}

		/**
		 * Function: search
		 * Returns an array of model objects that are found by the $options array.
		 *
		 * Parameters:
		 *     $options - An array of options, mostly SQL things.
		 *
		 * Options:
		 *     select - What to grab from the table. @(modelname)s@ by default.
		 *     from - Which table(s) to grab from? @(modelname)s.*@ by default.
		 *     left_join - A @LEFT JOIN@ associative array. Example: @array("table" => "foo", "where" => "foo = :bar")@
		 *     where - A string or array of conditions. @array("`__(modelname)s`.`id` = :id")@ by default.
		 *     params - An array of parameters to pass to PDO. @array(":id" => $id)@ by default.
		 *     group - A string or array of "GROUP BY" conditions.
		 *     order - What to order the SQL result by. @`__(modelname)s`.`id` DESC@ by default.
		 *     offset - Offset for SQL query.
		 *     limit - Limit for SQL query.
		 *     pagination - Whether or not to paginate the results.
		 *     per_page - If pagination is @true@, how many results per page?
		 *     page_var - If pagination is @true@, what to name the page variable?
		 */
		protected static function search($model, $options = array()) {
			global $paginate, $action;

			$model_name = strtolower($model);

			if ($model_name == "visitor")
				$model_name = "user";

			fallback($options["select"], "__".strtolower($model)."s.*");
			fallback($options["from"], strtolower($model)."s");
			fallback($options["left_join"], array());
			fallback($options["where"], null);
			fallback($options["params"], array());
			fallback($options["group"], array());
			fallback($options["order"], "`__".strtolower($model)."s`.`id` DESC");
			fallback($options["offset"], null);
			fallback($options["limit"], null);
			fallback($options["pagination"], true);
			fallback($options["per_page"], Config::current()->posts_per_page);
			fallback($options["page_var"], "page");

			$options["where"] = (array) $options["where"];
			$options["from"] = (array) $options["from"];
			$options["select"] = (array) $options["select"];

			$trigger = Trigger::current();
			$options = $trigger->filter($action."_".$model_name."s_get", $options);
			$options = $trigger->filter($model_name."s_get", $options);

			$grab = (!$options["pagination"]) ?
			         SQL::current()->select($options["from"], $options["select"], $options["where"], $options["order"], $options["params"], $options["limit"], $options["offset"], $options["group"], $options["left_join"]) :
			         $paginate->select($options["from"], $options["select"], $options["where"], $options["order"], $options["per_page"], $options["page_var"], $options["params"], $options["group"], $options["left_join"]) ;

			$shown_dates = array();
			$results = array();
			foreach ($grab->fetchAll() as $result) {
				$result = new $model(null, array("read_from" => $result));

				if (isset($result->created_at)) {
					$result->date_shown = in_array(when("m-d-Y", $result->created_at), $shown_dates);
					if (!in_array(when("m-d-Y", $result->created_at), $shown_dates))
						$shown_dates[] = when("m-d-Y", $result->created_at);
				}

				$results[] = $result;
			}

			return $results;
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

			SQL::current()->delete($model."s", "`id` = :id", array(":id" => $id));
		}
	}
