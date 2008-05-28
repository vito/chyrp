<?php
	require_once "../includes/common.php";

	if (!$visitor->group()->can("add_post"))
		error(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

	$sql->query("TRUNCATE TABLE `chyrp_posts`");
	$sql->query("TRUNCATE TABLE `chyrp_pages`");
	$errors = array();
	if (!empty($_POST)) {
		switch($_POST['step']) {
			case "1":
				if (isset($_FILES['posts_file']) and $_FILES['posts_file']['error'] == 0) {
					$posts = simplexml_load_file($_FILES['posts_file']['tmp_name']);

					if ($posts and $posts->generator == "Chyrp") {
						foreach ($posts->entry as $entry) {
							$chyrp = $entry->children("http://chyrp.net/export/1.0/");

							$_POST['feather'] = $chyrp->feather;
							$_POST['status']  = $chyrp->status;
							$_POST['pinned']  = (bool) (int) $chyrp->pinned;
							$_POST['created_at'] = $chyrp->created_at;
							$_POST['updated_at'] = $chyrp->updated_at;

							$data = Post::xml2arr($entry->content->post);
							$post = Post::add($data, $chyrp->clean, Post::check_url($chyrp->url));

							$trigger->call("import_chyrp_post", array($entry, $post));
						}
						$step = "2";
					} else
						$errors[] = __("Posts file does not seem to be a valid Chyrp export file.");
				}

				if (empty($errors) and isset($_FILES['pages_file']) and $_FILES['pages_file']['error'] == 0) {
					$pages = simplexml_load_string(file_get_contents($_FILES['pages_file']['tmp_name']));

					if ($pages and $pages->generator == "Chyrp") {
						foreach ($pages->entry as $entry) {
							$chyrp = $entry->children("http://chyrp.net/export/1.0/");
							$attr  = $entry->attributes("http://chyrp.net/export/1.0/");
							$page = Page::add($entry->title,
							                  $entry->content,
							                  $attr->parent_id,
							                  (bool) (int) $chyrp->show_in_list,
							                  $chyrp->list_order,
							                  $chyrp->clean,
							                  Page::check_url($chyrp->url));

							$trigger->call("import_chyrp_page", array($entry, $page));
						}

						$step = "2";
					} else
						$errors[] = __("Pages file does not seem to be a valid Chyrp export file.");
				}

				$trigger->call("chyrp_import", array(&$errors, &$step));

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
		<title><?php echo __("Chyrp: Import"); ?></title>
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
			<h1><?php echo __("Import Chyrp"); ?></h1>
			<p><?php echo __("Please upload the .atom files for what you would like to import."); ?></p>
			<form action="chyrp.php" method="post" accept-charset="utf-8" enctype="multipart/form-data">
				<label for="posts_file"><?php echo __("Posts .atom File"); ?></label>
				<input type="file" name="posts_file" value="" id="posts_file" />
				<br />
				<br />
				<label for="pages_file"><?php echo __("Pages .atom File"); ?></label>
				<input type="file" name="pages_file" value="" id="pages_file" />
				<br />
				<br />
				<?php $trigger->call("chyrp_import_fields"); ?>
				<input type="hidden" name="step" value="1" id="step" />
				<p class="center"><input type="submit" value="<?php echo __("Import &rarr;"); ?>" /></p>
				<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
			</form>
<?php
			break;
		case "2":
?>
			<h1><?php echo __("All done!"); ?></h1>
			<p><?php echo __("All posts and pages have been successfully imported."); ?></p>
			<a href="<?php echo $config->url; ?>" class="done"><?php echo __("View Site &raquo;"); ?></a>
<?php
			break;
	endswitch;
?>
		</div>
	</body>
</html>
