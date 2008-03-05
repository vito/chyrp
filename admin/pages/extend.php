<?php
	$sub = (isset($_GET['sub'])) ? $_GET['sub'] : "modules" ;
	if (!$user->can("change_settings"))
		error(__("Access Denied"), __("You do not have sufficient privileges to extend this site."));
?>
		<ul class="sub-nav">
			<li<?php admin_selected("extend", "modules"); ?>><a href="<?php url("extend", "modules"); ?>"><?php echo __("Modules"); ?></a></li>
			<li<?php admin_selected("extend", "feathers"); ?>><a href="<?php url("extend", "feathers"); ?>"><?php echo __("Feathers"); ?></a></li>
			<li<?php admin_selected("extend", "themes"); ?>><a href="<?php url("extend", "themes"); ?>"><?php echo __("Themes"); ?></a></li>
		</ul>
		<br class="clear" />
		<div class="content">
<?php require "extend/".$sub.".php"; ?>
		</div>