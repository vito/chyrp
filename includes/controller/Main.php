<?php
	/**
	 * Class: Main Controller
	 * The logic behind the Chyrp install.
	 */
	class MainController {
		/**
		 * Function: index
		 * Grabs the posts for the main page.
		 */
		public function index() {
			global $paginate, $private, $enabled_feathers, $get_posts, $user;
			$config = Config::current();
			$sql = SQL::current();
			$get_posts = $paginate->select("posts", # table
			                               "*", # fields
			                               $private.$enabled_feathers, # where
			                               "`pinned` desc, `created_at` desc, `id` desc", # order
			                               $config->posts_per_page, "page",
			                               array());
		}
		
		/**
		 * Function: archive
		 * Grabs the posts for the Archive page when viewing a year or a month.
		 */
		public function archive() {
			global $paginate, $private, $enabled_feathers, $year, $month, $get_posts;
			$year = fallback($_GET['year'], "", true);
			$month = fallback($_GET['month'], "", true);
			if (empty($year) or empty($month)) return;
			
			$config = Config::current();
			$sql = SQL::current();
			$get_posts = $paginate->select("posts",
			                               "*",
			                               "`created_at` like :date and
			                               ".$private.$enabled_feathers,
			                               "`pinned` desc, `created_at` desc, `id` desc",
			                               $config->posts_per_page, "page",
			                               array(
			                               	':date' => $year.'-'.$month.'%'
			                               ));
		}
		
		/**
		 * Function: search
		 * Grabs the posts for a search query.
		 */
		public function search() {
			global $paginate, $private, $enabled_feathers, $query, $get_posts;
			$query = fallback($_GET['query'], "", true);
			$config = Config::current();
			$sql = SQL::current();
			$get_posts = $paginate->select("posts",
			                               "*",
			                               "`yaml` like :query and
			                               ".$private.$enabled_feathers,
			                               "`pinned` desc, `created_at` desc, `id` desc",
			                               $config->posts_per_page, "page",
			                               array(
			                               	':query' => '%'.urldecode($query).'%'
			                               ));
		}
		
		/**
		 * Function: drafts
		 * Grabs the posts for viewing the Drafts lists. Shows an error if the user lacks permissions.
		 */
		public function drafts() {
			global $user, $paginate, $private, $enabled_feathers, $get_posts;
			if (!$user->can("view_draft"))
				error(__("Access Denied"), __("You do not have sufficient privileges to view drafts."));
			
			$config = Config::current();
			$sql = SQL::current();
			$get_posts = $paginate->select("posts",
			                               "*",
			                               "`status` = 'draft'",
			                               "`pinned` desc, `created_at` desc, `id` desc",
			                               $config->posts_per_page);
		}
		
		/**
		 * Function: feather
		 * Views posts of a specific feather.
		 */
		public function feather() {
			global $paginate, $private, $enabled_feathers, $plural_feathers, $get_posts;
			
			$config = Config::current();
			$sql = SQL::current();
			$get_posts = $paginate->select("posts",
			                               "*",
			                               $private.$enabled_feathers." and
			                               `feather` = :feather",
			                               "`pinned` desc, `created_at` desc, `id` desc",
			                               $config->posts_per_page, "page",
			                               array(
			                               	':feather' => $plural_feathers[$_GET['action']]
			                               ));
		}
		
		/**
		 * Function: feed
		 * Grabs posts for the feed.
		 */
		public function feed() {
			global $paginate, $private, $enabled_feathers, $get_posts;
			
			$config = Config::current();
			$sql = SQL::current();
			$get_posts = $paginate->select("posts",
			                               "*",
			                               $private.$enabled_feathers,
			                               "`pinned` desc, `created_at` desc, `id` desc",
			                               $config->posts_per_page);
		}
		
		/**
		 * Function: view
		 * Views a post.
		 */
		public function view() {
			global $action, $private, $enabled_feathers, $post;
			
			$config = Config::current();
			$sql = SQL::current();
			if (!$config->clean_urls)
				return $grab_post = $sql->select("posts",
				                                 "*",
				                                 $private.$enabled_feathers." and
				                                 `url` = :url",
				                                 "id",
				                                 array(
				                                 	':url' => $_GET['url']
				                                 ), 1);
			
			# Check for a post...
			$where = "";
			$times = array("year", "month", "day", "hour", "minute", "second");
			
			preg_match_all("/\(([^\)]+)\)/", $config->post_url, $matches);
			$params = array();
			foreach ($matches[1] as $attr)
				if (in_array($attr, $times))
				{
					$where.= " and ".$attr."(`created_at`) = :created".$attr;
					$params[':created'.$attr] = $_GET[$attr];
				}
				else
				{
					$where.= " and `".$attr."` = :attr".$attr;
					$params[':attr'.$attr] = $_GET[$attr];
				}
		
			$post = new Post(null, array("where" => $private.$enabled_feathers.$where, "params" => $params));
			
			if ($post->no_results) {
				# Check for a page...
				$url = fallback($_GET['url'], "", true);
				$check_page = $sql->count("pages",
				                          "`url` = :url",
				                          array(
				                          	':url' => $url
				                          ));
				if ($check_page == 1)
					return $action = $url;
			}
			
			return (!$post->no_results) ? $action = "view" : $action = $_GET['url'] ;
		}
		
		/**
		 * Function: id
		 * Views a post by its static ID.
		 */
		public function id() {
			global $post;
			$post = new Post($_GET['id']);
		}
		
		/**
		 * Function: theme_preview
		 * Handles theme previewing.
		 */
		public function theme_preview() {
			global $user, $action;
			if (!$user->can("change_settings")) {
				$this->index();
				return $action = "index";
			}
			if (empty($_GET['theme']))
				error(__("Error"), __("Please specify a theme to preview."));
			
			$this->index();
			return $action = "index";
		}
		
		/**
		 * Function: toggle_admin
		 * Toggles the Admin control panel (if available).
		 */
		public function toggle_admin() {
			if (!isset($_COOKIE['chyrp_hide_admin']))
				setcookie("chyrp_hide_admin", "true", time() + 2592000, "/"); # 30 days
			else
				setcookie("chyrp_hide_admin", "", time() - 2592000, "/");
			
			$route = Route::current();
			$route->redirect('/');
		}
		
		/**
		 * Function: process_registration
		 * Process registration. If registration is disabled or if the user is already logged in, it will error.
		 */
		public function process_registration() {
			global $user;
			
			$config = Config::current();
			if (!$config->can_register)
				error(__("Registration Disabled"), __("I'm sorry, but this site is not allowing registration."));
			if ($user->logged_in())
				error(__("Error"), __("You're already logged in."));
			
			if (empty($_POST['login']))
				error(__("Error"), __("Please enter a username for your account."));
			
			$sql = SQL::current();
			$check_user = $sql->query("select `id` from `".$sql->prefix."users`
			                           where `login` = :login",
			                          array(
			                          	':login' => $_POST['login']
			                          ));
			if ($check_user->rowCount() == 1)
				error(__("Error"), __("That username is already in use."));
			if (empty($_POST['password1']) or empty($_POST['password2']))
				error(__("Error"), __("Password cannot be blank."));
			if (empty($_POST['email']))
				error(__("Error"), __("Password cannot be blank."));
			if ($_POST['password1'] != $_POST['password2'])
				error(__("Error"), __("Passwords do not match."));
			if (!eregi("^[[:alnum:]][a-z0-9_.-\+]*@[a-z0-9.-]+\.[a-z]{2,6}$",$_POST['email']))
				error(__("Error"), __("E-Mail address cannot be blank."));
	
			$user->add($_POST['login'], $_POST['password1'], $_POST['email']);
			
			setcookie("chyrp_user_id", $sql->db->lastInsertId(), time() + 2592000, "/"); # 30 days
			setcookie("chyrp_password", md5($_POST['password1']), time() + 2592000, "/"); # 30 days
			
			$route = Route::current();
			$route->redirect('/');
		}
		
		/**
		 * Function: process_login
		 * Process logging in. If the username and password are incorrect or if the user is already logged in, it will error.
		 */
		public function process_login() {
			global $user;
			if (!$user->authenticate($_POST['login'], md5($_POST['password'])))
				error(__("Error"), __("Login incorrect."));
			if ($user->logged_in())
				error(__("Error"), __("You're already logged in."));
			
			$sql = SQL::current();
			$get_id = $sql->query("select `id` from `".$sql->prefix."users`
			                       where `login` = :login",
			                      array(
			                      	':login' => $_POST['login']
			                      ));
			
			setcookie("chyrp_user_id", $get_id->fetchColumn(), time() + 2592000, "/"); # 30 days
			setcookie("chyrp_password", md5($_POST['password']), time() + 2592000, "/"); # 30 days
			
			$route = Route::current();
			$route->redirect('/');
		}
		
		/**
		 * Function: logout
		 * Logs the current user out. If they are not logged in, it will error.
		 */
		public function logout() {
			global $user;
			if (!$user->logged_in())
				error(__("Error"), __("You aren't logged in."));
			
			setcookie("chyrp_user_id", "", time() - 2592000, "/");
			setcookie("chyrp_password", "", time() - 2592000, "/");
			
			$route = Route::current();
			$route->redirect('/');
		}
		
		/**
		 * Function: update_self
		 * Updates the current user when the form is submitted. Shows an error if they aren't logged in.
		 */
		public function update_self() {
			global $user, $current_user;
			if (empty($_POST)) return;
			if (!$user->logged_in())
				error(__("Error"), __("You must be logged in to access this area."));

			$password = (!empty($_POST['new_password1']) and $_POST['new_password1'] == $_POST['new_password2']) ? 
									md5($_POST['new_password1']) : 
									$user->info("password") ;

			$user->update($user->info("id"), $user->info("login"), $password, $_POST['full_name'], $_POST['email'], $_POST['website'], $user->info("group_id"));
			
			setcookie("chyrp_password", $password, time() + 2592000, "/"); # 30 days
			
			$route = Route::current();
			$route->redirect('/');
		}
	}
	$main = new MainController();
