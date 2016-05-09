<?php
    /**
     * Class: Model
     * The basis for the Models system.
     */
    class Model {
        # Array: $caches
        # Caches every loaded module into a clone of the object.
        static $caches = array();

        # Array: $belongs_to
        # An array of models that this Model belongs to.
        # This model should have a [modelname]_id column.
        public $belongs_to = array();

        # Array: $has_many
        # An array of models that belong to this Model.
        # They should have a [thismodel]_id column.
        public $has_many = array();

        # Array: $has_one
        # An array of models that this model has only one of.
        # The models should have a [thismodel]_id column.
        public $has_one = array();

        /**
         * Function: __get
         * Automatically handle model relationships when grabbing attributes of an object.
         *
         * Returns:
         *     @mixed@
         */
        public function __get($name) {
            $model_name = get_class($this);
            $placeholders = (isset($this->__placeholders) and $this->__placeholders);

            Trigger::current()->filter($filtered, $model_name."_".$name."_attr", $this);
            if ($filtered !== false)
                $this->$name = $filtered;

            $this->belongs_to = (array) $this->belongs_to;
            $this->has_many   = (array) $this->has_many;
            $this->has_one    = (array) $this->has_one;

            if (in_array($name, $this->belongs_to) or isset($this->belongs_to[$name])) {
                $class = (isset($this->belongs_to[$name])) ? $this->belongs_to[$name] : $name ;
                if (isset($this->belongs_to[$name])) {
                    $opts =& $this->belongs_to[$name];
                    $model = oneof(@$opts["model"], $name);

                    if (preg_match("/^\(([a-z0-9_]+)\)$/", $model, $match))
                        $model = $this->$match[1];

                    $match = oneof(@$opts["by"], strtolower($name));

                    fallback($opts["where"], array("id" => $this->{$match."_id"}));

                    $opts["where"] = (array) $opts["where"];
                    foreach ($opts["where"] as &$val)
                        if (preg_match("/^\(([a-z0-9_]+)\)$/", $val, $match))
                            $val = $this->$match[1];

                    fallback($opts["placeholders"], $placeholders);
                } else {
                    $model = $name;
                    $opts = array("where" => array("id" => $this->{$name."_id"}));
                }

                return $this->$name = new $model(null, $opts);
            } elseif (in_array($name, $this->has_many) or isset($this->has_many[$name])) {
                if (isset($this->has_many[$name])) {
                    $opts =& $this->has_many[$name];
                    $model = oneof(@$opts["model"], depluralize($name));

                    if (preg_match("/^\(([a-z0-9_]+)\)$/", $model, $match))
                        $model = $this->$match[1];

                    $match = oneof(@$opts["by"], strtolower($name));

                    fallback($opts["where"], array($match."_id" => $this->id));

                    $opts["where"] = (array) $opts["where"];
                    foreach ($opts["where"] as &$val)
                        if (preg_match("/^\(([a-z0-9_]+)\)$/", $val, $match))
                            $val = $this->$match[1];

                    fallback($opts["placeholders"], $placeholders);
                } else {
                    $model = depluralize($name);
                    $match = $model_name;
                    $opts = array("where" => array(strtolower($match)."_id" => $this->id),
                                  "placeholders" => $placeholders);
                }

                return $this->$name = call_user_func(array($model, "find"), $opts);
            } elseif (in_array($name, $this->has_one) or isset($this->has_one[$name])) {
                if (isset($this->has_one[$name])) {
                    $opts =& $this->has_one[$name];
                    $model = oneof(@$opts["model"], depluralize($name));

                    if (preg_match("/^\(([a-z0-9_]+)\)$/", $model, $match))
                        $model = $this->$match[1];

                    $match = oneof(@$opts["by"], strtolower($name));

                    fallback($opts["where"], array($match."_id" => $this->id));

                    $opts["where"] = (array) $opts["where"];
                    foreach ($opts["where"] as &$val)
                        if (preg_match("/^\(([a-z0-9_]+)\)$/", $val, $match))
                            $val = $this->$match[1];
                } else {
                    $model = depluralize($name);
                    $match = $model_name;
                    $opts = array("where" => array(strtolower($match)."_id" => $this->id));
                }

                return $this->$name = new $model(null, $opts);
            }

            if (isset($this->$name))
                return $this->$name;
        }

        /**
         * Function: __getPlaceholders
         * Calls __get with the requested $name, but grabs everything as placeholders.
         *
         * Parameters:
         *     $name - Name to call <Model.__get> with.
         *
         * Returns:
         *     @mixed@
         *
         * See Also:
         *     <Model.__get>
         */
        public function __getPlaceholders($name) {
            $this->__placeholders = true;
            $return = $this->__get($name);
            unset($this->__placeholders);
            return $return;
        }

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

            if (!isset($id) and isset($options["where"]["id"]))
                $id = $options["where"]["id"];

            $cache = (is_numeric($id) and isset(self::$caches[$model_name][$id])) ?
                         self::$caches[$model_name][$id] :
                         ((isset($options["read_from"]["id"]) and isset(self::$caches[$model_name][$options["read_from"]["id"]])) ?
                             self::$caches[$model_name][$options["read_from"]["id"]] :
                             (isset(self::$caches[$model_name][serialize($id)]) ?
                                 self::$caches[$model_name][serialize($id)] :
                                 array())) ;

            # Is this model already in the cache?
            if (!empty($cache)) {
                foreach ($cache as $attr => $val)
                    $model->$attr = $val;

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
            fallback($options["ignore_dupes"], array());

            $options["where"] = (array) $options["where"];
            $options["from"] = (array) $options["from"];
            $options["select"] = (array) $options["select"];

            if (is_numeric($id))
                $options["where"]["id"] = $id;
            elseif (is_array($id))
                $options["where"] = array_merge($options["where"], $id);

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
                                      null,
                                      $options["offset"],
                                      $options["group"],
                                      $options["left_join"]);
                $all = $query->fetchAll();

                if (count($all) == 1)
                    $read = $all[0];
                else {
                    $merged = array();

                    foreach ($all as $index => $row)
                        foreach ($row as $column => $val)
                            $merged[$row["id"]][$column][] = $val;

                    foreach ($all as $index => &$row)
                        $row = $merged[$row["id"]];

                    if (count($all)) {
                        $keys = array_keys($all);
                        $read = $all[$keys[0]];
                        foreach ($read as $name => &$column) {
                            $column = (!in_array($name, $options["ignore_dupes"]) ?
                                          array_unique($column) :
                                          $column);
                            $column = (count($column) == 1) ?
                                          $column[0] :
                                          $column ;
                        }
                    } else
                        $read = false;
                }
            }

            if (!count($read) or !$read)
                return $model->no_results = true;
            else
                $model->no_results = false;

            foreach ($read as $key => $val)
                if (!is_int($key))
                    $model->$key = $val;

            if (isset($query) and isset($query->queryString))
                $model->queryString = $query->queryString;

            if (isset($model->updated_at))
                $model->updated = (!empty($model->updated_at) and $model->updated_at != "0000-00-00 00:00:00");

            $clone = clone $model;

            self::$caches[$model_name][$read["id"]] = $clone;

            if (!is_numeric($id) and !isset($options["read_from"]["id"]) and $id !== null)
                self::$caches[$model_name][serialize($id)] = $clone;
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
            fallback($options["ignore_dupes"], array());

            $options["where"]  = (array) $options["where"];
            $options["from"]   = (array) $options["from"];
            $options["select"] = (array) $options["select"];

            $trigger = Trigger::current();
            $trigger->filter($options, pluralize(strtolower($model_name))."_get");

            $grab = SQL::current()->select($options["from"],
                                           $options["select"],
                                           $options["where"],
                                           $options["order"],
                                           $options["params"],
                                           $options["limit"],
                                           $options["offset"],
                                           $options["group"],
                                           $options["left_join"])->fetchAll();

            $shown_dates = array();
            $results = array();

            $rows = array();

            foreach ($grab as $row)
                foreach ($row as $column => $val)
                    $rows[$row["id"]][$column][] = $val;

            foreach ($rows as &$row)
                foreach ($row as $name => &$column) {
                    $column = (!in_array($name, $options["ignore_dupes"]) ?
                                  array_unique($column) :
                                  $column);
                    $column = (count($column) == 1) ?
                                  $column[0] :
                                  $column ;
                }

            foreach ($rows as $result) {
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
         * Deletes a given object. Calls the @delete_(model)@ trigger with the objects ID.
         *
         * Parameters:
         *     $model - The model name.
         *     $id - The ID of the object to delete.
         */
        protected static function destroy($model, $id) {
            $model = strtolower($model);
            if (Trigger::current()->exists("delete_".$model))
                Trigger::current()->call("delete_".$model, new $model($id));

            SQL::current()->delete(pluralize($model), array("id" => $id));
        }

        /**
         * Function: deletable
         * Checks if the <User> can delete the post.
         */
        public function deletable(User $user = null) {
            if ($this->no_results)
                return false;

            $name = strtolower(get_class($this));

            fallback($user, Visitor::current());
            return $user->group->can("delete_".$name);
        }

        /**
         * Function: editable
         * Checks if the <User> can edit the post.
         */
        public function editable(User $user = null) {
            if ($this->no_results)
                return false;

            $name = strtolower(get_class($this));

            fallback($user, Visitor::current());
            return $user->group->can("edit_".$name);
        }

        /**
         * Function: edit_link
         * Outputs an edit link for the model, if the visitor's <Group.can> edit_[model].
         *
         * Parameters:
         *     $text - The text to show for the link.
         *     $before - If the link can be shown, show this before it.
         *     $after - If the link can be shown, show this after it.
         *     $classes - Extra CSS classes for the link, space-delimited.
         */
        public function edit_link($text = null, $before = null, $after = null, $classes = "") {
            if (!$this->editable())
                return false;

            fallback($text, __("Edit"));

            $name = strtolower(get_class($this));

            if (@Feathers::$instances[$this->feather]->disable_ajax_edit)
                $classes = empty($classes) ? "no_ajax" : $classes." no_ajax" ;

            echo $before.'<a href="'.Config::current()->chyrp_url.'/admin/?action=edit_'.$name.'&amp;id='.$this->id.'" title="Edit" class="'.($classes ? $classes." " : '').$name.'_edit_link edit_link" id="'.$name.'_edit_'.$this->id.'">'.$text.'</a>'.$after;
        }

        /**
         * Function: delete_link
         * Outputs a delete link for the post, if the <User.can> delete_[model].
         *
         * Parameters:
         *     $text - The text to show for the link.
         *     $before - If the link can be shown, show this before it.
         *     $after - If the link can be shown, show this after it.
         *     $classes - Extra CSS classes for the link, space-delimited.
         */
        public function delete_link($text = null, $before = null, $after = null, $classes = "") {
            if (!$this->deletable())
                return false;

            fallback($text, __("Delete"));

            $name = strtolower(get_class($this));

            echo $before.'<a href="'.Config::current()->chyrp_url.'/admin/?action=delete_'.$name.'&amp;id='.$this->id.'" title="Delete" class="'.($classes ? $classes." " : '').$name.'_delete_link delete_link" id="'.$name.'_delete_'.$this->id.'">'.$text.'</a>'.$after;
        }
    }
