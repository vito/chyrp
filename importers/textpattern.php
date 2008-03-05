<?php
	require_once "../includes/common.php";
	
	if (!in_array("text", $config->enabled_feathers))
		error(__("Missing Feather"), __("Importing from TextPattern requires the Text feather to be installed and enabled."));
	if (!$user->can("add_post"))
		error(__("Access Denied"), __("You do not have sufficient privileges to create posts."));
	
	function fallback($index, $fallback = "") {
		echo (isset($_POST[$index])) ? $_POST[$index] : $fallback ;
	}
	
	$errors = array();
	if (!empty($_POST)) {
		switch($_POST['step']) {
			case "1":
				$dbcon = $dbsel = false;
				$step = "1";
				
				if (!$link = @mysql_connect($_POST['host'], $_POST['username'], $_POST['password'])) {
					$errors[] = "Could not connect to the MySQL server: ".mysql_error();
				} else {
					$dbcon = true;
					if (!@mysql_select_db($_POST['database'], $link))
						$errors[] = "Could not switch to the MySQL database.";
					else $dbsel = true;
				}
				
				if ($dbcon and $dbsel) {
					$get_posts = mysql_query("select * from `".$_POST['prefix']."textpattern` order by `ID` asc", $link);
					$posts = array();
					while ($the_post = mysql_fetch_array($get_posts)) {
						$status_translate = array(1 => "draft", 2 => "private", 3 => "draft", 4 => "public", 5 => "public");
						
						foreach ($the_post as $key => $val)
							$posts[$the_post["ID"]][$key] = $val;
						
						$posts[$the_post["ID"]] = $trigger->filter("import_textpattern_generate_array", $posts[$the_post["ID"]]);
					}
					
					mysql_close($link);
					
					$sql->connect();
				
					foreach ($posts as $the_post) {
						$yaml = Spyc::YAMLDump(array("title" => $the_post["Title"], "body" => $the_post["Body"]));
						$pinned = ($the_post["Status"] == "5");
						$status = str_replace(array_keys($status_translate), array_values($status_translate), $the_post["Status"]);
						$clean = (isset($the_post["url_title"])) ? $the_post["url_title"] : sanitize($the_post["Title"]) ;
						$url = Post::check_url($clean);
						
						# Damn it feels good to be a gangsta...
						$_POST['status'] = $status;
						$_POST['pinned'] = $pinned;
						$_POST['created_at'] = $the_post["Posted"];
						$id = Post::add($yaml, $clean, $url);
						
						$trigger->call("import_textpattern_post", array($the_post, $id));
				
						$step = "2";
					}
				}
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
		<title><?php echo __("Chyrp: Import: TextPattern"); ?></title>
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
			<h1><?php echo __("Import TextPattern"); ?></h1>
			<p><?php echo __("Please enter the database information for your TextPattern installation."); ?></p>
			<form action="textpattern.php" method="post" accept-charset="utf-8">
				<label for="host"><?php echo __("Host"); ?></label>
				<input type="text" name="host" value="<?php fallback("host", "localhost"); ?>" id="host" />
				<label for="username"><?php echo __("Username"); ?></label>
				<input type="text" name="username" value="<?php fallback("username"); ?>" id="username" />
				<label for="password"><?php echo __("Password"); ?></label>
				<input type="password" name="password" value="<?php fallback("password"); ?>" id="password" />
				<label for="database"><?php echo __("Database"); ?></label>
				<input type="text" name="database" value="<?php fallback("database"); ?>" id="database" />
				<label for="prefix"><?php echo __("Table Prefix"); ?> <span class="sub"><?php echo __("(if any)"); ?></span></label>
				<input type="text" name="prefix" value="<?php fallback("prefix"); ?>" id="prefix" />
			
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
