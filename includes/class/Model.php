<?php
	$loaded_models = array();

	/**
	 * Class: Model
	 * The basis for the Models system.
	 */
	class Model {
		static function grab($model, $id, $options = array()) {
			global $loaded_models, $action;

			$model_name = strtolower(get_class($model));

			if ($model_name == "visitor")
				$model_name = "user";

			fallback($options["from"], ($model_name == "visitor" ? "users" : $model_name."s"));
			fallback($options["select"], "*");
			fallback($options["where"], "");
			fallback($options["params"], array());
			fallback($options["order"], "`id` desc");
			fallback($options["offset"], null);
			fallback($options["read_from"], array());

			$options = Trigger::current()->filter($action."_".$model_name."_grab", $options);

			$sql = SQL::current();
			if ((!empty($options["read_from"])))
				$read = $options["read_from"];
			elseif (isset($loaded_models[$model_name][$id]))
				$read = $loaded_models[$model_name][$id];
			elseif (!empty($where))
				$read = $sql->select($options["from"],
				                     $options["select"],
				                     $options["where"],
				                     $options["order"],
				                     $options["params"],
				                     $options["limit"],
				                     $options["offset"])->fetch();
			else
				$read = $sql->select($options["from"],
				                     $options["select"],
				                     "`id` = :id",
				                     $options["order"],
				                     array(
				                         ":id" => $id
				                     ),
				                     1)->fetch();

			if (!count($read) or !$read)
				return $model->no_results = true;

			foreach ($read as $key => $val)
				if (!is_int($key))
					$model->$key = $loaded_models[$model_name][$read["id"]][$key] = $val;

			if (isset($model->updated_at))
				$model->updated = $model->updated_at != "0000-00-00 00:00:00";
		}

		static function search($model, $options = array()) {
			global $paginate, $action;

			$model_name = strtolower($model);

			if ($model_name == "visitor")
				$model_name = "user";

			fallback($options["where"], null);
			fallback($options["from"], strtolower($model)."s");
			fallback($options["params"], array());
			fallback($options["select"], "*");
			fallback($options["order"], "`created_at` desc, `id` desc");
			fallback($options["offset"], null);
			fallback($options["limit"], null);
			fallback($options["pagination"], true);
			fallback($options["per_page"], Config::current()->posts_per_page);
			fallback($options["page_var"], "page");

			$options = Trigger::current()->filter($action."_".$model_name."s_get", $options);

			$grab = (!$options["pagination"]) ?
			         SQL::current()->select($options["from"], $options["select"], $options["where"], $options["order"], $options["params"], $options["limit"], $options["offset"]) :
			         $paginate->select($options["from"], $options["select"], $options["where"], $options["order"], $options["per_page"], $options["page_var"], $options["params"]) ;

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
		 *     $id - The object to delete.
		 */
		static function destroy($model, $id) {
			$class = $model;
			$model = strtolower($model);
			if (Trigger::current()->exists("delete_".$model))
				Trigger::current()->call("delete_".$model, new $class($id));

			SQL::current()->delete($model."s", "`id` = :id", array(":id" => $id));
		}
	}
