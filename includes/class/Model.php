<?php
	$loaded_models = array();

	/**
	 * Class: Model
	 * The basis for the Models system.
	 */
	class Model {
		static function grab($model, $id, $options = array()) {
			global $loaded_models;

			$model_name = strtolower(get_class($model));

			if ($model_name == "visitor")
				$model_name = "user";

			$where  = fallback($options["where"], "", true);
			$params = fallback($options["params"], array(), true);
			$order  = fallback($options["order"], "`id` desc", true);
			$offset = fallback($options["offset"], null, true);
			$read_from = (isset($options["read_from"])) ? $options["read_from"] : array() ;

			$sql = SQL::current();
			if ((!empty($read_from) && $read_from))
				$read = $read_from;
			elseif (isset($loaded_models[$model_name][$id]))
				$read = $loaded_models[$model_name][$id];
			elseif (!empty($where))
				$read = $sql->select($model_name."s",
				                     "*",
				                     $where,
				                     $order,
				                     $params,
				                     1,
				                     $offset)->fetch();
			else
				$read = $sql->select($model_name."s",
				                     "*",
				                     "`id` = :id",
				                     $order,
				                     array(
				                         ":id" => $id
				                     ),
				                     1,
				                     $offset)->fetch();

			if (!count($read) or !$read)
				return $model->no_results = true;

			foreach ($read as $key => $val)
				if (!is_int($key))
					$model->$key = $loaded_models[$model_name][$read["id"]][$key] = $val;

			if (isset($model->updated_at))
				$model->updated = $model->updated_at != "0000-00-00 00:00:00";
		}

		static function search($model, $options = array()) {
			global $paginate;

			$where      = fallback($options["where"], null, true);
			$from       = fallback($options["from"], strtolower($model)."s", true);
			$params     = fallback($options["params"], array(), true);
			$select     = fallback($options["select"], "*", true);
			$order      = fallback($options["order"], "`created_at` desc, `id` desc", true);
			$offset     = fallback($options["offset"], null, true);
			$limit      = fallback($options["limit"], null, true);
			$pagination = fallback($options["pagination"], true, true);
			$per_page   = fallback($options["per_page"], Config::current()->posts_per_page, true);
			$page_var   = fallback($options["page_var"], "page", true);

			$grab = (!$pagination) ?
			         SQL::current()->select($from, $select, $where, $order, $params, $limit, $offset) :
			         $paginate->select($from, $select, $where, $order, $per_page, $page_var, $params) ;

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
