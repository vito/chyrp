<?php
	$current_comment = array("id" => 0);

	/**
	 * Class: Comment
	 * The model for the Comments SQL table.
	 */
	class Comment {
		public $no_results = false;

		/**
		 * Function: __construct
		 * Grabs the specified comment and injects it into the <Comment> class.
		 *
		 * Parameters:
		 *     $comment_id - The comment's unique ID.
		 *     $options - An array of options:
		 *         where: A SQL query to grab the comment by.
		 *         params: Parameters to use for the "where" option.
		 *         read_from: An associative array of values to load into the <Comment> class.
		 */
		public function __construct($comment_id, $options = array()) {
			global $current_comment;

			$where = fallback($options["where"], "", true);
			$params = isset($options["params"]) ? $options["params"] : array();
			$read_from = (isset($options["read_from"])) ? $options["read_from"] : array() ;

			$sql = SQL::current();
			if ((!empty($read_from) && $read_from))
				$read = $read_from;
			elseif (isset($comment_id) and $comment_id == $current_comment["id"])
				$read = $current_comment;
			elseif (!empty($where))
				$read = $sql->select("comments",
				                     "*",
				                     $where,
				                     "id",
				                     $params,
				                     1)->fetch();
			else
				$read = $sql->select("comments",
				                     "*",
				                     "`id` = :commentid",
				                     "id",
				                     array(
				                         ":commentid" => $comment_id
				                     ),
				                     1)->fetch();

			if (!count($read) or !$read)
				return $this->no_results = true;

			foreach ($read as $key => $val) {
				if (!is_int($key))
					$this->$key = $val;

				$current_comment[$key] = $val;
			}

			if ($this->user_id)
				$this->user = new User($this->user_id);
		}

		/**
		 * Function: create
		 * Attempts to create a comment using the passed information. If a Defensio API key is present, it will check it.
		 *
		 * Parameters:
		 *     $author - The name of the commenter.
		 *     $email - The commenter's email.
		 *     $url - The commenter's website.
		 *     $body - The comment.
		 *     $post_id - The ID of the <Post> they're commenting on.
		 *     $type - The type of comment. Optional, used for trackbacks/pingbacks.
		 */
		static function create($author, $email, $url, $body, $post_id, $type = null) {
			if (!$this->user_can($post_id)) return;

			$post = new Post($post_id);
			$config = Config::current();
			$route = Route::current();
			$visitor = Visitor::current();

			if (!$type) {
				$status = ($post->user_id == $visitor->id) ? "approved" : $config->default_comment_status ;
				$type = "comment";
			} else
				$status = $type;

			if (!empty($config->akismet_api_key)) {
				require_once "lib/Akismet.class.php";

				$akismet = new Akismet($config->url, $config->akismet_api_key);
				$akismet->setCommentAuthor($author);
				$akismet->setCommentAuthorEmail($email);
				$akismet->setCommentAuthorURL($url);
				$akismet->setCommentContent($body);
				$akismet->setPermalink($post->url());
				$akismet->setCommentType($type);

				if ($akismet->isKeyValid() && $akismet->isCommentSpam()) {
					self::add($body, $author, $url, $email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], "spam", datetime(), $post_id, $visitor->id);
					error(__("Spam Comment"), __("Your comment has been marked as spam. It will have to be approved before it will show up.", "comments"));
				} else {
					$id = self::add($body, $author, $url, $email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $status, datetime(), $post_id, $visitor->id);
					if (isset($_POST['ajax']))
						exit("{ comment_id: ".$id." }");
					$route->redirect($post->url()."#comment_".$id);
				}
			} else {
				$id = self::add($body, $author, $url, $email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $status, datetime(), $post_id, $visitor->id);
				if (isset($_POST['ajax']))
					exit("{ comment_id: ".$id." }");
				$route->redirect($post->url()."#comment_".$id);
			}
		}

		/**
		 * Function: add
		 * Adds a comment to the database.
		 *
		 * Parameters:
		 *     $body - The comment.
		 *     $author - The name of the commenter.
		 *     $url - The commenter's website.
		 *     $email - The commenter's email.
		 *     $ip - The commenter's IP address.
		 *     $agent - The commenter's user agent.
		 *     $status - The new comment's status.
		 *     $timestamp - The new comment's timestamp of creation.
		 *     $post_id - The ID of the <Post> they're commenting on.
		 *     $user_id - If the user is logged in, this should be their user ID. Optional.
		 */
		static function add($body, $author, $url, $email, $ip, $agent, $status, $timestamp, $post_id, $user_id) {
			if (!empty($url)) # Add the http:// if it isn't there.
				if (!parse_url($url, PHP_URL_SCHEME))
					$url = "http://".$url;

			$sql = SQL::current();
			$sql->query("insert into `".$sql->prefix."comments`
			             (`body`, `author`, `author_url`, `author_email`, `author_ip`,
			              `author_agent`, `status`, `created_at`, `post_id`, `user_id`)
			             values
			             (:body, :author, :author_url, :author_email, :author_ip,
			              :author_agent, :status, :created_at, :post_id, :user_id)",
			            array(
			                ":body" => $body,
			                ":author" => strip_tags($author),
			                ":author_url" => strip_tags($url),
			                ":author_email" => strip_tags($email),
			                ":author_ip" => ip2long($ip),
			                ":author_agent" => $agent,
			                ":status" => $status,
			                ":created_at" => $timestamp,
			                ":post_id" => $post_id,
			                ":user_id"=> $user_id
			            ));
			$id = $sql->db->lastInsertId();

			$trigger = Trigger::current();
			$trigger->call('add_comment', $id);
			return $id;
		}
		static function info($column, $comment_id = null) {
			if (is_null($comment_id)) return null;

			$sql = SQL::current();
			$grab_info = $sql->query("select `".$column."` from `".$sql->prefix."comments`
			                          where `id` = :id",
			                         array(
			                             ":id" => $comment_id
			                         ));
			if ($grab_info->rowCount() == 1)
				return $grab_info->fetchColumn();
			else return null;
		}
		public function edit_link($text = null, $before = null, $after = null){
			$visitor = Visitor::current();
			if (!$visitor->group()->can('edit_comment')) return;
			fallback($text, __("Edit"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=edit&amp;sub=comment&amp;id='.$this->id.'" title="Edit" class="comment_edit_link" id="comment_edit_'.$comment_id.'">'.$text.'</a>'.$after;
		}
		public function delete_link($text = null, $before = null, $after = null){
			$visitor = Visitor::current();
			if (!$visitor->group()->can('delete_comment')) return;
			fallback($text, __("Delete"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=delete&amp;sub=comment&amp;id='.$this->id.'" title="Delete" class="comment_delete_link" id="comment_delete_'.$comment_id.'">'.$text.'</a>'.$after;
		}
		public function author_link() {
			if ($this->author_url != "") # If a URL is set
				echo '<a href="'.$this->author_url.'">'.$this->author.'</a>';
			else # If not, just show their name
				echo $this->author;
		}
		public function update($author, $author_email, $author_url, $body, $status, $timestamp) {
			$sql = SQL::current();
			$sql->query("update `".$sql->prefix."comments`
			             set
			                 `author` = :author,
			                 `author_email` = :author_email,
			                 `author_url` = :author_url,
			                 `body` = :body,
			                 `status` = :status,
			                 `created_at` = :created_at
			             where `id` = :id",
			            array(
			                ":author" => $author,
			                ":author_email" => $author_email,
			                ":author_url" => $author_url,
			                ":body" => $body,
			                ":status" => $status,
			                ":created_at" => $timestamp,
			                ":id" => $this->id
			            ));
			$trigger = Trigger::current();
			$trigger->call("update_comment", $this);
		}
		public function delete() {
			$sql = SQL::current();
			$sql->query("delete from `".$sql->prefix."comments`
			             where `id` = :id",
			            array(
			                ":id" => $this->id
			            ));
			$trigger = Trigger::current();
			$trigger->call("delete_comment", $this->id);
		}
		static function user_can($post_id) {
			$visitor = Visitor::current();
			if (!$visitor->group()->can("add_comment")) return false;

			$post = new Post($post_id, array("filter" => false));
			// assume allowed comments by default
			return empty($post->comment_status) or
			       !($post->comment_status == "closed" or
			        ($post->comment_status == "registered_only" and !logged_in()) or
			        ($post->comment_status == "private" and !$visitor->group()->can("add_comment_private")));
		}
		static function user_count($user_id) {
			$sql = SQL::current();
			$count = $sql->query("select count(`id`) from `".$sql->prefix."comments`
			                      where `user_id` = :user_id",
			                     array(
			                         ":user_id" => $user_id
			                     ));
			return $count->fetchColumn();
		}
		static function post_count($post_id) {
			$sql = SQL::current();
			$count = $sql->query("select count(`id`) from `".$sql->prefix."comments`
			                      where `post_id` = :post_id and (
	                                  `status` != 'denied' or (
                                          `status` = 'denied' and (
                                              `author_ip` = :current_ip or
                                              `user_id` = :user_id
                                          )
                                      )
                                  ) and
                                  `status` != 'spam'",
			                     array(
			                         ":post_id" => $post_id,
		                             ":current_ip" => ip2long($_SERVER['REMOTE_ADDR']),
		                             ":user_id" => Visitor::current()->id
			                     ));
			return $count->fetchColumn();
		}
	}
