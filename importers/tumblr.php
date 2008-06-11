<?php
	require_once "../includes/common.php";

	if (!in_array("text", $config->enabled_feathers) or !in_array("video", $config->enabled_feathers) or !in_array("audio", $config->enabled_feathers) or !in_array("chat", $config->enabled_feathers) or !in_array("photo", $config->enabled_feathers) or !in_array("quote", $config->enabled_feathers) or !in_array("link", $config->enabled_feathers))
		error(__("Missing Feather"), __("Importing from Tumblr requires the Text, Video, Audio, Chat, Photo, Quote, and Link feathers to be installed and enabled."));

	if (!$visitor->group()->can("add_post"))
		error(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

	function reverse($a, $b) {
		if (empty($a) or empty($b)) return 0;
		return ($a->attributes()->id < $b->attributes()->id) ? -1 : 1 ;
	}

	$errors = array();

	if (!file_exists(MAIN_DIR.$config->uploads_path))
		$errors[] = _f("Please create the <code>%s</code> directory at your Chyrp install's root and CHMOD it to 777.", array($config->uploads_path));
	elseif (!is_writable(MAIN_DIR.$config->uploads_path))
		$errors[] = _f("Please CHMOD <code>%s</code> to 777.", array($config->uploads_path));

	if (!empty($_POST)) {
		switch($_POST['step']) {
			case "1":
				$url = rtrim($_POST['url'], "/")."/api/read?num=50";
				$xml = simplexml_load_string(get_remote($url));

				$already_in = $posts = array();
				foreach ($xml->posts->post as $post) {
					$posts[] = $post;
					$already_in[] = $post->attributes()->id;
				}

				while ($xml->posts->attributes()->total > count($posts)) {
					$xml = simplexml_load_string(get_remote($url."&start=".count($posts)), "SimpleXMLElement", LIBXML_NOCDATA);
					foreach ($xml->posts->post as $post)
						if (!in_array($post->attributes()->id, $already_in)) {
							$posts[] = $post;
							$already_in[] = $post->attributes()->id;
						}
				}

				usort($posts, "reverse");

				foreach ($posts as $post) {
					if ($post->attributes()->type == "audio")
						continue; # Can't import them since Tumblr has them locked in.

					$arr_post = (array) $post;

					$translate_types = array("regular" => "text", "conversation" => "chat");

					switch($post->attributes()->type) {
						case "regular":
							$title = (isset($arr_post["regular-title"])) ? $arr_post["regular-title"] : "" ;

							$values = array("title" => $title, "body" => $arr_post["regular-body"]);
							$clean = sanitize($title);
							break;
						case "video":
							$caption = (isset($arr_post["video-caption"])) ? $arr_post["video-caption"] : "" ;

							$values = array("embed" => $arr_post["video-player"], "caption" => $caption);
							$clean = "";
							break;
						case "conversation":
							$title = (isset($arr_post["conversation-title"])) ? $arr_post["conversation-title"] : "" ;
							$dialogue = trim($arr_post["conversation-text"]);

							$values = array("title" => $title, "dialogue" => $dialogue);
							$clean = sanitize($title);
							break;
						case "photo":
							$arr_post["photo-url"] = $arr_post["photo-url"][0]; # We only need the 500px-size.

							$file = tempnam(sys_get_temp_dir(), "chyrp");
							file_put_contents($file, get_remote($arr_post["photo-url"]));
							$fake_file = array("name" => basename(parse_url($arr_post["photo-url"], PHP_URL_PATH)),
							                   "tmp_name" => $file);
							$filename = upload($fake_file, null, "", true);

							$caption = (isset($arr_post["photo-caption"])) ? $arr_post["photo-caption"] : "" ;

							$values = array("filename" => $filename, "caption" => $caption);
							$clean = "";
							break;
						case "quote":
							$source = preg_replace("/&mdash; /", "", (isset($arr_post["quote-source"]) ? $arr_post["quote-source"] : ""), 1);

							$values = array("quote" => $arr_post["quote-text"], "source" => $source);
							$clean = "";
							break;
						case "link":
							$name = (isset($arr_post["link-text"])) ? $arr_post["link-text"] : "" ;
							$description = (isset($arr_post["link-description"])) ? $arr_post["link-description"] : "" ;

							$values = array("name" => $name, "source" => $arr_post["link-url"], "description" => $description);
							$clean = "";
							break;
					}

					# Damn it feels good to be a gangsta...
					$_POST['status'] = "public";
					$_POST['pinned'] = false;
					$_POST['created_at'] = when("Y-m-d H:i:s", $post->attributes()->date);
					$_POST['feather'] = str_replace(array_keys($translate_types), array_values($translate_types), $post->attributes()->type);

					$new_post = Post::add($values, $clean, Post::check_url($clean));
					$trigger->call("import_tumble", array($post, $new_post));
				}

				$step = "2";
				break;
		}
	}

	$step = (isset($step)) ? $step : "1" ;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title><?php echo __("Chyrp: Import: Tumblr"); ?></title>
		<style type="text/css" media="screen">
			body {
				font: .8em/1.5em normal "Lucida Grande", "Trebuchet MS", Verdana, Helvetica, Arial, sans-serif;
				color: #333;
				background: #eee;
				margin: 0;
				padding: 0;
			}
			.window {
				width: 250px;
				margin: 25px auto;
				padding: 1em;
				border: 1px solid #ddd;
				background: #fff;
			}
			h1 {
				font-size: 1.75em;
				margin-top: 0;
				color: #aaa;
				font-weight: bold;
			}
			label {
				display: block;
				font-weight: bold;
				border-bottom: 1px dotted #ddd;
				margin-bottom: 2px;
			}
			input[type="password"], input[type="text"], textarea {
				margin-bottom: 1em;
				font-size: 1.25em;
				width: 242px;
				padding: 3px;
				border: 1px solid #ddd;
			}
			select {
				margin-bottom: 1em;
			}
			.sub {
				font-size: .8em;
				color: #777;
				font-weight: normal;
			}
			.center {
				text-align: center;
			}
			.error {
				margin: 0 0 1em 0;
				padding: 6px 8px 5px 30px;
				cursor: pointer;
				border-bottom: 1px solid #FBC2C4;
				color: #D12F19;
				background: #FBE3E4 url('../admin/icons/failure.png') no-repeat 7px center;
			}
			.done {
				font-size: 1.25em;
				font-weight: bold;
				text-decoration: none;
				color: #555;
			}
		</style>
	</head>
	<body>
<?php foreach ($errors as $error): ?>
		<div class="error"><?php echo $error; ?></div>
<?php endforeach; ?>
		<div class="window">
<?php
	switch($step):
		case "1":
?>
			<h1><?php echo __("Import Tumblr"); ?></h1>
			<form action="tumblr.php" method="post" accept-charset="utf-8" enctype="multipart/form-data">
				<p><?php echo __("Audio tumbles cannot be imported."); ?></p>
				<label for="xml_file"><?php echo __("Tumblr URL"); ?></label>
				<input type="text" name="url" value="<?php echo fallback($_POST['url'], "", true); ?>" id="url" />
				<br />
				<input type="hidden" name="step" value="1" id="step" />
				<p class="center"><input type="submit" value="<?php echo __("Import &rarr;"); ?>" /></p>
				<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
			</form>
<?php
			break;
		case "2":
?>
			<h1><?php echo __("All done!"); ?></h1>
			<p><?php echo __("All entries have been successfully imported."); ?></p>
			<a href="<?php echo $config->url; ?>" class="done"><?php echo __("View Site &raquo;"); ?></a>
<?php
			break;
	endswitch;
?>
		</div>
	</body>
</html>
