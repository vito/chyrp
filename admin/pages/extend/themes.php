					<h1><?php echo __("Themes"); ?></h1>
<?php if (isset($_GET['changed'])): ?>
					<div class="success"><?php echo __("Theme changed."); ?></div>
<?php endif; ?>
					<h2><?php echo __("Current Theme"); ?></h2>
<?php
	$info = Spyc::YAMLLoad(THEME_DIR."/info.yaml");

	$image = (file_exists(THEMES_DIR."/".$config->theme."/screenshot.png")) ? $config->url."/themes/".$config->theme."/screenshot.png" : $config->url."/admin/images/noscreenshot.png" ;
?>
					<div class="theme-enabled">
						<img src="<?php echo $image; ?>" alt="<?php echo __($info["name"], "theme"); ?>" title="<?php echo __($info["name"], "theme"); ?>" class="screenshot" />
						<h3>
							<a href="<?php echo $info["url"]; ?>"><?php echo __($info["name"], "theme"); ?></a>
							<span class="sub">
								v<strong><?php echo $info["version"]; ?></strong>
								<?php echo sprintf(__("by <strong><a href=\"%s\">%s</a></strong>"), $info["author"]["url"], $info["author"]["name"]); ?>
							</span>
						</h3>
<?php if (isset($info["description"])): ?>
						<?php echo nl2br(__($info["description"], "theme")); ?>
						<br />
						<br />
<?php endif; ?>
						<?php echo sprintf(__("This theme's files are located at <code>%s</code>"), "/themes/".$config->theme); ?>
					</div>
					<br class="clear" />
					<br />
					<br />
					<h2><?php echo __("Available Themes"); ?></h2>
					<?php echo __("Click on a theme's screenshot to use it."); ?><br />
					<br />
<?php
  if ($open = opendir(THEMES_DIR)) {
    while (($folder = readdir($open)) !== false) {
      if ($folder != $config->theme and file_exists(THEMES_DIR."/".$folder."/info.yaml")) {
				if (file_exists(THEMES_DIR."/".$folder."/locale/".$config->locale.".mo"))
					load_translator($folder, THEMES_DIR."/".$folder."/locale/".$config->locale.".mo");

				$info = Spyc::YAMLLoad(THEMES_DIR."/".$folder."/info.yaml");

				$image = (file_exists(THEMES_DIR."/".$folder."/screenshot.png")) ? $config->url."/themes/".$folder."/screenshot.png" : $config->url."/admin/images/noscreenshot.png" ;
?>
					<div class="theme">
						<h3>
							<a href="<?php echo $info["url"]; ?>"><?php echo __($info["name"], $folder); ?></a>
							<span class="sub">
								v<strong><?php echo $info["version"]; ?></strong>
							</span>
						</h3>
						<a class="screenshot" href="<?php echo $config->url."/admin/?action=change_theme&amp;theme=".$folder; ?>"><img src="<?php echo $image; ?>" alt="<?php echo __($info["name"], $folder); ?>" title="<?php echo __($info["name"], $folder); ?>" /></a>
						<br />
						<?php echo sprintf(__("by <strong><a href=\"%s\">%s</a></strong>"), $info["author"]["url"], $info["author"]["name"]); ?><br />
						<a class="button preview" href="<?php echo $route->url("theme_preview/".$folder."/"); ?>" target="_blank"><img src="icons/appearance.png" alt="appearance" /> <?php echo __("Preview"); ?></a>
					</div>
<?php
			}
    }
    closedir($open);
  }
?>
					<br class="clear" />