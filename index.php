<?php
	require_once "includes/common.php";

	$trigger->call("top");

	switch($action) {
		case "index": case "search": case "drafts": case "feather":
			$context = array("posts" => $posts);

			if ($action == "feather") {
				$theme->title = ucfirst($_GET['action']);
				$context["feather"] = $_GET['action'];
			} elseif ($action == "search") {
				$theme->title = fix(sprintf(__("Search results for \"%s\""), urldecode($_GET['query'])), "html");
				$context["search"] = urldecode($_GET['query']);
			} elseif ($action == "drafts")
				$theme->title = __("Drafts");

			$theme->load(array("content/".$action, "content/index"), $context);

			break;
		case "view": case "id":
			if ($action == "id")
				redirect($post->url());

			if (!isset($post))
				fallback($post, new Post(null, array("where" => "`url` = :url", "params" => array(":url" => $_GET['url']))));

			if (!$post->theme_exists())
				error(__("Error"), __("The feather theme file for this post does not exist. The post cannot be displayed."));

			$theme->title = $post->title();

			$post->date_shown = true;

			$theme->load(array("content/view", "content/index"), array("post" => $post, "posts" => array($post)));
			break;
		case "archive":
			fallback($_GET['year']);
			fallback($_GET['month']);
			fallback($_GET['day']);
			if (empty($_GET['year']) or empty($_GET['month'])) {
				$theme->title = __("Archive");

				if (!empty($_GET['year']))
					$get_timestamps = $sql->query("select
					                                   distinct year(`created_at`) as `year`,
					                                   month(`created_at`) as `month`,
					                                   `created_at`, count(`id`) as `posts`
					                               from `__posts`
					                               where
					                                   year(`created_at`) = :year and
					                                   ".$private.$enabled_feathers."
					                               group by year(`created_at`), month(`created_at`)
					                               order by `created_at` desc, `id` desc",
					                              array(
					                                  ":year" => $_GET['year']
					                              ));
				else
					$get_timestamps = $sql->query("select
					                                   distinct year(`created_at`) as `year`,
					                                   month(`created_at`) as `month`,
					                                   `created_at`, count(`id`) as `posts`
					                               from `__posts`
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

					$archives[$timestamp]["posts"] = Post::find(array("where" => array($private, "`__posts`.`created_at` like :created_at"),
					                                                    "params" => array(":created_at" => when("Y-m", $time->created_at)."%")));
				}

				$theme->load("content/archive", array("archives" => $archives));
			} else {
				if (!is_numeric($_GET['year']) or !is_numeric($_GET['month']))
					error(__("Error"), __("Please enter a valid year and month."));

				$timestamp = @mktime(0, 0, 0, $_GET['month'], fallback($_GET['day'], "1", true), $_GET['year']);
				$theme->title = sprintf(__("Archive of %s"), @date("F Y", $timestamp));

				$theme->load("content/archive", array("posts" => $posts,
				                                      "archive" => array("year" => $_GET['year'],
				                                                         "month" => @date("F", $timestamp),
				                                                         "day" => @date("jS", $timestamp),
				                                                         "depth" => isset($_GET['day']) ? "day" : (isset($_GET['month']) ? "month" : (isset($_GET['year']) ? "year" : ""))
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
		case "lost_password":
			$sent = false;
			$invalid_user = false;
			if (isset($_POST['login'])) {
				$user = new User(null, array("where" => "`login` = :login", "params" => array(":login" => $_POST['login'])));
				if ($user->no_results)
					$invalid_user = true;
				else {
					$new_password = random(16);
					$user->update($user->login, md5($new_password), $user->full_name, $user->email, $user->website, $user->group_id);
					$sent = @mail($user->email, __("Lost Password Request"), sprintf(__("%s,\n\nWe have received a request for a new password for your account at %s.\n\nPlease log in with the following password, and feel free to change it once you've successfully logged in:\n\t%s"), $user->login, $config->name, $new_password));
				}
			}

			$theme->title = __("Lost Password");
			$theme->load("forms/user/lost_password", array("sent" => $sent, "invalid_user" => $invalid_user));

			break;
		case "feed":
			if (!isset($posts)) exit;

			header("Content-Type: application/atom+xml; charset=UTF-8");

			$latest_timestamp = 0;
			foreach ($posts as $post)
				if (@strtotime($post->created_at) > $latest_timestamp)
					$latest_timestamp = @strtotime($post->created_at);

			require "includes/feed.php";
			break;
		case "bookmarklet":
			if (!$visitor->group()->can("add_post"))
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
	ob_end_flush();
