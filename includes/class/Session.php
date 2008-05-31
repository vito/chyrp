<?php
	ini_set("session.gc_probability", 100);

	class Session {
		static function open() {
			return true;
		}

		static function close() {
			SQL::current()->db = null; # Close the database.
			return true;
		}

		static function read($id) {
			$data = SQL::current()->select("sessions", "data", "`id` = :id", "id", array(":id" => $id))->fetchColumn();
			return fallback($data, "", true);
		}

		static function write($id, $data) {
			$sql = SQL::current();
			$user_id = fallback(Visitor::current()->id, null, true);

			if ($sql->count("sessions", "`id` = :id", array(":id" => $id)))
				$sql->update("sessions",
				             "`id` = :id",
				             array("data" => ":data", "user_id" => ":user_id", "updated_at" => ":updated_at"),
				             array(":id" => $id, ":data" => $data, ":user_id" => $user_id, ":updated_at" => datetime()));
			else
				$sql->insert("sessions",
				             array("id" => ":id", "data" => ":data", "user_id" => ":user_id", "created_at" => ":created_at"),
				             array(":id" => $id, ":data" => $data, ":user_id" => $user_id, ":created_at" => datetime()));
		}

		static function destroy($id) {
			return SQL::current()->delete("sessions", "`id` = :id", array(":id" => $id));
		}

		static function gc() {
			$thirty_days = time() + Config::current()->time_offset + (60 * 60 * 24 * 30);
			$delete = SQL::current()->delete("sessions", "`created_at` >= :thirty_days OR `data` IS NULL", array(":thirty_days" => datetime($thirty_days)));
			return true;
		}
	}
