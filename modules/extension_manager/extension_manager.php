<?php

    class ExtensionManager extends Modules {

        public function extend_nav($navs) {
            if (Visitor::current()->group->can("toggle_extensions"))
                $navs["extend_manager"] = array("title" => __("Extension Manager", "extension_manager"));

            return $navs;
        }


        static function admin_context($context) {
            if($_GET['action']=='extend_manager'){
                $extensions=file_get_contents('http://chyrp.net/api/v1/chyrp_extensions.php');
                $extensions=explode(',',$extensions);
                array_pop($extensions);
                $extensions=array_reverse($extensions);
                $content='';
                foreach($extensions as $id){
                    $info=file_get_contents('http://chyrp.net/api/v1/chyrp_extensions.php?id='.$id);
                    $name = preg_replace("#\s*.*\[name\](.*?)\[/name\].*\s*#s", '$1', $info);
                    $description = strip_tags(substr(preg_replace("#\s*.*\[description\](.*?)\[/description\].*\s*#s", '$1', $info),0,128)).'…';
                    $download = preg_replace("#\s*.*\[download\](.*?)\[/download\].*\s*#s", '$1', $info);
                    $content.='<div class="extension_manager_item"><h3>'.$name.'</h3>';
                    $content.='<p>'.$description.'<br/>';
                    $content.='<a href="?action=newextension&url='.$download.'/">Download to Site</a></p></div>';
                }
                $context["extensions"]["extensions"] = $content;
                $themes=file_get_contents('http://chyrp.net/api/v1/chyrp_themes.php');
                $themes=explode(',',$themes);
                array_pop($themes);
                $themes=array_reverse($themes);
                $content='';
                foreach($themes as $id){
                    $info=file_get_contents('http://chyrp.net/api/v1/chyrp_themes.php?id='.$id);
                    $name = preg_replace("#\s*.*\[name\](.*?)\[/name\].*\s*#s", '$1', $info);
                    $description = strip_tags(substr(preg_replace("#\s*.*\[description\](.*?)\[/description\].*\s*#s", '$1', $info),0,128)).'…';
                    $download = preg_replace("#\s*.*\[download\](.*?)\[/download\].*\s*#s", '$1', $info);
                    $content.='<div class="extension_manager_item"><h3>'.$name.'</h3>';
                    $content.='<p>'.$description.'<br/>';
                    $content.='<a href="?action=newtheme&url='.$download.'/">Download to Site</a></p></div>';
                }
                $context["extensions"]["themes"] = $content;
                $feathers=file_get_contents('http://chyrp.net/api/v1/chyrp_feathers.php');
                $feathers=explode(',',$feathers);
                array_pop($feathers);
                $feathers=array_reverse($feathers);
                $content='';
                foreach($feathers as $id){
                    $info=file_get_contents('http://chyrp.net/api/v1/chyrp_feathers.php?id='.$id);
                    $name = preg_replace("#\s*.*\[name\](.*?)\[/name\].*\s*#s", '$1', $info);
                    $description = strip_tags(substr(preg_replace("#\s*.*\[description\](.*?)\[/description\].*\s*#s", '$1', $info),0,128)).'…';
                    $download = preg_replace("#\s*.*\[download\](.*?)\[/download\].*\s*#s", '$1', $info);
                    $content.='<div class="extension_manager_item"><h3>'.$name.'</h3>';
                    $content.='<p>'.$description.'<br/>';
                    $content.='<a href="?action=newfeather&url='.$download.'/">Download to Site</a></p></div>';
                }
                $context["extensions"]["feathers"] = $content;
            }
            return $context;
        }
        public function route_newextension(){
        	$fp = fopen ("../modules/latest.zip", 'w+');
        	$ch = curl_init($_GET['url']); # Here is the file we are downloading
        	curl_setopt($ch, CURLOPT_FILE, $fp);
        	curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        	curl_exec($ch);
        	curl_close($ch);
        	fclose($fp);
        	$zip = new ZipArchive();
        	$x = $zip->open("../modules/latest.zip");
        	if ($x == true) {
        		mkdir("../modules/latest");
        		$zip->extractTo("../modules/latest");
        	    $zip->close();
        		$handle=opendir("../modules/latest");
				if($handle) {
					while(($file = readdir($handle)) !== false) {
						if(is_dir("../modules/latest/".$file)){
							rename("../modules/latest/".$file,"../modules/".$file);
						}
					}
				}
				$this->rrmdir("../modules/latest");
        	    unlink("../modules/latest.zip");
        	    $this->rrmdir("../modules/__MACOSX");
        	}
        	header('location: ?action=extend_manager');
    	}
    	public function route_newtheme(){
        	$fp = fopen ("../themes/latest.zip", 'w+');
        	$ch = curl_init($_GET['url']); # Here is the file we are downloading
        	curl_setopt($ch, CURLOPT_FILE, $fp);
        	curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        	curl_exec($ch);
        	curl_close($ch);
        	fclose($fp);
        	$zip = new ZipArchive();
        	$x = $zip->open("../themes/latest.zip");
        	if ($x == true) {
        		mkdir("../themes/latest");
        		$zip->extractTo("../themes/latest");
        	    $zip->close();
        		$handle=opendir("../themes/latest");
				if($handle) {
					while(($file = readdir($handle)) !== false) {
						if(is_dir("../themes/latest/".$file)){
							rename("../themes/latest/".$file,"../themes/".$file);
						}
					}
				}
				$this->rrmdir("../themes/latest");
        	    unlink("../themes/latest.zip");
        	    $this->rrmdir("../themes/__MACOSX");
        	}
        	header('location: ?action=extend_manager');
    	}
    	public function route_newfeather(){
        	$fp = fopen ("../feathers/latest.zip", 'w+');
        	$ch = curl_init($_GET['url']); # Here is the file we are downloading
        	curl_setopt($ch, CURLOPT_FILE, $fp);
        	curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        	curl_exec($ch);
        	curl_close($ch);
        	fclose($fp);
        	$zip = new ZipArchive();
        	$x = $zip->open("../feathers/latest.zip");
        	if ($x == true) {
        		mkdir("../feathers/latest");
        		$zip->extractTo("../feathers/latest");
        	    $zip->close();
        		$handle=opendir("../feathers/latest");
				if($handle) {
					while(($file = readdir($handle)) !== false) {
						if(is_dir("../feathers/latest/".$file)){
							rename("../feathers/latest/".$file,"../modules/".$file);
						}
					}
				}
				$this->rrmdir("../feathers/latest");
        	    unlink("../feathers/latest.zip");
        	    $this->rrmdir("../feathers/__MACOSX");
        	    $this->rrmdir("../feathers/__MACOSX");
        	}
        	header('location: ?action=extend_manager');
    	}
    	//from http://www.php.net/manual/en/function.rmdir.php#98622
		function rrmdir($dir) { 
 			if (is_dir($dir)) { 
 				$objects = scandir($dir); 
 				foreach ($objects as $object) { 
 				  	if ($object != "." && $object != "..") { 
 				    	if (filetype($dir."/".$object) == "dir") $this->rrmdir($dir."/".$object); else unlink($dir."/".$object); 
 					} 
 				} 
 				reset($objects); 
 				rmdir($dir); 
 			} 
 		} 	
    }

