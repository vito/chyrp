<?php
	$id = $_GET['id'];
	$feather = Post::info("feather", $_GET['id']);
	
	if (file_exists(FEATHERS_DIR."/".$feather."/locale/".$config->locale.".mo"))
		load_translator($feather, FEATHERS_DIR."/".$feather."/locale/".$config->locale.".mo");
	
	$info = Spyc::YAMLLoad(FEATHERS_DIR."/".$feather."/info.yaml");
	
	require FEATHERS_DIR."/".$feather."/fields.php";
?>
							<input type="hidden" name="feather" value="<?php echo fix($feather, "html"); ?>" id="edit_feather" />