<?php
	/**
	 * Class: Model
	 * The basis for the Models system.
	 */
	class Model {
		public $table = "test";

		static function grab($model, $options = array()) {
			global $paginate, $private, $enabled_feathers;

			$where = fallback($options["where"], $private.$enabled_feathers, true);
			$from = fallback($options["from"], strtolower($model)."s", true);
			$params = fallback($options["params"], array(), true);
			$select = fallback($options["select"], "*", true);
			$order = fallback($options["order"], "`pinned` desc, `created_at` desc, `id` desc", true);
			$paginate = fallback($options["paginate"], true, true);
			$per_page = fallback($options["per_page"], Config::current()->posts_per_page, true);
			$page_var = fallback($options["page_var"], "page", true);

			$paginate = new Pagination();
			$grab = (!$paginate) ? SQL::current()->select($from, $select, $where, $order, $params) : $paginate->select($from, $select, $where, $order, $per_page, $page_var, $params) ;

			$shown_dates = array();
			$results = array();
			foreach ($grab->fetchAll() as $result) {
				$result = new $model(null, array("read_from" => $result));

				$result->date_shown = in_array(when("m-d-Y", $result->created_at), $shown_dates);
				if (!in_array(when("m-d-Y", $result->created_at), $shown_dates))
					$shown_dates[] = when("m-d-Y", $result->created_at);

				$results[] = $result;
			}

			return $results;
		}
	}
