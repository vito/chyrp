<?php
	require_once "includes/common.php";
	
	$trigger->call("top");
	
	switch($action) {
		case "index": case "search": case "drafts": case "feather":
			$theme->title = ($action == "feather") ? ucfirst($_GET['action']) : "" ;
			$theme->title = ($action == "search") ? fix(sprintf(__("Search results for \"%s\""), urldecode($query)), "html") : $theme->title ;
			$theme->title = ($action == "drafts") ? __("Drafts") : $theme->title ;
			$theme->load("layout/header.php", $GLOBALS);
			
			$trigger->call($action."_top");
			
			$count = 1;
			$shown_dates = array();
			foreach ($get_posts->fetchAll() as $post) {
				$post = new Post(null, array("read_from" => $post));
				if (!$post->theme_exists()) continue;
			
				$last = ($count == $get_posts->rowCount());
				$date_shown = in_array(when("m-d-Y", $post->created_at), $shown_dates);
				if (!in_array(when("m-d-Y", $post->created_at), $shown_dates))
					$shown_dates[] = when("m-d-Y", $post->created_at);
			
				$trigger->call("above_post");
				$theme->load("content/posts/".$post->feather.".php", $GLOBALS);
				$trigger->call("below_post");
				$count++;
			}
			if ($count == 1)
				$trigger->call("no_posts", $action);
			
			$theme->load("layout/footer.php", $GLOBALS);
			break;
		case "view": case "id":
			$count = 0;
			
			if ($action == "view")
				fallback($post, new Post(null, array("where" => "`url` = :url", "params" => array(":url" => $_GET['url']))));

			if (!$post->theme_exists())
				error(__("Error"), __("The feather theme file for this post does not exist. The post cannot be displayed."));

			$theme->title = $post->title();
			$theme->load("layout/header.php", $GLOBALS);

			$date_shown = true;
			$last = true;
		
			if ($post->status == "draft")
				$trigger->call("draft_view_top");
		
			$trigger->call("above_post");
			$theme->load("content/posts/".$post->feather.".php", $GLOBALS);
			$trigger->call("below_post");
		
			$theme->load("layout/footer.php", $GLOBALS);
			break;
		case "archive":
			if (empty($year) or empty($month)) {
				$theme->title = __("Archive");
				$theme->load("layout/header.php", $GLOBALS);

				$trigger->call("archive_list_top");
				
				if (!empty($year))
					$get_timestamps = $sql->query("select
					                               	distinct year(`created_at`) as `year`,
					                               	month(`created_at`) as `month`,
					                               	`created_at`, count(`id`) as `posts`
					                               from `".$sql->prefix."posts`
					                               where
					                               	year(`created_at`) = :year and
					                               	".$private.$enabled_feathers."
					                               group by year(`created_at`), month(`created_at`)
					                               order by `created_at` desc, `id` desc",
					                              array(
					                              	":year" => $year
					                              ));
				else
					$get_timestamps = $sql->query("select
					                               	distinct year(`created_at`) as `year`,
					                               	month(`created_at`) as `month`,
					                               	`created_at`, count(`id`) as `posts`
					                               from `".$sql->prefix."posts`
					                               where ".$private.$enabled_feathers."
					                               group by year(`created_at`), month(`created_at`)
					                               order by `created_at` desc, `id` desc");
					
				$count = (int) (!$get_timestamps->rowCount()); # Set $count to 1 so the "no posts" message shows
				
				while ($time = $get_timestamps->fetchObject()) {
					$trigger->call("archive_month", array(@date("F Y", mktime(0, 0, 0, $time->month + 1, 0, $time->year)), $route->url("archive/".when("Y/m/", $time->created_at))));
					$get_posts = $sql->query("select * from `".$sql->prefix."posts`
					                          where
					                          	`created_at` like :created_at and
					                          	".$private.$enabled_feathers."
					                          order by `created_at` desc, `id` desc",
					                         array(
					                         	":created_at" => when("Y-m", $time->created_at)."%"
					                         ));
					
					$split_wrapper = explode("{LIST}", $trigger->call("archive_list_wrapper", $time, true));
					echo $split_wrapper[0];
					foreach ($get_posts->fetchAll() as $post) {
						$post = new Post($post['id']);
						$trigger->call("archive_list_item");
					}
					echo $split_wrapper[1];
				}
			} else {
				if (!is_numeric($year) or !is_numeric($month))
					error(__("Error"), __("Please enter a valid year and month."));
				
				$theme->title = sprintf(__("Archive of %s"), @date("F Y", mktime(0, 0, 0, $month + 1, 0, $year)));
				$theme->load("layout/header.php", $GLOBALS);
				
				$trigger->call("archive_top", array($year, $month));
				
				$count = 1;
				$shown_dates = array();
				foreach ($get_posts->fetchAll() as $post) {
					$post = new Post($post['id']);
					if (!$post->theme_exists()) continue;
			
					$last = ($count == $get_posts->rowCount());
					$date_shown = in_array(when("m-d-Y", $post->created_at), $shown_dates);
					if (!in_array(when("m-d-Y", $post->created_at), $shown_dates))
						$shown_dates[] = when("m-d-Y", $post->created_at);
			
					$trigger->call("above_post");
					$theme->load("content/posts/".$post->feather.".php", $GLOBALS);
					$trigger->call("below_post");
					$count++;
				}
			}
			if ($count == 1)
				$trigger->call("no_posts", "archive");
							
			$theme->load("layout/footer.php", $GLOBALS);
			break;
		case "login":
			if ($user->logged_in())
				error(__("Error"), __("You're already logged in."));
			
			$theme->title = __("Log In");
			$theme->load("layout/header.php", $GLOBALS);
			
			$incorrect = isset($_GET['incorrect']);
			
			$theme->load("forms/user/login.php", $GLOBALS);
			
			$theme->load("layout/footer.php", $GLOBALS);
			break;
		case "register":
			if (!$config->can_register)
				error(__("Registration Disabled"), __("I'm sorry, but this site is not allowing registration."));
			if ($user->logged_in())
				error(__("Error"), __("You're already logged in."));
			
			$theme->title = __("Register");
			$theme->load("layout/header.php", $GLOBALS);
			$theme->load("forms/user/register.php", $GLOBALS);
			$theme->load("layout/footer.php", $GLOBALS);
			break;
		case "controls":
			if (!$user->logged_in())
				error(__("Error"), __("You must be logged in to access this area."));
			
			$theme->title = __("Controls");
			$theme->load("layout/header.php", $GLOBALS);
			$theme->load("forms/user/controls.php", $GLOBALS);
			$theme->load("layout/footer.php", $GLOBALS);
			break;
		case "feed":
			if (!isset($get_posts)) exit;
			
			header("Content-Type: application/atom+xml; charset=UTF-8");
			$get_latest_timestamp = $sql->query(preg_replace("/select (.*?) from/i", "select `created_at` from", $get_posts->queryString));
			$latest_timestamp = (!$get_latest_timestamp->rowCount()) ? 0 : $get_latest_timestamp->fetchColumn() ;
			
			require "includes/feed.php";
			break;
		case "bookmarklet":
			if (!$user->can("add_post"))
				error(__("Access Denied"), __("You do not have sufficient privileges to create posts."));
				
			if (empty($config->enabled_feathers))
				error(__("No Feathers"), __("Please install a feather or two in order to add a post."));

			require "includes/bookmarklet.php";
			break;
		case "page":
			fallback($page, new Page(null, array("where" => "`url` = :url", "params" => array(":url" => $_GET['url']))));
			
			if ($page) {
				$theme->title = $page->title;

				$page->body = $trigger->filter("markup_page_text", $page->body);
				
				if (file_exists(THEME_DIR."/content/".$page->url.".php"))
					$theme->load("content/".$page->url.".php", $GLOBALS);
				else {
					$theme->load("layout/header.php", $GLOBALS);
					$theme->load("content/page.php", $GLOBALS);
					$theme->load("layout/footer.php", $GLOBALS);
				}
			} else
				show_404($GLOBALS);
			
			break;
		default:
			$page_exists = false;
			foreach ($config->enabled_modules as $module) {
				if (file_exists(MODULES_DIR."/".$module."/pages/".$action.".php")) {
					require MODULES_DIR."/".$module."/pages/".$action.".php";
					$page_exists = true;
				}
			}
			
			foreach ($config->enabled_feathers as $feather) {
				if (file_exists(FEATHERS_DIR."/".$feather."/pages/".$action.".php")) {
					require FEATHERS_DIR."/".$feather."/pages/".$action.".php";
					$page_exists = true;
				}
			}
		
			if (file_exists(THEME_DIR."/pages/".$action.".php")) {
				$theme->load("pages/".$action.".php", $GLOBALS);
				$page_exists = true;
			}
			
			if (!$page_exists)
				show_404($GLOBALS);
			
			break;
	}
	
	$trigger->call("bottom");
	$sql->db = null;
	ob_end_flush();
