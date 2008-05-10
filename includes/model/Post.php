<?php
	$temp_id = null;

	/**
	 * Class: Post
	 * The model for the Posts SQL table.
	 */
	class Post extends Model {
		public $no_results = false;

		/**
		 * Function: __construct
		 * Grabs the specified post and injects it into the <Post> class.
		 *
		 * Parameters:
		 *     $post_id - The post's unique ID.
		 *     $options - An array of options:
		 *         where: A SQL query to grab the post by.
		 *         params: Parameters to use for the "where" option.
		 *         filter: Whether or not to run it through the _parse_post_ filter.
		 *         read_from: An associative array of values to load into the <Post> class.
		 */
		public function __construct($post_id = null, $options = array()) {
			if (!isset($post_id) and empty($options)) return;
			parent::grab($this, $post_id, $options);
			$this->slug = $this->url;
			$this->parse(!isset($options["filter"]) or $options["filter"]);
		}

		/**
		 * Function: add
		 * Adds a post to the database with the passed XML, sanitized URL, and unique URL. The rest is read from $_POST.
		 *
		 * Trackbacks are automatically sent based on the contents of $_POST['trackbacks'].
		 * Most of the $_POST variables will fall back gracefully if they don't exist, e.g. if they're posting from the Bookmarklet.
		 *
		 * Calls the add_post trigger with the inserted ID and extra options.
		 *
		 * Parameters:
		 *     $values - The data to insert.
		 *     $clean - The sanitized URL (or empty to default to "(feather).(new post's id)").
		 *     $url - The unique URL (or empty to default to "(feather).(new post's id)").
		 *
		 * Returns:
		 *     self - An object containing the new post.
		 *
		 * See Also:
		 *     <update>
		 */
		static function add($values, $clean = "", $url = "") {
			$pinned = (int) !empty($_POST['pinned']);
			$status = (isset($_POST['draft'])) ? "draft" : ((!empty($_POST['status'])) ? $_POST['status'] : "public") ;
			$timestamp = (!empty($_POST['created_at']) and (!isset($_POST['original_time']) or $_POST['created_at'] != $_POST['original_time'])) ?
			             when("Y-m-d H:i:s", $_POST['created_at']) :
			             datetime() ;
			$trackbacks = (!empty($_POST['trackbacks'])) ? $_POST['trackbacks'] : "" ;
			$options = (isset($_POST['option'])) ? $_POST['option'] : array() ;

			$xml = new SimpleXMLElement("<post></post>");
			self::arr2xml($xml, $values);
			self::arr2xml($xml, $options);

			$sql = SQL::current();
			$visitor = Visitor::current();
			$sql->insert("posts",
			             array(
			                 "xml" => ":xml",
			                 "feather" => ":feather",
			                 "user_id" => ":user_id",
			                 "pinned" => ":pinned",
			                 "status" => ":status",
			                 "clean" => ":clean",
			                 "url" => ":url",
			                 "created_at" => ":created_at",
			             ),
			             array(
			                 ":xml" => $xml->asXML(),
			                 ":feather" => $_POST['feather'],
			                 ":user_id" => $visitor->id,
			                 ":pinned" => $pinned,
			                 ":status" => $status,
			                 ":clean" => $clean,
			                 ":url" => $url,
			                 ":created_at" => $timestamp
			             ));
			$id = $sql->db->lastInsertId();

			if (empty($clean) or empty($url))
				$sql->update("posts",
				             "`id` = :id",
				             array(
				                 "clean" => ":clean",
				                 "url" => ":url"
				             ),
				             array(
				                 ':clean' => $_POST['feather'].".".$id,
				                 ':url' => $_POST['feather'].".".$id,
				                 ':id' => $id
				             ));

			$trackbacks = explode(",", $trackbacks);
			$trackbacks = array_map('trim', $trackbacks);
			$trackbacks = array_map('strip_tags', $trackbacks);
			$trackbacks = array_unique($trackbacks);
			$trackbacks = array_diff($trackbacks, array(""));
			foreach ($trackbacks as $url)
				trackback_send($id, $url);

			$post = new self($id);

			$trigger = Trigger::current();
			$trigger->call("add_post", array($post, $options));

			return $post;
		}

		/**
		 * Function: update
		 * Updates a post with the given XML. The rest is read from $_POST.
		 *
		 * Most of the $_POST variables will fall back gracefully if they don't exist, e.g. if they're posting from the Bookmarklet.
		 *
		 * Parameters:
		 *     $values - An array of data to set for the post.
		 *
		 * See Also:
		 *     <add>
		 */
		public function update($values, $pinned = null, $status = null, $slug = null, $timestamp = null) {
			if (!isset($this->id)) return;

			fallback($pinned, !empty($_POST['pinned']));
			fallback($status, (isset($_POST['draft'])) ? "draft" : ((!empty($_POST['status'])) ? $_POST['status'] : $this->status));
			fallback($slug, (!empty($_POST['slug'])) ? $_POST['slug'] : $this->feather.".".$this->id);
			fallback($timestamp, (!empty($_POST['created_at'])) ? when("Y-m-d H:i:s", $_POST['created_at']) : $this->created_at);

			$options = (isset($_POST['option'])) ? $_POST['option'] : array() ;

			$xml = new SimpleXMLElement("<post></post>");
			self::arr2xml($xml, $values);
			self::arr2xml($xml, $options);

			$sql = SQL::current();
			$sql->update("posts",
			             "`id` = :id",
			             array(
			                 "xml" => ":xml",
			                 "pinned" => ":pinned",
			                 "status" => ":status",
			                 "url" => ":url",
			                 "created_at" => ":created_at",
			                 "updated_at" => ":updated_at"
			             ),
			             array(
			                 ":xml" => $xml->asXML(),
			                 ":pinned" => $pinned,
			                 ":status" => $status,
			                 ":url" => $slug,
			                 ":created_at" => $timestamp,
			                 ":updated_at" => datetime(),
			                 ":id" => $this->id
			             ));

			$trigger = Trigger::current();
			$trigger->call("update_post", array($this, $options));
		}

		/**
		 * Function: delete
		 * Deletes a given post. Calls the "delete_post" trigger and passes the <Post> as an argument.
		 *
		 * Parameters:
		 *     $id - The user to delete.
		 */
		static function delete($id) {
			parent::destroy(get_class(), $id);
		}

		/**
		 * Function: deletable
		 * Checks if the <Visitor> can delete the post.
		 */
		public function deletable() {
			$visitor = Visitor::current();
			if ($visitor->group()->can("delete_post"))
				return true;

			return ($this->status == "draft" and $visitor->group()->can("delete_draft")) or
			       ($visitor->group()->can("delete_own_post") and $this->user_id == $visitor->id) or
			       (($visitor->group()->can("delete_own_draft") and $this->status == "draft") and $this->user_id == $visitor->id);
		}

		/**
		 * Function: editable
		 * Checks if the <Visitor> can edit the post.
		 */
		public function editable() {
			$visitor = Visitor::current();
			if ($visitor->group()->can("edit_post"))
				return true;

			return ($this->status == "draft" and $visitor->group()->can("edit_draft")) or
			       ($visitor->group()->can("edit_own_post") and $this->user_id == $visitor->id) or
			       (($visitor->group()->can("edit_own_draft") and $this->status == "draft") and $this->user_id == $visitor->id);
		}

		/**
		 * Function: any_editable
		 * Checks if the <Visitor> can edit any posts.
		 */
		static function any_editable() {
			$visitor = Visitor::current();

			# Can they edit posts?
			if ($visitor->group()->can("edit_post"))
				return true;

			# Can they edit drafts?
			if ($visitor->group()->can("edit_draft") and
			    Post::find(array("where" => "`status` = 'draft'")))
				return true;

			# Can they edit their own posts, and do they have any?
			if ($visitor->group()->can("edit_own_post") and
			    Post::find(array("where" => "`user_id` = :user_id", "params" => array(":user_id" => $visitor->id))))
				return true;

			# Can they edit their own drafts, and do they have any?
			if ($visitor->group()->can("edit_own_draft") and
			    Post::find(array("where" => "`status` = 'draft' and `user_id` = :user_id", "params" => array(":user_id" => $visitor->id))))
				return true;

			return false;
		}

		/**
		 * Function: any_deletable
		 * Checks if the <Visitor> can delete any posts.
		 */
		static function any_deletable() {
			$visitor = Visitor::current();

			# Can they delete posts?
			if ($visitor->group()->can("delete_post"))
				return true;

			# Can they delete drafts?
			if ($visitor->group()->can("delete_draft") and
			    Post::find(array("where" => "`status` = 'draft'")))
				return true;

			# Can they delete their own posts, and do they have any?
			if ($visitor->group()->can("delete_own_post") and
			    Post::find(array("where" => "`user_id` = :user_id", "params" => array(":user_id" => $visitor->id))))
				return true;

			# Can they delete their own drafts, and do they have any?
			if ($visitor->group()->can("delete_own_draft") and
			    Post::find(array("where" => "`status` = 'draft' and `user_id` = :user_id", "params" => array(":user_id" => $visitor->id))))
				return true;

			return false;
		}

		/**
		 * Function: find
		 * Grab all posts that match the passed options.
		 *
		 * Returns:
		 * An array of <Post>s from the result.
		 */
		static function find($options = array()) {
			global $private;

			$enabled_feathers = "`feather` in ('".implode("', '", Config::current()->enabled_feathers)."')";
			if (!isset($options["where"]))
				$options["where"] = array($private, $enabled_feathers);
			elseif ($options["where"] === false)
				$options["where"] = $enabled_feathers;
			else
				if (is_array($options["where"]))
					$options["where"][] = $enabled_feathers;
				else
					$options["where"] = array($options["where"], $enabled_feathers);

			$sql = SQL::current();
			fallback($options["order"], "`".$sql->prefix."posts`.`pinned` desc, `".$sql->prefix."posts`.`created_at` desc, `".$sql->prefix."posts`.`id` desc");

			$posts = parent::search(get_class(), $options);

			foreach ($posts as $index => $post)
				if (!$post->theme_exists())
					unset($posts[$index]);

			return $posts;
		}

		/**
		 * Function: info
		 * Grabs a specified column from a post's SQL row.
		 *
		 * Parameters:
		 *     $column - The name of the SQL column.
		 *     $post_id - The post ID to grab from.
		 *     $fallback - What to display if the result is empty.
		 *
		 * Returns:
		 *     false - if $post_id isn't set.
		 *     SQL result - if the SQL result isn't empty.
		 *     $fallback - if the SQL result is empty.
		 */
		static function info($column, $post_id, $fallback = false) {
			return SQL::current()->select("posts", $column, "`id` = :id", "`id` desc", array(":id" => $post_id))->fetchColumn();
		}

		/**
		 * Function: exists
		 * Checks if a post exists.
		 *
		 * Parameters:
		 *     $post_id - The post ID to check
		 *
		 * Returns:
		 *     true - if a post with that ID is in the database.
		 */
		static function exists($post_id) {
			$sql = SQL::current();
			$result = $sql->query("select count(`id`)
			                       from `{$sql->prefix}posts`
			                       where `id` = :id limit 1",
			                       array(
			                          ':id' => $post_id
			                       ));

			$count = $result->fetchColumn();
			$result->closeCursor();

			return ($count == 1);
		}

		/**
		 * Function: check_url
		 * Checks if a given clean URL is already being used as another post's URL.
		 *
		 * Parameters:
		 *     $clean - The clean URL to check.
		 *
		 * Returns:
		 *     $url - The unique version of the passed clean URL. If it's not used, it's the same as $clean. If it is, a number is appended.
		 */
		static function check_url($clean) {
			$sql = SQL::current();
			$check_url = $sql->select("posts",
			                          "id",
			                          "`clean` = :clean",
			                          "id",
			                          array(
			                              ':clean' => $clean
			                          ));
			$count = $check_url->rowCount() + 1;
			return ($count == 1 or empty($clean)) ? $clean : $clean."_".$count ;
		}

		/**
		 * Function: feather_class
		 * Returns the class name for the given posts's Feather.
		 *
		 * Parameters:
		 *     $post_id - The post ID to grab from.
		 */
		static function feather_class($post_id) {
			$feather = self::info("feather", $post_id);
			return camelize($feather);
		}

		/**
		 * Function: url
		 * Returns a post's URL.
		 */
		public function url() {
			$config = Config::current();
			$visitor = Visitor::current();
			if ($config->clean_urls) {
				$login = (strpos($config->post_url, "(author)") !== false) ? User::info("login", $this->user_id) : null ;
				$vals = array(when("Y", $this->created_at),
				              when("m", $this->created_at),
				              when("d", $this->created_at),
				              when("H", $this->created_at),
				              when("i", $this->created_at),
				              when("s", $this->created_at),
				              $this->id,
				              urlencode($login),
				              urlencode($this->clean),
				              urlencode($this->url),
				              urlencode($this->feather),
				              urlencode(pluralize($this->feather)));
				$trigger = Trigger::current();
				$vals = $trigger->filter("url_vals", $vals);
				$route = Route::current();
				return $config->url."/".str_replace(array_keys($route->code), $vals, $config->post_url);
			} else
				return $config->url."/?action=view&url=".urlencode($this->url);
		}

		/**
		 * Function: user
		 * Returns a post's user. Example: $post->user()->login
		 */
		public function user() {
			return new User($this->user_id);
		}

		/**
		 * Function: title_from_excerpt
		 * Generates an acceptable Title from the post's excerpt.
		 *
		 * Returns:
		 *     $normalized - The post's excerpt. filtered -> tags stripped -> truncated to 75 characters -> normalized.
		 */
		public function title_from_excerpt() {
			global $feathers;
			if (!isset($this->id)) return false;

			$trigger = Trigger::current();
			$excerpt = $trigger->filter("title_from_excerpt", $this->excerpt());
			$stripped = strip_tags($excerpt); # Strip all HTML
			$truncated = truncate($stripped, 75); # Truncate the excerpt to 75 characters
			$normalized = normalize($truncated); # Trim and normalize whitespace

			return $normalized;
		}

		/**
		 * Function: title
		 * Returns the given post's title, provided by its Feather.
		 */
		public function title() {
			global $feathers;
			return Trigger::current()->filter("title", $feathers[$this->feather]->title($this));
		}


		/**
		 * Function: excerpt
		 * Returns the given post's excerpt, provided by its Feather.
		 */
		public function excerpt() {
			global $feathers;
			return Trigger::current()->filter("excerpt", $feathers[$this->feather]->excerpt($this));
		}


		/**
		 * Function: feed_content
		 * Returns the given post's Feed content, provided by its Feather.
		 */
		public function feed_content() {
			return call_user_func(array(self::feather_class($this->id), "feed_content"), $this);
		}

		/**
		 * Function: next_link
		 * Displays a link to the next post.
		 */
		public function next_link($text = "(name) &rarr;", $class = "next_page", $truncate = 30) {
			global $private, $temp_id;
			$post = (!isset($temp_id)) ? $this : new self($temp_id) ;
			if (!isset($post->created_at)) return;

			if (!isset($temp_id))
				$temp_id = $this->id;

			$next = new self(null, array("where" => array($private, "`created_at` > :created_at"),
			                             "params" => array(":created_at" => $post->created_at)));
			if ($next->no_results)
				return;

			$text = str_replace("(name)", $next->title(), $text);

			echo '<a href="'.htmlspecialchars($next->url(), 2, "utf-8").'" class="'.$class.'">'.truncate($text, $truncate).'</a>';
		}

		/**
		 * Function: prev_link
		 * Displays a link to the previous post.
		 */
		public function prev_link($text = "&larr; (name)", $class = "prev_page", $truncate = 30) {
			global $private, $temp_id;
			$post = (!isset($temp_id)) ? $this : new self($temp_id) ;
			if (!isset($post->created_at)) return;

			if (!isset($temp_id))
				$temp_id = $this->id;

			$prev = new self(null, array("where" => array($private, "`created_at` < :created_at"),
			                             "params" => array(":created_at" => $post->created_at)));
			if ($prev->no_results)
				return;

			$text = str_replace("(name)", $prev->title(), $text);

			echo '<a href="'.htmlspecialchars($prev->url(), 2, "utf-8").'" class="'.$class.'">'.truncate($text, $truncate).'</a>';
		}

		/**
		 * Function: theme_exists
		 * Checks if the current post's feather theme file exists.
		 */
		public function theme_exists() {
			return file_exists(THEME_DIR."/content/feathers/".$this->feather.".twig");
		}

		/**
		 * Function: parse
		 * Parses the passed XML and loads the tags and values into <Post>.
		 *
		 * Parameters:
		 *     $filter - Should the data be run through any triggers?
		 */
		private function parse($filter = false) {
			global $post;

			foreach (self::xml2arr(simplexml_load_string($this->xml)) as $key => $val)
				$this->$key = $val;

			if ($filter) {
				$class = camelize($this->feather);
				$post = $this;

				$trigger = Trigger::current();
				$trigger->call("filter_post", $this);

				if (isset(Feather::$custom_filters[$class])) # Run through feather-specified filters, first.
					foreach (Feather::$custom_filters[$class] as $custom_filter)
						$this->$custom_filter["field"] = call_user_func(array($class, $custom_filter["name"]), $this->$custom_filter["field"]);

				if (isset(Feather::$filters[$class])) # Now actually filter it.
					foreach (Feather::$filters[$class] as $filter)
						$this->$filter["field"] = $trigger->filter($filter["name"], $this->$filter["field"]);
			}
		}

		/**
		 * Function: edit_link
		 * Outputs an edit link for the post, if the <User.can> edit_post.
		 *
		 * Parameters:
		 *     $text - The text to show for the link.
		 *     $before - If the link can be shown, show this before it.
		 *     $after - If the link can be shown, show this after it.
		 */
		public function edit_link($text = null, $before = null, $after = null){
			if (!$this->editable()) return false;

			fallback($text, __("Edit"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=edit_post&amp;id='.$this->id.'" title="Edit" class="post_edit_link" id="post_edit_'.$this->id.'">'.$text.'</a>'.$after;
		}

		/**
		 * Function: delete_link
		 * Outputs a delete link for the post, if the <User.can> delete_post.
		 *
		 * Parameters:
		 *     $text - The text to show for the link.
		 *     $before - If the link can be shown, show this before it.
		 *     $after - If the link can be shown, show this after it.
		 */
		public function delete_link($text = null, $before = null, $after = null){
			if (!$this->deletable()) return false;

			fallback($text, __("Delete"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=delete_post&amp;id='.$this->id.'" title="Delete" class="post_delete_link" id="post_delete_'.$this->id.'">'.$text.'</a>'.$after;
		}

		public function trackback_url() {
			return Config::current()->url."/includes/trackback.php?id=".$this->id;
		}

		/**
		 * Function: arr2xml
		 * Recursively adds an array (or object I guess) to a SimpleXML object.
		 *
		 * Parameters:
		 *     $object - The SimpleXML object to add to.
		 *     $data - The data to add to the SimpleXML object.
		 */
		static function arr2xml(&$object, $data) {
			foreach ($data as $key => $val) {
				if (is_int($key))
					$key = "data";

				if (is_array($val)) {
					$xml = $object->addChild($key);
					self::arr2xml($xml, $val);
				} else
					$object->addChild($key, $val);
			}
		}

		/**
		 * Function: xml2arr
		 * Recursively converts a SimpleXML object (and children) to an array.
		 *
		 * Parameters:
		 *     $parse - The SimpleXML object to convert into an array.
		 */
		static function xml2arr($parse) {
			if (empty($parse))
				return "";

			$parse = (array) $parse;

			foreach ($parse as &$val)
				if (get_class($val) == "SimpleXMLElement")
					$val = self::xml2arr($val);

			return $parse;
		}
	}
