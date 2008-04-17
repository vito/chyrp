<?php
	error_reporting(E_ALL ^ E_NOTICE);
	define('XML_RPC', true);
	require_once 'common.php';
	require_once INCLUDES_DIR.'/lib/ixr.php';
	if (!defined('XML_RPC_FEATHER')) define('XML_RPC_FEATHER', 'text');
	
	/**
	 * Class: XMLRPC
	 */
	class XMLRPC extends IXR_Server {
		/**
		 * Function: __construct
		 * Registers the various XMLRPC methods.
		 */
		public function __construct() {
			set_exception_handler(array($this, 'exception_handler'));
			
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
			$this->auth($args[1], $args[2]);
			
			$config = Config::current();
			$trigger = Trigger::current();
			$result = array();
			
			foreach ($this->getRecentPosts($args[3]) as $post) {
				$post = new Post(null, array('read_from' => $post, 'filter' => false));
				
				$struct = array(
					'postid'            => $post->id,
					'userid'            => $post->user_id,
					'title'             => $post->title,
					'dateCreated'       => new IXR_Date(when('Ymd\TH:i:s', $post->created_at)),
					'description'       => $post->body,
					'link'              => $post->url(),
					'permaLink'         => $post->url(),
					'mt_basename'       => $post->url,
					'mt_allow_pings'    => (int) $config->enable_trackbacking);
				
				list($post, $struct) = $trigger->filter('metaWeblog_getPost', array($post, $struct), true);
				$result[] = $struct;
			}
			
			return $result;
		}
		
		/**
		 * Function: metaWeblog_getCategories
		 * Returns a list of all categories to which the post is assigned.
		 */
		public function metaWeblog_getCategories($args) {
			$this->auth($args[1], $args[2]);
			
			$trigger = Trigger::current();
			$categories = array();
			return $trigger->filter('metaWeblog_getCategories', $categories);
		}
		
		/**
		 * Function: metaWeblog_newMediaObject
		 * Uploads a file to the server.
		 */
		public function metaWeblog_newMediaObject($args) {
			$this->auth($args[1], $args[2]);
			
			$file = unique_filename(trim($args[3]['name'], '/'));
			$path = MAIN_DIR.'/upload/'.$file;
			
			if (file_put_contents($path, $args[3]['bits']) === false)
				return new IXR_Error(500, __('Failed to write file.'));
			
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
			$this->auth($args[1], $args[2]);
			
			$config = Config::current();
			$trigger = Trigger::current();
			
			$post = new Post($args[0], array('filter' => false));
			$struct = array(
				'postid'            => $post->id,
				'userid'            => $post->user_id,
				'title'             => $post->title,
				'dateCreated'       => new IXR_Date(when('Ymd\TH:i:s', $post->created_at)),
				'description'       => $post->body,
				'link'              => $post->url(),
				'permaLink'         => $post->url(),
				'mt_basename'       => $post->url,
				'mt_allow_pings'    => (int) $config->enable_trackbacking);
			
			list($post, $struct) = $trigger->filter('metaWeblog_getPost', array($post, $struct), true);
			return array($struct);
		}
		
		/**
		 * Function: metaWeblog_newPost
		 * Creates a new post.
		 */
		public function metaWeblog_newPost($args) {
			$this->auth($args[1], $args[2], 'edit');
			
			# Support for extended body
			if (empty($args[3]['mt_text_more']))
				$body = $args[3]['description'];
			else
				$body = $args[3]['description'].'<!--more-->'.$args[3]['mt_text_more'];
			
			# Add excerpt to body so it isn't lost
			if (!empty($args[3]['mt_excerpt']))
				$body = $args[3]['mt_excerpt'].$body;
			
			if (trim($body) == '')
				return new IXR_Error(500, __("Body can't be blank."));
			
			# Support for adding tags to post xml
			if (isset($args[3]['mt_tags']))
				$_POST['option']['tags'] = $args[3]['mt_tags'];
			
			# Support for adding comment_status to post xml
			if (isset($args[3]['mt_allow_comments']))
				$_POST['option']['comment_status'] = ($args[3]['mt_allow_comments'] == 1) ? 'open' : 'closed';
			
			$clean = sanitize(fallback($args[3]['mt_basename'], $args[3]['title'], true));
			$url = Post::check_url($clean);
			
			$_POST['created_at'] = fallback($this->convertFromDateCreated($args[3]), datetime(), true);
			$_POST['feather'] = XML_RPC_FEATHER;
			if ($args[4] === 0) $_POST['draft'] = true;
			
			$post = Post::add(
				array(
					'title' => $args[3]['title'],
					'body' => $body
				),
				$clean,
				$url);

			$trigger = Trigger::current();
			$trigger->call('metaWeblog_newPost', array($post, $args[3]), true);
			
			# Send any and all pingbacks to URLs in the body
			$config = Config::current();
			if ($config->send_pingbacks)
				send_pingbacks($args[3]['description'], $post->id);
			
			return $post->id;		
		}
		
		/**
		 * Function: metaWeblog_editPost
		 * Updates a specified post.
		 */
		public function metaWeblog_editPost($args) {
			$this->auth($args[1], $args[2], 'edit');
			
			if (!Post::exists($args[0]))
				throw new Exception (__('Fake post ID, or nonexistant post.'));
			
			# Support for extended body
			if (empty($args[3]['mt_text_more']))
				$body = $args[3]['description'];
			else
				$body = $args[3]['description'].'<!--more-->'.$args[3]['mt_text_more'];
			
			# Add excerpt to body so it isn't lost
			if (!empty($args[3]['mt_excerpt']))
				$body = $args[3]['mt_excerpt'].$body;
			
			if (trim($body) == '')
				return new IXR_Error(500, __("Body can't be blank."));
			
			# Support for adding tags to post xml
			if (isset($args[3]['mt_tags']) and module_enabled('tags'))
				$_POST['option']['tags'] = $args[3]['mt_tags'];
			
			# Support for adding comment_status to post xml
			if (isset($args[3]['mt_allow_comments']) and module_enabled('comments'))
				$_POST['option']['comment_status'] = ($args[3]['mt_allow_comments'] == 1) ? 'open' : 'closed';
			
			$post = new Post($args[0], array('filter' => false));
			$post->update(
				array(
					'title' => $args[3]['title'],
					'body'  => $body
				),
				null,
				($args[4] !== 0) ? 'public' : 'draft',
				sanitize(
					fallback(
						$args[3]['mt_basename'],
						$args[3]['title'],
						true
					)
				),
				fallback(
					$this->convertFromDateCreated($args[3]),
					$post->created_at,
					true
				));
			
			$trigger = Trigger::current();
			$trigger->call('metaWeblog_editPost', array($post, $args[3]), true);
			
			return true;
		}
		
		/**
		 * Function: blogger_deletePost
		 * Deletes a specified post.
		 */
		public function blogger_deletePost($args) {
			$this->auth($args[2], $args[3], 'delete');
			
			if (!Post::exists($args[1]))
				throw new Exception (__('Fake post ID, or nonexistant post.'));
			
			$post = new Post($args[1]);
			$post->delete();
			return true;
		}
		
		/**
		 * Function: blogger_getUsersBlogs
		 * Returns information about the Chyrp installation.
		 */
		public function blogger_getUsersBlogs($args) {
			$this->auth($args[1], $args[2]);
			
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
			global $user;
			
			$this->auth($args[1], $args[2]);
			
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
			$this->auth($args[1], $args[2]);
			
			$result = array();
			
			foreach ($this->getRecentPosts($args[3]) as $post) {
				$post = new Post(null, array('read_from' => $post, 'filter' => false));

				$result[] = array(
					'postid'      => $post->id,
					'userid'      => $post->user_id,
					'title'       => $post->title,
					'dateCreated' => new IXR_Date(when('Ymd\TH:i:s', $post->created_at)));
			}

			return $result;
		}
		
		/**
		 * Function: mt_getCategoryList
		 * Returns a list of categories.
		 */
		public function mt_getCategoryList($args) {
			$this->auth($args[1], $args[2]);
			
			$trigger = Trigger::current();
			$categories = array();
			return $trigger->filter('mt_getCategoryList', $categories);
		}
		
		/**
		 * Function: mt_getPostCategories
		 * Returns a list of all categories to which the post is assigned.
		 */
		public function mt_getPostCategories($args) {
			$this->auth($args[1], $args[2]);
			
			if (!Post::exists($args[0]))
				return new IXR_Error(500, __('Fake post ID, or nonexistant post.'));
			
			$post = new Post($args[0]);
			
			$trigger = Trigger::current();
			$categories = array();
			list($post, $categories) = $trigger->filter('mt_getPostCategories', array($post, $categories), true);
			return $categories;
		}
		
		/**
		 * Function: mt_setPostCategories
		 * Sets the categories for a post.
		 */
		public function mt_setPostCategories($args) {
			$this->auth($args[1], $args[2], 'edit');
			
			if (!Post::exists($args[0]))
				throw new Exception(__('Fake post ID, or nonexistant post.'));
			
			$post = new Post($args[0]);
			
			$trigger = Trigger::current();
			$trigger->call('mt_setPostCategories', array($post, $args[3]), true);
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
			global $user;
			
			$statuses = "'public'";
			$statuses.= ($user->can('view_drafts')) ? ", 'draft'" : '';
			
			$config = Config::current();
			if (!in_array(XML_RPC_FEATHER, $config->enabled_feathers))
				throw new Exception(__(XML_RPC_FEATHER.' feather is not enabled.'));
			
			$sql = SQL::current();
			$result = $sql->query("SELECT *
			                       FROM `{$sql->prefix}posts`
			                       WHERE
			                       `feather` = ? AND
			                       `status` IN ( {$statuses} )
			                       ORDER BY
			                       `pinned` DESC,
			                       `created_at` DESC,
			                       `id` DESC
			                       LIMIT {$limit}",
			                        array(XML_RPC_FEATHER));
			return $result->fetchAll(PDO::FETCH_OBJ);
		}
		
		/**
		 * Function: convertFromDateCreated
		 * Converts an IXR_Date (in $args['dateCreated']) to SQL date format.
		 */
		private function convertFromDateCreated($args) {
			if (array_key_exists('dateCreated', $args))
				return when('Y-m-d H:i:s', $args['dateCreated']->getIso());
			else
				return null;
		}
		
		/**
		 * Function: auth
		 * Authenticates a given login and password, and checks for appropriate permission
		 */
		private function auth($login, $password, $do = 'add') {
			global $current_user, $user, $group;
			
			$current_user = $user->authenticate($login, md5($password));
			
			$user->load($current_user, md5($password));
			$group->load();
			
			if (!$user->can($do.'_post'))
				throw new Exception(__(sprintf("You don't have permission to %s posts.", $do)));
		}
		
		static public function exception_handler($exception) {
			$ixr_error = new IXR_Error(500, $exception->getMessage());
			echo $ixr_error->getXml();
		}
	}
	$server = new XMLRPC();
?>