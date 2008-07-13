<?php
	require_once "common.php";

	if (ini_get("memory_limit") < 48)
		ini_set("memory_limit", "48M");

	$gd_info = gd_info();
	$gd_version = (substr_count(strtolower($gd_info["GD Version"]), "2.")) ? 2 : 1 ;

	$quality = fallback($_GET["quality"], 80);
	$filename = rtrim(fallback($_GET['file']));
	$extension = pathinfo($filename, PATHINFO_EXTENSION);

	$new_width = (int) fallback($_GET["max_width"]);
	$new_height = (int) fallback($_GET["max_height"]);

	function display_error($string) {
		global $new_width;
		$thumbnail = imagecreatetruecolor($new_width, 12);
		imagestring($thumbnail, 1, 2, 2, $string, imagecolorallocate($thumbnail, 255, 255, 255));
		header("Content-type: image/png");
		header("Content-Disposition: inline; filename=error.png");
		imagepng($thumbnail);
		exit;
	}

	if (!file_exists($filename))
		display_error("Image Not Found");

	list($original_width, $original_height, $type, $attr) = getimagesize($filename);

	# If it's already below the maximum, just redirect to it.
	if ($original_width < $new_width and $original_height < $new_height)
		header("Location: ".$filename);

	$cache_filename = md5($filename.$new_width.$new_height.$quality).".".$extension;
	$cache_file = INCLUDES_DIR."/caches/thumb_".$cache_filename;

	if (isset($_GET['no_cache']) and $_GET['no_cache'] == "true" and file_exists($cache_file))
		unlink($cache_file);

	# Serve a cache if it exists and the original image has not changed.
	if (file_exists($cache_file) and filemtime($cache_file) > filemtime($filename)) {
		header("Last-Modified: ".gmdate('D, d M Y H:i:s', @filemtime($cache_file)).' GMT');
		header("Content-type: image/".($extension == "jpg" ? "jpeg" : $extension));
		header("Expires: ".now("+30 seconds")->format("r"));
		header("Content-Disposition: inline; filename=".$cache_filename);
		readfile($cache_file);
		exit;
	}

	# Verify that the image is able to be thumbnailed, and prepare variables used later in the script.
	switch ($type) {
		case IMAGETYPE_GIF:
			$image = imagecreatefromgif($filename);
			$done = (function_exists("imagegif")) ? "imagegif" : "imagejpeg" ;
			$mime = (function_exists("imagegif")) ? "image/gif" : "image/jpeg" ;
			break;

		case IMAGETYPE_JPEG:
			$image = imagecreatefromjpeg($filename);
			$done = "imagejpeg";
			$mime = "image/jpeg";
			break;

		case IMAGETYPE_PNG:
			$image = imagecreatefrompng($filename);
			$done = "imagepng";
			$mime = "image/png";
			break;

		case IMAGETYPE_BMP:
			$image = imagecreatefromwbmp($filename);
			$done = "imagewbmp";
			$mime = "image/bmp";
			break;

		default:
			display_error("Unsupported image type.");
			break;
	}

	if (!$image)
		display_error("Image could not be created.");

	# Determine the final scale of the thumbnail.
	if ($new_width and !$new_height)
		$new_height = ($new_width / $original_width) * $original_height;
	elseif (!$new_width and $new_height)
		$new_width = ($new_height / $original_height) * $original_width;
	elseif ($new_width and $new_height) {
		if ($original_width > $original_height)
			$new_height = ($new_width / $original_width) * $original_height;
		else
			$new_width = ($new_height / $original_height) * $original_width;
	} else
		display_error("Maxium width and height must be greater than zero.");

	# Decide what functions to use.
	$create = ($gd_version == 2) ? "imagecreatetruecolor" : "imagecreate" ;
	$copy = ($gd_version == 2 and $original_width < 4096) ? "imagecopyresampled" : "imagecopyresized" ;

	# Create the final resized image.
	$thumbnail = $create($new_width, $new_height);
	$copy($thumbnail, $image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

	header("Last-Modified: ".gmdate("D, d M Y H:i:s", @filemtime($filename))." GMT");
	header("Content-type: ".$mime);
	header("Content-Disposition: inline; filename=".$cache_filename.".".$extension);

	# Generate the cache image.
	if (!isset($_GET['no_cache']) or $_GET['no_cache'] == "false")
		if ($done == "imagejpeg")
			$done($thumbnail, $cache_file, $quality);
		else
			$done($thumbnail, $cache_file);

	# Serve the image.
	if ($done == "imagejpeg")
		$done($thumbnail, "", $quality);
	else
		$done($thumbnail);

	# Clear memory.
	imagedestroy($image);
	imagedestroy($thumbnail);
