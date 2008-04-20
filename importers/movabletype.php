<?php
	require_once "../includes/common.php";

	if (!in_array("text", $config->enabled_feathers))
		error(__("Missing Feather"), __("Importing from MovableType requires the Text feather to be installed and enabled."));

	if (!$user->can("add_post"))
		error(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

	$errors = array();
	if (!empty($_POST)) {
		switch($_POST['step']) {
			case "1":
				$txt = file_get_contents($_FILES['txt_file']['tmp_name']);
				$split = explode("--------", $txt);

				foreach ($split as $the_post) {
					preg_match("/TITLE: ([^\n]+)\n/s", $the_post, $title);
					preg_match("/BASENAME: ([^\n]+)\n/s", $the_post, $basename);
					preg_match("/STATUS: ([^\n]+)\n/s", $the_post, $status);
					preg_match("/DATE: ([^\n]+)\n/s", $the_post, $date);
					preg_match("/BODY:\n(.*?)\n-----/s", $the_post, $body);
					preg_match("/EXTENDED BODY:\n(.*?)\n-----/s", $the_post, $extended);
					preg_match("/EXCERPT:\n(.*?)\n-----/s", $the_post, $excerpt);

					if (empty($body)) continue;

					$status_translate = array("Publish" => "public", "Draft" => "draft", "Future" => "draft");

					$title = (empty($title)) ? "" : $title[1] ;
					$body = (!empty($extended[1])) ? $body[1]." <!--more-->\n".$extended[1] : $body[1] ;

					$values = array("title" => $title, "body" => $body);
					$status = (empty($status)) ? "public" : str_replace(array_keys($status_translate), array_values($status_translate), $status[1]) ;
					$clean = (empty($basename)) ? sanitize($title) : str_replace("_", "-", $basename[1]) ;
					$url = Post::check_url($clean);
					$timestamp = (empty($date)) ? datetime() : @date("Y-m-d H:i:s", strtotime($date[1])) ;

					# Damn it feels good to be a gangsta...
					$_POST['status'] = $status;
					$_POST['pinned'] = false;
					$_POST['created_at'] = $timestamp;
					$id = Post::add($values, $clean, $url);

					$trigger->call("import_movabletype_post", array($the_post, $id));
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
		<title><?php echo __("Chyrp: Import: MovableType"); ?></title>
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
			<h1><?php echo __("Import MovableType"); ?></h1>
			<p><?php echo __("Please upload your MovableType .txt file."); ?></p>
			<form action="movabletype.php" method="post" accept-charset="utf-8" enctype="multipart/form-data">
				<label for="txt_file"><?php echo __("MovableType .TXT File"); ?></label>
				<input type="file" name="txt_file" value="" id="txt_file" />
				<br />
				<br />
				<input type="hidden" name="step" value="1" id="step" />
				<input type="hidden" name="feather" value="text" id="feather" />
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
