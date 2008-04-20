<?php
	if (defined('AJAX') and AJAX or isset($_POST['ajax']))
	     exit($body."HEY_JAVASCRIPT_THIS_IS_AN_ERROR_JUST_SO_YOU_KNOW");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<title>Chyrp: <?php echo $title; ?></title>
		<style type="text/css">
			html {
				background: #FFF;
				margin: 0;
				padding: 0;
				font: 11px 'Lucida Grande', Verdana, sans-serif;
				color: #777;
			}
			html, body, ul, ol, li,
			h1, h2, h3, h4, h5, h6,
			form, fieldset, a, p {
				margin: 0;
				padding: 0;
				border: 0;
			}
			.window {
				width: 300px;
				margin: 0 auto;
				padding: 0;
			}
			h1 {
				display: block;
				padding: 5px;
				font-size: 14px;
				text-shadow: 5px 0;
				color: #8CC165;
				background: #191919;
				font-family: "HelveticaNeue-Light", "Helvetica Neue Light", "Trebuchet MS", Helvetica, Arial, sans-serif;
				border-bottom: 5px solid #FFF;
				border-top: 5px solid #FFF;
			}
			code {
				color: #6B0;
				font-family: Consolas, Monaco, monospace;
			}
			.footer {
				margin: 10px 0;
				color: #777;
				font-size: 9px;
				text-align: center;
			}
			p {
				padding: 0 10px;
			}
			.error {
				color: #F22;
				font-size: 12px;
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
		<div class="window">
			<h1><?php echo $title; ?></h1>
			<p>
				<?php echo $body; ?>
			</p>
		</div>
		<div class="footer">Chyrp &copy; 2007 Alex Suraci</div>
	</body>
</html>
