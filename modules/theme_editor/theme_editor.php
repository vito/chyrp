<?php
    class ThemeEditor extends Modules {
    	public function init(){
    		$this->addAlias("runtime", "admin_context");
    	}
        static function extend_nav($navs) {
            if (Visitor::current()->group->can("change_settings"))
                $navs["theme_editor"] = array("title" => __("Theme Editor", "theme_editor"));

            return $navs;
        }
		static function extend_nav_pages($pages) {
            array_push($pages, "theme_editor");
            return $pages;
        }
        public function admin_context($context) {
            $theme = Config::current()->theme;

            if (isset($_GET['file']) and is_file(THEME_DIR."/".$_GET['file'])) {
                # we have a file
                $context["theme_content"] = file_get_contents(THEME_DIR."/".$_GET['file']);
                $context["theme_path"] = THEME_DIR."/".$_GET['file'];
        	} elseif (isset($_GET['file']) and is_dir(THEME_DIR."/".$_GET['file'])) {
        		# we have a theme
                $context["theme_files"] = self::getFiles(THEME_DIR."/".$_GET['file']);
                $context["theme_get"] = $_GET['file'];
                $context["theme_path"] = THEME_DIR;
			} else {
                $context["theme_files"] = self::getFiles(THEME_DIR);
                $context["theme_get"] = $_GET['file'];
                $context["theme_path"] = THEME_DIR."/".$_GET['file'];
			}
            #$admin->display("pages/admin/theme_editor.twig", array("theme_files" => $context), __("Theme Editor", "theme_editor"));
            return $context;
        }
        public function getFiles($file = null){
        	if (!ADMIN)
        	   return;

            $theme = oneof($_GET['theme']);
            fallback($file, $_GET['file']);

    		if (isset($_GET['file']) and is_file(THEME_DIR."/".$file))
                return file_get_contents(THEME_DIR."/".$file); # we have a file
            elseif (isset($theme) and is_dir(THEME_DIR))
				$filelist = self::getList(); # we have a theme
            else
				$filelist = self::getList($file);

			return $filelist;
    	}
    	public function getList($dir = "themes"){
    		$filelist=array();
    		if (is_dir($dir)) {
				$handle = opendir($dir);
				if ($handle){
					while(($file = readdir($handle)) !== false){
						$extension=substr(strrchr($file,'.'),1);
						if (substr($file, 0, 1) != '.' and ($extension == "twig" or $extension == "css" or $extension=="")){
							array_push($filelist, $file);
						}
					}
				}
            }
			return $filelist;
    	}
    	static function admin_theme_editor_files($admin){
    		if (empty($_POST))
                return $admin->display("theme_editor");

            if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
                show_403(__("Access Denied"), __("Invalid security key."));

            $config = Config::current();
            file_put_contents($_POST['file'], $_POST['content']);
            //set the configs to our post
            if (!in_array(false, $set))
                Flash::notice(__("File Saved"), "/admin/?action=theme_editor");
    	}
    }
?>