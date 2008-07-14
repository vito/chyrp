<?php
	/**
	 * Class: Session
	 * Handles their session.
	 */
	class Session {
		static $data = "";

		/**
		 * Function: open
		 * Returns:
		 *     true
		 */
		static function open() {
			return true;
		}

		/**
		 * Function: close
		 * This is the very last action performed by Chyrp.
		 */
		static function close() {
			return true;
		}

		/**
		 * Function: read
		 * Reads their session from the database.
		 */
		static function read($id) {
			self::$data = SQL::current()->select("sessions",
			                                     "data",
			                                     "`__sessions`.`id` = :id",
			                                     "id",
			                                     array(":id" => $id),
			                                     null, null, null, null, true)->fetchColumn();

			return fallback(self::$data, "");
		}

		/**
		 * Function: write
		 * Writes their session to the database, or updates it if it already exists.
		 */
		static function write($id, $data) {
			if (empty($data) or $data == self::$data)
				return;

			$sql = SQL::current();
			$user_id = fallback(Visitor::current()->id, null, true);

			if ($sql->count("sessions", "`__sessions`.`id` = :id", array(":id" => $id)))
				$sql->update("sessions",
				             "`__sessions`.`id` = :id",
				             array("data" => ":data", "user_id" => ":user_id", "updated_at" => ":updated_at"),
				             array(":id" => $id, ":data" => $data, ":user_id" => $user_id, ":updated_at" => datetime()),
				             true);
			else
				$sql->insert("sessions",
				             array("id" => ":id", "data" => ":data", "user_id" => ":user_id", "created_at" => ":created_at"),
				             array(":id" => $id, ":data" => $data, ":user_id" => $user_id, ":created_at" => datetime()),
				             true);
		}

		/**
		 * Function: destroy
		 * Destroys their session.
		 */
		static function destroy($id) {
			if (SQL::current()->delete("sessions", "`__sessions`.`id` = :id", array(":id" => $id), true))
				return true;

			return false;
		}

		/**
		 * Function: gc
		 * Garbage collector. Removes sessions older than 30 days and sessions with no stored data.
		 */
		static function gc() {
			$thirty_days = now("+30 days");

			$delete = SQL::current()->delete("sessions",
			                                 "`__sessions`.`created_at` >= :thirty_days OR `__sessions`.`data` = '' OR `data` IS NULL",
			                                 array(":thirty_days" => datetime($thirty_days)),
			                                 true);
			return true;
		}
	}
