<?php
	require_once "includes/common.php";

	$trigger->call("top");

	switch($route->action) {
		case "index": case "search": case "drafts": case "feather":
			$context = array("posts" => $posts);

			if ($route->action == "feather") {
				$theme->title = ucfirst($_GET['feather']);
				$context["feather"] = $_GET['feather'];
			} elseif ($route->action == "search") {
				$theme->title = fix(_f("Search results for \"%s\"", array(urldecode($_GET['query'])), "html"));
				$context["search"] = urldecode($_GET['query']);
			} elseif ($route->action == "drafts")
				$theme->title = __("Drafts");

			$theme->load(array("pages/".$route->action, "pages/index"), $context);

			break;
		case "view": case "id":
			if ($route->action == "id")
				redirect($post->url());

			if (!$post->theme_exists())
				error(__("Error"), __("The feather theme file for this post does not exist. The post cannot be displayed."));

			$theme->title = $post->title();

			$post->date_shown = true;

			$theme->load(array("pages/view", "pages/id", "pages/index"), array("post" => $post, "posts" => array($post)));
			break;
		case "page":
			fallback($page, new Page(null, array("where" => "`url` = :url", "params" => array(":url" => $_GET['url']))));

			if (!$page->no_results) {
				$theme->title = $page->title;

				$page->body = $trigger->filter("markup_page_text", $page->body);

				if ($theme->file_exists("pages/".$page->url))
					$theme->load("pages/".$page->url, array("page" => $page));
				else if (file_exists(THEME_DIR."/content/$page->url.twig"))
					$theme->load("content/".$page->url, array("page" => $page));
				else
					$theme->load("content/page", array("page" => $page));
			} else
				show_404();

			break;
		case "archive":
			fallback($_GET['year']);
			fallback($_GET['month']);
			fallback($_GET['day']);
			if (empty($_GET['year']) or empty($_GET['month'])) {
				$theme->title = __("Archive");

				if (!empty($_GET['year']))
					$timestamps = $sql->select("posts",
					                           array("DISTINCT YEAR(`created_at`) AS `year",
					                                 "MONTH(`created_at`) AS `month`",
					                                 "`created_at`",
					                                 "COUNT(`id`) AS `posts`"),
					                           array("YEAR(`created_at`) = :year"),
					                           "`created_at` DESC, `id` DESC",
					                           array(":year" => $_GET['year']),
					                           null, null,
					                           array("YEAR(`created_at`)", "MONTH(`created_at`)"));
				else
					$timestamps = $sql->select("posts",
					                           array("DISTINCT YEAR(`created_at`) AS `year",
					                                 "MONTH(`created_at`) AS `month`",
					                                 "`created_at`",
					                                 "COUNT(`id`) AS `posts`"),
					                           null,
					                           "`created_at` DESC, `id` DESC",
					                           array(),
					                           null, null,
					                           array("YEAR(`created_at`)", "MONTH(`created_at`)"));

				$archives = array();
				while ($time = $timestamps->fetchObject()) {
					$timestamp = mktime(0, 0, 0, $time->month + 1, 0, $time->year);
					$archives[$timestamp] = array("posts" => array(),
					                              "year" => $time->year,
					                              "month" => strftime("%B", $timestamp),
					                              "timestamp" => $timestamp,
					                              "url" => $route->url("archive/".when("Y/m/", $time->created_at)));

					$archives[$timestamp]["posts"] = Post::find(array("where" => "`__posts`.`created_at` like :created_at",
					                                                  "params" => array(":created_at" => when("Y-m", $time->created_at)."%")));
				}

				$theme->load("pages/archive", array("archives" => $archives));
			} else {
				if (!is_numeric($_GET['year']) or !is_numeric($_GET['month']))
					error(__("Error"), __("Please enter a valid year and month."));

				$timestamp = mktime(0, 0, 0, $_GET['month'], fallback($_GET['day'], "1", true), $_GET['year']);
				$theme->title = _f("Archive of %s", array(strftime("%B %Y", $timestamp)));

				$depth = isset($_GET['day']) ? "day" : (isset($_GET['month']) ? "month" : (isset($_GET['year']) ? "year" : ""));
				$theme->load("pages/archive", array("posts" => $posts,
				                                    "archive" => array("year" => $_GET['year'],
				                                                       "month" => strftime("%B", $timestamp),
				                                                       "day" => strftime("%d", $timestamp),
				                                                       "timestamp" => $timestamp,
				                                                       "depth" => $depth)
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
					$sent = @mail($user->email, __("Lost Password Request"), _f("%s,\n\nWe have received a request for a new password for your account at %s.\n\nPlease log in with the following password, and feel free to change it once you've successfully logged in:\n\t%s", array($user->login, $config->name, $new_password)));
				}
			}

			$theme->title = __("Lost Password");
			$theme->load("forms/user/lost_password", array("sent" => $sent, "invalid_user" => $invalid_user));

			break;
		case "feed":
			if (!isset($posts)) exit;

			header("Content-Type: application/atom+xml; charset=UTF-8");

			$latest_timestamp = 0;
			foreach ($posts->paginated as $post)
				if (strtotime($post->created_at) > $latest_timestamp)
					$latest_timestamp = strtotime($post->created_at);

			require "includes/feed.php";
			break;
		case "bookmarklet":
			if (!$visitor->group()->can("add_post"))
				error(__("Access Denied"), __("You do not have sufficient privileges to create posts."));
			if (empty($config->enabled_feathers))
				error(__("No Feathers"), __("Please install a feather or two in order to add a post."));

			require "includes/bookmarklet.php";
			break;
		default:
			# Unknown action. Check for:
			#     1. Module-provided pages.
			#     2. Feather-provided pages.
			#     3. Theme-provided pages.

			foreach ($config->enabled_modules as $module)
				if (file_exists(MODULES_DIR."/".$module."/pages/".$route->action.".php"))
					return require MODULES_DIR."/".$module."/pages/".$route->action.".php";

			foreach ($config->enabled_feathers as $feather)
				if (file_exists(FEATHERS_DIR."/".$feather."/pages/".$route->action.".php"))
					return require FEATHERS_DIR."/".$feather."/pages/".$route->action.".php";

			if ($theme->file_exists("pages/".$route->action))
				return $theme->load("pages/".$route->action);

			if (!$page_exists)
				show_404();

			break;
	}

	$trigger->call("bottom");
	ob_end_flush();
