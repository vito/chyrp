<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head profile="http://gmpg.org/xfn/11">
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title><?php echo $theme->title(); ?></title>
		<meta name="generator" content="Chyrp" />
		<link rel="pingback" href="<?php echo $config->url."/includes/xmlrpc.php"; ?>" />
		<link rel="EditURI" type="application/rsd+xml" href="<?php echo $config->url."/includes/rsd.php"; ?>" />
		<?php $theme->feeds(); ?>
		<?php $theme->stylesheets(); ?>
		<?php $theme->javascripts(); ?>
<?php $trigger->call("head"); ?>
	</head>
	<body>
<?php $trigger->call("controls"); ?>
		<div class="wrapper">
			<div class="container">
				<div class="header">
					<h1><a href="<?php echo $config->url; ?>"><?php echo $config->name; ?></a> <span><?php echo $config->description; ?></span></h1>
				</div>
				<div id="posts">
