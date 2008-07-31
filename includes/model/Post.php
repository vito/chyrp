<?php
	/**
	 * Class: Post
	 * The Post model.
	 * See Also:
	 *     <Model>
	 */
	class Post extends Model {
		# String: $private
		# SQL "where" text for which posts the current user can view.
		static $private;

		# String: $enabled_feathers
		# SQL "where" text for each of the feathers. Prevents posts of a disabled Feather from showing.
		static $enabled_feathers;

		/**
		 * Function: __construct
		 * See Also:
		 *     <Model::grab>
		 */
		public function __construct($post_id = null, $options = array()) {
			if (!isset($post_id) and empty($options)) return;

			if (isset($options["where"]) and !is_array($options["where"]))
				$options["where"] = array($options["where"]);
			elseif (!isset($options["where"]))
				$options["where"] = array();

			$has_status = false;
			foreach ($options["where"] as $where)
				if (substr_count($where, "status"))
					$has_status = true;

			if (!XML_RPC) {
				$options["where"][] = self::$enabled_feathers;
				if (!$has_status)
					$options["where"][] = self::$private;
			}

			parent::grab($this, $post_id, $options);

			if ($this->no_results)
				return false;

			$this->filtered = (!isset($options["filter"]) or $options["filter"]) and !XML_RPC;
			$this->slug =& $this->url;
			fallback($this->clean, $this->url);

			$this->parse();
		}

		/**
		 * Function: find
		 * See Also:
		 *     <Model::search>
		 */
		static function find($options = array(), $options_for_object = array(), $debug = false) {
			if (isset($options["where"]) and !is_array($options["where"]))
				$options["where"] = array($options["where"]);
			elseif (!isset($options["where"]))
				$options["where"] = array();

			if (!XML_RPC)
				$options["where"] = array_merge($options["where"], array(self::$enabled_feathers, self::$private));

			fallback($options["order"], "pinned DESC, created_at DESC, id DESC");

			$posts = parent::search(get_class(), $options, $options_for_object);

			if (!ADMIN and !XML_RPC)
				if (!isset($options["placeholders"]) or !$options["placeholders"]) {
					foreach ($posts as $index => $post)
						if (!$post->theme_exists())
							unset($posts[$index]);
				} else {
					foreach ($posts[0] as $index => $data)
						if (!Theme::current()->file_exists("feathers/".$data["feather"]))
							unset($posts[0][$index]);

					$posts[0] = array_values($posts[0]);
				}

			return $posts;
		}

		/**
		 * Function: add
		 * Adds a post to the database with the passed XML, sanitized URL, and unique URL. The rest is read from $_POST.
		 *
		 * Trackbacks are automatically sent based on the contents of $_POST['trackbacks'].
		 * Most of the $_POST variables will fall back gracefully if they don't exist, e.g. if they're posting from the Bookmarklet.
		 *
		 * Calls the add_post trigger with the inserted post and extra options.
		 *
		 * Parameters:
		 *     $values - The data to insert.
		 *     $clean - The sanitized URL (or empty to default to "(feather).(new post's id)").
		 *     $url - The unique URL (or empty to default to "(feather).(new post's id)").
		 *     $feather - The feather to post as.
		 *     $user - <User> to set as the post's author.
		 *     $pinned - Pin the post?
		 *     $status - Post status
		 *     $slug - A new URL for the post.
		 *     $timestamp - New @created_at@ timestamp for the post.
		 *     $updated_timestamp - New @updated_at@ timestamp for the post, or @false@ to not updated it.
		 *     $trackbacks - URLs separated by " " to send trackbacks to.
		 *     $options - Options for the post.
		 *
		 * Returns:
		 *     self - An object containing the new post.
		 *
		 * See Also:
		 *     <update>
		 */
		static function add($values, $clean = "", $url = "", $feather = null, $user = null, $pinned = null, $status = null, $timestamp = null, $updated_timestamp = null, $trackbacks = "", $pingbacks = true, $options = null) {

			if ($user instanceof User)
				$user = $user->id;

			fallback($feather, fallback($_POST['feather'], "", true));
			fallback($user, fallback($_POST['user_id'], Visitor::current()->id, true));
			fallback($pinned, (int) !empty($_POST['pinned']));
			fallback($status, (isset($_POST['draft'])) ? "draft" : fallback($_POST['status'], "public", true));
			fallback($timestamp,
			         (!empty($_POST['created_at']) and (!isset($_POST['original_time']) or $_POST['created_at'] != $_POST['original_time'])) ?
			          when("Y-m-d H:i:s", $_POST['created_at']) :
			          datetime());
			fallback($updated_timestamp, fallback($_POST['updated_at'], "0000-00-00 00:00:00", true));
			fallback($trackbacks, fallback($_POST['trackbacks'], "", true));
			fallback($options, fallback($_POST['option'], array(), true));

			if (isset($_POST['bookmarklet'])) {
				Trigger::current()->filter($values, "bookmarklet_submit_values");
				Trigger::current()->filter($options, "bookmarklet_submit_options");
			}

			$xml = new SimpleXMLElement("<post></post>");
			self::arr2xml($xml, $values);
			self::arr2xml($xml, $options);

			$sql = SQL::current();
			$visitor = Visitor::current();
			$sql->insert("posts",
			             array("xml" => ":xml",
			                   "feather" => ":feather",
			                   "user_id" => ":user_id",
			                   "pinned" => ":pinned",
			                   "status" => ":status",
			                   "clean" => ":clean",
			                   "url" => ":url",
			                   "created_at" => ":created_at",
			                   "updated_at" => ":updated_at"),
			             array(":xml" => $xml->asXML(),
			                   ":feather" => $feather,
			                   ":user_id" => $user,
			                   ":pinned" => (int) $pinned,
			                   ":status" => $status,
			                   ":clean" => $clean,
			                   ":url" => $url,
			                   ":created_at" => $timestamp,
			                   ":updated_at" => $updated_timestamp));
			$id = $sql->latest();

			if (empty($clean) or empty($url))
				$sql->update("posts",
				             "id = :id",
				             array("clean" => ":clean",
				                   "url" => ":url"),
				             array(":clean" => $feather.".".$id,
				                   ":url" => $feather.".".$id,
				                   ":id" => $id));

			if ($trackbacks !== "") {
				$trackbacks = explode(",", $trackbacks);
				$trackbacks = array_map("trim", $trackbacks);
				$trackbacks = array_map("strip_tags", $trackbacks);
				$trackbacks = array_unique($trackbacks);
				$trackbacks = array_diff($trackbacks, array(""));
				foreach ($trackbacks as $url)
					trackback_send($id, $url);
			}

			$post = new self($id);

			if (Config::current()->send_pingbacks and $pingbacks)
				array_walk_recursive($values, array("Post", "send_pingbacks"), $post);

			$post->redirect = (isset($_POST['bookmarklet'])) ? url("/admin/?action=bookmarklet&done") : $post->url() ;

			Trigger::current()->call("add_post", $post, $options);

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
		 *     $user - <User> to set as the post's author.
		 *     $pinned - Pin the post?
		 *     $status - Post status
		 *     $slug - A new URL for the post.
		 *     $timestamp - New @created_at@ timestamp for the post.
		 *     $updated_timestamp - New @updated_at@ timestamp for the post, or @false@ to not updated it.
		 *     $options - Options for the post.
		 *
		 * See Also:
		 *     <add>
		 */
		public function update($values, $user = null, $pinned = null, $status = null, $slug = null, $timestamp = null, $updated_timestamp = null, $options = null) {
			if ($this->no_results)
				return false;

			if (isset($user))
				$user = $user->id;

			fallback($user, fallback($_POST['user_id'], $this->id, true));
			fallback($pinned, (int) !empty($_POST['pinned']));
			fallback($status, (isset($_POST['draft'])) ? "draft" : fallback($_POST['status'], $this->status, true));
			fallback($slug, fallback($_POST['slug'], $this->feather.".".$this->id));
			fallback($timestamp, (!empty($_POST['created_at'])) ? when("Y-m-d H:i:s", $_POST['created_at']) : $this->created_at);

			if ($updated_timestamp === false)
				$updated_timestamp = $this->updated_at;
			else
				fallback($updated_timestamp, fallback($_POST['updated_at'], $this->updated_at, true));

			fallback($options, fallback($_POST['option'], array(), true));

			$xml = new SimpleXMLElement("<post></post>");
			self::arr2xml($xml, $values);
			self::arr2xml($xml, $options);

			$sql = SQL::current();
			$sql->update("posts",
			             "id = :id",
			             array("xml" => ":xml",
			                   "pinned" => ":pinned",
			                   "status" => ":status",
			                   "clean" => ":clean",
			                   "url" => ":url",
			                   "created_at" => ":created_at",
			                   "updated_at" => ":updated_at"),
			             array(":xml" => $xml->asXML(),
			                   ":pinned" => $pinned,
			                   ":status" => $status,
			                   ":clean" => $slug,
			                   ":url" => $slug,
			                   ":created_at" => $timestamp,
			                   ":updated_at" => $updated_timestamp,
			                   ":id" => $this->id));

			$trigger = Trigger::current();
			$trigger->call("update_post", $this, $values, $user, $pinned, $status, $slug, $timestamp, $updated_timestamp, $options);
		}

		/**
		 * Function: delete
		 * See Also:
		 *     <Model::destroy>
		 */
		static function delete($id) {
			parent::destroy(get_class(), $id);
		}

		/**
		 * Function: deletable
		 * Checks if the <User> can delete the post.
		 */
		public function deletable($user = null) {
			if ($this->no_results)
				return false;

			fallback($user, Visitor::current());
			if ($user->group()->can("delete_post"))
				return true;

			return ($this->status == "draft" and $user->group()->can("delete_draft")) or
			       ($user->group()->can("delete_own_post") and $this->user_id == $user->id) or
			       (($user->group()->can("delete_own_draft") and $this->status == "draft") and $this->user_id == $user->id);
		}

		/**
		 * Function: editable
		 * Checks if the <User> can edit the post.
		 */
		public function editable($user = null) {
			if ($this->no_results)
				return false;

			fallback($user, Visitor::current());
			if ($user->group()->can("edit_post"))
				return true;

			return ($this->status == "draft" and $user->group()->can("edit_draft")) or
			       ($user->group()->can("edit_own_post") and $this->user_id == $user->id) or
			       (($user->group()->can("edit_own_draft") and $this->status == "draft") and $this->user_id == $user->id);
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
			    Post::find(array("where" => "status = 'draft'")))
				return true;

			# Can they edit their own posts, and do they have any?
			if ($visitor->group()->can("edit_own_post") and
			    Post::find(array("where" => "user_id = :visitor_id", "params" => array(":visitor_id" => $visitor->id))))
				return true;

			# Can they edit their own drafts, and do they have any?
			if ($visitor->group()->can("edit_own_draft") and
			    Post::find(array("where" => "status = 'draft' and user_id = :visitor_id", "params" => array(":visitor_id" => $visitor->id))))
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
			    Post::find(array("where" => "status = 'draft'")))
				return true;

			# Can they delete their own posts, and do they have any?
			if ($visitor->group()->can("delete_own_post") and
			    Post::find(array("where" => "user_id = :visitor_id", "params" => array(":visitor_id" => $visitor->id))))
				return true;

			# Can they delete their own drafts, and do they have any?
			if ($visitor->group()->can("delete_own_draft") and
			    Post::find(array("where" => "status = 'draft' and user_id = :visitor_id", "params" => array(":visitor_id" => $visitor->id))))
				return true;

			return false;
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
			return SQL::current()->count("posts", "id = :id", array(":id" => $post_id));
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
			$count = $sql->count("posts",
			                     "clean = :clean",
			                     array(":clean" => $clean));

			return (!$count or empty($clean)) ? $clean : $clean."-".($count + 1) ;
		}

		/**
		 * Function: url
		 * Returns a post's URL.
		 */
		public function url() {
			if ($this->no_results)
				return false;

			$config = Config::current();
			$visitor = Visitor::current();

			if (!$config->clean_urls)
				return $config->url."/?action=view&amp;url=".urlencode($this->url);

			$login = (strpos($config->post_url, "(author)") !== false) ? $this->user()->login : null ;
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

			Trigger::current()->filter($vals, "url_vals", $this);
			return $config->url."/".str_replace(array_keys(Route::current()->code), $vals, $config->post_url);
		}

		/**
		 * Function: user
		 * Returns a post's user. Example: $post->user()->login
		 */
		public function user() {
			if ($this->no_results)
				return false;

			return new User($this->user_id);
		}

		/**
		 * Function: title_from_excerpt
		 * Generates an acceptable Title from the post's excerpt.
		 *
		 * Returns:
		 *     The post's excerpt. iltered -> first line -> ftags stripped -> truncated to 75 characters -> normalized.
		 */
		public function title_from_excerpt() {
			if ($this->no_results)
				return false;

			global $feathers;

			# Excerpts are likely to have some sort of markup module applied to them;
			# if the current instantiation is not filtered, make one that is.
			$post = ($this->filtered) ? $this : new Post($this->id) ;

			$excerpt = $post->excerpt();
			Trigger::current()->filter($excerpt, "title_from_excerpt");

			$split_lines = explode("\n", $excerpt);
			$first_line = $split_lines[0];

			$stripped = strip_tags($first_line); # Strip all HTML
			$truncated = truncate($stripped, 75); # Truncate the excerpt to 75 characters
			$normalized = normalize($truncated); # Trim and normalize whitespace

			return $normalized;
		}

		/**
		 * Function: title
		 * Returns the given post's title, provided by its Feather.
		 */
		public function title() {
			if ($this->no_results)
				return false;

			global $feathers;

			# Excerpts are likely to have some sort of markup module applied to them;
			# if the current instantiation is not filtered, make one that is.
			$post = ($this->filtered) ? $this : new Post($this->id) ;

			$title = $feathers[$this->feather]->title($post);
			return Trigger::current()->filter($title, "title", $post);
		}


		/**
		 * Function: excerpt
		 * Returns the given post's excerpt, provided by its Feather.
		 */
		public function excerpt() {
			if ($this->no_results)
				return false;

			global $feathers;

			# Excerpts are likely to have some sort of markup module applied to them;
			# if the current instantiation is not filtered, make one that is.
			$post = ($this->filtered) ? $this : new Post($this->id) ;

			$excerpt = $feathers[$this->feather]->excerpt($post);
			return Trigger::current()->filter($excerpt, "excerpt", $post);
		}


		/**
		 * Function: feed_content
		 * Returns the given post's Feed content, provided by its Feather.
		 */
		public function feed_content() {
			if ($this->no_results)
				return false;

			global $feathers;

			# Excerpts are likely to have some sort of markup module applied to them;
			# if the current instantiation is not filtered, make one that is.
			$post = ($this->filtered) ? $this : new Post($this->id) ;

			$feed_content = $feathers[$this->feather]->feed_content($post);
			return Trigger::current()->filter($feed_content, "feed_content", $post);
		}

		/**
		 * Function: next
		 * Returns:
		 *     The next post (the post made after this one).
		 */
		public function next() {
			if ($this->no_results)
				return false;

			$where = array("(created_at > :created_at OR id > :id)");

			$statuses = array("public");
			if ($this->status == "draft")
				$statuses[] = "draft";
			if (logged_in())
				$statuses[] = "registered_only";
			if (Visitor::current()->group()->can("view_private"))
				$statuses[] = "private";

			$where[] = "status IN ('".implode("', '", $statuses)."')";

			return new self(null, array("where" => $where,
			                            "order" => "created_at ASC, id ASC",
			                            "params" => array(":created_at" => $this->created_at,
			                                              ":id" => $this->id)));
		}

		/**
		 * Function: prev
		 * Returns:
		 *     The next post (the post made after this one).
		 */
		public function prev() {
			if ($this->no_results)
				return false;

			$where = array("(created_at < :created_at OR id < :id)");

			$statuses = array("public");
			if ($this->status == "draft")
				$statuses[] = "draft";
			if (logged_in())
				$statuses[] = "registered_only";
			if (Visitor::current()->group()->can("view_private"))
				$statuses[] = "private";

			$where[] = "status IN ('".implode("', '", $statuses)."')";

			return new self(null, array("where" => $where,
			                            "order" => "created_at DESC, id DESC",
			                            "params" => array(":created_at" => $this->created_at,
			                                              ":id" => $this->id)));
		}

		/**
		 * Function: theme_exists
		 * Checks if the current post's feather theme file exists.
		 */
		public function theme_exists() {
			return !$this->no_results and Theme::current()->file_exists("feathers/".$this->feather);
		}

		/**
		 * Function: parse
		 * Parses the passed XML and loads the tags and values into <Post>.
		 */
		private function parse() {
			foreach (self::xml2arr(simplexml_load_string($this->xml)) as $key => $val)
				$this->$key = $val;

			if (!$this->filtered)
				return;

			global $feathers;

			$class = camelize($this->feather);

			$trigger = Trigger::current();
			$trigger->filter($this, "filter_post");


			if (isset(Feathers::$custom_filters[$class])) # Run through feather-specified filters, first.
				foreach (Feathers::$custom_filters[$class] as $custom_filter) {
					$varname = $custom_filter["field"]."_unfiltered";
					if (!isset($this->$varname))
						$this->$varname = $this->$custom_filter["field"];

					$this->$custom_filter["field"] = call_user_func_array(array($feathers[$this->feather], $custom_filter["name"]),
					                                                      array($this->$custom_filter["field"], $this));
				}

			if (isset(Feathers::$filters[$class])) # Now actually filter it.
				foreach (Feathers::$filters[$class] as $filter) {
					$varname = $filter["field"]."_unfiltered";
					if (!isset($this->$varname))
						$this->$varname = $this->$filter["field"];

					if (isset($this->$filter["field"]) and !empty($this->$filter["field"]))
						$trigger->filter($this->$filter["field"], $filter["name"], $this);
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
			if (!$this->editable())
				return false;

			fallback($text, __("Edit"));

			echo $before.'<a href="'.Config::current()->chyrp_url.'/admin/?action=edit_post&amp;id='.$this->id.'" title="Edit" class="post_edit_link edit_link" id="post_edit_'.$this->id.'">'.$text.'</a>'.$after;
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
			if (!$this->deletable())
				return false;

			fallback($text, __("Delete"));

			echo $before.'<a href="'.Config::current()->chyrp_url.'/admin/?action=delete_post&amp;id='.$this->id.'" title="Delete" class="post_delete_link delete_link" id="post_delete_'.$this->id.'">'.$text.'</a>'.$after;
		}

		/**
		 * Function: trackback_url
		 * Returns the posts trackback URL.
		 */
		public function trackback_url() {
			if ($this->no_results) return
				false;

			return Config::current()->chyrp_url."/includes/trackback.php?id=".$this->id;
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
				if (is_int($key) and (empty($val) or trim($val) == "")) {
					unset($data[$key]);
					continue;
				}

				if (is_array($val)) {
					$xml = $object->addChild($key);
					self::arr2xml($xml, $val);
				} else
					$object->addChild($key, fix($val, false, false));
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

		/**
		 * Function: send_pingbacks
		 * Sends any callbacks in $value. Used by array_walk_recursive in <Post.add>.
		 */
		static function send_pingbacks($value, $key, $post) {
			send_pingbacks($value, $post);
		}

		/**
		 * Function: from_url
		 * Attempts to grab a post from its clean URL.
		 */
		static function from_url($attrs = null) {
			fallback($attrs, $_GET);
			$get = array_map("urldecode", $attrs);

			$where = array();
			$times = array("year", "month", "day", "hour", "minute", "second");

			preg_match_all("/\(([^\)]+)\)/", Config::current()->post_url, $matches);
			$params = array();
			foreach ($matches[1] as $attr)
				if (in_array($attr, $times)) {
					$where[] = strtoupper($attr)."(created_at) = :created_".$attr;
					$params[':created_'.$attr] = $get[$attr];
				} elseif ($attr == "author") {
					$where[] = "user_id = :attrauthor";
					$params[':attrauthor'] = SQL::current()->select("users",
					                                      "id",
					                                      "login = :login",
					                                      "id",
					                                      array(
					                                          ":login" => $get['author']
					                                      ), 1)->fetchColumn();
				} elseif ($attr == "feathers") {
					$where[] = "feather = :feather";
					$params[':feather'] = depluralize($get['feathers']);
				} else {
					$tokens = array($where, $params, $attr);
					Trigger::current()->filter($tokens, "post_url_token");
					list($where, $params, $attr) = $tokens;

					if ($attr !== null) {
						$where[] = $attr." = :attr".$attr;
						$params[':attr'.$attr] = $get[$attr];
					}
				}

			return new self(null, array("where" => $where, "params" => $params));
		}
	}
