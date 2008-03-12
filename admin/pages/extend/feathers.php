			<h1><?php echo __("Feathers"); ?></h1>
<?php
	if (isset($_GET['enabled'])):
		if (file_exists(FEATHERS_DIR."/".$_GET['enabled']."/locale/".$config->locale.".mo"))
			load_translator($_GET['enabled'], FEATHERS_DIR."/".$_GET['enabled']."/locale/".$config->locale.".mo");
		
		$info = Spyc::YAMLLoad(FEATHERS_DIR."/".$_GET['enabled']."/info.yaml");
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

			<div class="success"><?php echo __("Feather enabled."); ?></div>
<?php
	elseif (isset($_GET['disabled'])):
?>
			<div class="success"><?php echo __("Feather disabled."); ?></div>
<?php
	endif;
	
  if ($open = opendir(FEATHERS_DIR)) {
    while (($folder = readdir($open)) !== false) {
			if (!file_exists(FEATHERS_DIR."/".$folder."/feather.php") or !file_exists(FEATHERS_DIR."/".$folder."/info.yaml")) continue;
			
			if (file_exists(FEATHERS_DIR."/".$folder."/locale/".$config->locale.".mo"))
				load_translator($folder, FEATHERS_DIR."/".$folder."/locale/".$config->locale.".mo");
		
			$info = Spyc::YAMLLoad(FEATHERS_DIR."/".$folder."/info.yaml");
			
			$text = (in_array($folder, $config->enabled_feathers)) ? __("disable") : __("enable") ;
			$icon = (!in_array($folder, $config->enabled_feathers)) ? "success.png" : "deny.png" ;
			$text = (feather_enabled($folder)) ? __("enabled") : __("disabled") ;
			$icon = (!feather_enabled($folder)) ? "deny.png" : "success.png" ;
			$class = (feather_enabled($folder)) ? "enabled" : "disabled" ;
			
			if (strpos($info["description"], "<pre>"))
				$info["description"] = preg_replace("/<pre>(.*?)<\/pre>/se", "'<pre>'.filter_highlight(highlight_string(htmlspecialchars_decode(stripslashes('\\1')), true)).'</pre>'", $info["description"]);
?>
			<div id="feather_<?php echo $folder; ?>" class="box <?php echo $class; ?>">
				<h1>
					<span class="right">
						<a href="<?php echo $config->url."/admin/?action=toggle&amp;feather=".$folder; ?>"><img src="<?php echo $config->url."/admin/icons/".$icon; ?>" alt="<?php echo $text; ?>" /> <?php echo $text; ?></a>
					</span>
					<a href="<?php echo $info["url"]; ?>"><?php echo __($info["name"], $folder); ?></a>&nbsp;v<?php echo $info["version"]; ?> (<a href="<?php echo $info["author"]["url"]; ?>"><?php echo $info["author"]["name"]; ?></a>)
				</h1>
				<div class="excerpt">
					<?php echo nl2br(__($info["description"], $folder)); ?>
				</div>
			</div>
<?php
    }
    closedir($open);
  }
?>