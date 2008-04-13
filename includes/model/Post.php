<?php
	$current_post = array("id" => 0);
	$temp_id = null;
	
	/**
	 * Class: Post
	 * The model for the Posts SQL table.
	 */
	class Post {
		public $no_results = false;
		
		/**
		 * Function: __construct
		 * Grabs the specified post and injects it into the <Post> class.
		 * 
		 * Parameters:
		 * 	$post_id - The post's unique ID.
		 * 	$where - A SQL query to grab the post by.
		 * 	$filter - Whether or not to run it through the _parse_post_ filter.
		 */
		public function __construct($post_id = null, $options = array()) {
			global $current_post;
			if (!isset($post_id) and empty($options)) return; # Just using this to hold data?
			
			$where = fallback($options["where"], "", true);
			$filter = (!isset($options["filter"]) or $options["filter"]);
			$params = fallback($options["params"], array(), true);
			$read_from = fallback($options["read_from"], array(), true);
			
			$sql = SQL::current();
			if ((!empty($read_from) && $read_from))
				$read = $read_from;
			elseif (isset($post_id) and $post_id == $current_post["id"])
				$read = $current_post;
			elseif (!empty($where))
				$read = $sql->select("posts",
				                     "*",
				                     $where,
				                     "id",
				                     $params,
				                     1)->fetch();
			else
				$read = $sql->select("posts",
				                     "*",
				                     "`id` = :postid",
				                     "id",
				                     array(
				                     	":postid" => $post_id
				                     ),
				                     1)->fetch();
			
			if (!count($read) or !$read)
				return $this->no_results = true;
						
			foreach ($read as $key => $val) {
				if (!is_int($key))
					$this->$key = $val;
				
				$current_post[$key] = $val;
			}
			
			$this->parse($filter);
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
		 * 	$values - The data to insert.
		 * 	$clean - The sanitized URL (or empty to default to "(feather).(new post's id)").
		 * 	$url - The unique URL (or empty to default to "(feather).(new post's id)").
		 * 
		 * Returns:
		 * 	self - An object containing the new post.
		 * 
		 * See Also:
		 * 	<update>
		 */
		static function add($values, $clean = "", $url = "") {
			global $user, $current_user;
			
			$pinned = (int) !empty($_POST['pinned']);
			$status = (isset($_POST['draft'])) ? "draft" : ((!empty($_POST['status'])) ? $_POST['status'] : "public") ;
			$timestamp = (!empty($_POST['created_at']) and (!isset($_POST['original_time']) or $_POST['created_at'] != $_POST['original_time'])) ?
			             when("Y-m-d H:i:s", $_POST['created_at']) :
			             datetime() ;
			$trackbacks = (!empty($_POST['trackbacks'])) ? $_POST['trackbacks'] : "" ;
			$options = (isset($_POST['option'])) ? $_POST['option'] : array() ;

			$xml = new SimpleXMLElement("<post></post>");
			foreach($values as $key => $val)
				$xml->addChild($key, $val);
			foreach($options as $key => $val)
				$xml->addChild($key, $val);
			
			$sql = SQL::current();
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
			             	":user_id" => $current_user,
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
			
			$trigger = Trigger::current();
			$trigger->call("add_post", array($id, $options));
			
			return new self($id);
		}
		
		/**
		 * Function: update
		 * Updates a post with the given XML. The rest is read from $_POST.
		 * 
		 * Most of the $_POST variables will fall back gracefully if they don't exist, e.g. if they're posting from the Bookmarklet.
		 * 
		 * Parameters:
		 * 	$values - An array of data to set for the post.
		 * 
		 * See Also:
		 * 	<add>
		 */
		public function update($values, $pinned = null, $status = null, $slug = null, $timestamp = null) {
			if (!isset($this->id)) return;

			fallback($pinned, !empty($_POST['pinned']));
			fallback($status, (isset($_POST['draft'])) ? "draft" : ((!empty($_POST['status'])) ? $_POST['status'] : $this->info("status", $this->id)));
			fallback($slug, (!empty($_POST['slug'])) ? $_POST['slug'] : $this->info("feather", $this->id).".".$this->id);
			fallback($timestamp, (!empty($_POST['created_at'])) ? when("Y-m-d H:i:s", $_POST['created_at']) : $this->info("created_at", $this->id));

			$options = (isset($_POST['option'])) ? $_POST['option'] : array() ;

			$xml = new SimpleXMLElement("<post></post>");
			foreach($values as $key => $val)
				$xml->addChild($key, $val);
			foreach($options as $key => $val)
				$xml->addChild($key, $val);
			
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
			$trigger->call("update_post", array($this->id, $options));
		}
		
		/**
		 * Function: delete
		 * Deletes the post. Calls the "delete_post" trigger with the post's ID.
		 */
		public function delete() {
			if (!isset($this->id)) return;
			
			$trigger = Trigger::current();
			$trigger->call("delete_post", $this->id);
			
			$sql = SQL::current();
			$sql->delete("posts",
			             "`id` = :id",
			             array(
			             	':id' => $this->id
			             ));
		}
		
		/**
		 * Function: info
		 * Grabs a specified column from a post's SQL row.
		 * 
		 * Parameters:
		 * 	$column - The name of the SQL column.
		 * 	$post_id - The post ID to grab from.
		 * 	$fallback - What to display if the result is empty.
		 * 
		 * Returns:
		 * 	false - if $post_id isn't set.
		 * 	SQL result - if the SQL result isn't empty.
		 * 	$fallback - if the SQL result is empty.
		 */
		static function info($column, $post_id, $fallback = false) {
			global $current_post;
			
			if ($current_post["id"] == $post_id)
				return $current_post[$column];
			
			$sql = SQL::current();
			$grab_column = $sql->select("posts",
			                            $column,
			                            "`id` = :id",
			                            "id",
			                            array(
			                            	':id' => $post_id
			                            ));
			return ($grab_column->rowCount() == 1) ? $grab_column->fetchColumn() : $fallback ;
		}
		
		/**
		 * Function: exists
		 * Checks if a post exists.
		 * 
		 * Parameters:
		 * 	$post_id - The post ID to check
		 * 
		 * Returns:
		 * 	true - if a post with that ID is in the database.
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
		 * 	$clean - The clean URL to check.
		 * 
		 * Returns:
		 * 	$url - The unique version of the passed clean URL. If it's not used, it's the same as $clean. If it is, a number is appended.
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
		 * 	$post_id - The post ID to grab from.
		 */
		static function feather_class($post_id) {
			$feather = self::info("feather", $post_id);
			return camelize($feather);
		}
		
		/**
		 * Function: edit_link
		 * Outputs an edit link for the post, if the <User.can> edit_post.
		 * 
		 * Parameters:
		 * 	$text - The text to show for the link.
		 * 	$before - If the link can be shown, show this before it.
		 * 	$after - If the link can be shown, show this after it.
		 */
		public function edit_link($text = null, $before = null, $after = null){
			global $user;
			if (!isset($this->id) or !$user->can("edit_post")) return false;
			
			fallback($text, __("Edit"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=edit&amp;sub=post&amp;id='.$this->id.'" title="Edit" class="post_edit_link" id="post_edit_'.$this->id.'">'.$text.'</a>'.$after;
		}
		
		/**
		 * Function: delete_link
		 * Outputs a delete link for the post, if the <User.can> delete_post.
		 * 
		 * Parameters:
		 * 	$text - The text to show for the link.
		 * 	$before - If the link can be shown, show this before it.
		 * 	$after - If the link can be shown, show this after it.
		 */
		public function delete_link($text = null, $before = null, $after = null){
			global $user;
			if (!isset($this->id) or !$user->can("delete_post")) return false;
			
			fallback($text, __("Delete"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=delete&amp;sub=post&amp;id='.$this->id.'" title="Delete" class="post_delete_link" id="post_delete_'.$this->id.'">'.$text.'</a>'.$after;
		}
		
		/**
		 * Function: url
		 * Returns a post's URL.
		 */
		public function url() {
			global $user;
			
			$config = Config::current();
			if ($config->clean_urls) {
				$login = (preg_match("/\(author\)/", $config->post_url)) ? $user->info("login", $this->user_id) : null ;
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
				              urlencode($this->feather));
				$trigger = Trigger::current();
				$vals = $trigger->filter("url_vals", $vals);
				$route = Route::current();
				return $config->url."/".str_replace(array_keys($route->code), $vals, $config->post_url);
			} else {
				return $config->url."/?action=view&url=".urlencode($this->url);
			}
		}
		
		/**
		 * Function: title_from_excerpt
		 * Generates an acceptable Title from the post's excerpt.
		 * 
		 * Returns:
		 * 	$normalized - The post's excerpt. filtered -> tags stripped -> truncated to 75 characters -> normalized.
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
			$trigger = Trigger::current();
			return $trigger->filter("title", $feathers[$this->feather]->title($this->id));
		}
		
		
		/**
		 * Function: excerpt
		 * Returns the given post's excerpt, provided by its Feather.
		 */
		public function excerpt() {
			global $feathers;
			$trigger = Trigger::current();
			return $trigger->filter("excerpt", $feathers[$this->feather]->excerpt($this->id));
		}
		
		
		/**
		 * Function: feed_content
		 * Returns the given post's Feed content, provided by its Feather.
		 */
		public function feed_content() {
			$class = self::feather_class($this->id);
			return call_user_func(array($class, "feed_content"), $this->id);
		}
		
		/**
		 * Function: next_link
		 * Displays a link to the next post.
		 */
		public function next_link($text = "(name) &rarr;", $class = "next_page", $truncate = 30) {
			global $private, $enabled_feathers, $temp_id;
			$post = (!isset($temp_id)) ? $this : new self($temp_id) ;
			if (!isset($post->created_at)) return;
			
			if (!isset($temp_id))
				$temp_id = $this->id;
			
			$sql = SQL::current();
			$grab_next = $sql->select("posts",
			                          "id",
			                          $private.$enabled_feathers." and
			                          `created_at` > :created_at",
			                          "`created_at` asc",
			                          array(
			                          	":created_at" => $post->created_at
			                          ),
			                          1);
			if (!$grab_next->rowCount()) return;
			
			$next = new self($grab_next->fetchColumn());
			$text = str_replace("(name)", $next->title(), $text);
			
			echo '<a href="'.htmlspecialchars($next->url(), 2, "utf-8").'" class="'.$class.'">'.truncate($text, $truncate).'</a>';
			
			new self($post->id);
		}
		
		/**
		 * Function: prev_link
		 * Displays a link to the previous post.
		 */
		public function prev_link($text = "&larr; (name)", $class = "prev_page", $truncate = 30) {
			global $private, $enabled_feathers, $temp_id;
			$post = (!isset($temp_id)) ? $this : new self($temp_id) ;
			if (!isset($post->created_at)) return;
			
			if (!isset($temp_id))
				$temp_id = $this->id;
			
			$sql = SQL::current();
			$grab_prev = $sql->select("posts",
			                          "id",
			                          $private.$enabled_feathers." and
			                          `created_at` < :created_at",
			                          "`created_at` desc",
			                          array(
			                          	":created_at" => $post->created_at
			                          ),
			                          1);
			if (!$grab_prev->rowCount()) return;
			
			$prev = new self($grab_prev->fetchColumn());
			$text = str_replace("(name)", $prev->title(), $text);
			
			echo '<a href="'.htmlspecialchars($prev->url(), 2, "utf-8").'" class="'.$class.'">'.truncate($text, $truncate).'</a>';
			
			new self($post->id);
		}
		
		/**
		 * Function: theme_exists
		 * Checks if the current post's feather theme file exists.
		 */
		public function theme_exists() {
			return file_exists(THEME_DIR."/content/posts/".$this->feather.".php");
		}

		/**
		 * Function: parse
		 * Parses the passed XML and loads the tags and values into <Post>.
		 * 
		 * Parameters:
		 * 	$filter - Should the data be run through any triggers?
		 */
		private function parse($filter = false) {
			global $post;
			
			$parse = simplexml_load_string($this->xml);
			foreach ($parse as $key => $val)
				if (!is_int($key))
					$this->$key = $val;
			
			if ($filter) {
				$class = camelize($this->feather);
				
				$post = $this;
				
				$trigger = Trigger::current();
				$trigger->call("filter_post");
				
				if (isset(Feather::$custom_filters[$class])) # Run through feather-specified filters, first.
					foreach (Feather::$custom_filters[$class] as $custom_filter)
						$this->$custom_filter["field"] = call_user_func(array($class, $custom_filter["name"]), $this->$custom_filter["field"]);
				
				if (isset(Feather::$filters[$class])) # Now actually filter it.
					foreach (Feather::$filters[$class] as $filter)
						$this->$filter["field"] = $trigger->filter($filter["name"], $this->$filter["field"]);
			}
		}
	}
