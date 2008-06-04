<?php
	require_once "./includes/common.php";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title><?php echo __("Chyrp Upgrader"); ?></title>
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
			select {
				margin-bottom: 1em;
			}
			.center {
				text-align: center;
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
		<div class="window">
<?php
	$current_version = 2000;

	function to_1030() {
		$sql = SQL::current();
		$sql->query("rename table `__tweets` to `__posts`");
		$sql->query("alter table `__groups`
		             change `add_tweet` `add_post` tinyint(1) not null default '0'");
		$sql->query("alter table `__groups`
		             change `edit_tweet` `edit_post` tinyint(1) not null default '0'");
		$sql->query("alter table `__groups`
		             change `delete_tweet` `delete_post` tinyint(1) not null default '0'");
		echo "<p>".sprintf(__("Upgrading to %s&hellip;"), "v1.0.3")."</p>\n";
	}
	function to_1040() {
		$sql = SQL::current();
		$sql->query("alter table `__pages`
		             add `parent_id` int(11) not null default '0' after `user_id`");
		echo "<p>".sprintf(__("Upgrading to %s&hellip;"), "v1.0.4a")."</p>\n";
	}
	function to_1100() {
		$sql = SQL::current();
		$sql->query("alter table `__pages`
		             add `list_order` int(11) not null default '0' after `show_in_list`");

		echo "<p>".sprintf(__("Upgrading to %s&hellip;"), "v1.1")."</p>\n";
	}
	function to_1130() {
		$config = Config::current();
		$config->set("secure_hashkey", md5(random(32, true)));
		echo "<p>".sprintf(__("Upgrading to %s&hellip;"), "v1.1.3")."</p>\n";
	}
	function to_2000() {
		$config = Config::current();
		$config->set("uploads_path", "/uploads/");
		$config->set("chyrp_url", $config->url);
		echo "<p>".sprintf(__("Upgrading to %s&hellip;"), "v2.0")."</p>\n";
	}

	if (!empty($_POST)) {
?>
			<h1><?php echo __("Upgrading&hellip;"); ?></h1>
<?php
		for ($i = (int) $_POST['version']; $i <= $current_version; $i++) {
			$function = "to_".($i + 1); # It's "to", not "from", so add 1
			if (is_callable($function))
				call_user_func($function);
		}
?>
			<p><?php echo __("All done!"); ?></p>
			<a class="done" href="<?php echo $config->url; ?>"><?php echo __("Take me to my site! &rarr;"); ?></a>
<?php
	} else {
?>
			<h1><?php echo __("Upgrade"); ?></h1>
			<form action="upgrade.php" method="post" accept-charset="utf-8">
				<label for="version"><?php echo __("What are you upgrading from?"); ?></label>
				<select name="version">
					<option value="1130">1.1.3.x</option>
					<option value="1100">1.1.x</option>
					<option value="1040">1.0.4a</option>
					<option value="1030">1.0.3</option>
					<option value="1020">1.0.2</option>
					<option value="1010">1.0.1</option>
					<option value="1000">1.0.0</option>
				</select>
				<p class="center"><input type="submit" value="<?php echo __("Upgrade &rarr;"); ?>"></p>
			</form>
<?php
	}
?>
		</div>
	</body>
</html>
