<?php
	$title = "Title";
	$body = "Body";
	$trigger->call("help_".$_GET['id']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<title>Chyrp: <?php echo $title; ?></title>
		<style type="text/css">
			html, body, ul, ol, li,
			h1, h2, h3, h4, h5, h6,
			form, fieldset, a, p {
				margin: 0;
				padding: 0;
				border: 0;
			}
			html {
				font-size: 62.5%;
			}
			body {
				background: #FFF;
				margin: 0;
				padding: 0;
				font: 1.25em/1.5em normal "Verdana", Helvetica, Arial, sans-serif;
				color: #777;
			}
			h1 {
				display: block;
				padding: .5em;
				font-size: 1.75em;
				text-shadow: 5px 0;
				color: #0096ff;
				background: #000;
				font-family: "HelveticaNeue-Light", "Helvetica Neue Light", "Trebuchet MS", Helvetica, Arial, sans-serif;
			}
			code {
				color: #6B0;
				font-family: Consolas, Monaco, monospace;
			}
			.body {
				padding: 1em;
			}
			.body p {
				margin: 0 0 1em;
			}
			.body ul {
				margin: 0 0 1em 2em;
			}
			.body ul li {
				margin: 0;
			}
			a:link, a:visited {
				text-decoration: none;
			}
			a.big {
				font-size: 16px;
				color: #6B0;
				font-weight: bold;
			}
			a:hover {
				text-decoration: underline;
			}
		</style>
	</head>
	<body>
		<h1><?php echo $title; ?></h1>
		<div class="body">
			<?php echo $body; ?>
		</div>
	</body>
</html>
