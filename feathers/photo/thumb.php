<?php
	require_once "../../includes/common.php";

    /*
	thumb.php v1.1
	----------------------------------------------------------------------
	Modified by Alex Suraci
	----------------------------------------------------------------------
	Copyright:
		(C) 2003 Chris Tomlinson. christo@mightystuff.net
		http://mightystuff.net

		This library is free software; you can redistribute it and/or
		modify it under the terms of the GNU Lesser General Public
		License as published by the Free Software Foundation; either
		version 2.1 of the License, or (at your option) any later version.

		This library is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
		Lesser General Public License for more details.

		http://www.gnu.org/copyleft/lesser.txt
	----------------------------------------------------------------------
    */

	//script configuration
	ini_set("memory_limit", "64M");

	$thumb_size = 128; //all thumbnails are this maximum width or height if not specified via get
	$image_error = (file_exists(THEME_URL."/images/icons/image_error.png")) ?
	               THEME_URL."/images/icons/image_error.png" :
	               THEME_URL."/img/icons/image_error.png" ;	// used if no image could be found, or a gif image is specified

	$thumb_size_x = 0;
	$thumb_size_y = 0;

	# Define quality of image
	$quality = (isset($_GET["quality"])) ? $_GET["quality"] : 80 ;

	# Define size of image (maximum width or height)- if specified via get.
	$thumb_size = (isset($_GET['size'])) ? (int) $_GET["size"] : 0;

	if (isset($_GET['sizex']) and (int) $_GET["sizex"] > 0) {
		$thumb_size_x = (int) $_GET["sizex"];
		$thumb_size_y = (isset($_GET['sizey']) and (int) $_GET["sizey"] > 0) ? (int) $_GET["sizey"] : $thumb_size_x ;
	}

	$filename = fallback($_GET['file'], $image_error, true);

	$filename = str_replace("\'","'",$filename);
	$filename = rtrim($filename);
	$filename = str_replace("//","/",$filename);
	$fileextension = substr($filename, strrpos($filename, ".") + 1);

	$cache_file = INCLUDES_DIR."/caches/thumb_".md5($filename.$thumb_size.$thumb_size_x.$thumb_size_y.$quality).'.'.$fileextension;

	# remove cache thumbnail?
	if (isset($_GET['nocache']) and $_GET['nocache'] == 1 and file_exists($cache_file))
		unlink($cache_file);

	if (file_exists($cache_file) and filemtime($cache_file) > filemtime($filename)) {
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', @filemtime($cache_file)).' GMT');
		header('Content-type: image/'.($fileextension == "jpg" ? "jpeg" : $fileextension));
		header("Expires: Mon, 26 Jul 2030 05:00:00 GMT");
		header('Content-Disposition: inline; filename='.str_replace("/", "", md5($filename.$thumb_size.$thumb_size_x.$thumb_size_y.$quality).".".$fileextension));
		readfile($cache_file);
		exit; # no need to create thumbnail - it already exists in the cache
	}
exit;
	# determine php and gd versions
	$ver = (int) str_replace(".", "", phpversion());
	if ($ver >= 430)
		$gd_version = gd_info();

	# define the right function for the right image types
	if (!$image_type_arr = getimagesize($filename)) {
		header('Content-type: image/png');

		if (isset($_GET['noerror']) and !$_GET['noerror'])
			readfile($image_error);

		exit;
	}

	$image_type = $image_type_arr[2];
	switch ($image_type) {
		case 2: # JPG
			if (!$image = imagecreatefromjpeg($filename)) {
				# not a valid jpeg file
				$image = imagecreatefrompng($image_error);
				$file_type = "png";
				if (file_exists($cache_file)) # remove the cached thumbnail
					unlink($cache_file);
			}
			break;

		case 3: # PNG
			if (!$image = imagecreatefrompng($filename)) {
				# not a valid png file
				$image = imagecreatefrompng($image_error);
				$file_type="png";
				if (file_exists($cache_file)) # remove the cached thumbnail
					unlink($cache_file);
			}
			break;

		case 1: # GIF
			if (!$image = imagecreatefromgif($filename)) {
				# not a valid gif file
				$image = imagecreatefrompng($image_error);
				$file_type="png";
				if (file_exists($cache_file)) # remove the cached thumbnail
					unlink($cache_file);
			}
			break;
		default:
			$image = imagecreatefrompng($image_error);
			break;

	}

	# define size of original image
	$image_width = imagesx($image);
	$image_height = imagesy($image);

	# define size of the thumbnail
	if ($thumb_size_x > 0) {
		# define images x AND y
		$thumb_width = $thumb_size_x;
		$factor = $image_width / $thumb_size_x;
		$thumb_height = intval($image_height / $factor);
		if ($thumb_height > $thumb_size_y) {
			$thumb_height = $thumb_size_y;
			$factor = $image_height/$thumb_size_y;
			$thumb_width = intval($image_width / $factor);
		}
	} else {
		# define images x OR y
		$thumb_width = $thumb_size;
		$factor = $image_width / $thumb_size;
		$thumb_height = intval($image_height / $factor);
		if ($thumb_height > $thumb_size) {
			$thumb_height = $thumb_size;
			$factor = $image_height / $thumb_size;
			$thumb_width = intval($image_width / $factor);
		}
	}

	# create the thumbnail
	if ($image_width < 4000) { //no point in resampling images larger than 4000 pixels wide - too much server processing overhead - a resize is more economical
		if (substr_count(strtolower($gd_version['GD Version']), "2.") > 0) {
			//GD 2.0
			$thumbnail = ImageCreateTrueColor($thumb_width, $thumb_height);
			imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $thumb_width, $thumb_height, $image_width, $image_height);
		} else {
			//GD 1.0
			$thumbnail = imagecreate($thumb_width, $thumb_height);
			imagecopyresized($thumbnail, $image, 0, 0, 0, 0, $thumb_width, $thumb_height, $image_width, $image_height);
		}
	} else {
		if (substr_count(strtolower($gd_version['GD Version']), "2.")>0) {
			# GD 2.0
			$thumbnail = ImageCreateTrueColor($thumb_width, $thumb_height);
			imagecopyresized($thumbnail, $image, 0, 0, 0, 0, $thumb_width, $thumb_height, $image_width, $image_height);
		} else {
			# GD 1.0
			$thumbnail = imagecreate($thumb_width, $thumb_height);
			imagecopyresized($thumbnail, $image, 0, 0, 0, 0, $thumb_width, $thumb_height, $image_width, $image_height);
		}
	}

	# insert string
	if (!empty($_GET['tag'])) {
		$font = 1;
		$string = $_GET['tag'];
		$white = imagecolorallocate ($thumbnail, 255, 255, 255);
		$black = imagecolorallocate ($thumbnail, 0, 0, 0);
		imagestring($thumbnail, $font, 3, $thumb_height-9, $string, $black);
		imagestring($thumbnail, $font, 2, $thumb_height-10, $string, $white);
	}

	header('Last-Modified: '.gmdate('D, d M Y H:i:s', @filemtime($filename)).' GMT');

	switch ($image_type) {
		case 2: # JPG
			header('Content-type: image/jpeg');
			header('Content-Disposition: inline; filename='.md5($filename.$thumb_size.$thumb_size_x.$thumb_size_y.$quality).'.jpeg');
			imagejpeg($thumbnail, $cache_file, $quality);
			imagejpeg($thumbnail, "", $quality);

			break;
		case 3: # PNG
			header('Content-type: image/png');
			header('Content-Disposition: inline; filename='.md5($filename.$thumb_size.$thumb_size_x.$thumb_size_y.$quality).'.png');
			imagepng($thumbnail, $cache_file);
			imagepng($thumbnail);
			break;

		case 1: # GIF
			if (function_exists('imagegif')) {
				header('Content-type: image/gif');
				header('Content-Disposition: inline; filename='.md5($filename.$thumb_size.$thumb_size_x.$thumb_size_y.$quality).'.gif');
				imagegif($thumbnail, $cache_file);
				imagegif($thumbnail);
			} else {
				header('Content-type: image/jpeg');
				header('Content-Disposition: inline; filename='.md5($filename.$thumb_size.$thumb_size_x.$thumb_size_y.$quality).'.jpg');
				imagejpeg($thumbnail, $cache_file);
				imagejpeg($thumbnail);
			}
			break;
	}

	//clear memory
	imagedestroy($image);
	imagedestroy($thumbnail);
