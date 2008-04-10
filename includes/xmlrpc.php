<?php
	define('XML_RPC', true);
	error_reporting(E_ALL ^ E_NOTICE);
	require_once "common.php";
	require_once INCLUDES_DIR."/lib/ixr.php";
	
	/**
	 * Class: XMLRPC
	 */
	class XMLRPC extends IXR_Server {
		/**
		 * Function: XMLRPC
		 * Registers the various XMLRPC methods.
		 */
		public function XMLRPC() {
			$methods = array(
				'pingback.ping' => 'this:pingback_ping',
				'metaWeblog.getRecentPosts' => 'this:metaWeblog_getRecentPosts',
				'metaWeblog.getCategories'  => 'this:metaWeblog_getCategories',
				'metaWeblog.newMediaObject' => 'this:metaWeblog_newMediaObject',
				'metaWeblog.newPost'        => 'this:metaWeblog_newPost',
				'metaWeblog.getPost'        => 'this:metaWeblog_getPost',
				'metaWeblog.editPost'       => 'this:metaWeblog_editPost',
				'blogger.deletePost'        => 'this:blogger_deletePost',
				'blogger.getUsersBlogs'     => 'this:blogger_getUsersBlogs',
				'blogger.getUserInfo'       => 'this:blogger_getUserInfo',
				'mt.getRecentPostTitles'    => 'this:mt_getRecentPostTitles',
				'mt.getCategoryList'        => 'this:mt_getCategoryList',
				'mt.getPostCategories'      => 'this:mt_getPostCategories',
				'mt.setPostCategories'      => 'this:mt_setPostCategories',
				'mt.supportedTextFilters'   => 'this:mt_supportedTextFilters',
				'mt.supportedMethods'       => 'this:listMethods');
			
			$trigger = Trigger::current();
			$methods = $trigger->filter('xmlrpc_methods', $methods);
			
			$this->IXR_Server($methods);
		}
		
		/**
		 * Function: pingback_ping
		 * Receive and register pingbacks. Calls the "pingback" trigger.
		 */
		public function pingback_ping($args) {
			$config = Config::current();
			$linked_from = str_replace('&amp;', '&', $args[0]);
			$linked_to	 = str_replace('&amp;', '&', $args[1]);
			$post_url	 = str_replace($config->url, "", $linked_to);
			$url         = extract(parse_url($linked_to));
			
			$cleaned_url = str_replace(array("http://www.", "http://"), "", $config->url);
			
			if ($linked_to == $linked_from)
				return new IXR_ERROR(0, __("The from and to URLs cannot be the same."));
			
			if(!strpos($linked_to, $cleaned_url))
				return new IXR_Error(0, __("There doesn't seem to be a valid link in your request."));
			
			if (!$config->clean_urls) {
				# Figure out what they're grabbing by
				$count = 0;
				$get = array();
				$queries = explode("&", $query);
				foreach ($queries as $query) {
					list($key, $value) = explode("=", $query);
					$get[$count]["key"] = $key;
					$get[$count]["value"] = $value;
					$count++;
				}
				$where = "`".$get[1]["key"]."` = :val";
				$params = array(":val" => $get[1]["value"]);
			} else {
				$route = Route::current();
				$where = substr($route->parse_url_to_sql($linked_to, $post_url), 0, -4);
				$params = array();
			}
			
			$sql = SQL::current();
			$check = $sql->query("select `id` from `".$sql->prefix."posts`
			                      where ".$where."
			                      limit 1",
			                     $params);
			$id = $check->fetchColumn();
			
			
			if (!Post::exists($id))
				return new IXR_Error(33, __("I can't find a post from that URL."));
				
			# Wait for the "from" server to publish
			sleep(1);
			
			extract(parse_url($linked_from), EXTR_SKIP);
			if (!isset($host)) return false;
			
			if (!isset($scheme) or !in_array($scheme, array("http")))
				$linked_from = "http://".$linked_from;

			# Connect
			$content = get_remote($linked_from);
			
			$content = str_replace("<!DOC", "<DOC", $content);
			preg_match("/<title>([^<]+)<\/title>/i", $content, $title);
			$title = $title[1];
			
			if (empty($title))
				return new IXR_Error(32, __("There isn't a title on that page."));
			
			$content = strip_tags($content, "<a>");
			
			preg_match("|<a[^>]+?".preg_quote($linked_to)."[^>]*>([^>]+?)</a>|", $content, $context);
			$context[1] = truncate($context[1], 100, false);
			$excerpt = str_replace($context[0], $context[1], $content);
			$excerpt = strip_tags($excerpt);
			$excerpt = preg_replace("|.*?\s(.{0,100}".preg_quote($context[1]).".{0,100})\s.*|s", '$1', $excerpt);
			$excerpt = preg_replace("/[\s\n\r\t]+/", " ", $excerpt);
			$excerpt = "[...] ".trim($excerpt)." [...]";
			
			$linked_to = str_replace('&', '&amp;', $linked_to);
			$trigger = Trigger::current();
			$trigger->call("pingback", array($id, $linked_to, $linked_from, $title, $excerpt));
			
			return sprintf(__("Pingback from %s to %s registered!"), $linked_from, $linked_to);
		}
		
		/**
		 * Function: metaWeblog_getRecentPosts
		 * Returns a list of the most recent posts.
		 */
		public function metaWeblog_getRecentPosts($args) {
			if (($auth = $this->auth($args[1], $args[2])) instanceof IXR_Error)
				return $auth;
			
			if (($posts = $this->getRecentPosts($args[3])) instanceof IXR_Error)
				return $posts;
			
			$config = Config::current();
			$trigger = Trigger::current();
			$result = array();
			
			foreach ($posts as $post) {
				$post = new Post(null, array("read_from" => $post, "filter" => false));
				
				$struct = array(
					'postid'            => $post->id,
					'userid'            => $post->user_id,
					'title'             => $post->title,
					'dateCreated'       => new IXR_Date(@date('Ymd\TH:i:s', @strtotime($post->created_at))),
					'description'       => $post->body,
					'link'              => $post->url(),
					'permaLink'         => $post->url(),
					'mt_basename'       => $post->url,
					'mt_excerpt'        => '',
					'mt_text_more'      => '',
					'mt_keywords'       => '',
					'mt_allow_pings'    => (int) $config->enable_trackbacking,
					'mt_allow_comments' => 0,
					'mt_convert_breaks' => '0');
				
				list($struct, $post) = $trigger->filter('metaWeblog_getPost', array($struct, $post), true);
				$result[] = $struct;
			}
			
			return $result;
		}
		
		/**
		 * Function: metaWeblog_getCategories
		 * Returns a list of all categories to which the post is assigned.
		 */
		public function metaWeblog_getCategories($args) {
			if (($auth = $this->auth($args[1], $args[2])) instanceof IXR_Error)
				return $auth;

			$trigger = Trigger::current();
			$categories = array();
			return $trigger->filter('metaWeblog_getCategories', $categories);
		}
		
		/**
		 * Function: metaWeblog_newMediaObject
		 * Uploads a file to the server.
		 */
		public function metaWeblog_newMediaObject($args) {
			if (($auth = $this->auth($args[1], $args[2])) instanceof IXR_Error)
				return $auth;
			
			$file = unique_filename(trim($args[3]['name'], '/'));
			$path = MAIN_DIR.'/upload/'.$file;
			
			if (file_put_contents($path, $args[3]['bits']) === false)
				return new IXR_Error(500, __("Failed to write file."));
			
			$config = Config::current();
			$url = $config->url.'/upload/'.urlencode($file);
			
			$trigger = Trigger::current();
			list($url, $path) = $trigger->filter('metaWeblog_newMediaObject', array($url, $path), true);
			
			return array('url' => $url);
		}
		
		/**
		 * Function: metaWeblog_getPost
		 * Retrieves a specified post.
		 */
		public function metaWeblog_getPost($args) {
			if (($auth = $this->auth($args[1], $args[2])) instanceof IXR_Error)
				return $auth;
			
			$config = Config::current();
			$trigger = Trigger::current();
			$post = new Post($args[0], array("filter" => false));
			
			$struct = array(
				'postid'            => $post->id,
				'userid'            => $post->user_id,
				'title'             => $post->title,
				'dateCreated'       => new IXR_Date(@date('Ymd\TH:i:s', @strtotime($post->created_at))),
				'description'       => $post->body,
				'link'              => $post->url(),
				'permaLink'         => $post->url(),
				'mt_basename'       => $post->url,
				'mt_excerpt'        => '',
				'mt_text_more'      => '',
				'mt_keywords'       => '',
				'mt_allow_pings'    => (int) $config->enable_trackbacking,
				'mt_allow_comments' => 0,
				'mt_convert_breaks' => '0');
			
			list($struct, $post) = $trigger->filter('metaWeblog_getPost', array($struct, $post), true);
			return array($struct);
		}
		
		/**
		 * Function: metaWeblog_newPost
		 * Creates a new post.
		 */
		public function metaWeblog_newPost($args) {
			if (($auth = $this->auth($args[1], $args[2])) instanceof IXR_Error)
				return $auth;
			else if (empty($args[3]['description']))
				return new IXR_Error(400, __("Body can't be blank."));
			
			$yaml = Spyc::YAMLDump(array("title" => $args[3]['title'], "body" => $args[3]['description']));
			
			$clean = sanitize(fallback($args[3]['mt_basename'], $args[3]['title'], true));
			$url = Post::check_url($clean);
			
			$timestamp = fallback($this->convertDateCreated($args[3]), datetime(), true);
						
			try {
				$sql = SQL::current();
				
				$result = $sql->query("SELECT `id`
				                       FROM `{$sql->prefix}users`
				                       WHERE `login` = ? and `password` = ?
				                       LIMIT 1",
				                       array($args[1], md5($args[2])), true);
				
				$user_id = $result->fetchColumn();
				$result->closeCursor();
				
				$sql->query("INSERT INTO `{$sql->prefix}posts`
				             ( yaml, feather, clean, url, user_id, created_at )
				             VALUES
				             ( ?, 'text', ?, ?, ?, ? )",
				             array($yaml, $clean, $url, $user_id, $timestamp), true);
			} catch (Exception $error) {
				return new IXR_Error(500, $error->getMessage());
			}
			
			$post_id = $sql->db->lastInsertId();
			
			# Send any and all pingbacks to URLs in the body
			$config = Config::current();
			if ($config->send_pingbacks)
				send_pingbacks($args[3]['description'], $post_id);
			
			$trigger = Trigger::current();
			$trigger->call('metaWeblog_newPost', array($args[3], $post_id), true);
			
			return $post_id;
		}
		
		/**
		 * Function: metaWeblog_editPost
		 * Updates a specified post.
		 */
		public function metaWeblog_editPost($args) {
			if (($auth = $this->auth($args[1], $args[2], 'edit_post')) instanceof IXR_Error)
				return $auth;
			else if (!Post::exists($args[0]))
				return new IXR_Error(404, __("Fake post ID, or nonexistant post."));
			else if (empty($args[3]['description']))
				return new IXR_Error(400, __("Body can't be blank."));
			
			$post = new Post($args[0]);
			$yaml = Spyc::YAMLDump(array("title" => $args[3]['title'], "body" => $args[3]['description']));

			$clean = sanitize(fallback($args[3]['mt_basename'], $args[3]['title'], true));
			
			$timestamp = fallback($this->convertDateCreated($args[3]), $post->created_at, true);
			
			$sql = SQL::current();
			$sql->query("UPDATE `{$sql->prefix}posts`
			             SET
			             `yaml` = ?, `clean` = ?, `url` = ?, `created_at` = ?, `updated_at` = ?
			             WHERE
			             `id` = ?
			             LIMIT 1",
			             array($yaml, $clean, $clean, $timestamp, datetime(), $args[0]));
			
			$trigger = Trigger::current();
			$trigger->call('metaWeblog_editPost', array($args[0], $args[3]), true);
			
			return true;
		}
		
		/**
		 * Function: blogger_deletePost
		 * Deletes a specified post.
		 */
		public function blogger_deletePost($args) {
			if (($auth = $this->auth($args[2], $args[3], 'delete_post')) instanceof IXR_Error)
				return $auth;
			else if (!Post::exists($args[1]))
				return new IXR_Error(404, __("Fake post ID, or nonexistant post."));
			
			$post = new Post($args[1]);
			$post->delete();
			return true;
		}
		
		/**
		 * Function: blogger_getUsersBlogs
		 * Returns information about the Chyrp installation.
		 */
		public function blogger_getUsersBlogs($args) {
			if (($auth = $this->auth($args[1], $args[2])) instanceof IXR_Error)
				return $auth;
			
			$config = Config::current();
			return array(array(
				'url'      => $config->url,
				'blogName' => $config->name,
				'blogid'   => 1));
		}
		
		/**
		 * Function: blogger_getUserInfo
		 * Retrieves a specified user.
		 */
		public function blogger_getUserInfo($args) {
			if (($auth = $this->auth($args[1], $args[2])) instanceof IXR_Error)
				return $auth;
			
			try {
				$sql = SQL::current();
				$result = $sql->query("SELECT `id`, `full_name`, `email`, `website`
				                       FROM `{$sql->prefix}users`
				                       WHERE `login` = ? and `password` = ?
				                       LIMIT 1",
				                       array($args[1], md5($args[2])));
			} catch (Exception $error) {
				return new IXR_Error(500, $error->getMessage());
			}
			
			$user = $result->fetchObject();
			$result->closeCursor();
			
			return array(array(
				'userid'    => $user->id,
				'nickname'  => $user->fullname,
				'firstname' => '',
				'lastname'  => '',
				'email'     => $user->email,
				'url'       => $user->website));
		}
		
		/**
		 * Function: mt_getRecentPostTitles
		 * Returns a bandwidth-friendly list of the most recent posts.
		 */
		public function mt_getRecentPostTitles($args) {
			if (($auth = $this->auth($args[1], $args[2])) instanceof IXR_Error)
				return $auth;
			
			if (($posts = $this->getRecentPosts($args[3])) instanceof IXR_Error)
				return $posts;
			
			$result = array();
			
			foreach ($posts as $post) {
				$post = new Post(null, array("read_from" => $post, "filter" => false));
				
				$result[] = array(
					'postid'      => $post->id,
					'userid'      => $post->user_id,
					'title'       => $post->title,
					'dateCreated' => new IXR_Date(@date('Ymd\TH:i:s', @strtotime($post->created_at))));
			}
			
			return $result;
		}
		
		/**
		 * Function: mt_getCategoryList
		 * Returns a list of categories.
		 */
		public function mt_getCategoryList($args) {
			if (($auth = $this->auth($args[1], $args[2])) instanceof IXR_Error)
				return $auth;
			
			$trigger = Trigger::current();
			$categories = array();
			return $trigger->filter('mt_getCategoryList', $categories);
		}
		
		/**
		 * Function: mt_getPostCategories
		 * Returns a list of all categories to which the post is assigned.
		 */
		public function mt_getPostCategories($args) {
			if (($auth = $this->auth($args[1], $args[2])) instanceof IXR_Error)
				return $auth;
			else if (!Post::exists($args[0]))
				return new IXR_Error(404, __("Fake post ID, or nonexistant post."));
			
			$trigger = Trigger::current();
			$categories = array();
			list($args[0], $categories) = $trigger->filter('mt_getPostCategories', array($args[0], $categories), true);
			return $categories;
		}
		
		/**
		 * Function: mt_setPostCategories
		 * Sets the categories for a post.
		 */
		public function mt_setPostCategories($args) {
			if (($auth = $this->auth($args[1], $args[2], 'edit_post')) instanceof IXR_Error)
				return $auth;
			else if (!Post::exists($args[0]))
				return new IXR_Error(404, __("Fake post ID, or nonexistant post."));
			
			$trigger = Trigger::current();
			$trigger->call('mt_setPostCategories', array($args[0], $args[3]), true);
			return true;
		}
		
		/**
		 * Function: mt_supportedTextFilters
		 * Returns an empty array, as this is not applicable for Chyrp.
		 */
		public function mt_supportedTextFilters() {
			return array();
		}
		
		/**
		 * Function: getRecentPosts
		 * Returns an array of the most recent posts.
		 */
		private function getRecentPosts($limit) {
			$config = Config::current();
			if (!in_array("text", $config->enabled_feathers))
				return new IXR_Error(500, __("Text feather is not enabled."));
			
			try {
				$sql = SQL::current();
				$result = $sql->query("SELECT *
				                       FROM `{$sql->prefix}posts`
				                       WHERE
				                       `feather` = 'text' AND
				                       `status` = 'public'
				                       ORDER BY
				                       `pinned` DESC,
				                       `created_at` DESC,
				                       `id` DESC
				                       LIMIT {$limit}");
				return $result->fetchAll(PDO::FETCH_OBJ);
			} catch (Exception $error) {
				return new IXR_Error(500, $error->getMessage());
			}
		}
		
		/**
		 * Function: convertDateCreated
		 * Converts an IXR_Date (in $args['dateCreated']) to SQL date format.
		 */
		private function convertDateCreated($args) {
			if (!array_key_exists('dateCreated', $args))
				return null;
			else {
				$args['dateCreated'] = date('Z') + $args['dateCreated']->getTimestamp();
				return date('Y-m-d H:i:s', $args['dateCreated']);
			}
		}
		
		/**
		 * Function: auth
		 * Authenticates a given login and password, and checks for appropriate permission
		 */
		private function auth($login, $password, $permission = 'add_post') {
			try {
				$sql = SQL::current();
				$result = $sql->query("SELECT `{$permission}`
				                       FROM `{$sql->prefix}groups`
				                       WHERE `id` =
				                       (
				                         SELECT  `group_id`
				                         FROM `{$sql->prefix}users`
				                         WHERE `login` = ? AND `password` = ?
				                         LIMIT 1
				                       )
				                       LIMIT 1",
				                       array($login, md5($password)));
			} catch (Exception $error) {
				return new IXR_Error(500, $error->getMessage());
			}
			
			$permission = $result->fetchColumn();
			$result->closeCursor();
			
			return ($permission) ? true : new IXR_Error(403, __("You don't have permission."));
		}
	}
	$server = new XMLRPC();
?>