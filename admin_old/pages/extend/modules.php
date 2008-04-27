<?php
	$issues = array();
	$dependencies = array();
	if ($open = opendir(MODULES_DIR)) {
	  while (($folder = readdir($open)) !== false) {
			if (!file_exists(MODULES_DIR."/".$folder."/module.php") or !file_exists(MODULES_DIR."/".$folder."/info.yaml")) continue;

			if (file_exists(MODULES_DIR."/".$folder."/locale/".$config->locale.".mo"))
				load_translator($folder, MODULES_DIR."/".$folder."/locale/".$config->locale.".mo");

			$info = Spyc::YAMLLoad(MODULES_DIR."/".$folder."/info.yaml");

			if (!empty($info["conflicts"])) {
				foreach ($info["conflicts"] as $conflict) {
					$issues[$conflict] = true;
					if (file_exists(MODULES_DIR."/".$conflict."/module.php"))
						$issues[$folder] = true;
				}
			}
			if (!empty($info["depends"])) {
				foreach ($info["depends"] as $dependency)
					if (!in_array($dependency, $config->enabled_modules))
						$dependencies[$folder] = true;
			}
			$info["conflicts"] = array();
			$info["depends"] = array();
		}
	}
?>
			<h1><?php echo __("Modules"); ?></h1>
<?php
	if (isset($_GET['enabled'])):
		if (file_exists(MODULES_DIR."/".$_GET['enabled']."/locale/".$config->locale.".mo"))
			load_translator($_GET['enabled'], MODULES_DIR."/".$_GET['enabled']."/locale/".$config->locale.".mo");

		$info = Spyc::YAMLLoad(MODULES_DIR."/".$_GET['enabled']."/info.yaml");
		fallback($info["uploader"], false);
		fallback($info["notifications"], array());

		if ($info["uploader"])
			if (!file_exists(MAIN_DIR."/upload"))
				$info["notifications"][] = __("Please create the <code>/upload</code> directory at your Chyrp install's root and CHMOD it to 777.");
			elseif (!is_writable(MAIN_DIR."/upload"))
				$info["notifications"][] = __("Please CHMOD <code>/upload</code> to 777.");

		foreach ($info["notifications"] as $message):
?>
			<div class="notice"><?php echo __($message, $_GET['enabled']); ?></div>
<?php
		endforeach;
?>

			<div class="success"><?php echo __("Module enabled."); ?></div>
<?php
	elseif (isset($_GET['disabled'])):
?>
			<div class="success"><?php echo __("Module disabled."); ?></div>
<?php
	endif;

  if ($open = opendir(MODULES_DIR)) {
    while (($folder = readdir($open)) !== false) {
			if (!file_exists(MODULES_DIR."/".$folder."/module.php") or !file_exists(MODULES_DIR."/".$folder."/info.yaml")) continue;

			if (file_exists(MODULES_DIR."/".$folder."/locale/".$config->locale.".mo"))
				load_translator($folder, MODULES_DIR."/".$folder."/locale/".$config->locale.".mo");

			$info = Spyc::YAMLLoad(MODULES_DIR."/".$folder."/info.yaml");

			$text = (module_enabled($folder)) ? __("enabled") : __("disabled") ;
			$icon = (!module_enabled($folder)) ? "deny.png" : "success.png" ;
			$class = (module_enabled($folder)) ? "enabled" : "disabled" ;

			if (strpos($info["description"], "<pre>"))
				$info["description"] = preg_replace("/<pre>(.*?)<\/pre>/se", "'<pre>'.filter_highlight(highlight_string(htmlspecialchars_decode(stripslashes('\\1')), true)).'</pre>'", $info["description"]);
?>
			<div id="module_<?php echo $folder; ?>" class="box <?php echo $class; if (isset($dependencies[$folder])) echo ' depends'; if (isset($issues[$folder])): echo ' conflict'; echo " conflict_".join(" conflict_", $info["conflicts"]); endif; ?>">
				<h1>
					<span class="right">
<?php if (!isset($dependencies[$folder])): ?>
						<a href="<?php echo $config->url."/admin/?action=toggle&amp;module=".$folder; ?>"><img src="<?php echo $config->url."/admin/icons/".$icon; ?>" alt="<?php echo $text; ?>" /> <?php echo $text; ?></a>
<?php else: ?>
						<a class="disable"><img src="<?php echo $config->url."/admin/icons/cancel.png" ?>" alt="depends" /> <?php echo __("can't"); ?></a>
<?php endif; ?>
					</span>
					<a href="<?php echo $info["url"]; ?>"><?php echo __($info["name"], $folder); ?></a>&nbsp;v<?php echo $info["version"]; ?> (<a href="<?php echo $info["author"]["url"]; ?>"><?php echo $info["author"]["name"]; ?></a>)
				</h1>
				<div class="excerpt">
					<?php echo nl2br(__($info["description"], $folder)); ?>
<?php if (isset($dependencies[$folder])): ?>
					<br />
					<br />
					<h3><?php echo __("Unmet Dependencies:"); ?></h3>
					<ul>
						<li><?php echo join($info["depends"], "</li><li>"); ?></li>
					</ul>
<?php endif; ?>
				</div>
			</div>
<?php
			$info["conflicts"] = array();
			$info["depends"] = array();
    }
    closedir($open);
  }
?>
			<h3><?php echo __("Legend:"); ?></h3>
			<span class="legend conflict">&nbsp;</span>: <?php echo __("Conflicting modules"); ?><br />
			<span class="legend depends">&nbsp;</span>: <?php echo __("Unmet dependency"); ?>