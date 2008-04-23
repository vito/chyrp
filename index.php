<?php
	require_once "includes/common.php";

	$trigger->call("top");

	switch($action) {
		case "index": case "search": case "drafts": case "feather":
			$theme->title = ($action == "feather") ? ucfirst($_GET['action']) : "" ;
			$theme->title = ($action == "search") ? fix(sprintf(__("Search results for \"%s\""), urldecode($query)), "html") : $theme->title ;
			$theme->title = ($action == "drafts") ? __("Drafts") : $theme->title ;

			$shown_dates = array();
			$posts = array();
			foreach ($get_posts->fetchAll() as $post) {
				$post = new Post(null, array("read_from" => $post));
				if (!$post->theme_exists()) continue;

				$post->date_shown = in_array(when("m-d-Y", $post->created_at), $shown_dates);
				if (!in_array(when("m-d-Y", $post->created_at), $shown_dates))
					$shown_dates[] = when("m-d-Y", $post->created_at);

				$posts[] = $post;
			}

			$file = ($theme->file_exists("content/".$action)) ?
			        "content/".$action :
			        "content/index" ;
			$context = array("posts" => $posts);
			if ($action == "search")
				$context["search"] = urldecode($query);
			if ($action == "feather")
				$context["feather"] = $_GET['action'];
			$theme->load($file, $context);

			break;
		case "view": case "id":
			if ($action == "view")
				fallback($post, new Post(null, array("where" => "`url` = :url", "params" => array(":url" => $_GET['url']))));

			if (!$post->theme_exists())
				error(__("Error"), __("The feather theme file for this post does not exist. The post cannot be displayed."));

			$theme->title = $post->title();

			$post->date_shown = true;

			$theme->load("content/view", array("post" => $post));
			break;
		case "archive":
			if (empty($year) or empty($month)) {
				$theme->title = __("Archive");

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

				$archives = array();
				while ($time = $get_timestamps->fetchObject()) {
					$timestamp = @mktime(0, 0, 0, $time->month + 1, 0, $time->year);
					$archives[$timestamp] = array("posts" => array(),
					                              "year" => $time->year,
					                              "month" => @date("F", $timestamp),
					                              "url" => $route->url("archive/".when("Y/m/", $time->created_at)));

					$get_posts = $sql->query("select * from `".$sql->prefix."posts`
					                          where
					                              `created_at` like :created_at and
					                              ".$private.$enabled_feathers."
					                          order by `created_at` desc, `id` desc",
					                         array(
					                             ":created_at" => when("Y-m", $time->created_at)."%"
					                         ));

					foreach ($get_posts->fetchAll() as $post) {
						$archives[$timestamp]["posts"][] = new Post(null, array("read_from" => $post));
					}
				}

				$theme->load("content/archive", array("archives" => $archives));
			} else {
				if (!is_numeric($year) or !is_numeric($month))
					error(__("Error"), __("Please enter a valid year and month."));

				$timestamp = @mktime(0, 0, 0, $month + 1, 0, $year);
				$theme->title = sprintf(__("Archive of %s"), @date("F Y", $timestamp));

				$shown_dates = array();
				$posts = array();
				foreach ($get_posts->fetchAll() as $post) {
					$post = new Post(null, array("read_from" => $post));
					if (!$post->theme_exists()) continue;

					$post->date_shown = in_array(when("m-d-Y", $post->created_at), $shown_dates);
					if (!in_array(when("m-d-Y", $post->created_at), $shown_dates))
						$shown_dates[] = when("m-d-Y", $post->created_at);

					$posts[] = $post;
				}

				$theme->load("content/archive", array("posts" => $posts,
				                                      "archive" => array("year" => $year,
				                                                         "month" => @date("F", $timestamp)
				                                                   )
				                                ));
			}
			break;
		case "login":
			if (logged_in())
				error(__("Error"), __("You're already logged in."));

			$theme->title = __("Log In");
			$theme->load("forms/user/login", array("incorrect" => isset($_GET['incorrect'])));

			break;
		case "register":
			if (!$config->can_register)
				error(__("Registration Disabled"), __("I'm sorry, but this site is not allowing registration."));
			if (logged_in())
				error(__("Error"), __("You're already logged in."));

			$theme->title = __("Register");
			$theme->load("forms/user/register");

			break;
		case "controls":
			if (!logged_in())
				error(__("Error"), __("You must be logged in to access this area."));

			$theme->title = __("Controls");
			$theme->load("forms/user/controls");

			break;
		case "feed":
			if (!isset($get_posts)) exit;

			header("Content-Type: application/atom+xml; charset=UTF-8");
			$get_latest_timestamp = $sql->query(preg_replace("/select (.*?) from/i", "select `created_at` from", $get_posts->queryString));
			$latest_timestamp = (!$get_latest_timestamp->rowCount()) ? 0 : $get_latest_timestamp->fetchColumn() ;

			require "includes/feed.php";
			break;
		case "bookmarklet":
			if (!$visitor->group->can("add_post"))
				error(__("Access Denied"), __("You do not have sufficient privileges to create posts."));
			if (empty($config->enabled_feathers))
				error(__("No Feathers"), __("Please install a feather or two in order to add a post."));

			require "includes/bookmarklet.php";
			break;
		case "page":
			fallback($page, new Page(null, array("where" => "`url` = :url", "params" => array(":url" => $_GET['url']))));

			if (empty($page->no_results)) {
				$theme->title = $page->title;

				$page->body = $trigger->filter("markup_page_text", $page->body);

				if (file_exists(THEME_DIR."/content/".$page->url.".php"))
					$theme->load("content/".$page->url, array("page" => $page));
				else
					$theme->load("content/page", array("page" => $page));
			} else
				show_404();

			break;
		default:
			$page_exists = false;
			foreach ($config->enabled_modules as $module)
				if (file_exists(MODULES_DIR."/".$module."/pages/".$action.".php"))
					$page_exists = require MODULES_DIR."/".$module."/pages/".$action.".php";

			foreach ($config->enabled_feathers as $feather)
				if (file_exists(FEATHERS_DIR."/".$feather."/pages/".$action.".php"))
					$page_exists = require FEATHERS_DIR."/".$feather."/pages/".$action.".php";

			if (file_exists(THEME_DIR."/pages/".$action.".php"))
				$page_exists = $theme->load("pages/".$action);

			if (!$page_exists)
				show_404();

			break;
	}

	$trigger->call("bottom");
	$sql->db = null;
	ob_end_flush();
