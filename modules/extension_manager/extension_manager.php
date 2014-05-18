<?php
    class ExtensionManager extends Modules {

        public function extend_nav($navs) {
            if (Visitor::current()->group->can("toggle_extensions"))
                $navs["extend_manager"] = array("title" => __("Extension Manager", "extension_manager"));

            return $navs;
        }

        static function admin_context($context) {
            if ($_GET['action'] == "extend_manager") {
                $types = array('feathers', 'modules', 'themes');
                
                foreach ($types as $type) {
                    $ids = file_get_contents("http://chyrp.net/api/1/extensions.php?type=$type");
                    $ids = explode(",", $ids);
                    array_pop($ids);
                    $ids = array_reverse($ids);
                    
                    $content = array();
                    foreach($ids as $id) {
                        $info = file_get_contents("http://chyrp.net/api/1/extensions.php?id=$id");
                        $data = json_decode($info, true);
                        $content[] = array('name' => $data['name'],
                                           'description' => $data['description'],
                                           'version' => $data['version'],
                                           'download' => $data['download']);
                    }
                    
                    $context["extensions"]["$type"] = $content;
                }
            }
            return $context;
        }

        public function route_makeRequest(){
            $type = pluralize(strip_tags($_GET['type']));

            set_time_limit(0);

            $fp = fopen("../{$type}/latest.zip", 'w+');
            $url = str_replace(" ", "%20", strip_tags($_GET['url']));

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_FILE, $fp); # write curl response to file
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);

            $zip = new ZipArchive;
            if ($zip->open("../{$type}/latest.zip") == true) {
                mkdir("../{$type}/latest", 0777);
                $zip->extractTo("../{$type}/latest");
                $zip->close();
                $handle = opendir("../{$type}/latest");
                if ($handle) {
                    while (($file = readdir($handle)) !== false) {
                        if (is_dir("../{$type}/latest/{$file}")) {
                            if ($file != '.' and $file != '..') {
                                rename("../{$type}/latest/{$file}", "../{$type}/{$file}");
                            }
                        }
                    }
                }

                $this->rrmdir("../{$type}/latest");
                unlink("../{$type}/latest.zip");
                $this->rrmdir("../{$type}/__MACOSX");
            }

            Flash::notice(__("Extension downloaded successfully.", "extension_manager"), "/admin/?action=extend_manager");
        }
    
        // from http://www.php.net/manual/en/function.rmdir.php#98622
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
