<?php
	class Comment {
		var $no_author = false;
		var $no_email = false;
		var $no_body = false;
		function find($id) {
			# I <3 <3 <3 this function.
			$sql = SQL::current();
			foreach ($sql->query("select * from `".$sql->prefix."comments`
			                      where `id` = :id",
			                     array(
			                     	":id" => $id
			                     ))->fetch() as $key => $val)
				$this->$key = $val;
		}
		function create($author, $email, $url, $body, $post_id, $status = null) {
			global $user, $current_user;
			if (!$this->user_can($post_id)) return;
			
			$post = new Post($post_id);
			$config = Config::current();
			$route = Route::current();
			if (!$status)
				$status = ($post->user_id == $current_user) ? "approved" : $config->default_comment_status ;
			if (!empty($config->akismet_api_key)) {
				require "lib/akismet.php";
				$comment = array(
					'author'    => $author,
					'email'     => $email,
					'website'   => $url,
					'body'      => $body,
					'permalink' => $post->url()
				);
				$akismet = new Akismet($config->url, $config->akismet_api_key, $comment);
				if ($akismet->isError(AKISMET_SERVER_NOT_FOUND) OR $akismet->isError(AKISMET_RESPONSE_FAILED)) {
					$id = $this->add($body, $author, $url, $email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $status, datetime(), $post_id, $current_user);
					if (isset($_POST['ajax']))
						exit("{ comment_id: ".$id." }");
					$route->redirect($post->url()."#comment_".$id);
				} elseif ($akismet->isError(AKISMET_INVALID_KEY)) {
					$id = $this->add($body, $author, $url, $email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $status, datetime(), $post_id, $current_user);
					if (isset($_POST['ajax']))
						exit("{ comment_id: ".$id." }");
					$route->redirect($post->url()."#comment_".$id);
				} else {
					if ($akismet->isSpam()) {
						$this->add($body, $author, $url, $email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], "spam", datetime(), $post_id, $current_user);
						error(__("Spam Comment"), __("Your comment has been marked as spam. It will have to be approved before it will show up.", "comments"));
					} else {
						$id = $this->add($body, $author, $url, $email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $status, datetime(), $post_id, $current_user);
						if (isset($_POST['ajax']))
							exit("{ comment_id: ".$id." }");
						$route->redirect($post->url()."#comment_".$id);
					}
				}
			} else {
				$id = $this->add($body, $author, $url, $email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $status, datetime(), $post_id, $current_user);
				if (isset($_POST['ajax']))
					exit("{ comment_id: ".$id." }");
				$route->redirect($post->url()."#comment_".$id);
			}
		}
		function add($body, $author, $url, $email, $ip, $agent, $status, $timestamp, $post_id, $user_id) {
			if (!empty($url)) {
				# Add the http:// if it isn't there.
				$parse = parse_url($url);
				if (!isset($parse["scheme"]))
					$url = "http://".$url;
			}
			
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
		function info($column, $comment_id = null) {
			if (is_null($comment_id)) return null; # Can't do anything without a comment ID.
			
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
		function edit_link($comment_id = null, $text = null, $before = null, $after = null){
			global $user;
			if (!$user->can('edit_comment')) return;
			fallback($text, __("Edit"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=edit&amp;sub=comment&amp;id='.$comment_id.'" title="Edit" class="comment_edit_link" id="comment_edit_'.$comment_id.'">'.$text.'</a>'.$after;
		}
		function delete_link($comment_id = null, $text = null, $before = null, $after = null){
			global $user;
			if (!$user->can('delete_comment')) return;
			fallback($text, __("Delete"));
			$config = Config::current();
			echo $before.'<a href="'.$config->url.'/admin/?action=delete&amp;sub=comment&amp;id='.$comment_id.'" title="Delete" class="comment_delete_link" id="comment_delete_'.$comment_id.'">'.$text.'</a>'.$after;
		}
		function author_link() {
			global $user;
		
			if ($this->author_url != "") # If a URL is set
				echo '<a href="'.$this->author_url.'">'.$this->author.'</a>';
			else # If not, just show their name
				echo $this->author;
		}
		function update($comment_id, $author, $author_email, $author_url, $body, $status, $timestamp) {
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
			            	":id" => $comment_id
			            ));
			$trigger = Trigger::current();
			$trigger->call('update_comment');
		}
		function delete($comment_id) {
			$sql = SQL::current();
			$sql->query("delete from `".$sql->prefix."comments`
			             where `id` = :id",
			            array(
			            	":id" => $comment_id
			            ));
			$trigger = Trigger::current();
			$trigger->call("delete_comment", $comment_id);
		}
		function user_can($post_id) {
			global $user;
			if (!$user->can("add_comment")) return false;
			
			$post = new Post($post_id);
			// assume allowed comments by default
			return empty($post->comment_status) or
			       !($post->comment_status == "closed" or 
			        ($post->comment_status == "registered_only" and !$user->logged_in()) or 
			        ($post->comment_status == "private" and !$user->can("add_comment_private")));
		}
		function user_count($user_id) {
			$sql = SQL::current();
			$count = $sql->query("select count(`id`) from `".$sql->prefix."comments`
			                      where `user_id` = :user_id",
			                     array(
			                     	":user_id" => $user_id
			                     ));
			return $count->fetchColumn();
		}
		function post_count($post_id) {
			$sql = SQL::current();
			$count = $sql->query("select count(`id`) from `".$sql->prefix."comments`
			                      where `post_id` = :post_id",
			                     array(
			                     	":post_id" => $post_id
			                     ));
			return $count->fetchColumn();
		}
	}
	$comment = new Comment();
