<?php
	$loaded_models = array();

	/**
	 * Class: Model
	 * The basis for the Models system.
	 */
	class Model {
		public $table;

		static function grab($model, $id, $options = array()) {
			global $loaded_models;

			$model_name = strtolower(get_class($model));

			if ($model_name == "visitor")
				$model_name = "user";

			$where = fallback($options["where"], "", true);
			$params = isset($options["params"]) ? $options["params"] : array();
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
				                     "id",
				                     $params,
				                     1)->fetch();
			else
				$read = $sql->select($model_name."s",
				                     "*",
				                     "`id` = :id",
				                     "id",
				                     array(
				                         ":id" => $id
				                     ),
				                     1)->fetch();

			if (!count($read) or !$read)
				return $model->no_results = true;

			foreach ($read as $key => $val)
				if (!is_int($key))
					$model->$key = $loaded_models[$model_name][$read["id"]][$key] = $val;
		}

		static function search($model, $options = array()) {
			global $paginate, $private, $enabled_feathers;

			if ($model == "Post")
				$order = "`pinned` desc, `created_at` desc, `id` desc";
			else
				$order = "`created_at` desc, `id` desc";

			$where = fallback($options["where"], ($model == "Post") ? $private.$enabled_feathers : null, true);
			$from = strtolower($model)."s";
			$params = fallback($options["params"], array(), true);
			$select = fallback($options["select"], "*", true);
			$order = fallback($options["order"], $order, true);
			$pagination = fallback($options["pagination"], true, true);
			$per_page = fallback($options["per_page"], Config::current()->posts_per_page, true);
			$page_var = fallback($options["page_var"], "page", true);

			$grab = (!$pagination) ? SQL::current()->select($from, $select, $where, $order, $params) : $paginate->select($from, $select, $where, $order, $per_page, $page_var, $params) ;

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
